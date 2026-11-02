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
class KdsController extends AppController {
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
        // Global Use Private Functions 
     
     /************************* MOBILE APP APIS ******************************/
     
    //kds 
    public function login()
    {
        $username = $_REQUEST['username'] ?? '';
        $password = $_REQUEST['password'] ?? '';
        
        if (empty($username)) {
            return json_encode(['status' => false, 'message' => "Username missing..!"]);
        }
        
        if (empty($password)) {
            return json_encode(['status' => false, 'message' => "Password missing..!"]);
        }
        
        $password = AuthComponent::password($password);
        
        $user = $this->Kds_user->find('first', [
            'conditions' => [
                'Kds_user.username' => $username,
                'Kds_user.password' => $password,
                'Kds_user.status' => 1
            ],
            'recursive' => 1
        ]);
        
        $stations = [];
        if (!empty($user)) {
            // $kds_stations = $this->Kds_users_station_mapping->find('all', [
            //     'conditions' => [
            //         'Kds_users_station_mapping.kds_id' => $user['Kds_user']['id'],
            //         'Kds_users_station_mapping.status' => 1
            //     ],
            //     'recursive' => 1
            // ]);
        
            // foreach ($kds_stations as $kds_station) {
            //     $stations[] = [
            //         'station_id' => $kds_station['Kds_users_station_mapping']['station_id'] ?? '',
            //         'station_name' => $kds_station['Kds_users_station_mapping']['station_name'] ?? '',
            //         'station_store_id' => $kds_station['Kds_users_station_mapping']['station_store_id'] ?? '',
            //         'station_store_name' => $kds_station['Kds_users_station_mapping']['station_store_name'] ?? '',
            //         'station_created' => $kds_station['Kds_users_station_mapping']['created'] ?? ''
            //     ];
            // }
        
            $response = [
                'status' => true,
                'message' => "Success..!",
                'id' => $user['Kds_user']['id'] ?? '',
                'username' => $user['Kds_user']['username'] ?? '',
                'role' => $user['Kds_user']['role'] ?? '',
                'location_id' => $user['Kds_user']['location_id'] ?? '',
                'location_name' => $user['Kds_user']['location_name'] ?? '',
                'user_status' => $user['Kds_user']['status'] ?? '',
                'created' => $user['Kds_user']['created'] ?? '',
                // 'stations' => $stations
            ];
        } else {
            $response = ['status' => false, 'message' => "Failed..!"];
        }
        
        return json_encode($response);
    }
    
    public function expo_listing()
    {
        $this->autoRender = false;
        $user_id = isset($_REQUEST['id']) ? $_REQUEST['id'] : '';
        $today = date("Y-m-d");
    
        if (empty($user_id)) {
            return json_encode(['status' => false, 'message' => "User Id Missing"]);
        }
    
        // Fetch user
        $user = $this->Kds_user->find('first', [
            'conditions' => [
                'Kds_user.id' => $user_id,
                'Kds_user.role' => 'Expo',
                'Kds_user.status' => 1
            ],
            'recursive' => -1
        ]);
    
        if (empty($user)) {
            return json_encode(['status' => false, 'message' => "User or Role not Found"]);
        }
    
        // Fetch KDS stations once
        $kds_stations = $this->Kds_users_station_mapping->find('all', [
            'conditions' => [
                'Kds_users_station_mapping.kds_id' => $user_id,
                'Kds_users_station_mapping.status' => 1
            ],
            'joins' => [
                [
                    'table' => 'pos_printers',
                    'alias' => 'Pos_printer',
                    'type' => 'INNER',
                    'conditions' => [
                        'Pos_printer.kds = Kds_users_station_mapping.station_id',
                        'Pos_printer.store_id = Kds_users_station_mapping.station_store_id',
                        'Pos_printer.status' => 1
                    ]
                ]
            ],
            'fields' => ['Kds_users_station_mapping.*'],
            'recursive' => -1
        ]);
    
        if (empty($kds_stations)) {
            return json_encode(['status' => false, 'message' => "No KDS stations assigned"]);
        }
    
        $stations_id = array();
        $stations_map = array();
        foreach ($kds_stations as $s) {
            $station = $s['Kds_users_station_mapping'];
            $stations_id[] = $station['station_id'];
            $stations_map[$station['station_id']] = array(
                'station_id' => $station['station_id'],
                'station_name' => $station['station_name'],
                'station_store_id' => $station['station_store_id'],
                'station_store_name' => $station['station_store_name'],
                'items' => array()
            );
        }
    
        if (empty($stations_id)) {
            $stations_id = array(0);
        }
    
        $location_id = !empty($user['Kds_user']['location_id']) ? $user['Kds_user']['location_id'] : '';

        $ordersConditions = array(
            'DATE(Order_orderhistory.created)' => $today,
            'Order_orderhistory.kds_status !=' => 6,
            'Order_orderhistory.status !=' => 0,
            'Order_orderhistory.kds' => $stations_id
        );
        if (!empty($location_id)) {
            $ordersConditions['Order_orderhistory.store_id'] = $location_id;
        }

        // Fetch all orders for today for these stations
        $orders_raw = $this->Order_orderhistory->find('all', [
            'conditions' => $ordersConditions,
            'fields' => array(
                'Order_orderhistory.*',
                'Order_table.waiter_name',
                'GROUP_CONCAT(DISTINCT Order_orderhistory.kot_number ORDER BY Order_orderhistory.kot_number SEPARATOR ", ") AS kot_numbers'
            ),
            'joins' => array(
                array(
                    'table' => 'order_tables',
                    'alias' => 'Order_table',
                    'type' => 'LEFT',
                    'conditions' => array('Order_table.reference_id = Order_orderhistory.reference_id')
                )
            ),
            'group' => array('Order_orderhistory.reference_id', 'Order_orderhistory.group_id'),
            'order' => ['Order_orderhistory.id ASC'],  // ðŸ‘ˆ Added order by
            // 'limit' => 10,   //  Added limit here
            'recursive' => -1
        ]);
    
        if (empty($orders_raw)) {
            return json_encode(array('status' => false, 'message' => "No Order found"));
        }
    
        // Collect all reference_ids
        $reference_ids = array();
        foreach ($orders_raw as $o) {
            $reference_ids[] = $o['Order_orderhistory']['reference_id'];
            $group_ids[] = $o['Order_orderhistory']['group_id'];
        }
    
        $orderItemsConditions = array(
            'Order_orderhistory.status !=' => 0,
            'DATE(Order_orderhistory.created)' => $today,
            'Order_orderhistory.reference_id' => $reference_ids,
            'Order_orderhistory.group_id' => $group_ids,
            'Order_orderhistory.kds_status !=' => 6,
            'Order_orderhistory.kds' => $stations_id
        );
        if (!empty($location_id)) {
            $orderItemsConditions['Order_orderhistory.store_id'] = $location_id;
        }

        $order_items_raw = $this->Order_orderhistory->find('all', array(
            'conditions' => $orderItemsConditions,
            'fields' => array(
                'Order_orderhistory.*',
                'Catalog_product.preparation_time'
            ),
            'joins' => array(
                array(
                    'table' => 'catalog_product',
                    'alias' => 'Catalog_product',
                    'type' => 'LEFT',
                    'conditions' => array('Catalog_product.id = Order_orderhistory.product_id')
                )
            ),
            'recursive' => -1
        ));
    
        // Fetch all modifiers in bulk
        $order_items_ids = array();
        foreach ($order_items_raw as $oi) {
            $order_items_ids[] = $oi['Order_orderhistory']['id'];
        }
    
        $modifiers_raw = array();
        if (!empty($order_items_ids)) {
            $modifiers_raw = $this->Order_modifier->find('all', array(
                'conditions' => array(
                    'Order_modifier.orderhistory_id' => $order_items_ids,
                    'Order_modifier.status !=' => 0
                ),
                'recursive' => -1
            ));
        }
    
        // Map modifiers by orderhistory_id
        $modifiers_map = array();
        foreach ($modifiers_raw as $mod) {
            $m = $mod['Order_modifier'];
            $modifiers_map[$m['orderhistory_id']][] = array(
                'modifier_group_id' => $m['modifier_group_id'],
                'modifier_group_name' => $m['modifier_group_name'],
                'modifier_id' => $m['modifier_id'],
                'modifier_name' => $m['modifier_name'],
                'modifier_price' => $m['modifier_price']
            );
        }
    
        // Organize items under stations and references
        $orders = array();
        foreach ($orders_raw as $order) {
            $order_data = $order['Order_orderhistory'];
            $reference_id = $order_data['reference_id'];
            $group_id = $order_data['group_id'];
            $kotNumbers = $order[0]['kot_numbers'];  // from the custom SQL field
            
            $stations = array();
            foreach ($stations_map as $station_id => $station_data) {
                $items = array();
                foreach ($order_items_raw as $oi) {
                    $oi_data = $oi['Order_orderhistory'];
                    if ($oi_data['reference_id'] == $reference_id && $oi_data['kds'] == $station_id && $oi_data['group_id'] == $group_id ) {
                        
                        
                        $kds_last_details = $this->Kds_track->find('first', [
                            'conditions' => [
                                'Kds_track.order_reference_id' => $reference_id,
                                'Kds_track.kds_group_id'       => $group_id,
                                'Kds_track.product_id'         => $oi_data['product_id'],
                                'Kds_track.status'             => 1
                            ],
                            'order'     => ['Kds_track.id' => 'DESC'], // latest record
                            'recursive' => -1
                        ]);
                        
                        if (empty($kds_last_details)) {
                            $kds_time = $oi_data['created'];
                        } else {
                            $kds_time = $kds_last_details['Kds_track']['created'];
                        }

                        $items[] = array(
                            'order_history_id' => $oi_data['id'],
                            'group_id' => $oi_data['group_id'],
                            'item_id' => $oi_data['product_id'],
                            'quantity' => $oi_data['quantity'],
                            'item_name' => $oi_data['product_name'],
                            'kds_status' => $oi_data['kds_status'],
                            'kds_status_update_at' => $kds_time,
                            'remarks' => $oi_data['remarks'],
                            'prep_time' => strval(isset($oi['Catalog_product']['preparation_time']) ? $oi['Catalog_product']['preparation_time'] : '0'),
                            'modifiers' => isset($modifiers_map[$oi_data['id']]) ? $modifiers_map[$oi_data['id']] : array()
                        );
                    }
                }
                $station_data['items'] = $items;
                if($station_data['items']){
                    $stations[] = $station_data;
                }
            }
            
            if($order_data['table_number']){
                $table_number=$order_data['table_number'];
            }else{
                $table_number='Quick Order';
            }
    
            $orders[] = array(
                'kds_ref_id' => $reference_id,
                'kds_group_id' => $group_id,
                'table_no' => $table_number,
                'kot_number' => $kotNumbers,
                'created' => $order_data['created'],
                'waiter_name' => isset($order['Order_table']['waiter_name']) ? $order['Order_table']['waiter_name'] : '',
                'kds_status' => $order_data['kds_status'],
                'stations' => $stations
            );
        }
    
        return json_encode(array('status' => true, 'orders' => $orders), JSON_UNESCAPED_UNICODE);
    }
    
