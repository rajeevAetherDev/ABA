<?php
App::uses('AppController', 'Controller');

class PaymentpartnerController extends AppController {
    
    public $uses = array(
        'Location', 
        'Order_transaction', 
        'Order_payment', 
        'EazydinerToken', 
        'PaymentpartnerApiLog', 
        'PaymentPartner', 
        'Order_split_payment', 
        'Order_split_transaction', 
        'Order_split_orderhistory', 
        'Order_table', 
        'Order_tax', 
        'Order_orderhistory', 
        'User', 
        'Coupon'
    );
    
    public function beforeFilter() {
        parent::beforeFilter();
        // Allow API actions
        if (in_array($this->request->params['action'], array('process_partner_payment', 'update_bills_discount', 'process_eazydiner_verification', 'process_zomato_verification', 'cron_get_token', 'write_db_token', 'get_eazydiner_status'))) {
            $this->Auth->allow();
        }
    }

    /**
     * Cron action to fetch EazyDiner auth token.
     * Checks eazydiner_tokens DB table first:
     * - If entry exists and token is valid (not older than 25 days), returns that token without cURL.
     * - Otherwise calls EazyDiner API via cURL, inserts/updates generated token in DB, and returns it.
     * Accessible via URL: /paymentpartner/cron_get_token
     */
    public function cron_get_token() {
        $this->autoRender = false;
        $this->layout = false;

        try {
            if (!isset($this->EazydinerToken)) {
                $this->EazydinerToken = ClassRegistry::init('EazydinerToken');
            }

            // 1. Check entry on eazydiner_tokens table
            $existing = $this->EazydinerToken->find('first', [
                'order'     => ['EazydinerToken.id' => 'DESC'],
                'recursive' => -1
            ]);

            if (!empty($existing) && !empty($existing['EazydinerToken']['token'])) {
                $tokenDateStr = !empty($existing['EazydinerToken']['token_date'])
                    ? $existing['EazydinerToken']['token_date']
                    : (!empty($existing['EazydinerToken']['updated_at'])
                        ? $existing['EazydinerToken']['updated_at']
                        : ($existing['EazydinerToken']['created_at'] ?? null));

                if (!empty($tokenDateStr)) {
                    $tokenTime = strtotime($tokenDateStr);
                    $daysOld   = (time() - $tokenTime) / (60 * 60 * 24);

                    // Check validity: token must not be older than 25 days
                    if ($daysOld <= 25 && $daysOld >= 0) {
                        return json_encode([
                            'status'  => true,
                            'message' => 'Valid EazyDiner token retrieved from DB',
                            'token'   => $existing['EazydinerToken']['token'],
                            'source'  => 'database'
                        ]);
                    }
                }
            }

            // 2. Token does not exist or is older than 25 days -> generate using cURL
            $url = Configure::read('Eazydiner.login_url');
            $headers = [
                'secret: ' . (Configure::read('Eazydiner.secret') ?? ''),
                'Content-Type: application/json',
                'version: ' . (Configure::read('Eazydiner.version') ?? '')
            ];
            $body = [
                'username' => Configure::read('Eazydiner.username') ?? '',
                'authkey'  => Configure::read('Eazydiner.authkey') ?? ''
            ];

            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_TIMEOUT, 15);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

            $res = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode == 200 || $httpCode == 201) {
                $data = json_decode($res, true);
                $newToken = $data['token'] ?? $data['data']['token'] ?? null;

                if (!empty($newToken)) {
                    $today    = date('Y-m-d');
                    $saveData = [
                        'token'      => $newToken,
                        'token_date' => $today
                    ];

                    if (!empty($existing['EazydinerToken']['id'])) {
                        // Update existing entry
                        $saveData['id'] = $existing['EazydinerToken']['id'];
                    } else {
                        // Insert new entry
                        $this->EazydinerToken->create();
                    }

                    $this->EazydinerToken->save($saveData);

                    $this->_log_eazydiner_response(
                        null, null, null, null,
                        json_encode($body),
                        $res,
                        'token_generated',
                        0, 0, 0, 0,
                        $httpCode,
                        'Token Generated Successfully & Saved to DB'
                    );

                    return json_encode([
                        'status'  => true,
                        'message' => 'EazyDiner token fetched and saved to DB successfully',
                        'token'   => $newToken,
                        'source'  => 'curl_api'
                    ]);
                }
            }

            $this->_log_eazydiner_response(
                null, null, null, null,
                json_encode($body),
                $res,
                'token_error',
                0, 0, 0, 0,
                $httpCode,
                'Failed to fetch token from EazyDiner API'
            );

