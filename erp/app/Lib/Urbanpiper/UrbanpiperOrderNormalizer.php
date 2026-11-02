<?php
/**
 * UrbanpiperOrderNormalizer
 *
 * Converts a raw UrbanPiper order webhook payload (array) into the TGIF
 * canonical order structure used throughout the integration.
 *
 * Usage:
 *   $normalizer = new UrbanpiperOrderNormalizer();
 *   $canonical  = $normalizer->normalize($rawPayload);
 *
 * @author  Aether Integration Engineering
 * @version 1.1  2026-03-06  Fixed 6 field-mapping bugs
 */
class UrbanpiperOrderNormalizer {

    /**
     * Main entry point.
     *
     * @param  array $payload  Decoded JSON from UP webhook body
     * @return array           Canonical order array
     */
    public function normalize(array $payload) {
        $order   = isset($payload['order'])       ? $payload['order']       : array();
        $details = isset($order['details'])       ? $order['details']       : array();
        $store   = isset($order['store'])         ? $order['store']         : array();
        $items   = isset($order['items'])         ? $order['items']         : array();
        $delivery= isset($order['delivery_info']) ? $order['delivery_info'] : array();

        // FIX 1: customer is at the TOP level of the webhook payload,
        //         NOT nested inside order{}.
        $customer = isset($payload['customer']) ? $payload['customer'] : array();

        $extPlatforms = isset($details['ext_platforms']) ? $details['ext_platforms'] : array();
        $extPlatform  = !empty($extPlatforms) ? $extPlatforms[0] : array();

        $orderType = $this->_normalizeOrderType(
            isset($details['order_type']) ? $details['order_type'] : '',
            $delivery
        );

        return array(
            'header' => array(
                'up_order_id'       => $this->_safe($details, 'id'),
                'up_display_id'     => $this->_safe($details, 'display_id'),
                'channel'           => strtolower($this->_safe($details, 'channel', 'unknown')),
                'ext_platform_id'   => $this->_safe($extPlatform, 'id'),
                'ext_platform_name' => $this->_safe($extPlatform, 'name'),
                'store_id'          => $this->_safe($store, 'merchant_ref_id'),
                'store_name'        => $this->_safe($store, 'name'),
                'order_type'        => $orderType,
                'is_delivery'       => ($orderType === 'delivery'),
                'is_takeaway'       => ($orderType === 'pickup'),
                'payment_mode'      => strtolower($this->_safe($details, 'payment_option', 'prepaid')),
                'instructions'      => $this->_safe($details, 'instructions'),
                'delivery_datetime' => $this->_safe($details, 'delivery_datetime'),
                'order_state'       => $this->_safe($details, 'order_state', 'Acknowledged'),
                'customer' => array(
                    'name'         => $this->_safe($customer, 'name'),
                    'phone'        => $this->_safe($customer, 'phone'),
                    'email'        => $this->_safe($customer, 'email'),
                    'address_city' => isset($customer['address']['city']) ? $customer['address']['city'] : '',
                ),
            ),
            'financials' => array(
                'subtotal'          => (float)$this->_safe($details, 'order_subtotal', 0),
                'total'             => (float)$this->_safe($details, 'order_total', 0),
                'payable_amount'    => (float)$this->_safe($details, 'payable_amount', 0),
                'discount'          => (float)$this->_safe($details, 'discount', 0),

                // FIX 4: merchant_discount = sum of discounts where is_merchant_discount=true
                //         total_external_discount is the AGGREGATOR's contribution, not merchant's
                'merchant_discount' => $this->_merchantDiscount($extPlatform),

                // FIX 2: UP uses 'total_taxes', not 'order_total_taxes'
                'total_taxes'       => (float)$this->_safe($details, 'total_taxes',
                                         $this->_safe($details, 'item_level_total_taxes', 0)),

                'delivery_charges'  => (float)$this->_safe($details, 'delivery_charges', 0),

                // FIX 3: UP uses 'order_level_total_charges' for packaging
                'packaging_charges' => (float)$this->_safe($details, 'order_level_total_charges',
                                         $this->_safe($details, 'packaging_charges', 0)),

                'service_charges'   => (float)$this->_safe($details, 'service_charges', 0),
            ),
            'items' => $this->_normalizeItems($items),
			'other_charges' => $this->_sumChargeTaxes(isset($details['charges']) ? $details['charges'] : array()),
			'charges_details' => isset($details['charges']) ? $details['charges'] : array(),
        );
    }

