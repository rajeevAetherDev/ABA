<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
App::uses('UtilityController', 'Controller');
App::uses('AppController', 'Controller');
App::uses('CakeTime', 'Utility');
/**
 * CakePHP ApisController
 * @author aetherss
 */
class OnlineOrderWebhookController extends AppController {
    public function beforeFilter() {
            parent::beforeFilter();
            // Allow users to register and logout.
            $this->Auth->allow();
            $this->layout = false;
            $this->autoRender = false;
            error_reporting(0);
        }
        
    public function index()
    {
        echo "Welcome to PhyloPos";
    }
 
    public function order_placed()
    {
        // $result = $this->request->data;
        // get raw POST body
        $raw = file_get_contents("php://input");
    
        // decode JSON
        $result = json_decode($raw, true);
        
        // print_r($result);exit;
        
        do {
            $reference = uniqid('UP');
            $exists = $this->Online_order->find('count', [
                'conditions' => ['Online_order.reference_id' => $reference]
            ]);
        } while ($exists  > 0);
        
        if (!empty($result)) {
            $responseJson = json_encode($result); // Correct: no need for `true` in json_encode
            $order = $result['order'];
    
            $orderno = isset($order['details']['id']) ? $order['details']['id'] : null;
            $store_id = isset($order['store']['merchant_ref_id']) ? $order['store']['merchant_ref_id'] : null;
    
            $existing = $this->Online_order->find('first', [
                'conditions' => ['Online_order.order_id' => $orderno],
                'fields' => ['Online_order.id']
            ]);
            if (empty($existing)) {
                // Assuming $responseJson contains your JSON string
                $responseArr = json_decode($responseJson, true); // decode into array
                $details = $responseArr['order']['details'];
                
                $delivery_date = $details['delivery_datetime'];
                $platform_id = $details['ext_platforms'][0]['id'];
                $platform_name = $details['ext_platforms'][0]['name'];
                $merchant_receivable_amount = $details['order_total']+ $details['total_external_discount'];
                $merchant_sponsored_discount= $details['discount'] - $details['total_external_discount'];
                $total_price = $details['order_subtotal'];
                $total_taxes = $details['item_level_total_taxes'];
                $total_amount = $details['payable_amount'];
                $state = $details['order_state'];
                
                switch ($state) {
                    case 'Acknowledged': $order_status = 1; break;
                    case 'Food Ready':   $order_status = 2; break;
                    case 'Dispatched':   $order_status = 3; break;
                    case 'Completed':    $order_status = 4; break;
                    case 'Cancelled':    $order_status = 5; break;
                    default:             $order_status = 0; break;
                }
                
                $timestamp_s  = $delivery_date / 1000; // convert to seconds
                $formatted_date = date('Y-m-d H:i:s', $timestamp_s);
                        
                        
                $data = [
                    'Online_order' => [
                        'store_id'     => $store_id,
                        'reference_id' => $reference,
                        'status'       => $order_status,
                        'order_id'     => $orderno,
                        'response'     => $responseJson,
                        'delivery_date' =>$formatted_date,
                        'platform_id' =>$platform_id,
                        'platform_name' =>$platform_name,
                        'merchant_receivable_amount' =>$merchant_receivable_amount,
                        'merchant_sponsored_discount' =>$merchant_sponsored_discount,
                        'total_price' =>$total_price,
                        'total_taxes' =>$total_taxes,
                        'total_amount' =>$total_amount,
                    ]
                ];
    
                if ($this->Online_order->saveAll($data)) {
                    return json_encode([
                        'response_code' => 1,
                        'response_message' => 'Order saved successfully',
                        'order_ref_id' => $reference
                    ]);
                }
            }
        }
    }

