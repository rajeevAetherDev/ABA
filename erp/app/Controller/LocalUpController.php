<?php
/**
 * LocalUpController — Receives UrbanPiper orders from the store connector daemon
 *                     and queues them in online_order for cashier review.
 *
 * RUNS ON: STORE LOCAL SERVER (the per-store clone of TGIF-ERP)
 * CALLED BY: tools/urbanpiper_store_connector/connector.js (Node.js daemon)
 *            which polls the CLOUD and relays jobs here over localhost HTTP.
 *
 * NEVER called from the internet — Apache must restrict /local_up/* to 127.0.0.1
 *
 * Flow for a new order:
 *   Cloud receives UP webhook
 *     └─ Inserts into up_order_events (status=pending)
 *          └─ connector.js polls cloud every 2s, fetches pending jobs
 *               └─ POST /local_up/order_create   ← THIS CONTROLLER
 *                    └─ Saves canonical payload to online_order (status=0 pending)
 *                         └─ Cashier sees order in Online Order panel
 *                              └─ Cashier clicks Accept → /Apis/online_order_accept
 *                                   └─ POS records created + UP "Acknowledged" pushed
 *
 * @author  Aether Integration Engineering
 * @version 3.0  2026-03-05 — defer POS writes to accept time; fix column names
 */

App::uses('AppController', 'Controller');
App::uses('UrbanpiperLogger', 'Lib/Urbanpiper');
App::uses('UrbanpiperClient',  'Lib/Urbanpiper');

class LocalUpController extends AppController {

    public $uses = array(
        'Order_transaction',
        'Order_orderhistory',
        'Online_order',
        'Order_payment',
        'Order_tax',
    );

