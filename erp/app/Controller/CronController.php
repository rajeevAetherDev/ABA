<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
App::uses('UtilityController', 'Controller');
App::uses('AppController', 'Controller');
App::uses('CakeTime', 'Utility');
App::uses('ConnectionManager', 'Model');
/**
 * CakePHP ApisController
 * @author aetherss
 */
class CronController extends AppController {
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
 
    public function close_open_table()
    {
        $this->autoRender = false;
        $this->layout = false;
        $this->loadModel('Order_table');
        $currentTime = date('Y-m-d H:i:s');

        try {
            $result = $this->Order_table->query("UPDATE order_tables SET status = '1', modified='$currentTime'  WHERE status = 2");
    
            $response = [
                'status' => true,
                'message' => 'done..!',
            ];
        } catch (Exception $e) {
            $response = [
                'status' => false,
                'message' => 'Failed: ' . $e->getMessage(),
            ];
        }
    
        echo json_encode($response);
        exit;
    }
   
    public function sync_to_pos()
    {
        // Get all unprocessed online orders
        $webhooks = $this->Online_order->find('all', [
            'conditions' => ['Online_order.cron_run' => 0],
            'limit' => 10,
            'order' => ['Online_order.id' => 'ASC']
        ]);
    
        foreach ($webhooks as $webhook) {
            $remarks = [];
            $data = [];
    
            $response = json_decode($webhook['Online_order']['response']);
            $txnid = $webhook['Online_order']['reference_id'];
    
            if (empty($response->order->items)) {
                $remarks[] = "Missing order items for txn $txnid";
                continue;
            }
            
            $trxchk=$this->Order_transaction->query("SELECT id FROM `order_transactions` WHERE reference_id='$txnid'");
            if(empty($trxchk)){
            
                $items = $response->order->items;
                $details = $response->order->details;
                $store = $response->store;
                $payments = $response->order->payment ?? [];
        
                $deliverydatetime = date("Y-m-d H:i:s", $details->delivery_datetime / 1000);
                $created = date("Y-m-d H:i:s", $details->created / 1000);
        
                // Initialize totals
                $total_tax_value = 0;
                $total_pack_charge = 0;
                $total_pack_taxes= 0;
                
                // Calculate packaging charges
                if (!empty($details->charges)) {
                    foreach ($details->charges as $char) {
                        $title = strtolower($char->title);
                        if ($title === 'packaging charge' || $title === 'restaurant packaging charges') {
                            $total_pack_charge += $char->value;
                            foreach ($char->taxes as $char_taxes) {
                                $total_pack_taxes += $char_taxes->value;
                            }
                        }
                    }
                }
                
                // Process each item
                $total_amount=0;
                foreach ($items as $item) {
                    $item_tax_value = 0;
                    $item_tax_rate = 0;
        
                    // Sum item taxes
                    if (!empty($item->taxes)) {
                        foreach ($item->taxes as $tax) {
                            $item_tax_value += $tax->value;
                            $item_tax_rate += $tax->rate;
                        }
                    }
                    $total_tax_value += $item_tax_value;
        
                    // Total price per item after discount
                    $ttlprice = ($item->price * $item->quantity) - ($item->discount ?? 0);
                    $total_amount += $ttlprice;
                    // Save order history
                    // $orderHistoryData = [
                    //     'reference_id'         => $txnid,
                    //     'cashier_id'           => 0,
                    //     'category'             => $item->category->id?$item->category->id:0,
                    //     'category_name'        => $item->category->name?$item->category->name:'',
                    //     'product_id'           => $item->merchant_id?$item->merchant_id:0,
                    //     'product_name'         => $item->title?$item->title:'',
                    //     'quantity'             => $item->quantity?$item->quantity:0,
                    //     'price'                => $item->price?$item->price:0,
                    //     'total_tax'            => $item_tax_value?$item_tax_value:0,
                    //     'total_tax_percentage' => $item_tax_rate?$item_tax_rate:0,
                    //     'total_price'          => $ttlprice?$ttlprice:0,
                    //     'discount'             => $item->discount ?? 0,
                    //     'status'               => 2
                    // ];
                    $orderHistoryData = [
                        'reference_id'         => $txnid,
                        'cashier_id'           => 2,
                        'category'             => $item->category->id?$item->category->id:0,
                        'category_name'        => $item->category->name?$item->category->name:'',
                        'product_id'           => $item->merchant_id?$item->merchant_id:0,
                        'product_name'         => $item->title?$item->title:'',
                        'quantity'             => $item->quantity?$item->quantity:0,
                        'price'                => $item->price?$item->price:0,
                        'total_tax'            => 0,
                        'total_tax_percentage' => 0,
                        'total_price'          => $ttlprice?$ttlprice:0,
                        'discount'             => $item->discount ?? 0,
                        'status'               => 2,
                        'created'              => $created
                    ];
                    
                    $this->Order_orderhistory->create();
                    $this->Order_orderhistory->save($orderHistoryData);
                    $lastorder_id = $this->Order_orderhistory->getLastInsertID();
    
                    $discount_percentage= ( $item->discount * 100 ) / $item->price ;
                    // Save taxes per item
                    
                    $ordertaxData = [
                        'orderhistory_id' => $lastorder_id, // Fill after saving order history if needed
                        'cashier_id'      => 2,
                        'product_id'      => $item->merchant_id ?? 0,
                        'product_name'    => $item->title ?? '',
                        'price'           => $item->price ?? 0,
                        'taxable_price'   => $ttlprice ?? 0,
                        'cat_id'          => $item->category->id ?? 0,
                        'cat_name'        => $item->category->name ?? '',
                        'discount_percentage' => round($discount_percentage,2),
                        'quantity'        => $item->quantity ?? 0,
                        'tax_id'          => 0,
                        'tax_name'        => 'Online',
                        'tax_percentage'  => 0,
                        'tax_value'       => 0,
                        'reference_id'    => $txnid ?? 0,
                        'status'          => 2,
                        'created'         => $created
                    ];
                    $this->Order_tax->create();
                    $this->Order_tax->save($ordertaxData);
                }
                    
                $currenttime = date("H:i:s", $details->delivery_datetime / 1000);
                
                $finalamount = round($total_amount);        
                $round_amount = round($total_amount,2)-round($total_amount);
                
                $todayDate = date("Y-m-d");
                $tdaytrx=$this->Order_transaction->query("SELECT COUNT(id) as tkn FROM `order_transactions` WHERE date(created)='$todayDate'");
                $token=$tdaytrx['0']['0']['tkn']+1;
                
                $deliveryother=0;
                $deliveryother=round((($details->total_charges)-$total_pack_charge),2);


                // Save transaction
                $data['Order_transaction'][] = [
                        'reference_id'=>$txnid,
                        'cashier_id'=>2,
                        'price'=>$details->order_subtotal ?? 0,
                        'total_tax'=> 0,
                        'other_taxes'=>$total_pack_taxes ?? 0,
                        'discount'=>$details->discount ?? 0,
                        'discount_type'=>2,
                        'discount_type_value'=>$details->discount ?? 0,
                        'amount'=>round($total_amount ?? 0),
                        'round'=>round($round_amount,2),
                        'delivery_charge'=>$deliveryother ?? 0,
                        'packing_charge' => $total_pack_charge ?? 0,
                        'name' => $response->customer->name ?? '',
                        'phone' => $response->customer->phone ?? '',
                        'address' => $response->customer->address->city ?? '',
                        'createtime'=>$currenttime,
                        'token'=>$token,
                        'payment_mode'=>$response->order->details->channel,
                        'status'           => 2,
                        'created'    => $created
                    ];
                
                if (!empty($data['Order_transaction'])) {
                    $this->Order_transaction->saveAll($data['Order_transaction']);
                }
                
                // Save payments
                if (!empty($payments)) {
                    foreach ($payments as $payment) {
                        $data['Order_payment'][] = [
                            'reference_id' => $txnid,
                            'cashier_id'   =>  2,
                            'type'         => '9', // Map your payment type
                            'type_value'   => $details->channel ?? '',
                            'amount'       => round($total_amount) ?? 0,
                            'remarks'      => $payment->option ?? '' ,
                            'status'       => 2,
                            'created'      => $created
                        ];
                    }
                }
                
                if (!empty($data['Order_payment'])) {
                    $this->Order_payment->saveAll($data['Order_payment']);
                }
                $this->Online_order->updateAll(
                    ['Online_order.cron_run' => 1],
                    ['Online_order.id' => $webhook['Online_order']['id']]
                );
                
                $response1 =array(
                    'status'=> true,
                    'message'=> "Synced order $txnid successfully.",
                );
            }
        }
        return json_encode($response1);
    }