    public function expo_history()
    {
        $this->autoRender = false;
        $user_id = isset($_REQUEST['id']) ? $_REQUEST['id'] : '';
        $today = date("Y-m-d");
    
        if (empty($user_id)) {
            return json_encode(['status' => false, 'message' => "User Id Missing"]);
        }
    
        // Fetch user
        $user = $this->Kds_user->find('first', [
            'conditions' => [
                'Kds_user.id' => $user_id,
                'Kds_user.role' => 'Expo',
                'Kds_user.status' => 1
            ],
            'recursive' => -1
        ]);
    
        if (empty($user)) {
            return json_encode(['status' => false, 'message' => "User or Role not Found"]);
        }
    
        // Fetch KDS stations once
        $kds_stations = $this->Kds_users_station_mapping->find('all', [
            'conditions' => [
                'Kds_users_station_mapping.kds_id' => $user_id,
                'Kds_users_station_mapping.status' => 1
            ],
            'joins' => [
                [
                    'table' => 'pos_printers',
                    'alias' => 'Pos_printer',
                    'type' => 'INNER',
                    'conditions' => [
                        'Pos_printer.kds = Kds_users_station_mapping.station_id',
                        'Pos_printer.store_id = Kds_users_station_mapping.station_store_id',
                        'Pos_printer.status' => 1
                    ]
                ]
            ],
            'fields' => ['Kds_users_station_mapping.*'],
            'recursive' => -1
        ]);
    
        if (empty($kds_stations)) {
            return json_encode(['status' => false, 'message' => "No KDS stations assigned"]);
        }
    
        $stations_id = array();
        $stations_map = array();
        foreach ($kds_stations as $s) {
            $station = $s['Kds_users_station_mapping'];
            $stations_id[] = $station['station_id'];
            $stations_map[$station['station_id']] = array(
                'station_id' => $station['station_id'],
                'station_name' => $station['station_name'],
                'station_store_id' => $station['station_store_id'],
                'station_store_name' => $station['station_store_name'],
                'items' => array()
            );
        }
    
        if (empty($stations_id)) {
            $stations_id = array(0);
        }
    
        $location_id = !empty($user['Kds_user']['location_id']) ? $user['Kds_user']['location_id'] : '';

        $ordersConditions = array(
            'DATE(Order_orderhistory.created)' => $today,
            'Order_orderhistory.kds_status' => 6,
            'Order_orderhistory.status !=' => 0,
            'Order_orderhistory.kds' => $stations_id
        );
        if (!empty($location_id)) {
            $ordersConditions['Order_orderhistory.store_id'] = $location_id;
        }

        // Fetch all orders for today for these stations
        $orders_raw = $this->Order_orderhistory->find('all', [
            'conditions' => $ordersConditions,
            'fields' => array(
                'Order_orderhistory.*',
                'Order_table.waiter_name',
                'GROUP_CONCAT(DISTINCT Order_orderhistory.kot_number ORDER BY Order_orderhistory.kot_number SEPARATOR ", ") AS kot_numbers'
            ),
            'joins' => array(
                array(
                    'table' => 'order_tables',
                    'alias' => 'Order_table',
                    'type' => 'LEFT',
                    'conditions' => array('Order_table.reference_id = Order_orderhistory.reference_id')
                )
            ),
            'group' => array('Order_orderhistory.reference_id', 'Order_orderhistory.group_id'),
            'order' => ['Order_orderhistory.id DESC'],  // ðŸ‘ˆ Added order by
            'recursive' => -1
        ]);
    
        if (empty($orders_raw)) {
            return json_encode(array('status' => false, 'message' => "No Order found"));
        }
    
        // Collect all reference_ids
        $reference_ids = array();
        foreach ($orders_raw as $o) {
            $reference_ids[] = $o['Order_orderhistory']['reference_id'];
        }
    
        $orderItemsConditions = array(
            'Order_orderhistory.status !=' => 0,
            'Order_orderhistory.reference_id' => $reference_ids,
            'Order_orderhistory.kds_status' => 6,
            'Order_orderhistory.kds' => $stations_id
        );
        if (!empty($location_id)) {
            $orderItemsConditions['Order_orderhistory.store_id'] = $location_id;
        }

        // Fetch all order items for these references and stations
        $order_items_raw = $this->Order_orderhistory->find('all', array(
            'conditions' => $orderItemsConditions,
            'fields' => array(
                'Order_orderhistory.*',
                'Catalog_product.preparation_time'
            ),
            'joins' => array(
                array(
                    'table' => 'catalog_product',
                    'alias' => 'Catalog_product',
                    'type' => 'LEFT',
                    'conditions' => array('Catalog_product.id = Order_orderhistory.product_id')
                )
            ),
            'recursive' => -1
        ));
    
        // Fetch all modifiers in bulk
        $order_items_ids = array();
        foreach ($order_items_raw as $oi) {
            $order_items_ids[] = $oi['Order_orderhistory']['id'];
        }
    
        $modifiers_raw = array();
        if (!empty($order_items_ids)) {
            $modifiers_raw = $this->Order_modifier->find('all', array(
                'conditions' => array(
                    'Order_modifier.orderhistory_id' => $order_items_ids,
                    'Order_modifier.status !=' => 0
                ),
                'recursive' => -1
            ));
        }
    
        // Map modifiers by orderhistory_id
        $modifiers_map = array();
        foreach ($modifiers_raw as $mod) {
            $m = $mod['Order_modifier'];
            $modifiers_map[$m['orderhistory_id']][] = array(
                'modifier_group_id' => $m['modifier_group_id'],
                'modifier_group_name' => $m['modifier_group_name'],
                'modifier_id' => $m['modifier_id'],
                'modifier_name' => $m['modifier_name'],
                'modifier_price' => $m['modifier_price']
            );
        }
    
        // Organize items under stations and references
        $orders = array();
        foreach ($orders_raw as $order) {
            $order_data = $order['Order_orderhistory'];
            $reference_id = $order_data['reference_id'];
            $group_id = $order_data['group_id'];
            $kotNumbers = $order[0]['kot_numbers'];  // from the custom SQL field

            $stations = array();
            foreach ($stations_map as $station_id => $station_data) {
                $items = array();
                foreach ($order_items_raw as $oi) {
                    $oi_data = $oi['Order_orderhistory'];
                    if ($oi_data['reference_id'] == $reference_id && $oi_data['kds'] == $station_id  && $oi_data['group_id'] == $group_id) {
                        $items[] = array(
                            'order_history_id' => $oi_data['id'],
                            'item_id' => $oi_data['product_id'],
                            'quantity' => $oi_data['quantity'],
                            'remarks' => $oi_data['remarks'],
                            'item_name' => $oi_data['product_name'],
                            'kds_status' => $oi_data['kds_status'],
                            'prep_time' => strval(isset($oi['Catalog_product']['preparation_time']) ? $oi['Catalog_product']['preparation_time'] : '0'),
                            'modifiers' => isset($modifiers_map[$oi_data['id']]) ? $modifiers_map[$oi_data['id']] : array()
                        );
                    }
                }
                $station_data['items'] = $items;
                if($station_data['items']){
                    $stations[] = $station_data;
                }
            }
            
            if($order_data['table_number']){
                $table_number=$order_data['table_number'];
            }else{
                $table_number='Quick Order';
            }
    
            $orders[] = array(
                'kds_ref_id' => $reference_id,
                'kds_group_id' => $group_id,
                'table_no' => $table_number,
                'kot_number' => $kotNumbers,
                'created' => $order_data['created'],
                'waiter_name' => isset($order['Order_table']['waiter_name']) ? $order['Order_table']['waiter_name'] : '',
                'kds_status' => $order_data['kds_status'],
                'stations' => $stations
            );
        }
    
        return json_encode(array('status' => true, 'orders' => $orders), JSON_UNESCAPED_UNICODE);
    }
    