    public function beforeFilter() {
        parent::beforeFilter();
        $this->Auth->allow(array('order_create', 'order_update', 'order_cancel'));
        $this->layout     = false;
        $this->autoRender = false;
        error_reporting(0);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function _json($data, $code = 200) {
        $this->response->statusCode($code);
        $this->response->type('json');
        echo json_encode($data);
        $this->response->send();
        exit;
    }

    private function _requirePost() {
        if (!$this->request->is('post')) {
            $this->_json(array('error' => 'method_not_allowed'), 405);
        }
    }

    private function _body() {
        return json_decode(file_get_contents('php://input'), true) ?: array();
    }

    /**
     * Generate a unique reference_id for this order.
     * Format: UP + YYYYMMDDHHMMSS + 2-digit random
     * Checks uniqueness against online_order table.
     */
    private function _generateRefId() {
        do {
            $ref = 'UP' . date('YmdHis') . rand(10, 99);
            $exists = $this->Online_order->find('count', array(
                'conditions' => array('Online_order.reference_id' => $ref)
            ));
        } while ($exists > 0);
        return $ref;
    }

    // -------------------------------------------------------------------------
    // POST /local_up/order_create
    // Saves the incoming canonical order to online_order (status=0 pending).
    // Does NOT write POS tables yet — that happens when cashier accepts the order.
    // Does NOT acknowledge to UrbanPiper — that happens on accept.
    // -------------------------------------------------------------------------

    public function order_create() {
        $this->_requirePost();
        $body = $this->_body();

        $upOrderId = isset($body['up_order_id']) ? trim($body['up_order_id']) : '';
        $channel   = isset($body['channel'])     ? strtolower(trim($body['channel'])) : 'online';
        $payload   = isset($body['payload'])     ? $body['payload']                   : array();

        if (!$upOrderId) {
            $this->_json(array('status' => 'error', 'message' => 'missing_up_order_id'), 400);
        }

        // ── Idempotency guard ───────────────────────────────────────────────
        $existing = $this->Online_order->find('first', array(
            'conditions' => array('Online_order.order_id' => $upOrderId),
            'fields'     => array('Online_order.reference_id'),
        ));
        if (!empty($existing) && !empty($existing['Online_order']['reference_id'])) {
            UrbanpiperLogger::info('order_create DUPLICATE up_order_id=' . $upOrderId .
                ' returning existing reference_id=' . $existing['Online_order']['reference_id']);
            $this->_json(array(
                'status'       => 'ok',
                'reference_id' => $existing['Online_order']['reference_id'],
                'duplicate'    => true,
            ));
        }

        // ── Parse header fields for summary columns ─────────────────────────
        $header     = isset($payload['header'])     ? $payload['header']     : array();
        $financials = isset($payload['financials']) ? $payload['financials'] : array();

        $storeId     = isset($header['store_id'])         ? $header['store_id']          : '';
        $displayId   = isset($header['up_display_id'])    ? $header['up_display_id']     : $upOrderId;
        $extPlatId   = isset($header['ext_platform_id'])  ? $header['ext_platform_id']   : $channel;
        $extPlatName = isset($header['ext_platform_name'])? $header['ext_platform_name'] : $channel;
        $totalAmount = (float)($financials['payable_amount'] ?? $financials['total'] ?? 0);
        $totalTaxes  = (float)($financials['total_taxes'] ?? 0);
        $totalPrice  = (float)($financials['subtotal'] ?? 0);

        // ── Generate reference_id ───────────────────────────────────────────
        $referenceId = $this->_generateRefId();

        UrbanpiperLogger::info(
            'order_create queued up_order_id=' . $upOrderId .
            ' store=' . $storeId .
            ' ref=' . $referenceId .
            ' channel=' . $channel
        );

        // ── Save to online_order (status=0 pending cashier accept) ──────────
        $this->Online_order->create();
        $saved = $this->Online_order->save(array('Online_order' => array(
            'reference_id'  => $referenceId,
            'order_id'      => $upOrderId,
            'store_id'      => $storeId,
            'platform_id'   => $extPlatId,
            'platform_name' => $extPlatName,
            'total_price'   => $totalPrice,
            'total_taxes'   => $totalTaxes,
            'total_amount'  => $totalAmount,
            'status'        => 0,   // 0 = pending cashier accept
            'cron_run'      => 0,
            'response'      => json_encode($payload),
        )));

        if (!$saved) {
            UrbanpiperLogger::error('order_create save_failed up_order_id=' . $upOrderId);
            $this->_json(array('status' => 'error', 'message' => 'save_failed'), 500);
        }

        UrbanpiperLogger::info(
            'order_create OK up_order_id=' . $upOrderId .
            ' reference_id=' . $referenceId .
            ' awaiting_cashier_accept'
        );

        $this->_json(array('status' => 'ok', 'reference_id' => $referenceId));
    }

    // -------------------------------------------------------------------------
    // POST /local_up/order_update
    // Updates local order status when UP sends a downstream status change.
    // -------------------------------------------------------------------------

    public function order_update() {
        $this->_requirePost();
        $body = $this->_body();

        $upOrderId = isset($body['up_order_id']) ? trim($body['up_order_id']) : '';
        $payload   = isset($body['payload'])     ? $body['payload']           : array();

        if (!$upOrderId) {
            $this->_json(array('status' => 'error', 'message' => 'missing_up_order_id'), 400);
        }

        // Find the local order via online_order table
        $onlineOrder = $this->Online_order->find('first', array(
            'conditions' => array('Online_order.order_id' => $upOrderId),
            'fields'     => array('Online_order.reference_id', 'Online_order.status'),
        ));

        if (empty($onlineOrder)) {
            UrbanpiperLogger::warn('order_update order_not_found up_order_id=' . $upOrderId);
            $this->_json(array('status' => 'error', 'message' => 'order_not_found'), 404);
        }

        $refId    = $onlineOrder['Online_order']['reference_id'];
        $upStatus = isset($payload['order']['details']['order_state'])
            ? $payload['order']['details']['order_state'] : '';

        // Map UP status → local online_order.status
        $statusMap = array(
            'Acknowledged' => 1,
            'Food Ready'   => 2,
            'Dispatched'   => 3,
            'Completed'    => 4,
            'Cancelled'    => 5,
        );
        $localStatus = isset($statusMap[$upStatus]) ? $statusMap[$upStatus] : null;

        if ($localStatus) {
            $this->Online_order->updateAll(
                array('Online_order.status' => $localStatus),
                array('Online_order.order_id' => $upOrderId)
            );
        }

        // If cancelled, void the POS transaction if it was accepted
        if ($upStatus === 'Cancelled' && $refId) {
            $this->Order_transaction->updateAll(
                array('Order_transaction.status' => 0),
                array('Order_transaction.reference_id' => $refId)
            );
            $this->Order_orderhistory->updateAll(
                array('Order_orderhistory.status' => 0),
                array('Order_orderhistory.reference_id' => $refId)
            );
            
            $this->Order_payment->updateAll(
                array('Order_payment.status' => 0),
                array('Order_payment.reference_id' => $refId)
            );
            
            $this->Order_tax->updateAll(
                array('Order_tax.status' => 0),
                array('Order_tax.reference_id' => $refId)
            );
            UrbanpiperLogger::info('order_update cancelled reference_id=' . $refId);
        }

        UrbanpiperLogger::info(
            'order_update up_order_id=' . $upOrderId . ' status=' . $upStatus . ' ref=' . $refId
        );

        $this->_json(array('status' => 'ok', 'reference_id' => $refId));
    }

    // -------------------------------------------------------------------------
    // POST /local_up/order_cancel
    // -------------------------------------------------------------------------

    public function order_cancel() {
        $this->_requirePost();
        $body = $this->_body();

        $upOrderId = isset($body['up_order_id']) ? trim($body['up_order_id']) : '';

        if (!$upOrderId) {
            $this->_json(array('status' => 'error', 'message' => 'missing_up_order_id'), 400);
        }

        $onlineOrder = $this->Online_order->find('first', array(
            'conditions' => array('Online_order.order_id' => $upOrderId),
            'fields'     => array('Online_order.reference_id'),
        ));

        $refId = !empty($onlineOrder) ? $onlineOrder['Online_order']['reference_id'] : null;

        if ($refId) {
            $this->Order_transaction->updateAll(
                array('Order_transaction.status' => 0),
                array('Order_transaction.reference_id' => $refId)
            );
            $this->Order_orderhistory->updateAll(
                array('Order_orderhistory.status' => 0),
                array('Order_orderhistory.reference_id' => $refId)
            );
            $this->Order_payment->updateAll(
                array('Order_payment.status' => 0),
                array('Order_payment.reference_id' => $refId)
            );
            
            $this->Order_tax->updateAll(
                array('Order_tax.status' => 0),
                array('Order_tax.reference_id' => $refId)
            );
        }

        // Update online_order status to cancelled (5)
        $this->Online_order->updateAll(
            array('Online_order.status' => 5),
            array('Online_order.order_id' => $upOrderId)
        );

        UrbanpiperLogger::info('order_cancel up_order_id=' . $upOrderId . ' ref=' . $refId);

        // Push cancellation status to UP API
        try {
            $client = new UrbanpiperClient();
            $client->cancelOrder($upOrderId, 'Cancelled at POS');
        } catch (Exception $e) {
            UrbanpiperLogger::error('order_cancel ack_to_up EXCEPTION: ' . $e->getMessage());
        }

        $this->_json(array('status' => 'ok', 'reference_id' => $refId));
    }
}