    public function food_cost_product_update()
    {
        $this->autoRender = false;
        $this->layout = false;
        
        $currentTime = date('Y-m-d H:i:s');
        
        // $costprices = $this->Inventory_linking->query("SELECT il.parent_id AS product_id, ROUND(SUM( CASE WHEN il.component_type = 'SKU' THEN il.quantity * COALESCE(s.price, 0) WHEN il.component_type = 'Semifinished' THEN il.quantity * COALESCE(sf.food_cost, 0) ELSE 0 END ), 2) AS total_component_cost FROM inventory_linking il LEFT JOIN inventory_skus s ON il.component_type = 'SKU' AND il.component_id = s.id LEFT JOIN inventory_semifinished sf ON il.component_type = 'Semifinished' AND il.component_id = sf.id WHERE il.parent_type = 'Product' GROUP BY il.parent_id ORDER BY il.parent_id; ");
        $costprices = $this->Inventory_linking->query("
            SELECT 
                il.parent_id AS product_id,
            
                ROUND(
                    SUM(
                        CASE 
                            WHEN il.component_type = 'SKU' 
                                THEN il.quantity * COALESCE(s.price, 0)
            
                            WHEN il.component_type = 'Semifinished' 
                                THEN il.quantity * COALESCE(sf.food_cost, 0)
            
                            ELSE 0
                        END
                    ),
                2) AS total_component_cost
            
            FROM inventory_linking il
            
            LEFT JOIN inventory_skus s 
                ON il.component_type = 'SKU' 
               AND il.component_id = s.id
               AND s.status = 1
            
            LEFT JOIN inventory_semifinished sf 
                ON il.component_type = 'Semifinished' 
               AND il.component_id = sf.id
               AND sf.status = 1
            
            WHERE il.parent_type = 'Product'
              AND il.status = 1
            
            GROUP BY il.parent_id
            ORDER BY il.parent_id
        ");
        
        foreach($costprices as $costprice){
            $product_id= $costprice['il']['product_id'];
            $total_sku_cost= $costprice[0]['total_component_cost'];
            
            $this->Catalog_product->id = $product_id;
            
            if ($this->Catalog_product->exists()) {
                $saved  = $this->Catalog_product->save([
                    'id' => $product_id,
                    'food_cost' => $total_sku_cost,
                    'modified' => $currentTime
                ]);
                
                $response[] = [
                    'product_id' => $product_id,
                    'food_cost' => $total_sku_cost,
                    'status' => $saved ? 'updated' : 'failed'
                ];
            } else {
                
            }

            
        }
        
        echo json_encode([
            'status' => true,
            'message' => 'Food cost updated for products',
            'data' => $response
        ]);
        exit;
    }
    
    public function food_cost_semifinished_update()
    {
        
        $this->autoRender = false;
        $this->layout = false;
        
        $currentTime = date('Y-m-d H:i:s');
        
        // $costprices = $this->Inventory_linking->query("WITH RECURSIVE bom AS ( SELECT l.parent_id AS semifinished_id, l.component_type, l.component_id, l.component_name, CAST(l.quantity AS DECIMAL(18,6)) AS qty FROM inventory_linking l WHERE l.parent_type = 'Semifinished' UNION ALL SELECT bom.semifinished_id, l.component_type, l.component_id, l.component_name, bom.qty * CAST(l.quantity AS DECIMAL(18,6)) AS qty FROM bom JOIN inventory_linking l ON l.parent_type = 'Semifinished' AND l.parent_id = bom.component_id ) SELECT bom.semifinished_id, ROUND(SUM(bom.qty * COALESCE(s.price, 0)) / NULLIF(sf.yield, 0), 2) AS cost_per_unit FROM bom JOIN inventory_skus s ON bom.component_type = 'SKU' AND bom.component_id = s.id JOIN inventory_semifinished sf ON bom.semifinished_id = sf.id GROUP BY bom.semifinished_id, sf.yield; ");
        $costprices = $this->Inventory_linking->query("
            WITH RECURSIVE bom AS (
            
                -- Base Level
                SELECT 
                    l.parent_id AS semifinished_id,
                    l.component_type,
                    l.component_id,
                    l.component_name,
                    CAST(l.quantity AS DECIMAL(18,6)) AS qty
                FROM inventory_linking l
                WHERE l.parent_type = 'Semifinished'
                  AND l.status = 1
            
                UNION ALL
            
                -- Recursive Level
                SELECT 
                    bom.semifinished_id,
                    l.component_type,
                    l.component_id,
                    l.component_name,
                    bom.qty * CAST(l.quantity AS DECIMAL(18,6)) AS qty
                FROM bom
                JOIN inventory_linking l 
                    ON l.parent_type = 'Semifinished'
                   AND l.parent_id = bom.component_id
                   AND l.status = 1
            )
            
            SELECT 
                bom.semifinished_id,
                ROUND(
                    SUM(bom.qty * COALESCE(s.price, 0)) 
                    / NULLIF(sf.yield, 0),
                    2
                ) AS cost_per_unit
            
            FROM bom
            JOIN inventory_skus s 
                ON bom.component_type = 'SKU' 
               AND bom.component_id = s.id
            
            JOIN inventory_semifinished sf 
                ON bom.semifinished_id = sf.id
            
            GROUP BY bom.semifinished_id, sf.yield
        ");
        foreach($costprices as $costprice){
            $product_id= $costprice['bom']['semifinished_id'];
            $total_sku_cost= $costprice[0]['cost_per_unit'];
            
            $this->Inventory_semifinished->id = $product_id;
            // print_r($costprice);exit;
        
            if ($this->Inventory_semifinished->exists()) {
                
                $saved  = $this->Inventory_semifinished->saveAll([
                    'id' => $product_id,
                    'food_cost' => $total_sku_cost,
                    'modified' => $currentTime
                ]);
                
                $response[] = [
                    'semifinished_id' => $product_id,
                    'food_cost' => $total_sku_cost,
                    'status' => $saved ? 'updated' : 'failed'
                ];
            } else {
                
            }

            
        }
        
        echo json_encode([
            'status' => true,
            'message' => 'Food cost updated for semifinished',
            'data' => $response
        ]);
        exit;
    
    }
   
    public function fire_order()
    {
        $this->autoRender = false;
        $this->layout = false;
        $currentTime = date('Y-m-d H:i:s');

        $fire_items = $this->Order_orderhistory->find('all', [
            'conditions' => ['Order_orderhistory.kds_status' => 0,'Order_orderhistory.fire_at <= ' => $currentTime],
            'order' => ['Order_orderhistory.id' => 'ASC']
        ]);
        
        foreach ($fire_items as $fire_item) {
            
            $txnid = $fire_item['Order_orderhistory']['id'];
            $reference_id = $fire_item['Order_orderhistory']['reference_id'];
            $cashier_id = $fire_item['Order_orderhistory']['cashier_id'];
            
            $store_id='';
            $stores = $this->User->find('first', [
                'conditions' => [
                    'User.id' => $cashier_id,
                    'User.role' => 'cashier',
                    'User.status' => 1
                ],
                'recursive' => 1
            ]);
            $store_id=$stores['User']['location_id'];
            $store_name=$stores['User']['location_name'];
        
            $data['Kds_track'][] = [
                'order_history_id'   => $txnid,
                'order_reference_id' => $reference_id,
                'kds_group_id'       => $fire_item['Order_orderhistory']['group_id'],
                'kds_user_id'        => 0,
                'kds_station_id'     => $fire_item['Order_orderhistory']['kds'],
                'kds_username'       => 'auto-fire',
                'product_id'         => $fire_item['Order_orderhistory']['product_id'],
                'product_name'       => $fire_item['Order_orderhistory']['product_name'],
                'role'               => 'auto-fire',
                'table_no'           => $fire_item['Order_orderhistory']['table_number'],
                'cashier_id'         => $fire_item['Order_orderhistory']['cashier_id'],
                'location_id'        => $store_id,
                'location_name'      => $store_name,
                'status'             => 1
            ];
            
            
            $this->Kds_track->create();
            $this->Kds_track->saveAll($data['Kds_track']);
            $track_id = $this->Kds_track->id; // last inserted id
        
            $this->Order_orderhistory->updateAll(
                [
                    'Order_orderhistory.kds_status'   => 1,
                    'Order_orderhistory.kds_track_id'   => $track_id,
                ],
                ['Order_orderhistory.id' => $txnid]
            );
            $response='done';
        }
        echo json_encode($response);
        
    }
   
    // =========================================================
    // PUSH DATA v2 — Bill-level grouping with error quarantine
    // Runs on LOCAL, pushes to cloud
    // =========================================================
    public function push_data()
    {
        $this->autoRender = false;
        
        // Limit execution time to 2 minutes to prevent hanging cron processes
        set_time_limit(120);

        // Pre-flight internet check (4-second socket probe to tolerate slow connections)
        if (!$this->has_internet_connection(4)) {
            echo json_encode([
                'success' => false,
                'message' => 'No active internet connection or cloud unreachable. Skipping push_data.'
            ]);
            return;
        }
        
        $this->loadModel('Sync_error');

        $billTables    = bill_tables;
        $nonBillTables = non_bill_tables;
        $billLimit     = sync_bill_limit;
        $recordLimit   = sync_record_limit;
        $maxAttempts   = sync_max_quarantine_attempts;
        $syncLink      = sync_link;

        $summary = [
            'bills_synced' => 0,
            'bills_failed' => 0,
            'non_bill_synced' => 0,
            'errors' => [],
            'started_at' => date('Y-m-d H:i:s')
        ];

        // Capture read time BEFORE fetching data.
        // Used to guard against race conditions where ApisController
        // modifies a record between our read and sync=1 mark.
        $readTime = date('Y-m-d H:i:s');
        // $microTime = microtime(true);
        // $readTime = date('Y-m-d H:i:s', (int)$microTime)     // local time — matches MySQL modified columnFIX-1
        //           . '.' . sprintf('%06d', ($microTime - (int)$microTime) * 1000000);
            
                  
        // ----- PHASE 1: Bill-level sync -----

        $unionParts = [];
        foreach ($billTables as $table => $model) {
            $unionParts[] = "SELECT DISTINCT reference_id FROM `$table` WHERE sync = 0 AND reference_id IS NOT NULL AND reference_id != ''";
        }

        if (!empty($unionParts)) {
            $unionSql = implode(' UNION ', $unionParts) . " LIMIT $billLimit";

            $firstModel = reset($billTables);
            $this->loadModel($firstModel);
            $refRows = $this->$firstModel->query($unionSql);

            $refIds = [];
            foreach ($refRows as $row) {
                $rid = $row[0]['reference_id'] ?? ($row['reference_id'] ?? null);
                if (!empty($rid)) {
                    $refIds[] = $rid;
                }
            }

            // Exclude quarantined reference_ids (too many failed attempts)
            if (!empty($refIds)) {
                $quarantined = $this->Sync_error->find('list', [
                    'fields' => ['reference_id', 'reference_id'],
                    'conditions' => [
                        'reference_id' => $refIds,
                        'direction' => 'push',
                        'resolved' => 0,
                        'attempts >=' => $maxAttempts
                    ]
                ]);
                $refIds = array_diff($refIds, $quarantined);
            }

            if (!empty($refIds)) {
                // Gather all unsynced data grouped by reference_id
                $billPayload = [];
                foreach ($refIds as $refId) {
                    $billData = [];
                    foreach ($billTables as $table => $model) {
                        $this->loadModel($model);
                        $rows = $this->$model->find('all', [
                            'conditions' => [
                                'reference_id' => $refId,
                                'sync' => 0
                            ]
                        ]);
                        if (!empty($rows)) {
                            $tableConfig = push_table_config;
                            foreach ($rows as $r) {
                                $rec = $r[$model];
                                $siteKey = $tableConfig[$table]['site_key'] ?? 'store_id';
                                if (!empty($siteKey)) {
                                    $rec[$siteKey] = !empty($rec[$siteKey]) ? (int)$rec[$siteKey] : $this->getSyncStoreIdVal();
                                }
                                $billData[$table][] = $rec;
                            }
                        }
                    }
                    if (!empty($billData)) {
                        $billPayload[$refId] = $billData;
                    }
                }

                if (!empty($billPayload)) {
                    $url = $syncLink . "/sync_data_to_live";
                    $ch = curl_init($url);
                    curl_setopt_array($ch, [
                        CURLOPT_RETURNTRANSFER => true,
                        CURLOPT_POST           => true,
                        CURLOPT_HTTPHEADER     => ['Content-Type: application/x-www-form-urlencoded'],
                        CURLOPT_POSTFIELDS     => http_build_query([
                            'sync_data' => json_encode($billPayload),
                            'mode' => 'bill'
                        ]),
                        CURLOPT_TIMEOUT => 120
                    ]);
                    $response = curl_exec($ch);
                    $curlErr  = curl_error($ch);
                    curl_close($ch);

                    if (!empty($curlErr)) {
                        $summary['errors'][] = "cURL error: $curlErr";
                        CakeLog::write('error', "[PUSH] cURL error: $curlErr");
                    } else {
                        $result = json_decode($response, true);

                        if (!empty($result['bills'])) {
                            foreach ($result['bills'] as $refId => $billResult) {

                                $billRefId = $refId;  // capture to distinct var before inner loopsFIX-8

                                if (!empty($billResult['success'])) {
                                    // Mark records as synced — ONLY if not modified since read.
                                    // The modified guard prevents the race condition where
                                    // ApisController updates a record between our read & this mark.
                                    foreach ($billTables as $table => $tblModel) {
                                        if (!empty($billPayload[$billRefId][$table])) {

                                            $this->loadModel($tblModel);

                                            foreach ($billPayload[$billRefId][$table] as $sentRecord) {
                                                $recordId = (int)$sentRecord['id'];
                                                
                                                // Only mark sync=1 if:
                                                // 1. Not modified since we read it (modified guard), AND
                                                // 2. The status we sent is still the current status
                                                //    (guards against payment arriving after our read)
                                                $statusGuard = '';
                                                if (isset($sentRecord['status'])) {
                                                    $sentStatus = intval($sentRecord['status']);
                                                    $statusGuard = "AND `status` = " . $sentStatus . " ";
                                                }
                                                
                                                $this->$tblModel->query(
                                                    "UPDATE `$table` SET sync = 1 "
                                                  . "WHERE id = $recordId "
                                                  . "AND modified <= '$readTime' "
                                                  . $statusGuard
                                                );
                                            }
                                            
                                            // $ids = array_column($billPayload[$refId][$table], 'id');
                                            // if ($ids) {
                                            //     $this->loadModel($tblModel);
                                            //     $idList = implode(',', array_map('intval', $ids));
                                            //     $this->$tblModel->query(
                                            //         "UPDATE `$table` SET sync = 1 "
                                            //       . "WHERE id IN ($idList) "
                                            //       . "AND modified <= '$readTime'"
                                            //     );
                                            // }
                                        }
                                    }
                                    $summary['bills_synced']++;

                                    // Clear previous sync_errors for this bill
                                    $this->Sync_error->updateAll(
                                        ['Sync_error.resolved' => 1],
                                        [
                                            'Sync_error.reference_id' => $billRefId,
                                            'Sync_error.direction' => 'push',
                                            'Sync_error.resolved' => 0
                                        ]
                                    );
                                } else {
                                    // Bill failed — quarantine it
                                    $summary['bills_failed']++;
                                    $errMsg = $billResult['error'] ?? 'Unknown cloud error';
                                    $failedTable = $billResult['failed_table'] ?? '';
                                    $failedId    = $billResult['failed_record_id'] ?? null;
                                    $summary['errors'][] = "Bill $billRefId: $errMsg";

                                    $this->_logSyncError($billRefId, $failedTable, $failedId, $errMsg, $billPayload[$billRefId] ?? null, 'push');
                                }
                            }
                        } else {
                            $summary['errors'][] = 'Invalid cloud response';
                            CakeLog::write('error', '[PUSH] Invalid cloud response: ' . substr($response, 0, 500));
                        }
                    }
                }
            }
        }

        // ----- PHASE 2: Non-bill tables (per-record) -----
        
        foreach ($nonBillTables as $table => $model) {
            $this->loadModel($model);
            
            $readTimePhase2 = date('Y-m-d H:i:s');
          
            $quarantinedNonBill = $this->Sync_error->find('list', [ // <<< FIX #5 START
                'fields'     => ['record_id', 'record_id'],
                'conditions' => [
                    'table_name'  => $table,
                    'direction'   => 'push',
                    'resolved'    => 0,
                    'attempts >=' => $maxAttempts
                ]
            ]); // <<< FIX #5 END
 
            $findConditions = ['sync' => 0];
            if (!empty($quarantinedNonBill)) { // <<< FIX #5
                $findConditions['NOT'] = ['id' => array_values($quarantinedNonBill)]; // <<< FIX #5
            } // <<< FIX #5
            
            // $microTime2     = microtime(true);
            // $readTimePhase2 = date('Y-m-d H:i:s', (int)$microTime2)   // local timeFIX-4
            //                 . '.' . sprintf('%06d', ($microTime2 - (int)$microTime2) * 1000000);
            $rows = $this->$model->find('all',[ 
                'conditions' => $findConditions,   
                'order' => ['id' => 'ASC'],
                'limit' => $recordLimit
            ]);

            if (empty($rows)) continue;
            
            // ✅ use per-table config instead of hardcoded store_id
            $tableConfig = push_table_config;
            
            $records = [];
            foreach ($rows as $r) {
                $rec = $r[$model];
                
                $siteKey = $tableConfig[$table]['site_key'] ?? 'store_id';
                
                // ✅ only inject if the table actually uses a site key
                if (!empty($siteKey)) {
                    $rec[$siteKey] = !empty($rec[$siteKey]) ? (int)$rec[$siteKey] : $this->getSyncStoreIdVal();}
                $records[] = $rec;
            }
            
            // ── STOCK LEDGER: strip balance fields before pushing ──
            // We only push the delta (quantity + metadata).
            // The live server will recompute balance_stock from its own current stock.
            // We must NOT send balance_stock / actual_balance / variance from local
            // because local never received live purchase/transfer entries.
            if ($table === 'stock_ledger') {
                foreach ($records as &$rec) {
                    unset($rec['balance_stock']);   // live will recalculate this
                    unset($rec['actual_balance']);  // live will recalculate this
                    unset($rec['variance']);        // live will recalculate this
                }
                unset($rec);
            }

            $url = $syncLink . "/sync_data_to_live";
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST           => true,
                CURLOPT_HTTPHEADER     => ['Content-Type: application/x-www-form-urlencoded'],
                CURLOPT_POSTFIELDS     => http_build_query([
                    'sync_data' => json_encode([$table => $records]),
                    'mode' => 'table'
                ]),
                CURLOPT_TIMEOUT => 60
            ]);
            $resp = curl_exec($ch);
            // curl_close($ch);
            // FIX #3 — Check for cURL errors in Phase 2.
            // BEFORE: curl_error() was never checked. If the request failed,
            //         $resp was false, json_decode(false) returned null, and
            //         the failure was silently swallowed — records never marked
            //         synced, no error logged, no retry triggered.
            // AFTER:  We check curl_error() and log it before trying to decode.
            $curlErrPhase2 = curl_error($ch); // <<< FIX #3
            curl_close($ch);
            
            if (!empty($curlErrPhase2)) { // <<< FIX #3 START
                $errMsg = "[$table] cURL error: $curlErrPhase2";
                $summary['errors'][] = $errMsg;
                CakeLog::write('error', "[PUSH NON-BILL] $errMsg");
                continue; // skip to next table, records remain sync=0 for next run
            } // <<< FIX #3 END
 
            $res = json_decode($resp, true);
            if (!empty($res['records'][$table])) {
                $successIds = $res['records'][$table]['success'] ?? [];
                $failedIds  = $res['records'][$table]['failed'] ?? [];

                if (!empty($successIds)) {
                    $idList = implode(',', array_map('intval', $successIds));
                    $this->$model->query(
                        "UPDATE `$table` SET sync = 1 "
                      . "WHERE id IN ($idList) AND modified <= '$readTimePhase2'"
                    );
                    $summary['non_bill_synced'] += count($successIds);
                }

                foreach ($failedIds as $fail) {
                    // $this->_logSyncError(null, $table, $fail['id'] ?? null, $fail['error'] ?? 'Unknown', null, 'push');
                    $failErrMsg = "[$table][id=" . ($fail['id'] ?? '?') . "] " . ($fail['error'] ?? 'Unknown'); // <<< FIX #8
                    $summary['errors'][] = $failErrMsg; // <<< FIX #8
                    CakeLog::write('error', "[PUSH NON-BILL] $failErrMsg"); // <<< FIX #8
 
                    $this->_logSyncError(null, $table, $fail['id'] ?? null, $fail['error'] ?? 'Unknown', null, 'push');
                }
            } else {
                // <<<< ADD THIS
                $errMsg = "[$table] Unexpected response: " . substr($resp, 0, 200);
                $summary['errors'][] = $errMsg;
                CakeLog::write('error', "[PUSH NON-BILL] $errMsg");
                // Note: records remain sync=0 and will retry next run.
                // We do NOT log a sync_error here because we don't know
                // which specific record IDs failed — it was a transport/parse issue.
            }
        }

        // ----- PHASE 3: Record last sync time -----
        $summary['completed_at'] = date('Y-m-d H:i:s');
        $this->_updateLastSyncTime('push', $summary);

        // ----- PHASE 4: Check for persistent alert conditions -----
        $this->_checkSyncAlerts();

        echo json_encode(['success' => true, 'summary' => $summary]);
    }
    
