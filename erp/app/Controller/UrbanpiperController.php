<?php
/**
 * UrbanpiperController — Cloud-side integration endpoints for UrbanPiper Downstream API
 *
 * Endpoints:
 *   POST /urbanpiper/webhook/order          — receive new order webhook
 *   POST /urbanpiper/webhook/order_update   — receive order status update
 *   POST /urbanpiper/webhook/menu_callback  — menu publish callback
 *   GET  /urbanpiper/store/pull_jobs        — store connector pulls pending jobs
 *   POST /urbanpiper/store/ack_job          — store connector acks processed job
 *   GET  /urbanpiper/admin/orders           — admin view (session auth)
 *
 * @author  Aether Integration Engineering
 * @version 1.2  2026-03-03 — align downstream API (G1 G3 G4 G5 G13)
 */

App::uses('AppController',             'Controller');
App::uses('UrbanpiperOrderNormalizer', 'Lib/Urbanpiper');
App::uses('UrbanpiperClient',          'Lib/Urbanpiper');
App::uses('UrbanpiperLogger',          'Lib/Urbanpiper');

class UrbanpiperController extends AppController {

    // One model loaded to obtain a live DataSource connection without overhead
    public $uses = array('Location');

    // -------------------------------------------------------------------------
    // CakePHP lifecycle
    // -------------------------------------------------------------------------

    public function beforeFilter() {
        parent::beforeFilter();
        $this->layout     = false;
        $this->autoRender = false;
        error_reporting(0);

        $this->Auth->allow(array(
            'order_webhook', 'order_update_webhook', 'menu_callback_webhook',
            'pull_jobs', 'ack_job'
        ));
    }

    // -------------------------------------------------------------------------
    // DB helpers (CakePHP 2.x DboSource pattern — use value() for escaping)
    // -------------------------------------------------------------------------

    /** @var DboSource */
    private $_ds;

    private function _ds() {
        if (!$this->_ds) {
            $this->_ds = $this->Location->getDataSource();
        }
        return $this->_ds;
    }

    /** Escape a scalar value for safe SQL interpolation */
    private function _v($val) {
        if ($val === null) {
            return 'NULL';
        }
        return $this->_ds()->value($val);
    }

    /** Execute a write query */
    private function _exec($sql) {
        return $this->_ds()->query($sql);
    }

    /** Execute a read query — returns array of rows */
    private function _fetch($sql) {
        $result = $this->_ds()->query($sql);
        return is_array($result) ? $result : array();
    }

    /** Last insert ID */
    private function _lastId() {
        return $this->_ds()->getConnection()->lastInsertId();
    }

    /** Return JSON and halt */
    private function _json($data, $code = 200) {
        $this->response->statusCode($code);
        $this->response->type('json');
        echo json_encode($data);
        $this->response->send();
        exit;
    }

    // -------------------------------------------------------------------------
    // Security helpers
    // -------------------------------------------------------------------------

    /**
     * Verify incoming UrbanPiper webhook using the x_api_token custom header.
     *
     * UrbanPiper does NOT send HMAC body signatures. Auth is via a custom header
     * you define when registering the webhook at POST /external/api/v1/webhooks/:
     *   "headers": {"x_api_token": "<value_of_UP_WEBHOOK_TOKEN>"}
     * UP sends this header verbatim on every webhook POST to your URL.
     *
     * Config key: UrbanPiper.webhook_token  (env var: UP_WEBHOOK_TOKEN)
     * Gap fixed:  G1 — was incorrectly checking HMAC X-UP-Signature
     */
    private function _verifyWebhookSignature($rawBody) {
        $expected = Configure::read('UrbanPiper.webhook_token');
        if (empty($expected)) {
            UrbanpiperLogger::warn('webhook_token not configured — skipping auth check');
            return true;
        }

        // Apache+PHP-FPM may rename headers; try all variants
        $received = $this->request->header('X-Api-Token');
        if (empty($received)) {
            $received = isset($_SERVER['HTTP_X_API_TOKEN'])          ? $_SERVER['HTTP_X_API_TOKEN']          : '';
        }
        if (empty($received)) {
            $received = isset($_SERVER['REDIRECT_HTTP_X_API_TOKEN']) ? $_SERVER['REDIRECT_HTTP_X_API_TOKEN'] : '';
        }

        if (empty($received)) {
            UrbanpiperLogger::warn('webhook auth: x_api_token header missing');
            return false;
        }
        return hash_equals($expected, $received);
    }