    public function prep_listing()
    {
        $this->autoRender = false;
        $user_id = isset($_REQUEST['id']) ? $_REQUEST['id'] : '';
        $today = date("Y-m-d");
    
        if (empty($user_id)) {
            return json_encode(['status' => false, 'message' => "User Id Missing"]);
        }
    
        // Fetch user
        $user = $this->Kds_user->find('first', [
            'conditions' => [
                'Kds_user.id' => $user_id,
                'Kds_user.role' => 'Preparation',
                'Kds_user.status' => 1
            ],
            'recursive' => -1
        ]);
    
        if (empty($user)) {
            return json_encode(['status' => false, 'message' => "User or Role not Found"]);
        }
    
        // Fetch KDS stations once
        $kds_stations = $this->Kds_users_station_mapping->find('all', [
            'conditions' => [
                'Kds_users_station_mapping.kds_id' => $user_id,
                'Kds_users_station_mapping.status' => 1
            ],
            'joins' => [
                [
                    'table' => 'pos_printers',
                    'alias' => 'Pos_printer',
                    'type' => 'INNER',
                    'conditions' => [
                        'Pos_printer.kds = Kds_users_station_mapping.station_id',
                        'Pos_printer.store_id = Kds_users_station_mapping.station_store_id',
                        'Pos_printer.status' => 1
                    ]
                ]
            ],
            'fields' => ['Kds_users_station_mapping.*'],
            'recursive' => -1
        ]);
    
        if (empty($kds_stations)) {
            return json_encode(['status' => false, 'message' => "No KDS stations assigned"]);
        }
    
        $stations_id = array();
        $stations_map = array();
        foreach ($kds_stations as $s) {
            $station = $s['Kds_users_station_mapping'];
            $stations_id[] = $station['station_id'];
            $stations_map[$station['station_id']] = array(
                'station_id' => $station['station_id'],
                'station_name' => $station['station_name'],
                'station_store_id' => $station['station_store_id'],
                'station_store_name' => $station['station_store_name'],
                'items' => array()
            );
        }
    
        if (empty($stations_id)) {
            $stations_id = array(0);
        }
    
        $location_id = !empty($user['Kds_user']['location_id']) ? $user['Kds_user']['location_id'] : '';

        $ordersConditions = array(
            'DATE(Order_orderhistory.created)' => $today,
            'Order_orderhistory.kds_status' => array(1,3,7),
            'Order_orderhistory.status !=' => 0,
            'Order_orderhistory.kds' => $stations_id
        );
        if (!empty($location_id)) {
            $ordersConditions['Order_orderhistory.store_id'] = $location_id;
        }

        // Fetch all orders for today for these stations
        $orders_raw = $this->Order_orderhistory->find('all', [
            'conditions' => $ordersConditions,
            'fields' => array(
                'Order_orderhistory.*',
                'Order_table.waiter_name',
                'GROUP_CONCAT(DISTINCT Order_orderhistory.kot_number ORDER BY Order_orderhistory.kot_number SEPARATOR ", ") AS kot_numbers'
            ),
            'joins' => array(
                array(
                    'table' => 'order_tables',
                    'alias' => 'Order_table',
                    'type' => 'LEFT',
                    'conditions' => array('Order_table.reference_id = Order_orderhistory.reference_id')
                )
            ),
            'group' => array('Order_orderhistory.reference_id', 'Order_orderhistory.group_id'),
            'order' => ['Order_orderhistory.id ASC'],  // ðŸ‘ˆ Added order by
            // 'limit' => 10,   //  Added limit here
            'recursive' => -1
        ]);
    
        if (empty($orders_raw)) {
            return json_encode(array('status' => false, 'message' => "No Order found"));
        }
    
        // Collect all reference_ids
        $reference_ids = array();
        foreach ($orders_raw as $o) {
            $reference_ids[] = $o['Order_orderhistory']['reference_id'];
        }
    
        $orderItemsConditions = array(
            'DATE(Order_orderhistory.created)' => $today,
            'Order_orderhistory.kds_status' => array(1,3,7),
            'Order_orderhistory.status !=' => 0,
            'Order_orderhistory.reference_id' => $reference_ids,
            'Order_orderhistory.kds' => $stations_id
        );
        if (!empty($location_id)) {
            $orderItemsConditions['Order_orderhistory.store_id'] = $location_id;
        }

        // Fetch all order items for these references and stations
        $order_items_raw = $this->Order_orderhistory->find('all', array(
            'conditions' => $orderItemsConditions,
            'fields' => array(
                'Order_orderhistory.*',
                'Catalog_product.preparation_time'
            ),
            'joins' => array(
                array(
                    'table' => 'catalog_product',
                    'alias' => 'Catalog_product',
                    'type' => 'LEFT',
                    'conditions' => array('Catalog_product.id = Order_orderhistory.product_id')
                )
            ),
            'recursive' => -1
        ));
    
        // Fetch all modifiers in bulk
        $order_items_ids = array();
        foreach ($order_items_raw as $oi) {
            $order_items_ids[] = $oi['Order_orderhistory']['id'];
        }
    
        $modifiers_raw = array();
        if (!empty($order_items_ids)) {
            $modifiers_raw = $this->Order_modifier->find('all', array(
                'conditions' => array(
                    'Order_modifier.orderhistory_id' => $order_items_ids,
                    'Order_modifier.status !=' => 0
                ),
                'recursive' => -1
            ));
        }
    
        // Map modifiers by orderhistory_id
        $modifiers_map = array();
        foreach ($modifiers_raw as $mod) {
            $m = $mod['Order_modifier'];
            $modifiers_map[$m['orderhistory_id']][] = array(
                'modifier_group_id' => $m['modifier_group_id'],
                'modifier_group_name' => $m['modifier_group_name'],
                'modifier_id' => $m['modifier_id'],
                'modifier_name' => $m['modifier_name'],
                'modifier_price' => $m['modifier_price']
            );
        }
    
        // Organize items under stations and references
        $orders = array();
        foreach ($orders_raw as $order) {
            $order_data = $order['Order_orderhistory'];
            $reference_id = $order_data['reference_id'];
            $group_id = $order_data['group_id'];
            $kotNumbers = $order[0]['kot_numbers'];  // from the custom SQL field

            $stations = array();
            foreach ($stations_map as $station_id => $station_data) {
                $items = array();
                foreach ($order_items_raw as $oi) {
                    $oi_data = $oi['Order_orderhistory'];
                    if ($oi_data['reference_id'] == $reference_id && $oi_data['kds'] == $station_id  && $oi_data['group_id'] == $group_id) {
                        
                        $kds_last_details = $this->Kds_track->find('first', [
                            'conditions' => [
                                'Kds_track.order_reference_id' => $reference_id,
                                'Kds_track.kds_group_id'       => $group_id,
                                'Kds_track.product_id'         => $oi_data['product_id'],
                                'Kds_track.status'         => 1
                            ],
                            'order'     => ['Kds_track.id' => 'DESC'], // latest record
                            'recursive' => -1
                        ]);
                        
                        if (empty($kds_last_details)) {
                            $kds_time = $oi_data['created'];
                        } else {
                            $kds_time = $kds_last_details['Kds_track']['created'];
                        }

                        $items[] = array(
                            'order_history_id' => $oi_data['id'],
                            'item_id' => $oi_data['product_id'],
                            'quantity' => $oi_data['quantity'],
                            'remarks' => $oi_data['remarks'],
                            'item_name' => $oi_data['product_name'],
                            'kds_status' => $oi_data['kds_status'],
                            'kds_status_update_at' => $kds_time,
                            'prep_time' => strval(isset($oi['Catalog_product']['preparation_time']) ? $oi['Catalog_product']['preparation_time'] : '0'),
                            'modifiers' => isset($modifiers_map[$oi_data['id']]) ? $modifiers_map[$oi_data['id']] : array()
                        );
                    }
                }
                $station_data['items'] = $items;
                if($station_data['items']){
                    $stations[] = $station_data;
                }
            }
            
            if($order_data['table_number']){
                $table_number=$order_data['table_number'];
            }else{
                $table_number='Quick Order';
            }
    
            $orders[] = array(
                'kds_ref_id' => $reference_id,
                'kds_group_id' => $group_id,
                'table_no' => $table_number,
                'kot_number' => $kotNumbers,
                'created' => $order_data['created'],
                'waiter_name' => isset($order['Order_table']['waiter_name']) ? $order['Order_table']['waiter_name'] : '',
                'kds_status' => $order_data['kds_status'],
                'stations' => $stations
            );
        }
    
        return json_encode(array('status' => true, 'orders' => $orders), JSON_UNESCAPED_UNICODE);
    }

