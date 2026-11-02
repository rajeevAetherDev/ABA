<?php
/**
 * UrbanpiperClient — Outbound HTTP client for UrbanPiper Downstream API
 *
 * Handles status updates, menu push, and item availability updates.
 * All credentials read from Configure (set via env vars in bootstrap.php).
 * Never logs secrets.
 *
 * @author  Aether Integration Engineering
 * @version 1.1  2026-03-03 — G3 auth header, G4 PUT method, G5 new_status, G13 endpoint path
 */
class UrbanpiperClient {

    /** Base URL, e.g. https://api.urbanpiper.com */
    private $baseUrl;

    /** API key (username for HTTP Basic Auth) */
    private $apiUsername;

    /** API password */
    private $apiKey;

    /** Business ID */
    private $bizId;

    public function __construct() {
        $this->baseUrl = '';
        $this->apiUsername = '';
        $this->apiKey = '';
        $this->bizId  = '';
    }

    // -------------------------------------------------------------------------

    /**
     * Push an order status update to UrbanPiper.
     *
     * @param  string $upOrderId   UrbanPiper order ID
     * @param  string $status      UP status string: Acknowledged|Food Ready|Dispatched|Completed|Cancelled
     * @param  array  $meta        Optional: ['message' => ..., 'reason' => ...]
     * @return array               ['success' => bool, 'http_code' => int, 'body' => string]
     */
    public function updateOrderStatus($upOrderId, $status, $meta = array()) {
        // G13 fix: correct endpoint is /external/api/v1/orders/{id}/status/
        // G5  fix: payload key is 'new_status', not 'status'
        $endpoint = '/external/api/v1/orders/' . rawurlencode($upOrderId) . '/status/';
        $body = array('new_status' => $status);
        if (!empty($meta['message'])) {
            $body['message'] = substr($meta['message'], 0, 255);
        }
        if (!empty($meta['reason'])) {
            $body['cancellation_reason'] = substr($meta['reason'], 0, 255);
        }

        $result = $this->_put($endpoint, $body);

        UrbanpiperLogger::info(
            'status_push up_order_id=' . $upOrderId .
            ' status=' . $status .
            ' http_code=' . $result['http_code'] .
            ' success=' . ($result['success'] ? 'true' : 'false')
        );

        if (!$result['success']) {
            UrbanpiperLogger::warn(
                'status_push FAILED up_order_id=' . $upOrderId .
                ' http_code=' . $result['http_code']
            );
        }

        return $result;
    }

    /**
     * Acknowledge order to UrbanPiper (send "Acknowledged" status).
     * Call this immediately after the POS successfully creates the order.
     *
     * @param  string $upOrderId
     * @return array
     */
    public function acknowledgeOrder($upOrderId) {
        return $this->updateOrderStatus($upOrderId, 'Acknowledged');
    }

    /**
     * Mark order as Food Ready.
     *
     * @param  string $upOrderId
     * @return array
     */
    public function markFoodReady($upOrderId) {
        return $this->updateOrderStatus($upOrderId, 'Food Ready');
    }

    /**
     * Mark order as Dispatched (rider picked up).
     *
     * @param  string $upOrderId
     * @return array
     */
    public function markDispatched($upOrderId) {
        return $this->updateOrderStatus($upOrderId, 'Dispatched');
    }

    /**
     * Mark order as Completed.
     *
     * @param  string $upOrderId
     * @return array
     */
    public function markCompleted($upOrderId) {
        return $this->updateOrderStatus($upOrderId, 'Completed');
    }

    /**
     * Cancel order with optional reason.
     *
     * @param  string $upOrderId
     * @param  string $reason
     * @return array
     */
    public function cancelOrder($upOrderId, $reason = '') {
        return $this->updateOrderStatus(
            $upOrderId,
            'Cancelled',
            array('reason' => $reason)
        );
    }

    /**
     * Update item availability (stock out / stock in).
     *
     * @param  string $bizLocationId  UrbanPiper store/outlet ID
     * @param  string $upItemId       UrbanPiper item ID
     * @param  bool   $available
     * @return array
     */
    public function updateItemAvailability($bizLocationId, $upItemId, $available) {
        $endpoint = '/api/v2/items/availability/';
        $body = array(
            'biz_location_id' => $bizLocationId,
            'items' => array(
                array(
                    'item_id'  => $upItemId,
                    'available'=> $available,
                )
            )
        );
        return $this->_post($endpoint, $body);
    }

    /**
     * Trigger a menu publish request to UrbanPiper.
     * UP will call back via the menu_callback webhook when done.
     *
     * @param  string $bizLocationId
     * @param  array  $menuPayload    Full UP menu structure
     * @return array
     */
    public function publishMenu($bizLocationId, array $menuPayload) {
        $endpoint = '/api/v2/items/';
        $body = array_merge(
            array('biz_location_id' => $bizLocationId),
            $menuPayload
        );
        return $this->_post($endpoint, $body);
    }

    // -------------------------------------------------------------------------
    // Internal HTTP helpers
    // -------------------------------------------------------------------------

    private function _post($endpoint, array $body) {
        return $this->_request('POST', $endpoint, $body);
    }

    // G4 fix: status updates must use PUT, not POST
    private function _put($endpoint, array $body) {
        return $this->_request('PUT', $endpoint, $body);
    }

    private function _get($endpoint, array $params = array()) {
        $url = $this->baseUrl . $endpoint;
        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }
        return $this->_request('GET', $url, null, true);
    }

    private function _request($method, $endpoint, $body = null, $absoluteUrl = false) {
        $url = $absoluteUrl ? $endpoint : $this->baseUrl . $endpoint;

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        // G3 fix: UrbanPiper requires 'Authorization: apikey KEY:PASSWORD' — NOT HTTP Basic Auth.
        // CURLOPT_USERPWD generates 'Authorization: Basic base64(...)' which is rejected.
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Authorization: apikey ' . $this->apiUsername . ':' . $this->apiKey,
            'Content-Type: application/json',
            'Accept: application/json',
        ));

        if ($method === 'POST' && $body !== null) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
        } elseif ($method === 'PUT' && $body !== null) {
            // G4 fix: PUT for status updates
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
        }

        $responseBody = curl_exec($ch);
        $httpCode     = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError    = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            UrbanpiperLogger::warn('http_error endpoint=' . $endpoint . ' curl_error=' . $curlError);
        }

        return array(
            'success'  => ($httpCode >= 200 && $httpCode < 300),
            'http_code'=> $httpCode,
            'body'     => $responseBody,
            'error'    => $curlError,
        );
    }
}