            return json_encode([
                'status'    => false,
                'message'   => 'Failed to fetch token from EazyDiner API',
                'http_code' => $httpCode
            ]);
        } catch (Exception $e) {
            CakeLog::error('[cron_get_token Error] ' . $e->getMessage());
            return json_encode([
                'status'  => false,
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * Reads/invokes cron_get_token via HTTP cURL from remote server, gets the generated token, and writes/updates it in the local database table (EazydinerToken).
     * Accessible via URL: /paymentpartner/write_db_token
     */
    public function write_db_token() {
        $this->autoRender = false;
        $this->layout = false;

        try {
            if (!isset($this->EazydinerToken)) {
                $this->EazydinerToken = ClassRegistry::init('EazydinerToken');
            }

            $url = Configure::read('Eazydiner.cron_token_url');
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 15);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

            $apiResJson = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            $apiRes = json_decode($apiResJson, true);

            if (!empty($apiRes['status']) && !empty($apiRes['token'])) {
                $newToken = $apiRes['token'];
                $today    = date('Y-m-d');

                $existing = $this->EazydinerToken->find('first', [
                    'order'     => ['EazydinerToken.id' => 'DESC'],
                    'recursive' => -1
                ]);

                $saveData = [
                    'token'      => $newToken,
                    'token_date' => $today
                ];

                if (!empty($existing['EazydinerToken']['id'])) {
                    $saveData['id'] = $existing['EazydinerToken']['id'];
                } else {
                    $this->EazydinerToken->create();
                }

                $this->EazydinerToken->save($saveData);

                return json_encode([
                    'status'  => true,
                    'message' => 'Token fetched from remote cron_get_token and updated in DB successfully',
                    'token'   => $newToken
                ]);
            }

            return json_encode([
                'status'    => false,
                'message'   => 'Failed to obtain token from cron_get_token',
                'http_code' => $httpCode,
                'details'   => $apiRes
            ]);
        } catch (Exception $e) {
            CakeLog::error('[write_db_token Error] ' . $e->getMessage());
            return json_encode([
                'status'  => false,
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Action to fetch EazyDiner payment status via cURL.
     * Connects to EazyDiner status API server via cURL and returns the response in JSON.
     * Accessible via URL: /paymentpartner/get_eazydiner_status
     */
    public function get_eazydiner_status($order_id = null, $eazydiner_outlet_id = null, $token = null, $headers = null, $url = null, $bodyPayload = null) {
        $this->autoRender = false;
        $this->layout     = false;

        $rawBody   = file_get_contents('php://input');
        $inputData = json_decode($rawBody, true);
        if (!is_array($inputData)) {
            $inputData = $_REQUEST;
        }

        $order_id            = $order_id ?? $inputData['order_id'] ?? null;
        $eazydiner_outlet_id = $eazydiner_outlet_id ?? $inputData['outlet_id'] ?? $inputData['eazydiner_outlet_id'] ?? null;
        $token               = $token ?? $inputData['token'] ?? $this->_get_db_token();

        $config = Configure::read('Eazydiner');
        $url    = $url ?? $inputData['url'] ?? $config['status_url'];

        if (!empty($inputData['headers']) && is_array($inputData['headers'])) {
            $headers = $inputData['headers'];
        } elseif (empty($headers)) {
            $headers = [
                'secret: ' . ($config['secret'] ?? ''),
                'Content-Type: application/json',
                'version: ' . ($config['version'] ?? ''),
                'token: ' . $token
            ];
        }

        if (!empty($inputData['bodyPayload']) && is_array($inputData['bodyPayload'])) {
            $bodyPayload = $inputData['bodyPayload'];
        } elseif (empty($bodyPayload)) {
            $bodyPayload = [
                'outlet_id' => (string)$eazydiner_outlet_id,
                'order_id'  => (string)$order_id
            ];
        }

        try {
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($bodyPayload));
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_TIMEOUT, 15);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

            $response = curl_exec($ch);
            // $response='{"data":{"order_status":"complete","order_id":17796968,"payment_amount":1290,"payment_details":{"merchant_discount":323,"merchant_discount_percent":25,"cover_charge":0,"amount_paid":967,"total_discount":468},"order_date":"2026-08-13","order_time":"16:48:58"}}';
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            if ($response === false) {
                $curlError = curl_error($ch);
                CakeLog::error('[get_eazydiner_status cURL Error] ' . $curlError);
            }
            curl_close($ch);

            return json_encode([
                'status'       => ($httpCode == 200 || $httpCode == 201),
                'http_code'    => $httpCode,
                'raw_response' => $response,
                'body_payload' => $bodyPayload,
                'url'          => $url
            ]);
        } catch (Exception $e) {
            CakeLog::error('[get_eazydiner_status Error] ' . $e->getMessage());
            return json_encode([
                'status'    => false,
                'http_code' => 500,
                'message'   => 'Error: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Fetch the cached EazyDiner auth token from database table without calling external API.
     */
    private function _get_db_token($force_refresh = false) {
        try {
            if (!isset($this->EazydinerToken)) {
                $this->EazydinerToken = ClassRegistry::init('EazydinerToken');
            }

            $existing = $this->EazydinerToken->find('first', [
                'order' => ['EazydinerToken.id' => 'DESC'],
                'recursive' => -1
            ]);
            
            if (!empty($existing['EazydinerToken']['token'])) {
                return $existing['EazydinerToken']['token'];
            }
        } catch (Exception $e) {
            CakeLog::error('[EazyDiner DB Token Fetch Error] ' . $e->getMessage());
        }

        return null;
    }

    /**
     * Log audit records in paymentpartner_api_logs table.
     */
    private function _log_paymentpartner_response($order_id, $partner_name = null, $pos_ref_id = null, $res_payload = null, $order_status = null, $extra = null) {
        try {
            if (!isset($this->PaymentpartnerApiLog)) {
                $this->PaymentpartnerApiLog = ClassRegistry::init('PaymentpartnerApiLog');
            }

            if (is_array($order_id)) {
                $data = $order_id;
                $logData = [
                    'orderid'          => $data['orderid'] ?? $data['order_id'] ?? null,
                    'partner_name'     => $data['partner_name'] ?? $data['type_value'] ?? 'PaymentPartner',
                    'pos_reference_id' => $data['pos_reference_id'] ?? $data['pos_ref_id'] ?? null,
                    'response_payload' => isset($data['response_payload']) ? (is_array($data['response_payload']) ? json_encode($data['response_payload']) : $data['response_payload']) : (is_array($data['res_payload'] ?? null) ? json_encode($data['res_payload']) : ($data['res_payload'] ?? null)),
                    'order_status'     => $data['order_status'] ?? null,
                    'created_at'       => date('Y-m-d H:i:s')
                ];
                $this->PaymentpartnerApiLog->create();
                $this->PaymentpartnerApiLog->save($logData);
                return;
            }

            // Handle legacy parameter ordering if passed
            if (empty($partner_name) || is_numeric($partner_name)) {
                $partner_name = 'PaymentPartner';
            }

            $logData = [
                'orderid'          => $order_id,
                'partner_name'     => $partner_name,
                'pos_reference_id' => $pos_ref_id,
                'response_payload' => is_array($res_payload) ? json_encode($res_payload) : $res_payload,
                'order_status'     => $order_status,
                'created_at'       => date('Y-m-d H:i:s')
            ];

            $this->PaymentpartnerApiLog->create();
            $this->PaymentpartnerApiLog->save($logData);
        } catch (Exception $e) {
            CakeLog::error('[PaymentPartner Log Error] ' . $e->getMessage());
        }
    }

    private function _log_eazydiner_response($order_id, $outlet_id = null, $pos_order_no = null, $pos_ref_id = null, $req_payload = null, $res_payload = null, $order_status = null, $payment_amount = 0, $amount_paid = 0, $total_discount = 0, $calc_pct = 0, $status_code = null, $message = null) {
        $this->_log_paymentpartner_response($order_id, 'EazyDiner', $pos_ref_id, $res_payload, $order_status);
    }

    private function _parse_eazydiner_error($res, $httpCode) {
        $errData = json_decode($res, true);
        $errMsg = '';
        
        if (!empty($errData['message'])) {
            $errMsg = $errData['message'];
        } elseif (!empty($errData['errors'])) {
            if (is_array($errData['errors'])) {
                $errMsg = implode(', ', $errData['errors']);
            } else {
                $errMsg = $errData['errors'];
            }
        } else {
            $errMsg = 'HTTP Error ' . $httpCode;
        }
        
        return 'Eazydiner Error: ' . $errMsg;
    }

    /**
     * Binary search discount solver matching POS tax & SC rules.
     */
    private function _find_matching_discount_percentage($reference_id, $target_amount, $sc) {
        $pos_amount = $this->_dry_run_compute($reference_id, $sc, 0, false);
        
        if ($pos_amount <= 0) {
            return 0;
        }
        
        $total_discount_needed = $pos_amount - $target_amount;
        if ($total_discount_needed <= 0) {
            return 0;
        }
        
        $est_pct = ($total_discount_needed / $pos_amount) * 100;
        
        $sim_exact = $this->_dry_run_compute($reference_id, $sc, $est_pct, true);
        if (abs($sim_exact - $target_amount) < 0.01) {
            return round($est_pct, 4);
        }
        
        $low = 0.0;
        $high = 100.0;
        $matched_pct = $est_pct;
        
        for ($i = 0; $i < 25; $i++) {
            $mid = ($low + $high) / 2;
            $sim_exact = $this->_dry_run_compute($reference_id, $sc, $mid, true);
            $sim_int   = round($sim_exact);
            
            if (abs($sim_exact - $target_amount) < 0.01 || $sim_int == round($target_amount)) {
                $matched_pct = $mid;
                if (abs($sim_exact - $target_amount) < 0.01) {
                    break;
                }
            }
            
            if ($sim_exact > $target_amount) {
                $low = $mid;
            } else {
                $high = $mid;
            }
        }
        
        return round($matched_pct, 4);
    }

    private function _dry_run_compute($ref_id, $sc_percent, $discount_pct, $return_exact = false) {
        if (!isset($this->Order_orderhistory)) {
            $this->Order_orderhistory = ClassRegistry::init('Order_orderhistory');
        }
        
        if($sc_percent == 1){
            $locIdForSc = $this->resolveLocationIdForTxn($ref_id);
            $sc_rate = $this->getLocationServiceChargeRate($locIdForSc);
        }

        // $sc_rate = ($sc_percent == 1) ? (defined('servicecharge') ? servicecharge : 0) : 0;
        
        $priceq = "(Order_orderhistory.total_price - Order_orderhistory.discount)";
        $discq  = "($priceq * $discount_pct / 100)";
        $scq    = "(($priceq - $discq) * $sc_rate / 100)";
        $taxq   = "(($priceq - $discq + $scq) * (Order_orderhistory.total_tax_percentage / 100))";
        
        $this->Order_orderhistory->virtualFields = [
            'tprice'      => "SUM($priceq)",
            'tdisc'       => "SUM($discq)",
            'tservicechg' => "SUM($scq)",
            'total_tax'   => "SUM($taxq)"
        ];
        
        $summary = $this->Order_orderhistory->find('first', [
            'conditions' => [
                'Order_orderhistory.reference_id' => $ref_id,
                'Order_orderhistory.status !='    => 0
            ],
            'fields' => ['reference_id', 'tprice', 'tdisc', 'tservicechg', 'total_tax'],
            'recursive' => -1
        ]);
        
        $this->Order_orderhistory->virtualFields = [];
        
        if (empty($summary['Order_orderhistory']['reference_id'])) {
            return 0;
        }
        
        $totalprice     = round($summary['Order_orderhistory']['tprice'], 2);
        $totaldiscount  = round($summary['Order_orderhistory']['tdisc'], 2);
        $totalservicechg= round($summary['Order_orderhistory']['tservicechg'], 2);
        $totaltax       = round($summary['Order_orderhistory']['total_tax'], 2);
        
        $exactTotalAmount = (($totalprice + $totaltax + $totalservicechg) - $totaldiscount);
        
        if ($return_exact) {
            return round($exactTotalAmount, 2);
        }
        return round($exactTotalAmount);
    }

    /**
     * Helper to create discrepancy adjustment payment entry (type = 11).
     */
    private function _create_misc_adjustment($reference_id, $split_reference_id, $store_id, $device_id, $cashierid, $diff_amount, $misc_type_value, $order_id = null) {
        $order_id = $order_id ?? $_REQUEST['order_id'] ?? null;
        $remarks  = (!empty($misc_type_value) ? $misc_type_value : 'Partner') . ' adjustment entry';
        if (!empty($order_id) && strpos($remarks, 'OrderID:') === false) {
            $remarks .= ' OrderID:' . $order_id;
        }

        $miscData = [
            'reference_id'       => $reference_id,
            'split_reference_id' => $split_reference_id,
            'store_id'           => $store_id,
            'device_id'          => $device_id,
            'cashier_id'         => $cashierid,
            'type'               => 11,
            'type_value'         => $misc_type_value,
            'amount'             => floatval($diff_amount),
            'status'             => 2,
            'remarks'            => $remarks
        ];
        
        if (!empty($split_reference_id) && $split_reference_id !== '0') {
            $this->Order_split_payment->create();
            return $this->Order_split_payment->save($miscData);
        } else {
            $this->Order_payment->create();
            return $this->Order_payment->save($miscData);
        }
    }

    /**
     * Helper to record final partner payment and settle transaction.
     */
    private function _save_partner_payment_entry($context, $reference_id, $amount) {
        $cashierid          = $context['cashier_id'] ?? 0;
        $split_reference_id = $context['split_reference_id'] ?? '0';
        $device_id          = $context['device_id'] ?? '';
        $type_value         = $context['type_value'] ?? 'Partner';
        $pay_type           = $context['pay_type'] ?? 1;
        $remarks            = $context['remarks'] ?? ($type_value . ' partner payment');

        $saveData = [
            'id'                 => $context['id'] ?? 0,
            'reference_id'       => $reference_id,
            'split_reference_id' => $split_reference_id,
            'device_id'          => $device_id,
            'payment'            => [
                [
                    'type'       => $pay_type,
                    'type_value' => $type_value,
                    'amount'     => floatval($amount),
                    'remarks'    => $remarks
                ]
            ]
        ];

        $saveResponse = $this->_save_split_orderpayment_data($cashierid, $saveData);

        return [
            'status'                  => $saveResponse['status'] ?? true,
            'message'                 => $saveResponse['message'] ?? 'Payment processed successfully',
            'full_payment'            => $saveResponse['full_payment']
        ];
    }

    /**
     * Updates POS bill discount percentage, taxes, and service charge.
     */
    public function update_bills_discount($cashierid = null, $ref_id = null, $disc = null, $sc = null, $type = null, $dish_type = null)
    {
        $this->autoRender = false;
        
        $ref_id = $ref_id ?? $_REQUEST['reference_id'] ?? '';
        $getCashier = $this->Order_transaction->find('first', array(
            'conditions' => array(
                'Order_transaction.reference_id' => $ref_id,
                'NOT' => array('Order_transaction.status' => array(0, 3, 4))
            ),
            'fields' => array('cashier_id','servicecharge'),
            'recursive' => -1
        ));
        
        $cashierid = $cashierid ?? $_REQUEST['id'] ?? $_REQUEST['cashier_id'] ?? ($getCashier['Order_transaction']['cashier_id'] ?? null);
        $serviceCharge = $getCashier['Order_transaction']['servicecharge'] ?? 0;
        if($serviceCharge == 0 || $serviceCharge == null){
            $sc = 0;
        } else {
            $sc = 1;
        }
         
        $disc = $disc ?? $_REQUEST['discount'] ?? '';
        $type = $type ?? $_REQUEST['type'] ?? '';
        $dish_type = $dish_type ?? $_REQUEST['kds'] ?? '';

        if (empty($cashierid)) {
            return json_encode(['status' => false, 'message' => 'Cashier Id is Missing']);
        }
        if (empty($ref_id)) {
            return json_encode(['status' => false, 'message' => 'Table reference Id is Missing']);
        }
        if ($disc === '' || $disc === null) {
            return json_encode(['status' => false, 'message' => 'Discount Missing']);
        }
        if ($type === '' || $type === null) {
            return json_encode(['status' => false, 'message' => 'Discount Type Missing']);
        }
        if (empty($dish_type)) {
            return json_encode(['status' => false, 'message' => 'kds Missing']);
        }

        $discount_type_value = $disc;

        if ($type == 1) {
            $disc = $disc;
        } elseif ($type == 2) {
            if ($dish_type === 'all') {
                $subtotals = $this->Order_orderhistory->query("
                    SELECT SUM(total_price) as subtotal 
                    FROM order_orderhistories 
                    WHERE status!=0 AND reference_id='$ref_id'
                ");
            } else {
                $subtotals = $this->Order_orderhistory->query("
                    SELECT SUM(total_price) as subtotal 
                    FROM order_orderhistories 
                    WHERE status!=0 AND reference_id='$ref_id' 
                    AND dish_type=$dish_type
                ");
            }
            if ($subtotals && !empty($subtotals[0][0]['subtotal'])) {
                $subtotal = $subtotals[0][0]['subtotal'];
                $disc = (($disc * 100) / $subtotal);
            } else {
                return json_encode(['status' => false, 'message' => 'Bill Not Found']);
            }
        }

        if ($disc > 99) {
            return json_encode(['status' => false, 'message' => 'Discount is not more than 99%']);
        }

        if ($dish_type === 'all') {
            $orderdata = $this->ordercompute($ref_id, $cashierid, $sc, $disc);
        } else {
            $this->Order_tax->query("UPDATE order_tax SET discount_percentage='0',sync=0,local_sync=0 WHERE reference_id = '$ref_id'");
            $orderdata = $this->ordercomputewithkds($ref_id, $cashierid, $sc, $disc, $dish_type);
        }

        if ($orderdata['status']) {
            $totalservicechg = $orderdata['totalservicechg'];
            $totalprice      = $orderdata['totalprice'];
            $totaltax        = $orderdata['totaltax'];
            $totaldiscount   = $orderdata['totaldiscount'];
            $exactTotalAmount= (($totalprice + $totaltax + $totalservicechg) - $totaldiscount);
            $round2TotalAmount = round($exactTotalAmount, 2);
            $finalTotalAmount  = round($exactTotalAmount);
            $roundOffAmount    = round($finalTotalAmount - $round2TotalAmount, 2);
            $createtime        = date('H:i:s');

            $this->Order_transaction->query("
                UPDATE order_transactions 
                SET 
                    price = '$totalprice',
                    total_tax='$totaltax',
                    servicecharge='$totalservicechg',
                    discount='$totaldiscount',
                    discount_type='$type',
                    discount_type_value='$discount_type_value',
                    amount='$finalTotalAmount',
                    round='$roundOffAmount',
                    createtime='$createtime',sync=0,local_sync=0
                WHERE reference_id = '$ref_id' and status!=0
            ");

            $getAmount = $this->Order_transaction->find('first', array(
                'conditions' => array(
                    'Order_transaction.reference_id' => $ref_id,
                    'NOT' => array('Order_transaction.status' => array(0, 3, 4))
                ),
                'fields' => array('amount')
            ));
            $updatedAmount = $getAmount['Order_transaction']['amount'] ?? $finalTotalAmount;

            return json_encode([
                'status'         => true,
                'message'        => 'Success!!',
                'updated_amount' => $updatedAmount
            ]);
        } else {
            return json_encode(['status' => false, 'message' => 'Not Saved']);
        }
    }

    public function ordercompute($txnid = null, $user_id = null, $is_sc = 1, $discount = 0)
    {
        $response = ['status' => false];
        if (empty($txnid)) return $response;
        
        if($is_sc == 1){
            $locIdForSc = $this->resolveLocationIdForTxn($txnid, $user_id);
            $sc = $this->getLocationServiceChargeRate($locIdForSc);
        }
        
        // $sc = ($is_sc == 1) ? (defined('servicecharge') ? servicecharge : 0) : 0;

        $priceq = "(Order_orderhistory.total_price - Order_orderhistory.discount)";
        $discq  = "($priceq * $discount / 100)";
        $scq    = "(($priceq - $discq) * $sc / 100)";
        $taxq   = "(($priceq - $discq + $scq) * (Order_orderhistory.total_tax_percentage / 100))";

        $this->Order_orderhistory->virtualFields = [
            'tprice'      => "SUM($priceq)",
            'tdisc'       => "SUM($discq)",
            'tservicechg' => "SUM($scq)",
            'total_tax'   => "SUM($taxq)"
        ];

        $summary = $this->Order_orderhistory->find('first', [
            'conditions' => [
                'Order_orderhistory.reference_id' => $txnid,
                'Order_orderhistory.status !='    => 0
            ],
            'fields' => ['reference_id', 'tprice', 'tdisc', 'tservicechg', 'total_tax'],
            'recursive' => -1
        ]);

        $this->Order_orderhistory->virtualFields = [];
        if (empty($summary['Order_orderhistory']['reference_id'])) return $response;

        $response['status']          = true;
        $response['totalprice']      = round($summary['Order_orderhistory']['tprice'], 2);
        $response['totaldiscount']   = round($summary['Order_orderhistory']['tdisc'], 2);
        $response['totalservicechg'] = round($summary['Order_orderhistory']['tservicechg'], 2);
        $response['totaltax']        = round($summary['Order_orderhistory']['total_tax'], 2);

        $priceq = "(Order_orderhistory.price - Order_orderhistory.discount)";
        $discq  = "($priceq * $discount / 100)";
        $scq    = "(($priceq - $discq) * $sc / 100)";
        $tamountq = "($priceq - $discq + $scq)";

        $this->Order_orderhistory->virtualFields = ['tamount' => $tamountq];

        $items = $this->Order_orderhistory->find('all', [
            'conditions' => [
                'Order_orderhistory.reference_id' => $txnid,
                'Order_orderhistory.status !='    => 0
            ],
            'fields' => ['id', 'product_id', 'price', 'tamount'],
            'recursive' => -1
        ]);

        $this->Order_orderhistory->virtualFields = [];

        foreach ($items as $row) {
            $orderhistory_id = $row['Order_orderhistory']['id'];
            $product_id      = $row['Order_orderhistory']['product_id'];
            $tamount         = round($row['Order_orderhistory']['tamount'], 2);

            $taxItems = $this->Order_tax->find('all', [
                'conditions' => [
                    'Order_tax.reference_id'     => $txnid,
                    'Order_tax.orderhistory_id' => $orderhistory_id,
                    'Order_tax.product_id'      => $product_id,
                    'Order_tax.status !='       => 0
                ],
                'recursive' => -1
            ]);

            foreach ($taxItems as $taxRow) {
                $tax = $taxRow['Order_tax'];
                $taxable_price = round($tamount * $tax['quantity'], 2);
                $tax_value     = round(($taxable_price * $tax['tax_percentage']) / 100, 2);

                try {
                    $this->Order_tax->updateAll(
                        [
                            'Order_tax.taxable_price'       => $taxable_price,
                            'Order_tax.tax_value'           => $tax_value,
                            'Order_tax.discount_percentage' => $discount,
                            'Order_tax.sync'                => 0,
                            'Order_tax.local_sync'          => 0
                        ],
                        ['Order_tax.id' => $tax['id']]
                    );
                } catch (Exception $e) {
                    CakeLog::error('[Order Tax Update Exception] ' . $e->getMessage());
                }
            }
        }

        return $response;
    }

    public function ordercomputewithkds($txnid = null, $user_id = null, $is_sc = 1, $discount = 0, $dish = 0)
    {
        $response = ['status' => false];
        if (empty($txnid)) return $response;
        
        if($is_sc == 1){
            $locIdForSc = $this->resolveLocationIdForTxn($txnid, $user_id);
            $sc = $this->getLocationServiceChargeRate($locIdForSc);
        }
        
        // $sc = ($is_sc == 1) ? (defined('servicecharge') ? servicecharge : 0) : 0;
        $kds = (int)$dish;

        $items = $this->Order_orderhistory->find('all', [
            'conditions' => [
                'Order_orderhistory.reference_id' => $txnid,
                'Order_orderhistory.status !=' => 0
            ],
            'fields' => [
                'Order_orderhistory.id',
                'Order_orderhistory.product_id',
                'Order_orderhistory.dish_type',
                'Order_orderhistory.price',
                'Order_orderhistory.quantity',
                'Order_orderhistory.discount',
                'Order_orderhistory.total_tax_percentage'
            ],
            'recursive' => -1
        ]);

        if (empty($items)) return $response;

        $totalprice = $totaldiscount = $totalservicechg = $totaltax = 0;

        foreach ($items as $row) {
            $item = $row['Order_orderhistory'];
            $basePrice = ($item['price'] * $item['quantity']) - $item['discount'];
            $applyDiscount = ($item['dish_type'] == $kds);
            $itemDiscount = $applyDiscount ? ($basePrice * $discount) / 100 : 0;

            $priceAfterDiscount = $basePrice - $itemDiscount;
            $serviceCharge = ($priceAfterDiscount * $sc) / 100;
            $taxableAmount = $priceAfterDiscount + $serviceCharge;
            $taxValue = ($taxableAmount * $item['total_tax_percentage']) / 100;

            $totalprice += $basePrice;
            $totaldiscount += $itemDiscount;
            $totalservicechg += $serviceCharge;
            $totaltax += $taxValue;
        }

        $taxRows = $this->Order_tax->find('all', [
            'conditions' => [
                'Order_tax.reference_id' => $txnid,
                'Order_tax.status !=' => 0
            ],
            'fields' => [
                'Order_tax.id',
                'Order_tax.orderhistory_id',
                'Order_tax.tax_percentage',
                'Order_tax.quantity'
            ],
            'recursive' => -1
        ]);

        $itemMap = [];
        foreach ($items as $itemRow) {
            $itemMap[$itemRow['Order_orderhistory']['id']] = $itemRow['Order_orderhistory'];
        }

        foreach ($taxRows as $row) {
            $tax = $row['Order_tax'];
            if (!isset($itemMap[$tax['orderhistory_id']])) continue;

            $item = $itemMap[$tax['orderhistory_id']];
            $basePrice = ($item['price'] * $tax['quantity']) - $item['discount'];
            $applyDiscount = ($item['dish_type'] == $kds);
            $itemDiscount = $applyDiscount ? ($basePrice * $discount) / 100 : 0;

            $priceAfterDiscount = $basePrice - $itemDiscount;
            $serviceCharge = ($priceAfterDiscount * $sc) / 100;
            $taxablePrice = round($priceAfterDiscount + $serviceCharge, 2);
            $taxValue = round(($taxablePrice * $tax['tax_percentage']) / 100, 2);
            $discountPercent = $applyDiscount ? (float)$discount : 0.0;

            try {
                $this->Order_tax->updateAll(
                    [
                        'taxable_price' => (float)$taxablePrice,
                        'tax_value' => (float)$taxValue,
                        'discount_percentage' => $discountPercent,
                        'dish_type' => (int)$item['dish_type'],
                        'sync' => 0,
                        'local_sync' => 0
                    ],
                    ['id' => (int)$tax['id']]
                );
            } catch (Exception $e) {
                CakeLog::error('[Order Tax Update Error] ' . $e->getMessage());
            }
        }

        return [
            'status' => true,
            'totalprice' => round($totalprice, 2),
            'totaldiscount' => round($totaldiscount, 2),
            'totalservicechg' => round($totalservicechg, 2),
            'totaltax' => round($totaltax, 2)
        ];
    }

    /**
     * EazyDiner Payment Verification with Branching Logic (1.a, 1.b, 2.a, 2.b).
     */
    public function process_eazydiner_verification($reference_id = null, $order_id = null, $context = [], $eazydiner_outlet_id  = null)
    {
        $this->autoRender = false;
        
        $reference_id = $reference_id ?? $_REQUEST['reference_id'] ?? $context['reference_id'] ?? null;
        $order_id     = $order_id ?? $_REQUEST['order_id'] ?? $context['order_id'] ?? null;
        
        if (empty($reference_id) || empty($order_id)) {
            return json_encode(['status' => false, 'message' => 'reference_id and order_id are required']);
        }

        $txn = $this->Order_transaction->find('first', [
            'conditions' => [
                'Order_transaction.reference_id' => $reference_id,
                'NOT' => ['Order_transaction.status' => [0, 3, 4]]
            ],
            'recursive' => -1
        ]);

        if (empty($txn)) {
            return json_encode(['status' => false, 'message' => 'Active order transaction not found']);
        }

        $bill_amount = floatval($txn['Order_transaction']['amount'] ?? 0);
        $store_id    = $txn['Order_transaction']['store_id'] ?? 0;
        $sc_setting  = $txn['Order_transaction']['servicecharge'] ?? 0;
        $sc          = ($sc_setting == 0 || $sc_setting == null) ? 0 : 1;

        $cashierid          = $context['cashier_id'] ?? $_REQUEST['id'] ?? $txn['Order_transaction']['cashier_id'] ?? 0;
        $split_reference_id = $context['split_reference_id'] ?? $_REQUEST['split_reference_id'] ?? '0';
        $device_id          = $context['device_id'] ?? $_REQUEST['device_id'] ?? '';
        $type_value         = $context['type_value'] ?? $_REQUEST['type_value'] ?? 'EazyDiner';
        $pay_type           = $context['pay_type'] ?? $_REQUEST['type'] ?? 1;
        $remarks            = $context['remarks'] ?? $_REQUEST['remarks'] ?? 'EazyDiner partner payment';
        if (!empty($order_id) && strpos($remarks, 'OrderID:') === false) {
            $remarks = trim($remarks . ' OrderID:' . $order_id);
        }

        $already_paid = 0;
        if (!empty($split_reference_id) && $split_reference_id !== '0') {
            $existing_split_payments = $this->Order_split_payment->query("
                SELECT SUM(amount) AS total_paid 
                FROM order_split_payments 
                WHERE reference_id = '$reference_id' 
                  AND split_reference_id = '$split_reference_id' 
                  AND status != 0
            ");
            $already_paid = floatval($existing_split_payments[0][0]['total_paid'] ?? 0);
        } else {
            $existing_payments = $this->Order_payment->query("
                SELECT SUM(amount) AS total_paid 
                FROM order_payments 
                WHERE reference_id = '$reference_id' 
                  AND status != 0
            ");
            $already_paid = floatval($existing_payments[0][0]['total_paid'] ?? 0);
        }

        $effective_bill = max(0, floatval($bill_amount - $already_paid));

        $execContext = [
            'id'                 => $context['id'] ?? $_REQUEST['id'] ?? 0,
            'cashier_id'         => $cashierid,
            'split_reference_id' => $split_reference_id,
            'device_id'          => $device_id,
            'type_value'         => $type_value,
            'pay_type'           => $pay_type,
            'remarks'            => $remarks
        ];

        if (empty($eazydiner_outlet_id) && !empty($store_id)) {
            $loc = $this->Location->find('first', [
                'conditions' => ['Location.id' => $store_id, 'Location.status' => 1],
                'recursive'  => -1
            ]);
            $eazydiner_outlet_id = $loc['Location']['eazydiner_outlet_id'] ?? '';
        }

        $token  = $this->_get_db_token();
        $config = Configure::read('Eazydiner');
        $url    = $config['status_url'];

        $headers = [
            'secret: ' . ($config['secret'] ?? ''),
            'Content-Type: application/json',
            'version: ' . ($config['version'] ?? ''),
            'token: ' . $token
        ];

        $bodyPayload = [
            'outlet_id' => (string)$eazydiner_outlet_id,
            'order_id'  => (string)$order_id
        ];

        $postParams = [
            'url'         => $url,
            'headers'     => $headers,
            'bodyPayload' => $bodyPayload,
            'token'       => $token,
            'order_id'    => (string)$order_id,
            'outlet_id'   => (string)$eazydiner_outlet_id
        ];

        $cron_status_url = Configure::read('Eazydiner.cron_status_url');
        $statusResJson   = false;

        if (!empty($cron_status_url)) {
            $ch = curl_init($cron_status_url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postParams));
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            curl_setopt($ch, CURLOPT_TIMEOUT, 15);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

            $res = curl_exec($ch);
            $remoteHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if (($remoteHttpCode == 200 || $remoteHttpCode == 201) && !empty($res)) {
                $statusResJson = $res;
            } else {
                CakeLog::error("[Remote get_eazydiner_status HTTP Error {$remoteHttpCode}] Response: " . $res);
            }
        }

        $statusResData = json_decode($statusResJson, true);

        $httpCode    = $statusResData['http_code'] ?? 0;
        $response    = $statusResData['raw_response'] ?? '';
        $bodyPayload = $statusResData['body_payload'] ?? ['outlet_id' => (string)$eazydiner_outlet_id, 'order_id' => (string)$order_id];

        $resData = json_decode($response, true);

        if (empty($resData) || !isset($resData['data']) || !isset($resData['data']['payment_details'])) {
            $errMsg = $this->_parse_eazydiner_error($response, $httpCode);
            $this->_log_eazydiner_response(
                $order_id, $store_id, null, $reference_id,
                $bodyPayload, $response, 'error',
                0, 0, 0, 0, $httpCode, $errMsg
            );
            return json_encode(['status' => false, 'message' => $errMsg]);
        }

        $payment_amount    = floatval($resData['data']['payment_amount'] ?? 0);
        $receivable_amount = floatval($resData['data']['payment_details']['amount_paid'] ?? 0);
        $total_discount    = floatval($resData['data']['payment_details']['total_discount'] ?? 0);

        // =====================================================================
        // CONDITION 1: $payment_amount == $effective_bill
        // =====================================================================
        if (abs($payment_amount - $effective_bill) < 0.01) {

            // CONDITION 1.a: $receivable_amount < $effective_bill (Merchant discount applied)
            if ($receivable_amount < $effective_bill) {
                $target_order_amount = $receivable_amount + $already_paid;
                $calc_pct = $this->_find_matching_discount_percentage($reference_id, $target_order_amount, $sc);
                $updateResJson = $this->update_bills_discount($cashierid, $reference_id, $calc_pct, $sc, 1, 'all');
                $updateRes = json_decode($updateResJson, true);

                $payResult = $this->_save_partner_payment_entry($execContext, $reference_id, $receivable_amount);

                $this->_log_eazydiner_response(
                    $order_id, $store_id, null, $reference_id,
                    ['order_id' => $order_id], $response, 'complete',
                    $payment_amount, $receivable_amount, $total_discount, $calc_pct, $httpCode,
                    'Condition 1.a Executed: Discount applied & payment settled'
                );

                return json_encode($payResult);
            } 
            // CONDITION 1.b: $receivable_amount >= $effective_bill (Direct payment settled)
            else {
                $pay_amount = min($receivable_amount, $effective_bill);
                $payResult = $this->_save_partner_payment_entry($execContext, $reference_id, $pay_amount);

                $this->_log_eazydiner_response(
                    $order_id, $store_id, null, $reference_id,
                    ['order_id' => $order_id], $response, 'complete',
                    $payment_amount, $receivable_amount, $total_discount, 0, $httpCode,
                    'Condition 1.b Executed: Direct payment settled'
                );

                return json_encode($payResult);
            }
        } 
        // =====================================================================
        // CONDITION 2: $payment_amount != $effective_bill
        // =====================================================================
        else {

            // CONDITION 2.a: $payment_amount < $effective_bill
            if ($payment_amount < $effective_bill) {
                
                    // CONDITION 2.new: Small variance tolerance (< 5 rupees)
                    if (abs($effective_bill - $payment_amount) < 5) {
                        
                        $target_order_amount = $receivable_amount + $already_paid;
                        $calc_pct = $this->_find_matching_discount_percentage($reference_id, $target_order_amount, $sc);
                        $updateResJson = $this->update_bills_discount($cashierid, $reference_id, $calc_pct, $sc, 1, 'all');
                        $updateRes = json_decode($updateResJson, true);
        
                        $payResult = $this->_save_partner_payment_entry($execContext, $reference_id, $receivable_amount);
        
                        $this->_log_eazydiner_response(
                            $order_id, $store_id, null, $reference_id,
                            ['order_id' => $order_id], $response, 'complete',
                            $payment_amount, $receivable_amount, $total_discount, $calc_pct, $httpCode,
                            'Condition 2.new Executed: Small variance tolerance'
                        );
    
                    return json_encode($payResult);
                
                }
                
                else{
                    
                    $diff_amount = round($effective_bill - $receivable_amount, 2);
                    $misc_type_value = trim($type_value) . ' Miscellaneous';
    
                    if ($diff_amount > 0) {
                        $this->_create_misc_adjustment($reference_id, $split_reference_id, $store_id, $device_id, $cashierid, $diff_amount, $misc_type_value, $order_id);
                    }
                }
                
                $payResult = $this->_save_partner_payment_entry($execContext, $reference_id, $receivable_amount);

                $this->_log_eazydiner_response(
                    $order_id, $store_id, null, $reference_id,
                    ['order_id' => $order_id], $response, 'complete',
                    $payment_amount, $receivable_amount, $total_discount, 0, $httpCode,
                    'Condition 2.a Executed: Discrepancy miscellaneous entry created & payment settled'
                );

                return json_encode($payResult);
            } 
            // CONDITION 2.b: $payment_amount > $effective_bill
            else {
                $payResult = $this->_save_partner_payment_entry($execContext, $reference_id, $effective_bill);

                $this->_log_eazydiner_response(
                    $order_id, $store_id, null, $reference_id,
                    ['order_id' => $order_id], $response, 'complete',
                    $payment_amount, $receivable_amount, $total_discount, 0, $httpCode,
                    'Condition 2.b Executed: Direct payment settled capped at effective_bill'
                );

                return json_encode($payResult);
            }
        }
    }

    /**
     * Process Zomato Gold verification via Cashless API.
     * Endpoint: https://api.zomato.com/v2.1/cashless/validateTransaction
     */
    public function process_zomato_verification($reference_id, $auth_code, $context = [])
    {
        $this->autoRender = false;
        
        $reference_id = $reference_id ?? $context['reference_id'] ?? $_REQUEST['reference_id'] ?? '';
        $auth_code    = $auth_code ?? $context['auth_code'] ?? $context['order_id'] ?? $_REQUEST['auth_code'] ?? $_REQUEST['order_id'] ?? '';

        if (empty($reference_id) || empty($auth_code)) {
            return json_encode(['status' => false, 'message' => 'Missing reference_id or Zomato auth_code']);
        }

        $txn = $this->Order_transaction->find('first', [
            'conditions' => [
                'Order_transaction.reference_id' => $reference_id,
                'NOT' => array('Order_transaction.status' => array(0, 3, 4))
            ],
            'recursive' => -1
        ]);

        if (empty($txn)) {
            return json_encode(['status' => false, 'message' => 'Transaction not found or invalid status']);
        }

        $bill_amount = floatval($txn['Order_transaction']['amount'] ?? 0);
        $cashierid          = $context['cashier_id'] ?? $_REQUEST['id'] ?? $txn['Order_transaction']['cashier_id'] ?? 0;
        $split_reference_id = $context['split_reference_id'] ?? $_REQUEST['split_reference_id'] ?? '0';
        $device_id          = $context['device_id'] ?? $_REQUEST['device_id'] ?? '';
        $type_value         = $context['type_value'] ?? $_REQUEST['type_value'] ?? 'Zomato Gold';
        $pay_type           = $context['pay_type'] ?? $_REQUEST['type'] ?? 1;
        $remarks            = $context['remarks'] ?? $_REQUEST['remarks'] ?? 'Zomato Gold partner payment';
        $order_date         = $context['order_date'] ?? $_REQUEST['order_date'] ?? date('Y-m-d');

        $already_paid = 0;
        if (!empty($split_reference_id) && $split_reference_id !== '0') {
            $existing_split_payments = $this->Order_split_payment->query("
                SELECT SUM(amount) AS total_paid 
                FROM order_split_payments 
                WHERE reference_id = '$reference_id' 
                  AND split_reference_id = '$split_reference_id' 
                  AND status != 0
            ");
            $already_paid = floatval($existing_split_payments[0][0]['total_paid'] ?? 0);
        } else {
            $existing_payments = $this->Order_payment->query("
                SELECT SUM(amount) AS total_paid 
                FROM order_payments 
                WHERE reference_id = '$reference_id' 
                  AND status != 0
            ");
            $already_paid = floatval($existing_payments[0][0]['total_paid'] ?? 0);
        }

        $effective_bill = max(0, floatval($bill_amount - $already_paid));

        if (!empty($auth_code) && strpos($remarks, 'OrderID:') === false) {
            $remarks = trim($remarks . ' OrderID:' . $auth_code);
        }

        $execContext = [
            'id'                 => $context['id'] ?? $_REQUEST['id'] ?? 0,
            'cashier_id'         => $cashierid,
            'split_reference_id' => $split_reference_id,
            'device_id'          => $device_id,
            'type_value'         => $type_value,
            'pay_type'           => $pay_type,
            'remarks'            => $remarks
        ];

        // Fetch Zomato configuration
        $config    = Configure::read('Zomato');
        $url       = $config['validate_url'];
        $apiKey    = $config['api_key'] ?? '';
        $partnerId = intval($config['partner_id'] ?? 0);
        $debugMode = intval($config['debug'] ?? 1);

        // Fetch Store ID from Location settings
        $user = $this->User->find('first', [
            'conditions' => ['User.id' => $cashierid],
            'recursive'  => -1
        ]);
        $userStoreId = $user['User']['location_id'] ?? '';
        $location = $this->Location->find('first', [
            'conditions' => ['Location.id' => $userStoreId],
            'recursive'  => -1
        ]);

        $zomatoStoreId = $location['Location']['zomato_store_id'];
        $location_id = $location['Location']['location_id'];

        $body = [
            'res_id'            => -1,
            'partner_id'        => $zomatoStoreId,
            'partner_entity_id' => strval($location_id),
            'auth_code'         => intval($auth_code),
            'order_date'        => $order_date,
            'paid_amount'       => round($bill_amount, 2),
            'debug'             => $debugMode
        ];

        if (!empty($auth_code)) {
            $body['order_id'] = strval($auth_code);
        }

        $headers = [
            'X-Zomato-Api-Key: ' . $apiKey,
            'Content-Type: application/json'
        ];

        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $resData = json_decode($response, true);

        // Log transaction to paymentpartner_api_logs
        $logStatus = $resData['status'] ?? ('HTTP_' . $httpCode);
        $this->_log_paymentpartner_response($auth_code, 'Zomato Gold', $reference_id, $resData ?? $response, $logStatus);

        if ($httpCode != 200 || empty($resData)) {
            $errMsg = !empty($resData['message']) ? $resData['message'] : ('HTTP Error ' . $httpCode);
            return json_encode(['status' => false, 'message' => 'Zomato Validation Failed: ' . $errMsg]);
        }

        $statusMessage = strtolower($resData['message'] ?? '');
        $statusKey     = strtolower($resData['status'] ?? '');

        if ($statusKey === 'transaction_found' || $statusKey === 'success' || $statusMessage === 'success') {
            $payment_amount    = floatval($resData['bill_amount'] ?? $resData['payment_amount'] ?? 0);
            $receivable_amount = floatval($resData['amount_receivable'] ?? $resData['net_amount'] ?? 0);
            $total_discount    = floatval($resData['discount_amount'] ?? $resData['discount'] ?? 0);

            $sc_setting = $txn['Order_transaction']['servicecharge'] ?? 0;
            $sc         = ($sc_setting == 0 || $sc_setting == null) ? 0 : 1;

            // =====================================================================
            // CONDITION 1: $payment_amount == $effective_bill
            // =====================================================================
            if (abs($payment_amount - $effective_bill) < 0.01) {

                // CONDITION 1.a: $receivable_amount < $effective_bill
                if ($receivable_amount < $effective_bill) {
                    $target_order_amount = $receivable_amount + $already_paid;
                    $calc_pct = $this->_find_matching_discount_percentage($reference_id, $target_order_amount, $sc);
                    $updateResJson = $this->update_bills_discount($cashierid, $reference_id, $calc_pct, $sc, 1, 'all');
                    $updateRes = json_decode($updateResJson, true);

                    $payResult = $this->_save_partner_payment_entry($execContext, $reference_id, $receivable_amount);

                    $this->_log_paymentpartner_response(
                        $auth_code, 'Zomato Gold', $reference_id, $resData, $logStatus
                    );

                    return json_encode($payResult);
                } 
                // CONDITION 1.b: $receivable_amount >= $effective_bill
                else {
                    $pay_amount = min($receivable_amount, $effective_bill);
                    $payResult = $this->_save_partner_payment_entry($execContext, $reference_id, $pay_amount);

                    $this->_log_paymentpartner_response(
                        $auth_code, 'Zomato Gold', $reference_id, $resData, $logStatus
                    );

                    return json_encode($payResult);
                }
            } 
            // =====================================================================
            // CONDITION 2: $payment_amount != $effective_bill
            // =====================================================================
            else {

                // CONDITION 2.a: $payment_amount < $effective_bill
                if ($payment_amount < $effective_bill) {
                    $diff_amount = round($effective_bill - $receivable_amount, 2);
                    $misc_type_value = trim($type_value) . ' Discrepancy';

                    if ($diff_amount > 0) {
                        $this->_create_misc_adjustment($reference_id, $split_reference_id, $userStoreId, $device_id, $cashierid, $diff_amount, $misc_type_value, $auth_code);
                    }

                    $payResult = $this->_save_partner_payment_entry($execContext, $reference_id, $receivable_amount);

                    $this->_log_paymentpartner_response(
                        $auth_code, 'Zomato Gold', $reference_id, $resData, $logStatus
                    );

                    return json_encode($payResult);
                } 
                // CONDITION 2.b: $payment_amount > $effective_bill
                else {
                    $payResult = $this->_save_partner_payment_entry($execContext, $reference_id, $effective_bill);

                    $this->_log_paymentpartner_response(
                        $auth_code, 'Zomato Gold', $reference_id, $resData, $logStatus
                    );

                    return json_encode($payResult);
                }
            }
        } else {
            $errMsg = $resData['message'] ?? 'Transaction status invalid or refunded';
            return json_encode(['status' => false, 'message' => 'Zomato Transaction Error: ' . $errMsg]);
        }
    }

    /**
     * Primary Payment Partner Dispatch API Endpoint
     */
    public function process_partner_payment()
    {
        $this->autoRender = false;

        $rawBody = file_get_contents('php://input');
        $inputData = json_decode($rawBody, true);
        if (empty($inputData) || !is_array($inputData)) {
            $inputData = $_REQUEST;
            if (isset($_REQUEST['data'])) {
                $decodedData = json_decode($_REQUEST['data'], true);
                if (is_array($decodedData)) {
                    $inputData = array_merge($inputData, $decodedData);
                }
            }
        }
        
        // return json_encode(['status' => false, 'message' => $_REQUEST['data']]);

        $cashierid          = $_REQUEST['id'] ?? '';
        $reference_id       = $inputData['reference_id'] ?? $_REQUEST['reference_id'] ?? '';
        $split_reference_id = $inputData['split_reference_id'] ?? $_REQUEST['split_reference_id'] ?? '0';
        $device_id          = $inputData['device_id'] ?? $_REQUEST['device_id'] ?? '';
        $remarks            = $inputData['remarks'] ?? $_REQUEST['remarks'] ?? '';
        $payments           = $inputData['payment'] ?? [];
        $order_id           = $inputData['order_id'] ?? $_REQUEST['order_id'] ?? $_REQUEST['auth_code'] ?? ($payments[0]['order_id'] ?? $payments[0]['auth_code'] ?? '');
        
        if (empty($cashierid)) {
            return json_encode(['status' => false, 'message' => 'Cashier Id is Missing']);
        }
        if (empty($reference_id)) {
            return json_encode(['status' => false, 'message' => 'Reference Id is Missing']);
        }
        if (empty($payments) || !is_array($payments)) {
            return json_encode(['status' => false, 'message' => 'Payment list is Missing or invalid']);
        }

        $user = $this->User->find('first', [
            'conditions' => ['User.id' => $cashierid, 'User.status' => 1],
            'recursive'  => -1
        ]);
        if (empty($user)) {
            return json_encode(['status' => false, 'message' => 'Cashier not found or inactive']);
        }
        $userStoreId = $user['User']['location_id'] ?? '';
        if(empty($userStoreId)) {
            return json_encode(['status' => false, 'message' => 'Location is not assigned to this cashier']);
        }
        $locationData = $this->Location->find('first', [
            'conditions' => ['Location.id' => $userStoreId, 'Location.status' => 1],
            'recursive'  => -1
        ]);

        $transaction = $this->Order_transaction->find('first', [
            'conditions' => [
                'Order_transaction.reference_id' => $reference_id,
                'NOT' => array('Order_transaction.status' => array(0, 3, 4))
            ],
            'recursive' => -1
        ]);
        

        if (empty($transaction)) {
            return json_encode(['status' => false, 'message' => 'Order transaction not found for this reference_id']);
        }
        
        
        foreach ($payments as &$payItem) {
            $type_value  = trim($payItem['type_value'] ?? '');
            $amount      = floatval($payItem['amount'] ?? 0);
            $pay_type    = $payItem['type'] ?? 1;
            $pay_remarks = !empty($payItem['remarks']) ? $payItem['remarks'] : (!empty($remarks) ? $remarks : '');

            if (!empty($order_id) && strpos($pay_remarks, 'OrderID:') === false) {
                $pay_remarks = trim($pay_remarks . ' OrderID:' . $order_id);
            }
            $payItem['remarks'] = $pay_remarks;
            
            if (strcasecmp($type_value, 'EazyDiner') === 0) {

                if (!empty($order_id)) {
                    $context = [
                        'id'                 => $inputData['id'] ?? 0,
                        'cashier_id'         => $cashierid,
                        'reference_id'       => $reference_id,
                        'split_reference_id' => $split_reference_id,
                        'device_id'          => $device_id,
                        'type_value'         => $type_value,
                        'pay_type'           => $pay_type,
                        'remarks'            => $pay_remarks,
                        'order_id'           => $order_id
                    ];

                    $eazydiner_outlet_id = $locationData['Location']['eazydiner_outlet_id'] ?? '';
                    if (empty($eazydiner_outlet_id)) {
                        return json_encode(['status' => false, 'message' => 'EazyDiner outlet ID is not configured for this location']);
                    }
                    
                    return $this->process_eazydiner_verification($reference_id, $order_id, $context, $eazydiner_outlet_id);
                } else {
                    return json_encode(['status' => false, 'message' => 'EazyDiner order_id is missing']);
                }
            }
            if (strcasecmp($type_value, 'Zomato Gold Test') === 0) {
                $auth_code = $payItem['auth_code'] ?? $payItem['order_id'] ?? $order_id;
                if (!empty($auth_code)) {
                    $context = [
                        'id'                 => $inputData['id'] ?? 0,
                        'cashier_id'         => $cashierid,
                        'reference_id'       => $reference_id,
                        'split_reference_id' => $split_reference_id,
                        'device_id'          => $device_id,
                        'type_value'         => $type_value,
                        'pay_type'           => $pay_type,
                        'remarks'            => $pay_remarks,
                        'order_id'           => $auth_code,
                        'auth_code'          => $auth_code,
                        'order_date'         => $inputData['order_date'] ?? date('Y-m-d')
                    ];

                    return $this->process_zomato_verification($reference_id, $auth_code, $context);
                } else {
                    return json_encode(['status' => false, 'message' => 'Zomato Gold Auth Code is missing']);
                }
            }
        }
        unset($payItem);

        // Standard fallback for non-EazyDiner partner payments
        $saveData = [
            'id'                 => $inputData['id'] ?? 0,
            'reference_id'       => $reference_id,
            'split_reference_id' => $split_reference_id,
            'device_id'          => $device_id,
            'payment'            => $payments
        ];

        $saveResponse = $this->_save_split_orderpayment_data($cashierid, $saveData);

        return json_encode([
            'status'             => $saveResponse['status'] ?? true,
            'message'            => $saveResponse['message'] ?? 'Payment processed successfully',
            'full_payment'       => $saveResponse['full_payment']
        ]);
    }


    public function _save_split_orderpayment_data($cashierid, $data)
    {
        $reference_id = $data['reference_id'];
        $device_id    = $data['device_id'] ?? '';
        
        $transactions = $this->Order_transaction->find('first', array(
            'conditions' => array(
                'Order_transaction.reference_id' => $reference_id,
                'NOT' => array('Order_transaction.status' => array(0, 3, 4))
            )
        ));
        if (empty($transactions)) {
            return ['status' => false, 'message' => "Active transaction not found for reference_id: $reference_id"];
        }
        
        $store_id = $transactions['Order_transaction']['store_id'];
        $is_split = $transactions['Order_transaction']['is_split'];
        
        if ($is_split) {
            $split_id           = $data['id'] ?? 0;
            $split_reference_id = $data['split_reference_id'] ?? '0';
            
            $splittransactions = $this->Order_split_transaction->find('all', [
                'fields' => [
                    'Order_split_transaction.*',
                    'SUM(Order_split_transaction.amount) AS total_amount'
                ],
                'conditions' => [
                    'Order_split_transaction.reference_id'       => $reference_id,
                    'Order_split_transaction.split_reference_id' => $split_reference_id,
                    'Order_split_transaction.status !='          => 0
                ]
            ]);
            
            if (empty($splittransactions[0]['Order_split_transaction']['id'])) {
                return ['status' => false, 'message' => "No active Split Transaction found"];
            }
            
            foreach ($data['payment'] as $payment) {
                if (($payment['type'] ?? 0) == 7) {
                    return ['status' => false, 'message' => "Option not allowed"];
                }
                
                $split_finalamount = $splittransactions[0][0]['total_amount'];
         
                $datas['Order_split_payment'] = array(
                    'split_reference_id' => $split_reference_id,
                    'split_txn_id'       => $split_id,
                    'reference_id'       => $reference_id,
                    'store_id'           => $store_id,
                    'device_id'          => $device_id,
                    'cashier_id'         => $cashierid,
                    'type'               => $payment['type'],
                    'type_value'         => $payment['type_value'],
                    'amount'             => $payment['amount'],
                    'remarks'            => $payment['remarks'] ?? ''
                );
                
                if ($this->Order_split_payment->saveAll($datas['Order_split_payment'])) {
                    $splitPaymentId = $this->Order_split_payment->id;
                    $type_value     = $payment['type_value'];

                    $split_allpayments  = $this->Order_split_payment->query("SELECT sum(amount) as pay_amount FROM order_split_payments WHERE split_reference_id='$split_reference_id' and reference_id='$reference_id' and status!=0");
                    $split_finalpayment = $split_allpayments[0][0]['pay_amount'] ?? 0;
                    $split_amount_diff  = round(($split_finalamount - $split_finalpayment), 2);
                    
                    if ($split_amount_diff < 1) {
                        $this->Order_split_orderhistory->query("UPDATE order_split_orderhistories SET status = 2,sync=0,local_sync=0 WHERE split_reference_id='$split_reference_id' and reference_id = '$reference_id' and status = 1");
                        $this->Order_split_transaction->query("UPDATE order_split_transactions SET status = 2,payment_mode='$type_value',sync=0,local_sync=0 WHERE split_reference_id='$split_reference_id' and reference_id = '$reference_id' and status!=0");
                        $this->Order_split_payment->query("UPDATE order_split_payments SET status = 2,sync=0,local_sync=0 WHERE split_reference_id='$split_reference_id' and reference_id = '$reference_id' and status!=0");
                    }
                    
                    $datas['Order_payment'] = array(
                        'reference_id' => $reference_id,
                        'store_id'     => $store_id,
                        'device_id'    => $device_id,
                        'cashier_id'   => $cashierid,
                        'type'         => $payment['type'],
                        'type_value'   => $payment['type_value'],
                        'amount'       => $payment['amount'],
                        'remarks'      => $payment['remarks'] ?? ''
                    );
                    
                    if ($this->Order_payment->saveAll($datas['Order_payment'])) {
                        $mainpayid = $this->Order_payment->id;
                        $this->Order_split_payment->query("UPDATE order_split_payments SET main_payment_id = '$mainpayid',sync=0,local_sync=0 WHERE id='$splitPaymentId' and reference_id = '$reference_id' and status!=0");
                        
                        $allpayments  = $this->Order_payment->query("SELECT sum(amount) as pay_amount FROM order_payments WHERE reference_id='$reference_id' and status!=0");
                        $finalpayment = $allpayments[0][0]['pay_amount'] ?? 0;
                        $freshTxn     = $this->Order_transaction->query("SELECT amount FROM order_transactions WHERE reference_id='$reference_id' AND status!=0");
                        $finalamount  = floatval($freshTxn[0]['order_transactions']['amount'] ?? $transactions['Order_transaction']['amount']);
                        $amount_diff  = $finalamount - $finalpayment;
                        
                        if ($amount_diff <= 1) {
                            $this->Order_orderhistory->query("UPDATE order_orderhistories SET status = 2,sync=0,local_sync=0 WHERE reference_id = '$reference_id' and status = 1");
                            $this->Order_transaction->query("UPDATE order_transactions SET status = 2,payment_mode='$type_value',sync=0,local_sync=0 WHERE reference_id = '$reference_id' and status!=0");
                            $this->Order_payment->query("UPDATE order_payments SET status = 2,sync=0,local_sync=0 WHERE reference_id = '$reference_id' and status!=0");
                            $this->Order_tax->query("UPDATE order_tax SET status = 2,sync=0,local_sync=0 WHERE reference_id = '$reference_id' and status != 0");
                            
                            do {
                                $pin = 'TX' . $cashierid . date('Y') . uniqid();
                                // $pin = uniqid();
                            } while ($this->Order_table->find('first', array('fields' => 'Order_table.id', 'conditions' => array('Order_table.reference_id' => $pin))));
                            
                            $tablestatus = $this->Order_table->find('first', array('conditions'=>array('reference_id'=>$reference_id,'is_takeaway'=>1)));
                            if(!empty($tablestatus))
                            {   
                                $this->Order_table->query("UPDATE order_tables SET status=0,sync=0,local_sync=0,modified=NOW()  WHERE reference_id = '$reference_id'");
                            }else{
                                $this->Order_table->query("UPDATE order_tables SET customer_name='',customer_phone='',customer_address='',customer_pax=1,status = 1,reference_id='$pin',is_alert=0,sync=0,local_sync=0,modified=NOW() WHERE reference_id = '$reference_id'");
                            }
                            
                            return ['status' => true, 'message' => "Payment Data Saved Successfully..!", 'full_payment' => true];
                        } else {
                            return ['status' => true, 'message' => "Payment Data Saved Successfully..!", 'full_payment' => ($split_amount_diff < 1)];
                        }
                    }
                }
            }
        } else {
            foreach ($data['payment'] as $payment) {
                $type_value = $payment['type_value'];
                $datas['Order_payment'] = array(
                    'reference_id' => $reference_id,
                    'store_id'     => $store_id,
                    'device_id'    => $device_id,
                    'cashier_id'   => $cashierid,
                    'type'         => $payment['type'],
                    'type_value'   => $payment['type_value'],
                    'amount'       => $payment['amount'],
                    'remarks'      => $payment['remarks'] ?? ''
                );
                    if ($this->Order_payment->saveAll($datas['Order_payment'])) {
                        $allpayments  = $this->Order_payment->query("SELECT sum(amount) as pay_amount FROM order_payments WHERE reference_id='$reference_id' and status!=0");
                        $finalpayment = $allpayments[0][0]['pay_amount'] ?? 0;
                        $freshTxn     = $this->Order_transaction->query("SELECT amount FROM order_transactions WHERE reference_id='$reference_id' AND status!=0");
                        $finalamount  = floatval($freshTxn[0]['order_transactions']['amount'] ?? $transactions['Order_transaction']['amount']);
                        $amount_diff  = $finalamount - $finalpayment;
                    
                    if ($amount_diff < 1) {
                        $this->Order_orderhistory->query("UPDATE order_orderhistories SET status = 2,sync=0,local_sync=0 WHERE reference_id = '$reference_id' and status = 1");
                        $this->Order_transaction->query("UPDATE order_transactions SET status = 2,payment_mode='$type_value',sync=0,local_sync=0 WHERE reference_id = '$reference_id' and status!=0");
                        $this->Order_payment->query("UPDATE order_payments SET status = 2,sync=0,local_sync=0 WHERE reference_id = '$reference_id' and status!=0");
                        $this->Order_tax->query("UPDATE order_tax SET status = 2,sync=0,local_sync=0 WHERE reference_id = '$reference_id' and status != 0");

                        do {
                                $pin = 'TX' . $cashierid . date('Y') . uniqid();
                            } while ($this->Order_table->find('first', array('fields' => 'Order_table.id', 'conditions' => array('Order_table.reference_id' => $pin))));
                            
                            $tablestatus = $this->Order_table->find('first', array('conditions'=>array('reference_id'=>$reference_id,'is_takeaway'=>1)));
                            if(!empty($tablestatus))
                            {   
                                $this->Order_table->query("UPDATE order_tables SET status=0,sync=0,local_sync=0,modified=NOW()  WHERE reference_id = '$reference_id'");
                            }else{
                                $this->Order_table->query("UPDATE order_tables SET customer_name='',customer_phone='',customer_address='',customer_pax=1,status = 1,reference_id='$pin',is_alert=0,sync=0,local_sync=0,modified=NOW() WHERE reference_id = '$reference_id'");
                            }
        
                            $stockupdate=$this->stock_update($reference_id);
                            if(empty($stockupdate['status'])){
                                return ['status' => true, 'message' => "Payment Data Saved Successfully..! But stock not updated", 'full_payment' => true];
                            }
                        
                        return ['status' => true, 'message' => "Payment Data Saved Successfully..!", 'full_payment' => true];
                    } else {
                        return ['status' => true, 'message' => "Payment Data Saved Successfully..!", 'full_payment' => false];
                    }
                }
            }
        }
        return ['status' => false, 'message' => "Payment Data not saved"];
    } 
   

    public function stock_update($txnid=null){
        $response['status']=false;
        $response['message']='Stock update failed';
        
        $txnid = $txnid ?? $_REQUEST['reference_id'] ?? $_REQUEST['txnid'] ?? null;
        if (empty($txnid)) {
            $response['message'] = 'Transaction ID is missing';
            return $response;
        }

        if (!isset($this->Inventory_linking)) $this->Inventory_linking = ClassRegistry::init('Inventory_linking');
        if (!isset($this->Inventory_semifinished)) $this->Inventory_semifinished = ClassRegistry::init('Inventory_semifinished');
        if (!isset($this->Inventory_sku)) $this->Inventory_sku = ClassRegistry::init('Inventory_sku');
        if (!isset($this->Stock_ledger)) $this->Stock_ledger = ClassRegistry::init('Stock_ledger');
        if (!isset($this->Order_modifier)) $this->Order_modifier = ClassRegistry::init('Order_modifier');
        
        $orderhistories=$this->Order_orderhistory->find('all',array('conditions'=>array('Order_orderhistory.reference_id'=>$txnid,'status' =>2)));
        if (empty($orderhistories)) {
            $response['message'] = 'No completed order history records found for stock update';
            return $response;
        }

        $check = true;
        foreach($orderhistories as $data){
            $product_id=$data['Order_orderhistory']['product_id'];
            $product_quantity=$data['Order_orderhistory']['quantity'];
            $cashier_id=$data['Order_orderhistory']['cashier_id'];
            
            $store=$this->User->find('first',array('conditions'=>array('User.id'=>$cashier_id,'status' =>1)));
            $store_id=$store['User']['location_id'] ?? null;
            if (empty($store_id)) {
                continue;
            }
            
            //product linking with semifinished 
            // $semifinished_products=$this->Inventory_product_semifinished_linking->find('all',array('conditions'=>array('product_id'=>$product_id,'status' =>1)));
            $semifinished_products=$this->Inventory_linking->find('all',array('conditions'=>array('parent_type'=>'Product','parent_id'=>$product_id,'component_type'=>'Semifinished','status' =>1)));
            foreach($semifinished_products as $semifinished_product){
                $semifinished_id=$semifinished_product['Inventory_linking']['component_id'];
                $semiquantity=$semifinished_product['Inventory_linking']['quantity']*$product_quantity;
                
                $semidetails=$this->Inventory_semifinished->find('first',array('conditions'=>array('id'=>$semifinished_id)));
                $semiprice=$semidetails['Inventory_semifinished']['price'] ?? 0;
                
                   
                    $ledgerprevdetails = $this->Stock_ledger->find('first', [
                        'conditions' => ['sku_id' => $semifinished_product['Inventory_linking']['component_id'],'store_id' =>$store_id,'type'=>'OUTLET','item_type'=>'semifinished' ],
                        'fields' => ['actual_balance'],
                        'order' => ['id DESC'],
                        'recursive' => -1
                    ]);
                    
                    $ledger_prev_quantity = !empty($ledgerprevdetails['Stock_ledger']['actual_balance']) 
                        ? $ledgerprevdetails['Stock_ledger']['actual_balance'] 
                        : 0;
                    
                    $newQuantity = $ledger_prev_quantity-$semiquantity;
                    
                    $ledgerData['Stock_ledger'] = [
                            'store_id'        => $store_id,
                            'sku_id'          => $semifinished_product['Inventory_linking']['component_id'],
                            'sku_name'        => $semifinished_product['Inventory_linking']['component_name'],
                            'sku_price'       => $semiprice,
                            'unit_id'         => $semifinished_product['Inventory_linking']['component_unit'],
                            'unit_name'       => $semifinished_product['Inventory_linking']['component_unit_name'],
                            'record_id'       => $txnid,
                            'record_type'     => 'stockout',
                            'quantity'        => $semiquantity,
                            'balance_stock'   => $newQuantity,
                            'actual_balance'  => $newQuantity,
                            'quantity_type'   => 'debit',
                            'type'            => 'OUTLET',
                            'item_type'       => 'semifinished',
                            'created'         => date('Y-m-d H:i:s')
                    ];
                    if(!$this->Stock_ledger->saveAll($ledgerData['Stock_ledger'])){
                        $check=false;
                    }
                    
                    
                    //semifinished linking with sku
                    $sku_products=$this->Inventory_linking->find('all',array('conditions'=>array('parent_type'=>'Semifinished','parent_id'=>$semifinished_id,'component_type'=>'Sku','status' =>1)));
                    foreach($sku_products as $sku_product){
                        $sku_id=$sku_product['Inventory_linking']['component_id'];
                        $skuquantity=$sku_product['Inventory_linking']['quantity']*$semiquantity;
                        
                        $skudetails=$this->Inventory_sku->find('first',array('conditions'=>array('id'=>$sku_id)));
                        $skuprice=$skudetails['Inventory_sku']['price'] ?? 0;
                        
                        
                            $ledgerprevdetails = $this->Stock_ledger->find('first', [
                                'conditions' => ['sku_id' => $sku_product['Inventory_linking']['component_id'],'store_id' =>$store_id ,'type' =>'OUTLET','item_type'=>'SKU' ],
                                'fields' => ['actual_balance'],
                                'order' => ['id DESC'],
                                'recursive' => -1
                            ]);
                            
                            $ledger_prev_quantity = !empty($ledgerprevdetails['Stock_ledger']['actual_balance']) 
                                ? $ledgerprevdetails['Stock_ledger']['actual_balance'] 
                                : 0;
                            
                            $newQuantity = $ledger_prev_quantity-$skuquantity;
                            
                            $ledgerData['Stock_ledger'] = [
                                    'store_id'        => $store_id,
                                    'sku_id'          => $sku_product['Inventory_linking']['component_id'],
                                    'sku_name'        => $sku_product['Inventory_linking']['component_name'],
                                    'sku_price'       => $skuprice,
                                    'unit_id'         => $sku_product['Inventory_linking']['component_unit'],
                                    'unit_name'       => $sku_product['Inventory_linking']['component_unit_name'],
                                    'record_id'       => $txnid,
                                    'record_type'     => 'stockout',
                                    'quantity'        => $skuquantity,
                                    'balance_stock'   => $newQuantity,
                                    'actual_balance'  => $newQuantity,
                                    'quantity_type'   => 'debit',
                                    'type'            => 'OUTLET',
                                    'item_type'       => 'SKU',
                                    'created'         => date('Y-m-d H:i:s')
                            ];
                            if(!$this->Stock_ledger->saveAll($ledgerData['Stock_ledger'])){
                                $check=false;
                            }
                        
                    }
                    
                    
                    //semifinished linking with semifinished
                    $linked_semi_products=$this->Inventory_linking->find('all',array('conditions'=>array('parent_type'=>'Semifinished','parent_id'=>$semifinished_id,'component_type'=>'Semifinished','status' =>1)));
                    foreach($linked_semi_products as $linked_semi_product){
                        $linked_semi_id=$linked_semi_product['Inventory_linking']['component_id'];
                        $linked_semi_quantity=$linked_semi_product['Inventory_linking']['quantity']*$semiquantity;
                        
                        $linkedsemidetails=$this->Inventory_semifinished->find('first',array('conditions'=>array('id'=>$linked_semi_id)));
                        $linkedsemiprice=$linkedsemidetails['Inventory_semifinished']['price'] ?? 0;
                        
                            $ledgerprevdetails = $this->Stock_ledger->find('first', [
                                'conditions' => ['sku_id' => $linked_semi_id,'store_id' =>$store_id ,'type' =>'OUTLET','item_type'=>'Semifinished' ],
                                'fields' => ['actual_balance'],
                                'order' => ['id DESC'],
                                'recursive' => -1
                            ]);
                            
                            $ledger_prev_quantity = !empty($ledgerprevdetails['Stock_ledger']['actual_balance']) 
                                ? $ledgerprevdetails['Stock_ledger']['actual_balance'] 
                                : 0;
                            
                            $newQuantity = $ledger_prev_quantity-$linked_semi_quantity;
                            
                            $ledgerData['Stock_ledger'] = [
                                    'store_id'        => $store_id,
                                    'sku_id'          => $linked_semi_id,
                                    'sku_name'        => $linkedsemidetails['Inventory_semifinished']['name'] ?? '',
                                    'sku_price'       => $linkedsemiprice,
                                    'unit_id'         => $linkedsemidetails['Inventory_semifinished']['unit'] ?? '',
                                    'unit_name'       => $linkedsemidetails['Inventory_semifinished']['unit_name'] ?? '',
                                    'record_id'       => $txnid,
                                    'record_type'     => 'stockout',
                                    'quantity'        => $linked_semi_quantity,
                                    'balance_stock'   => $newQuantity,
                                    'actual_balance'  => $newQuantity,
                                    'quantity_type'   => 'debit',
                                    'type'            => 'OUTLET',
                                    'item_type'       => 'Semifinished',
                                    'created'         => date('Y-m-d H:i:s')
                            ];
                            if(!$this->Stock_ledger->saveAll($ledgerData['Stock_ledger'])){
                                $check=false;
                            }
                        
                    }
                    
            }
            
            //product linking with direct sku 
            $sku_direct_products=$this->Inventory_linking->find('all',array('conditions'=>array('parent_type'=>'Product','parent_id'=>$product_id,'component_type'=>'Sku','status' =>1)));
            foreach($sku_direct_products as $sku_direct_product){
                $sku_id=$sku_direct_product['Inventory_linking']['component_id'];
                //sku previous stock
                $skudirectquantity=$sku_direct_product['Inventory_linking']['quantity']*$product_quantity;
                $skudetails=$this->Inventory_sku->find('first',array('conditions'=>array('id'=>$sku_id)));
                $skuprice=$skudetails['Inventory_sku']['price'] ?? 0;
                
                    
                    $ledgerprevdetails = $this->Stock_ledger->find('first', [
                        'conditions' => ['sku_id' => $sku_id,'store_id' =>$store_id ,'type' =>'OUTLET','item_type'=>'SKU' ],
                        'fields' => ['actual_balance'],
                        'order' => ['id DESC'],
                        'recursive' => -1
                    ]);
                    
                    $ledger_prev_quantity = !empty($ledgerprevdetails['Stock_ledger']['actual_balance']) 
                        ? $ledgerprevdetails['Stock_ledger']['actual_balance'] 
                        : 0;
                    
                    $newQuantity = $ledger_prev_quantity-$skudirectquantity;
                    
                    $ledgerData['Stock_ledger'] = [
                            'store_id'        => $store_id,
                            'sku_id'          => $sku_direct_product['Inventory_linking']['component_id'],
                            'sku_name'        => $sku_direct_product['Inventory_linking']['component_name'],
                            'sku_price'       => $skuprice,
                            'unit_id'         => $sku_direct_product['Inventory_linking']['component_unit'],
                            'unit_name'       => $sku_direct_product['Inventory_linking']['component_unit_name'],
                            'record_id'       => $txnid,
                            'record_type'     => 'stockout',
                            'quantity'        => $skudirectquantity,
                            'balance_stock'   => $newQuantity,
                            'actual_balance'  => $newQuantity,
                            'quantity_type'   => 'debit',
                            'type'            => 'OUTLET',
                            'item_type'       => 'SKU',
                            'created'         => date('Y-m-d H:i:s')
                    ];
                    if(!$this->Stock_ledger->saveAll($ledgerData['Stock_ledger'])){
                        $check=false;
                    }
                
            }
            
            //modifier linking with products 
            $ordermodifiers=$this->Order_modifier->find('all',array('conditions'=>array('reference_id'=>$txnid,'product_id'=>$product_id,'status' =>1)));
            foreach($ordermodifiers as $modifier){
                $modifier_id=$modifier['Order_modifier']['modifier_id'];
                $quantity=$modifier['Order_modifier']['quantity'];
                
                //modifier linking with semifinished 
                $semifinished_products=$this->Inventory_linking->find('all',array('conditions'=>array('parent_type'=>'Modifier','parent_id'=>$modifier_id,'component_type'=>'Semifinished','status' =>1)));
                foreach($semifinished_products as $semifinished_product){
                    $semifinished_id=$semifinished_product['Inventory_linking']['component_id'];
                    $semiquantity=$semifinished_product['Inventory_linking']['quantity']*$quantity;
                    
                    $semidetails=$this->Inventory_semifinished->find('first',array('conditions'=>array('id'=>$semifinished_id)));
                    $semiprice=$semidetails['Inventory_semifinished']['price'] ?? 0;
                    
                        $ledgerprevdetails = $this->Stock_ledger->find('first', [
                            'conditions' => ['sku_id' => $semifinished_id,'store_id' =>$store_id,'type'=>'OUTLET','item_type'=>'semifinished' ],
                            'fields' => ['actual_balance'],
                            'order' => ['id DESC'],
                            'recursive' => -1
                        ]);
                        
                        $ledger_prev_quantity = !empty($ledgerprevdetails['Stock_ledger']['actual_balance']) 
                            ? $ledgerprevdetails['Stock_ledger']['actual_balance'] 
                            : 0;
                        
                        $newQuantity = $ledger_prev_quantity-$semiquantity;
                        
                        $ledgerData['Stock_ledger'] = [
                                'store_id'        => $store_id,
                                'sku_id'          => $semifinished_id,
                                'sku_name'        => $semifinished_product['Inventory_linking']['component_name'],
                                'sku_price'       => $semiprice,
                                'unit_id'         => $semifinished_product['Inventory_linking']['component_unit'],
                                'unit_name'       => $semifinished_product['Inventory_linking']['component_unit_name'],
                                'record_id'       => $txnid,
                                'record_type'     => 'stockout_modifier',
                                'quantity'        => $semiquantity,
                                'balance_stock'   => $newQuantity,
                                'actual_balance'  => $newQuantity,
                                'quantity_type'   => 'debit',
                                'type'            => 'OUTLET',
                                'item_type'       => 'semifinished',
                                'created'         => date('Y-m-d H:i:s')
                        ];
                        if(!$this->Stock_ledger->saveAll($ledgerData['Stock_ledger'])){
                            $check=false;
                        }
                        
                        //semifinished linking with sku
                        $sku_products=$this->Inventory_linking->find('all',array('conditions'=>array('parent_type'=>'Semifinished','parent_id'=>$semifinished_id,'component_type'=>'Sku','status' =>1)));
                        foreach($sku_products as $sku_product){
                            $sku_id=$sku_product['Inventory_linking']['component_id'];
                            $skuquantity=$sku_product['Inventory_linking']['quantity']*$semiquantity;
                            
                            $skudetails=$this->Inventory_sku->find('first',array('conditions'=>array('id'=>$sku_id)));
                            $skuprice=$skudetails['Inventory_sku']['price'] ?? 0;
                            
                                $ledgerprevdetails = $this->Stock_ledger->find('first', [
                                    'conditions' => ['sku_id' => $sku_id,'store_id' =>$store_id ,'type' =>'OUTLET','item_type'=>'SKU' ],
                                    'fields' => ['actual_balance'],
                                    'order' => ['id DESC'],
                                    'recursive' => -1
                                ]);
                                
                                $ledger_prev_quantity = !empty($ledgerprevdetails['Stock_ledger']['actual_balance']) 
                                    ? $ledgerprevdetails['Stock_ledger']['actual_balance'] 
                                    : 0;
                                
                                $newQuantity = $ledger_prev_quantity-$skuquantity;
                                
                                $ledgerData['Stock_ledger'] = [
                                        'store_id'        => $store_id,
                                        'sku_id'          => $sku_id,
                                        'sku_name'        => $sku_product['Inventory_linking']['component_name'],
                                        'sku_price'       => $skuprice,
                                        'unit_id'         => $sku_product['Inventory_linking']['component_unit'],
                                        'unit_name'       => $sku_product['Inventory_linking']['component_unit_name'],
                                        'record_id'       => $txnid,
                                        'record_type'     => 'stockout_modifier',
                                        'quantity'        => $skuquantity,
                                        'balance_stock'   => $newQuantity,
                                        'actual_balance'  => $newQuantity,
                                        'quantity_type'   => 'debit',
                                        'type'            => 'OUTLET',
                                        'item_type'       => 'SKU',
                                        'created'         => date('Y-m-d H:i:s')
                                ];
                                if(!$this->Stock_ledger->saveAll($ledgerData['Stock_ledger'])){
                                    $check=false;
                                }
                            
                        }
                    
                }
                
                //modifier linking with direct sku 
                $sku_direct_products=$this->Inventory_linking->find('all',array('conditions'=>array('parent_type'=>'Modifier','parent_id'=>$modifier_id,'component_type'=>'Sku','status' =>1)));
                foreach($sku_direct_products as $sku_direct_product){
                    $sku_id=$sku_direct_product['Inventory_linking']['component_id'];
                    //sku previous stock
                    $skudirectquantity=$sku_direct_product['Inventory_linking']['quantity']*$quantity;
                    $skudetails=$this->Inventory_sku->find('first',array('conditions'=>array('id'=>$sku_id)));
                    $skuprice=$skudetails['Inventory_sku']['price'] ?? 0;
                    
                        
                        $ledgerprevdetails = $this->Stock_ledger->find('first', [
                            'conditions' => ['sku_id' => $sku_id,'store_id' =>$store_id ,'type' =>'OUTLET' ,'item_type'=>'SKU'],
                            'fields' => ['actual_balance'],
                            'order' => ['id DESC'],
                            'recursive' => -1
                        ]);
                        
                        $ledger_prev_quantity = !empty($ledgerprevdetails['Stock_ledger']['actual_balance']) 
                            ? $ledgerprevdetails['Stock_ledger']['actual_balance'] 
                            : 0;
                        
                        $newQuantity = $ledger_prev_quantity-$skudirectquantity;
                        
                        $ledgerData['Stock_ledger'] = [
                                'store_id'        => $store_id,
                                'sku_id'          => $sku_direct_product['Inventory_linking']['component_id'],
                                'sku_name'        => $sku_direct_product['Inventory_linking']['component_name'],
                                'sku_price'       => $skuprice,
                                'unit_id'         => $sku_direct_product['Inventory_linking']['component_unit'],
                                'unit_name'       => $sku_direct_product['Inventory_linking']['component_unit_name'],
                                'record_id'       => $txnid,
                                'record_type'     => 'stockout_modifier',
                                'quantity'        => $skudirectquantity,
                                'balance_stock'   => $newQuantity,
                                'actual_balance'  => $newQuantity,
                                'quantity_type'   => 'debit',
                                'type'            => 'OUTLET',
                                'item_type'       => 'SKU',
                                'created'         => date('Y-m-d H:i:s')
                        ];
                        if(!$this->Stock_ledger->saveAll($ledgerData['Stock_ledger'])){
                            $check=false;
                        }
                
                }
                
            }
        }
        if($check){
            $response['status']=true;
            $response['message']='Stock updated successfully';
        }
        return $response;
    }

}