    public function prep_history()
    {
        $this->autoRender = false;
        $user_id = isset($_REQUEST['id']) ? $_REQUEST['id'] : '';
        $today = date("Y-m-d");
    
        if (empty($user_id)) {
            return json_encode(['status' => false, 'message' => "User Id Missing"]);
        }
    
        // Fetch user
        $user = $this->Kds_user->find('first', [
            'conditions' => [
                'Kds_user.id' => $user_id,
                'Kds_user.role' => 'Preparation',
                'Kds_user.status' => 1
            ],
            'recursive' => -1
        ]);
    
        if (empty($user)) {
            return json_encode(['status' => false, 'message' => "User or Role not Found"]);
        }
    
        // Fetch KDS stations once
        $kds_stations = $this->Kds_users_station_mapping->find('all', [
            'conditions' => [
                'Kds_users_station_mapping.kds_id' => $user_id,
                'Kds_users_station_mapping.status' => 1
            ],
            'joins' => [
                [
                    'table' => 'pos_printers',
                    'alias' => 'Pos_printer',
                    'type' => 'INNER',
                    'conditions' => [
                        'Pos_printer.kds = Kds_users_station_mapping.station_id',
                        'Pos_printer.store_id = Kds_users_station_mapping.station_store_id',
                        'Pos_printer.status' => 1
                    ]
                ]
            ],
            'fields' => ['Kds_users_station_mapping.*'],
            'recursive' => -1
        ]);
    
        if (empty($kds_stations)) {
            return json_encode(['status' => false, 'message' => "No KDS stations assigned"]);
        }
    
        $stations_id = array();
        $stations_map = array();
        foreach ($kds_stations as $s) {
            $station = $s['Kds_users_station_mapping'];
            $stations_id[] = $station['station_id'];
            $stations_map[$station['station_id']] = array(
                'station_id' => $station['station_id'],
                'station_name' => $station['station_name'],
                'station_store_id' => $station['station_store_id'],
                'station_store_name' => $station['station_store_name'],
                'items' => array()
            );
        }
    
        if (empty($stations_id)) {
            $stations_id = array(0);
        }
    
        $location_id = !empty($user['Kds_user']['location_id']) ? $user['Kds_user']['location_id'] : '';

        $ordersConditions = array(
            'DATE(Order_orderhistory.created)' => $today,
            'Order_orderhistory.kds_status' => array(4,5,6),
            'Order_orderhistory.status !=' => 0,
            'Order_orderhistory.kds' => $stations_id
        );
        if (!empty($location_id)) {
            $ordersConditions['Order_orderhistory.store_id'] = $location_id;
        }

        // Fetch all orders for today for these stations
        $orders_raw = $this->Order_orderhistory->find('all', [
            'conditions' => $ordersConditions,
            'fields' => array(
                'Order_orderhistory.*',
                'Order_table.waiter_name',
                'GROUP_CONCAT(DISTINCT Order_orderhistory.kot_number ORDER BY Order_orderhistory.kot_number SEPARATOR ", ") AS kot_numbers'
            ),
            'joins' => array(
                array(
                    'table' => 'order_tables',
                    'alias' => 'Order_table',
                    'type' => 'LEFT',
                    'conditions' => array('Order_table.reference_id = Order_orderhistory.reference_id')
                )
            ),
            'group' => array('Order_orderhistory.reference_id', 'Order_orderhistory.group_id'),
            'order' => ['Order_orderhistory.id DESC'],  //  Added order by
            'recursive' => -1
        ]);
    
        if (empty($orders_raw)) {
            return json_encode(array('status' => false, 'message' => "No Order found"));
        }
    
        // Collect all reference_ids
        $reference_ids = array();
        foreach ($orders_raw as $o) {
            $reference_ids[] = $o['Order_orderhistory']['reference_id'];
        }
    
        $orderItemsConditions = array(
            'Order_orderhistory.kds_status' => array(4,5,6),
            'Order_orderhistory.status !=' => 0,
            'Order_orderhistory.reference_id' => $reference_ids,
            'Order_orderhistory.kds' => $stations_id
        );
        if (!empty($location_id)) {
            $orderItemsConditions['Order_orderhistory.store_id'] = $location_id;
        }

        // Fetch all order items for these references and stations
        $order_items_raw = $this->Order_orderhistory->find('all', array(
            'conditions' => $orderItemsConditions,
            'fields' => array(
                'Order_orderhistory.*',
                'Catalog_product.preparation_time'
            ),
            'joins' => array(
                array(
                    'table' => 'catalog_product',
                    'alias' => 'Catalog_product',
                    'type' => 'LEFT',
                    'conditions' => array('Catalog_product.id = Order_orderhistory.product_id')
                )
            ),
            'recursive' => -1
        ));
    
        // Fetch all modifiers in bulk
        $order_items_ids = array();
        foreach ($order_items_raw as $oi) {
            $order_items_ids[] = $oi['Order_orderhistory']['id'];
        }
    
        $modifiers_raw = array();
        if (!empty($order_items_ids)) {
            $modifiers_raw = $this->Order_modifier->find('all', array(
                'conditions' => array(
                    'Order_modifier.orderhistory_id' => $order_items_ids,
                    'Order_modifier.status !=' => 0
                ),
                'recursive' => -1
            ));
        }
    
        // Map modifiers by orderhistory_id
        $modifiers_map = array();
        foreach ($modifiers_raw as $mod) {
            $m = $mod['Order_modifier'];
            $modifiers_map[$m['orderhistory_id']][] = array(
                'modifier_group_id' => $m['modifier_group_id'],
                'modifier_group_name' => $m['modifier_group_name'],
                'modifier_id' => $m['modifier_id'],
                'modifier_name' => $m['modifier_name'],
                'modifier_price' => $m['modifier_price']
            );
        }
    
        // Organize items under stations and references
        $orders = array();
        foreach ($orders_raw as $order) {
            $order_data = $order['Order_orderhistory'];
            $reference_id = $order_data['reference_id'];
            $group_id = $order_data['group_id'];
            $kotNumbers = $order[0]['kot_numbers'];  // from the custom SQL field

            $stations = array();
            foreach ($stations_map as $station_id => $station_data) {
                $items = array();
                foreach ($order_items_raw as $oi) {
                    $oi_data = $oi['Order_orderhistory'];
                    if ($oi_data['reference_id'] == $reference_id && $oi_data['kds'] == $station_id && $oi_data['group_id'] == $group_id) {
                        $items[] = array(
                            'order_history_id' => $oi_data['id'],
                            'item_id' => $oi_data['product_id'],
                            'quantity' => $oi_data['quantity'],
                            'remarks' => $oi_data['remarks'],
                            'item_name' => $oi_data['product_name'],
                            'kds_status' => $oi_data['kds_status'],
                            'prep_time' => strval(isset($oi['Catalog_product']['preparation_time']) ? $oi['Catalog_product']['preparation_time'] : '0'),
                            'modifiers' => isset($modifiers_map[$oi_data['id']]) ? $modifiers_map[$oi_data['id']] : array()
                        );
                    }
                }
                $station_data['items'] = $items;
                if($station_data['items']){
                    $stations[] = $station_data;
                }
            }
            
            if($order_data['table_number']){
                $table_number=$order_data['table_number'];
            }else{
                $table_number='Quick Order';
            }
    
            $orders[] = array(
                'kds_ref_id' => $reference_id,
                'kds_group_id' => $group_id,
                'table_no' => $table_number,
                'kot_number' => $kotNumbers,
                'created' => $order_data['created'],
                'waiter_name' => isset($order['Order_table']['waiter_name']) ? $order['Order_table']['waiter_name'] : '',
                'kds_status' => $order_data['kds_status'],
                'stations' => $stations
            );
        }
    
        return json_encode(array('status' => true, 'orders' => $orders), JSON_UNESCAPED_UNICODE);
    }

