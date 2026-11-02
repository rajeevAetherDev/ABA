<?php
/**
 * UrbanpiperLogger — Thin wrapper around CakeLog for UrbanPiper events.
 *
 * Writes to app/tmp/logs/urbanpiper.log
 * NEVER logs raw tokens, secrets, full customer PII or raw payloads.
 *
 * @author  Aether Integration Engineering
 * @version 1.0  2026-03-03
 */
class UrbanpiperLogger {

    const LOG_SCOPE = 'urbanpiper';

    public static function info($message) {
        CakeLog::write('info', '[UP] ' . $message, array('scope' => array(self::LOG_SCOPE)));
    }

    public static function warn($message) {
        CakeLog::write('warning', '[UP] ' . $message, array('scope' => array(self::LOG_SCOPE)));
    }

    public static function error($message) {
        CakeLog::write('error', '[UP] ' . $message, array('scope' => array(self::LOG_SCOPE)));
    }

    /**
     * Log an incoming webhook receipt (safe summary only).
     *
     * @param string $type     'order'|'order_update'|'menu_callback'
     * @param string $orderId  UP order ID
     * @param string $storeId
     * @param string $channel
     */
    public static function webhookReceived($type, $orderId, $storeId, $channel = '') {
        self::info(
            'webhook:' . $type .
            ' up_order_id=' . $orderId .
            ' store=' . $storeId .
            ' channel=' . $channel
        );
    }

    /**
     * Log a job delivery to a store connector.
     */
    public static function jobDelivered($storeKey, $eventId, $upOrderId, $eventType) {
        self::info(
            'job_delivered store=' . $storeKey .
            ' event_id=' . $eventId .
            ' up_order_id=' . $upOrderId .
            ' type=' . $eventType
        );
    }

    /**
     * Log a successful ack from store connector.
     */
    public static function jobAcked($storeKey, $eventId, $referenceId) {
        self::info(
            'job_acked store=' . $storeKey .
            ' event_id=' . $eventId .
            ' reference_id=' . $referenceId
        );
    }
}