    private function _authStore() {
        $storeKey = isset($_GET['store_key']) ? trim($_GET['store_key']) : '';
        if (empty($storeKey)) {
            return false;
        }

        // Read Authorization header — try multiple sources for Apache+PHP-FPM compatibility
        $authHeader = $this->request->header('Authorization');
        if (empty($authHeader)) {
            $authHeader = isset($_SERVER['HTTP_AUTHORIZATION'])          ? $_SERVER['HTTP_AUTHORIZATION']          : '';
        }
        if (empty($authHeader)) {
            $authHeader = isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION']) ? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] : '';
        }
        if (!$authHeader || strpos($authHeader, 'Bearer ') !== 0) {
            return false;
        }
        $token = substr($authHeader, 7);

        $rows = $this->_fetch(
            'SELECT * FROM store_connectors WHERE store_key = ' . $this->_v($storeKey) .
            ' AND is_enabled = 1 LIMIT 1'
        );
        if (empty($rows)) {
            return false;
        }
        // CakePHP DboSource->query(): plain SELECT uses table name as key
        $store = array();
        if (isset($rows[0]['store_connectors'])) {
            $store = $rows[0]['store_connectors'];
        } elseif (isset($rows[0][0])) {
            $store = $rows[0][0];
        } elseif (isset($rows[0])) {
            // Fallback: first numeric row
            $r = reset($rows[0]);
            if (is_array($r)) $store = $r;
        }
        if (empty($store) || empty($store['store_token_hash'])) {
            UrbanpiperLogger::warn('_authStore: store row not found store_key=' . $storeKey);
            return false;
        }

        if (!password_verify($token, $store['store_token_hash'])) {
            return false;
        }

        $this->_exec(
            'UPDATE store_connectors SET last_pull_at = NOW() WHERE store_key = ' . $this->_v($storeKey)
        );

        return $store;
    }

    // -------------------------------------------------------------------------
    // WEBHOOK: POST /urbanpiper/webhook/order
    // -------------------------------------------------------------------------