    public function status_update()
    {
        $this->autoRender = false;
    
        $kds_user_id = $_REQUEST['kds_user_id'] ?? '';
        $reference_id = $_REQUEST['reference_id'] ?? '';
        $order_id = $_REQUEST['order_history_id'] ?? '';
        $status = $_REQUEST['status'] ?? '';
    
        if (empty($kds_user_id)) {
            return json_encode(['status' => false, 'message' => "KDS User Id Missing"]);
        }
        if (empty($reference_id)) {
            return json_encode(['status' => false, 'message' => "Reference Id Missing"]);
        }
        if (empty($order_id)) {
            return json_encode(['status' => false, 'message' => "Order ID Missing"]);
        }
        if (!isset($status)) {
            return json_encode(['status' => false, 'message' => "Status Missing"]);
        }
    
        $user = $this->Kds_user->find('first', [
            'conditions' => [
                'Kds_user.id' => $kds_user_id,
                'Kds_user.status' => 1
            ],
            'recursive' => 1
        ]);
        if (empty($user)) {
            return json_encode(['status' => false, 'message' => "KDS user not found"]);
        }
    
        $orders = $this->Order_orderhistory->find('first', [
            'conditions' => [
                'id' => $order_id,
                'reference_id' => $reference_id
            ]
        ]);
        if (empty($orders)) {
            return json_encode(['status' => false, 'message' => "Order not found"]);
        }
        
        if ($status == 0) {
            $minstatus = [1];
            $cond = [
                'id' => $order_id,
                'reference_id' => $reference_id,
                'kds_status IN' => $minstatus
            ];
        } elseif ($status == 1) {
            $minstatus = [2, 0, 7];
            $cond = [
                'id' => $order_id,
                'reference_id' => $reference_id,
                'kds_status IN' => $minstatus
            ];
        } elseif ($status == 2) {
            $minstatus = [0, 7];
            $cond = [
                'id' => $order_id,
                'reference_id' => $reference_id,
                'kds_status IN' => $minstatus
            ];
        } elseif ($status == 3) {
            $minstatus = [1,7];
            $cond = [
                'id' => $order_id,
                'reference_id' => $reference_id,
                'kds_status IN' => $minstatus
            ];
        } elseif ($status == 4) {
            $minstatus = [3,7];
            $cond = [
                'id' => $order_id,
                'reference_id' => $reference_id,
                'kds_status IN' => $minstatus
            ];
        } elseif ($status == 5) {
            $minstatus = [0,7,3];
            $cond = [
                'id' => $order_id,
                'reference_id' => $reference_id,
                'kds_status IN' => $minstatus
            ];
        } elseif ($status == 6) {
            //  Special case: all items for reference_id must have kds_status = 4
            $total = $this->Order_orderhistory->find('count', [
                'conditions' => ['reference_id' => $reference_id]
            ]);
        
            $allWith4 = $this->Order_orderhistory->find('count', [
                'conditions' => [
                    'reference_id' => $reference_id,
                    'kds_status' => 4
                ]
            ]);
        
            if ($total == 0 || $total != $allWith4) {
                return json_encode(['status' => false, 'message' => "Not all items are ready for this update"]);
            }
        
            // all items are 4, so you can allow the update
            $cond = [
                'reference_id' => $reference_id
            ];
        } elseif ($status == 7) {
            $minstatus = [4, 5];
            $cond = [
                'id' => $order_id,
                'reference_id' => $reference_id,
                'kds_status IN' => $minstatus
            ];
        }
        
        $statuschk = $this->Order_orderhistory->find('first', [
            'conditions' => $cond
        ]);
        
        if (empty($statuschk)) {
            return json_encode(['status' => false, 'message' => "Order not available for this update"]);
        }

        $data['Kds_track'][] = [
            'order_history_id'   => $order_id,
            'order_reference_id' => $reference_id,
            'kds_group_id'       => $orders['Order_orderhistory']['group_id'],
            'kds_user_id'        => $kds_user_id,
            'kds_station_id'     => $orders['Order_orderhistory']['kds'],
            'kds_username'       => $user['Kds_user']['username'],
            'product_id'         => $orders['Order_orderhistory']['product_id'],
            'product_name'       => $orders['Order_orderhistory']['product_name'],
            'role'               => $user['Kds_user']['role'],
            'table_no'           => $orders['Order_orderhistory']['table_number'],
            'cashier_id'         => $orders['Order_orderhistory']['cashier_id'],
            'location_id'        => $user['Kds_user']['location_id'],
            'location_name'      => $user['Kds_user']['location_name'],
            'status'             => $status
        ];
        $track_id = null;
        if (empty($data['Kds_track'])) {
            return json_encode(['status' => false, 'message' => "data not found"]);
        }
        
        $this->Kds_track->create();
        $this->Kds_track->saveAll($data['Kds_track']);
        $track_id = $this->Kds_track->id; // last inserted id
        
        // Update Order_orderhistory
        $this->Order_orderhistory->updateAll(
            [
                'Order_orderhistory.kds_track_id' => "'$track_id'",
                'Order_orderhistory.kds_status'   => "'$status'"
            ],
            ['Order_orderhistory.id' => $order_id]
        );
        
        return json_encode([
            'status'   => true,
            'message'  => "KDS Status Updated",
            'track_id' => $track_id
        ]);

    }
    