    public function order_status_update()
    {
        $raw = file_get_contents("php://input");
        $result = json_decode($raw, true);
        
        if (!empty($result)) {
            $responseJson = json_encode($result);
            $new_state  = strval($result['new_state']);
            $prev_state = strval($result['prev_state']);
            $remarks    = isset($result['message']) ? $result['message'] : '';
    
            $existing = $this->Online_order->find('first', [
                'conditions' => ['Online_order.order_id' => $result['order_id']],
                'fields'     => ['Online_order.id', 'Online_order.reference_id']
            ]);
    
            // Map state name to numeric status
            switch ($new_state) {
                case 'Acknowledged': $order_status = 1; break;
                case 'Food Ready':   $order_status = 2; break;
                case 'Dispatched':   $order_status = 3; break;
                case 'Completed':    $order_status = 4; break;
                case 'Cancelled':    $order_status = 5; break;
                default:             $order_status = 0; break;
            }
    
            if (!empty($existing)) {
                $escapedRemarks = addslashes($remarks);
                
                $success = $this->Online_order->updateAll(
                    [
                        'Online_order.status'   => (int)$order_status,
                        'Online_order.cron_run' => 0,
                        'Online_order.remarks'  => "'" . $escapedRemarks . "'",
                    ],
                    ['Online_order.reference_id' => $existing['Online_order']['reference_id']]
                );

                
                if ($order_status==5) {
                    $possuccess = $this->pos_status_update(
                        $existing['Online_order']['reference_id'],
                        $order_status
                    );
                }
                
                if ($success) {
                    return json_encode([
                        'response_code'    => 1,
                        'response_message' => 'Order updated successfully'
                    ]);
                }
            }
        }
    }
    
    public function rider_status_update()
    {
        $raw = file_get_contents("php://input");
        $result = json_decode($raw, true);
        
        if (!empty($result)) {
    
            $responseJson  = json_encode($result);
            $order_id      = $result['order_id'];
            $store_id      = $result["store"]["ref_id"];
            $current_state = $result['delivery_info']['current_state'];
            $rider_name    = $result['delivery_info']['delivery_person_details']['name'];
            $rider_phone   = $result['delivery_info']['delivery_person_details']['phone'];
    
            // Map delivery state to order status
            switch ($current_state) {
                case 'Delivered':
                    $status  = "Completed";
                    $message = "Order completed";
                    break;
                default:
                    $status  = $current_state;
                    $message = "Rider status updated";
                    break;
            }
    
            // Find the order
            $existing = $this->Online_order->find('first', [
                'conditions' => ['Online_order.order_id' => $order_id],
                'fields'     => ['Online_order.reference_id']
            ]);
        
            if (!empty($existing)) {
                $rider_name = addslashes($rider_name);
                
		        $deliveryTime=date('Y-m-d H:i:s');

                $success = $this->Online_order->updateAll(
                    [
                        'Online_order.rider_status'    => "'" . $current_state . "'",
                        'Online_order.rider_name'      => "'" . $rider_name . "'",
                        'Online_order.rider_contactno' => "'" . $rider_phone . "'",
                        'Online_order.rider_response'  => "'" . $responseJson . "'",
                        'Online_order.delivery_date'   => "'" . $deliveryTime . "'"
                    ],
                    ['Online_order.reference_id' => $existing['Online_order']['reference_id']]
                );
                if ($success) {
                    return json_encode([
                        'response_code'    => 1,
                        'response_message' => $message
                    ]);
                }
            }
        }
    }
    
    public function pos_status_update($reference_id, $status)
    {
        $success = true;
    
        // Update Order_orderhistory
        if (!$this->Order_orderhistory->updateAll(
            ['Order_orderhistory.status' => 0],
            ['Order_orderhistory.reference_id' => $reference_id]
        )) {
            $success = false;
        }
    
        // Update Order_tax
        if (!$this->Order_tax->updateAll(
            ['Order_tax.status' => 0],
            ['Order_tax.reference_id' => $reference_id]
        )) {
            $success = false;
        }
    
        // Update Order_transaction
        if (!$this->Order_transaction->updateAll(
            ['Order_transaction.status' => 0],
            ['Order_transaction.reference_id' => $reference_id]
        )) {
            $success = false;
        }
    
        // Update Order_payments
        if (!$this->Order_payment->updateAll(
            ['Order_payment.status' => 0],
            ['Order_payment.reference_id' => $reference_id]
        )) {
            $success = false;
        }
    
        return $success;
    }
    