    public function order_webhook() {
        if (!$this->request->is('post')) {
            $this->_json(array('error' => 'method_not_allowed'), 405);
        }

        $rawBody = file_get_contents('php://input');
        UrbanpiperLogger::info('webhook:order raw_bytes=' . strlen($rawBody));

        if (!$this->_verifyWebhookSignature($rawBody)) {
            UrbanpiperLogger::warn('webhook:order signature_mismatch');
            $this->_json(array('error' => 'unauthorized'), 401);
        }

        $payload = json_decode($rawBody, true);
        if (empty($payload)) {
            UrbanpiperLogger::warn('webhook:order invalid_json');
            $this->_json(array('error' => 'invalid_json'), 400);
        }

        $normalizer  = new UrbanpiperOrderNormalizer();
        $canonical   = $normalizer->normalize($payload);
        $upOrderId   = $canonical['header']['up_order_id'];
        $storeId     = $canonical['header']['store_id'];
        $channel     = $canonical['header']['channel'];
        $orderType   = $canonical['header']['order_type'];
        $paymentMode = $canonical['header']['payment_mode'];
        $displayId   = $canonical['header']['up_display_id'];

        UrbanpiperLogger::info(
            'webhook:order up_order_id=' . $upOrderId . ' store=' . $storeId . ' channel=' . $channel
        );

        // Idempotent insert
        $this->_exec(
            'INSERT IGNORE INTO up_orders
               (store_id, channel, up_order_id, up_order_display_id, status, order_type, payment_mode, raw_payload, created, modified)
             VALUES (' .
                $this->_v($storeId) . ',' .
                $this->_v($channel) . ',' .
                $this->_v($upOrderId) . ',' .
                $this->_v($displayId) . ',' .
                $this->_v('received') . ',' .
                $this->_v($orderType) . ',' .
                $this->_v($paymentMode) . ',' .
                $this->_v($rawBody) .
             ', NOW(), NOW())'
        );

        UrbanpiperLogger::info('webhook:order upserted up_orders up_order_id=' . $upOrderId);

        $this->_exec(
            'INSERT INTO up_order_events
               (up_order_id, store_id, event_type, payload, status, attempt_count, created)
             VALUES (' .
                $this->_v($upOrderId) . ',' .
                $this->_v($storeId) . ',' .
                $this->_v('PUSH_TO_STORE') . ',' .
                $this->_v(json_encode($canonical)) . ',' .
                $this->_v('pending') . ',' .
                '0, NOW())'
        );
        $eventId = $this->_lastId();
        UrbanpiperLogger::info('webhook:order enqueued event_id=' . $eventId . ' type=PUSH_TO_STORE');

        $this->_json(array('status' => 'ok', 'event_id' => (int)$eventId));
    }

    // -------------------------------------------------------------------------
    // WEBHOOK: POST /urbanpiper/webhook/order_update
    // -------------------------------------------------------------------------

    public function order_update_webhook() {
        if (!$this->request->is('post')) {
            $this->_json(array('error' => 'method_not_allowed'), 405);
        }

        $rawBody = file_get_contents('php://input');
        UrbanpiperLogger::info('webhook:order_update raw_bytes=' . strlen($rawBody));

        if (!$this->_verifyWebhookSignature($rawBody)) {
            UrbanpiperLogger::warn('webhook:order_update signature_mismatch');
            $this->_json(array('error' => 'unauthorized'), 401);
        }

        $payload   = json_decode($rawBody, true);
        if (empty($payload)) {
            $this->_json(array('error' => 'invalid_json'), 400);
        }


        // $upOrderId = isset($payload['order']['details']['id'])          ? $payload['order']['details']['id']          : null;
        // $newState  = isset($payload['order']['details']['order_state']) ? $payload['order']['details']['order_state'] : null;

        $upOrderId = isset($payload['order_id']) ? $payload['order_id'] : null;
        $newState  = isset($payload['new_state']) ? $payload['new_state'] : null;

        if (!$upOrderId || !$newState) {
            $this->_json(array('error' => 'missing_fields'), 400);
        }

        $statusMap = array(
            'Acknowledged' => 'acknowledged',
            'Food Ready'   => 'food_ready',
            'Dispatched'   => 'dispatched',
            'Completed'    => 'completed',
            'Cancelled'    => 'cancelled',
        );
        $status = isset($statusMap[$newState]) ? $statusMap[$newState] : 'unknown';

        $this->_exec(
            'UPDATE up_orders SET status = ' . $this->_v($status) . ', modified = NOW()' .
            ' WHERE up_order_id = ' . $this->_v($upOrderId)
        );

        $eventType = ($status === 'cancelled') ? 'CANCEL' : 'UPDATE_STATUS';

        $row = $this->_fetch(
            'SELECT store_id FROM up_orders WHERE up_order_id = ' . $this->_v($upOrderId) . ' LIMIT 1'
        );
        // For plain SELECT (no alias), CakePHP uses table name as key
        $storeId = 'unknown';
        if (!empty($row)) {
            if (isset($row[0]['up_orders']['store_id'])) {
                $storeId = $row[0]['up_orders']['store_id'];
            } elseif (isset($row[0][0]['store_id'])) {
                $storeId = $row[0][0]['store_id'];
            } elseif (isset($row[0]['store_id'])) {
                $storeId = $row[0]['store_id'];
            }
        }

        $this->_exec(
            'INSERT INTO up_order_events
               (up_order_id, store_id, event_type, payload, status, attempt_count, created)
             VALUES (' .
                $this->_v($upOrderId) . ',' .
                $this->_v($storeId) . ',' .
                $this->_v($eventType) . ',' .
                $this->_v(json_encode($payload)) . ',' .
                $this->_v('pending') . ',' .
             '0, NOW())'
        );
        $eventId = $this->_lastId();

        UrbanpiperLogger::info(
            'webhook:order_update up_order_id=' . $upOrderId . ' status=' . $status . ' event_id=' . $eventId
        );

        $this->_json(array('status' => 'ok', 'event_id' => (int)$eventId));
    }

    // -------------------------------------------------------------------------
    // WEBHOOK: POST /urbanpiper/webhook/menu_callback
    // -------------------------------------------------------------------------

    public function menu_callback_webhook() {
        if (!$this->request->is('post')) {
            $this->_json(array('error' => 'method_not_allowed'), 405);
        }

        $rawBody = file_get_contents('php://input');

        if (!$this->_verifyWebhookSignature($rawBody)) {
            UrbanpiperLogger::warn('webhook:menu_callback signature_mismatch');
            $this->_json(array('error' => 'unauthorized'), 401);
        }

        $payload = json_decode($rawBody, true);
        $status  = isset($payload['status'])       ? $payload['status']       : 'unknown';
        $refId   = isset($payload['reference_id']) ? $payload['reference_id'] : '';

        UrbanpiperLogger::info('webhook:menu_callback status=' . $status . ' ref=' . $refId);

        $this->_exec(
            'INSERT INTO up_order_events
               (up_order_id, store_id, event_type, payload, status, attempt_count, created)
             VALUES (' .
                $this->_v('MENU') . ',' .
                $this->_v('global') . ',' .
                $this->_v('MENU_CALLBACK') . ',' .
                $this->_v(json_encode($payload)) . ',' .
                $this->_v('pending') . ',' .
             '0, NOW())'
        );

        $this->_json(array('status' => 'ok'));
    }

    // -------------------------------------------------------------------------
    // STORE PULL: GET /urbanpiper/store/pull_jobs
    // -------------------------------------------------------------------------

    public function pull_jobs() {
        if (!$this->request->is('get')) {
            $this->_json(array('error' => 'method_not_allowed'), 405);
        }

        $store = $this->_authStore();
        if (!$store) {
            UrbanpiperLogger::warn('pull_jobs: auth_failed store_key=' . (isset($_GET['store_key']) ? $_GET['store_key'] : ''));
            $this->_json(array('error' => 'unauthorized'), 401);
        }

        $storeId      = $store['store_id'];
        $storeKey     = $store['store_key'];
        $sinceEventId = isset($_GET['since_event_id']) ? (int)$_GET['since_event_id'] : 0;
        $limit        = 20;

        $rows = $this->_fetch(
            'SELECT e.id, e.up_order_id, e.event_type, e.payload, e.created,' .
            ' o.up_order_display_id, o.channel, o.status as order_status' .
            ' FROM up_order_events e' .
            ' LEFT JOIN up_orders o ON o.up_order_id = e.up_order_id' .
            ' WHERE e.store_id = ' . $this->_v($storeId) .
            ' AND e.status = ' . $this->_v('pending') .
            ' AND e.id > ' . (int)$sinceEventId .
            ' ORDER BY e.id ASC LIMIT ' . $limit
        );

        $jobs = array();
        foreach ($rows as $row) {
            // CakePHP DboSource->query() uses SQL table alias as array key
            // Aliases in our query: 'e' = up_order_events, 'o' = up_orders
            $event = isset($row['e']) ? $row['e'] : array();
            $order = isset($row['o']) ? $row['o'] : array();

            $this->_exec(
                'UPDATE up_order_events SET status = ' . $this->_v('delivered') .
                ', delivered_at = NOW() WHERE id = ' . (int)$event['id']
            );

            $jobs[] = array(
                'event_id'           => (int)$event['id'],
                'up_order_id'        => $event['up_order_id'],
                'up_order_display_id'=> isset($order['up_order_display_id']) ? $order['up_order_display_id'] : null,
                'event_type'         => $event['event_type'],
                'channel'            => isset($order['channel']) ? $order['channel'] : null,
                'order_status'       => isset($order['order_status']) ? $order['order_status'] : null,
                'payload'            => json_decode($event['payload'], true),
                'created'            => $event['created'],
            );
        }

        $lastId = !empty($jobs) ? $jobs[count($jobs) - 1]['event_id'] : $sinceEventId;

        UrbanpiperLogger::info(
            'pull_jobs store=' . $storeKey . ' since=' . $sinceEventId .
            ' returned=' . count($jobs) . ' last_event_id=' . $lastId
        );

        $this->_json(array(
            'store_id'      => $storeId,
            'jobs'          => $jobs,
            'last_event_id' => $lastId,
            'count'         => count($jobs),
        ));
    }

    // -------------------------------------------------------------------------
    // STORE ACK: POST /urbanpiper/store/ack_job
    // -------------------------------------------------------------------------

    public function ack_job() {
        if (!$this->request->is('post')) {
            $this->_json(array('error' => 'method_not_allowed'), 405);
        }

        $store = $this->_authStore();
        if (!$store) {
            $this->_json(array('error' => 'unauthorized'), 401);
        }

        $body      = json_decode(file_get_contents('php://input'), true);
        $eventId   = isset($body['event_id'])     ? (int)$body['event_id']        : 0;
        $upOrderId = isset($body['up_order_id'])  ? trim($body['up_order_id'])     : '';
        $refId     = isset($body['reference_id']) ? trim($body['reference_id'])    : null;
        $success   = isset($body['success'])      ? (bool)$body['success']         : false;
        $errMsg    = isset($body['error'])        ? substr($body['error'], 0, 500) : null;

        if (!$eventId || !$upOrderId) {
            $this->_json(array('error' => 'missing_fields'), 400);
        }

        if ($success) {
            $this->_exec(
                'UPDATE up_order_events SET status = ' . $this->_v('acked') .
                ', acked_at = NOW(), last_error = NULL' .
                ' WHERE id = ' . $eventId . ' AND store_id = ' . $this->_v($store['store_id'])
            );

            if ($refId) {
                $this->_exec(
                    'UPDATE up_orders SET reference_id = ' . $this->_v($refId) . ', modified = NOW()' .
                    ' WHERE up_order_id = ' . $this->_v($upOrderId) .
                    ' AND (reference_id IS NULL OR reference_id = ' . $this->_v($refId) . ')'
                );
            }

            $this->_exec(
                'UPDATE store_connectors SET last_event_id = GREATEST(last_event_id, ' . $eventId . ')' .
                ' WHERE store_id = ' . $this->_v($store['store_id'])
            );

            UrbanpiperLogger::info(
                'ack_job event_id=' . $eventId . ' up_order_id=' . $upOrderId .
                ' reference_id=' . $refId . ' status=ok'
            );

        } else {
            $this->_exec(
                'UPDATE up_order_events SET status = ' . $this->_v('failed') .
                ', attempt_count = attempt_count + 1, last_error = ' . $this->_v($errMsg) .
                ' WHERE id = ' . $eventId . ' AND store_id = ' . $this->_v($store['store_id'])
            );

            UrbanpiperLogger::warn(
                'ack_job event_id=' . $eventId . ' up_order_id=' . $upOrderId .
                ' status=failed error=' . $errMsg
            );
        }

        $this->_json(array('status' => $success ? 'ok' : 'recorded_failure'));
    }

    // -------------------------------------------------------------------------
    // ADMIN: GET /urbanpiper/admin/orders
    // -------------------------------------------------------------------------

    public function admin_orders() {
        $this->autoRender = false;
        $this->layout     = false;

        $rows = $this->_fetch(
            'SELECT o.id, o.store_id, o.channel, o.up_order_id, o.up_order_display_id,' .
            ' o.reference_id, o.status, o.order_type, o.payment_mode,' .
            ' o.created, o.modified,' .
            ' COUNT(e.id) as event_count,' .
            ' SUM(CASE WHEN e.status = \'pending\' THEN 1 ELSE 0 END) as pending_events' .
            ' FROM up_orders o' .
            ' LEFT JOIN up_order_events e ON e.up_order_id = o.up_order_id' .
            ' GROUP BY o.id ORDER BY o.id DESC LIMIT 100'
        );

        $orders = array();
        foreach ($rows as $row) {
            // GROUP BY query: CakePHP uses table name for grouped fields
            $o = isset($row['up_orders']) ? $row['up_orders'] : (isset($row[0]) ? $row[0] : array());
            $o['event_count']    = isset($row[0]['event_count'])    ? $row[0]['event_count']    : 0;
            $o['pending_events'] = isset($row[0]['pending_events']) ? $row[0]['pending_events'] : 0;
            $orders[] = $o;
        }

        $this->response->type('json');
        echo json_encode(array('orders' => $orders, 'count' => count($orders)));
        $this->response->send();
    }
}