    public function status_update_all()
    {
        $this->autoRender = false;
    
        $kds_user_id = $_REQUEST['kds_user_id'] ?? '';
        $reference_id = $_REQUEST['reference_id'] ?? '';
        $group_id = $_REQUEST['group_id'] ?? '';
        $status = $_REQUEST['status'] ?? '';
        
        if (empty($kds_user_id)) {
            return json_encode(['status' => false, 'message' => "KDS User Id Missing"]);
        }
        if (empty($reference_id)) {
            return json_encode(['status' => false, 'message' => "Reference Id Missing"]);
        }
        if (empty($group_id)) {
            return json_encode(['status' => false, 'message' => "Group ID Missing"]);
        }
        if (!isset($status)) {
            return json_encode(['status' => false, 'message' => "Status Missing"]);
        }
    
        $user = $this->Kds_user->find('first', [
            'conditions' => [
                'Kds_user.id' => $kds_user_id,
                'Kds_user.status' => 1
            ],
            'recursive' => 1
        ]);
        if (empty($user)) {
            return json_encode(['status' => false, 'message' => "KDS user not found"]);
        }
    
        $orders = $this->Order_orderhistory->find('all', [
            'conditions' => [
                'reference_id' => $reference_id,
                'group_id' => $group_id
            ]
        ]);
        
        if (empty($orders)) {
            return json_encode(['status' => false, 'message' => "Order not found"]);
        }
        
        if ($status == 0) {
            $minstatus = [1];
            $cond = [
                'group_id' => $group_id,
                'reference_id' => $reference_id,
                'kds_status IN' => $minstatus
            ];
        } elseif ($status == 1) {
            $minstatus = [2, 0, 7];
            $cond = [
                'group_id' => $group_id,
                'reference_id' => $reference_id,
                'kds_status IN' => $minstatus
            ];
        } elseif ($status == 2) {
            $minstatus = [0, 7];
            $cond = [
                'group_id' => $group_id,
                'reference_id' => $reference_id,
                'kds_status IN' => $minstatus
            ];
        } elseif ($status == 3) {
            $minstatus = [1,7];
            $cond = [
                'group_id' => $group_id,
                'reference_id' => $reference_id,
                'kds_status IN' => $minstatus
            ];
        } elseif ($status == 4) {
            $minstatus = [3];
            $cond = [
                'group_id' => $group_id,
                'reference_id' => $reference_id,
                'kds_status IN' => $minstatus
            ];
        } elseif ($status == 5) {
            $minstatus = [0,7,3];
            $cond = [
                'group_id' => $group_id,
                'reference_id' => $reference_id,
                'kds_status IN' => $minstatus
            ];
        } elseif ($status == 6) {
            // âœ… Special case: all items for reference_id must have kds_status = 4
            $total = $this->Order_orderhistory->find('count', [
                'conditions' => ['reference_id' => $reference_id,'group_id' => $group_id]
            ]);
        
            $allWith4 = $this->Order_orderhistory->find('count', [
                'conditions' => [
                    'reference_id' => $reference_id,
                    'group_id' => $group_id,
                    'kds_status' => 4
                ]
            ]);
        
            if ($total == 0 || $total != $allWith4) {
                return json_encode(['status' => false, 'message' => "Not all items are ready for this update"]);
            }
        
            // âœ… all items are 4, so you can allow the update
            $cond = [
                'group_id' => $group_id,'reference_id' => $reference_id
            ];
        } elseif ($status == 7) {
            $minstatus = [6, 4, 5];
            $cond = [
                'group_id' => $group_id,
                'reference_id' => $reference_id,
                'kds_status IN' => $minstatus
            ];
        }
        
        $statuschk = $this->Order_orderhistory->find('first', [
            'conditions' => $cond
        ]);
        
        if (empty($statuschk)) {
            return json_encode(['status' => false, 'message' => "Order not available for this update"]);
        }
        
        $kds_track_data = array();

        foreach ($orders as $order) {
            $order_id = $order['Order_orderhistory']['id'];
            $kds_track_data = array(
                'order_history_id'   => $order_id,
                'order_reference_id' => $reference_id,
                'kds_group_id'       => $order['Order_orderhistory']['group_id'],
                'kds_user_id'        => $kds_user_id,
                'kds_station_id'     => $order['Order_orderhistory']['kds'],
                'kds_username'       => $user['Kds_user']['username'],
                'product_id'         => $order['Order_orderhistory']['product_id'],
                'product_name'       => $order['Order_orderhistory']['product_name'],
                'role'               => $user['Kds_user']['role'],
                'table_no'           => $order['Order_orderhistory']['table_number'],
                'cashier_id'         => $order['Order_orderhistory']['cashier_id'],
                'location_id'        => $user['Kds_user']['location_id'],
                'location_name'      => $user['Kds_user']['location_name'],
                'status'             => $status
            );
        
            // Save KDS track individually
            $this->Kds_track->create();
            $this->Kds_track->save($kds_track_data);
        
            $track_id = $this->Kds_track->id; // Ã¢Å“â€¦ ID for this specific order
        
            // Update the corresponding order
            $this->Order_orderhistory->updateAll(
                [
                    'Order_orderhistory.kds_track_id' => $track_id,
                    'Order_orderhistory.kds_status'   => "'$status'"
                ],
                ['Order_orderhistory.id' => $order_id]
            );
        }
        
        $is_print = false;
        if($status == 4){
            $laststatuschk = $this->Order_orderhistory->find('first', [
                'conditions' => [
                    'Order_orderhistory.group_id'      => $group_id,
                    'Order_orderhistory.reference_id'  => $reference_id,
                    'Order_orderhistory.kds_status <'  => 4   // ✅correct operator
                ]
            ]);
            
            if (empty($laststatuschk)) {
                $is_print = true;
            }
        }
        
        return json_encode([
            'status'   => true,
            'message'  => "KDS Status Updated",
            'track_id' => $track_id,
            'is_print' => $is_print
        ]);
        
        // return json_encode(['status' => true, 'message' => "KDS Status Updated", 'track_id' => $track_id]);
    }