    // =========================================================
    // SYNC DATA TO LIVE v2 — Per-bill transactions, per-record UPSERT
    // Runs on CLOUD, receives data from local push_data()
    // =========================================================
    public function sync_data_to_live()
    {
        $this->autoRender = false;

        $syncData = json_decode($_POST['sync_data'] ?? '', true);
        $mode     = $_POST['mode'] ?? 'bill';

        if (empty($syncData)) {
            echo json_encode(['success' => true, 'message' => 'No data']);
            return;
        }

        $allTables = push_tables;

        $db = ConnectionManager::getDataSource('default');

        $response = ['success' => true];

        if ($mode === 'bill') {
            // ----- BILL MODE: data grouped by reference_id -----
            $response['bills'] = [];

            foreach ($syncData as $refId => $billData) {
                if (empty($billData) || !is_array($billData)) continue;

                $db->begin();
                $billSuccess = true;
                $failedTable = '';
                $failedRecordId = null;
                $errorMsg = '';
                
                // FIX #6 — Track whether at least one row was actually written.
                // BEFORE: If every table in $billData was missing from $allTables,
                //         the transaction committed with zero rows written and
                //         returned success:true — a silent no-op success.
                // AFTER:  We count rows written. If none were written we rollback
                //         and return a clear error instead of a false positive.
                $rowsWritten = 0; // <<< FIX #6

                try {
                    foreach ($billData as $table => $rows) {
                        if (empty($rows) || !is_array($rows) || empty($allTables[$table])) continue;

                        foreach ($rows as $row) {
                            if (!is_array($row)) continue;
                            
                            // ✅ ADD THIS BLOCK
                            foreach ($row as $k => $v) {
                    
                                if (is_array($v)) {
                            
                                    // if numeric array like [1]
                                    if (count($v) === 1 && is_numeric($v[0])) {
                                        $row[$k] = (int)$v[0];  // ✅ convert to int
                                    } else {
                                        $row[$k] = json_encode($v); // ✅ keep as JSON
                                    }
                                }
                            }
                            // ================================
                            // 🔥 FIX 1: ADD LOCAL ID
                            // ================================
                            $row['local_id'] = $row['id'];
                            unset($row['id']); // let LIVE auto increment
                            
                            $row['sync'] = 1;
                            
                            $columns = array_keys($row);
                            $escaped = array_map([$db, 'value'], array_values($row));

                            // ================================
                            // 🔥 FIX 2: DO NOT UPDATE local_id/store_id
                            // ================================
                            // NEW — uses per-table site_key
                            $tableConfig = push_table_config;
                            $siteKey = $tableConfig[$table]['site_key'] ?? 'store_id'; // fallback to store_id
                            
                            $updates = [];
                            foreach ($columns as $col) {
                                if (in_array($col, ['id', 'local_id', $siteKey])) continue;
                                $updates[] = "`$col` = VALUES(`$col`)";
                            }
    
                            $sql = "INSERT INTO `$table` (`" . implode('`,`', $columns) . "`)
                                    VALUES (" . implode(',', $escaped) . ")
                                    ON DUPLICATE KEY UPDATE " . implode(',', $updates);
                                    
                            $db->query($sql);
                            $rowsWritten++; // <<< FIX #6
                        }
                    }
                    
                    // FIX #6 continued — Rollback and report if nothing was written.
                    if ($rowsWritten === 0) { // <<< FIX #6 START
                        $db->rollback();
                        $response['success']       = false;
                        $response['bills'][$refId] = [
                            'success' => false,
                            'error'   => 'No valid tables found in payload; nothing written.'
                        ];
                        CakeLog::write('error', "[SYNC_BILL][$refId] No rows written — all tables missing from allTables.");
                        continue;
                    } // <<< FIX #6 END
 
                    $db->commit();

                    $response['bills'][$refId] = ['success' => true];

                } catch (Exception $e) {
                    $db->rollback();
                    $billSuccess = false;
                    $errorMsg = $e->getMessage();

                    $failInfo = $this->_identifyFailedRecord($db, $billData, $allTables);
                    $failedTable    = $failInfo['table'] ?? '';
                    $failedRecordId = $failInfo['record_id'] ?? null;
                    if (!empty($failInfo['error'])) {
                        $errorMsg = $failInfo['error'];
                    }

                    CakeLog::write('error', "[SYNC_BILL][$refId][$failedTable] $errorMsg");

                    $response['success'] = false;
                    $response['bills'][$refId] = [
                        'success'          => false,
                        'error'            => $errorMsg,
                        'failed_table'     => $failedTable,
                        'failed_record_id' => $failedRecordId
                    ];
                }
            }

        } elseif ($mode === 'table') {
            // ----- TABLE MODE: non-bill tables, per-record processing -----
            $response['records'] = [];
            
            CakeLog::write('debug', '[SYNC_DATA_KEYS] ' . json_encode(array_keys($syncData)));
            foreach ($syncData as $table => $rows) {
                if (empty($rows) || !is_array($rows) || empty($allTables[$table])) continue;

                $successIds = [];
                $failedIds  = [];

                foreach ($rows as $row) {
                    if (!is_array($row)) continue;
                    
                    // ✅ ADD THIS BLOCK
                    foreach ($row as $k => $v) {
                    
                        if (is_array($v)) {
                    
                            // if numeric array like [1]
                            if (count($v) === 1 && is_numeric($v[0])) {
                                $row[$k] = (int)$v[0];  // ✅ convert to int
                            } else {
                                $row[$k] = json_encode($v); // ✅ keep as JSON
                            }
                        }
                    }
                    // ================================
                    // 🔥 FIX 3: STORE ORIGINAL ID
                    // ================================
                    $localId = $row['id'] ?? null;

                    $row['local_id'] = $localId;
                    unset($row['id']);

                    $row['sync'] = 1;
                    
                    $recordId = $localId;
                    
                    // ══════════════════════════════════════════════════════════
                    // STOCK LEDGER SPECIAL HANDLING
                    // 
                    // Problem: Local only records sale deltas (e.g. -2 apples).
                    //          Local never receives live purchases (+5 apples).
                    //          So local balance_stock is WRONG for live context.
                    //
                    // Solution:
                    //   1. Find the latest ledger entry on LIVE for this sku+store
                    //   2. Take its balance_stock as the current live balance
                    //   3. Apply the incoming quantity delta on top of it
                    //   4. Save the new row with the correctly recalculated balance
                    // ══════════════════════════════════════════════════════════
                    if ($table === 'stock_ledger') {
                        try {
                            $row['sku_id']   = is_array($row['sku_id'])   ? (int)$row['sku_id'][0]   : (int)$row['sku_id'];
                            $row['store_id'] = is_array($row['store_id']) ? (int)$row['store_id'][0] : (int)$row['store_id'];
                            $row['item_type'] = $row['item_type'] ?? 'sku';
                            
                            $skuId       = (int)$row['sku_id'];
                            $storeId     = (int)$row['store_id'];
                            $itemTypeRaw = $row['item_type'];
                            $typeRaw     = $row['type'] ?? 'OUTLET';
                            $itemType    = $db->value($itemTypeRaw);
                            $delta       = (float)($row['quantity'] ?? 0);       // sale quantity (positive number)
                            $saleCreated = $row['created'] ?? date('Y-m-d H:i:s'); // timestamp of this sale (may be backdated)
                            
                            if ($skuId <= 0 || $storeId <= 0) {
                                CakeLog::write('error', "[SYNC_STOCK_LEDGER] Invalid sku/store");
                                continue;
                            }
                            
                            $db->begin();
                        
                            // ── STEP 1: Find the last actual_balance BEFORE this sale ──
                            // ✅ FIX: Use local_id as tiebreaker when created timestamps are equal
                            // BEFORE: AND created < $saleCreated  → all same-second entries see same opening balance
                            // AFTER:  AND (created < $saleCreated OR (created = $saleCreated AND local_id < $localId))
                            //         → correctly chains entries within the same second using insertion order

                            $localIdInt = (int)$row['local_id']; // ← ADD: capture local_id for tiebreaker

                             // ── STEP 1: Find the last actual_balance BEFORE this sale's created time ──
                            // This is the correct opening balance for this entry regardless of when it's being synced
                            $prevBalanceRow = $db->fetchRow(
                                "SELECT actual_balance FROM stock_ledger
                                  WHERE sku_id     = $skuId
                                    AND store_id   = $storeId
                                    AND item_type  = $itemType
                                    AND type       = " . $db->value($typeRaw) . "
                                    AND (
                                        created < " . $db->value($saleCreated) . "   -- ← CHANGED
                                        OR (
                                            created = " . $db->value($saleCreated) . "
                                            AND local_id < $localIdInt
                                        )
                                    )
                                  ORDER BY created DESC, local_id DESC 
                                  LIMIT 1 FOR UPDATE"
                            );
                            
                            $openingBalance = isset($prevBalanceRow['stock_ledger']['actual_balance'])
                                ? (float)$prevBalanceRow['stock_ledger']['actual_balance']
                                : 0.0;
                    
                            // ── STEP 2: Calculate this sale's new balance ─────────────────────────────
                            // quantity_type tells us debit/credit direction
                            $quantityType  = $row['quantity_type'] ?? 'debit';
                            $newBalance    = ($quantityType === 'credit')
                                ? $openingBalance + $delta
                                : $openingBalance - $delta;
                    
                            $row['balance_stock']  = $newBalance;
                            $row['actual_balance'] = $newBalance;
                            $row['variance']       = 0;                          // no variance on the sale itself
                            
                            $row['modified'] = date('Y-m-d H:i:s');

                            foreach ($row as $k => $v) {
                                if (is_array($v)) $row[$k] = json_encode($v);
                            }
                    
                            // ── STEP 3: INSERT this sale row ──────────────────────────────────────────
                            $columns = array_keys($row);
                            $escaped = array_map([$db, 'value'], array_values($row));
                    
                            $tableConfig = push_table_config;
                            $siteKey     = $tableConfig['stock_ledger']['site_key'] ?? 'store_id';

                            $updates = [];
        
                            foreach ($columns as $col) {
                                if (in_array($col, ['id', 'local_id', $siteKey])) continue;
                                $updates[] = "`$col` = VALUES(`$col`)";
                            }
                    
                            $sql = "INSERT INTO `stock_ledger` (`" . implode('`,`', $columns) . "`) "
                                 . "VALUES (" . implode(',', $escaped) . ")";
                    
                            CakeLog::write('debug', "[SYNC_STOCK_LEDGER_SQL] " . $sql);
                            $db->query($sql);
                    
                            CakeLog::write('info',
                                "[SYNC_STOCK_LEDGER] sku=$skuId store=$storeId "
                              . "opening=$openingBalance delta=$delta new_balance=$newBalance created=$saleCreated"
                            );
                            
                            // ── STEP 4: Check if any entries exist AFTER this sale's created time ─────
                            // These need to be back-recalculated because our insert shifted the balance chain
                            $futureEntries = $db->fetchAll(
                                "SELECT * FROM stock_ledger
                                  WHERE store_id   = $storeId
                                    AND sku_id     = $skuId
                                    AND item_type  = $itemType
                                    AND type       = " . $db->value($typeRaw) . "
                                    AND (
                                        created > " . $db->value($saleCreated) . "   -- ← CHANGED
                                        OR (
                                            created = " . $db->value($saleCreated) . "
                                            AND local_id > $localIdInt
                                        )
                                    )
                                  ORDER BY created ASC, local_id ASC"
                            );
                    
                            if (!empty($futureEntries)) {
                                // ── STEP 5: Back-recalculate all future entries ───────────────────────
                                // Seed from the balance we just inserted
                                $runningBalance = $newBalance;
                    
                                foreach ($futureEntries as $entry) {
                                    $ledger          = $entry['stock_ledger'];
                                    $entryQty        = (float)$ledger['quantity'];
                                    $entryQtyType    = $ledger['quantity_type'];
                                    $oldActual       = (float)$ledger['actual_balance'];
                    
                                    // Rebuild what this row's balance should be
                                    $recalcBalance = ($entryQtyType === 'credit')
                                        ? $runningBalance + $entryQty
                                        : $runningBalance - $entryQty;
                    
                                    // variance = how much we shifted this entry (0 if it was already correct)
                                    $variance = $oldActual - $entryQty;
                    
                                    $entryId = (int)$ledger['id'];
                    
                                    $db->query(
                                        "UPDATE stock_ledger SET
                                            actual_balance = " . $db->value($recalcBalance) . ",
                                            balance_stock  = " . $db->value($recalcBalance) . ",
                                            variance       = " . $db->value($variance) . ",
                                            variance_type  = 'back_sale',
                                            sync           = 0,
                                            local_sync     = 0,
                                            modified       = NOW()
                                         WHERE id = $entryId"
                                    );
                    
                                    CakeLog::write('info',
                                        "[BACK_LEDGER] id=$entryId qty=$entryQty type=$entryQtyType "
                                      . "old=$oldActual recalc=$recalcBalance variance=$variance"
                                    );
                    
                                    $runningBalance = $recalcBalance; // move anchor forward
                                }
                    
                            } else {
                                // No future entries — this sale is the latest, no back-recalc needed
                                CakeLog::write('info',
                                    "[SYNC_STOCK_LEDGER] No future entries after created=$saleCreated "
                                  . "for sku=$skuId store=$storeId — no back-recalc needed"
                                );
                            }

                            $db->commit();
                            $successIds[] = $recordId;
                    
                        } catch (Exception $e) {
                            // Duplicate local_id = already synced on a previous attempt → treat as success
                            try { $db->rollback(); } catch (Exception $ignored) {}
                            
                            if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                                CakeLog::write('info',
                                    "[SYNC_STOCK_LEDGER] Duplicate local_id=$recordId — already synced, skipping"
                                );
                                $successIds[] = $recordId;
                            } else {
                                CakeLog::write('error',
                                    "[SYNC_STOCK_LEDGER][sku={$row['sku_id']}] " . $e->getMessage()
                                );
                                $failedIds[] = [
                                    'id'    => $recordId,
                                    'error' => 'Stock ledger sync failed: ' . $e->getMessage()
                                ];
                            }
                        }
                        continue;
                        
                    }else{
                        $columns = array_keys($row);
                        $escaped = array_map([$db, 'value'], array_values($row));
                        
                        // NEW — uses per-table site_key
                        $tableConfig = push_table_config;
                        $siteKey = $tableConfig[$table]['site_key'] ?? 'store_id'; // fallback to store_id
                        
                        $updates = [];
                        foreach ($columns as $col) {
                            if (in_array($col, ['id', 'local_id', $siteKey])) continue;
                            $updates[] = "`$col` = VALUES(`$col`)";
                        }
                        
                        $sql = "INSERT INTO `$table` (`" . implode('`,`', $columns) . "`) "
                             . "VALUES (" . implode(',', $escaped) . ") "
                             . "ON DUPLICATE KEY UPDATE " . implode(',', $updates);
    
                        try {
                            $db->query($sql);
                            $successIds[] = $recordId;
                        } catch (Exception $e) {
                            CakeLog::write('error', "[SYNC_REC][$table][id=$recordId] " . $e->getMessage());
                            $failedIds[] = ['id' => $recordId, 'error' => $e->getMessage()];
                        }
                    }
                }
                
                // ================================
                // 🔥 FIX 7: FORCE RESPONSE FOR STOCK LEDGER
                // ================================
                if ($table === 'stock_ledger' && empty($successIds) && empty($failedIds)) {
                    $failedIds[] = [
                        'id' => null,
                        'error' => 'No valid stock ledger rows processed'
                    ];
                }
                
                $response['records'][$table] = [
                    'success' => $successIds,
                    'failed'  => $failedIds
                ];
            }
            
            // ================================
            // 🔥 FIX 8: PREVENT EMPTY ARRAY RESPONSE
            // ================================
            // 🔥 FINAL FIX — FORCE stock_ledger RESPONSE
            if (!isset($response['records']['stock_ledger'])) {
                $response['records']['stock_ledger'] = [
                    'success' => [],
                    'failed'  => [
                        [
                            'id' => null,
                            'error' => 'No stock ledger data received'
                        ]
                    ]
                ];
            }
    
            CakeLog::write('debug', '[FINAL_RESPONSE] ' . json_encode($response));
            
        } else {
            // ----- LEGACY fallback -----
            $response['tables'] = [];
            $response['errors'] = [];

            foreach ($syncData as $table => $rows) {
                if (empty($rows) || !is_array($rows) || empty($allTables[$table])) continue;

                $columns = [];
                $values  = [];

                foreach ($rows as $row) {
                    if (!is_array($row)) continue;
                    $row['sync'] = 1;
                    if (empty($columns)) {
                        $columns = array_keys($row);
                    }
                    $escaped = array_map([$db, 'value'], array_values($row));
                    $values[] = '(' . implode(',', $escaped) . ')';
                }

                if (empty($values)) continue;

                $updates = [];
                foreach ($columns as $col) {
                    if ($col === 'id') continue;
                    $updates[] = "`$col` = VALUES(`$col`)";
                }

                $sql = "INSERT INTO `$table` (`" . implode('`,`', $columns) . "`) "
                     . "VALUES " . implode(',', $values) . " "
                     . "ON DUPLICATE KEY UPDATE " . implode(',', $updates);

                try {
                    $db->begin();
                    $db->query($sql);
                    $db->commit();
                    $response['tables'][$table] = true;
                } catch (Exception $e) {
                    $db->rollback();
                    $response['success'] = false;
                    $response['tables'][$table] = false;
                    $response['errors'][$table] = $e->getMessage();
                    CakeLog::write('error', "[SYNC_LEGACY][$table] " . $e->getMessage());
                }
            }
        }