    // -------------------------------------------------------------------------

    private function _normalizeItems(array $items) {
        $out = array();
        foreach ($items as $item) {
            $category = isset($item['category']) ? $item['category'] : array();
            $out[] = array(
                'up_item_id'    => $this->_safe($item, 'id'),
                'merchant_id'   => $this->_safe($item, 'merchant_id', '0'),
                'name'          => $this->_safe($item, 'title'),
                'qty'           => (int)$this->_safe($item, 'quantity', 1),
                'unit_price'    => (float)$this->_safe($item, 'price', 0),

                // FIX 5: UP uses 'total' per item, not 'total_price'
                'total_price'   => (float)$this->_safe($item, 'total',
                                      $this->_safe($item, 'total_price', 0)),

                'food_type'     => (int)$this->_safe($item, 'food_type', 0),
                'discount'      => (float)$this->_safe($item, 'discount', 0),
                'category_id'   => $this->_safe($category, 'merchant_id', '0'),
                'category_name' => $this->_safe($category, 'name', ''),
                'taxes'         => $this->_safe($item, 'taxes', array()),

                // FIX 6: options are in 'options_to_add', not 'options'
                'options'       => $this->_normalizeOptions(
                                      isset($item['options_to_add']) ? $item['options_to_add']
                                      : (isset($item['options']) ? $item['options'] : array())
                                   ),
            );
        }
        return $out;
    }

    private function _normalizeOptions(array $options) {
        $out = array();
        foreach ($options as $opt) {
            // FIX 6 cont.: group info is nested under opt['group'], not flat
            $group = isset($opt['group']) ? $opt['group'] : array();

            $out[] = array(
                'group_id'    => $this->_safe($group, 'id',          $this->_safe($opt, 'group_id')),
                'group_title' => $this->_safe($group, 'title',       $this->_safe($opt, 'group_title')),
                'option_id'   => $this->_safe($opt,   'id'),
                'option_name' => $this->_safe($opt,   'title'),
                'price'       => (float)$this->_safe($opt, 'price', 0),
                'qty'         => (int)$this->_safe($opt, 'quantity', 1),
            );
        }
        return $out;
    }

    /**
     * Sum discounts from ext_platform where is_merchant_discount = true.
     * These are merchant-funded (restaurant pays), as opposed to aggregator-funded.
     */
    private function _merchantDiscount($extPlatform) {
        $discounts = isset($extPlatform['discounts']) ? $extPlatform['discounts'] : array();
        $total = 0.0;
        foreach ($discounts as $d) {
            if (!empty($d['is_merchant_discount'])) {
                $total += (float)$this->_safe($d, 'value', 0);
            }
        }
        return $total;
    }

    private function _normalizeOrderType($upType, $deliveryInfo) {
        $upType = strtolower(trim($upType));
        if (in_array($upType, array('delivery', 'pickup', 'dine_in'))) {
            return $upType;
        }
        $deliveryType = isset($deliveryInfo['delivery_type'])
            ? strtolower($deliveryInfo['delivery_type'])
            : '';
        if (strpos($deliveryType, 'pickup') !== false || strpos($deliveryType, 'self') !== false) {
            return 'pickup';
        }
        return 'delivery';
    }

    private function _safe($arr, $key, $default = null) {
        return isset($arr[$key]) ? $arr[$key] : $default;
    }
	
	private function _sumChargeTaxes(array $charges) {
		$total = 0.0;
		foreach ($charges as $charge) {
			if (!empty($charge['taxes']) && is_array($charge['taxes'])) {
				foreach ($charge['taxes'] as $ct) {
					$total += (float)(isset($ct['value']) ? $ct['value'] : 0);
				}
			}
		}
		return $total;
	}
}