    public function print_expo()
    {
        $kds_user_id = $_REQUEST['id'];
        $reference_id = $_REQUEST['reference_id'];
        $group_id = $_REQUEST['group_id'];
        
        // ✅ Read JSON body safely
        $rawBody = file_get_contents('php://input');
        $jsonData = json_decode($rawBody, true);
          // ✅ Accept both form-data and raw JSON
        if (isset($_REQUEST['data'])) {
            $data = $_REQUEST['data'];
            if (is_string($data)) $data = json_decode($data, true);
        } elseif (isset($jsonData['data'])) {
            $data = $jsonData['data'];
        } else {
            return json_encode(['status' => false, 'message' => "Data not found"]);
        }
        
         // ✅ Extract order_history_ids correctly
        $oi_ids = [];
        foreach ($data['items'] as $item) {
            if (!empty($item['order_history_id'])) {
                $oi_ids[] = $item['order_history_id'];
            }
        }
        if (!empty($oi_ids)) {
            $oi_ids_str = implode(',', $oi_ids);
        }
        
        $response = array();
        if($kds_user_id==''){
            $response =array(
                'status'=> false,
                'message'=> "User Id Missing",
            );
            return json_encode($response);
        }
        if($reference_id==''){
            $response =array(
                'status'=> false,
                'message'=> "Reference Id Missing",
            );
            return json_encode($response);
        }
        if($group_id==''){
            $response =array(
                'status'=> false,
                'message'=> "Group Id Missing",
            );
            return json_encode($response);
        }
        $cashier_name='';
        $user = $this->Kds_user->find('first', [
            'conditions' => [
                'Kds_user.id' => $kds_user_id,
                'Kds_user.status' => 1
            ],
            'recursive' => 1
        ]);
        if (empty($user)) {
            return json_encode(['status' => false, 'message' => "KDS user not found"]);
        }
        
        $cashier_name =$user['Kds_user']['username'];
        
        $orderitems=[];
        $orderss = $this->Order_orderhistory->query("SELECT *,(SELECT name FROM users WHERE id = ( SELECT cashier_id FROM order_orderhistories WHERE reference_id = '$reference_id'  AND status != 0 ORDER BY id ASC LIMIT 1) LIMIT 1) AS first_cashier_name FROM `order_orderhistories` WHERE reference_id='$reference_id' and group_id='$group_id' and kds_status=6 and id in($oi_ids_str)");
        if(!empty($orderss))
        {
            foreach($orderss as $orders){
                
                $product_id=$orders['order_orderhistories']['product_id'];
                $order_id=$orders['order_orderhistories']['id'];
                $cashier_id=$orders['order_orderhistories']['cashier_id'];
                $order_punch_by = $orders[0]['first_cashier_name'];

                $selected_modifiers=[];
                $orders_modifiers = $this->Order_modifier->query("SELECT * FROM `order_modifier` WHERE reference_id='$reference_id' and product_id='$product_id' and orderhistory_id='$order_id' and status!=0");
                foreach($orders_modifiers as $orders_modifier){
                    
                    $modifiers=$orders_modifier['order_modifier'];
                    $modifier_group_id=$modifiers['modifier_group_id'];
                    $modifier_group_name=$modifiers['modifier_group_name'];
                    $modifier_id=$modifiers['modifier_id'];
                    $modifier_name=$modifiers['modifier_name'];
                    $modifier_price=$modifiers['modifier_price'];
                    
                    $selected_modifiers[]=array(
                        'modifier_group_id' => $modifier_group_id,
                        'modifier_group_name' => $modifier_group_name,
                        'modifier_id' => $modifier_id,
                        'modifier_name' => $modifier_name,
                        'modifier_price' => $modifier_price,
                        );
                    
                }
                
                if(!isset($orders['order_orderhistories']['remarks'])){
                    $orders['order_orderhistories']['remarks']='';
                }
                $orderitems[]=array(
                    'id' => $orders['order_orderhistories']['id'],
                    'reference_id' =>  $orders['order_orderhistories']['reference_id'],
                    'cashier_id' =>  $orders['order_orderhistories']['cashier_id'],
                    'group_id' =>  $orders['order_orderhistories']['group_id'],
                    'category' =>  $orders['order_orderhistories']['category'],
                    'category_name' =>  $orders['order_orderhistories']['category_name'],
                    'kot_number' =>  $orders['order_orderhistories']['kot_number'],
                    'kds' =>  $orders['order_orderhistories']['kds'],
                    'product_id' =>  $orders['order_orderhistories']['product_id'],
                    'product_name' =>  $orders['order_orderhistories']['product_name'],
                    'quantity' =>  $orders['order_orderhistories']['quantity'],
                    'price' =>  $orders['order_orderhistories']['price'],
                    'total_tax' =>  $orders['order_orderhistories']['total_tax'],
                    'total_price' =>  $orders['order_orderhistories']['total_price'],
                    'discount' =>  $orders['order_orderhistories']['discount'],
                    'table_number' =>  $orders['order_orderhistories']['table_number'],
                    'remarks' =>  $orders['order_orderhistories']['remarks'],
                    'selected_modifiers' => $selected_modifiers,
                    'status' =>  $orders['order_orderhistories']['status'],
                    'created' =>  $orders['order_orderhistories']['created']
                );
            }
        }
        $finalamount=0;
        $transactionitems=[];
        $transactions = $this->Order_transaction->find('all', array('conditions'=>array('reference_id'=>$reference_id,'status !='=>0)));
        if(!empty($transactions))
        {
            foreach($transactions as $orders){
                if(!$orders['Order_transaction']['remarks']){
                    $orders['Order_transaction']['remarks']='';
                }
                $finalamount=$orders['Order_transaction']['amount'];
                $cashierid=$orders['Order_transaction']['cashier_id'];
                $reference_id=$orders['Order_transaction']['reference_id'];
                
                $discper = $this->Order_tax->query("SELECT discount_percentage FROM `order_tax` WHERE reference_id='$reference_id' and status=2 and discount_percentage!=0 limit 1");
                if(!empty($discper))
                {
                    $discount_percentage=number_format($discper[0]['order_tax']['discount_percentage'],2);
                }
                if(!isset($discount_percentage)){
                    $discount_percentage="0";
                }
                
                // 🔹 Get KOT numbers
                $kotQuery = $this->Order_orderhistory->query("
                    SELECT GROUP_CONCAT(DISTINCT(kot_number)) AS kot_numbers 
                    FROM order_orderhistories 
                    WHERE reference_id = '$reference_id' and status!=0
                ");
                $kot_numbers = '';
                if (!empty($kotQuery) && isset($kotQuery[0][0]['kot_numbers'])) {
                    $kot_numbers = $kotQuery[0][0]['kot_numbers'];
                }
                
                $transactionitems[]=array(
                    'id' => $orders['Order_transaction']['id'],
                    'reference_id' =>  $orders['Order_transaction']['reference_id'],
                    'orderno' =>  $orders['Order_transaction']['orderno'],
                    'device_id' =>  $orders['Order_transaction']['device_id'],
                    'cashier_id' =>  $orders['Order_transaction']['cashier_id'],
                    'table_number' =>  $orders['Order_transaction']['table_number'],
                    'price' =>  $orders['Order_transaction']['price'],
                    'total_tax' =>  $orders['Order_transaction']['total_tax'],
                    'servicecharge' =>  $orders['Order_transaction']['servicecharge'],
                    'discount' =>  $orders['Order_transaction']['discount'],
                    'discount_percentage' =>  $discount_percentage,
                    'discount_type' =>  $orders['Order_transaction']['discount_type'],
                    'discount_type_value' =>  $orders['Order_transaction']['discount_type_value'],
                    'delivery_charge' =>  $orders['Order_transaction']['delivery_charge'],
                    'packing_charge' =>  $orders['Order_transaction']['packing_charge'],
                    'amount' =>  $orders['Order_transaction']['amount'],
                    'round' =>  $orders['Order_transaction']['round'],
                    'name' =>  $orders['Order_transaction']['name'],
                    'phone' =>  $orders['Order_transaction']['phone'],
                    'address' =>  $orders['Order_transaction']['address'],
                    'remarks' =>  $orders['Order_transaction']['remarks'],
                    'status' =>  $orders['Order_transaction']['status'],
                    'tokenno' =>  $orders['Order_transaction']['tokenno'],
                    'createtime' =>  $orders['Order_transaction']['createtime'],
                    'created' =>  $orders['Order_transaction']['created'],
                    'kot_number' =>  $kot_numbers
                );
            }
        }
        $paymentitems=[];
        $payments = $this->Order_payment->find('all', array('conditions'=>array('reference_id'=>$reference_id,'status !='=>0)));
        if(!empty($payments))
        {
            foreach($payments as $orders){
                if(!isset($orders['Order_payment']['remarks'])){
                    $orders['Order_payment']['remarks']='';
                }
                $paymentitems[]=array(
                    'id' => $orders['Order_payment']['id'],
                    'reference_id' =>  $orders['Order_payment']['reference_id'],
                    'cashier_id' =>  $orders['Order_payment']['cashier_id'],
                    'type' =>  $orders['Order_payment']['type'],
                    'type_value' =>  $orders['Order_payment']['type_value'],
                    'amount' =>  $orders['Order_payment']['amount'],
                    'status' =>  $orders['Order_payment']['status'],
                    'remarks' =>  $orders['Order_payment']['remarks'],
                    'created' =>  $orders['Order_payment']['created']
                );
            }
        }
        $taxitems=[];
        $taxes = $this->Order_tax->query("SELECT tax_id,tax_name,tax_percentage,sum(tax_value) as tax_value FROM `order_tax` WHERE reference_id='$reference_id' and status!=0 GROUP by tax_id");
        if(!empty($taxes))
        {
            foreach($taxes as $tax){
                // print_r($tax);exit;
                
                $taxitems[]=array(
                    'tax_id' => $tax['order_tax']['tax_id'],
                    'tax_name' =>  $tax['order_tax']['tax_name'],
                    'tax_percentage' =>  $tax['order_tax']['tax_percentage'],
                    'tax_value' =>  "" .round($tax[0]['tax_value'],2)
                );
            }
        }
        $finalpayment=0;
        $allpayments=$this->Order_payment->query("SELECT sum(amount) as pay_amount FROM `order_payments` WHERE reference_id='$reference_id'");
        if($allpayments){
            $finalpayment=$allpayments[0][0]['pay_amount'];
        }
        $amount_diff=round(($finalamount-$finalpayment),2);
        
        $store_id='';
        $stores = $this->User->find('first', [
            'conditions' => [
                'User.id' => $cashier_id,
                'User.role' => 'cashier',
                'User.status' => 1
            ],
            'recursive' => 1
        ]);
        if (empty($stores)) {
            return json_encode(['status' => false, 'message' => "Store not found"]);
        }
        $store_id=$stores['User']['location_id'];
        
        $printeritems=[];
        $printerss = $this->Pos_printer->query("SELECT * FROM `pos_printers` WHERE status=1 and store_id='$store_id'");
        if(!empty($printerss))
        {
            foreach($printerss as $printers){
                // print_r($tax);exit;
                
                $printeritems[]=array(
                    'id' => $printers['pos_printers']['id'],
                    'store_id' =>  $printers['pos_printers']['store_id'],
                    'name' =>  $printers['pos_printers']['name'],
                    'kds' =>  $printers['pos_printers']['kds'],
                    'ip' =>  $printers['pos_printers']['ip'],
                    'status' =>  $printers['pos_printers']['status']
                );
            }
        }
        
        $response= array('status'=> true,
                'message'=> "success",
                'cashier_name'=>$cashier_name,
                'waiter_name' =>$order_punch_by,
                'orders'=>$orderitems,
                'transactions'=>$transactionitems,
                'payments'=>$paymentitems,
                'taxes'=>$taxitems,
                'printers'=>$printeritems,
                'remaining_amount'=>number_format($amount_diff, 2, '.', '')
            );
        return json_encode($response);
    }
    
    public function status_update_item_ids()
    {
        $this->autoRender = false;
        $this->response->type('json');
    
        $kds_user_id  = $_REQUEST['kds_user_id'] ?? '';
        $reference_id = $_REQUEST['reference_id'] ?? '';
        $group_id     = $_REQUEST['group_id'] ?? '';
        $status       = $_REQUEST['status'] ?? '';
    
        // ✅ Read JSON body safely
        $rawBody = file_get_contents('php://input');
        $jsonData = json_decode($rawBody, true);
    
        // ✅ Accept both form-data and raw JSON
        if (isset($_REQUEST['data'])) {
            $data = $_REQUEST['data'];
            if (is_string($data)) $data = json_decode($data, true);
        } elseif (isset($jsonData['data'])) {
            $data = $jsonData['data'];
        } else {
            return json_encode(['status' => false, 'message' => "Data not found"]);
        }
    
        if (empty($kds_user_id))  return json_encode(['status' => false, 'message' => "KDS User Id Missing"]);
        if (empty($reference_id)) return json_encode(['status' => false, 'message' => "Reference Id Missing"]);
        if (empty($group_id))     return json_encode(['status' => false, 'message' => "Group ID Missing"]);
        if ($status === '')       return json_encode(['status' => false, 'message' => "Status Missing"]);
    
        // ✅ Validate KDS user
        $user = $this->Kds_user->find('first', [
            'conditions' => ['Kds_user.id' => $kds_user_id, 'Kds_user.status' => 1],
            'recursive'  => 1
        ]);
        if (empty($user)) {
            return json_encode(['status' => false, 'message' => "KDS user not found"]);
        }
    
        // ✅ Extract order_history_ids correctly
        $oi_ids = [];
        foreach ($data['items'] as $item) {
            if (!empty($item['order_history_id'])) {
                $oi_ids[] = $item['order_history_id'];
            }
        }
  
        if (empty($oi_ids)) {
            return json_encode(['status' => false, 'message' => "No valid order IDs found"]);
        }
    
        // ✅  orders  check
        $orderscheck = $this->Order_orderhistory->find('all', [
            'conditions' => ['Order_orderhistory.id IN' => $oi_ids,'Order_orderhistory.kds_status !='=>4]
        ]);
        if (!empty($orderscheck)) {
            return json_encode(['status' => false, 'message' => "Order not valid for this update"]);
        }
    
        if ($status != 6) {
            return json_encode(['status' => false, 'message' => "Order Status not available for this update"]);
        }
        
        $orders = $this->Order_orderhistory->find('all', [
            'conditions' => ['Order_orderhistory.id IN' => $oi_ids,'Order_orderhistory.kds_status'=>4]
        ]);
        foreach ($orders as $order) {
            $order_id = $order['Order_orderhistory']['id'];
        
            $orderchecks = $this->Order_orderhistory->find('first', [
                'conditions' => ['Order_orderhistory.id' => $order_id,'Order_orderhistory.kds_status' => 4]
            ]);
            if (!empty($orderchecks)) {
                    
                $kds_track_data = [
                    'order_history_id'   => $order_id,
                    'order_reference_id' => $reference_id,
                    'kds_group_id'       => $order['Order_orderhistory']['group_id'],
                    'kds_user_id'        => $kds_user_id,
                    'kds_station_id'     => $order['Order_orderhistory']['kds'],
                    'kds_username'       => $user['Kds_user']['username'],
                    'product_id'         => $order['Order_orderhistory']['product_id'],
                    'product_name'       => $order['Order_orderhistory']['product_name'],
                    'role'               => $user['Kds_user']['role'],
                    'table_no'           => $order['Order_orderhistory']['table_number'],
                    'cashier_id'         => $order['Order_orderhistory']['cashier_id'],
                    'location_id'        => $user['Kds_user']['location_id'],
                    'location_name'      => $user['Kds_user']['location_name'],
                    'status'             => $status
                ];
        
                $this->Kds_track->create();
                $this->Kds_track->save($kds_track_data);
                $track_id = $this->Kds_track->id;
        
                $this->Order_orderhistory->updateAll(
                    [
                        'Order_orderhistory.kds_track_id' => $track_id,
                        'Order_orderhistory.kds_status'   => "'$status'"
                    ],
                    ['Order_orderhistory.id' => $order_id]
                );
            }
        }
    
        return json_encode([
            'status'   => true,
            'message'  => "KDS Status Updated",
            'is_print' => true
        ]);
    }

   
}