        echo json_encode($response);
    }

    /**
     * After a bill-level transaction fails, pinpoint exactly which record caused it.
     */
    private function _identifyFailedRecord($db, $billData, $allTables)
    {
        foreach ($billData as $table => $rows) {
            if (empty($rows) || empty($allTables[$table])) continue;

            foreach ($rows as $row) {
                if (!is_array($row)) continue;
                $row['local_id'] = $row['id']; // mirror what sync_data_to_live does
                unset($row['id']);
                $row['sync'] = 1;
                
                $capturedLocalId = $row['local_id']; // ← ADD THIS
    
                $columns = array_keys($row);
                $escaped = array_map([$db, 'value'], array_values($row));

                $tableConfig = push_table_config;
                $siteKey = $tableConfig[$table]['site_key'] ?? 'store_id';
                
                $updates = [];
                foreach ($columns as $col) {
                    if (in_array($col, ['id', 'local_id', $siteKey])) continue; // ✅
                    $updates[] = "`$col` = VALUES(`$col`)";
                }

                $sql = "INSERT INTO `$table` (`" . implode('`,`', $columns) . "`) "
                     . "VALUES (" . implode(',', $escaped) . ") "
                     . "ON DUPLICATE KEY UPDATE " . implode(',', $updates);
                
                // FIX #2 — Use try/finally to guarantee the transaction is always
                // closed, even when $db->begin() or $db->query() succeeds (no exception).
                // BEFORE: $db->rollback() was only reached inside the catch block.
                //         If the query succeeded (no throw), begin() opened a transaction
                //         that was NEVER rolled back — leaving a dangling transaction on
                //         the connection for every record that passed the probe test.
                // AFTER:  finally{} always runs, ensuring rollback in both the success
                //         path (record is fine, move to next) and the failure path.
                try { // <<< FIX #2 START
                    $db->begin();
                    $db->query($sql);
                    // If we reach here the row is fine — intentionally roll back the probe
                    
                } catch (Exception $e) {
                    return [
                        'table'     => $table,
                        'record_id' => $capturedLocalId ?? null,
                        'error'     => $e->getMessage()
                    ];
                } finally {
                    // Always rollback — this is a probe, not a real write
                    // $db->rollback(); // <<< FIX #2 END
                    try { $db->rollback(); } catch (Exception $ignored) {} // safe
                }
            }
        }

        return ['table' => '', 'record_id' => null, 'error' => 'Could not isolate failing record'];
    }
    
    // =========================================================
    // PULL DATA v2 — Per-record UPSERT with error isolation
    // Runs on LOCAL, pulls data from cloud
    // =========================================================
    public function pull_data($cashierid = null)
    {
        $this->autoRender = false;
        
        // Limit execution time to 2 minutes to prevent hanging cron processes
        set_time_limit(120);

        // Pre-flight internet check (4-second socket probe to tolerate slow connections)
        if (!$this->has_internet_connection(4)) {
            echo json_encode([
                'success' => false,
                'message' => 'No active internet connection or cloud unreachable. Skipping pull_data.'
            ]);
            return;
        }
        
        $this->loadModel('Location');
        $this->loadModel('User');
        $this->loadModel('Sync_error');

        $maxAttempts = sync_max_quarantine_attempts;

        if (!empty($cashierid)) {
            $locationIds = $this->User->find('list', [
                'fields' => ['location_id', 'location_id'],
                'conditions' => [
                    'id' => (int)$cashierid,
                    'status' => 1
                ]
            ]);
        } else {
            $locationIds = $this->getSyncStoreIds();
        }

        $locationIds = array_values($locationIds);
        if (empty($locationIds)) {
            echo json_encode(['success' => false, 'message' => 'No locations found']);
            return;
        }

        $locationStr = implode(',', $locationIds);
        $liveUrl     = sync_link;
        $batchSize   = batchSize;

        $tables = pull_tables;

        $db = ConnectionManager::getDataSource('default');

        $summary = ['tables_processed' => 0, 'records_synced' => 0, 'records_failed' => 0];

        foreach ($tables as $table => $model) {

            $this->loadModel($model);
            $page = 1;

            while (true) {

                $url = $liveUrl . "/get_unsynced_data_batch/$locationStr"
                     . "?table=$table&page=$page&limit=$batchSize";

                $ch = curl_init($url);
                curl_setopt_array($ch, [
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_TIMEOUT => 30
                ]);
                $response = curl_exec($ch);
                $curlErr  = curl_error($ch);
                curl_close($ch);

                if (!empty($curlErr)) {
                    CakeLog::write('error', "[PULL][$table] cURL: $curlErr");
                    break;
                }

                $json = json_decode($response, true);

                if (empty($json['data'])) break;

                $records   = $json['data'];
                $successIds = [];
                $failedIds  = [];

                foreach ($records as $row) {
                    $row = $row[$model] ?? $row;
                    if (!is_array($row) || empty($row)) continue;

                    $recordId = $row['id'] ?? null;

                    $columns = array_keys($row);
                    
                    // Filter out columns that don't exist in the local database schema
                    $validCols = array_keys($this->{$model}->schema());
                    $columns = array_values(array_intersect($columns, $validCols));

                    $escaped = [];
                    foreach ($columns as $col) {
                        $escaped[] = $db->value($row[$col], 'string');
                    }

                    $updates = [];
                    foreach ($columns as $col) {
                        if (in_array($table, ['order_tables', 'order_table']) && $col === 'reference_id') {
                            continue;
                        }
                        if (in_array($table, ['order_tables', 'order_table']) && $col === 'status') {
                            continue;
                        }
                        if (!in_array($col, ['id', 'created'])) {
                            $updates[] = "`$col` = VALUES(`$col`)";
                        }
                    }

                    if ($table === 'locations') {
                        $setClauses = [];
                        foreach ($columns as $index => $col) {
                            if (!in_array($col, ['id', 'created'])) {
                                $setClauses[] = "`$col` = " . $escaped[$index];
                            }
                        }
                        $sql = "UPDATE `$table` SET " . implode(', ', $setClauses) . " WHERE id = " . (int)$recordId;
                    } else {
                        $sql = "INSERT INTO `$table` (`" . implode('`,`', $columns) . "`) "
                             . "VALUES (" . implode(',', $escaped) . ") "
                             . "ON DUPLICATE KEY UPDATE " . implode(',', $updates);
                    }

                    try {
                        $db->query($sql);
                        $successIds[] = (int)$recordId;
                        $summary['records_synced']++;
                    } catch (Exception $e) {
                        CakeLog::write('error', "[PULL][$table][id=$recordId] " . $e->getMessage());
                        $failedIds[] = (int)$recordId;
                        $summary['records_failed']++;

                        $this->_logSyncError(null, $table, $recordId, $e->getMessage(), $row, 'pull');
                    }
                }

                if (!empty($successIds)) {
                    $this->notify_live_item_sync($liveUrl, $table, $successIds, $locationIds);
                }

                if (count($records) < $batchSize) break;
                $page++;
            }

            $summary['tables_processed']++;
        }

        $this->notify_live_location_sync($liveUrl, $locationIds);

        $summary['completed_at'] = date('Y-m-d H:i:s');
        $this->_updateLastSyncTime('pull', $summary);

        echo json_encode(['success' => true, 'message' => 'Pull completed', 'summary' => $summary]);
    }
    
    private function notify_live_item_sync($liveUrl, $table, $ids, $locationIds)
    {
        $ch = curl_init($liveUrl . "/mark_data_synced_batch");
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query([
                'table' => $table,
                'ids'   => json_encode($ids),
                'location_ids' => json_encode($locationIds)
            ]),
            CURLOPT_TIMEOUT => 15
        ]);
        curl_exec($ch);
        curl_close($ch);
    }
    
    private function notify_live_location_sync($liveUrl, $locationIds)
    {
        $ch = curl_init($liveUrl . "/mark_location_synced_batch");
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query([
                'location_ids' => json_encode($locationIds)
            ]),
            CURLOPT_TIMEOUT => 15
        ]);
        curl_exec($ch);
        curl_close($ch);
    }

    public function get_unsynced_data_batch($location_ids)
    {
        $this->autoRender = false;
    
        $table = $_GET['table'] ?? '';
        $page  = (int)($_GET['page'] ?? 1);
        $limit = (int)($_GET['limit'] ?? 200);
    
        if (!$table) {
            echo json_encode(['success' => false]);
            return;
        }
    
        $locationIds = array_map('intval', explode(',', $location_ids));
    
        $this->loadModel('Location');
        $targetLocations = $this->Location->find('list', [
            'fields' => ['id', 'id'],
            'conditions' => [
                'id' => $locationIds,
                'is_publish' => 0
            ]
        ]);
    
        if (empty($targetLocations)) {
            echo json_encode(['success' => true, 'data' => []]);
            return;
        }
    
        $tables = pull_tables;
    
        if (!isset($tables[$table])) {
            echo json_encode(['success' => false]);
            return;
        }
    
        $model = $tables[$table];
        $this->loadModel($model);
    
        $conditions = ['local_sync' => 0];
    
        if ($this->$model->hasField('location_id')) {
            $conditions['location_id'] = array_values($targetLocations);
        } elseif ($this->$model->hasField('store_id')) {
            $conditions['store_id'] = array_values($targetLocations);
        }
    
        $records = $this->$model->find('all', [
            'conditions' => $conditions,
            'limit' => $limit,
            'offset' => ($page - 1) * $limit,
            'order' => ['id' => 'ASC']
        ]);
    
        echo json_encode(['success' => true, 'data' => $records]);
    }
    
    public function mark_data_synced_batch()
    {
        $this->autoRender = false;
    
        $table = $_POST['table'] ?? '';
        $ids   = json_decode($_POST['ids'] ?? '[]', true);
        $locationIds = json_decode($_POST['location_ids'] ?? '[]', true);
    
        if (!$table || empty($ids)) {
            echo json_encode(['success' => false]);
            return;
        }
    
        $tables = pull_tables;
    
        if (!isset($tables[$table])) {
            echo json_encode(['success' => false]);
            return;
        }
    
        $model = $tables[$table];
        $this->loadModel($model);
        $this->loadModel('Location');
    
        $pending = $this->Location->find('count', [
            'conditions' => [
                'status' => 1,
                'is_publish' => 0
            ]
        ]);

        if ($this->$model->hasField('location_id')) {
            $publishedLocations = $this->Location->find('count', [
                'conditions' => [
                    'id'         => $locationIds,
                    'is_publish' => 0,
                    'status'     => 1
                ]
            ]);
        
            if ($publishedLocations == 1) {
                $this->$model->updateAll(
                    ['local_sync' => 1],
                    [
                        'id' => $ids,
                        'location_id' => $locationIds
                    ]
                );
            }
        } elseif ($this->$model->hasField('store_id')) {
            $publishedLocations = $this->Location->find('count', [
                'conditions' => [
                    'id'         => $locationIds,
                    'is_publish' => 0,
                    'status'     => 1
                ]
            ]);
        
            if ($publishedLocations == 1) {
                $this->$model->updateAll(
                    ['local_sync' => 1],
                    [
                        'id' => $ids,
                        'store_id' => $locationIds
                    ]
                );
            }
        } else {
            if ($pending == 1) {
                $this->$model->updateAll(
                    ['local_sync' => 1],
                    ['id' => $ids]
                );
            }
        }
    
        echo json_encode(['success' => true]);
    }
    
    public function mark_location_synced_batch()
    {
        $this->autoRender = false;
    
        $locationIds = json_decode($_POST['location_ids'] ?? '[]', true);
        if (empty($locationIds)) {
            echo json_encode(['success' => false]);
            return;
        }
    
        $tables = pull_tables;
        $this->loadModel('Location');
    
        $publishedLocations = [];
        
        foreach ($locationIds as $locId) {
    
            $allSynced = true;
    
            foreach ($tables as $table => $model) {
                $this->loadModel($model);
                $remaining = 0; // ← add this before the if block

                if ($this->$model->hasField('location_id')) {
                    // Check ONLY this location
                    $remaining = $this->$model->find('count', [
                        'conditions' => [
                            'local_sync' => 0,
                            'location_id' => $locId
                        ]
                    ]);
                } elseif ($this->$model->hasField('store_id')) {
                    // Check ONLY this store
                    $remaining = $this->$model->find('count', [
                        'conditions' => [
                            'local_sync' => 0,
                            'store_id' => $locId
                        ]
                    ]);
                } else {
                    // Common table (no location_id / store_id) — ALL records must be synced
                    // Because common data (like tax) belongs to every location
                    $remaining = $this->$model->find('count', [
                        'conditions' => [
                            'local_sync' => 0
                        ]
                    ]);
                }

                if ($remaining > 0) {
                    $allSynced = false;
                    break;
                }
            }
    
            // ✅ Publish only this location
            if ($allSynced) {
                $this->Location->updateAll(
                    ['is_publish' => 1],
                    [
                        'id' => $locId,
                        'is_publish' => 0
                    ]
                );
    
                $publishedLocations[] = $locId;
            }
        }
    
        echo json_encode([
            'success' => true,
            'published_locations' => $publishedLocations
        ]);
    }
    // =========================================================
    // RETRY QUARANTINED — Re-attempt failed syncs periodically
    // =========================================================
    public function retry_quarantined()
    {
        $this->autoRender = false;
        $this->loadModel('Sync_error');

        $maxRetry   = 10;
        $billTables = bill_tables;
        $syncLink   = sync_link;

        $summary = ['retried' => 0, 'resolved' => 0, 'still_failing' => 0];

        // ----- Retry PUSH errors (bill-level) -----
        $failedBills = $this->Sync_error->find('all', [
            'conditions' => [
                'Sync_error.direction' => 'push',
                'Sync_error.resolved' => 0,
                'Sync_error.attempts <' => $maxRetry,
                'Sync_error.reference_id IS NOT NULL'
            ],
            'order' => ['Sync_error.attempts' => 'ASC'],
            'limit' => 20
        ]);

        foreach ($failedBills as $err) {
            $refId = $err['Sync_error']['reference_id'];
            $summary['retried']++;
            
            // $mt = microtime(true);
            // $retryReadTime = date('Y-m-d H:i:s', (int)$mt)
            //     . '.' . sprintf('%06d', ($mt - (int)$mt) * 1000000);
            $retryReadTime = date('Y-m-d H:i:s');
            
            // Re-read current data for this bill from DB
            $billData = [];
            foreach ($billTables as $table => $model) {
                $this->loadModel($model);
                $rows = $this->$model->find('all', [
                    'conditions' => [
                        'reference_id' => $refId,
                        'sync' => 0
                    ]
                ]);
                if (!empty($rows)) {
                    $tableConfig = push_table_config;
                    foreach ($rows as $r) {
                        $rec = $r[$model];
                        $siteKey = $tableConfig[$table]['site_key'] ?? 'store_id';
                        if (!empty($siteKey)) {
                            $rec[$siteKey] = !empty($rec[$siteKey]) ? (int)$rec[$siteKey] : $this->getSyncStoreIdVal();
                        }
                        $billData[$table][] = $rec;
                    }
                }
            }

            if (empty($billData)) {
                // All records already synced — mark as resolved
                $this->Sync_error->updateAll(
                    ['Sync_error.resolved' => 1],
                    ['Sync_error.reference_id' => $refId, 'Sync_error.direction' => 'push', 'Sync_error.resolved' => 0]
                );
                $summary['resolved']++;
                continue;
            }

            $payload = [$refId => $billData];
            $url = $syncLink . "/sync_data_to_live";
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST           => true,
                CURLOPT_HTTPHEADER     => ['Content-Type: application/x-www-form-urlencoded'],
                CURLOPT_POSTFIELDS     => http_build_query(['sync_data' => json_encode($payload), 'mode' => 'bill']),
                CURLOPT_TIMEOUT        => 60
            ]);
            $resp = curl_exec($ch);
            $curlErr = curl_error($ch);
            curl_close($ch);
            
            if (!empty($curlErr)) {
                $this->Sync_error->updateAll(
                    ['Sync_error.attempts' => 'Sync_error.attempts + 1',
                     'Sync_error.error_message' => "'" . addslashes("cURL: $curlErr") . "'"],
                    ['Sync_error.reference_id' => $refId, 'Sync_error.direction' => 'push', 'Sync_error.resolved' => 0]
                );
                $summary['still_failing']++;
                continue;
            }
            $result = json_decode($resp, true);

            if (!empty($result['bills'][$refId]['success'])) {
                               
                foreach ($billTables as $table => $tblModel) {
                    if (!empty($billData[$table])) {
                        $ids = array_column($billData[$table], 'id');
                        if ($ids) {
                            $this->loadModel($tblModel);
                            $idList = implode(',', array_map('intval', $ids));
                            $this->$tblModel->query(
                                "UPDATE `$table` SET sync = 1 "
                              . "WHERE id IN ($idList) AND modified <= '$retryReadTime'"
                            );
                        }
                    }
                }
                $this->Sync_error->updateAll(
                    ['Sync_error.resolved' => 1],
                    ['Sync_error.reference_id' => $refId, 'Sync_error.direction' => 'push', 'Sync_error.resolved' => 0]
                );
                $summary['resolved']++;
            } else {
                $newError = $result['bills'][$refId]['error'] ?? 'Retry failed';
                $this->Sync_error->updateAll(
                    [
                        'Sync_error.attempts' => 'Sync_error.attempts + 1',
                        'Sync_error.error_message' => "'" . addslashes($newError) . "'"
                    ],
                    ['Sync_error.reference_id' => $refId, 'Sync_error.direction' => 'push', 'Sync_error.resolved' => 0]
                );
                $summary['still_failing']++;
            }
        }

        // ----- Retry PULL errors (per-record) -----
        $failedPulls = $this->Sync_error->find('all', [
            'conditions' => [
                'Sync_error.direction' => 'pull',
                'Sync_error.resolved' => 0,
                'Sync_error.attempts <' => $maxRetry,
                'Sync_error.record_data IS NOT NULL'
            ],
            'order' => ['Sync_error.attempts' => 'ASC'],
            'limit' => 50
        ]);

        $db = ConnectionManager::getDataSource('default');

        foreach ($failedPulls as $err) {
            $table    = $err['Sync_error']['table_name'];
            $recordId = $err['Sync_error']['record_id'];
            $rowData  = json_decode($err['Sync_error']['record_data'], true);

            if (empty($rowData) || empty($table)) {
                $this->Sync_error->id = $err['Sync_error']['id'];
                $this->Sync_error->save(['resolved' => 1]);
                continue;
            }

            $summary['retried']++;

            $columns = array_keys($rowData);
            $escaped = [];
            foreach ($columns as $col) {
                $escaped[] = $db->value($rowData[$col], 'string');
            }

            $updates = [];
            foreach ($columns as $col) {
                if (!in_array($col, ['id', 'created'])) {
                    $updates[] = "`$col` = VALUES(`$col`)";
                }
            }

            $sql = "INSERT INTO `$table` (`" . implode('`,`', $columns) . "`) "
                 . "VALUES (" . implode(',', $escaped) . ") "
                 . "ON DUPLICATE KEY UPDATE " . implode(',', $updates);

            try {
                $db->query($sql);
                $this->Sync_error->id = $err['Sync_error']['id'];
                $this->Sync_error->save(['resolved' => 1]);
                $summary['resolved']++;
            } catch (Exception $e) {
                $this->Sync_error->id = $err['Sync_error']['id'];
                $this->Sync_error->save([
                    'attempts' => $err['Sync_error']['attempts'] + 1,
                    'error_message' => $e->getMessage()
                ]);
                $summary['still_failing']++;
            }
        }

        echo json_encode(['success' => true, 'summary' => $summary]);
    }

    // =========================================================
    // SYNC STATUS — Dashboard/API view of sync health
    // =========================================================
    public function sync_status()
    {
        $this->autoRender = false;
        $this->loadModel('Sync_error');

        $billTables    = bill_tables;
        $nonBillTables = non_bill_tables;

        $unsyncedCounts = [];
        foreach (array_merge($billTables, $nonBillTables) as $table => $model) {
            $this->loadModel($model);
            $count = $this->$model->find('count', ['conditions' => ['sync' => 0]]);
            if ($count > 0) {
                $unsyncedCounts[$table] = $count;
            }
        }

        $quarantined = $this->Sync_error->find('count', [
            'conditions' => ['resolved' => 0, 'attempts >=' => sync_max_quarantine_attempts]
        ]);

        $pendingRetry = $this->Sync_error->find('count', [
            'conditions' => ['resolved' => 0, 'attempts <' => sync_max_quarantine_attempts]
        ]);

        $recentErrors = $this->Sync_error->find('all', [
            'conditions' => ['resolved' => 0],
            'order' => ['modified' => 'DESC'],
            'limit' => 10
        ]);

        // Last sync times
        $lastSync = $this->_getLastSyncTimes();

        // Alerts
        $alerts = $this->_getActiveAlerts();

        echo json_encode([
            'success' => true,
            'unsynced_records' => $unsyncedCounts,
            'total_unsynced' => array_sum($unsyncedCounts),
            'quarantined_bills' => $quarantined,
            'pending_retry' => $pendingRetry,
            'recent_errors' => $recentErrors,
            'last_sync' => $lastSync,
            'alerts' => $alerts
        ]);
    }

    // =========================================================
    // LAST SYNC TIME — API endpoint for Dashboard/DSR views
    // =========================================================
    public function get_last_sync_time()
    {
        $this->autoRender = false;
        echo json_encode($this->_getLastSyncTimes());
    }

    // =========================================================
    // PRIVATE HELPERS
    // =========================================================

    /**
     * Log a sync error to the sync_errors table (upsert for bills).
     */
    private function _logSyncError($refId, $table, $recordId, $errMsg, $data, $direction)
    {
        $this->loadModel('Sync_error');

        if (!empty($refId)) {
            $existing = $this->Sync_error->find('first', [
                'conditions' => [
                    'Sync_error.reference_id' => $refId,
                    'Sync_error.direction' => $direction,
                    'Sync_error.resolved' => 0
                ]
            ]);
            if ($existing) {
                $this->Sync_error->id = $existing['Sync_error']['id'];
                $this->Sync_error->save([
                    'attempts' => $existing['Sync_error']['attempts'] + 1,
                    'error_message' => $errMsg,
                    'table_name' => $table,
                    'record_id' => $recordId
                ]);
                return;
            }
        }
        
        // --- Non-bill: match by table_name + record_id ---
        if (!empty($table) && !empty($recordId)) {
            $existing = $this->Sync_error->find('first', [
                'conditions' => [
                    'Sync_error.table_name' => $table,
                    'Sync_error.record_id'  => $recordId,
                    'Sync_error.direction'  => $direction,
                    'Sync_error.resolved'   => 0
                ]
            ]);
            if ($existing) {
                $this->Sync_error->id = $existing['Sync_error']['id'];
                $this->Sync_error->save([
                    'attempts'      => $existing['Sync_error']['attempts'] + 1,
                    'error_message' => $errMsg
                ]);
                return;
            }
        }
        
        $locationIds = $this->getSyncStoreIdVal();
        
        $this->Sync_error->create();
        $this->Sync_error->save([
            'reference_id' => $refId,
            'table_name' => $table,
            'record_id' => $recordId,
            'local_id'     => !empty($recordId) ? $recordId : null,   // 🔥 NEW
            'store_id'     => $locationIds, // 🔥 NEW
            'error_message' => $errMsg,
            'record_data' => is_array($data) ? json_encode($data) : $data,
            'direction' => $direction,
            'attempts' => 1,
            'resolved' => 0
        ]);
    }

    /**
     * Store last sync time in a file (lightweight, no extra DB table needed).
     */
    private function _updateLastSyncTime($direction, $summary)
    {
        $filePath = TMP . 'sync_status.json';
        $existing = [];
        if (file_exists($filePath)) {
            $existing = json_decode(file_get_contents($filePath), true) ?: [];
        }

        $existing[$direction] = [
            'time' => date('Y-m-d H:i:s'),
            'bills_synced' => $summary['bills_synced'] ?? 0,
            'bills_failed' => $summary['bills_failed'] ?? 0,
            'non_bill_synced' => $summary['non_bill_synced'] ?? 0,
            'records_synced' => $summary['records_synced'] ?? 0,
            'records_failed' => $summary['records_failed'] ?? 0,
        ];

        file_put_contents($filePath, json_encode($existing, JSON_PRETTY_PRINT));
    }

    /**
     * Read last sync times from the status file.
     */
    private function _getLastSyncTimes()
    {
        $filePath = TMP . 'sync_status.json';
        if (!file_exists($filePath)) {
            return ['push' => null, 'pull' => null];
        }
        return json_decode(file_get_contents($filePath), true) ?: ['push' => null, 'pull' => null];
    }

    /**
     * Check for alert conditions and log them.
     */
    private function _checkSyncAlerts()
    {
        $this->loadModel('Sync_error');

        $alertThreshold = 5;
        $alerts = [];

        // Check for repeatedly failing bills
        $criticalErrors = $this->Sync_error->find('all', [
            'conditions' => [
                'resolved' => 0,
                'attempts >=' => $alertThreshold,
                'direction' => 'push'
            ],
            'order' => ['attempts' => 'DESC'],
            'limit' => 20
        ]);

        foreach ($criticalErrors as $err) {
            $refId = $err['Sync_error']['reference_id'];
            $attempts = $err['Sync_error']['attempts'];
            $lastErr = $err['Sync_error']['error_message'];

            $alert = "[SYNC ALERT] Bill $refId has failed $attempts times. Last error: $lastErr";
            CakeLog::write('error', $alert);
            $alerts[] = $alert;
        }

        // Check total unsynced backlog
        $billTables = bill_tables;
        $totalUnsynced = 0;
        foreach ($billTables as $table => $model) {
            $this->loadModel($model);
            $totalUnsynced += $this->$model->find('count', ['conditions' => ['sync' => 0]]);
        }

        if ($totalUnsynced > 500) {
            $alert = "[SYNC ALERT] High unsynced backlog: $totalUnsynced records across bill tables";
            CakeLog::write('error', $alert);
            $alerts[] = $alert;
        }

        // Write alerts to file for dashboard consumption
        $filePath = TMP . 'sync_alerts.json';
        $existing = [];
        if (file_exists($filePath)) {
            $existing = json_decode(file_get_contents($filePath), true) ?: [];
        }

        $existing[] = [
            'time' => date('Y-m-d H:i:s'),
            'alerts' => $alerts,
            'total_unsynced' => $totalUnsynced,
            'critical_bills' => count($criticalErrors)
        ];

        // Keep only last 100 alert entries
        if (count($existing) > 100) {
            $existing = array_slice($existing, -100);
        }

        file_put_contents($filePath, json_encode($existing, JSON_PRETTY_PRINT));
    }

    /**
     * Get active alerts for the dashboard.
     */
    private function _getActiveAlerts()
    {
        $filePath = TMP . 'sync_alerts.json';
        if (!file_exists($filePath)) {
            return [];
        }
        $all = json_decode(file_get_contents($filePath), true) ?: [];
        return array_slice($all, -5);
    }
    
    // cron for sending dsr in whatsaap
    public function send_daily_dsr() 
    {

        $stores = $this->Location->find('all', [
            'conditions' => ['status' => 1,'type' => 'Outlet','id !=' =>3 ]
        ]);

        foreach ($stores as $store) {

            $storeId   = $store['Location']['id'];
            $storeName = $store['Location']['name'];

            echo "Processing Store: $storeName\n";

            $this->SendDsr($storeId,$storeName);
        }

        echo "All DSR sent\n";
    }

    public function SendDsr($storeId,$storeName)
    {
        date_default_timezone_set('Asia/Kolkata');

        $startdate  = date('Y-m-d', strtotime('-1 day'));
        $enddate      = date('Y-m-d');

        $_shiftRange = $this->getShiftDateRange($startdate, $enddate, isset($store_id) ? $store_id : (isset($location_id) ? $location_id : null)); $startDatetime = $_shiftRange['startDatetime']; $endDatetime = $_shiftRange['endDatetime'];

        $this->_sendDsrWhatsapp($startdate, $storeName,$storeId);
        
        // ── After rendering PDF, send via WhatsApp ────────────────
    }

    private function _dsrOptinChk($phone)
    {
        $appId     = appId;
        $api_token = apiKey;
        $appName = appName;

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL            => 'https://partner.gupshup.io/partner/app/' . $appId . '/userStatus?phone=' . $phone,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING       => '',
            CURLOPT_TIMEOUT        => 0,
            CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST  => 'GET',
            CURLOPT_HTTPHEADER     => [
                'token: ' . $api_token,
            ],
        ]);

        $response = curl_exec($curl);
        curl_close($curl);

        $results = json_decode($response, true);

            if (!isset($results['userStatus']) || $results['userStatus'] != 'OPT_IN') {
            $this->_dsrOptin($phone);
        }
    }

    private function _dsrOptin($phone)
    {
        $appName   = appName;
        $api_token = apiKey;

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL            => 'https://api.gupshup.io/wa/api/v1/app/opt/in/' . $appName,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING       => '',
            CURLOPT_TIMEOUT        => 0,
            CURLOPT_CUSTOMREQUEST  => 'POST',
            CURLOPT_POSTFIELDS     => 'user=' . $phone,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/x-www-form-urlencoded',
                'apikey: ' . $api_token,
                'accept: application/json',
            ],
        ]);

        $response = curl_exec($curl);
        curl_close($curl);
    }

    private function _sendDsrWhatsapp($date, $storeName,$storeId)
    {
        $apiKey     = apiKey;
        $srcNumber  = srcNumber;
        $templateId = templateId;
        $appName = appName;

        $recipients = [
            '918859705077',
        ];
        
        
        // Build the full PDF URL with actual store ID
        $pdfUrl = pdfUrl . '?id=' . $storeId;
    
        foreach ($recipients as $phone) {

            $this->_dsrOptinChk($phone);
            
            // Build template JSON separately
            $templatePayload = json_encode([
                'id'     => $templateId,
                'params' => [
                    $date,
                    $storeName,
                    $storeId
                ],
                'buttons' => [
                    [
                        'type'       => 'url',
                        'index'      => '0',
                        'url_suffix' => (string)$storeId,  // just "2", appended to base URL in template
                        'url'        => $pdfUrl
                    ]
                ]
            ]);
            
            // Build POST fields as a plain string — NOT http_build_query
            $postFields = 'source=' . urlencode($srcNumber)
                . '&destination=' . urlencode($phone)
                . '&channel=whatsapp'
                . '&src.name=' . urlencode($appName)
                . '&template=' . urlencode($templatePayload);  // ✅ manually urlencode the JSON
    
           
            $curl = curl_init();
            curl_setopt_array($curl, [
                CURLOPT_URL            => 'https://api.gupshup.io/wa/api/v1/template/msg',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CUSTOMREQUEST  => 'POST',
                CURLOPT_POSTFIELDS     => $postFields,         // ✅ raw string, not array
                CURLOPT_HTTPHEADER     => [
                    'accept: application/json',
                    'apikey: ' . $apiKey,
                    'content-type: application/x-www-form-urlencoded',
                ],
            ]);

            $response = curl_exec($curl);
            curl_close($curl);
            
            $results   = json_decode($response, true);
            $status    = $results['status'] ?? 'unknown';
            $messageId = $results['messageId'] ?? '';
        }
    }
    
    public function dsr_pdf_whatsapp()
    {
        /*
        |------------------------------------------------------------
        | PDF SETTINGS
        |------------------------------------------------------------
        */
        $this->layout = false;

        $this->pdfConfig = array(
            'download' => false,
            'filename'  => 'DSR_Report.pdf'
        );

        /*
        |------------------------------------------------------------
        | DATE LOGIC
        | Matches dsr_report: startdate uses shiftstart,
        | endDatetime adds +1 day then appends shiftend
        |------------------------------------------------------------
        */
        date_default_timezone_set('Asia/Kolkata');

        $startdate  = date('Y-m-d', strtotime('-1 day'));
        $enddate      = date('Y-m-d');

        $_shiftRange = $this->getShiftDateRange($startdate, $enddate, isset($store_id) ? $store_id : (isset($location_id) ? $location_id : null)); $startDatetime = $_shiftRange['startDatetime']; $endDatetime = $_shiftRange['endDatetime'];

        /*
        |------------------------------------------------------------
        | STORE / CASHIER — hardcoded to store_id = 1
        | Same as dsr_report when $loc_id = 1 is selected
        |------------------------------------------------------------
        */
        $cashier_id = $_GET['id'];   // ← fixed: was missing semicolon

        if ($cashier_id) {

            $cashiernames = $this->Location->find('first', [
                'conditions' => ['id' => $cashier_id]
            ]);

            $cashiername = !empty($cashiernames['Location']['name'])
                ? $cashiernames['Location']['name']
                : 'Store';

            $cashierFilter = " AND store_id=$cashier_id";
            $oohFilter     = " AND ooh.store_id=$cashier_id";
            $otTaxFilter   = " AND ot_tax.store_id=$cashier_id";
            $otDFilter     = " AND ot_d.store_id=$cashier_id";
            $otFilter      = " AND ot.store_id=$cashier_id";

        } else {

            $cashiername   = 'All Stores';
            $cashierFilter = '';
            $oohFilter     = '';
            $otTaxFilter   = '';
            $otDFilter     = '';
            $otFilter      = '';
        }

        /*
        |------------------------------------------------------------
        | PAYMENTS
        |------------------------------------------------------------
        */
        $payments = $this->Order_payment->query("
            SELECT
                COALESCE(SUM(amount), 0)                              AS total_amount,
                COALESCE(SUM(IF(type=1,  amount, 0)), 0)              AS cash,
                COALESCE(SUM(IF(type=10, amount, 0)), 0)              AS hold,
                COALESCE(SUM(IF(type=2,  amount, 0)), 0)              AS card,
                COALESCE(SUM(IF(type=9,  amount, 0)), 0)              AS online,
                COALESCE(SUM(IF(type=6,  amount, 0)), 0)              AS upi,
                COALESCE(SUM(IF(type=7,  amount, 0)), 0)              AS no_charge_with_tax,
                COALESCE(SUM(IF(type=7, ROUND(amount - (
                    SELECT COALESCE(SUM(ot_sub.total_tax), 0)
                    FROM order_transactions ot_sub
                    WHERE ot_sub.status != 0
                      AND ot_sub.reference_id = order_payments.reference_id
                      AND ot_sub.store_id     = order_payments.store_id
                ), 2), 0)), 0)                                        AS no_charge,
                COALESCE(SUM(IF(type=8,  amount, 0)), 0)              AS storecard
            FROM order_payments
            WHERE status = 2
              AND created BETWEEN '$startDatetime' AND '$endDatetime'
              $cashierFilter
        ");

        /*
        |------------------------------------------------------------
        | ONLINE PAYMENTS
        |------------------------------------------------------------
        */
        $onlinepayments = $this->Order_payment->query("
            SELECT
                type_value,
                COALESCE(SUM(amount), 0) AS total_amount
            FROM order_payments
            WHERE type = 11
              AND status = 2
              AND created BETWEEN '$startDatetime' AND '$endDatetime'
              $cashierFilter
            GROUP BY type_value
        ");

        /*
        |------------------------------------------------------------
        | TRANSACTIONS (excluding no-charge bills)
        |------------------------------------------------------------
        */
        $transactions = $this->Order_transaction->query("
            SELECT
                COALESCE(COUNT(order_transactions.id), 0)            AS total_count,
                COALESCE(SUM(order_transactions.pax), 0)             AS pax,
                COALESCE(SUM(order_transactions.total_tax), 0)       AS total_tax,
                COALESCE(SUM(order_transactions.servicecharge), 0)   AS servicecharge,
                COALESCE(SUM(order_transactions.discount), 0)        AS discount,
                COALESCE(SUM(order_transactions.delivery_charge), 0) AS delivery_charge,
                COALESCE(SUM(order_transactions.packing_charge), 0)  AS packing_charge,
                COALESCE(SUM(order_transactions.amount), 0)          AS amount,
                COALESCE(SUM(order_transactions.price), 0)           AS price,
                COALESCE(SUM(order_transactions.round), 0)           AS round,
                COALESCE(SUM(order_transactions.other_taxes), 0)     AS other_taxes
            FROM order_transactions
            WHERE order_transactions.created BETWEEN '$startDatetime' AND '$endDatetime'
              AND order_transactions.status = 2
              $cashierFilter
              AND order_transactions.reference_id NOT IN (
                  SELECT op_nc.reference_id FROM order_payments op_nc
                  WHERE op_nc.type = 7 AND op_nc.status = 2 $cashierFilter
              )
        ");

        /*
        |------------------------------------------------------------
        | TRANSACTIONS — no-charge tax only
        |------------------------------------------------------------
        */
        $transactionsnc = $this->Order_transaction->query("
            SELECT COALESCE(SUM(order_transactions.total_tax), 0) AS total_tax
            FROM order_transactions
            WHERE order_transactions.created BETWEEN '$startDatetime' AND '$endDatetime'
              AND order_transactions.status = 2
              $cashierFilter
              AND order_transactions.reference_id IN (
                  SELECT op_nc2.reference_id FROM order_payments op_nc2
                  WHERE op_nc2.type = 7 AND op_nc2.status = 2 $cashierFilter
              )
        ");

        /*
        |------------------------------------------------------------
        | TRANSACTION SUBTOTAL (all bills incl. no-charge)
        |------------------------------------------------------------
        */
        $transactionsubtotal = $this->Order_transaction->query("
            SELECT
                COALESCE(SUM(order_transactions.price), 0)    AS price,
                COALESCE(SUM(order_transactions.discount), 0) AS discount
            FROM order_transactions
            WHERE order_transactions.created BETWEEN '$startDatetime' AND '$endDatetime'
              AND order_transactions.status = 2
              $cashierFilter
        ");

        /*
        |------------------------------------------------------------
        | TAXES (excluding no-charge bills)
        |------------------------------------------------------------
        */
        $taxes = $this->Order_tax->query("
            SELECT
                order_tax.tax_id,
                order_tax.tax_name,
                order_tax.tax_percentage,
                COALESCE(SUM(order_tax.tax_value), 0) AS tax_value
            FROM order_tax
            WHERE order_tax.created BETWEEN '$startDatetime' AND '$endDatetime'
              AND order_tax.status = 2
              $cashierFilter
              AND order_tax.reference_id NOT IN (
                  SELECT op_t.reference_id FROM order_payments op_t
                  WHERE op_t.type = 7 AND op_t.status = 2 $cashierFilter
              )
            GROUP BY order_tax.tax_id
        ");

        /*
        |------------------------------------------------------------
        | CHARGES TAXES
        |------------------------------------------------------------
        */
        $charges_taxes = $this->Order_payment->query("
            SELECT
                tax_id,
                tax_name,
                tax_percentage,
                COALESCE(SUM(tax_value), 0) AS tax_value
            FROM tax_charges
            WHERE status = 1
              AND created BETWEEN '$startDatetime' AND '$endDatetime'
              $cashierFilter
            GROUP BY tax_id
        ");

        /*
        |------------------------------------------------------------
        | NET AMOUNT (excluding no-charge)
        |------------------------------------------------------------
        */
        $net_amount_query = $this->Order_transaction->query("
            SELECT ROUND(
                COALESCE(SUM(ot.price), 0) - COALESCE(SUM(ot.discount), 0),
            2) AS net_amount
            FROM order_transactions ot
            WHERE ot.status = 2
              AND ot.created BETWEEN '$startDatetime' AND '$endDatetime'
              $cashierFilter
              AND ot.reference_id NOT IN (
                  SELECT op_n1.reference_id FROM order_payments op_n1
                  WHERE op_n1.type = 7 AND op_n1.status = 2 $cashierFilter
              )
        ");

        $ncnet_amount_query = $this->Order_transaction->query("
            SELECT ROUND(
                COALESCE(SUM(ot.price), 0) - COALESCE(SUM(ot.discount), 0),
            2) AS net_amount
            FROM order_transactions ot
            WHERE ot.status = 2
              AND ot.created BETWEEN '$startDatetime' AND '$endDatetime'
              $cashierFilter
              AND ot.reference_id IN (
                  SELECT op_n2.reference_id FROM order_payments op_n2
                  WHERE op_n2.type = 7 AND op_n2.status = 2 $cashierFilter
              )
        ");

        /*
        |------------------------------------------------------------
        | TAXABLE / NON-TAXABLE
        |------------------------------------------------------------
        */
        $taxable = $this->Order_orderhistory->query("
            SELECT COALESCE(SUM(total_price), 0) AS total_taxable
            FROM order_orderhistories
            WHERE status = 2
              AND created BETWEEN '$startDatetime' AND '$endDatetime'
              AND total_tax_percentage != 0
              $cashierFilter
              AND reference_id NOT IN (
                  SELECT op_tx1.reference_id FROM order_payments op_tx1
                  WHERE op_tx1.type = 7 AND op_tx1.status = 2 $cashierFilter
              )
        ");

        $nontaxable = $this->Order_orderhistory->query("
            SELECT COALESCE(SUM(total_price), 0) AS total_nontaxable
            FROM order_orderhistories
            WHERE status = 2
              AND created BETWEEN '$startDatetime' AND '$endDatetime'
              AND total_tax_percentage = 0
              $cashierFilter
              AND reference_id NOT IN (
                  SELECT op_tx2.reference_id FROM order_payments op_tx2
                  WHERE op_tx2.type = 7 AND op_tx2.status = 2 $cashierFilter
              )
        ");

        $nctaxable = $this->Order_orderhistory->query("
            SELECT COALESCE(SUM(total_price), 0) AS total_taxable
            FROM order_orderhistories
            WHERE status = 2
              AND created BETWEEN '$startDatetime' AND '$endDatetime'
              AND total_tax_percentage != 0
              $cashierFilter
              AND reference_id IN (
                  SELECT op_tx3.reference_id FROM order_payments op_tx3
                  WHERE op_tx3.type = 7 AND op_tx3.status = 2 $cashierFilter
              )
        ");

        $ncnontaxable = $this->Order_orderhistory->query("
            SELECT COALESCE(SUM(total_price), 0) AS total_nontaxable
            FROM order_orderhistories
            WHERE status = 2
              AND created BETWEEN '$startDatetime' AND '$endDatetime'
              AND total_tax_percentage = 0
              $cashierFilter
              AND reference_id IN (
                  SELECT op_tx4.reference_id FROM order_payments op_tx4
                  WHERE op_tx4.type = 7 AND op_tx4.status = 2 $cashierFilter
              )
        ");

        /*
        |------------------------------------------------------------
        | DISH SALES
        |------------------------------------------------------------
        */
        $dish_sales = $this->Order_orderhistory->query("
            SELECT cp.dish_type,
                   SUM(ooh.quantity) AS total_qty,
                   ROUND(SUM(ot_tax.taxable_price), 2) AS total_amount
            FROM order_orderhistories ooh
            INNER JOIN catalog_product cp ON cp.id = ooh.product_id
            INNER JOIN (
                SELECT orderhistory_id, MAX(taxable_price) AS taxable_price, store_id
                FROM order_tax
                WHERE order_tax.status != 0
                GROUP BY orderhistory_id
            ) ot_tax ON ot_tax.orderhistory_id = ooh.local_id
            WHERE ooh.created BETWEEN '$startDatetime' AND '$endDatetime'
              AND ooh.status = 2
              $oohFilter
              $otTaxFilter
              AND ooh.reference_id NOT IN (
                  SELECT op_d.reference_id FROM order_payments op_d
                  WHERE op_d.type = 7 AND op_d.status = 2 $cashierFilter
              )
            GROUP BY cp.dish_type
            ORDER BY cp.dish_type DESC
        ");

        $dish_saleswithoutservice = $this->Order_orderhistory->query("
            SELECT cp.dish_type,
                   SUM(ooh.quantity) AS total_qty,
                   ROUND(SUM((ooh.price * ooh.quantity) * (1 - IFNULL(ot.discount_percentage, 0) / 100)), 2) AS total_amount
            FROM order_orderhistories ooh
            INNER JOIN catalog_product cp ON cp.id = ooh.product_id
            LEFT JOIN (
                SELECT orderhistory_id, MAX(discount_percentage) AS discount_percentage, store_id
                FROM order_tax
                WHERE order_tax.status != 0
                GROUP BY orderhistory_id
            ) ot ON ot.orderhistory_id = ooh.local_id
            WHERE ooh.created BETWEEN '$startDatetime' AND '$endDatetime'
              AND ooh.status = 2
              $oohFilter
              $otFilter
              AND ooh.reference_id NOT IN (
                  SELECT op_dw.reference_id FROM order_payments op_dw
                  WHERE op_dw.type = 7 AND op_dw.status = 2 $cashierFilter
              )
            GROUP BY cp.dish_type
            ORDER BY cp.dish_type DESC
        ");

        $sc_base_sales = $this->Order_orderhistory->query("
            SELECT cp.dish_type,
                   ROUND(SUM(ooh.price * ooh.quantity * (1 - IFNULL(ot_d.discount_percentage, 0) / 100)), 2) AS base_amount
            FROM order_orderhistories ooh
            INNER JOIN catalog_product cp ON cp.id = ooh.product_id
            LEFT JOIN (
                SELECT orderhistory_id, MAX(discount_percentage) AS discount_percentage, store_id
                FROM order_tax
                WHERE order_tax.status != 0
                GROUP BY orderhistory_id
            ) ot_d ON ot_d.orderhistory_id = ooh.local_id
            WHERE ooh.created BETWEEN '$startDatetime' AND '$endDatetime'
              AND ooh.status = 2
              $oohFilter
              $otDFilter
              AND ooh.reference_id NOT IN (
                  SELECT op_sc.reference_id FROM order_payments op_sc
                  WHERE op_sc.type = 7 AND op_sc.status = 2 $cashierFilter
              )
            GROUP BY cp.dish_type
        ");

        /*
        |------------------------------------------------------------
        | KDS SALES
        |------------------------------------------------------------
        */
        $sales = $this->Order_tax->query("
            SELECT kds, kds_name, value, tax_percentage
            FROM (
                SELECT SUM(tax_percentage) AS tax_percentage, kds, kds_name,
                       ROUND(taxable_price, 2) AS value
                FROM (
                    SELECT order_tax.tax_percentage, order_tax.tax_id, order_tax.kds,
                    (SELECT name FROM pos_printers WHERE kds = order_tax.kds AND store_id = order_tax.store_id AND status = 1 LIMIT 1) AS kds_name,
                           ROUND(SUM(order_tax.taxable_price), 2) AS taxable_price,
                           SUM(order_tax.tax_value) AS tax_value
                    FROM order_tax
                    WHERE order_tax.created BETWEEN '$startDatetime' AND '$endDatetime'
                      AND order_tax.status = 2
                      AND order_tax.reference_id IN (
                          SELECT ooh_s1.reference_id FROM order_orderhistories ooh_s1
                          WHERE ooh_s1.status != 0
                            AND ooh_s1.reference_id NOT IN (
                                SELECT op_s1.reference_id FROM order_payments op_s1
                                WHERE op_s1.type = 7 AND op_s1.status = 2 $cashierFilter
                            )
                            $cashierFilter
                      )
                      $cashierFilter
                    GROUP BY order_tax.kds, order_tax.tax_id
                ) AS sub
                GROUP BY kds, tax_value
            ) AS final
            GROUP BY kds, tax_percentage
        ");

        $ncsales = $this->Order_tax->query("
            SELECT kds, kds_name, value, tax_percentage
            FROM (
                SELECT SUM(tax_percentage) AS tax_percentage, kds, kds_name,
                       ROUND(taxable_price, 2) AS value
                FROM (
                    SELECT order_tax.tax_percentage, order_tax.tax_id, order_tax.kds,
                      (SELECT name FROM pos_printers WHERE kds = order_tax.kds AND store_id = order_tax.store_id AND status = 1 LIMIT 1) AS kds_name,
                           ROUND(SUM(order_tax.taxable_price), 2) AS taxable_price,
                           SUM(order_tax.tax_value) AS tax_value
                    FROM order_tax
                    WHERE order_tax.created BETWEEN '$startDatetime' AND '$endDatetime'
                      AND order_tax.status = 2
                      AND order_tax.reference_id IN (
                          SELECT ooh_s2.reference_id FROM order_orderhistories ooh_s2
                          WHERE ooh_s2.status != 0
                            AND ooh_s2.reference_id IN (
                                SELECT op_s2.reference_id FROM order_payments op_s2
                                WHERE op_s2.type = 7 AND op_s2.status = 2 $cashierFilter
                            )
                            $cashierFilter
                      )
                      $cashierFilter
                    GROUP BY order_tax.kds, order_tax.tax_id
                ) AS sub
                GROUP BY kds, tax_value
            ) AS final
            GROUP BY kds, tax_percentage
        ");

        /*
        |------------------------------------------------------------
        | CALCULATIONS — guarded like dsr_report
        |------------------------------------------------------------
        */
        $subtotal        = $transactions[0][0]['price']          ?? 0;
        $discount        = $transactions[0][0]['discount']       ?? 0;
        $tax             = $transactions[0][0]['total_tax']      ?? 0;
        $service         = $transactions[0][0]['servicecharge']  ?? 0;
        $delivery        = $transactions[0][0]['delivery_charge']?? 0;
        $packing         = $transactions[0][0]['packing_charge'] ?? 0;

        // ✅ Matches dsr_report null guard on other_taxes
        $other_taxes_sum = isset($transactions[0][0]['other_taxes'])
            ? round($transactions[0][0]['other_taxes'], 2)
            : 0;

        $netSale     = $subtotal - $discount;
        $finalAmount = $netSale + $tax + $service + $delivery + $packing;

        /*
        |------------------------------------------------------------
        | MAIN DATA ARRAY
        |------------------------------------------------------------
        */
        $datas   = [];
        $datas[] = [
            'cashier'            => $cashiername,
            'total_collection'   => round($payments[0][0]['total_amount'] ?? 0, 2),
            'cash'               => round($payments[0][0]['cash']         ?? 0, 2),
            'card'               => round($payments[0][0]['card']         ?? 0, 2),
            'online'             => round($payments[0][0]['online']       ?? 0, 2),
            'upi'                => round($payments[0][0]['upi']          ?? 0, 2),
            'hold'               => round($payments[0][0]['hold']         ?? 0, 2),
            'storecard'          => round($payments[0][0]['storecard']    ?? 0, 2),
            'no_charge_with_tax' => round($payments[0][0]['no_charge_with_tax'] ?? 0, 2),
            'no_charge'          => round($payments[0][0]['no_charge']    ?? 0, 2),
            'subtotal'           => round($subtotal,  2),
            'discount'           => round($discount,  2),
            'net_sale'           => round($netSale,   2),
            'service_charge'     => round($service,   2),
            'delivery_charge'    => round($delivery,  2),
            'packing_charge'     => round($packing,   2),
            'total_tax'          => round($tax,       2),
            'final_amount'       => round($finalAmount, 2),
            'net_amount'         => round($transactions[0][0]['amount'] ?? 0, 2),
            'round'              => round($transactions[0][0]['round']  ?? 0, 2),
            'bill_count'         => $transactions[0][0]['total_count']  ?? 0,
            'pax'                => round($transactions[0][0]['pax']    ?? 0, 2),
            'other_taxes'        => $other_taxes_sum,
        ];

        /*
        |------------------------------------------------------------
        | TAX ARRAY
        |------------------------------------------------------------
        */
        $taxesarr = [];
        foreach ($taxes as $tax) {
            $taxesarr[] = [
                'tax_name'  => $tax['order_tax']['tax_name']
                             . ' (' . $tax['order_tax']['tax_percentage'] . '%)',
                'tax_value' => round($tax[0]['tax_value'], 2),
            ];
        }

        /*
        |------------------------------------------------------------
        | ONLINE PAYMENT ARRAY
        |------------------------------------------------------------
        */
        $onlinepaymentsarr = [];
        foreach ($onlinepayments as $op) {
            $onlinepaymentsarr[] = [
                'type'   => $op['order_payments']['type_value'],
                'amount' => round($op[0]['total_amount'], 2),
            ];
        }

        /*
        |------------------------------------------------------------
        | SEND TO VIEW
        |------------------------------------------------------------
        */
        $this->set(compact(
            'startDatetime', 'endDatetime', 'datas', 'taxesarr', 'onlinepaymentsarr',
            'payments', 'onlinepayments', 'taxes', 'transactions',
            'transactionsnc', 'transactionsubtotal',
            'net_amount_query', 'ncnet_amount_query',
            'taxable', 'nontaxable', 'nctaxable', 'ncnontaxable',
            'dish_sales', 'dish_saleswithoutservice', 'sc_base_sales',
            'sales', 'ncsales', 'charges_taxes', 'other_taxes_sum'
        ));


        $this->render('dsr_pdf_whatsapp');
    }
    
    /**
     * Check if internet connection is active before running cron sync.
     * Uses socket connection checks with fallback so even on slower connections
     * it succeeds without false negatives.
     *
     * @param int $timeout Connection probe timeout in seconds (default 4)
     * @return bool
     */
    private function has_internet_connection($timeout = 4)
    {
        // 1. Direct IP check (Google Public DNS 8.8.8.8) - fast & independent of local DNS
        $connected = @fsockopen("8.8.8.8", 53, $errno, $errstr, $timeout);
        if ($connected) {
            fclose($connected);
            return true;
        }

        // 2. Direct IP check to cloud sync server (103.35.121.89)
        $connected = @fsockopen("103.35.121.89", 80, $errno, $errstr, $timeout);
        if ($connected) {
            fclose($connected);
            return true;
        }

        // 3. Fallback domain check (www.google.com)
        $connected = @fsockopen("www.google.com", 80, $errno, $errstr, $timeout);
        if ($connected) {
            fclose($connected);
            return true;
        }

        return false;
    }

    
}