    public function sync_to_pos()
    {
        $webhooks = $this->Online_order->find('all', array(
            'conditions' => array(
                'Online_order.cron_run' => 0
            )
        ));
    
        foreach ($webhooks as $webhook) {
            $data = array();
            $remarks = array();
    
            $response = json_decode($webhook['Online_order']['response']);
            $txnid = $webhook['Online_order']['reference_id'];
    
            if (empty($response->order->details)) {
                $remarks[] = "Missing order details for txn $txnid";
                continue;
            }
    
            $details = $response->order->details;
            $store = $response->order->store;
            $items = $details->items;
            $delivery_date = $details->delivery_date;
            $deliverydatetime = date("Y-m-d H:i:s", $delivery_date / 1000);
            $payments=$order->payment;
				
            // Initialize totals
            $total_tax_value = 0;
            $total_pack_charge = 0;
    
            // Loop through items
            foreach ($items as $item) {
                $item_tax_value = 0;
                $item_tax_rate = 0;
    
                // Item taxes
                if (!empty($item->taxes)) {
                    foreach ($item->taxes as $tax) {
                        $item_tax_value += $tax->value;
                        $item_tax_rate  += $tax->rate;
                    }
                }
                $total_tax_value += $item_tax_value;
    
                // Item-level packaging
                if (!empty($item->charges)) {
                    foreach ($item->charges as $char) {
                        $packtitle = strtolower($char->title);
                        if ($packtitle == 'packaging charge' || $packtitle == "restaurant packaging charges") {
                            $total_pack_charge += $char->value;
                        }
                    }
                }
    
                // Price after discount
                $ttlprice = (($item->total) == ($item->discount)) ? 0 : $item->total;
    
                // Order history entry
                $data['Order_orderhistory'][] = array(
                    'reference_id'         => $txnid,
                    'cashier_id'           => $store->merchant_ref_id,
                    'category'             => $item->category->merchant_id,
                    'category_name'        => $item->category->name,
                    'product_id'           => $item->merchant_id,
                    'product_name'         => $item->title,
                    'quantity'             => $item->quantity,
                    'price'                => $item->price,
                    'total_tax'            => $item_tax_value,
                    'total_tax_percentage' => $item_tax_rate,
                    'total_price'          => $ttlprice,
                    'discount'             => $item->discount,
                    'status'               => 2
                );
    
                // Tax breakdown per item
                if (!empty($item->taxes)) {
                    foreach ($item->taxes as $tax) {
                        $data['Order_tax'][] = array(
                            'orderhistory_id' => '',
                            'product_id'      => $item->merchant_id,
                            'product_name'    => $item->title,
                            'price'           => $item->price,
                            'taxable_price'   => $item->price * $item->quantity,
                            'cat_id'          => $item->category->merchant_id,
                            'cat_name'        => $item->category->name,
                            'quantity'        => $item->quantity,
                            'tax_id'          => $tax->rate,
                            'tax_name'        => $tax->title,
                            'tax_percentage'  => $tax->rate,
                            'tax_value'       => $tax->value,
                            'reference_id'    => $txnid,
                            'status'          => 2
                        );
                    }
                }
            }
    
            // Order-level packaging charges
            if (!empty($details->charges)) {
                foreach ($details->charges as $char) {
                    $packtitle = strtolower($char->title);
                    if ($packtitle == 'packaging charge' || $packtitle == "restaurant packaging charges") {
                        $total_pack_charge += $char->value;
                    }
                }
            }
    
            // Transaction data
            $data['Order_transaction'][] = array(
                'reference_id'    => $txnid,
                'order_id'        => $details->id,
                'merchant_id'     => $store->merchant_id,
                'cashier_id'      => $store->merchant_ref_id,
                'order_total'     => $details->total,
                'total_tax'       => $total_tax_value,
                'packaging_charge'=> $total_pack_charge,
                'discount'        => $details->discount, // From order-level
                'created_at'      => $deliverydatetime,
                'status'          => 2
            );
            
            if (!empty($payments)) {
                foreach ($payments as $payment) {
                    $data['Order_payment'][] = array(
                        'reference_id' => $txnid,
                        'cashier_id'   => $store->merchant_ref_id,
                        'type'         => '9',
                        'type_value'   => "" . $details->channel,
                        'amount'       => $payment->amount,
                        'remarks'      => $payment->option,
                        'status'       => 2
                    );
                }
            }            
            
            // Mark webhook processed
            $this->Online_order->updateAll(
                array('Online_order.cron_run' => 1),
                array('Online_order.id' => $webhook['Online_order']['id'])
            );
    
            // Save to DB (pseudo - depends on your ORM)
            if (!empty($data)) {
                $this->Order_orderhistory->saveAll($data['Order_orderhistory']);
                if (!empty($data['Order_tax'])) {
                    $this->Order_tax->saveAll($data['Order_tax']);
                }
                $this->Order_transaction->saveAll($data['Order_transaction']);
            }
    
            $remarks[] = "Synced order $txnid successfully.";
        }
    }

}