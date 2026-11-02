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
 * CakePHP DsrController
 * @author Aether2
 */
class OutletController extends AppController {
    public $uses = array();
    public $components = array('Paginator');
    
    public function beforeFilter()
    {
        parent::beforeFilter();
        $this->layout = "dashboard";
        $this->response->disableCache();

        $this->Auth->allow();

        if ($this->Auth->user()) {

            $storeId = $this->Session->read('Auth.Location.id');
            $storeIdsArr = [$storeId];

            $this->loadModel('Inventory_semifinished');
            $this->loadModel('Inventory_sku');

            $lowStockSemifinished = $this->Inventory_semifinished->getLowStockData($storeId);
            $lowStockSkus = $this->Inventory_sku->getLowStockSkus($storeIdsArr);

            $semifinishedSkuIds = [];
            if (!empty($lowStockSemifinished)) {
                $semifinishedSkuIds = array_map(function($row) {
                    return (int)$row['s']['id'];
                }, $lowStockSemifinished);
            }

            $skuIds = [];
            if (!empty($lowStockSkus)) {
                $skuIds = array_map(function($row) {
                    return (int)$row['l']['sku_id'];
                }, $lowStockSkus);
            }

            $limit = 4;

            $semifinishedMessage = $this->buildLowStockList($lowStockSemifinished, $limit, 'semifinished');
            $skuMessage = $this->buildLowStockList($lowStockSkus, $limit, 'sku');
            $controller = 'outlet';

            $this->set(compact('semifinishedMessage', 'skuMessage', 'semifinishedSkuIds', 'skuIds', 'storeId', 'controller'));
        }
    }
    
     
    private function buildLowStockList($data, $limit = 4, $type = 'semifinished')
    {
        if (empty($data)) {
            return '';
        }

        $count = count($data);
        $html  = "<ul style='margin:0; padding-left:15px;'>";

        for ($i = 0; $i < min($limit, $count); $i++) {
            $item = $data[$i];
            
            // Handle different data structures
            if ($type === 'semifinished') {
                $name = h($item['s']['name']);
                $requiredStock = (float)$item['s']['required_stock'];
                $availableStock = (float)$item['sl']['available_stock'];
            } else {
                $name = h($item['s']['name']);
                $requiredStock = (float)$item['s']['required_stock'];
                $availableStock = (float)$item['l']['last_balance'];
            }

            $html .= "
                <li>
                    <span style='color:red; font-weight:bold;'>{$name}</span>
                    <span> / Required: {$requiredStock}</span>
                    <span> / Available: {$availableStock}</span>
                </li>
            ";
        }

        $html .= "</ul>";

        if ($count > $limit) {
            $html .= "<div style='font-weight:bold; margin-top:4px;'>... more</div>";
        }

        return $html;
    }

    public function markAlertViewed() {
        $this->autoRender = false;
        $this->layout = false;
        if ($this->request->is('post')) {
            $type = $this->request->data('type');
            $skuIds = $this->request->data('sku_ids');
            $storeId = $this->request->data('store_id');

            if (empty($skuIds) || empty($storeId)) {
                echo json_encode(['success' => false, 'message' => 'Missing parameters']);
                return;
            }

            try {
                if ($type === 'semifinished') {
                    $this->loadModel('Inventory_semifinished');
                    $this->Inventory_semifinished->markAlertsAsViewed($storeId, $skuIds);
                } elseif ($type === 'sku') {
                    $this->loadModel('Inventory_sku');
                    $this->Inventory_sku->markAlertsAsViewed([$storeId], $skuIds);
                }

                echo json_encode(['success' => true, 'message' => 'Alerts marked as viewed']);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid request']);
        }
    }
    
    public function logout() {
	    return $this->redirect($this->Auth->logout());
    }
    
    public function index() {
        $this->set('title', 'Outlet Login Page');
        $this->Flash->success(__('Login'));
    }

    public function login() {
        $this->set('title', 'Outlet Login Page');
    
        if ($this->Session->check('Auth.User')) {
            return $this->redirect(array('controller' => 'Outlet', 'action' => 'indent'));
        }
    
        if ($this->request->is('post')) {
            if ($this->Auth->login()) {
                $location = $this->Auth->user(); // fetched from `locations` table
                $this->Session->write('Auth.Location', $location); // Optional: store separately
                return $this->redirect($this->Auth->redirectUrl());
            }
            $this->Flash->error(__('Invalid outlet code or password, try again'));
        }
    }
    
    public function indent()
    {
        if (!$this->Session->check('Auth.Location.id')) {
            return $this->redirect(['controller' => 'Dashboard', 'action' => 'login']);
        }
    
        $this->layout = "users";
    
        // Default date range
        $startDate = date('m/d/Y', strtotime('-6 months'));
        $endDate = date('m/d/Y');
        
        if ($this->request->is(['post', 'put'])) 
        {
			$daterange= $this->request->data['daterange'];
            $dates = explode(" - ", $daterange);
            $start_date = trim($dates[0]);
            $startDate = date('m/d/Y', strtotime($start_date));
            $end_date = trim($dates[1]);
            $endDate = date('m/d/Y', strtotime($end_date));
            $startdate= date('Y-m-d', strtotime($start_date));
            $enddate= date('Y-m-d', strtotime($end_date));
        }else{
            $startdate= date('Y-m-d', strtotime($startDate));
            $enddate= date('Y-m-d', strtotime($endDate));
        }   
    
        // Auth info
        $locationId = $this->Session->read('Auth.Location.id');
        // Base condition
        $conditions = "created BETWEEN '$startdate 00:00:01' AND '$enddate 23:59:59'";
        if ($userRole !== 'admin') {
            $conditions .= " AND location_id = '$locationId'";
        }
    
        // Indents by status
        $submittedIndents = $this->Inventory_indent->query("
            SELECT * FROM inventory_indent 
            WHERE $conditions and status IN (0,1)
            ORDER BY id DESC
        ");
        $approvedIndents = $this->Inventory_indent->query("
            SELECT * FROM inventory_indent 
            WHERE $conditions AND status = 2 
            ORDER BY id DESC
        ");
        $processedIndents = $this->Inventory_indent->query("
            SELECT * FROM inventory_indent 
            WHERE $conditions AND status = 3 
            ORDER BY id DESC
        ");
        $yourStartDateValue=$startDate . ' - ' . $endDate;
        // print_r($yourStartDateValue);exit;
        $this->set('startdate', $yourStartDateValue);
                
        $newconditions='';
        if ($userRole !== 'admin') {
            $newconditions .= " location_id = '$locationId'";
        }
        $submitted_indents = $this->Inventory_indent->query("
            SELECT count(id) as total FROM inventory_indent 
            WHERE $newconditions and status IN (1)
        ");
        $submitted_indent=$submitted_indents[0][0]['total'];
        
        $approved_indents = $this->Inventory_indent->query("
            SELECT count(id) as total FROM inventory_indent 
            WHERE $newconditions AND status = 2 
        ");
        $approved_indent=$approved_indents[0][0]['total'];
        
        $processed_indents = $this->Inventory_indent->query("
            SELECT count(id) as total FROM inventory_indent 
            WHERE $newconditions AND status = 3 
        ");
        $processed_indent=$processed_indents[0][0]['total'];
        
        $stockedin_indents = $this->Inventory_indent->query("
            SELECT count(id) as total FROM inventory_indent 
            WHERE $newconditions AND status = 4 
        ");
        $stockedin_indent=$stockedin_indents[0][0]['total'];
        
        $this->set(compact('submittedIndents', 'approvedIndents', 'processedIndents','submitted_indent','approved_indent','processed_indent','stockedin_indent'));
    }
    /*
    public function create_indent()
    {
        if (!$this->Session->check('Auth.User.id')) {
            return $this->redirect(['controller' => 'dashboard', 'action' => 'login']);
        }
    
        $this->layout = "users";
    
        // Logged-in location
        $locations = $this->Session->read('Auth.Location.id');
        $this->set(compact('locations'));
    
        // All DC locations
        $optlocations = $this->Location->find('all', [
            'conditions' => ['status' => 1, 'type' => 'dc']
        ]);
        $this->set(compact('optlocations'));    
        // Use user's assigned location_id (not user id)
        $authUser = $this->Session->read('Auth.User');
        $location_id = $authUser['id']; 
    
        // --- SEMIFINISHED ITEMS ---
        // $this->Inventory_semifinished->virtualFields['actual_balance'] = 
        //     "(SELECT sl.actual_balance 
        //       FROM stock_ledger sl 
        //       WHERE sl.sku_id = Inventory_semifinished.id 
        //         AND sl.store_id = $location_id
        //         AND sl.type = 'OUTLET' 
        //         AND sl.item_type = 'semifinished' 
        //       ORDER BY sl.id DESC 
        //       LIMIT 1)";
    
        // $semifinished = $this->Inventory_semifinished->find('all', [
        //     'conditions' => ['Inventory_semifinished.status' => 1],
        //     'order' => ['Inventory_semifinished.name ASC'],
        //     'fields' => ['Inventory_semifinished.*', 'Inventory_semifinished.actual_balance']
        // ]);
    
        // --- SKU ITEMS ---
        // $skus = $this->Inventory_sku->find('all', [
        //     'conditions' => ['Inventory_sku.status' => 1],
        //     'order' => ['Inventory_sku.name ASC']
        // ]);

        $skus = $this->Inventory_sku->find('all', [
            'fields' => [
                'Inventory_sku.*',
                'StockLedger.actual_balance',
                'COALESCE(SUM(InventorySkuTax.tax_percentage),0) AS total_tax_percentage'
            ],
        
            'joins' => [
        
                // ✅ Stock ledger join (existing)
                [
                    'table' => 'stock_ledger',
                    'alias' => 'StockLedger',
                    'type' => 'LEFT',
                    'conditions' => [
                        'StockLedger.sku_id = Inventory_sku.id',
                        'StockLedger.store_id' => $location_id,
                        'StockLedger.type' => 'OUTLET',
                        'StockLedger.id = (
                            SELECT MAX(sl2.id)
                            FROM stock_ledger sl2
                            WHERE sl2.sku_id = Inventory_sku.id
                            AND sl2.store_id = ' . $location_id . '
                            AND sl2.type = "OUTLET"
                        )'
                    ]
                ],
        
                // ✅ NEW Tax join
                [
                    'table' => 'inventory_sku_tax',
                    'alias' => 'InventorySkuTax',
                    'type' => 'LEFT',
                    'conditions' => [
                        'InventorySkuTax.product_id = Inventory_sku.id',
                        'InventorySkuTax.status' => 1
                    ]
                ]
            ],
        
            'conditions' => [
                'Inventory_sku.status' => 1
            ],
        
            // ✅ IMPORTANT for SUM()
            'group' => ['Inventory_sku.id'],
        
            'order' => ['Inventory_sku.name ASC']
        ]);
    
        // Combine both sets
        $items = array_merge($skus);

        // echo "<pre/>";
        // print_r($items);exit;
    
        $this->set(compact('items'));
    }

    public function get_actualbal_items_by_location()
    {
        $this->autoRender = false;
        $this->response->type('json');

        $dc_location_id = $this->request->data('location_id');
        $outlet_location_id = $this->Session->read('Auth.Location.id');

        if (empty($dc_location_id) || empty($outlet_location_id)) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid locations']);
            return;
        }

        
        $skus = $this->Inventory_sku->find('all', [
            'fields' => [
                'Inventory_sku.*',
                'OutletLedger.actual_balance AS outlet_balance',
                'DcLedger.actual_balance AS dc_balance'
            ],
            'joins' => [
                [
                    'table' => 'stock_ledger',
                    'alias' => 'OutletLedger',
                    'type' => 'LEFT',
                    'conditions' => [
                        'OutletLedger.sku_id = Inventory_sku.id',
                        'OutletLedger.store_id' => $outlet_location_id,
                        'OutletLedger.type' => 'OUTLET',
                        'OutletLedger.id = (
                            SELECT MAX(sl1.id)
                            FROM stock_ledger sl1
                            WHERE sl1.sku_id = Inventory_sku.id
                            AND sl1.store_id = ' . $outlet_location_id . '
                            AND sl1.type = "OUTLET"
                        )'
                    ]
                ],
                [
                    'table' => 'stock_ledger',
                    'alias' => 'DcLedger',
                    'type' => 'LEFT',
                    'conditions' => [
                        'DcLedger.sku_id = Inventory_sku.id',
                        'DcLedger.store_id' => $dc_location_id,
                        'DcLedger.type' => 'DC',
                        'DcLedger.id = (
                            SELECT MAX(sl2.id)
                            FROM stock_ledger sl2
                            WHERE sl2.sku_id = Inventory_sku.id
                            AND sl2.store_id = ' . $dc_location_id . '
                            AND sl2.type = "DC"
                        )'
                    ]
                ]
            ],
            'conditions' => ['Inventory_sku.status' => 1],
            'order' => ['Inventory_sku.name ASC']
        ]);

        $items = [];
        foreach ($skus as $s) {
            $sku = $s['Inventory_sku'];
            $outlet = $s['OutletLedger'];
            $dc = $s['DcLedger'];
            $items[] = [
                'id' => $sku['id'],
                'name' => $sku['name'],
                'cat_name' => $sku['cat_name'] ?? '-',
                'unit_name' => $sku['unit_name'] ?? '-',
                'price' => $sku['price'] ?? 0,
                'outlet_balance' => $outlet['outlet_balance'] ?? 'NA',
                'dc_balance' => $dc['dc_balance'] ?? 'NA',
                'unit' => $sku['unit'],
                'type' => 'SKU'
            ];
        }

        echo json_encode(['status' => 'success', 'items' => $items]);
    }
    */
    
    public function create_indent()
    {
        if (!$this->Session->check('Auth.User.id')) {
            return $this->redirect(['controller' => 'dashboard', 'action' => 'login']);
        }
    
        $this->layout = "users";
    
        // Logged-in location
        $locations = $this->Session->read('Auth.Location.id');
        $this->set(compact('locations'));
    
        // All DC locations
        $optlocations = $this->Location->find('all', [
            'conditions' => ['status' => 1, 'type' => 'dc']
        ]);
        $this->set(compact('optlocations'));    
        // Use user's assigned location_id (not user id)
        $authUser = $this->Session->read('Auth.User');
        $location_id = $authUser['id']; 
    
        // --- SEMIFINISHED ITEMS ---
        // $this->Inventory_semifinished->virtualFields['actual_balance'] = 
        //     "(SELECT sl.actual_balance 
        //       FROM stock_ledger sl 
        //       WHERE sl.sku_id = Inventory_semifinished.id 
        //         AND sl.store_id = $location_id
        //         AND sl.type = 'OUTLET' 
        //         AND sl.item_type = 'semifinished' 
        //       ORDER BY sl.id DESC 
        //       LIMIT 1)";
    
        // $semifinished = $this->Inventory_semifinished->find('all', [
        //     'conditions' => ['Inventory_semifinished.status' => 1],
        //     'order' => ['Inventory_semifinished.name ASC'],
        //     'fields' => ['Inventory_semifinished.*', 'Inventory_semifinished.actual_balance']
        // ]);
    
        // --- SKU ITEMS ---
        // $skus = $this->Inventory_sku->find('all', [
        //     'conditions' => ['Inventory_sku.status' => 1],
        //     'order' => ['Inventory_sku.name ASC']
        // ]);

        $skus = $this->Inventory_sku->find('all', [
            'fields' => [
                'Inventory_sku.*',
                'StockLedger.actual_balance',
                'COALESCE(SUM(InventorySkuTax.tax_percentage),0) AS total_tax_percentage'
            ],
        
            'joins' => [
        
                // ✅ Stock ledger join (existing)
                [
                    'table' => 'stock_ledger',
                    'alias' => 'StockLedger',
                    'type' => 'LEFT',
                    'conditions' => [
                        'StockLedger.sku_id = Inventory_sku.id',
                        'StockLedger.store_id' => $location_id,
                        'StockLedger.type' => 'OUTLET',
                        'StockLedger.id = (
                            SELECT MAX(sl2.id)
                            FROM stock_ledger sl2
                            WHERE sl2.sku_id = Inventory_sku.id
                            AND sl2.store_id = ' . $location_id . '
                            AND sl2.type = "OUTLET"
                        )'
                    ]
                ],
        
                // ✅ NEW Tax join
                [
                    'table' => 'inventory_sku_tax',
                    'alias' => 'InventorySkuTax',
                    'type' => 'LEFT',
                    'conditions' => [
                        'InventorySkuTax.product_id = Inventory_sku.id',
                        'InventorySkuTax.status' => 1
                    ]
                ]
            ],
        
            'conditions' => [
                'Inventory_sku.status' => 1
            ],
        
            // ✅ IMPORTANT for SUM()
            'group' => ['Inventory_sku.id'],
        
            'order' => ['Inventory_sku.name ASC']
        ]);

    
        // Combine both sets
        $items = array_merge($skus);

        // echo "<pre/>";
        // print_r($items);exit;
    
        $this->set(compact('items'));
    }

    public function get_actualbal_items_by_location()
    {
        $this->autoRender = false;
        $this->response->type('json');

        $dc_location_id = $this->request->data('location_id');
        $outlet_location_id = $this->Session->read('Auth.Location.id');

        if (empty($dc_location_id) || empty($outlet_location_id)) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid locations']);
            return;
        }

        
        // $skus = $this->Inventory_sku->find('all', [
        //     'fields' => [
        //         'Inventory_sku.*',
        //         'OutletLedger.actual_balance AS outlet_balance',
        //         'DcLedger.actual_balance AS dc_balance'
        //     ],
        //     'joins' => [
        //         [
        //             'table' => 'stock_ledger',
        //             'alias' => 'OutletLedger',
        //             'type' => 'LEFT',
        //             'conditions' => [
        //                 'OutletLedger.sku_id = Inventory_sku.id',
        //                 'OutletLedger.store_id' => $outlet_location_id,
        //                 'OutletLedger.type' => 'OUTLET',
        //                 'OutletLedger.id = (
        //                     SELECT MAX(sl1.id)
        //                     FROM stock_ledger sl1
        //                     WHERE sl1.sku_id = Inventory_sku.id
        //                     AND sl1.store_id = ' . $outlet_location_id . '
        //                     AND sl1.type = "OUTLET"
        //                 )'
        //             ]
        //         ],
        //         [
        //             'table' => 'stock_ledger',
        //             'alias' => 'DcLedger',
        //             'type' => 'LEFT',
        //             'conditions' => [
        //                 'DcLedger.sku_id = Inventory_sku.id',
        //                 'DcLedger.store_id' => $dc_location_id,
        //                 'DcLedger.type' => 'DC',
        //                 'DcLedger.id = (
        //                     SELECT MAX(sl2.id)
        //                     FROM stock_ledger sl2
        //                     WHERE sl2.sku_id = Inventory_sku.id
        //                     AND sl2.store_id = ' . $dc_location_id . '
        //                     AND sl2.type = "DC"
        //                 )'
        //             ]
        //         ]
        //     ],
        //     'conditions' => ['Inventory_sku.status' => 1],
        //     'order' => ['Inventory_sku.name ASC']
        // ]);
        
        $skus = $this->Inventory_sku->find('all', [
            'fields' => [
                'Inventory_sku.*',
        
                // ledgers
                'OutletLedger.actual_balance AS outlet_balance',
                'DcLedger.actual_balance AS dc_balance',
        
                // ✅ ADD THIS (tax sum)
                 '(SELECT COALESCE(SUM(tax_percentage),0)
                  FROM inventory_sku_tax
                  WHERE product_id = Inventory_sku.id
                  AND status = 1) AS total_tax_percentage'
            ],
        
            'joins' => [
        
                // Outlet stock
                [
                    'table' => 'stock_ledger',
                    'alias' => 'OutletLedger',
                    'type' => 'LEFT',
                    'conditions' => [
                        'OutletLedger.sku_id = Inventory_sku.id',
                        'OutletLedger.store_id' => $outlet_location_id,
                        'OutletLedger.type' => 'OUTLET',
                        'OutletLedger.id = (
                            SELECT MAX(sl1.id)
                            FROM stock_ledger sl1
                            WHERE sl1.sku_id = Inventory_sku.id
                            AND sl1.store_id = ' . $outlet_location_id . '
                            AND sl1.type = "OUTLET"
                        )'
                    ]
                ],
        
                // DC stock
                [
                    'table' => 'stock_ledger',
                    'alias' => 'DcLedger',
                    'type' => 'LEFT',
                    'conditions' => [
                        'DcLedger.sku_id = Inventory_sku.id',
                        'DcLedger.store_id' => $dc_location_id,
                        'DcLedger.type' => 'DC',
                        'DcLedger.id = (
                            SELECT MAX(sl2.id)
                            FROM stock_ledger sl2
                            WHERE sl2.sku_id = Inventory_sku.id
                            AND sl2.store_id = ' . $dc_location_id . '
                            AND sl2.type = "DC"
                        )'
                    ]
                ]
            ],
        
            'conditions' => ['Inventory_sku.status' => 1],
            'order' => ['Inventory_sku.name ASC']
        ]);


        $items = [];
        foreach ($skus as $s) {
            $sku = $s['Inventory_sku'];
            $outlet = $s['OutletLedger'];
            $dc = $s['DcLedger'];
            $items[] = [
                'id' => $sku['id'],
                'name' => $sku['name'],
                'cat_name' => $sku['cat_name'] ?? '-',
                'unit_name' => $sku['unit_name'] ?? '-',
                'price' => $sku['price'] ?? 0,
                'outlet_balance' => $outlet['outlet_balance'] ?? 'NA',
                'dc_balance' => $dc['dc_balance'] ?? 'NA',
                'unit' => $sku['unit'],
                'type' => 'SKU',
                'tax' => $s[0]['total_tax_percentage'] ?? 0
            ];
        }

        echo json_encode(['status' => 'success', 'items' => $items]);
    }


    public function add_indent() 
    {
        $this->autoRender = false;
    
        if (!$this->Session->check('Auth.User.id')) {
            echo json_encode(['response_code' => 0, 'data' => 'User session expired.']);
            return;
        }
    
        $id = $this->Session->read('Auth.Location.id');
        $name = $this->Session->read('Auth.Location.name');
        
        if (isset($_POST['checkedData']) && isset($_POST['location_id'])) {
            $checkeddatas = json_decode($_POST['checkedData'], true);
            $location_id = (int)$_POST['location_id'];
            $expiry = $_POST['expiry'];
            $dclocation = $_POST['dclocation'];
            // Validate location
            $location = $this->Location->find('first', [
                'conditions' => ['Location.id' => $location_id],
                'fields' => ['id', 'name'],
                'recursive' => -1
            ]);
            
            if (empty($location)) {
                echo json_encode(['response_code' => 0, 'data' => 'Invalid location selected.']);
                return;
            }
            
            $dclocation = $this->Location->find('first', [
                'conditions' => ['Location.id' => $dclocation,'type' => 'dc'],
                'fields' => ['id', 'name'],
                'recursive' => -1
            ]);
            if (empty($dclocation)) {
                echo json_encode(['response_code' => 0, 'data' => 'Invalid Warehouse selected.']);
                return;
            }
    
            // Generate unique indent_id
            do {
                $txnid = rand(1, 9);
                for ($i = 0; $i < 10; $i++) {
                    $txnid .= rand(0, 9);
                }
    
                $existing = $this->Inventory_indent_items->find('first', [
                    'conditions' => ['indent_id' => $txnid],
                    'recursive' => -1
                ]);
            } while ($existing);
    
            $amount = 0;
            $itemsSaved = true;

            // echo "<pre/>";
            // print_r($checkeddatas);exit;
    
            foreach ($checkeddatas as $item) {
                $idParts = explode('_', $item['id']);
                $item_type = $idParts[0];   // "Semifinished" or "SKU"
                $skuid = $idParts[1];       // "4" or "133"
                $price = $item['price'];
                $quantity = $item['sku_quantity'];
                // $item_type = $item['item_type'];
                
                if($item_type=='Semifinished'){
                    
                    $product_taxes=$this->Inventory_semifinished_tax->query("select SUM(tax_percentage) as total_tax from inventory_semifinished_tax where product_id='$skuid' AND status = 1");
                    $total_tax=round($product_taxes['0']['0']['total_tax'],2);
                    
                    $tax_value = round(($price*$quantity) * ($total_tax / 100),2);
    
                    $skuamount=round(($price*$quantity) + $tax_value,2);
                    
                    $skuData = [
                        'indent_id' => $txnid,
                        'sku_id' => $skuid,
                        'sku_name' => $item['sku_name'],
                        'sku_brand' => $item['sku_brand'],
                        'sku_unit' => $item['unit_id'],
                        'sku_unit_name' => $item['unit_name'],
                        'sku_price' => $price,
                        'sku_quantity' => $quantity,
                        'sku_total_tax_percentage' => $total_tax,
                        'sku_total_tax_value' => $tax_value,
                        'sku_amount' => $skuamount,
                        'item_type' =>'semifinished'
                    ];
                
                    $this->Inventory_indent_items->create(); // ensure new row
                    if (!$this->Inventory_indent_items->save($skuData)) {
                        $itemsSaved = false;
                        break;
                    }
                    
                }else{
                    $product_taxes=$this->Inventory_sku_tax->query("select SUM(tax_percentage) as total_tax from inventory_sku_tax where product_id='$skuid' and status = 1");
                    $total_tax=round($product_taxes['0']['0']['total_tax'],2);
                    
                    $tax_value = round(($price*$quantity) * ($total_tax / 100),2);
    
                    $skuamount=round(($price*$quantity) + $tax_value,2);
                    
                    $skuData = [
                        'indent_id' => $txnid,
                        'sku_id' => $skuid,
                        'sku_name' => $item['sku_name'],
                        'sku_brand' => $item['sku_brand'],
                        'sku_unit' => $item['unit_id'],
                        'sku_unit_name' => $item['unit_name'],
                        'sku_price' => $price,
                        'sku_quantity' => $quantity,
                        'sku_total_tax_percentage' => $total_tax,
                        'sku_total_tax_value' => $tax_value,
                        'sku_amount' => $skuamount,
                        'item_type' =>'sku'
                    ];
        
                    $this->Inventory_indent_items->create(); // ensure new row
                    if (!$this->Inventory_indent_items->save($skuData)) {
                        $itemsSaved = false;
                        break;
                    }
                }
                
                $amount += ($skuamount);
            }
        
            if ($itemsSaved) {
                $indentData = [
                    'indent_id' => $txnid,
                    'created_by' => $id,
                    'created_by_name' => $name,
                    'total_amount' => $amount,
                    'location_id' => $location['Location']['id'],
                    'location_name' => $location['Location']['name'],
                    'warehouse_id' => $dclocation['Location']['id'],
                    'warehouse_name' => $dclocation['Location']['name'],
                    'expiry' => $expiry
                ];
    
                $this->Inventory_indent->create();
                
                if ($this->Inventory_indent->save($indentData)) {
                    echo json_encode(['response_code' => 1, 'data' => 'saved']);
                } else {
                    echo json_encode(['response_code' => 0, 'data' => 'Failed to save indent master data.']);
                }
            } else {
                echo json_encode(['response_code' => 0, 'data' => 'Failed to save one or more items.']);
            }
        } else {
            echo json_encode(['response_code' => 0, 'data' => 'Invalid request. Missing data.']);
        }
    }

    public function view_indent()
	{
	    if(!$this->Session->check('Auth.User.id')){
			$this->redirect(array('controller'=>'dashboard','action' => 'login'));		
		}
        
	    $this->layout = "users";
		$id=$_REQUEST['id'];
        $indents = $this->Inventory_indent->query("SELECT * FROM inventory_indent where indent_id=$id and status != 0");
        $this->set(compact('indents'));
        
		$items = $this->Inventory_indent_items->query("SELECT * FROM inventory_indent_items where indent_id=$id and status != 0");
        
        $this->set(compact('items'));
        
        
	}
	/*
	public function edit_indent()
    {
        if (!$this->Session->check('Auth.User.id')) {
            return $this->redirect(['controller' => 'dashboard', 'action' => 'login']);
        }
    
        $this->layout = "users";
    
        // --- GET INDENT ID ---
        $id = $_REQUEST['id'];
    
        // --- USER SESSION INFO ---
        $authUser = $this->Session->read('Auth.User');
        $user_location = $this->Session->read('Auth.Location.id');
    
        // --- FETCH EXISTING INDENT RECORD ---
        $indentData = $this->Inventory_indent->find('first', [
            'conditions' => ['indent_id' => $id,'status' => 1],
            'recursive' => -1
        ]);
    
        if (empty($indentData)) {
            $this->Flash->error('Invalid Indent ID.');
            return $this->redirect(['controller' => 'outlet', 'action' => 'indent']);
        }
    
        // --- FETCH SEMIFINISHED ITEMS + EXISTING QUANTITIES ---
        // $semifinishedQuery = "
        //     SELECT 
        //         sf.*, 
        //         -- previously saved indent quantity
        //         (
        //             SELECT iit.sku_quantity 
        //             FROM inventory_indent_items iit
        //             WHERE iit.indent_id = $id
        //               AND iit.sku_id = sf.id 
        //               AND iit.item_type = 'Semifinished' and iit.status=1
        //             LIMIT 1
        //         ) AS quantity,
        
        //         -- latest stock balance from ledger
        //         (
        //             SELECT sl.actual_balance 
        //             FROM stock_ledger sl 
        //             WHERE sl.sku_id = sf.id 
        //               AND sl.store_id = $user_location
        //               AND sl.type = 'OUTLET' 
        //               AND sl.item_type = 'Semifinished'
        //             ORDER BY sl.id DESC 
        //             LIMIT 1
        //         ) AS actual_balance
        
        //     FROM inventory_semifinished sf
        //     WHERE sf.status = 1
        //     ORDER BY sf.name ASC
        // ";
        // $semifinished = $this->Inventory_semifinished->query($semifinishedQuery);
    
        $selected_location_id = $indentData['Inventory_indent']['location_id']; //outlet
        $selected_warehouse_id = $indentData['Inventory_indent']['warehouse_id']; //dc
        // --- FETCH SKU ITEMS + EXISTING QUANTITIES ---
        // $skuQuery = "
        //     SELECT *,
        //         (
        //             SELECT sku_quantity 
        //             FROM inventory_indent_items 
        //             WHERE indent_id = '$id' 
        //             AND sku_id = inventory_skus.id 
        //             AND item_type = 'sku'  and status=1
        //         ) AS quantity
        //     FROM inventory_skus
        //     WHERE inventory_skus.status = 1
        //     ORDER BY inventory_skus.name ASC;
        // ";
        // $skus = $this->Inventory_sku->query($skuQuery);

        $skuQuery = "
            SELECT 
                inventory_skus.*, 
                (
                    SELECT sku_quantity 
                    FROM inventory_indent_items 
                    WHERE indent_id = '$id' 
                    AND sku_id = inventory_skus.id 
                    AND item_type = 'sku'  
                    AND status = 1
                    LIMIT 1
                ) AS quantity,
                (
                    SELECT sl1.actual_balance 
                    FROM stock_ledger sl1 
                    WHERE sl1.sku_id = inventory_skus.id 
                    AND sl1.store_id = '$selected_location_id'
                    AND sl1.type = 'Outlet'
                    ORDER BY sl1.id DESC 
                    LIMIT 1
                ) AS outlet_balance,
                (
                    SELECT sl2.actual_balance 
                    FROM stock_ledger sl2 
                    WHERE sl2.sku_id = inventory_skus.id 
                    AND sl2.store_id = '$selected_warehouse_id'
                    AND sl2.type = 'DC'
                    ORDER BY sl2.id DESC 
                    LIMIT 1
                ) AS dc_balance

            FROM inventory_skus 
            WHERE inventory_skus.status = 1
            ORDER BY inventory_skus.name ASC;";
        $skus = $this->Inventory_sku->query($skuQuery);

    
        // --- COMBINE BOTH ---
        $items = array_merge($skus);
    
        // --- FETCH DC LOCATIONS ---
        $optlocations = $this->Location->find('all', [
            'conditions' => ['status' => 1, 'type' => 'dc']
        ]);
    
        // --- SET VARIABLES FOR VIEW ---
        $selected_expiry = $indentData['Inventory_indent']['expiry'];
    
        $this->set(compact(
            'items',
            'id',
            'selected_location_id',
            'selected_warehouse_id',
            'selected_expiry',
            'optlocations'
        ));
    }
    */
    public function edit_indent()
    {
        if (!$this->Session->check('Auth.User.id')) {
            return $this->redirect(['controller' => 'dashboard', 'action' => 'login']);
        }
    
        $this->layout = "users";
    
        // --- GET INDENT ID ---
        $id = $_REQUEST['id'];
    
        // --- USER SESSION INFO ---
        $authUser = $this->Session->read('Auth.User');
        $user_location = $this->Session->read('Auth.Location.id');
    
        // --- FETCH EXISTING INDENT RECORD ---
        $indentData = $this->Inventory_indent->find('first', [
            'conditions' => ['indent_id' => $id,'status' => 1],
            'recursive' => -1
        ]);
    
        if (empty($indentData)) {
            $this->Flash->error('Invalid Indent ID.');
            return $this->redirect(['controller' => 'outlet', 'action' => 'indent']);
        }
    
        // --- FETCH SEMIFINISHED ITEMS + EXISTING QUANTITIES ---
        // $semifinishedQuery = "
        //     SELECT 
        //         sf.*, 
        //         -- previously saved indent quantity
        //         (
        //             SELECT iit.sku_quantity 
        //             FROM inventory_indent_items iit
        //             WHERE iit.indent_id = $id
        //               AND iit.sku_id = sf.id 
        //               AND iit.item_type = 'Semifinished' and iit.status=1
        //             LIMIT 1
        //         ) AS quantity,
        
        //         -- latest stock balance from ledger
        //         (
        //             SELECT sl.actual_balance 
        //             FROM stock_ledger sl 
        //             WHERE sl.sku_id = sf.id 
        //               AND sl.store_id = $user_location
        //               AND sl.type = 'OUTLET' 
        //               AND sl.item_type = 'Semifinished'
        //             ORDER BY sl.id DESC 
        //             LIMIT 1
        //         ) AS actual_balance
        
        //     FROM inventory_semifinished sf
        //     WHERE sf.status = 1
        //     ORDER BY sf.name ASC
        // ";
        // $semifinished = $this->Inventory_semifinished->query($semifinishedQuery);
    
        $selected_location_id = $indentData['Inventory_indent']['location_id']; //outlet
        $selected_warehouse_id = $indentData['Inventory_indent']['warehouse_id']; //dc
        // --- FETCH SKU ITEMS + EXISTING QUANTITIES ---
        // $skuQuery = "
        //     SELECT *,
        //         (
        //             SELECT sku_quantity 
        //             FROM inventory_indent_items 
        //             WHERE indent_id = '$id' 
        //             AND sku_id = inventory_skus.id 
        //             AND item_type = 'sku'  and status=1
        //         ) AS quantity
        //     FROM inventory_skus
        //     WHERE inventory_skus.status = 1
        //     ORDER BY inventory_skus.name ASC;
        // ";
        // $skus = $this->Inventory_sku->query($skuQuery);

        $skuQuery = "
            SELECT 
                inventory_skus.*, 
                (SELECT COALESCE(SUM(tax_percentage),0)
                  FROM inventory_sku_tax
                  WHERE product_id = inventory_skus.id
                  AND status = 1) AS total_tax_percentage,
                (
                    SELECT sku_quantity 
                    FROM inventory_indent_items 
                    WHERE indent_id = '$id' 
                    AND sku_id = inventory_skus.id 
                    AND item_type = 'sku'  
                    AND status = 1
                    LIMIT 1
                ) AS quantity,
                (
                    SELECT sl1.actual_balance 
                    FROM stock_ledger sl1 
                    WHERE sl1.sku_id = inventory_skus.id 
                    AND sl1.store_id = '$selected_location_id'
                    AND sl1.type = 'Outlet'
                    ORDER BY sl1.id DESC 
                    LIMIT 1
                ) AS outlet_balance,
                (
                    SELECT sl2.actual_balance 
                    FROM stock_ledger sl2 
                    WHERE sl2.sku_id = inventory_skus.id 
                    AND sl2.store_id = '$selected_warehouse_id'
                    AND sl2.type = 'DC'
                    ORDER BY sl2.id DESC 
                    LIMIT 1
                ) AS dc_balance

            FROM inventory_skus 
            WHERE inventory_skus.status = 1
            ORDER BY inventory_skus.name ASC";
        $skus = $this->Inventory_sku->query($skuQuery);

    
        // --- COMBINE BOTH ---
        $items = array_merge($skus);
    
        // --- FETCH DC LOCATIONS ---
        $optlocations = $this->Location->find('all', [
            'conditions' => ['status' => 1, 'type' => 'dc']
        ]);
    
        // --- SET VARIABLES FOR VIEW ---
        $selected_expiry = $indentData['Inventory_indent']['expiry'];
    
        $this->set(compact(
            'items',
            'id',
            'selected_location_id',
            'selected_warehouse_id',
            'selected_expiry',
            'optlocations'
        ));
    }

    public function update_indent() 
    {
        $this->autoRender = false;
    
        $id = $this->Session->read('Auth.Location.id');
        $name = $this->Session->read('Auth.Location.name');
    
        if (isset($_POST['checkedData'])) {
            $checkeddatas = json_decode($_POST['checkedData'], true);
            $indentid = $_POST['indent_id'];
            $expiry = $_POST['expiry'];
            $dclocation = $_POST['dclocation'];
            $location_id = isset($_POST['location_id']) ? $_POST['location_id'] : null;
            $location_name = '';
    
            // --- Fetch location name if not passed ---
            if (!empty($location_id)) {
                $loc = $this->Location->find('first', [
                    'conditions' => ['Location.id' => $location_id],
                    'fields' => ['Location.name'],
                    'recursive' => -1
                ]);
                $location_name = isset($loc['Location']['name']) ? $loc['Location']['name'] : '';
            }
    
            // --- Validate DC location ---
            $dclocation = $this->Location->find('first', [
                'conditions' => ['Location.id' => $dclocation, 'type' => 'dc','status'=>1],
                'fields' => ['id', 'name'],
                'recursive' => -1
            ]);
            if (empty($dclocation)) {
                echo json_encode(['response_code' => 0, 'data' => 'Invalid Warehouse selected.']);
                return;
            }
    
            // --- Mark existing indent and items inactive ---
            $this->Inventory_indent_items->query("UPDATE inventory_indent_items SET status=0, sync=0, local_sync=0 WHERE indent_id ='$indentid'");
            $this->Inventory_indent->query("UPDATE inventory_indent SET status=0, sync=0, local_sync=0 WHERE indent_id = '$indentid'");
    
            $amount = 0;
            $anySaved = false;
    
            // --- Loop through each checked item ---
            foreach ($checkeddatas as $item) {
                $skuid = $item['id'];
                $price = $item['price'];
                $sku_name = $item['sku_name'];
                $sku_brand = $item['sku_brand'];
                $unit_name = $item['unit_name'];
                $unit_id = $item['unit_id'];
                $quantity = $item['sku_quantity'];
                $item_type = strtolower(trim($item['item_type'])) === 'semifinished' ? 'Semifinished' : 'SKU';
                
                if (!empty($quantity)) {
    
                    // === Select tax table based on item_type ===
                    if ($item_type === 'Semifinished') {
                        $product_taxes = $this->Inventory_semifinished_tax->query(
                            "SELECT SUM(tax_percentage) AS total_tax 
                             FROM inventory_semifinished_tax 
                             WHERE product_id = '$skuid' AND status = 1"
                        );
                    } else {
                        $product_taxes = $this->Inventory_sku_tax->query(
                            "SELECT SUM(tax_percentage) AS total_tax 
                             FROM inventory_sku_tax 
                             WHERE product_id = '$skuid' AND status = 1"
                        );
                    }
                
                    $total_tax = round($product_taxes[0][0]['total_tax'] ?? 0, 2);
                    $tax_value = round(($price * $quantity) * ($total_tax / 100), 2);
                    $skuamount = round(($price * $quantity) + $tax_value, 2);
    
                    // === Save item ===
                    $itemData = [
                        'Inventory_indent_items' => [
                            'indent_id' => $indentid,
                            'sku_id' => $skuid,
                            'sku_name' => $sku_name,
                            'sku_brand' => $sku_brand,
                            'sku_unit' => $unit_id,
                            'sku_unit_name' => $unit_name,
                            'sku_price' => $price,
                            'sku_quantity' => $quantity,
                            'sku_total_tax_percentage' => $total_tax,
                            'sku_total_tax_value' => $tax_value,
                            'sku_amount' => $skuamount,
                            'item_type' => $item_type
                        ]
                    ];
                    
                    if ($this->Inventory_indent_items->saveAll($itemData)) {
                        $anySaved = true;
                    }
                    $amount += $skuamount;
                }
            }
            
    
            // --- Save indent master ---
            if ($anySaved) {
                $indentData = [
                    'Inventory_indent' => [
                        'indent_id' => $indentid,
                        'expiry' => $expiry,
                        'created_by' => $id,
                        'created_by_name' => $name,
                        'location_id' => $location_id,
                        'location_name' => $location_name,
                        'total_amount' => $amount,
                        'warehouse_id' => $dclocation['Location']['id'],
                        'warehouse_name' => $dclocation['Location']['name'],
                        'is_updated' => 1
                    ]
                ];
    
                if ($this->Inventory_indent->save($indentData)) {
                    echo json_encode(['response_code' => 1, 'data' => 'Indent updated successfully.']);
                } else {
                    echo json_encode(['response_code' => 0, 'data' => 'Failed to update indent.']);
                }
            } else {
                echo json_encode(['response_code' => 0, 'data' => 'No items saved.']);
            }
        }
    }
    /*
    public function indent_change_status() 
    {
        $this->autoRender = false; 
    
        if ($this->request->is('post')) {
            $indentId = $this->request->data['_id']; 

            $currentStatusData = $this->Inventory_indent->find('first', [
                'conditions' => ['Inventory_indent.indent_id' => $indentId],
                'fields' => ['Inventory_indent.status','Inventory_indent.id'],
                'order' => ['Inventory_indent.id DESC'],
                'recursive' => -1
            ]);
            
            $ind_id=$currentStatusData['Inventory_indent']['id'];
            $newStatus = $currentStatusData['Inventory_indent']['status'] == 1 ? 0 : 1;
    
            $updated = $this->Inventory_indent->updateAll(
                [
                    'Inventory_indent.status' => $newStatus,
                    'Inventory_indent.sync' => 0,
                    'Inventory_indent.local_sync' => 0
                ],
                ['Inventory_indent.id' => $ind_id]
            );
    
            if ($updated) {
                echo json_encode(['status' => true]);
            } else {
                echo json_encode(['status' => false]);
            }
        }
    }
    */
    public function indent_change_status()
    {
        $this->autoRender = false;
        $this->response->type('json');
    
        if (!$this->request->is('post')) {
            echo json_encode([
                'status' => false,
                'message' => 'Invalid request method'
            ]);
            return;
        }
    
        $indentId = $this->request->data['_id'] ?? null;
    
        if (empty($indentId)) {
            echo json_encode([
                'status' => false,
                'message' => 'Indent ID missing'
            ]);
            return;
        }
    
        $currentStatusData = $this->Inventory_indent->find('first', [
            'conditions' => ['Inventory_indent.indent_id' => $indentId],
            'fields' => ['Inventory_indent.status', 'Inventory_indent.id'],
            'recursive' => -1
        ]);
    
        if (empty($currentStatusData)) {
            echo json_encode([
                'status' => false,
                'message' => 'Indent not found'
            ]);
            return;
        }
    
        $ind_id    = $currentStatusData['Inventory_indent']['id'];
        $oldStatus = (int)$currentStatusData['Inventory_indent']['status'];
        $newStatus = ($oldStatus === 1) ? 0 : 1;
    
        // ===== UPDATE INDENT =====
        $updatedIndent = $this->Inventory_indent->updateAll(
            [
                'Inventory_indent.status'      => $newStatus,
                'Inventory_indent.sync'        => 0,
                'Inventory_indent.local_sync'  => 0
            ],
            ['Inventory_indent.id' => $ind_id]
        );
    
        if (!$updatedIndent) {
            echo json_encode([
                'status' => false,
                'message' => 'Failed to update indent status'
            ]);
            return;
        }
    
        // ===== UPDATE INDENT ITEMS =====
        $updatedItems = $this->Inventory_indent_items->updateAll(
            [
                'Inventory_indent_items.status'     => $newStatus,
                'Inventory_indent_items.sync'       => 0,
                'Inventory_indent_items.local_sync' => 0
            ],
            ['Inventory_indent_items.indent_id' => $indentId]
        );
    
        if (!$updatedItems) {
            echo json_encode([
                'status' => false,
                'message' => 'Indent updated but items update failed'
            ]);
            return;
        }
    
        echo json_encode([
            'status' => true,
            'message' => 'Status updated successfully'
        ]);
    }
    /*
    public function grn_history()
    {
        if (!$this->Session->check('Auth.Location.id')) {
            return $this->redirect(['controller' => 'dashboard', 'action' => 'login']);
        }
    
        $this->layout = "users";
    
        $startDate = date('m/d/Y', strtotime('-1 month'));
        $endDate = date('m/d/Y');
    
        if ($this->request->is(['post', 'put'])) {
            $daterange = $this->request->data['daterange'];
            $dates = explode(" - ", $daterange);
            $start_date = trim($dates[0]);
            $end_date = trim($dates[1]);
    
            $startDate = date('m/d/Y', strtotime($start_date));
            $endDate = date('m/d/Y', strtotime($end_date));
    
            $startdate = date('Y-m-d', strtotime($start_date));
            $enddate = date('Y-m-d', strtotime($end_date));
        } else {
            $startdate = date('Y-m-d', strtotime($startDate));
            $enddate = date('Y-m-d', strtotime($endDate));
        }
    
        // Session values
        $locationId = $this->Session->read('Auth.Location.id');
        
        // Build condition based on role
        $conditions = "created BETWEEN '$startdate 00:00:01' AND '$enddate 23:59:59'";
        if ($userRole !== 'admin') {
            $conditions .= " AND location_id = '$locationId'";
        }
        $conditions .= " AND status = '4'";
        
        $users = $this->Inventory_indent->query("
            SELECT * FROM inventory_indent 
            WHERE  $conditions 
            ORDER BY id DESC
        ");
    
        $yourStartDateValue = $startDate . ' - ' . $endDate;
        $this->set('startdate', $yourStartDateValue);
        $this->set(compact('users'));
    }
    */
    public function grn_history()
    {
        if (!$this->Session->check('Auth.Location.id')) {
            return $this->redirect(['controller' => 'dashboard', 'action' => 'login']);
        }
    
        $this->layout = "users";
    
        $startDate = date('m/d/Y', strtotime('-1 month'));
        $endDate = date('m/d/Y');
    
        if ($this->request->is(['post', 'put'])) {
            $daterange = $this->request->data['daterange'];
            $dates = explode(" - ", $daterange);
            $start_date = trim($dates[0]);
            $end_date = trim($dates[1]);
    
            $startDate = date('m/d/Y', strtotime($start_date));
            $endDate = date('m/d/Y', strtotime($end_date));
    
            $startdate = date('Y-m-d', strtotime($start_date));
            $enddate = date('Y-m-d', strtotime($end_date));
        } else {
            $startdate = date('Y-m-d', strtotime($startDate));
            $enddate = date('Y-m-d', strtotime($endDate));
        }
    
        // Session values
        $locationId = $this->Session->read('Auth.Location.id');
        
        // Build condition based on role
        $conditions = "created BETWEEN '$startdate 00:00:01' AND '$enddate 23:59:59'";
        if ($userRole !== 'admin') {
            $conditions .= " AND location_id = '$locationId'";
        }
        $conditions .= " AND status = '4'";
        
        $users = $this->Inventory_indent->query("
            SELECT * FROM inventory_indent 
            WHERE  $conditions 
            ORDER BY id DESC
        ");
    
        $yourStartDateValue = $startDate . ' - ' . $endDate;
        $this->set('startdate', $yourStartDateValue);
        
        $newconditions='';
        if ($userRole !== 'admin') {
            $newconditions .= " location_id = '$locationId'";
        }
        $submitted_indents = $this->Inventory_indent->query("
            SELECT count(id) as total FROM inventory_indent 
            WHERE $newconditions and status IN (1)
        ");
        $submitted_indent=$submitted_indents[0][0]['total'];
        
        $approved_indents = $this->Inventory_indent->query("
            SELECT count(id) as total FROM inventory_indent 
            WHERE $newconditions AND status = 2 
        ");
        $approved_indent=$approved_indents[0][0]['total'];
        
        $processed_indents = $this->Inventory_indent->query("
            SELECT count(id) as total FROM inventory_indent 
            WHERE $newconditions AND status = 3 
        ");
        $processed_indent=$processed_indents[0][0]['total'];
        
        $stockedin_indents = $this->Inventory_indent->query("
            SELECT count(id) as total FROM inventory_indent 
            WHERE $newconditions AND status = 4 
        ");
        $stockedin_indent=$stockedin_indents[0][0]['total'];
        
        $this->set(compact('submitted_indent','approved_indent','processed_indent','stockedin_indent'));
     
        $this->set(compact('users'));
    }
    /*
    public function stockin()
    {
        if (!$this->Session->check('Auth.Location.id')) {
            return $this->redirect(['controller' => 'dashboard', 'action' => 'login']);
        }
    
        $authUser = $this->Session->read('Auth.Location');
        $userLocationId = $authUser['id'] ?? 0;
    
        $this->layout = "users";
        $id = $_POST['po'] ?? null;
        $this->set(compact('id'));
    
        if ($id) {
            // Fetch PO
            $poQuery = "SELECT * FROM inventory_indent WHERE consignment_id = '$id'";
            $indents = $this->Inventory_indent->query($poQuery);
    
            if (empty($indents)) {
                $this->set('po_error', 'Consignment Not Found'); // trigger JS alert
                return;
            }
    
            $po = $indents[0]['inventory_indent'];
            
            if ($po['status'] == 4) {
                $this->set('po_error', 'Consignment Already Stocked IN'); // trigger JS alert
                return;
            }
            
            if ($po['status'] != 3) {
                $this->set('po_error', 'Consignment is not Proccessed'); // trigger JS alert
                return;
            }
            
            if ($userRole != 'admin' && $po['location_id'] != $userLocationId) {
                $this->set('po_error', 'Location Not Found'); // trigger JS alert
                return;
            }
            // print_r($indents);exit;
            // All good, load items
            $items = $this->Inventory_indent_items->query(
                "SELECT * FROM inventory_indent_items WHERE consignment_id = '$id' AND status = 3"
            );
            $this->set(compact('indents', 'items'));
        }
    }
    
    public function stockin()
    {
        if (!$this->Session->check('Auth.Location.id')) {
            return $this->redirect(['controller' => 'dashboard', 'action' => 'login']);
        }
    
        $authUser = $this->Session->read('Auth.Location');
        $userLocationId = $authUser['id'] ?? 0;
    
        $this->layout = "users";
        $id = $_POST['po'] ?? null;
        $this->set(compact('id'));
        
        $newconditions='';
        if ($userRole !== 'admin') {
            $newconditions .= " location_id = '$userLocationId'";
        }
        $submitted_indents = $this->Inventory_indent->query("
            SELECT count(id) as total FROM inventory_indent 
            WHERE $newconditions and status IN (1)
        ");
        $submitted_indent=$submitted_indents[0][0]['total'];
        
        $approved_indents = $this->Inventory_indent->query("
            SELECT count(id) as total FROM inventory_indent 
            WHERE $newconditions AND status = 2 
        ");
        $approved_indent=$approved_indents[0][0]['total'];
        
        $processed_indents = $this->Inventory_indent->query("
            SELECT count(id) as total FROM inventory_indent 
            WHERE $newconditions AND status = 3 
        ");
        $processed_indent=$processed_indents[0][0]['total'];
        
        $stockedin_indents = $this->Inventory_indent->query("
            SELECT count(id) as total FROM inventory_indent 
            WHERE $newconditions AND status = 4 
        ");
        $stockedin_indent=$stockedin_indents[0][0]['total'];
        
        $this->set(compact('submitted_indent','approved_indent','processed_indent','stockedin_indent'));
        
        if ($id) {
            // Fetch PO
            if($_POST['data']['Search']['type']==0){
                $poQuery = "SELECT * FROM inventory_indent WHERE consignment_id = '$id'";
                $indents = $this->Inventory_indent->query($poQuery);
                    
                if (empty($indents)) {
                    $this->set('po_error', 'Consignment Not Found'); // trigger JS alert
                    return;
                }
                $po = $indents[0]['inventory_indent'];
                
                if ($po['status'] == 4) {
                    $this->set('po_error', 'Consignment Already Stocked IN'); // trigger JS alert
                    return;
                }
                
                if ($po['status'] != 3) {
                    $this->set('po_error', 'Consignment is not Proccessed'); // trigger JS alert
                    return;
                }
                
                if ($userRole != 'admin' && $po['location_id'] != $userLocationId) {
                    $this->set('po_error', 'Location Not Found'); // trigger JS alert
                    return;
                }
                
                $items = $this->Inventory_indent_items->query(
                    "SELECT * FROM inventory_indent_items WHERE consignment_id = '$id' AND status = 3"
                );
                
            }else{
                $poQuery = "SELECT * FROM inventory_purchase_order WHERE p_o_id = '$id' and location_id='$userLocationId'";
                $indents = $this->Inventory_purchase_order->query($poQuery);
                
                if (empty($indents)) {
                    $this->set('po_error', 'PO Not Found or not available for this location'); // trigger JS alert
                    return;
                }
                $po = $indents[0]['inventory_purchase_order'];
                
                if ($po['status'] == 4) {
                    $this->set('po_error', 'PO Already Stocked IN'); // trigger JS alert
                    return;
                }
                
                if ($po['status'] != 3) {
                    $this->set('po_error', 'PO is not Proccessed'); // trigger JS alert
                    return;
                }
                
                $items = $this->Inventory_indent_items->query(
                    "SELECT * FROM inventory_purchase_order_items WHERE p_o_id = '$id' AND status = 3"
                );
                
            }
    
            $this->set(compact('indents', 'items'));
        }
    }
    */
    
    /*
	public function stockin_po() 
    {
        $this->autoRender = false; // Disable view rendering
    
        if ($this->request->is('post')) {
            $poid = $this->request->data['_id'];
            $invoice = trim($this->request->data['_invoice']);
            $quantities = $this->request->data['_quantities']; // associative array: sku_id => quantity
            $item_types = $this->request->data['_item_type']; 

            $today = date('Y-m-d H:i:s');
            
            $invoiceidchk = $this->Inventory_indent->find('first', [
                'conditions' => ['consignment_id' => $poid,'indent_id' => $invoice]
            ]);
            
            $amount = 0;
            // Generate unique stockin_id
            do {
                $txnid = rand(1, 9);
                for ($i = 0; $i < 10; $i++) {
                    $txnid .= rand(0, 9);
                }
    
                $existing = $this->Inventory_indent->find('first', [
                    'conditions' => ['stockin_id' => $txnid]
                ]);
            } while ($existing);
    
            $location_id = $this->Session->read('Auth.Location.id');
            
            $stockin_skus = $this->Inventory_indent_items->find('all', [
                'conditions' => ['consignment_id' => $poid,'status' => 3]
            ]);
            // echo "<pre/>";
            // print_r($stockin_skus);exit;
            
            foreach ($stockin_skus as $stockin_sku) {
                $sku = $stockin_sku['Inventory_indent_items'];
                $sku_id = $sku['sku_id'];
                $approved_price = $sku['approved_price'];
                
                if (isset($quantities[$sku_id])) {
                    $qty = floatval($quantities[$sku_id]);
                    $item_type = isset($item_types[$sku_id]) ? $item_types[$sku_id] : 'sku';
                    
                    if ($item_type == 'semifinished') {
                        $product_taxes = $this->Inventory_semifinished_tax->query("
                            SELECT SUM(tax_percentage) AS total_tax 
                            FROM inventory_semifinished_tax 
                            WHERE product_id='$sku_id' AND status = 1
                        ");
                    } else {
                        $product_taxes = $this->Inventory_sku_tax->query("
                            SELECT SUM(tax_percentage) AS total_tax 
                            FROM inventory_sku_tax 
                            WHERE product_id='$sku_id' AND status = 1
                        ");
                    }
                    $total_tax = isset($product_taxes[0][0]['total_tax']) ? round($product_taxes[0][0]['total_tax'], 2) : 0;
                    $tax_value = round(($approved_price * $qty) * ($total_tax / 100), 2);
                    $skuamount = round(($approved_price * $qty) + $tax_value, 2);
                    
                    // Update purchase order item
                    $this->Inventory_indent_items->updateAll(
                        ['status' => 4, 'sync' => 0, 'local_sync' => 0, 'stockin_quantity' => $qty,'stockin_tax_value' => $tax_value,
                        'stockin_amount' => $skuamount, 'status'=> 4],
                        ['consignment_id' => $poid, 'sku_id' => $sku_id]
                    );
                    
                    $amount += ($skuamount);
                    
                    //stock ledger entry semifinished
                    if ($item_type == 'semifinished') {
                        
                        $ledgerprevdetails = $this->Stock_ledger->find('first', [
                            'conditions' => ['sku_id' => $sku_id,'type'=>'OUTLET','item_type'=>'semifinished','store_id'=>$location_id],
                            'fields' => ['actual_balance'],
                            'order' => ['id DESC'],
                            'recursive' => -1
                        ]);
                        
                        $ledger_prev_quantity = !empty($ledgerprevdetails['Stock_ledger']['actual_balance']) 
                            ? $ledgerprevdetails['Stock_ledger']['actual_balance'] 
                            : 0;
                        
                        $newQuantity = $qty + $ledger_prev_quantity;
                        
                        $ledgerData['Stock_ledger'] = [
                                'store_id'        => $location_id,
                                'sku_id'          => $sku_id,
                                'sku_name'        => $sku['sku_name'],
                                'sku_price'       => $sku['sku_price'],
                                'unit_id'         => $sku['sku_unit'],
                                'unit_name'       => $sku['sku_unit_name'],
                                'record_id'       => $txnid,
                                'record_type'     => 'stockin',
                                'quantity'        => $qty,
                                'balance_stock'   => $newQuantity,
                                'actual_balance'  => $newQuantity,
                                'quantity_type'   => 'credit',
                                'type'            => 'OUTLET',
                                'item_type'       => 'semifinished',
                                'created'         => date('Y-m-d H:i:s')
                        ];
        				if($this->Stock_ledger->saveAll($ledgerData['Stock_ledger'])){
        				
            				$ingredients = $this->Inventory_linking->find('all', [
                                'conditions' => ['parent_id' => $sku_id,'parent_type' => 'Semifinished','component_type' => 'Sku', 'status' => 1]
                            ]);
                            // print_r($ingredients);exit;
                            foreach ($ingredients as $ingredient) {
                                $linked_sku_id = $ingredient['Inventory_linking']['component_id'];
                                $sku_quantity= $ingredient['Inventory_linking']['quantity']*$qty;
                                    
                                $ledgerprevdetails = $this->Stock_ledger->find('first', [
                                    'conditions' => ['sku_id' => $linked_sku_id,'store_id' =>$location_id,'item_type'=>'SKU' ,'type' =>'OUTLET' ],
                                    'fields' => ['actual_balance'],
                                    'order' => ['id DESC'],
                                    'recursive' => -1
                                ]);
                                
                                $ledger_prev_quantity = !empty($ledgerprevdetails['Stock_ledger']['actual_balance']) 
                                    ? $ledgerprevdetails['Stock_ledger']['actual_balance'] 
                                    : 0;
                                
                                $newQuantity = $ledger_prev_quantity+$sku_quantity;
                                
                                $sku = $this->Inventory_sku->find('first', [
                                    'conditions' => ['Inventory_sku.id' => $sku_id],
                                    'recursive' => -1
                                ]);
                                
                                $ledgerData['Stock_ledger'] = [
                                        'store_id'        => $location_id,
                                        'sku_id'          => $ingredient['Inventory_linking']['component_id'],
                                        'sku_name'        => $ingredient['Inventory_linking']['component_name'],
                                        'sku_price'       => $sku['Inventory_sku']['price'],
                                        'unit_id'         => $ingredient['Inventory_linking']['component_unit'],
                                        'unit_name'       => $ingredient['Inventory_linking']['component_unit_name'],
                                        'record_id'       => $txnid,
                                        'record_type'     => 'stockin',
                                        'quantity'        => $sku_quantity,
                                        'balance_stock'   => $newQuantity,
                                        'actual_balance'  => $newQuantity,
                                        'quantity_type'   => 'credit',
                                        'item_type'       => 'SKU',
                                        'type'            => 'OUTLET',
                                        'created'         => date('Y-m-d H:i:s')
                                ];
                				if(!$this->Stock_ledger->saveAll($ledgerData['Stock_ledger'])){
                                    echo json_encode(['status' => false]);
                                }
                            }
        				}else{
        				    echo json_encode(['status' => false]);
        				}
                    }else{
                        
                        $ledgerprevdetails = $this->Stock_ledger->find('first', [
                            'conditions' => ['sku_id' => $sku_id,'type'=>'OUTLET','item_type'=>'SKU','store_id'=>$location_id],
                            'fields' => ['actual_balance'],
                            'order' => ['id DESC'],
                            'recursive' => -1
                        ]);
                        
                        $ledger_prev_quantity = !empty($ledgerprevdetails['Stock_ledger']['actual_balance']) 
                            ? $ledgerprevdetails['Stock_ledger']['actual_balance'] 
                            : 0;
                        
                        $newQuantity = $qty + $ledger_prev_quantity;
                        
                        $ledgerData['Stock_ledger'] = [
                                'store_id'        => $location_id,
                                'sku_id'          => $sku_id,
                                'sku_name'        => $sku['sku_name'],
                                'sku_price'       => $sku['sku_price'],
                                'unit_id'         => $sku['sku_unit'],
                                'unit_name'       => $sku['sku_unit_name'],
                                'record_id'       => $txnid,
                                'record_type'     => 'stockin',
                                'quantity'        => $qty,
                                'balance_stock'   => $newQuantity,
                                'actual_balance'  => $newQuantity,
                                'quantity_type'   => 'credit',
                                'type'            => 'OUTLET',
                                'item_type'       => 'SKU',
                                'created'         => date('Y-m-d H:i:s')
                        ];
        				if(!$this->Stock_ledger->saveAll($ledgerData['Stock_ledger'])){
        				    echo json_encode(['status' => false]);
        				}
                    }
                }
            }
    
            // Update purchase order
            $this->Inventory_indent->query("UPDATE inventory_indent SET 
                stockin_id = '$txnid',
                status = 4,
                stockin_date = '$today',
                invoice_no = '$invoice',
                stockin_amount = '$amount',
                sync = 0, 
                local_sync = 0 
                WHERE consignment_id = '$poid'");
    
            echo json_encode(['status' => true]);
        }
    }

    public function stockin_po() 
    {
        $this->autoRender = false; // Disable view rendering
    
        if ($this->request->is('post')) {
            $poid = $this->request->data['_id'];
            $invoice = trim($this->request->data['_invoice']);
            $quantities = $this->request->data['_quantities']; // associative array: sku_id => quantity
            $item_types = $this->request->data['_item_type']; 
            $charge = $this->request->data['_charge'] ?? 0; 
            $type = $this->request->data['_type']; 

            $today = date('Y-m-d H:i:s');
            
            if (empty($poid)) {
                echo json_encode(['status'=>false,'message'=>'Invalid PO/Consignment ID']);
                return;
            }
            
            if (empty($invoice)) {
                echo json_encode(['status'=>false,'message'=>'Invoice number required']);
                return;
            }
            
            $dataSource = $this->Inventory_indent->getDataSource();
            $dataSource->begin();
            
            try {
                if (empty($quantities) || !is_array($quantities)) {
                    throw new Exception('Invalid quantity data');
                }

                if($type=='consignment'){
                    $invoiceidchk = $this->Inventory_indent->find('first', [
                        'conditions' => ['consignment_id' => $poid,'indent_id' => $invoice]
                    ]);
                    
                    if(empty($invoiceidchk)){
                        throw new Exception('Something went wrong for this indent...!');
                    }
                    
                    $amount = 0;
                    // Generate unique stockin_id
                    do {
                        $txnid = rand(1, 9);
                        for ($i = 0; $i < 10; $i++) {
                            $txnid .= rand(0, 9);
                        }
            
                        $existing = $this->Inventory_indent->find('first', [
                            'conditions' => ['stockin_id' => $txnid]
                        ]);
                    } while ($existing);
            
                    $location_id = $this->Session->read('Auth.Location.id');
                    
                    $stockin_skus = $this->Inventory_indent_items->find('all', [
                        'conditions' => ['consignment_id' => $poid,'status' => 3]
                    ]);
                    
                    if(empty($stockin_skus)){
                        throw new Exception('No item found for this...!');
                    }
                    
                    foreach ($stockin_skus as $stockin_sku) {
                        $sku = $stockin_sku['Inventory_indent_items'];
                        $sku_id = $sku['sku_id'];
                        $approved_price = $sku['approved_price'];
                        
                        if (isset($quantities[$sku_id])) {
                            $qty = floatval($quantities[$sku_id]);
                            $item_type = isset($item_types[$sku_id]) ? $item_types[$sku_id] : 'sku';
                            
                            if ($item_type == 'semifinished') {
                                $product_taxes = $this->Inventory_semifinished_tax->find('all', [
                                    'fields' => ['SUM(tax_percentage) as total_tax'],
                                    'conditions' => ['product_id' => $sku_id, 'status' => 1]
                                ]);
                            } else {
                                $product_taxes = $this->Inventory_sku_tax->find('all', [
                                    'fields' => ['SUM(tax_percentage) as total_tax'],
                                    'conditions' => ['product_id' => $sku_id, 'status' => 1]
                                ]);

                            }
                            $total_tax = isset($product_taxes[0][0]['total_tax'])
                                ? round($product_taxes[0][0]['total_tax'], 2)
                                : 0;

                            // $total_tax = isset($product_taxes[0][0]['total_tax']) ? round($product_taxes[0][0]['total_tax'], 2) : 0;
                            $tax_value = round(($approved_price * $qty) * ($total_tax / 100), 2);
                            $skuamount = round(($approved_price * $qty) + $tax_value, 2);
                            
                            // Update purchase order item
                            $this->Inventory_indent_items->updateAll(
                                ['status' => 4, 'sync' => 0, 'local_sync' => 0, 'stockin_quantity' => $qty,'stockin_tax_value' => $tax_value,
                                'stockin_amount' => $skuamount],
                                ['consignment_id' => $poid, 'sku_id' => $sku_id, 'status'=> 3]
                            );
                            
                            $amount += ($skuamount);
                            
                            //stock ledger entry semifinished
                            if ($item_type == 'semifinished') {
                                
                                $ledgerprevdetails = $this->Stock_ledger->find('first', [
                                    'conditions' => ['sku_id' => $sku_id,'type'=>'OUTLET','item_type'=>'semifinished','store_id'=>$location_id],
                                    'fields' => ['actual_balance'],
                                    'order' => ['id DESC'],
                                    'recursive' => -1
                                ]);
                                
                                $ledger_prev_quantity = !empty($ledgerprevdetails['Stock_ledger']['actual_balance']) 
                                    ? $ledgerprevdetails['Stock_ledger']['actual_balance'] 
                                    : 0;
                                
                                $newQuantity = $qty + $ledger_prev_quantity;
                                
                                $ledgerData['Stock_ledger'] = [
                                        'store_id'        => $location_id,
                                        'sku_id'          => $sku_id,
                                        'sku_name'        => $sku['sku_name'],
                                        'sku_price'       => $approved_price,
                                        'unit_id'         => $sku['sku_unit'],
                                        'unit_name'       => $sku['sku_unit_name'],
                                        'record_id'       => $txnid,
                                        'record_type'     => 'stockin',
                                        'quantity'        => $qty,
                                        'balance_stock'   => $newQuantity,
                                        'actual_balance'  => $newQuantity,
                                        'quantity_type'   => 'credit',
                                        'type'            => 'OUTLET',
                                        'item_type'       => 'semifinished',
                                        'created'         => date('Y-m-d H:i:s')
                                ];
                				if($this->Stock_ledger->saveAll($ledgerData['Stock_ledger'])){
                				
                				    //semis semi update
                    				$ingredients = $this->Inventory_linking->find('all', [
                                        'conditions' => ['parent_id' => $sku_id,'parent_type' => 'Semifinished','component_type' => 'Semifinished', 'status' => 1]
                                    ]);
                                    // print_r($ingredients);exit;
                                    foreach ($ingredients as $ingredient) {
                                        $linked_sku_id = $ingredient['Inventory_linking']['component_id'];
                                        $sku_quantity= $ingredient['Inventory_linking']['quantity']*$qty;
                                            
                                        $ledgerprevdetails = $this->Stock_ledger->find('first', [
                                            'conditions' => ['sku_id' => $linked_sku_id,'store_id' =>$location_id,'item_type'=>'Semifinished' ,'type' =>'OUTLET' ],
                                            'fields' => ['actual_balance'],
                                            'order' => ['id DESC'],
                                            'recursive' => -1
                                        ]);
                                        
                                        $ledger_prev_quantity = !empty($ledgerprevdetails['Stock_ledger']['actual_balance']) 
                                            ? $ledgerprevdetails['Stock_ledger']['actual_balance'] 
                                            : 0;
                                        
                                        $newQuantity = $ledger_prev_quantity+$sku_quantity;
                                        
                                        $semi = $this->Inventory_semifinished->find('first', [
                                            'conditions' => ['Inventory_semifinished.id' => $sku_id],
                                            'recursive' => -1
                                        ]);
                                        
                                        $ledgerData['Stock_ledger'] = [
                                                'store_id'        => $location_id,
                                                'sku_id'          => $ingredient['Inventory_linking']['component_id'],
                                                'sku_name'        => $ingredient['Inventory_linking']['component_name'],
                                                'sku_price'       => $semi['Inventory_semifinished']['price'],
                                                'unit_id'         => $ingredient['Inventory_linking']['component_unit'],
                                                'unit_name'       => $ingredient['Inventory_linking']['component_unit_name'],
                                                'record_id'       => $txnid,
                                                'record_type'     => 'stockin',
                                                'quantity'        => $sku_quantity,
                                                'balance_stock'   => $newQuantity,
                                                'actual_balance'  => $newQuantity,
                                                'quantity_type'   => 'credit',
                                                'item_type'       => 'Semifinished',
                                                'type'            => 'OUTLET',
                                                'created'         => date('Y-m-d H:i:s')
                                        ];
                        				if(!$this->Stock_ledger->saveAll($ledgerData['Stock_ledger'])){
                                            throw new Exception('issue in linking ledger update!');
                                        }
                                    }
                                    
                                    //semis sku update
                                    $ingredients = $this->Inventory_linking->find('all', [
                                        'conditions' => ['parent_id' => $sku_id,'parent_type' => 'Semifinished','component_type' => 'Sku', 'status' => 1]
                                    ]);
                                    // print_r($ingredients);exit;
                                    foreach ($ingredients as $ingredient) {
                                        $linked_sku_id = $ingredient['Inventory_linking']['component_id'];
                                        $sku_quantity= $ingredient['Inventory_linking']['quantity']*$qty;
                                            
                                        $ledgerprevdetails = $this->Stock_ledger->find('first', [
                                            'conditions' => ['sku_id' => $linked_sku_id,'store_id' =>$location_id,'item_type'=>'SKU' ,'type' =>'OUTLET' ],
                                            'fields' => ['actual_balance'],
                                            'order' => ['id DESC'],
                                            'recursive' => -1
                                        ]);
                                        
                                        $ledger_prev_quantity = !empty($ledgerprevdetails['Stock_ledger']['actual_balance']) 
                                            ? $ledgerprevdetails['Stock_ledger']['actual_balance'] 
                                            : 0;
                                        
                                        $newQuantity = $ledger_prev_quantity+$sku_quantity;
                                        
                                        $sku = $this->Inventory_sku->find('first', [
                                            'conditions' => ['Inventory_sku.id' => $sku_id],
                                            'recursive' => -1
                                        ]);
                                        
                                        $ledgerData['Stock_ledger'] = [
                                                'store_id'        => $location_id,
                                                'sku_id'          => $ingredient['Inventory_linking']['component_id'],
                                                'sku_name'        => $ingredient['Inventory_linking']['component_name'],
                                                'sku_price'       => $sku['Inventory_sku']['price'],
                                                'unit_id'         => $ingredient['Inventory_linking']['component_unit'],
                                                'unit_name'       => $ingredient['Inventory_linking']['component_unit_name'],
                                                'record_id'       => $txnid,
                                                'record_type'     => 'stockin',
                                                'quantity'        => $sku_quantity,
                                                'balance_stock'   => $newQuantity,
                                                'actual_balance'  => $newQuantity,
                                                'quantity_type'   => 'credit',
                                                'item_type'       => 'SKU',
                                                'type'            => 'OUTLET',
                                                'created'         => date('Y-m-d H:i:s')
                                        ];
                        				if(!$this->Stock_ledger->saveAll($ledgerData['Stock_ledger'])){
                                            throw new Exception('issue in sku ledger update!');
                                        }
                                    }
                				}else{
                				    throw new Exception('issue in linking sku ledger update!');
                                }
                            }else{
                                
                                $ledgerprevdetails = $this->Stock_ledger->find('first', [
                                    'conditions' => ['sku_id' => $sku_id,'type'=>'OUTLET','item_type'=>'SKU','store_id'=>$location_id],
                                    'fields' => ['actual_balance'],
                                    'order' => ['id DESC'],
                                    'recursive' => -1
                                ]);
                                
                                $ledger_prev_quantity = !empty($ledgerprevdetails['Stock_ledger']['actual_balance']) 
                                    ? $ledgerprevdetails['Stock_ledger']['actual_balance'] 
                                    : 0;
                                
                                $newQuantity = $qty + $ledger_prev_quantity;
                                
                                $ledgerData['Stock_ledger'] = [
                                        'store_id'        => $location_id,
                                        'sku_id'          => $sku_id,
                                        'sku_name'        => $sku['sku_name'],
                                        'sku_price'       => $approved_price,
                                        'unit_id'         => $sku['sku_unit'],
                                        'unit_name'       => $sku['sku_unit_name'],
                                        'record_id'       => $txnid,
                                        'record_type'     => 'stockin',
                                        'quantity'        => $qty,
                                        'balance_stock'   => $newQuantity,
                                        'actual_balance'  => $newQuantity,
                                        'quantity_type'   => 'credit',
                                        'type'            => 'OUTLET',
                                        'item_type'       => 'SKU',
                                        'created'         => date('Y-m-d H:i:s')
                                ];
                				if(!$this->Stock_ledger->saveAll($ledgerData['Stock_ledger'])){
                				    throw new Exception('issue in ledger update!');
                				}
                            }
                        }
                    }
                    
                    $this->Inventory_indent->updateAll(
                        [
                            'stockin_id'     => "'$txnid'",
                            'status'         => 4,
                            'stockin_date'   => "'$today'",
                            'invoice_no'     => "'$invoice'",
                            'stockin_amount' => "'$amount'",
                            'sync'           => 0,
                            'local_sync'     => 0
                        ],
                        [
                            'consignment_id' => $poid,
                            'status' => 3
                        ]
                    );
            
                    $response = ['status'=>true,'message'=>'success'];

                }elseif($type=='po'){
                    $indents = $this->Inventory_purchase_order->find('first', [
                        'conditions' => ['p_o_id' => $poid, 'status' => 3]
                    ]);
                    
                    if (empty($indents)) {
                        throw new Exception('Invalid PO!');
                    }
                    
                    $invidcheck = $this->Inventory_purchase_order->find('first', [
                        'conditions' => ['invoice_no' => $invoice, 'status !=' => 0]
                    ]);
                    
                    if (!empty($invidcheck)) {
                        throw new Exception('Invoice No already exist!');
                    }

                    $store_id=$indents['Inventory_purchase_order']['location_id'];
                    $name=$indents['Inventory_purchase_order']['location_name'];
                    // Generate unique stockin_id
                    do {
                        $txnid = rand(1, 9);
                        for ($i = 0; $i < 10; $i++) {
                            $txnid .= rand(0, 9);
                        }
            
                        $existing = $this->Inventory_purchase_order->find('first', [
                            'conditions' => ['stockin_id' => $txnid]
                        ]);
                    } while ($existing);
            
                    $stockin_skus = $this->Inventory_purchase_order_item->find('all', [
                        'conditions' => ['p_o_id' => $poid,'status' => 3]
                    ]);
                    
                    $amount=0;
                    foreach ($stockin_skus as $stockin_sku) {
                        $sku = $stockin_sku['Inventory_purchase_order_item'];
                        $sku_id = $sku['sku_id'];
                        
                        if (isset($quantities[$sku_id])) {
                            $qty = floatval($quantities[$sku_id]);
                            
                            $price = $sku['sku_price'];
                            
                            $product_taxes = $this->Inventory_sku_tax->find('all', [
                                'fields' => ['SUM(tax_percentage) as total_tax'],
                                'conditions' => ['product_id' => $sku_id, 'status' => 1]
                            ]);
                            
                            $total_tax = isset($product_taxes[0][0]['total_tax'])
                                ? round($product_taxes[0][0]['total_tax'], 2)
                                : 0;
                            // $product_taxes=$this->Inventory_sku_tax->query("select SUM(tax_percentage) as total_tax from inventory_sku_tax where product_id='$sku_id' and status=1 ");
                            // $total_tax=round($product_taxes['0']['0']['total_tax'],2);
                            $tax_value = round(($price*$qty) * ($total_tax / 100),2);
                            $skuamount=round(($price*$qty) + $tax_value,2);
                            
                            // Update purchase order item
                            $this->Inventory_purchase_order_item->updateAll(
                                ['status' => 4, 'sync' => 0, 'local_sync' => 0, 'stockin_quantity' => $qty, 'stockin_total_tax_value' => $tax_value, 'stockin_amount' => $skuamount],
                                ['p_o_id' => $poid, 'sku_id' => $sku_id, 'status' => 3]
                            );
                            $amount=$amount+$skuamount;
                            
                            $ledgerprevdetails = $this->Stock_ledger->find('first', [
                                'conditions' => ['sku_id' => $sku_id,'type'=>'OUTLET','item_type'=>'SKU','store_id'=>$store_id],
                                'fields' => ['actual_balance'],
                                'order' => ['id DESC'],
                                'recursive' => -1
                            ]);
                            
                            $ledger_prev_quantity = !empty($ledgerprevdetails['Stock_ledger']['actual_balance']) 
                                ? $ledgerprevdetails['Stock_ledger']['actual_balance'] 
                                : 0;
                            
                            $newQuantity = $qty + $ledger_prev_quantity;
                            
                            $ledgerData['Stock_ledger'] = [
                                    'store_id'        => $store_id,
                                    'sku_id'          => $sku_id,
                                    'sku_name'        => $sku['sku_name'],
                                    'sku_price'       => $sku['sku_price'],
                                    'unit_id'         => $sku['sku_unit'],
                                    'unit_name'       => $sku['sku_unit_name'],
                                    'record_id'       => $txnid,
                                    'record_type'     => 'stockin',
                                    'quantity'        => $qty,
                                    'balance_stock'   => $newQuantity,
                                    'actual_balance'  => $newQuantity,
                                    'quantity_type'   => 'credit',
                                    'type'            => 'OUTLET',
                                    'sync'            =>0,
                                    'local_sync'      =>0,
                                    'created'         => date('Y-m-d H:i:s')
                            ];
            				if(!$this->Stock_ledger->saveAll($ledgerData['Stock_ledger'])){
            				    throw new Exception('issue in po ledger update!');
            				}
                            
                        }
                    }
                    
                    $amount=$charge+$amount;
                    // Update purchase order
                    // $this->Inventory_purchase_order->query("UPDATE inventory_purchase_order SET 
                    //     stockin_id = '$txnid',
                    //     status = 4,
                    //     stockin_on = '$today',
                    //     stockin_by_id = $store_id,
                    //     stockin_by_name = '$name',
                    //     stockin_charge = '$charge',
                    //     stockin_amount = '$amount',
                    //     invoice_no = '$invoice',
                    //     sync = 0,
                    //     local_sync = 0
                    //     WHERE p_o_id = '$poid' and status=3");
                    
                    $this->Inventory_purchase_order->updateAll(
                    [
                        'stockin_id' => "'$txnid'",
                        'status' => 4,
                        'stockin_on' => "'$today'",
                        'stockin_by_id' => $store_id,
                        'stockin_by_name' => "'$name'",
                        'stockin_charge' => "'$charge'",
                        'stockin_amount' => "'$amount'",
                        'invoice_no' => "'$invoice'",
                        'sync' => 0,
                        'local_sync' => 0
                    ],
                    [
                        'p_o_id' => $poid,
                        'status' => 3
                    ]
                    );

                    
                    $response = ['status'=>true,'message'=>'success'];
                }
                
                $dataSource->commit();
                
                echo json_encode($response);
                return;
            } catch (Exception $e) {
                $dataSource->rollback();
            
                echo json_encode(['status'=>false,'message'=>$e->getMessage()]);
                return;
            }
        }
    }
    */
    
    /*
    public function stockin()
    {
        if (!$this->Session->check('Auth.Location.id')) {
            return $this->redirect(['controller' => 'dashboard', 'action' => 'login']);
        }
    
        $authUser = $this->Session->read('Auth.Location');
        $userLocationId = $authUser['id'] ?? 0;
    
        $this->layout = "users";
        $id = $_POST['po'] ?? null;
        $this->set(compact('id'));
        
        $newconditions='';
        if ($userRole !== 'admin') {
            $newconditions .= " location_id = '$userLocationId'";
        }
        $submitted_indents = $this->Inventory_indent->query("
            SELECT count(id) as total FROM inventory_indent 
            WHERE $newconditions and status IN (1)
        ");
        $submitted_indent=$submitted_indents[0][0]['total'];
        
        $approved_indents = $this->Inventory_indent->query("
            SELECT count(id) as total FROM inventory_indent 
            WHERE $newconditions AND status = 2 
        ");
        $approved_indent=$approved_indents[0][0]['total'];
        
        $processed_indents = $this->Inventory_indent->query("
            SELECT count(id) as total FROM inventory_indent 
            WHERE $newconditions AND status = 3 
        ");
        $processed_indent=$processed_indents[0][0]['total'];
        
        $stockedin_indents = $this->Inventory_indent->query("
            SELECT count(id) as total FROM inventory_indent 
            WHERE $newconditions AND status = 4 
        ");
        $stockedin_indent=$stockedin_indents[0][0]['total'];
        
        $this->set(compact('submitted_indent','approved_indent','processed_indent','stockedin_indent'));
        
        if ($id) {
            // Fetch PO
            if($_POST['data']['Search']['type']==0){
                $poQuery = "SELECT * FROM inventory_indent WHERE consignment_id = '$id'";
                $indents = $this->Inventory_indent->query($poQuery);
                    
                if (empty($indents)) {
                    $this->set('po_error', 'Consignment Not Found'); // trigger JS alert
                    return;
                }
                $po = $indents[0]['inventory_indent'];
                
                if ($po['status'] == 4) {
                    $this->set('po_error', 'Consignment Already Stocked IN'); // trigger JS alert
                    return;
                }
                
                if ($po['status'] != 3) {
                    $this->set('po_error', 'Consignment is not Proccessed'); // trigger JS alert
                    return;
                }
                
                if ($userRole != 'admin' && $po['location_id'] != $userLocationId) {
                    $this->set('po_error', 'Location Not Found'); // trigger JS alert
                    return;
                }
                
                $items = $this->Inventory_indent_items->query(
                    "SELECT * FROM inventory_indent_items WHERE consignment_id = '$id' AND status = 3"
                );
                
            }else{
                $poQuery = "SELECT * FROM inventory_purchase_order WHERE p_o_id = '$id' and location_id='$userLocationId'";
                $indents = $this->Inventory_purchase_order->query($poQuery);
                
                if (empty($indents)) {
                    $this->set('po_error', 'PO Not Found or not available for this location'); // trigger JS alert
                    return;
                }
                $po = $indents[0]['inventory_purchase_order'];
                
                if ($po['status'] == 4 && $po['is_stockedin'] == 1) {
                    $this->set('po_error', 'PO Already Stocked IN'); // trigger JS alert
                    return;
                }
                
                if ($po['status'] != 3 && $po['status'] != 4) {
                    $this->set('po_error', 'PO is not Proccessed'); // trigger JS alert
                    return;
                }
                
                $items = $this->Inventory_indent_items->query("SELECT * 
                    FROM inventory_purchase_order_items 
                    WHERE p_o_id = '$id' 
                    AND (
                            status = 3 
                         OR (status = 4 AND is_stockedin = 0)
                        
                    )"
                );
                    
            }
    
            $this->set(compact('indents', 'items'));
        }
    }

	public function invoice_id_check() 
    {
        $this->autoRender = false; // Disable view rendering
    
        if ($this->request->is('post')) {
            $poid = $this->request->data['_id'];
            $invoice = trim($this->request->data['_invoice']);
            
            $invoiceidchk = $this->Inventory_indent->find('first', [
                'conditions' => ['consignment_id' => $poid,'indent_id' => $invoice]
            ]);
            
            if(!empty($invoiceidchk)){
                echo json_encode(['status' => true]);
            }else{
                echo json_encode(['status' => false,'message' => 'Invalid Invoice No']);
            }
        }
    }
    
    public function stockin_po() 
    {
        $this->autoRender = false; // Disable view rendering 
    
        if ($this->request->is('post')) {
            $poid = $this->request->data['_id'];
            $invoice = trim($this->request->data['_invoice']);
            $quantities = $this->request->data['_quantities']; // associative array: sku_id => quantity
            $item_types = $this->request->data['_item_type']; 
            $charge = $this->request->data['_charge'] ?? 0; 
            $type = $this->request->data['_type']; 

            $discountTypes  = $this->request->data['_discount_types']  ?? [];
            $discountValues = $this->request->data['_discount_values'] ?? [];

            $today = date('Y-m-d H:i:s');
            
            if (empty($poid)) {
                echo json_encode(['status'=>false,'message'=>'Invalid PO/Consignment ID']);
                return;
            }
            
            if (empty($invoice)) {
                echo json_encode(['status'=>false,'message'=>'Invoice number required']);
                return;
            }
            
            $dataSource = $this->Inventory_indent->getDataSource();
            $dataSource->begin();
            
            try {
                if (empty($quantities) || !is_array($quantities)) {
                    throw new Exception('Invalid quantity data');
                }

                if($type=='consignment'){
                    $invoiceidchk = $this->Inventory_indent->find('first', [
                        'conditions' => ['consignment_id' => $poid,'indent_id' => $invoice]
                    ]);
                    
                    if(empty($invoiceidchk)){
                        throw new Exception('Something went wrong for this indent...!');
                    }
                    
                    $amount = 0;
                    // Generate unique stockin_id
                    do {
                        $txnid = rand(1, 9);
                        for ($i = 0; $i < 10; $i++) {
                            $txnid .= rand(0, 9);
                        }
            
                        $existing = $this->Inventory_indent->find('first', [
                            'conditions' => ['stockin_id' => $txnid]
                        ]);
                    } while ($existing);
            
                    $location_id = $this->Session->read('Auth.Location.id');
                    
                    $stockin_skus = $this->Inventory_indent_items->find('all', [
                        'conditions' => ['consignment_id' => $poid,'status' => 3]
                    ]);
                    
                    if(empty($stockin_skus)){
                        throw new Exception('No item found for this...!');
                    }
                    
                    foreach ($stockin_skus as $stockin_sku) {
                        $sku = $stockin_sku['Inventory_indent_items'];
                        $sku_id = $sku['sku_id'];
                        $approved_price = $sku['approved_price'];
                        
                        if (isset($quantities[$sku_id])) {
                            $qty = floatval($quantities[$sku_id]);
                            $item_type = isset($item_types[$sku_id]) ? $item_types[$sku_id] : 'sku';
                            
                            if ($item_type == 'semifinished') {
                                $product_taxes = $this->Inventory_semifinished_tax->find('all', [
                                    'fields' => ['SUM(tax_percentage) as total_tax'],
                                    'conditions' => ['product_id' => $sku_id, 'status' => 1]
                                ]);
                            } else {
                                $product_taxes = $this->Inventory_sku_tax->find('all', [
                                    'fields' => ['SUM(tax_percentage) as total_tax'],
                                    'conditions' => ['product_id' => $sku_id, 'status' => 1]
                                ]);
                            }
                            $total_tax = isset($product_taxes[0][0]['total_tax'])
                                ? round($product_taxes[0][0]['total_tax'], 2)
                                : 0;

                            $tax_value = round(($approved_price * $qty) * ($total_tax / 100), 2);
                            $skuamount = round(($approved_price * $qty) + $tax_value, 2);
                            
                            // Update purchase order item
                            $this->Inventory_indent_items->updateAll(
                                ['status' => 4, 'sync' => 0, 'local_sync' => 0, 'stockin_quantity' => $qty,'stockin_tax_value' => $tax_value,
                                'stockin_amount' => $skuamount],
                                ['consignment_id' => $poid, 'sku_id' => $sku_id, 'status'=> 3]
                            );
                            
                            $amount += ($skuamount);
                            
                            //stock ledger entry semifinished
                            if ($item_type == 'semifinished') {
                                
                                $ledgerprevdetails = $this->Stock_ledger->find('first', [
                                    'conditions' => ['sku_id' => $sku_id,'type'=>'OUTLET','item_type'=>'semifinished','store_id'=>$location_id],
                                    'fields' => ['actual_balance'],
                                    'order' => ['id DESC'],
                                    'recursive' => -1
                                ]);
                                
                                $ledger_prev_quantity = !empty($ledgerprevdetails['Stock_ledger']['actual_balance']) 
                                    ? $ledgerprevdetails['Stock_ledger']['actual_balance'] 
                                    : 0;
                                
                                $newQuantity = $qty + $ledger_prev_quantity;
                                
                                $ledgerData['Stock_ledger'] = [
                                        'store_id'        => $location_id,
                                        'sku_id'          => $sku_id,
                                        'sku_name'        => $sku['sku_name'],
                                        'sku_price'       => $approved_price,
                                        'unit_id'         => $sku['sku_unit'],
                                        'unit_name'       => $sku['sku_unit_name'],
                                        'record_id'       => $txnid,
                                        'record_type'     => 'stockin',
                                        'quantity'        => $qty,
                                        'balance_stock'   => $newQuantity,
                                        'actual_balance'  => $newQuantity,
                                        'quantity_type'   => 'credit',
                                        'type'            => 'OUTLET',
                                        'item_type'       => 'semifinished',
                                        'created'         => date('Y-m-d H:i:s')
                                ];
                				if($this->Stock_ledger->saveAll($ledgerData['Stock_ledger'])){
                				
                				    //semis semi update
                    				$ingredients = $this->Inventory_linking->find('all', [
                                        'conditions' => ['parent_id' => $sku_id,'parent_type' => 'Semifinished','component_type' => 'Semifinished', 'status' => 1]
                                    ]);
                                    foreach ($ingredients as $ingredient) {
                                        $linked_sku_id = $ingredient['Inventory_linking']['component_id'];
                                        $sku_quantity= $ingredient['Inventory_linking']['quantity']*$qty;
                                            
                                        $ledgerprevdetails = $this->Stock_ledger->find('first', [
                                            'conditions' => ['sku_id' => $linked_sku_id,'store_id' =>$location_id,'item_type'=>'Semifinished' ,'type' =>'OUTLET' ],
                                            'fields' => ['actual_balance'],
                                            'order' => ['id DESC'],
                                            'recursive' => -1
                                        ]);
                                        
                                        $ledger_prev_quantity = !empty($ledgerprevdetails['Stock_ledger']['actual_balance']) 
                                            ? $ledgerprevdetails['Stock_ledger']['actual_balance'] 
                                            : 0;
                                        
                                        $newQuantity = $ledger_prev_quantity+$sku_quantity;
                                        
                                        $semi = $this->Inventory_semifinished->find('first', [
                                            'conditions' => ['Inventory_semifinished.id' => $sku_id],
                                            'recursive' => -1
                                        ]);
                                        
                                        $ledgerData['Stock_ledger'] = [
                                                'store_id'        => $location_id,
                                                'sku_id'          => $ingredient['Inventory_linking']['component_id'],
                                                'sku_name'        => $ingredient['Inventory_linking']['component_name'],
                                                'sku_price'       => $semi['Inventory_semifinished']['price'],
                                                'unit_id'         => $ingredient['Inventory_linking']['component_unit'],
                                                'unit_name'       => $ingredient['Inventory_linking']['component_unit_name'],
                                                'record_id'       => $txnid,
                                                'record_type'     => 'stockin',
                                                'quantity'        => $sku_quantity,
                                                'balance_stock'   => $newQuantity,
                                                'actual_balance'  => $newQuantity,
                                                'quantity_type'   => 'credit',
                                                'item_type'       => 'Semifinished',
                                                'type'            => 'OUTLET',
                                                'created'         => date('Y-m-d H:i:s')
                                        ];
                        				if(!$this->Stock_ledger->saveAll($ledgerData['Stock_ledger'])){
                                            throw new Exception('issue in linking ledger update!');
                                        }
                                    }
                                    
                                    //semis sku update
                                    $ingredients = $this->Inventory_linking->find('all', [
                                        'conditions' => ['parent_id' => $sku_id,'parent_type' => 'Semifinished','component_type' => 'Sku', 'status' => 1]
                                    ]);
                                    foreach ($ingredients as $ingredient) {
                                        $linked_sku_id = $ingredient['Inventory_linking']['component_id'];
                                        $sku_quantity= $ingredient['Inventory_linking']['quantity']*$qty;
                                            
                                        $ledgerprevdetails = $this->Stock_ledger->find('first', [
                                            'conditions' => ['sku_id' => $linked_sku_id,'store_id' =>$location_id,'item_type'=>'SKU' ,'type' =>'OUTLET' ],
                                            'fields' => ['actual_balance'],
                                            'order' => ['id DESC'],
                                            'recursive' => -1
                                        ]);
                                        
                                        $ledger_prev_quantity = !empty($ledgerprevdetails['Stock_ledger']['actual_balance']) 
                                            ? $ledgerprevdetails['Stock_ledger']['actual_balance'] 
                                            : 0;
                                        
                                        $newQuantity = $ledger_prev_quantity+$sku_quantity;
                                        
                                        $sku = $this->Inventory_sku->find('first', [
                                            'conditions' => ['Inventory_sku.id' => $sku_id],
                                            'recursive' => -1
                                        ]);
                                        
                                        $ledgerData['Stock_ledger'] = [
                                                'store_id'        => $location_id,
                                                'sku_id'          => $ingredient['Inventory_linking']['component_id'],
                                                'sku_name'        => $ingredient['Inventory_linking']['component_name'],
                                                'sku_price'       => $sku['Inventory_sku']['price'],
                                                'unit_id'         => $ingredient['Inventory_linking']['component_unit'],
                                                'unit_name'       => $ingredient['Inventory_linking']['component_unit_name'],
                                                'record_id'       => $txnid,
                                                'record_type'     => 'stockin',
                                                'quantity'        => $sku_quantity,
                                                'balance_stock'   => $newQuantity,
                                                'actual_balance'  => $newQuantity,
                                                'quantity_type'   => 'credit',
                                                'item_type'       => 'SKU',
                                                'type'            => 'OUTLET',
                                                'created'         => date('Y-m-d H:i:s')
                                        ];
                        				if(!$this->Stock_ledger->saveAll($ledgerData['Stock_ledger'])){
                                            throw new Exception('issue in sku ledger update!');
                                        }
                                    }
                				}else{
                				    throw new Exception('issue in linking sku ledger update!');
                                }
                            }else{
                                
                                $ledgerprevdetails = $this->Stock_ledger->find('first', [
                                    'conditions' => ['sku_id' => $sku_id,'type'=>'OUTLET','item_type'=>'SKU','store_id'=>$location_id],
                                    'fields' => ['actual_balance'],
                                    'order' => ['id DESC'],
                                    'recursive' => -1
                                ]);
                                
                                $ledger_prev_quantity = !empty($ledgerprevdetails['Stock_ledger']['actual_balance']) 
                                    ? $ledgerprevdetails['Stock_ledger']['actual_balance'] 
                                    : 0;
                                
                                $newQuantity = $qty + $ledger_prev_quantity;
                                
                                $ledgerData['Stock_ledger'] = [
                                        'store_id'        => $location_id,
                                        'sku_id'          => $sku_id,
                                        'sku_name'        => $sku['sku_name'],
                                        'sku_price'       => $approved_price,
                                        'unit_id'         => $sku['sku_unit'],
                                        'unit_name'       => $sku['sku_unit_name'],
                                        'record_id'       => $txnid,
                                        'record_type'     => 'stockin',
                                        'quantity'        => $qty,
                                        'balance_stock'   => $newQuantity,
                                        'actual_balance'  => $newQuantity,
                                        'quantity_type'   => 'credit',
                                        'type'            => 'OUTLET',
                                        'item_type'       => 'SKU',
                                        'created'         => date('Y-m-d H:i:s')
                                ];
                				if(!$this->Stock_ledger->saveAll($ledgerData['Stock_ledger'])){
                				    throw new Exception('issue in ledger update!');
                				}
                            }
                        }
                    }
                    
                    $this->Inventory_indent->updateAll(
                        [
                            'stockin_id'     => "'$txnid'",
                            'status'         => 4,
                            'stockin_date'   => "'$today'",
                            'invoice_no'     => "'$invoice'",
                            'stockin_amount' => "'$amount'",
                            'sync'           => 0,
                            'local_sync'     => 0
                        ],
                        [
                            'consignment_id' => $poid,
                            'status' => 3
                        ]
                    );
            
                    $response = ['status'=>true,'message'=>'success'];

                }
				elseif($type=='po'){
                    $indents = $this->Inventory_purchase_order->find('first', [
                        'conditions' => [
                            'p_o_id' => $poid,
                            'OR' => [
                                ['status' => 3],
                                [
                                    'status' => 4,
                                    'is_stockedin' => 0
                                ]
                            ]
                        ]
                    ]);
                    
                    if (empty($indents)) {
                        throw new Exception('Invalid PO!');
                    }
                    
                    $invidcheck = $this->Inventory_purchase_order->find('first', [
                        'conditions' => ['invoice_no' => $invoice, 'status !=' => 0]
                    ]);
                    
                    if (!empty($invidcheck)) {
                        throw new Exception('Invoice No already exist!');
                    }

                    $store_id      = $indents['Inventory_purchase_order']['location_id'];
                    $name          = $indents['Inventory_purchase_order']['location_name'];
                    $location_type = $indents['Inventory_purchase_order']['location_type'];
                    $vendor_id     = $indents['Inventory_purchase_order']['vendor_id'];
                    $vendor_name   = $indents['Inventory_purchase_order']['vendor_name'];
                    $indent_id     = $indents['Inventory_purchase_order']['internal_indent_id'];
                    $stockin_charge = $indents['Inventory_purchase_order']['stockin_charge'];

                    // Generate unique stockin_id
                    do {
                        $txnid = rand(1, 9);
                        for ($i = 0; $i < 10; $i++) {
                            $txnid .= rand(0, 9);
                        }
            
                        $existing = $this->Inventory_purchase_order->find('first', [
                            'conditions' => ['stockin_id' => $txnid]
                        ]);
                    } while ($existing);
            
                    $stockin_skus = $this->Inventory_purchase_order_item->find('all', [
                        'conditions' => [
                            'p_o_id' => $poid,
                            'OR' => [
                                ['status' => 3],
                                [
                                    'status' => 4,
                                    'is_stockedin' => 0
                                ]
                            ]
                        ]
                    ]);
                    
                    $amount         = 0;
                    $stockinamount  = 0;
                    $total_discount = 0;

                    foreach ($stockin_skus as $stockin_sku) {
                        $sku    = $stockin_sku['Inventory_purchase_order_item'];
                        $sku_id = $sku['sku_id'];
                        
                        if (isset($quantities[$sku_id]) && $quantities[$sku_id] != 0) {
                            $stockinqty = floatval($quantities[$sku_id]);

                            $processed_qty    = floatval($sku['processed_quantity']);
                            $prev_stockin_qty = floatval($sku['stockin_quantity']);
                            $max_allowed      = $processed_qty - $prev_stockin_qty;
                            if ($max_allowed <= 0) continue;
                            if ($stockinqty > $max_allowed) $stockinqty = $max_allowed;

                            $qty = $prev_stockin_qty + $stockinqty;

                            // UPDATED: use edited price if provided
                            $price = isset($prices[$sku_id]) ? floatval($prices[$sku_id]) : floatval($sku['sku_price']);
                            
                            $product_taxes = $this->Inventory_sku_tax->find('all', [
                                'fields'     => ['SUM(tax_percentage) as total_tax'],
                                'conditions' => ['product_id' => $sku_id, 'status' => 1]
                            ]);
                            
                            $total_tax = isset($product_taxes[0][0]['total_tax'])
                                ? round($product_taxes[0][0]['total_tax'], 2)
                                : 0;

                            $discountType = isset($discountTypes[$sku_id]) ? $discountTypes[$sku_id] : 'flat';
                            $rawDiscount  = isset($discountValues[$sku_id]) ? floatval($discountValues[$sku_id]) : 0;

                            $base_stockin = $price * $stockinqty;
                            if ($discountType === 'percentage') {
                                $discountAmount = round(($base_stockin * $rawDiscount) / 100, 2);
                            } else {
                                $discountAmount = $rawDiscount;
                            }
                            if ($discountAmount > $base_stockin) $discountAmount = $base_stockin;

                            $stockin_after_discount = $base_stockin - $discountAmount;
							$unit_price_after_dis = $stockin_after_discount / $stockinqty;
                            $stockintax_value       = round($stockin_after_discount * ($total_tax / 100), 2);
                            $stockinskuamount       = round($stockin_after_discount + $stockintax_value, 2);

                            $base_cumulative           = $price * $qty;
                            $discount_per_unit         = ($stockinqty > 0) ? ($discountAmount / $stockinqty) : 0;
                            $cumulative_discount       = round($discount_per_unit * $qty, 2);
                            $cumulative_after_discount = $base_cumulative - $cumulative_discount;
                            $tax_value  = round($cumulative_after_discount * ($total_tax / 100), 2);
                            $skuamount  = round($cumulative_after_discount + $tax_value, 2);
                            
                            $is_stockedin = 1;
                            if ($processed_qty > $qty) {
                                $is_stockedin = 0;
                            }
                            
                            $stockinData['Inventory_stockin_item'] = [
                                'stockin_id'              => $txnid,
                                'p_o_id'                  => $poid,
                                'sku_id'                  => $sku_id,
                                'sku_name'                => $sku['sku_name'],
                                'sku_price'               => $unit_price_after_dis,
                                'sku_unit'                => $sku['sku_unit'],
                                'sku_unit_name'           => $sku['sku_unit_name'],
                                'stockin_quantity'        => $stockinqty,
                                'stockin_total_tax_value' => $stockintax_value,
                                'stockin_amount'          => $stockinskuamount,
                            ];
            				if(!$this->Inventory_stockin_item->saveAll($stockinData['Inventory_stockin_item'])){
            				    throw new Exception('issue in stockin item insert!');
            				}
            				
                            $this->Inventory_purchase_order_item->updateAll(
                                [
                                    'status'                  => 4,
                                    'sync'                    => 0,
                                    'local_sync'              => 0,
                                    'stockin_quantity'        => $qty,
                                    'stockin_total_tax_value' => $tax_value,
                                    'stockin_amount'          => $skuamount,
                                    'is_stockedin'            => $is_stockedin,
                                    // UPDATED: write to stockin_discount_* columns instead of sku_discount_*
                                    'stockin_discount'        => $rawDiscount,
                                    "stockin_discount_type"   => "'$discountType'",
                                    'stockin_discount_value'  => $discountAmount,
                                    // UPDATED: also save excise/VAT/TCS used during stockin
                                    'excise_per_unit'         => $excise_pu_s,
                                    'addl_excise_per_unit'    => $addl_excise_pu_s,
                                    'vat_percentage'          => $vat_pct_s,
                                    'tcs_percentage'          => $tcs_pct_s,
                                    'excise_duty_amt'         => $excise_line_cum,
                                    'addl_excise_duty_amt'    => $addl_excise_line_cum,
                                    'vat_amt'                 => $vat_cum,
                                    'tcs_amt'                 => $tcs_cum
                                ],
                                ['p_o_id' => $poid, 'sku_id' => $sku_id, 'status !=' => 0]
                            );

                            $amount        += $skuamount;
                            $stockinamount += $stockinskuamount;
                            $total_discount += $discountAmount;
                            
                            $ledgerprevdetails = $this->Stock_ledger->find('first', [
                                'conditions' => ['sku_id' => $sku_id,'type'=>'OUTLET','item_type'=>'SKU','store_id'=>$store_id],
                                'fields'     => ['actual_balance'],
                                'order'      => ['id DESC'],
                                'recursive'  => -1
                            ]);
                            
                            $ledger_prev_quantity = !empty($ledgerprevdetails['Stock_ledger']['actual_balance']) 
                                ? $ledgerprevdetails['Stock_ledger']['actual_balance'] 
                                : 0;
                            
                            $newQuantity = $stockinqty + $ledger_prev_quantity;
                            
                            $ledgerData['Stock_ledger'] = [
                                'store_id'      => $store_id,
                                'sku_id'        => $sku_id,
                                'sku_name'      => $sku['sku_name'],
                                'sku_price'     => $unit_price_after_dis,
                                'unit_id'       => $sku['sku_unit'],
                                'unit_name'     => $sku['sku_unit_name'],
                                'record_id'     => $txnid,
                                'record_type'   => 'stockin',
                                'quantity'      => round($stockinqty,2),
                                'balance_stock' => round($newQuantity,2),
                                'actual_balance'=> round($newQuantity,2),
                                'quantity_type' => 'credit',
                                'type'          => 'OUTLET',
                                'sync'          => 0,
                                'local_sync'    => 0,
                                'created'       => date('Y-m-d H:i:s')
                            ];
            				if(!$this->Stock_ledger->saveAll($ledgerData['Stock_ledger'])){
            				    throw new Exception('issue in po ledger update!');
            				}
                        }
                    }
                    
                    $amount         = $charge + $amount;
                    $stockin_charge = $stockin_charge + $charge;
                    $stockinamount  = $charge + $stockinamount;
                            
                    $pocompletioncheck = $this->Inventory_purchase_order_item->find('first', [
                        'conditions' => [
                            'p_o_id'      => $poid,
                            'is_stockedin'=> 0,
                            'status !='   => 0
                        ]
                    ]);
                    
                    $pois_stockedin = $pocompletioncheck ? 0 : 1;
                    
                    $stockinmainData['Inventory_stockin'] = [
                        'location_type'   => $location_type,
                        'vendor_id'       => $vendor_id,
                        'vendor_name'     => $vendor_name,
                        'indent_id'       => $indent_id,
                        'p_o_id'          => $poid,
                        'stockin_by_id'   => $store_id,
                        'stockin_by_name' => $name,
                        'stockin_charge'  => $charge,
                        'stockin_amount'  => $stockinamount,
                        'invoice_no'      => $invoice,
                        'stockin_id'      => $txnid,
                        'discount_value'  => $total_discount
                    ];
    				if(!$this->Inventory_stockin->saveAll($stockinmainData['Inventory_stockin'])){
    				    throw new Exception('issue in stockin insert!');
    				}
    				
                    $this->Inventory_purchase_order->updateAll(
                        [
                            'stockin_id'      => "'$txnid'",
                            'status'          => 4,
                            'stockin_on'      => "'$today'",
                            'stockin_by_id'   => $store_id,
                            'stockin_by_name' => "'$name'",
                            'stockin_charge'  => "'$stockin_charge'",
                            'stockin_amount'  => "'$amount'",
                            'invoice_no'      => "'$invoice'",
                            'is_stockedin'    => "'$pois_stockedin'",
                            // UPDATED: write to stockin_discount_value instead of discount_value
                            'stockin_discount_value' => "'$total_discount'",
                            'sync'            => 0,
                            'local_sync'      => 0
                        ],
                        [
                            'p_o_id'    => $poid,
                            'status !=' => 0
                        ]
                    );
                    
                    $response = ['status'=>true,'message'=>'success'];
                }
                
                $dataSource->commit();
                
                echo json_encode($response);
                return;
            } catch (Exception $e) {
                $dataSource->rollback();
            
                echo json_encode(['status'=>false,'message'=>$e->getMessage()]);
                return;
            }
        }
    }
    */
    
    public function stockin()
    {
        if (!$this->Session->check('Auth.Location.id')) {
            return $this->redirect(['controller' => 'dashboard', 'action' => 'login']);
        }
    
        $authUser = $this->Session->read('Auth.Location');
        $userLocationId = $authUser['id'] ?? 0;
        $userRole = $authUser['role'] ?? '';

        $this->layout = "users";
        
        /* =======================
           GET TYPE + ID (UPDATED)
        ======================= */
        $data = $this->request->data;
        
        $type = $data['Search']['type'] ?? null;
        $id   = ($type == 0)
            ? ($data['Search']['consignment_id'] ?? null)
            : ($data['Search']['po_id'] ?? null);
        $this->set(compact('id'));


        $newconditions = ($userRole !== 'admin') 
        ? " location_id = '$userLocationId'" 
        : "1=1";   // ✅ FIXED (was empty before)

        $submitted_indents = $this->Inventory_indent->query("
            SELECT count(id) as total FROM inventory_indent 
            WHERE $newconditions and status IN (1)
        ");
        $submitted_indent=$submitted_indents[0][0]['total'];
        
        $approved_indents = $this->Inventory_indent->query("
            SELECT count(id) as total FROM inventory_indent 
            WHERE $newconditions AND status = 2 
        ");
        $approved_indent=$approved_indents[0][0]['total'];
        
        $processed_indents = $this->Inventory_indent->query("
            SELECT count(id) as total FROM inventory_indent 
            WHERE $newconditions AND status = 3 
        ");
        $processed_indent=$processed_indents[0][0]['total'];
        
        $stockedin_indents = $this->Inventory_indent->query("
            SELECT count(id) as total FROM inventory_indent 
            WHERE $newconditions AND status = 4 
        ");
        $stockedin_indent=$stockedin_indents[0][0]['total'];
        
        /* =======================
           DROPDOWN DATA
        ======================= */
        $consignmentList = $this->Inventory_indent->find('list', [
            'fields' => ['consignment_id', 'consignment_id'],
            'conditions' => ['status' => 3,'location_id' => $userLocationId],
            'order' => ['id DESC']
        ]);

        $poData = $this->Inventory_purchase_order->find('all', [
            'fields' => ['p_o_id', 'vendor_name'],
            'conditions' => [
                'status' => [3, 4], // only processed
                'location_id' => $userLocationId,
                'NOT' => [
                    [
                        'status' => 4,
                        'is_stockedin' => 1 // exclude already stocked
                    ]
                ]],
                'order' => ['id DESC']
        ]);

        $poList = [];

        foreach ($poData as $po) {
            $po_id = $po['Inventory_purchase_order']['p_o_id']; // ✅ FIXED
            $vendor = $po['Inventory_purchase_order']['vendor_name'];

            $poList[$po_id] = $po_id . ' (' . $vendor . ')';
        }
        $this->set(compact('submitted_indent','approved_indent','processed_indent','stockedin_indent','consignmentList','poList'));
        
        if (!empty($id)) {
            // Fetch PO
            if ($type == 0) {
                $poQuery = "SELECT * FROM inventory_indent WHERE consignment_id = '$id'";
                $indents = $this->Inventory_indent->query($poQuery);
                    
                if (empty($indents)) {
                    $this->set('po_error', 'Consignment Not Found'); // trigger JS alert
                    return;
                }
                $po = $indents[0]['inventory_indent'];
                
                if ($po['status'] == 4) {
                    $this->set('po_error', 'Consignment Already Stocked IN'); // trigger JS alert
                    return;
                }
                
                if ($po['status'] != 3) {
                    $this->set('po_error', 'Consignment is not Processed'); // trigger JS alert
                    return;
                }
                
                if ($userRole != 'admin' && $po['location_id'] != $userLocationId) {
                    $this->set('po_error', 'Location Not Found'); // trigger JS alert
                    return;
                }
                
                $items = $this->Inventory_indent_items->query(
                    "SELECT * FROM inventory_indent_items WHERE consignment_id = '$id' AND status = 3"
                );
                
            }else{
                $poQuery = "SELECT * FROM inventory_purchase_order WHERE p_o_id = '$id' and location_id='$userLocationId'";
                $indents = $this->Inventory_purchase_order->query($poQuery);
                
                if (empty($indents)) {
                    $this->set('po_error', 'PO Not Found or not available for this location'); // trigger JS alert
                    return;
                }
                $po = $indents[0]['inventory_purchase_order'];
                
                if ($po['status'] == 4 && $po['is_stockedin'] == 1) {
                    $this->set('po_error', 'PO Already Stocked IN'); // trigger JS alert
                    return;
                }
                
                if ($po['status'] != 3 && $po['status'] != 4) {
                    $this->set('po_error', 'PO is not Processed'); // trigger JS alert
                    return;
                }
                
                // $items = $this->Inventory_purchase_order_item->query("SELECT * 
                //     FROM inventory_purchase_order_items 
                //     WHERE p_o_id = '$id' 
                //     AND (
                //             status = 3 
                //          OR (status = 4 AND is_stockedin = 0)
                        
                //     )"
                // );
                    
                $po_loc_id = intval($po['location_id']);
                $items = $this->Inventory_purchase_order_item->query("
                    SELECT inventory_purchase_order_items.*,
                           inventory_skus.excise_applicable,
                           inventory_skus.has_variable_tax,
                           COALESCE(ise_loc.excise_per_unit,      ise_base.excise_per_unit,      0) AS excise_per_unit,
                           COALESCE(ise_loc.addl_excise_per_unit, ise_base.addl_excise_per_unit, 0) AS addl_excise_per_unit,
                           COALESCE(ise_loc.vat_percentage,       ise_base.vat_percentage,       0) AS vat_percentage,
                           COALESCE(ise_loc.tcs_percentage,       ise_base.tcs_percentage,       0) AS tcs_percentage
                    FROM inventory_purchase_order_items
                    LEFT JOIN inventory_skus
                           ON inventory_skus.id = inventory_purchase_order_items.sku_id
                    LEFT JOIN inventory_sku_excise ise_loc
                           ON ise_loc.sku_id = inventory_purchase_order_items.sku_id
                          AND ise_loc.location_id = $po_loc_id AND ise_loc.status = 1
                    LEFT JOIN inventory_sku_excise ise_base
                           ON ise_base.sku_id = inventory_purchase_order_items.sku_id
                          AND ise_base.location_id = 0 AND ise_base.status = 1
                    WHERE inventory_purchase_order_items.p_o_id = '$id'
                    AND (inventory_purchase_order_items.status = 3
                      OR (inventory_purchase_order_items.status = 4 AND inventory_purchase_order_items.is_stockedin = 0))
                ");

            }
    
            $this->set(compact('indents', 'items'));
        }
    }
    
    public function invoice_id_check() 
    {
        $this->autoRender = false; // Disable view rendering
        
        /* =======================
           🔴 FIX 1: ONLY ALLOW POST
        ======================= */
        if (!$this->request->is('post')) {
            echo json_encode(['status' => false, 'message' => 'Invalid Request']);
            return;
        }
        
        /* =======================
           🔴 FIX 2: SAFE INPUT READ
        ======================= */
        $poid = trim($this->request->data['_id'] ?? '');
        $invoice = trim($this->request->data['_invoice'] ?? '');
    
        /* =======================
           🔴 FIX 3: VALIDATION
        ======================= */
        if (empty($poid) || empty($invoice)) {
            echo json_encode(['status' => false, 'message' => 'Missing Required Data']);
            return;
        }
        
        /* =======================
           🔴 FIX 4: QUERY
        ======================= */
        $invoiceidchk = $this->Inventory_indent->find('first', [
            'conditions' => [
                'consignment_id' => $poid,
                'indent_id' => $invoice
            ],
            'fields' => ['id'], // ✅ optimized (no need full row)
            'recursive' => -1   // ✅ performance
        ]);
    
        /* =======================
           RESPONSE
        ======================= */
        if (!empty($invoiceidchk)) {
            echo json_encode(['status' => true]);
        } else {
            echo json_encode([
                'status' => false,
                'message' => 'Invalid Invoice No'
            ]);
        }
    }
    
    /*
    public function stockin_po() 
    {
        $this->autoRender = false; // Disable view rendering 
    
        if ($this->request->is('post')) {
            $poid = $this->request->data['_id'];
            $invoice = trim($this->request->data['_invoice']);
            if (!preg_match('/^[A-Za-z0-9\-\/]+$/', $invoice)) {
                echo json_encode(['status'=>false,'message'=>'Invalid Invoice format']);
                return;
            }
            $quantities = $this->request->data['_quantities']; // associative array: sku_id => quantity
            $item_types = $this->request->data['_item_type']; 
            // $charge = $this->request->data['_charge'] ?? 0; 
            $charge = isset($this->request->data['_charge']) 
                ? floatval($this->request->data['_charge']) 
                : 0;
            
            if ($charge < 0) $charge = 0;
            $type = $this->request->data['_type']; 

            $discountTypes  = $this->request->data['_discount_types']  ?? [];
            $discountValues = $this->request->data['_discount_values'] ?? [];

            $today = date('Y-m-d H:i:s');
            
            if (empty($poid)) {
                echo json_encode(['status'=>false,'message'=>'Invalid PO/Consignment ID']);
                return;
            }
            
            if (empty($invoice)) {
                echo json_encode(['status'=>false,'message'=>'Invoice number required']);
                return;
            }
            
            $dataSource = $this->Inventory_indent->getDataSource();
            $dataSource->begin();
            
            try {
                if (empty($quantities) || !is_array($quantities)) {
                    throw new Exception('Invalid quantity data');
                }

                if($type=='consignment'){
                    $invoiceidchk = $this->Inventory_indent->find('first', [
                        'conditions' => ['consignment_id' => $poid,'indent_id' => $invoice]
                    ]);
                    
                    if(empty($invoiceidchk)){
                        throw new Exception('Something went wrong for this indent...!');
                    }
                    
                    $amount = 0;
                    // Generate unique stockin_id
                    do {
                        $txnid = rand(1, 9);
                        for ($i = 0; $i < 10; $i++) {
                            $txnid .= rand(0, 9);
                        }
            
                        $existing = $this->Inventory_indent->find('first', [
                            'conditions' => ['stockin_id' => $txnid]
                        ]);
                    } while ($existing);
            
                    $location_id = $this->Session->read('Auth.Location.id');
                    
                    $stockin_skus = $this->Inventory_indent_items->find('all', [
                        'conditions' => ['consignment_id' => $poid,'status' => 3]
                    ]);
                    
                    if(empty($stockin_skus)){
                        throw new Exception('No item found for this...!');
                    }
                    
                    foreach ($stockin_skus as $stockin_sku) {
                        $sku = $stockin_sku['Inventory_indent_items'];
                        $sku_id = $sku['sku_id'];
                        // UPDATED: use edited price if provided
                        $approved_price = isset($prices[$sku_id]) ? floatval($prices[$sku_id]) : floatval($sku['approved_price']);
                        
                        $stockinqty = floatval($quantities[$sku_id]);
                        
                        if ($stockinqty > 0) {
                        // if (isset($quantities[$sku_id])) {
                            $qty = floatval($quantities[$sku_id]);
                            $item_type = isset($item_types[$sku_id]) ? $item_types[$sku_id] : 'sku';
                            
                            if ($item_type == 'semifinished') {
                                $product_taxes = $this->Inventory_semifinished_tax->find('all', [
                                    'fields' => ['SUM(tax_percentage) as total_tax'],
                                    'conditions' => ['product_id' => $sku_id, 'status' => 1]
                                ]);
                            } else {
                                $product_taxes = $this->Inventory_sku_tax->find('all', [
                                    'fields' => ['SUM(tax_percentage) as total_tax'],
                                    'conditions' => ['product_id' => $sku_id, 'status' => 1]
                                ]);
                            }
                            $total_tax = isset($product_taxes[0][0]['total_tax'])
                                ? round($product_taxes[0][0]['total_tax'], 2)
                                : 0;

                            $tax_value = round(($approved_price * $qty) * ($total_tax / 100), 2);
                            $skuamount = round(($approved_price * $qty) + $tax_value, 2);
                            
                            // Update purchase order item
                            $this->Inventory_indent_items->updateAll(
                                ['status' => 4, 'sync' => 0, 'local_sync' => 0, 'stockin_quantity' => $qty,'stockin_tax_value' => $tax_value,
                                'stockin_amount' => $skuamount],
                                ['consignment_id' => $poid, 'sku_id' => $sku_id, 'status'=> 3]
                            );
                            
                            $amount += ($skuamount);
                            
                            //stock ledger entry semifinished
                            if ($item_type == 'semifinished') {
                                
                                $ledgerprevdetails = $this->Stock_ledger->find('first', [
                                    'conditions' => ['sku_id' => $sku_id,'type'=>'OUTLET','item_type'=>'semifinished','store_id'=>$location_id],
                                    'fields' => ['actual_balance'],
                                    'order' => ['id DESC'],
                                    'recursive' => -1
                                ]);
                                
                                $ledger_prev_quantity = !empty($ledgerprevdetails['Stock_ledger']['actual_balance']) 
                                    ? $ledgerprevdetails['Stock_ledger']['actual_balance'] 
                                    : 0;
                                
                                $newQuantity = $qty + $ledger_prev_quantity;
                                
                                $ledgerData['Stock_ledger'] = [
                                        'store_id'        => $location_id,
                                        'sku_id'          => $sku_id,
                                        'sku_name'        => $sku['sku_name'],
                                        'sku_price'       => $approved_price,
                                        'unit_id'         => $sku['sku_unit'],
                                        'unit_name'       => $sku['sku_unit_name'],
                                        'record_id'       => $txnid,
                                        'record_type'     => 'stockin',
                                        'quantity'        => $qty,
                                        'balance_stock'   => $newQuantity,
                                        'actual_balance'  => $newQuantity,
                                        'quantity_type'   => 'credit',
                                        'type'            => 'OUTLET',
                                        'item_type'       => 'semifinished',
                                        'created'         => date('Y-m-d H:i:s')
                                ];
                				if($this->Stock_ledger->saveAll($ledgerData['Stock_ledger'])){
                				
                				    //semis semi update
                    				$ingredients = $this->Inventory_linking->find('all', [
                                        'conditions' => ['parent_id' => $sku_id,'parent_type' => 'Semifinished','component_type' => 'Semifinished', 'status' => 1]
                                    ]);
                                    foreach ($ingredients as $ingredient) {
                                        $linked_sku_id = $ingredient['Inventory_linking']['component_id'];
                                        $sku_quantity= $ingredient['Inventory_linking']['quantity']*$qty;
                                            
                                        $ledgerprevdetails = $this->Stock_ledger->find('first', [
                                            'conditions' => ['sku_id' => $linked_sku_id,'store_id' =>$location_id,'item_type'=>'Semifinished' ,'type' =>'OUTLET' ],
                                            'fields' => ['actual_balance'],
                                            'order' => ['id DESC'],
                                            'recursive' => -1
                                        ]);
                                        
                                        $ledger_prev_quantity = !empty($ledgerprevdetails['Stock_ledger']['actual_balance']) 
                                            ? $ledgerprevdetails['Stock_ledger']['actual_balance'] 
                                            : 0;
                                        
                                        $newQuantity = $ledger_prev_quantity+$sku_quantity;
                                        
                                        $semi = $this->Inventory_semifinished->find('first', [
                                            'conditions' => ['Inventory_semifinished.id' => $sku_id],
                                            'recursive' => -1
                                        ]);
                                        
                                        $ledgerData['Stock_ledger'] = [
                                                'store_id'        => $location_id,
                                                'sku_id'          => $ingredient['Inventory_linking']['component_id'],
                                                'sku_name'        => $ingredient['Inventory_linking']['component_name'],
                                                'sku_price'       => $semi['Inventory_semifinished']['price'],
                                                'unit_id'         => $ingredient['Inventory_linking']['component_unit'],
                                                'unit_name'       => $ingredient['Inventory_linking']['component_unit_name'],
                                                'record_id'       => $txnid,
                                                'record_type'     => 'stockin',
                                                'quantity'        => $sku_quantity,
                                                'balance_stock'   => $newQuantity,
                                                'actual_balance'  => $newQuantity,
                                                'quantity_type'   => 'credit',
                                                'item_type'       => 'Semifinished',
                                                'type'            => 'OUTLET',
                                                'created'         => date('Y-m-d H:i:s')
                                        ];
                        				if(!$this->Stock_ledger->saveAll($ledgerData['Stock_ledger'])){
                                            throw new Exception('issue in linking ledger update!');
                                        }
                                    }
                                    
                                    //semis sku update
                                    $ingredients = $this->Inventory_linking->find('all', [
                                        'conditions' => ['parent_id' => $sku_id,'parent_type' => 'Semifinished','component_type' => 'Sku', 'status' => 1]
                                    ]);
                                    foreach ($ingredients as $ingredient) {
                                        $linked_sku_id = $ingredient['Inventory_linking']['component_id'];
                                        $sku_quantity= $ingredient['Inventory_linking']['quantity']*$qty;
                                            
                                        $ledgerprevdetails = $this->Stock_ledger->find('first', [
                                            'conditions' => ['sku_id' => $linked_sku_id,'store_id' =>$location_id,'item_type'=>'SKU' ,'type' =>'OUTLET' ],
                                            'fields' => ['actual_balance'],
                                            'order' => ['id DESC'],
                                            'recursive' => -1
                                        ]);
                                        
                                        $ledger_prev_quantity = !empty($ledgerprevdetails['Stock_ledger']['actual_balance']) 
                                            ? $ledgerprevdetails['Stock_ledger']['actual_balance'] 
                                            : 0;
                                        
                                        $newQuantity = $ledger_prev_quantity+$sku_quantity;
                                        
                                        $sku = $this->Inventory_sku->find('first', [
                                            'conditions' => ['Inventory_sku.id' => $sku_id],
                                            'recursive' => -1
                                        ]);
                                        
                                        $ledgerData['Stock_ledger'] = [
                                                'store_id'        => $location_id,
                                                'sku_id'          => $ingredient['Inventory_linking']['component_id'],
                                                'sku_name'        => $ingredient['Inventory_linking']['component_name'],
                                                'sku_price'       => $sku['Inventory_sku']['price'],
                                                'unit_id'         => $ingredient['Inventory_linking']['component_unit'],
                                                'unit_name'       => $ingredient['Inventory_linking']['component_unit_name'],
                                                'record_id'       => $txnid,
                                                'record_type'     => 'stockin',
                                                'quantity'        => $sku_quantity,
                                                'balance_stock'   => $newQuantity,
                                                'actual_balance'  => $newQuantity,
                                                'quantity_type'   => 'credit',
                                                'item_type'       => 'SKU',
                                                'type'            => 'OUTLET',
                                                'created'         => date('Y-m-d H:i:s')
                                        ];
                        				if(!$this->Stock_ledger->saveAll($ledgerData['Stock_ledger'])){
                                            throw new Exception('issue in sku ledger update!');
                                        }
                                    }
                				}else{
                				    throw new Exception('issue in linking sku ledger update!');
                                }
                            }else{
                                
                                $ledgerprevdetails = $this->Stock_ledger->find('first', [
                                    'conditions' => ['sku_id' => $sku_id,'type'=>'OUTLET','item_type'=>'SKU','store_id'=>$location_id],
                                    'fields' => ['actual_balance'],
                                    'order' => ['id DESC'],
                                    'recursive' => -1
                                ]);
                                
                                $ledger_prev_quantity = !empty($ledgerprevdetails['Stock_ledger']['actual_balance']) 
                                    ? $ledgerprevdetails['Stock_ledger']['actual_balance'] 
                                    : 0;
                                
                                $newQuantity = $qty + $ledger_prev_quantity;
                                
                                $ledgerData['Stock_ledger'] = [
                                        'store_id'        => $location_id,
                                        'sku_id'          => $sku_id,
                                        'sku_name'        => $sku['sku_name'],
                                        'sku_price'       => $approved_price,
                                        'unit_id'         => $sku['sku_unit'],
                                        'unit_name'       => $sku['sku_unit_name'],
                                        'record_id'       => $txnid,
                                        'record_type'     => 'stockin',
                                        'quantity'        => $qty,
                                        'balance_stock'   => $newQuantity,
                                        'actual_balance'  => $newQuantity,
                                        'quantity_type'   => 'credit',
                                        'type'            => 'OUTLET',
                                        'item_type'       => 'SKU',
                                        'created'         => date('Y-m-d H:i:s')
                                ];
                				if(!$this->Stock_ledger->saveAll($ledgerData['Stock_ledger'])){
                				    throw new Exception('issue in ledger update!');
                				}
                            }
                        }
                    }
                    
                    $this->Inventory_indent->updateAll(
                        [
                            'stockin_id'     => "'$txnid'",
                            'status'         => 4,
                            'stockin_date'   => "'$today'",
                            'invoice_no'     => "'$invoice'",
                            'stockin_amount' => "'$amount'",
                            'sync'           => 0,
                            'local_sync'     => 0
                        ],
                        [
                            'consignment_id' => $poid,
                            'status' => 3
                        ]
                    );
            
                    $response = ['status'=>true,'message'=>'success'];

                }
				elseif($type=='po'){
                    $indents = $this->Inventory_purchase_order->find('first', [
                        'conditions' => [
                            'p_o_id' => $poid,
                            'OR' => [
                                ['status' => 3],
                                [
                                    'status' => 4,
                                    'is_stockedin' => 0
                                ]
                            ]
                        ]
                    ]);
                    
                    if (empty($indents)) {
                        throw new Exception('Invalid PO!');
                    }
                    
                    $invidcheck = $this->Inventory_purchase_order->find('first', [
                        'conditions' => ['invoice_no' => $invoice, 'status !=' => 0]
                    ]);
                    
                    if (!empty($invidcheck)) {
                        throw new Exception('Invoice No already exist!');
                    }

                    $store_id      = $indents['Inventory_purchase_order']['location_id'];
                    $name          = $indents['Inventory_purchase_order']['location_name'];
                    $location_type = $indents['Inventory_purchase_order']['location_type'];
                    $vendor_id     = $indents['Inventory_purchase_order']['vendor_id'];
                    $vendor_name   = $indents['Inventory_purchase_order']['vendor_name'];
                    $indent_id     = $indents['Inventory_purchase_order']['internal_indent_id'];
                    $stockin_charge = $indents['Inventory_purchase_order']['stockin_charge'];

                    // Generate unique stockin_id
                    do {
                        $txnid = rand(1, 9);
                        for ($i = 0; $i < 10; $i++) {
                            $txnid .= rand(0, 9);
                        }
            
                        $existing = $this->Inventory_purchase_order->find('first', [
                            'conditions' => ['stockin_id' => $txnid]
                        ]);
                    } while ($existing);
            
                    $stockin_skus = $this->Inventory_purchase_order_item->find('all', [
                        'conditions' => [
                            'p_o_id' => $poid,
                            'OR' => [
                                ['status' => 3],
                                [
                                    'status' => 4,
                                    'is_stockedin' => 0
                                ]
                            ]
                        ]
                    ]);
                    
                    $amount         = 0;
                    $stockinamount  = 0;
                    $total_discount = 0;

                    foreach ($stockin_skus as $stockin_sku) {
                        $sku    = $stockin_sku['Inventory_purchase_order_item'];
                        $sku_id = $sku['sku_id'];
                        
                        if (isset($quantities[$sku_id]) && $quantities[$sku_id] != 0) {
                            $stockinqty = floatval($quantities[$sku_id]);

                            $processed_qty    = floatval($sku['processed_quantity']);
                            $prev_stockin_qty = floatval($sku['stockin_quantity']);
                            $max_allowed      = $processed_qty - $prev_stockin_qty;
                            if ($max_allowed <= 0) continue;
                            if ($stockinqty > $max_allowed) $stockinqty = $max_allowed;

                            $qty = $prev_stockin_qty + $stockinqty;

                            // UPDATED: use edited price if provided
                            $price = isset($prices[$sku_id]) ? floatval($prices[$sku_id]) : floatval($sku['sku_price']);
                            
                            $product_taxes = $this->Inventory_sku_tax->find('all', [
                                'fields'     => ['SUM(tax_percentage) as total_tax'],
                                'conditions' => ['product_id' => $sku_id, 'status' => 1]
                            ]);
                            
                            $total_tax = isset($product_taxes[0][0]['total_tax'])
                                ? round($product_taxes[0][0]['total_tax'], 2)
                                : 0;

                            $discountType = isset($discountTypes[$sku_id]) ? $discountTypes[$sku_id] : 'flat';
                            $rawDiscount  = isset($discountValues[$sku_id]) ? floatval($discountValues[$sku_id]) : 0;

                            $base_stockin = $price * $stockinqty;
                            if ($discountType === 'percentage') {
                                $discountAmount = round(($base_stockin * $rawDiscount) / 100, 2);
                            } else {
                                $discountAmount = $rawDiscount;
                            }
                            if ($discountAmount > $base_stockin) $discountAmount = $base_stockin;

                            // $stockin_after_discount = $base_stockin - $discountAmount;
                            $stockin_after_discount = round($base_stockin - $discountAmount, 2); // 🔥 FIX
				// 			$unit_price_after_dis = $stockin_after_discount / $stockinqty;
				            $unit_price_after_dis = round($stockin_after_discount / $stockinqty, 2); // 🔥 FIX (CRITICAL)
                            $stockintax_value       = round($stockin_after_discount * ($total_tax / 100), 2);
                            $stockinskuamount       = round($stockin_after_discount + $stockintax_value, 2);

                            $base_cumulative           = $price * $qty;
                            // $discount_per_unit         = ($stockinqty > 0) ? ($discountAmount / $stockinqty) : 0;
                            $discount_per_unit = ($stockinqty > 0) ? round($discountAmount / $stockinqty, 4) : 0; // 🔥 FIX
                            $cumulative_discount       = round($discount_per_unit * $qty, 2);
                            $cumulative_after_discount = $base_cumulative - $cumulative_discount;
                            $tax_value  = round($cumulative_after_discount * ($total_tax / 100), 2);
                            $skuamount  = round($cumulative_after_discount + $tax_value, 2);
                            
                            $is_stockedin = 1;
                            if ($processed_qty > $qty) {
                                $is_stockedin = 0;
                            }
                            
                            $stockinData['Inventory_stockin_item'] = [
                                'stockin_id'              => $txnid,
                                'p_o_id'                  => $poid,
                                'sku_id'                  => $sku_id,
                                'sku_name'                => $sku['sku_name'],
                                'sku_price'               => $unit_price_after_dis,
                                'sku_unit'                => $sku['sku_unit'],
                                'sku_unit_name'           => $sku['sku_unit_name'],
                                'stockin_quantity'        => $stockinqty,
                                'stockin_total_tax_value' => $stockintax_value,
                                'stockin_amount'          => $stockinskuamount,
                            ];
            				if(!$this->Inventory_stockin_item->saveAll($stockinData['Inventory_stockin_item'])){
            				    throw new Exception('issue in stockin item insert!');
            				}
            				
                            $this->Inventory_purchase_order_item->updateAll(
                                [
                                    'status'                  => 4,
                                    'sync'                    => 0,
                                    'local_sync'              => 0,
                                    'stockin_quantity'        => $qty,
                                    'stockin_total_tax_value' => $tax_value,
                                    'stockin_amount'          => $skuamount,
                                    'is_stockedin'            => $is_stockedin,
                                    // UPDATED: write to stockin_discount_* columns instead of sku_discount_*
                                    'stockin_discount'        => $rawDiscount,
                                    "stockin_discount_type"   => "'$discountType'",
                                    'stockin_discount_value'  => $discountAmount,
                                    // UPDATED: also save excise/VAT/TCS used during stockin
                                    'excise_per_unit'         => $excise_pu_s,
                                    'addl_excise_per_unit'    => $addl_excise_pu_s,
                                    'vat_percentage'          => $vat_pct_s,
                                    'tcs_percentage'          => $tcs_pct_s,
                                    'excise_duty_amt'         => $excise_line_cum,
                                    'addl_excise_duty_amt'    => $addl_excise_line_cum,
                                    'vat_amt'                 => $vat_cum,
                                    'tcs_amt'                 => $tcs_cum
                                ],
                                ['p_o_id' => $poid, 'sku_id' => $sku_id, 'status !=' => 0]
                            );

                            $amount        += $skuamount;
                            $stockinamount += $stockinskuamount;
                            $total_discount += $discountAmount;
                            
                            $ledgerprevdetails = $this->Stock_ledger->find('first', [
                                'conditions' => ['sku_id' => $sku_id,'type'=>'OUTLET','item_type'=>'SKU','store_id'=>$store_id],
                                'fields'     => ['actual_balance'],
                                'order'      => ['id DESC'],
                                'recursive'  => -1
                            ]);
                            
                            $ledger_prev_quantity = !empty($ledgerprevdetails['Stock_ledger']['actual_balance']) 
                                ? $ledgerprevdetails['Stock_ledger']['actual_balance'] 
                                : 0;
                            
                            $newQuantity = $stockinqty + $ledger_prev_quantity;
                            
                            $ledgerData['Stock_ledger'] = [
                                'store_id'      => $store_id,
                                'sku_id'        => $sku_id,
                                'sku_name'      => $sku['sku_name'],
                                'sku_price'     => $unit_price_after_dis,
                                'unit_id'       => $sku['sku_unit'],
                                'unit_name'     => $sku['sku_unit_name'],
                                'record_id'     => $txnid,
                                'record_type'   => 'stockin',
                                'quantity'      => round($stockinqty,2),
                                'balance_stock' => round($newQuantity,2),
                                'actual_balance'=> round($newQuantity,2),
                                'quantity_type' => 'credit',
                                'type'          => 'OUTLET',
                                'sync'          => 0,
                                'local_sync'    => 0,
                                'created'       => date('Y-m-d H:i:s')
                            ];
            				if(!$this->Stock_ledger->saveAll($ledgerData['Stock_ledger'])){
            				    throw new Exception('issue in po ledger update!');
            				}
                        }
                    }
                    
                    $amount         = $charge + $amount;
                    $stockin_charge = $stockin_charge + $charge;
                    $stockinamount  = $charge + $stockinamount;
                            
                    $pocompletioncheck = $this->Inventory_purchase_order_item->find('first', [
                        'conditions' => [
                            'p_o_id'      => $poid,
                            'is_stockedin'=> 0,
                            'status !='   => 0
                        ]
                    ]);
                    
                    $pois_stockedin = $pocompletioncheck ? 0 : 1;
                    
                    $stockinmainData['Inventory_stockin'] = [
                        'location_type'   => $location_type,
                        'vendor_id'       => $vendor_id,
                        'vendor_name'     => $vendor_name,
                        'indent_id'       => $indent_id,
                        'p_o_id'          => $poid,
                        'stockin_by_id'   => $store_id,
                        'stockin_by_name' => $name,
                        'stockin_charge'  => $charge,
                        'stockin_amount'  => $stockinamount,
                        'invoice_no'      => $invoice,
                        'stockin_id'      => $txnid,
                        'discount_value'  => $total_discount
                    ];
    				if(!$this->Inventory_stockin->saveAll($stockinmainData['Inventory_stockin'])){
    				    throw new Exception('issue in stockin insert!');
    				}
    				
                    $this->Inventory_purchase_order->updateAll(
                        [
                            'stockin_id'      => "'$txnid'",
                            'status'          => 4,
                            'stockin_on'      => "'$today'",
                            'stockin_by_id'   => $store_id,
                            'stockin_by_name' => "'$name'",
                            'stockin_charge'  => "'$stockin_charge'",
                            'stockin_amount'  => "'$amount'",
                            'invoice_no'      => "'$invoice'",
                            'is_stockedin'    => "'$pois_stockedin'",
                            // UPDATED: write to stockin_discount_value instead of discount_value
                            'stockin_discount_value' => "'$total_discount'",
                            'sync'            => 0,
                            'local_sync'      => 0
                        ],
                        [
                            'p_o_id'    => $poid,
                            'status' => [3,4]
                        ]
                    );
                    
                    $response = ['status'=>true,'message'=>'success'];
                }
                
                $dataSource->commit();
                
                echo json_encode($response);
                return;
            } catch (Exception $e) {
                $dataSource->rollback();
            
                echo json_encode(['status'=>false,'message'=>$e->getMessage()]);
                return;
            }
        }
    }
    */
    public function stockin_po() 
    {
        $this->autoRender = false; // Disable view rendering 
    
        if ($this->request->is('post')) {
            $poid = $this->request->data['_id'];
            $invoice = trim($this->request->data['_invoice']);
            if (!preg_match('/^[A-Za-z0-9\-\/]+$/', $invoice)) {
                echo json_encode(['status'=>false,'message'=>'Invalid Invoice format']);
                return;
            }
            $quantities = $this->request->data['_quantities']; // associative array: sku_id => quantity
            $item_types = $this->request->data['_item_type']; 
            // $charge = $this->request->data['_charge'] ?? 0; 
            $charge = isset($this->request->data['_charge']) 
                ? floatval($this->request->data['_charge']) 
                : 0;
            
            if ($charge < 0) $charge = 0;
            // $type = $this->request->data['_type']; 

            // $discountTypes  = $this->request->data['_discount_types']  ?? [];
            // $discountValues = $this->request->data['_discount_values'] ?? [];
            // $po_date = isset($this->request->data['_po_date']) ? trim($this->request->data['_po_date']) : null; // ← add this

            $type             = $this->request->data['_type']; 
            $prices           = $this->request->data['_prices']            ?? [];
            $discountTypes    = $this->request->data['_discount_types']    ?? [];
            $discountValues   = $this->request->data['_discount_values']   ?? [];
            $excise_per_units = $this->request->data['_excise_per_units']  ?? [];
            $addl_excise_units= $this->request->data['_addl_excise_units'] ?? [];
            $vat_per_units    = $this->request->data['_vat_per_units']     ?? [];
            $tcs_per_units    = $this->request->data['_tcs_per_units']     ?? [];
            $po_date          = isset($this->request->data['_po_date']) ? trim($this->request->data['_po_date']) : null;

            $today = date('Y-m-d H:i:s');
            
            if (empty($poid)) {
                echo json_encode(['status'=>false,'message'=>'Invalid PO/Consignment ID']);
                return;
            }
            
            if (empty($invoice)) {
                echo json_encode(['status'=>false,'message'=>'Invoice number required']);
                return;
            }
            
            $dataSource = $this->Inventory_indent->getDataSource();
            $dataSource->begin();
            
            try {
                if (empty($quantities) || !is_array($quantities)) {
                    throw new Exception('Invalid quantity data');
                }

                if($type=='consignment'){
                    $invoiceidchk = $this->Inventory_indent->find('first', [
                        'conditions' => ['consignment_id' => $poid,'indent_id' => $invoice]
                    ]);
                    
                    if(empty($invoiceidchk)){
                        throw new Exception('Something went wrong for this indent...!');
                    }
                    
                    $amount = 0;
                    // Generate unique stockin_id
                    do {
                        $txnid = rand(1, 9);
                        for ($i = 0; $i < 10; $i++) {
                            $txnid .= rand(0, 9);
                        }
            
                        $existing = $this->Inventory_indent->find('first', [
                            'conditions' => ['stockin_id' => $txnid]
                        ]);
                    } while ($existing);
            
                    $location_id = $this->Session->read('Auth.Location.id');
                    
                    $stockin_skus = $this->Inventory_indent_items->find('all', [
                        'conditions' => ['consignment_id' => $poid,'status' => 3]
                    ]);
                    
                    if(empty($stockin_skus)){
                        throw new Exception('No item found for this...!');
                    }
                    
                    foreach ($stockin_skus as $stockin_sku) {
                        $sku = $stockin_sku['Inventory_indent_items'];
                        $sku_id = $sku['sku_id'];
                        // UPDATED: use edited price if provided
                        $approved_price = isset($prices[$sku_id]) ? floatval($prices[$sku_id]) : floatval($sku['approved_price']);
                        
                        $stockinqty = floatval($quantities[$sku_id]);
                        
                        if ($stockinqty > 0) {
                        // if (isset($quantities[$sku_id])) {
                            $qty = floatval($quantities[$sku_id]);
                            $item_type = isset($item_types[$sku_id]) ? $item_types[$sku_id] : 'sku';
                            
                            if ($item_type == 'semifinished') {
                                $product_taxes = $this->Inventory_semifinished_tax->find('all', [
                                    'fields' => ['SUM(tax_percentage) as total_tax'],
                                    'conditions' => ['product_id' => $sku_id, 'status' => 1]
                                ]);
                            } else {
                                $product_taxes = $this->Inventory_sku_tax->find('all', [
                                    'fields' => ['SUM(tax_percentage) as total_tax'],
                                    'conditions' => ['product_id' => $sku_id, 'status' => 1]
                                ]);
                            }
                            $total_tax = isset($product_taxes[0][0]['total_tax'])
                                ? round($product_taxes[0][0]['total_tax'], 2)
                                : 0;

                            $tax_value = round(($approved_price * $qty) * ($total_tax / 100), 2);
                            $skuamount = round(($approved_price * $qty) + $tax_value, 2);
                            
                            // Update purchase order item
                            $this->Inventory_indent_items->updateAll(
                                ['status' => 4, 'sync' => 0, 'local_sync' => 0, 'stockin_quantity' => $qty,'stockin_tax_value' => $tax_value,
                                'stockin_amount' => $skuamount],
                                ['consignment_id' => $poid, 'sku_id' => $sku_id, 'status'=> 3]
                            );
                            
                            $amount += ($skuamount);
                            
                            //stock ledger entry semifinished
                            if ($item_type == 'semifinished') {
                                
                                $ledgerprevdetails = $this->Stock_ledger->find('first', [
                                    'conditions' => ['sku_id' => $sku_id,'type'=>'OUTLET','item_type'=>'semifinished','store_id'=>$location_id],
                                    'fields' => ['actual_balance'],
                                    'order' => ['id DESC'],
                                    'recursive' => -1
                                ]);
                                
                                $ledger_prev_quantity = !empty($ledgerprevdetails['Stock_ledger']['actual_balance']) 
                                    ? $ledgerprevdetails['Stock_ledger']['actual_balance'] 
                                    : 0;
                                
                                $newQuantity = $qty + $ledger_prev_quantity;
                                
                                $ledgerData['Stock_ledger'] = [
                                        'store_id'        => $location_id,
                                        'sku_id'          => $sku_id,
                                        'sku_name'        => $sku['sku_name'],
                                        'sku_price'       => $approved_price,
                                        'unit_id'         => $sku['sku_unit'],
                                        'unit_name'       => $sku['sku_unit_name'],
                                        'record_id'       => $txnid,
                                        'record_type'     => 'stockin',
                                        'quantity'        => $qty,
                                        'balance_stock'   => $newQuantity,
                                        'actual_balance'  => $newQuantity,
                                        'quantity_type'   => 'credit',
                                        'type'            => 'OUTLET',
                                        'item_type'       => 'semifinished',
                                        'created'         => date('Y-m-d H:i:s')
                                ];
                                if($this->Stock_ledger->saveAll($ledgerData['Stock_ledger'])){
                                
                                    //semis semi update
                                    $ingredients = $this->Inventory_linking->find('all', [
                                        'conditions' => ['parent_id' => $sku_id,'parent_type' => 'Semifinished','component_type' => 'Semifinished', 'status' => 1]
                                    ]);
                                    foreach ($ingredients as $ingredient) {
                                        $linked_sku_id = $ingredient['Inventory_linking']['component_id'];
                                        $sku_quantity= $ingredient['Inventory_linking']['quantity']*$qty;
                                            
                                        $ledgerprevdetails = $this->Stock_ledger->find('first', [
                                            'conditions' => ['sku_id' => $linked_sku_id,'store_id' =>$location_id,'item_type'=>'Semifinished' ,'type' =>'OUTLET' ],
                                            'fields' => ['actual_balance'],
                                            'order' => ['id DESC'],
                                            'recursive' => -1
                                        ]);
                                        
                                        $ledger_prev_quantity = !empty($ledgerprevdetails['Stock_ledger']['actual_balance']) 
                                            ? $ledgerprevdetails['Stock_ledger']['actual_balance'] 
                                            : 0;
                                        
                                        $newQuantity = $ledger_prev_quantity+$sku_quantity;
                                        
                                        $semi = $this->Inventory_semifinished->find('first', [
                                            'conditions' => ['Inventory_semifinished.id' => $sku_id],
                                            'recursive' => -1
                                        ]);
                                        
                                        $ledgerData['Stock_ledger'] = [
                                                'store_id'        => $location_id,
                                                'sku_id'          => $ingredient['Inventory_linking']['component_id'],
                                                'sku_name'        => $ingredient['Inventory_linking']['component_name'],
                                                'sku_price'       => $semi['Inventory_semifinished']['price'],
                                                'unit_id'         => $ingredient['Inventory_linking']['component_unit'],
                                                'unit_name'       => $ingredient['Inventory_linking']['component_unit_name'],
                                                'record_id'       => $txnid,
                                                'record_type'     => 'stockin',
                                                'quantity'        => $sku_quantity,
                                                'balance_stock'   => $newQuantity,
                                                'actual_balance'  => $newQuantity,
                                                'quantity_type'   => 'credit',
                                                'item_type'       => 'Semifinished',
                                                'type'            => 'OUTLET',
                                                'created'         => date('Y-m-d H:i:s')
                                        ];
                                        if(!$this->Stock_ledger->saveAll($ledgerData['Stock_ledger'])){
                                            throw new Exception('issue in linking ledger update!');
                                        }
                                    }
                                    
                                    //semis sku update
                                    $ingredients = $this->Inventory_linking->find('all', [
                                        'conditions' => ['parent_id' => $sku_id,'parent_type' => 'Semifinished','component_type' => 'Sku', 'status' => 1]
                                    ]);
                                    foreach ($ingredients as $ingredient) {
                                        $linked_sku_id = $ingredient['Inventory_linking']['component_id'];
                                        $sku_quantity= $ingredient['Inventory_linking']['quantity']*$qty;
                                            
                                        $ledgerprevdetails = $this->Stock_ledger->find('first', [
                                            'conditions' => ['sku_id' => $linked_sku_id,'store_id' =>$location_id,'item_type'=>'SKU' ,'type' =>'OUTLET' ],
                                            'fields' => ['actual_balance'],
                                            'order' => ['id DESC'],
                                            'recursive' => -1
                                        ]);
                                        
                                        $ledger_prev_quantity = !empty($ledgerprevdetails['Stock_ledger']['actual_balance']) 
                                            ? $ledgerprevdetails['Stock_ledger']['actual_balance'] 
                                            : 0;
                                        
                                        $newQuantity = $ledger_prev_quantity+$sku_quantity;
                                        
                                        $sku = $this->Inventory_sku->find('first', [
                                            'conditions' => ['Inventory_sku.id' => $sku_id],
                                            'recursive' => -1
                                        ]);
                                        
                                        $ledgerData['Stock_ledger'] = [
                                                'store_id'        => $location_id,
                                                'sku_id'          => $ingredient['Inventory_linking']['component_id'],
                                                'sku_name'        => $ingredient['Inventory_linking']['component_name'],
                                                'sku_price'       => $sku['Inventory_sku']['price'],
                                                'unit_id'         => $ingredient['Inventory_linking']['component_unit'],
                                                'unit_name'       => $ingredient['Inventory_linking']['component_unit_name'],
                                                'record_id'       => $txnid,
                                                'record_type'     => 'stockin',
                                                'quantity'        => $sku_quantity,
                                                'balance_stock'   => $newQuantity,
                                                'actual_balance'  => $newQuantity,
                                                'quantity_type'   => 'credit',
                                                'item_type'       => 'SKU',
                                                'type'            => 'OUTLET',
                                                'created'         => date('Y-m-d H:i:s')
                                        ];
                                        if(!$this->Stock_ledger->saveAll($ledgerData['Stock_ledger'])){
                                            throw new Exception('issue in sku ledger update!');
                                        }
                                    }
                                }else{
                                    throw new Exception('issue in linking sku ledger update!');
                                }
                            }else{
                                
                                $ledgerprevdetails = $this->Stock_ledger->find('first', [
                                    'conditions' => ['sku_id' => $sku_id,'type'=>'OUTLET','item_type'=>'SKU','store_id'=>$location_id],
                                    'fields' => ['actual_balance'],
                                    'order' => ['id DESC'],
                                    'recursive' => -1
                                ]);
                                
                                $ledger_prev_quantity = !empty($ledgerprevdetails['Stock_ledger']['actual_balance']) 
                                    ? $ledgerprevdetails['Stock_ledger']['actual_balance'] 
                                    : 0;
                                
                                $newQuantity = $qty + $ledger_prev_quantity;
                                
                                $ledgerData['Stock_ledger'] = [
                                        'store_id'        => $location_id,
                                        'sku_id'          => $sku_id,
                                        'sku_name'        => $sku['sku_name'],
                                        'sku_price'       => $approved_price,
                                        'unit_id'         => $sku['sku_unit'],
                                        'unit_name'       => $sku['sku_unit_name'],
                                        'record_id'       => $txnid,
                                        'record_type'     => 'stockin',
                                        'quantity'        => $qty,
                                        'balance_stock'   => $newQuantity,
                                        'actual_balance'  => $newQuantity,
                                        'quantity_type'   => 'credit',
                                        'type'            => 'OUTLET',
                                        'item_type'       => 'SKU',
                                        'created'         => date('Y-m-d H:i:s')
                                ];
                                if(!$this->Stock_ledger->saveAll($ledgerData['Stock_ledger'])){
                                    throw new Exception('issue in ledger update!');
                                }
                            }
                        }
                    }
                    
                    $this->Inventory_indent->updateAll(
                        [
                            'stockin_id'     => "'$txnid'",
                            'status'         => 4,
                            'stockin_date'   => "'$po_date'",
                            'invoice_no'     => "'$invoice'",
                            'stockin_amount' => "'$amount'",
                            'approved_amount' => "'$amount'",
                            'processed_amount'=> "'$amount'",
                            'total_amount'    => "'$amount'",
                            'sync'           => 0,
                            'local_sync'     => 0
                        ],
                        [
                            'consignment_id' => $poid,
                            'status' => 3
                        ]
                    );
                    
                    // Insert inventory_stockin header record (was missing for consignment type)
                    $consignmentStockinData['Inventory_stockin'] = [
                        'location_type'   => 'Outlet',
                        'vendor_id'       => 0,
                        'vendor_name'     => '',
                        'indent_id'       => $poid,
                        'p_o_id'          => '',
                        'stockin_by_id'   => $location_id,
                        'stockin_by_name' => '',
                        'stockin_charge'  => 0,
                        'stockin_amount'  => $amount,
                        'invoice_no'      => $invoice,
                        'stockin_id'      => $txnid,
                        'discount_value'  => 0,
                    ];
                    if (!$this->Inventory_stockin->saveAll($consignmentStockinData['Inventory_stockin'])) {
                        throw new Exception('issue in consignment stockin header insert!');
                    }


            
                    $response = ['status'=>true,'message'=>'success'];

                }
                elseif($type=='po'){
                    $indents = $this->Inventory_purchase_order->find('first', [
                        'conditions' => [
                            'p_o_id' => $poid,
                            'OR' => [
                                ['status' => 3],
                                [
                                    'status' => 4,
                                    'is_stockedin' => 0
                                ]
                            ]
                        ]
                    ]);
                    
                    if (empty($indents)) {
                        throw new Exception('Invalid PO!');
                    }
                    
                    $invidcheck = $this->Inventory_purchase_order->find('first', [
                        'conditions' => ['invoice_no' => $invoice, 'status !=' => 0]
                    ]);
                    
                    if (!empty($invidcheck)) {
                        throw new Exception('Invoice No already exist!');
                    }

                    $store_id      = $indents['Inventory_purchase_order']['location_id'];
                    $name          = $indents['Inventory_purchase_order']['location_name'];
                    $location_type = $indents['Inventory_purchase_order']['location_type'];
                    $vendor_id     = $indents['Inventory_purchase_order']['vendor_id'];
                    $vendor_name   = $indents['Inventory_purchase_order']['vendor_name'];
                    $indent_id     = $indents['Inventory_purchase_order']['internal_indent_id'];
                    $stockin_charge = $indents['Inventory_purchase_order']['stockin_charge'];

                    // Generate unique stockin_id
                    do {
                        $txnid = rand(1, 9);
                        for ($i = 0; $i < 10; $i++) {
                            $txnid .= rand(0, 9);
                        }
            
                        $existing = $this->Inventory_purchase_order->find('first', [
                            'conditions' => ['stockin_id' => $txnid]
                        ]);
                    } while ($existing);
            
                    $stockin_skus = $this->Inventory_purchase_order_item->find('all', [
                        'conditions' => [
                            'p_o_id' => $poid,
                            'OR' => [
                                ['status' => 3],
                                [
                                    'status' => 4,
                                    'is_stockedin' => 0
                                ]
                            ]
                        ]
                    ]);
                    
                    $amount         = 0;
                    $stockinamount  = 0;
                    $total_discount = 0;

                    foreach ($stockin_skus as $stockin_sku) {
                        $sku    = $stockin_sku['Inventory_purchase_order_item'];
                        $sku_id = $sku['sku_id'];
                        
                        if (isset($quantities[$sku_id]) && $quantities[$sku_id] != 0) {
                            $stockinqty = floatval($quantities[$sku_id]);

                            $processed_qty    = floatval($sku['processed_quantity']);
                            $prev_stockin_qty = floatval($sku['stockin_quantity']);
                            $max_allowed      = $processed_qty - $prev_stockin_qty;
                            if ($max_allowed <= 0) continue;
                            if ($stockinqty > $max_allowed) $stockinqty = $max_allowed;

                            $qty = $prev_stockin_qty + $stockinqty;

                            // UPDATED: use edited price if provided
                            $price = isset($prices[$sku_id]) ? floatval($prices[$sku_id]) : floatval($sku['sku_price']);
                            
                            $product_taxes = $this->Inventory_sku_tax->find('all', [
                                'fields'     => ['SUM(tax_percentage) as total_tax'],
                                'conditions' => ['product_id' => $sku_id, 'status' => 1]
                            ]);
                            
                            $total_tax = isset($product_taxes[0][0]['total_tax'])
                                ? round($product_taxes[0][0]['total_tax'], 2)
                                : 0;

                            $discountType = isset($discountTypes[$sku_id]) ? $discountTypes[$sku_id] : 'flat';
                            $rawDiscount  = isset($discountValues[$sku_id]) ? floatval($discountValues[$sku_id]) : 0;

                            $base_stockin = $price * $stockinqty;
                            if ($discountType === 'percentage') {
                                $discountAmount = round(($base_stockin * $rawDiscount) / 100, 2);
                            } else {
                                $discountAmount = $rawDiscount;
                            }
                            if ($discountAmount > $base_stockin) $discountAmount = $base_stockin;

                            // $stockin_after_discount = $base_stockin - $discountAmount;
                //             $stockin_after_discount = round($base_stockin - $discountAmount, 2); // 🔥 FIX
                // //          $unit_price_after_dis = $stockin_after_discount / $stockinqty;
                //             $unit_price_after_dis = round($stockin_after_discount / $stockinqty, 2); // 🔥 FIX (CRITICAL)
                //             $stockintax_value       = round($stockin_after_discount * ($total_tax / 100), 2);
                //             $stockinskuamount       = round($stockin_after_discount + $stockintax_value, 2);
                            
                            $stockin_after_discount = round($base_stockin - $discountAmount, 2);
                            $unit_price_after_dis   = ($stockinqty > 0) ? round($stockin_after_discount / $stockinqty, 4) : 0;

                            // Per-line excise/VAT/TCS from POST
                            $excise_pu_s      = floatval($excise_per_units[$sku_id]  ?? 0);
                            $addl_excise_pu_s = floatval($addl_excise_units[$sku_id] ?? 0);
                            $vat_pct_s        = floatval($vat_per_units[$sku_id]     ?? 0);
                            $tcs_pct_s        = floatval($tcs_per_units[$sku_id]     ?? 0);

                            $excise_line_s      = round($excise_pu_s * $stockinqty, 4);
                            $addl_excise_line_s = round($addl_excise_pu_s * $stockinqty, 4);

                            // VAT base = base + excise + addl_excise
                            $vat_base_s       = $stockin_after_discount + $excise_line_s + $addl_excise_line_s;
                            // VAT: irrecoverable — in landed cost
                            $vat_line_s       = round($vat_base_s * ($vat_pct_s / 100), 2);
                            // TCS = (base + excise + VAT) x TCS% — addl_excise excluded
                            $tcs_line_s       = round(($stockin_after_discount + $excise_line_s + $vat_line_s) * ($tcs_pct_s / 100), 2);
                            // Landed = base + excise + VAT (addl_excise only feeds VAT base, not a separate line)
                            $landed_s         = $stockin_after_discount + $excise_line_s + $vat_line_s;
                            // Keep legacy tax regardless of excise/VAT
                            $stockintax_value = round($landed_s * ($total_tax / 100), 2);
                            $stockinskuamount = round($landed_s + $stockintax_value + $tcs_line_s, 2);

                            // Landed per unit = (base + excise + VAT) / stockin_qty
                            $vat_base_pu_s     = $unit_price_after_dis + $excise_pu_s + $addl_excise_pu_s;
                            $vat_pu_s          = round($vat_base_pu_s * ($vat_pct_s / 100), 4);
                            $landed_per_unit_s = round($unit_price_after_dis + $excise_pu_s + $vat_pu_s, 4);

                            $base_cumulative   = $price * $qty;
                            $discount_per_unit = ($stockinqty > 0) ? round($discountAmount / $stockinqty, 4) : 0;

                            // $base_cumulative           = $price * $qty;
                            // $discount_per_unit         = ($stockinqty > 0) ? ($discountAmount / $stockinqty) : 0;
                            // $discount_per_unit = ($stockinqty > 0) ? round($discountAmount / $stockinqty, 4) : 0; // 🔥 FIX
                            $cumulative_discount       = round($discount_per_unit * $qty, 2);
                            $cumulative_after_discount = $base_cumulative - $cumulative_discount;
                            
                            $excise_line_cum      = round($excise_pu_s * $qty, 4);
                            $addl_excise_line_cum = round($addl_excise_pu_s * $qty, 4);
                            // VAT base (cumulative) = base + excise + addl_excise
                            $vat_base_cum = $cumulative_after_discount + $excise_line_cum + $addl_excise_line_cum;
                            $vat_cum      = round($vat_base_cum * ($vat_pct_s / 100), 2);
                            // TCS (cumulative) = (base + excise + VAT) x TCS%
                            $tcs_cum      = round(($cumulative_after_discount + $excise_line_cum + $vat_cum) * ($tcs_pct_s / 100), 2);
                            // Landed (cumulative) = base + excise + VAT
                            $landed_cum   = $cumulative_after_discount + $excise_line_cum + $vat_cum;
                            // Keep legacy tax regardless of excise/VAT
                            $tax_value    = round($landed_cum * ($total_tax / 100), 2);
                            $skuamount    = round($landed_cum + $tax_value + $tcs_cum, 2);

                            // $tax_value  = round($cumulative_after_discount * ($total_tax / 100), 2);
                            // $skuamount  = round($cumulative_after_discount + $tax_value, 2);
                            
                            $is_stockedin = 1;
                            if ($processed_qty > $qty) {
                                $is_stockedin = 0;
                            }
                            
                            $stockinData['Inventory_stockin_item'] = [
                                'stockin_id'              => $txnid,
                                'p_o_id'                  => $poid,
                                'sku_id'                  => $sku_id,
                                'sku_name'                => $sku['sku_name'],
                                'sku_price'               => $unit_price_after_dis,
                                'sku_unit'                => $sku['sku_unit'],
                                'sku_unit_name'           => $sku['sku_unit_name'],
                                'stockin_quantity'        => $stockinqty,
                                'stockin_total_tax_value' => $stockintax_value,
                                'stockin_amount'          => $stockinskuamount,
                            ];
                            if(!$this->Inventory_stockin_item->saveAll($stockinData['Inventory_stockin_item'])){
                                throw new Exception('issue in stockin item insert!');
                            }
                            
                            $this->Inventory_purchase_order_item->updateAll(
                                [
                                    'status'                  => 4,
                                    'sync'                    => 0,
                                    'local_sync'              => 0,
                                    'stockin_quantity'        => $qty,
                                    'stockin_total_tax_value' => $tax_value,
                                    'stockin_amount'          => $skuamount,
                                    'is_stockedin'            => $is_stockedin,
                                    // UPDATED: write to stockin_discount_* columns instead of sku_discount_*
                                    'stockin_discount'        => $rawDiscount,
                                    "stockin_discount_type"   => "'$discountType'",
                                    'stockin_discount_value'  => $discountAmount,
                                    // UPDATED: also save excise/VAT/TCS used during stockin
                                    'excise_per_unit'         => $excise_pu_s,
                                    'addl_excise_per_unit'    => $addl_excise_pu_s,
                                    'vat_percentage'          => $vat_pct_s,
                                    'tcs_percentage'          => $tcs_pct_s,
                                    'excise_duty_amt'         => $excise_line_cum,
                                    'addl_excise_duty_amt'    => $addl_excise_line_cum,
                                    'vat_amt'                 => $vat_cum,
                                    'tcs_amt'                 => $tcs_cum
                                ],
                                ['p_o_id' => $poid, 'sku_id' => $sku_id, 'status !=' => 0]
                            );

                            $amount        += $skuamount;
                            $stockinamount += $stockinskuamount;
                            $total_discount += $discountAmount;
                            
                            $ledgerprevdetails = $this->Stock_ledger->find('first', [
                                'conditions' => ['sku_id' => $sku_id,'type'=>'OUTLET','item_type'=>'SKU','store_id'=>$store_id],
                                'fields'     => ['actual_balance'],
                                'order'      => ['id DESC'],
                                'recursive'  => -1
                            ]);
                            
                            $ledger_prev_quantity = !empty($ledgerprevdetails['Stock_ledger']['actual_balance']) 
                                ? $ledgerprevdetails['Stock_ledger']['actual_balance'] 
                                : 0;
                            
                            $newQuantity = $stockinqty + $ledger_prev_quantity;
                            
                            $ledgerData['Stock_ledger'] = [
                                'store_id'      => $store_id,
                                'sku_id'        => $sku_id,
                                'sku_name'      => $sku['sku_name'],
                                'sku_price'     => round($landed_per_unit_s, 4), // landed = base/unit + excise/unit
                                'unit_id'       => $sku['sku_unit'],
                                'unit_name'     => $sku['sku_unit_name'],
                                'record_id'     => $txnid,
                                'record_type'   => 'stockin',
                                'quantity'      => round($stockinqty,2),
                                'balance_stock' => round($newQuantity,2),
                                'actual_balance'=> round($newQuantity,2),
                                'quantity_type' => 'credit',
                                'type'          => 'OUTLET',
                                'sync'          => 0,
                                'local_sync'    => 0,
                                'created'       => date('Y-m-d H:i:s')
                            ];
                            if(!$this->Stock_ledger->saveAll($ledgerData['Stock_ledger'])){
                                throw new Exception('issue in po ledger update!');
                            }
                                                        // Update SKU last_stockin_price with landed cost
                            $this->Inventory_sku->query("UPDATE inventory_skus SET
                                last_stockin_price = " . round($landed_per_unit_s, 4) . ",
                                sync = 0, local_sync = 0 WHERE id = '$sku_id'");

                        }
                    }
                    
                    $amount         = $charge + $amount;
                    $stockin_charge = $stockin_charge + $charge;
                    $stockinamount  = $charge + $stockinamount;
                            
                    $pocompletioncheck = $this->Inventory_purchase_order_item->find('first', [
                        'conditions' => [
                            'p_o_id'      => $poid,
                            'is_stockedin'=> 0,
                            'status !='   => 0
                        ]
                    ]);
                    
                    $pois_stockedin = $pocompletioncheck ? 0 : 1;
                    
                    $stockinmainData['Inventory_stockin'] = [
                        'location_type'   => $location_type,
                        'vendor_id'       => $vendor_id,
                        'vendor_name'     => $vendor_name,
                        'indent_id'       => $indent_id,
                        'p_o_id'          => $poid,
                        'stockin_by_id'   => $store_id,
                        'stockin_by_name' => $name,
                        'stockin_charge'  => $charge,
                        'stockin_amount'  => $stockinamount,
                        'invoice_no'      => $invoice,
                        'stockin_id'      => $txnid,
                        'discount_value'  => $total_discount
                    ];
                    if(!$this->Inventory_stockin->saveAll($stockinmainData['Inventory_stockin'])){
                        throw new Exception('issue in stockin insert!');
                    }
                    
                    // Accumulate excise/VAT/TCS totals from per-line values
                    $total_excise_outlet      = 0;
                    $total_addl_excise_outlet = 0;
                    $total_vat_outlet         = 0;
                    $total_tcs_outlet         = 0;
                    foreach ($excise_per_units as $sid => $epv) {
                        $qty_s     = floatval($quantities[$sid] ?? 0);
                        $exc_pu    = floatval($epv);
                        $addl_pu   = floatval($addl_excise_units[$sid] ?? 0);
                        $vat_pct_v = floatval($vat_per_units[$sid] ?? 0);
                        $tcs_pct_v = floatval($tcs_per_units[$sid] ?? 0);
                        // Retrieve per-unit price for this SKU from the PO items
                        $sku_price_v = 0;
                        foreach ($stockin_skus as $ss) {
                            if ($ss['Inventory_purchase_order_item']['sku_id'] == $sid) {
                                $sku_price_v = floatval($ss['Inventory_purchase_order_item']['sku_price']);
                                break;
                            }
                        }
                        $exc_line    = $exc_pu * $qty_s;
                        $addl_line   = $addl_pu * $qty_s;
                        $base_line   = $sku_price_v * $qty_s;
                        $vat_base_v  = $base_line + $exc_line + $addl_line;
                        $vat_amt_v   = round($vat_base_v * $vat_pct_v / 100, 2);
                        $tcs_base_v  = $base_line + $exc_line + $vat_amt_v;
                        $tcs_amt_v   = round($tcs_base_v * $tcs_pct_v / 100, 2);
                        $total_excise_outlet      += $exc_line;
                        $total_addl_excise_outlet += $addl_line;
                        $total_vat_outlet         += $vat_amt_v;
                        $total_tcs_outlet         += $tcs_amt_v;
                    }
                    $total_excise_outlet      = round($total_excise_outlet, 2);
                    $total_addl_excise_outlet = round($total_addl_excise_outlet, 2);
                    $total_vat_outlet         = round($total_vat_outlet, 2);
                    $total_tcs_outlet         = round($total_tcs_outlet, 2);

                    // $this->Inventory_purchase_order->updateAll(
                    //     [
                    //         'stockin_id'      => "'$txnid'",
                    //         'status'          => 4,
                    //         'stockin_on'      => "'$po_date'",
                    //         'stockin_by_id'   => $store_id,
                    //         'stockin_by_name' => "'$name'",
                    //         'stockin_charge'  => "'$stockin_charge'",
                    //         'stockin_amount'  => "'$amount'",
                    //         'invoice_no'      => "'$invoice'",
                    //         'is_stockedin'    => "'$pois_stockedin'",
                    //         // UPDATED: write to stockin_discount_value instead of discount_value
                    //         'stockin_discount_value' => "'$total_discount'",
                    //         'sync'            => 0,
                    //         'local_sync'      => 0
                    //     ],
                    //     [
                    //         'p_o_id'    => $poid,
                    //         'status' => [3,4]
                    //     ]
                    // );
                    
                    $this->Inventory_purchase_order->updateAll(
                        [
                            'stockin_id'             => "'$txnid'",
                            'status'                 => 4,
                            'stockin_on'             => "'$po_date'",
                            'stockin_by_id'          => $store_id,
                            'stockin_by_name'        => "'$name'",
                            'stockin_charge'         => "'$stockin_charge'",
                            'stockin_amount'         => "'$amount'",
                            'invoice_no'             => "'$invoice'",
                            'is_stockedin'           => "'$pois_stockedin'",
                            'stockin_discount_value' => "'$total_discount'",
                            'excise_duty_total'      => $total_excise_outlet,
                            'addl_excise_duty_total' => $total_addl_excise_outlet,
                            'vat_total'              => $total_vat_outlet,
                            'tcs_total'              => $total_tcs_outlet,
                            'sync'                   => 0,
                            'local_sync'             => 0
                        ],
                        [
                            'p_o_id' => $poid,
                            'status' => [3,4]
                        ]
                    );
                    
                    $response = ['status'=>true,'message'=>'success'];
                }
                
                $dataSource->commit();
                
                echo json_encode($response);
                return;
            } catch (Exception $e) {
                $dataSource->rollback();
            
                echo json_encode(['status'=>false,'message'=>$e->getMessage()]);
                return;
            }
        }
    }

   
    public function stock_level() {
        if (!$this->Session->check('Auth.User.id')) {
            return $this->redirect(['controller' => 'dashboard', 'action' => 'login']);
        }
    
        $this->layout = "users";
        
        $today = date("Y-m-d");
        $currentTime = date("H:i", strtotime("+1 minutes"));

        $sel_date = $today;      // default
        $sel_time = $currentTime; // default
        
        $skuledger_startdate = date('Y-m-d', strtotime('-10 days'));
        $stocklevel_enddate   = $skuledger_enddate   = date('Y-m-d');
        $sku_id = null;
        $stocklevel_startdate = $sel_date.' '.$sel_time.':00';
        if ($this->request->is(['post', 'put'])) {
            if (isset($this->request->data['submit_stocklevel'])) {
                
                $sel_date=$this->request->data['sel_date'];
                $sel_time=$this->request->data['sel_time'];
            
                $stocklevel_startdate = $sel_date.' '.$sel_time.':00';
            }
    
            if (isset($this->request->data['submit_skutransactions'])) {
                $daterange = $this->request->data['daterange'] ?? '';
                $dates = explode(" - ", $daterange);
                if (count($dates) === 2) {
                    $skuledger_startdate = date('Y-m-d', strtotime(trim($dates[0])));
                    $skuledger_enddate = date('Y-m-d', strtotime(trim($dates[1])));
                }
                $sku_id = $this->request->data['SkuLedger']['sku'] ?? null;
            }
            
        }
        
        $this->set(compact('sel_date', 'sel_time'));

        $store_id = $this->Session->read('Auth.Location.id');
        // $username = $this->User->query("SELECT location_id FROM users WHERE id = $id");
        // $store_id = $username[0]['users']['location_id'];
        
        $transactions = $this->Stock_ledger->query("
            SELECT sl1.sku_id,sl1.sku_price,sl1.sku_name,sl1.unit_name,s.stock, sl1.actual_balance
            FROM stock_ledger sl1
            INNER JOIN (
                SELECT sku_id, MAX(id) AS max_id
                FROM stock_ledger
                WHERE type = 'OUTLET' and item_type = 'Semifinished'
                  AND store_id = $store_id
                  AND created < '$stocklevel_startdate'
                GROUP BY sku_id
            ) latest ON sl1.id = latest.max_id
            LEFT JOIN inventory_semifinished s ON s.id = sl1.sku_id
            WHERE sl1.type = 'OUTLET'  and sl1.item_type = 'Semifinished'
              AND sl1.store_id = $store_id order by s.name asc
        ");
        
        $skus = $this->Inventory_semifinished->query("SELECT * FROM inventory_semifinished WHERE status=1 order by name asc");
        
        $skutransactions = [];
        if (!empty($sku_id)) {
            
            $_shiftRange = $this->getShiftDateRange($skuledger_startdate, $enddate, isset($store_id) ? $store_id : (isset($location_id) ? $location_id : null)); $startDatetime = $_shiftRange['startDatetime']; $endDatetime = $_shiftRange['endDatetime'];
            
            // $skutransactions = $this->Stock_ledger->query("
                // SELECT * FROM stock_ledger 
                // WHERE created BETWEEN '$startDatetime' AND '$endDatetime'
                // AND type='OUTLET' AND sku_id = $sku_id and store_id=$store_id and item_type = 'Semifinished'
                // ORDER BY id DESC
            // ");
			$skutransactions = $this->Stock_ledger->query("SELECT sl.*, ipo.vendor_name,ii.warehouse_name
						FROM stock_ledger sl 
						LEFT JOIN inventory_purchase_order ipo
							ON ipo.stockin_id = sl.record_id
						LEFT JOIN inventory_indent ii ON ii.stockin_id = sl.record_id
						WHERE sl.created BETWEEN '$startDatetime' AND '$endDatetime'
						AND sl.type = 'OUTLET'
						AND sl.sku_id = $sku_id
						AND sl.store_id = $store_id
						AND sl.item_type = 'Semifinished'
						ORDER BY sl.id DESC");
        }
        
        $this->set('stocklevel_daterange', date('m/d/Y', strtotime($stocklevel_startdate)) . ' - ' . date('m/d/Y', strtotime($stocklevel_enddate)));
        $this->set('skuledger_daterange', date('m/d/Y', strtotime($skuledger_startdate)) . ' - ' . date('m/d/Y', strtotime($skuledger_enddate)));
    
        $this->set(compact('transactions', 'skus', 'skutransactions'));
    }

    public function stock_levelexport()
    {
        $date_range = $_GET['sdate'] ?? '';
        
        if (!$date_range) {
            throw new BadRequestException('Invalid date range.');
        }
    
        $store_id = $this->Session->read('Auth.Location.id');
    
        // Fetch store name
        $store_data = $this->Location->find('first', [
            'conditions' => ['Location.id' => $store_id],
            'fields' => ['Location.name']
        ]);
        $store_name = $store_data['Location']['name'] ?? 'Unknown Store';
    
        // Fetch stock ledger data (same logic as in view)
        $transactions = $this->Stock_ledger->query("
            SELECT sl1.sku_id, sl1.sku_price, sl1.sku_name, sl1.unit_name,
                   s.stock, sl1.actual_balance
            FROM stock_ledger sl1
            INNER JOIN (
                SELECT sku_id, MAX(id) AS max_id
                FROM stock_ledger
                WHERE type = 'OUTLET' 
                  AND item_type = 'Semifinished'
                  AND store_id = $store_id
                  AND created < '$date_range'
                GROUP BY sku_id
            ) latest ON sl1.id = latest.max_id
            LEFT JOIN inventory_semifinished s ON s.id = sl1.sku_id
            WHERE sl1.type = 'OUTLET' 
              AND sl1.item_type = 'Semifinished'
              AND sl1.store_id = $store_id
            ORDER BY s.name ASC
        ");
    
        // Prepare for Excel export
        App::import('Vendor', 'PHPExcel/PHPExcel');
        error_reporting(0);
        ini_set('display_errors', TRUE);
        ini_set('display_startup_errors', TRUE);
    
        if (PHP_SAPI == 'cli') {
            die('This example should only be run from a Web Browser');
        }
    
        $objPHPExcel = new PHPExcel();
        $objPHPExcel->getProperties()
            ->setCreator("Aether")
            ->setLastModifiedBy("Aether")
            ->setTitle("Stock Level Report")
            ->setSubject("Stock Report")
            ->setDescription("Exported stock level data.")
            ->setKeywords("stock excel")
            ->setCategory("Export File");
    
        // ===========================
        // HEADER SECTION
        // ===========================
        $sheet = $objPHPExcel->setActiveSheetIndex(0);
        $sheet->setCellValue('A1', "OUTLET STOCK LEVEL REPORT");
        $sheet->mergeCells('A1:G1');
        $sheet->getStyle('A1:G1')->getFont()->setBold(true)->setSize(14);
    
        $sheet->setCellValue('A2', "Store: $store_name");
        $sheet->mergeCells('A2:G2');
        $sheet->getStyle('A2:G2')->getFont()->setBold(true)->setSize(12);
    
        $sheet->setCellValue('A3', "Date $date_range ");
        $sheet->mergeCells('A3:G3');
        $sheet->getStyle('A3:G3')->getFont()->setBold(true)->setSize(11);
    
        // ===========================
        // TABLE HEADER
        // ===========================
        $headers = ['Sr No.', 'SKU Name', 'Unit Name', 'Price', 'Required Qty', 'Available Qty', 'Value'];
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . '5', $header);
            $sheet->getStyle($col . '5')->getFont()->setBold(true);
            $sheet->getColumnDimension($col)->setAutoSize(true);
            $col++;
        }
    
        // ===========================
        // TABLE DATA
        // ===========================
        $row = 6;
        $count = 1;
    
        foreach ($transactions as $txn) {
            $data = $txn['sl1'];
            $stock = $txn['s']['stock'] ?? 0;
            $value = round($data['sku_price'] * $data['actual_balance'], 2);
    
            $sheet->setCellValue("A$row", $count);
            $sheet->setCellValue("B$row", $data['sku_name']);
            $sheet->setCellValue("C$row", $data['unit_name']);
            $sheet->setCellValue("D$row", (float)$data['sku_price']);
            $sheet->setCellValue("E$row", (float)$stock);
            $sheet->setCellValue("F$row", (float)$data['actual_balance']);
            $sheet->setCellValue("G$row", $value);
    
            $row++;
            $count++;
        }
    
        // ===========================
        // STYLE & FINALIZE
        // ===========================
        $sheet->getStyle("A5:G5")->getFont()->setBold(true);
        $sheet->setTitle('Stock Level');
    
        $objPHPExcel->setActiveSheetIndex(0);
    
        // ===========================
        // OUTPUT EXCEL
        // ===========================
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="Stock_Level_Report.xls"');
        header('Cache-Control: max-age=0');
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
        header('Cache-Control: cache, must-revalidate');
        header('Pragma: public');
    
        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $objWriter->save('php://output');
        exit;
    }

    public function indent_pdf() {
        if (!$this->Session->check('Auth.Location.id')) {
            return $this->redirect(['controller' => 'dashboard', 'action' => 'login']);
        }

        $this->loadModel('Inventory_purchase_order_item');    
        $this->layout = "login"; // or use 'ajax' or a dedicated PDF layout
    
        if ($this->request->is(['post', 'put'])) {
            $po_id = $this->request->data['Report']['poid'];
            // Secure: Use bound parameters
            $indents = $this->Inventory_indent->query(
                "SELECT *,(SELECT print_line1  FROM `locations`where id=inventory_indent.location_id) as print_line1
                ,(SELECT print_line2  FROM `locations`where id=inventory_indent.location_id) as print_line2
                ,(SELECT print_line3  FROM `locations`where id=inventory_indent.location_id) as print_line3
                ,(SELECT print_line1  FROM `locations`where id=inventory_indent.warehouse_id) as warehouse_print_line1
                ,(SELECT print_line2  FROM `locations`where id=inventory_indent.warehouse_id) as warehouse_print_line2
                ,(SELECT print_line3  FROM `locations`where id=inventory_indent.warehouse_id) as warehouse_print_line3
                FROM inventory_indent 
                 WHERE inventory_indent.indent_id = $po_id AND status != 0"
            );
    
            $items = $this->Inventory_indent_items->query(
                "SELECT * FROM inventory_indent_items 
                 WHERE indent_id = $po_id AND status != 0"
            );
            $location_id=$indents[0]['inventory_indent']['location_id'];
    
            // Get location details - default static for now
            $locationdetail = $this->Location->find('first', [
                'conditions' => ['Location.id' => $location_id]
            ]);
    
        // Fetch the associated PO for discount/charge info (for direct stockin)
            $poData = [];
            $poItems = [];
            if (!empty($indents[0]['inventory_indent']['indent_id'])) {
                $iid = $indents[0]['inventory_indent']['indent_id'];
                $poRows = $this->Inventory_purchase_order->find('first', [
                    'conditions' => ['internal_indent_id' => $iid],
                    'recursive'  => -1
                ]);
                if (!empty($poRows)) {
                    $poData = $poRows['Inventory_purchase_order'];
                    $poItemRows = $this->Inventory_purchase_order_item->find('all', [
                        'conditions' => ['p_o_id' => $poData['p_o_id']],
                        'recursive'  => -1
                    ]);
                    $poItems = $poItemRows;
                }
            }
            
            // Build a mapping of SKU -> Discount values from PO Items
            $poItemMap = [];
            if (!empty($poItems)) {
                foreach ($poItems as $pi) {
                    $skuId = $pi['Inventory_purchase_order_item']['sku_id'];
                    $poItemMap[$skuId] = $pi['Inventory_purchase_order_item'];
                }
            }

            // Send data to the view
            $this->set(compact('po_id', 'indents', 'items', 'locationdetail', 'poData', 'poItems', 'poItemMap'));
    
            // Optional: Force specific view if needed
            $this->render('indent_pdf'); // e.g., pdf_template.ctp
        }
    }
    
    public function indent_excel() {
        if (!$this->Session->check('Auth.Location.id')) {
            return $this->redirect(['controller' => 'dashboard', 'action' => 'login']);
        }

        $this->loadModel('Inventory_purchase_order_item');    
        $this->layout = "ajax"; // Use ajax layout to prevent standard HTML wrappers
        
        if ($this->request->is(['post', 'put'])) {
            $po_id = $this->request->data['Report']['poid'];
            // Secure: Use bound parameters
            $indents = $this->Inventory_indent->query(
                "SELECT *,(SELECT print_line1  FROM `locations`where id=inventory_indent.location_id) as print_line1
                ,(SELECT print_line2  FROM `locations`where id=inventory_indent.location_id) as print_line2
                ,(SELECT print_line3  FROM `locations`where id=inventory_indent.location_id) as print_line3
                ,(SELECT print_line1  FROM `locations`where id=inventory_indent.warehouse_id) as warehouse_print_line1
                ,(SELECT print_line2  FROM `locations`where id=inventory_indent.warehouse_id) as warehouse_print_line2
                ,(SELECT print_line3  FROM `locations`where id=inventory_indent.warehouse_id) as warehouse_print_line3
                FROM inventory_indent 
                 WHERE inventory_indent.indent_id = $po_id AND status != 0"
            );

            $items = $this->Inventory_indent_items->query(
                "SELECT * FROM inventory_indent_items 
                 WHERE indent_id = $po_id AND status != 0"
            );
            $location_id=$indents[0]['inventory_indent']['location_id'];

            // Get location details - default static for now
            $locationdetail = $this->Location->find('first', [
                'conditions' => ['Location.id' => $location_id]
            ]);

            // Fetch the associated PO for discount/charge info (for direct stockin)
            $poData = [];
            $poItems = [];
            $poItemMap = [];
            if (!empty($indents[0]['inventory_indent']['indent_id'])) {
                $iid = $indents[0]['inventory_indent']['indent_id'];
                $poRows = $this->Inventory_purchase_order->find('first', [
                    'conditions' => ['internal_indent_id' => $iid],
                    'recursive'  => -1
                ]);
                if (!empty($poRows)) {
                    $poData = $poRows['Inventory_purchase_order'];
                    $poItemRows = $this->Inventory_purchase_order_item->find('all', [
                        'conditions' => ['p_o_id' => $poData['p_o_id']],
                        'recursive'  => -1
                    ]);
                    $poItems = $poItemRows;
                }
            }
            
            // Build a mapping of SKU -> Discount values from PO Items
            if (!empty($poItems)) {
                foreach ($poItems as $pi) {
                    $skuId = $pi['Inventory_purchase_order_item']['sku_id'];
                    $poItemMap[$skuId] = $pi['Inventory_purchase_order_item'];
                }
            }
            
            // Send data to the view
            $this->set(compact('po_id', 'indents', 'items', 'locationdetail', 'poData', 'poItems', 'poItemMap'));

            header('Content-Type: application/vnd.ms-excel');
            header('Content-Disposition: attachment;filename="Indent_GRN_' . $po_id . '.xls"');
            header('Cache-Control: max-age=0');
            header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
            header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
            header('Cache-Control: cache, must-revalidate');

            $this->render('indent_excel');
        }
    }
        
	public function stock_damage()
    {
        if (!$this->Session->check('Auth.User.id')) {
            return $this->redirect(['controller' => 'dashboard', 'action' => 'login']);
        }
    
        $this->layout = "users";
    
        $startDate = date('m/d/Y', strtotime('-1 month'));
        $endDate = date('m/d/Y');
    
        if ($this->request->is(['post', 'put'])) {
            $daterange = $this->request->data['daterange'];
            $dates = explode(" - ", $daterange);
            $start_date = trim($dates[0]);
            $end_date = trim($dates[1]);
    
            $startDate = date('m/d/Y', strtotime($start_date));
            $endDate = date('m/d/Y', strtotime($end_date));
    
            $startdate = date('Y-m-d', strtotime($start_date));
            $enddate = date('Y-m-d', strtotime($end_date));
        } else {
            $startdate = date('Y-m-d', strtotime($startDate));
            $enddate = date('Y-m-d', strtotime($endDate));
        }
    
        // Session values
        $locationId = $this->Session->read('Auth.Location.id');
    
        // Build condition based on role
        $conditions = "created BETWEEN '$startdate 00:00:01' AND '$enddate 23:59:59'";
        if ($userRole !== 'admin') {
            $conditions .= " AND location_id = '$locationId'";
        }
    
        $users = $this->Inventory_damage->query("
            SELECT * FROM inventory_damage 
            WHERE $conditions and item_type='SKU' and type='Outlet' and status=1
            ORDER BY id DESC
        ");
    
        $yourStartDateValue = $startDate . ' - ' . $endDate;
        $this->set('startdate', $yourStartDateValue);
        $this->set(compact('users'));
    }
    
    public function damage_change_status() 
    {
        $this->autoRender = false; // Disable view rendering
    
        if ($this->request->is('post')) {
            $userId = $this->request->data['_id'];
    
            // Update user status
            $this->Inventory_damage->id = $userId;
            $newStatus = $this->Inventory_damage->field('status') == 1 ? 0 : 1;
            if ($this->Inventory_damage->save(['status' => $newStatus,'sync' => 0])) {
                echo json_encode(['status' => true]);
            } else {
                echo json_encode(['status' => false]);
            }
        }
    }

	public function create_damage()
    {
        if (!$this->Session->check('Auth.User.id')) {
            $this->redirect(['controller' => 'dashboard', 'action' => 'login']);
        }
    
        $this->layout = "users";
    
        $location_id = $this->Session->read('Auth.Location.id');
        $locations = $this->Location->find('all', [
            'conditions' => ['status' => 1, 'id' => $location_id,'type' => 'Outlet']
        ]);
    
        $this->set(compact('locations'));
    
        $users = $this->Inventory_sku->find('all', [
            'conditions' => ['Inventory_sku.status' => 1],
            'order' => ['Inventory_sku.name ASC']
        ]);
        $this->set(compact('users'));
    }

    public function add_damage() 
    {
        $this->autoRender = false;
    
        if (!$this->Session->check('Auth.User.id')) {
            echo json_encode(['response_code' => 0, 'data' => 'User session expired.']);
            return;
        }
    
        $id = $this->Session->read('Auth.Location.id');
        $name = $this->Session->read('Auth.Location.name');
    
        if (isset($_POST['checkedData']) && isset($_POST['location_id'])) {
            $checkeddatas = json_decode($_POST['checkedData'], true);
            $location_id = (int)$_POST['location_id'];
            $damage_date = $_POST['damage_date'];
            
            // Validate location
            $location = $this->Location->find('first', [
                'conditions' => ['Location.id' => $location_id],
                'fields' => ['id', 'name'],
                'recursive' => -1
            ]);
    
            if (empty($location)) {
                echo json_encode(['response_code' => 0, 'data' => 'Invalid location selected.']);
                return;
            }
    
            // Generate unique indent_id
            do {
                // $txnid = '';
                $txnid = rand(1, 9);
                for ($i = 0; $i < 10; $i++) {
                    $txnid .= rand(0, 9);
                }
    
                $existing = $this->Inventory_damage->find('first', [
                    'conditions' => ['damage_id' => $txnid],
                    'recursive' => -1
                ]);
            } while ($existing);
    
            $amount = 0;
            $itemsSaved = true;
    
            foreach ($checkeddatas as $item) {
                $skuid = $item['id'];
                $price = $item['price'];
                $quantity = $item['sku_quantity'];
                
                $skuData = [
                    'location_id' => $location_id,
                    'location_name' => $location['Location']['name'],
                    'damage_id' => $txnid,
                    'item_id' => $skuid,
                    'item_name' => $item['sku_name'],
                    'item_category_id' => $item['cat_id'],
                    'item_category_name' => $item['cat_name'],
                    'unit_id' => $item['unit_id'],
                    'unit_name' => $item['unit_name'],
                    'item_price' => $price,
                    'quantity' => $quantity,
                    'amount' => $price*$quantity
                ];
                $this->Inventory_damage_item->create(); // ensure new row
                if (!$this->Inventory_damage_item->save($skuData)) {
                    $itemsSaved = false;
                    break;
                }
                
                $amount=$amount+($price*$quantity);
                
                $ledgerprevdetails = $this->Stock_ledger->find('first', [
                    'conditions' => ['sku_id' => $skuid,'type'=>'Outlet','item_type'=>'SKU','store_id'=>$location_id],
                    'fields' => ['actual_balance'],
                    'order' => ['id DESC'],
                    'recursive' => -1
                ]);
                
                $ledger_prev_quantity = !empty($ledgerprevdetails['Stock_ledger']['actual_balance']) 
                    ? $ledgerprevdetails['Stock_ledger']['actual_balance'] 
                    : 0;
                
                $newQuantity = $ledger_prev_quantity - $quantity;
                // print_r($ledger_prev_quantity);exit;
                $ledgerData['Stock_ledger'] = [
                        'store_id'        => $location_id,
                        'sku_id'          => $skuid,
                        'sku_name'        => $item['sku_name'],
                        'sku_price'       => $price,
                        'unit_id'         => $item['unit_id'],
                        'unit_name'       => $item['unit_name'],
                        'record_id'       => $txnid,
                        'record_type'     => 'damage',
                        'quantity'        => $quantity,
                        'balance_stock'   => $newQuantity,
                        'actual_balance'  => $newQuantity,
                        'quantity_type'   => 'debit',
                        'type'            => 'Outlet',
                        'created'         => date('Y-m-d H:i:s')
                ];
				$this->Stock_ledger->saveAll($ledgerData['Stock_ledger']);
                
            }
    
            if ($itemsSaved) {
                $damageData = [
                    'location_id' => $location_id,
                    'location_name' => $location['Location']['name'],
                    'damage_id' => $txnid,
                    'damage_date' => $damage_date,
                    'type' => 'Outlet',
                    'item_type' => 'SKU',
                    'amount' => $amount
                ];
                
                $this->Inventory_damage->create();
                if ($this->Inventory_damage->save($damageData)) {
                    echo json_encode(['response_code' => 1, 'data' => 'saved']);
                } else {
                    echo json_encode(['response_code' => 0, 'data' => 'Failed to save damage master data.']);
                }
            } else {
                echo json_encode(['response_code' => 0, 'data' => 'Failed to save one or more items.']);
            }
        } else {
            echo json_encode(['response_code' => 0, 'data' => 'Invalid request. Missing data.']);
        }
    }

    public function view_damage()
	{
	    if(!$this->Session->check('Auth.User.id')){
			$this->redirect(array('controller'=>'dashboard','action' => 'login'));		
		}
        
	    $this->layout = "users";
		$id=$_REQUEST['id'];
    
        $indents = $this->Inventory_damage->query("SELECT * FROM inventory_damage where damage_id=$id and status=1");
        $this->set(compact('indents'));
        
		$items = $this->Inventory_damage_item->query("SELECT * FROM inventory_damage_items where damage_id=$id and status=1");
        
        $this->set(compact('items'));
        
        
	}
	
	public function edit_damage()
    {
        if (!$this->Session->check('Auth.User.id')) {
            return $this->redirect(['controller' => 'dashboard', 'action' => 'login']);
        }
    
        $this->layout = "users";
        $id = $_REQUEST['id'];
    
        $user_location = $this->Session->read('Auth.Location.id');
    
        // Location dropdown data
        
        $locations = $this->Location->find('list', [
            'conditions' => ['status' => 1, 'id' => $user_location,'type' => 'Outlet']
        ]);
    
        // Fetch indent data
        $user = "SELECT *,
            (SELECT quantity FROM inventory_damage_items
             WHERE damage_id='$id' AND item_id=inventory_skus.id and status=1) as quantity 
            FROM inventory_skus 
            WHERE inventory_skus.status=1 
            ORDER BY inventory_skus.name ASC";
       
        $users = $this->Inventory_sku->query($user);
         
        // Fetch existing indent record
        $indentData = $this->Inventory_damage->find('first', [
            'conditions' => ['damage_id' => $id,'status' => 1],
            'recursive' => -1
        ]);
        $selected_location_id = $indentData['Inventory_damage']['location_id'];
        $selected_date = $indentData['Inventory_damage']['damage_date'];
    
        $this->set(compact('users', 'id', 'locations', 'selected_location_id', 'selected_date'));
    }

    public function update_damage() 
    {
        $this->autoRender = false;
    
        $id = $this->Session->read('Auth.Location.id');
        $name = $this->Session->read('Auth.Location.name');
    
        if (isset($_POST['checkedData'])) {
            $checkeddatas = json_decode($_POST['checkedData'], true);
            $indentid = $_POST['indent_id'];
            $damage_date = $_POST['damage_date'];
            $location_id = isset($_POST['location_id']) ? $_POST['location_id'] : null;
            $location_name = '';
            
            // Optional: fetch location name if not passed from frontend
            if (!empty($location_id)) {
                $loc = $this->Location->find('first', [
                    'conditions' => ['Location.id' => $location_id],
                    'fields' => ['Location.name'],
                    'recursive' => -1
                ]);
                $location_name = isset($loc['Location']['name']) ? $loc['Location']['name'] : '';
            }
            
            $items = $this->Inventory_damage_item->find('all', [
                'conditions' => [
                    'damage_id' => $indentid,
                    'status' => 1
                ],
                'recursive' => -1
            ]);
            
            foreach ($items as $lastdata) {
                $damagedata = $lastdata['Inventory_damage_item']; // Use the correct model alias
                $skuid       = $damagedata['item_id'];
                $price       = $damagedata['item_price'];
                $sku_name    = $damagedata['item_name'];
                $sku_brand   = isset($damagedata['sku_brand']) ? $damagedata['sku_brand'] : ''; // if available
                $unit_name   = $damagedata['unit_name'];
                $unit_id     = $damagedata['unit_id'];
                $cat_id      = $damagedata['item_category_id'];
                $cat_name    = $damagedata['item_category_name'];
                $quantity    = $damagedata['quantity'];
            
                if (!empty($quantity)) {
                    $ledgerprevdetails = $this->Stock_ledger->find('first', [
                        'conditions' => [
                            'sku_id' => $skuid,
                            'type' => 'Outlet',
                            'item_type'=>'SKU',
                            'store_id' => $location_id
                        ],
                        'fields' => ['actual_balance'],
                        'order' => ['id DESC'],
                        'recursive' => -1
                    ]);
            
                    $ledger_prev_quantity = !empty($ledgerprevdetails['Stock_ledger']['actual_balance']) 
                        ? $ledgerprevdetails['Stock_ledger']['actual_balance'] 
                        : 0;
            
                    $newQuantity = $ledger_prev_quantity + $quantity;
            
                    $ledgerData = [
                        'Stock_ledger' => [
                            'store_id'        => $location_id,
                            'sku_id'          => $skuid,
                            'sku_name'        => $sku_name,
                            'sku_price'       => $price,
                            'unit_id'         => $unit_id,
                            'unit_name'       => $unit_name,
                            'record_id'       => $indentid,
                            'record_type'     => 'reverse_damage',
                            'quantity'        => $quantity,
                            'balance_stock'   => $newQuantity,
                            'actual_balance'  => $newQuantity,
                            'quantity_type'   => 'credit',
                            'type'            => 'Outlet',
                            'created'         => date('Y-m-d H:i:s')
                        ]
                    ];
            
                    $this->Stock_ledger->create(); // ensure a fresh entry
                    $this->Stock_ledger->save($ledgerData['Stock_ledger']);
                }
            }

    
            
            // Delete existing indent and items
            $this->Inventory_damage_item->query("UPDATE inventory_damage_items SET  status=0,sync = 0,local_sync=0 WHERE damage_id = " . (int)$indentid);
            $this->Inventory_damage->query("UPDATE inventory_damage SET  status=0,sync = 0,local_sync=0 WHERE damage_id = " . (int)$indentid);
    
            $amount = 0;
            $chk = false;
    
            foreach ($checkeddatas as $checkeddata) {
                $skuid = $checkeddata['id'];
                $price = $checkeddata['price'];
                $sku_name = $checkeddata['sku_name'];
                $sku_brand = $checkeddata['sku_brand'];
                $unit_name = $checkeddata['unit_name'];
                $unit_id = $checkeddata['unit_id'];
                $cat_id = $checkeddata['cat_id'];
                $cat_name = $checkeddata['cat_name'];
                $quantity = $checkeddata['sku_quantity'];
                
                if (!empty($quantity)) {
                    $itemData = array(
                        'location_id' => $location_id,
                        'location_name' => $location_name,
                        'damage_id' => $indentid,
                        'item_id' => $skuid,
                        'item_name' => $sku_name,
                        'item_category_id' => $cat_id,
                        'item_category_name' => $cat_name,
                        'unit_id' => $unit_id,
                        'unit_name' => $unit_name,
                        'item_price' => $price,
                        'quantity' => $quantity,
                        'amount' => $price*$quantity
                    );
                    
                    if ($this->Inventory_damage_item->saveAll($itemData)) {
                        
                        $ledgerprevdetails = $this->Stock_ledger->find('first', [
                            'conditions' => ['sku_id' => $skuid,'type'=>'Outlet','item_type'=>'SKU','store_id'=>$location_id],
                            'fields' => ['actual_balance'],
                            'order' => ['id DESC'],
                            'recursive' => -1
                        ]);
                        
                        $ledger_prev_quantity = !empty($ledgerprevdetails['Stock_ledger']['actual_balance']) 
                            ? $ledgerprevdetails['Stock_ledger']['actual_balance'] 
                            : 0;
                        
                        $newQuantity = $ledger_prev_quantity - $quantity;
                        // print_r($ledger_prev_quantity);exit;
                        $ledgerData['Stock_ledger'] = [
                                'store_id'        => $location_id,
                                'sku_id'          => $skuid,
                                'sku_name'        => $sku_name,
                                'sku_price'       => $price,
                                'unit_id'         => $unit_id,
                                'unit_name'       => $unit_name,
                                'record_id'       => $indentid,
                                'record_type'     => 'update_damage',
                                'quantity'        => $quantity,
                                'balance_stock'   => $newQuantity,
                                'actual_balance'  => $newQuantity,
                                'quantity_type'   => 'debit',
                                'type'            => 'Outlet',
                                'created'         => date('Y-m-d H:i:s')
                        ];
        				$this->Stock_ledger->saveAll($ledgerData['Stock_ledger']);
                        
                        $chk = true;
                    } else {
                        $chk = false;
                        $this->Flash->error(__('Damage could not be updated. Please, try again.'));
                    }
    
                    // $productamount = $quantity * $price;
                    // $amount += $productamount;
                    $amount += ($price*$quantity);
                }
            }
    
            if ($chk) {
                $indentData = array(
                    'location_id' => $location_id,
                    'location_name' => $location_name,
                    'damage_id' => $indentid,
                    'damage_date' => $damage_date,
                    'type' => 'Outlet',
                    'item_type' => 'SKU',
                    'amount' => $amount
                );
    
                if ($this->Inventory_damage->saveAll($indentData)) {
                    echo json_encode(['response_code' => 1, 'data' => 'Updated']);
                } else {
                    echo json_encode(['response_code' => 0, 'data' => 'Damage could not be updated. Please, try again.']);
                }
            } else {
                echo json_encode(['response_code' => 0, 'data' => 'Damage items could not be updated. Please, try again.']);
            }
        }
    }
    
    public function semi_stock_damage()
    {
        if (!$this->Session->check('Auth.Location.id')) {
            return $this->redirect(['controller' => 'dashboard', 'action' => 'login']);
        }
    
        $this->layout = "users";
    
        $startDate = date('m/d/Y', strtotime('-1 month'));
        $endDate = date('m/d/Y');
    
        if ($this->request->is(['post', 'put'])) {
            $daterange = $this->request->data['daterange'];
            $dates = explode(" - ", $daterange);
            $start_date = trim($dates[0]);
            $end_date = trim($dates[1]);
    
            $startDate = date('m/d/Y', strtotime($start_date));
            $endDate = date('m/d/Y', strtotime($end_date));
    
            $startdate = date('Y-m-d', strtotime($start_date));
            $enddate = date('Y-m-d', strtotime($end_date));
        } else {
            $startdate = date('Y-m-d', strtotime($startDate));
            $enddate = date('Y-m-d', strtotime($endDate));
        }
    
        // Session values
        $userRole = $this->Session->read('Auth.User.role');
        $locationId = $this->Session->read('Auth.Location.id');
    
        // Build condition based on role
        $conditions = "created BETWEEN '$startdate 00:00:01' AND '$enddate 23:59:59'";
        if ($userRole !== 'admin') {
            $conditions .= " AND location_id = '$locationId'";
        }
    
        $users = $this->Inventory_damage->query("
            SELECT * FROM inventory_damage 
            WHERE $conditions and item_type='semifinished' and type='Outlet'
            ORDER BY id DESC
        ");
    
        $yourStartDateValue = $startDate . ' - ' . $endDate;
        $this->set('startdate', $yourStartDateValue);
        $this->set(compact('users'));
    }
    
    public function semi_damage_change_status() 
    {
        $this->autoRender = false; // Disable view rendering
    
        if ($this->request->is('post')) {
            $userId = $this->request->data['_id'];
    
            // Update user status
            $this->Inventory_damage->id = $userId;
            $newStatus = $this->Inventory_damage->field('status') == 1 ? 0 : 1;
            if ($this->Inventory_damage->save(['status' => $newStatus,'sync' => 0,'local_sync' => 0])) {
                echo json_encode(['status' => true]);
            } else {
                echo json_encode(['status' => false]);
            }
        }
    }

	public function semi_create_damage()
    {
        if (!$this->Session->check('Auth.Location.id')) {
            $this->redirect(['controller' => 'dashboard', 'action' => 'login']);
        }
    
        $this->layout = "users";
    
        if ($this->Session->read('Auth.User.role') == 'admin') {
            $locations = $this->Location->find('all', ['conditions' => ['status' => 1,'type' => 'Outlet']]);
        } else {
            $location_id = $this->Session->read('Auth.Location.id');
            $locations = $this->Location->find('all', [
                'conditions' => ['status' => 1, 'id' => $location_id,'type' => 'Outlet']
            ]);
        }
    
        $this->set(compact('locations'));
    
        $users = $this->Inventory_semifinished->find('all', [
            'conditions' => ['Inventory_semifinished.status' => 1],
            'order' => ['Inventory_semifinished.name DESC']
        ]);
        $this->set(compact('users'));
    }

    public function semi_add_damage() 
    {
        $this->autoRender = false;
    
        if (!$this->Session->check('Auth.User.id')) {
            echo json_encode(['response_code' => 0, 'data' => 'User session expired.']);
            return;
        }
    
        $id = $this->Session->read('Auth.User.id');
        $name = $this->Session->read('Auth.User.name');
    
        if (isset($_POST['checkedData']) && isset($_POST['location_id'])) {
            $checkeddatas = json_decode($_POST['checkedData'], true);
            $location_id = (int)$_POST['location_id'];
            $damage_date = $_POST['damage_date'];
            
            // Validate location
            $location = $this->Location->find('first', [
                'conditions' => ['Location.id' => $location_id],
                'fields' => ['id', 'name'],
                'recursive' => -1
            ]);
    
            if (empty($location)) {
                echo json_encode(['response_code' => 0, 'data' => 'Invalid location selected.']);
                return;
            }
    
            // Generate unique indent_id
            do {
                // $txnid = '';
                $txnid = rand(1, 9);
                for ($i = 0; $i < 10; $i++) {
                    $txnid .= rand(0, 9);
                }
    
                $existing = $this->Inventory_damage->find('first', [
                    'conditions' => ['damage_id' => $txnid],
                    'recursive' => -1
                ]);
            } while ($existing);
    
            $amount = 0;
            $itemsSaved = true;
    
            foreach ($checkeddatas as $item) {
                $skuid = $item['id'];
                $price = $item['price'];
                $quantity = $item['sku_quantity'];
                
                $skuData = [
                    'location_id' => $location_id,
                    'location_name' => $location['Location']['name'],
                    'damage_id' => $txnid,
                    'item_id' => $skuid,
                    'item_name' => $item['sku_name'],
                    'item_category_id' => $item['cat_id'],
                    'item_category_name' => $item['cat_name'],
                    'unit_id' => $item['unit_id'],
                    'unit_name' => $item['unit_name'],
                    'item_price' => $price,
                    'quantity' => $quantity,
                    'amount' => $price*$quantity
                ];
                $this->Inventory_damage_item->create(); // ensure new row
                if (!$this->Inventory_damage_item->save($skuData)) {
                    $itemsSaved = false;
                    break;
                }
                
                $amount=$amount+($price*$quantity);
                
                $ledgerprevdetails = $this->Stock_ledger->find('first', [
                    'conditions' => ['sku_id' => $skuid,'type'=>'OUTLET','item_type'=>'semifinished','store_id'=>$location_id],
                    'fields' => ['actual_balance'],
                    'order' => ['id DESC'],
                    'recursive' => -1
                ]);
                
                $ledger_prev_quantity = !empty($ledgerprevdetails['Stock_ledger']['actual_balance']) 
                    ? $ledgerprevdetails['Stock_ledger']['actual_balance'] 
                    : 0;
                
                $newQuantity = $ledger_prev_quantity - $quantity;
                // print_r($ledger_prev_quantity);exit;
                $ledgerData['Stock_ledger'] = [
                        'store_id'        => $location_id,
                        'sku_id'          => $skuid,
                        'sku_name'        => $item['sku_name'],
                        'sku_price'       => $price,
                        'unit_id'         => $item['unit_id'],
                        'unit_name'       => $item['unit_name'],
                        'record_id'       => $txnid,
                        'record_type'     => 'damage',
                        'quantity'        => $quantity,
                        'balance_stock'   => $newQuantity,
                        'actual_balance'  => $newQuantity,
                        'quantity_type'   => 'debit',
                        'type'            => 'OUTLET',
                        'item_type'       => 'semifinished',
                        'created'         => date('Y-m-d H:i:s')
                ];
				$this->Stock_ledger->saveAll($ledgerData['Stock_ledger']);
                
            }
    
            if ($itemsSaved) {
                $damageData = [
                    'location_id' => $location_id,
                    'location_name' => $location['Location']['name'],
                    'damage_id' => $txnid,
                    'damage_date' => $damage_date,
                    'type' => 'Outlet',
                    'item_type' => 'semifinished',
                    'amount' => $amount
                ];
                
                $this->Inventory_damage->create();
                if ($this->Inventory_damage->save($damageData)) {
                    echo json_encode(['response_code' => 1, 'data' => 'saved']);
                } else {
                    echo json_encode(['response_code' => 0, 'data' => 'Failed to save damage master data.']);
                }
            } else {
                echo json_encode(['response_code' => 0, 'data' => 'Failed to save one or more items.']);
            }
        } else {
            echo json_encode(['response_code' => 0, 'data' => 'Invalid request. Missing data.']);
        }
    }

    public function semi_view_damage()
	{
	    if(!$this->Session->check('Auth.Location.id')){
			$this->redirect(array('controller'=>'dashboard','action' => 'login'));		
		}
        
	    $this->layout = "users";
		$id=$_REQUEST['id'];
    
        $indents = $this->Inventory_damage->query("SELECT * FROM inventory_damage where damage_id=$id and status=1");
        $this->set(compact('indents'));
        
		$items = $this->Inventory_damage_item->query("SELECT * FROM inventory_damage_items where damage_id=$id and status=1");
        
        $this->set(compact('items'));
        
        
	}
	
	public function semi_edit_damage()
    {
        if (!$this->Session->check('Auth.Location.id')) {
            return $this->redirect(['controller' => 'dashboard', 'action' => 'login']);
        }
    
        $this->layout = "users";
        $id = $_REQUEST['id'];
    
        $user_id = $this->Session->read('Auth.User.id');
        $role = $this->Session->read('Auth.User.role');
        $user_location = $this->Session->read('Auth.Location.id');
    
        // Location dropdown data
        if ($role == 'admin') {
            $locations = $this->Location->find('list', ['conditions' => ['status' => 1,'type' => 'Outlet']]);
        } else {
            $locations = $this->Location->find('list', [
                'conditions' => ['status' => 1, 'id' => $user_location,'type' => 'Outlet']
            ]);
        }
    
        // Fetch indent data
        $user = "SELECT *,
            (SELECT quantity FROM inventory_damage_items
             WHERE damage_id='$id' AND item_id=inventory_semifinished.id and status=1) as quantity 
            FROM inventory_semifinished 
            WHERE inventory_semifinished.status=1 
            ORDER BY inventory_semifinished.name DESC";
       
        $users = $this->Inventory_semifinished->query($user);
         
        // Fetch existing indent record
        $indentData = $this->Inventory_damage->find('first', [
            'conditions' => ['damage_id' => $id,'status' => 1],
            'recursive' => -1
        ]);
        $selected_location_id = $indentData['Inventory_damage']['location_id'];
        $selected_date = $indentData['Inventory_damage']['damage_date'];
    
        $this->set(compact('users', 'id', 'locations', 'selected_location_id', 'selected_date'));
    }

    public function semi_update_damage() 
    {
        $this->autoRender = false;
    
        $id = $this->Session->read('Auth.User.id');
        $name = $this->Session->read('Auth.User.name');
    
        if (isset($_POST['checkedData'])) {
            $checkeddatas = json_decode($_POST['checkedData'], true);
            $indentid = $_POST['indent_id'];
            $damage_date = $_POST['damage_date'];
            $location_id = isset($_POST['location_id']) ? $_POST['location_id'] : null;
            $location_name = '';
            
            // Optional: fetch location name if not passed from frontend
            if (!empty($location_id)) {
                $loc = $this->Location->find('first', [
                    'conditions' => ['Location.id' => $location_id],
                    'fields' => ['Location.name'],
                    'recursive' => -1
                ]);
                $location_name = isset($loc['Location']['name']) ? $loc['Location']['name'] : '';
            }
            
            $items = $this->Inventory_damage_item->find('all', [
                'conditions' => [
                    'damage_id' => $indentid,
                    'status' => 1
                ],
                'recursive' => -1
            ]);
            
            foreach ($items as $lastdata) {
                $damagedata = $lastdata['Inventory_damage_item']; // Use the correct model alias
                $skuid       = $damagedata['item_id'];
                $price       = $damagedata['item_price'];
                $sku_name    = $damagedata['item_name'];
                $sku_brand   = isset($damagedata['sku_brand']) ? $damagedata['sku_brand'] : ''; // if available
                $unit_name   = $damagedata['unit_name'];
                $unit_id     = $damagedata['unit_id'];
                $cat_id      = $damagedata['item_category_id'];
                $cat_name    = $damagedata['item_category_name'];
                $quantity    = $damagedata['quantity'];
            
                if (!empty($quantity)) {
                    $ledgerprevdetails = $this->Stock_ledger->find('first', [
                        'conditions' => [
                            'sku_id' => $skuid,
                            'item_type' => 'semifinished',
                            'type' => 'OUTLET',
                            'store_id' => $location_id
                        ],
                        'fields' => ['actual_balance'],
                        'order' => ['id DESC'],
                        'recursive' => -1
                    ]);
            
                    $ledger_prev_quantity = !empty($ledgerprevdetails['Stock_ledger']['actual_balance']) 
                        ? $ledgerprevdetails['Stock_ledger']['actual_balance'] 
                        : 0;
            
                    $newQuantity = $ledger_prev_quantity + $quantity;
            
                    $ledgerData = [
                        'Stock_ledger' => [
                            'store_id'        => $location_id,
                            'sku_id'          => $skuid,
                            'sku_name'        => $sku_name,
                            'sku_price'       => $price,
                            'unit_id'         => $unit_id,
                            'unit_name'       => $unit_name,
                            'record_id'       => $indentid,
                            'record_type'     => 'reverse_damage',
                            'quantity'        => $quantity,
                            'balance_stock'   => $newQuantity,
                            'actual_balance'  => $newQuantity,
                            'quantity_type'   => 'credit',
                            'item_type'       => 'semifinished',
                            'type'            => 'OUTLET',
                            'created'         => date('Y-m-d H:i:s')
                        ]
                    ];
            
                    $this->Stock_ledger->create(); // ensure a fresh entry
                    $this->Stock_ledger->save($ledgerData['Stock_ledger']);
                }
            }

    
            
            // Delete existing indent and items
            $this->Inventory_damage_item->query("UPDATE inventory_damage_items SET status=0, sync=0, local_sync=0  WHERE damage_id = '$indentid'");
            $this->Inventory_damage->query("UPDATE inventory_damage SET status=0, sync=0, local_sync=0  WHERE damage_id = '$indentid'");
    
            $amount = 0;
            $chk = false;
    
            foreach ($checkeddatas as $checkeddata) {
                $skuid = $checkeddata['id'];
                $price = $checkeddata['price'];
                $sku_name = $checkeddata['sku_name'];
                $sku_brand = $checkeddata['sku_brand'];
                $unit_name = $checkeddata['unit_name'];
                $unit_id = $checkeddata['unit_id'];
                $cat_id = $checkeddata['cat_id'];
                $cat_name = $checkeddata['cat_name'];
                $quantity = $checkeddata['sku_quantity'];
                
                if (!empty($quantity)) {
                    $itemData = array(
                        'location_id' => $location_id,
                        'location_name' => $location_name,
                        'damage_id' => $indentid,
                        'item_id' => $skuid,
                        'item_name' => $sku_name,
                        'item_category_id' => $cat_id,
                        'item_category_name' => $cat_name,
                        'unit_id' => $unit_id,
                        'unit_name' => $unit_name,
                        'item_price' => $price,
                        'quantity' => $quantity,
                        'amount' => $price*$quantity
                    );
                    
                    if ($this->Inventory_damage_item->saveAll($itemData)) {
                        
                        $ledgerprevdetails = $this->Stock_ledger->find('first', [
                            'conditions' => ['sku_id' => $skuid,'type'=>'OUTLET','item_type'=>'semifinished','store_id'=>$location_id],
                            'fields' => ['actual_balance'],
                            'order' => ['id DESC'],
                            'recursive' => -1
                        ]);
                        
                        $ledger_prev_quantity = !empty($ledgerprevdetails['Stock_ledger']['actual_balance']) 
                            ? $ledgerprevdetails['Stock_ledger']['actual_balance'] 
                            : 0;
                        
                        $newQuantity = $ledger_prev_quantity - $quantity;
                        // print_r($ledger_prev_quantity);exit;
                        $ledgerData['Stock_ledger'] = [
                                'store_id'        => $location_id,
                                'sku_id'          => $skuid,
                                'sku_name'        => $sku_name,
                                'sku_price'       => $price,
                                'unit_id'         => $unit_id,
                                'unit_name'       => $unit_name,
                                'record_id'       => $indentid,
                                'record_type'     => 'update_damage',
                                'quantity'        => $quantity,
                                'balance_stock'   => $newQuantity,
                                'actual_balance'  => $newQuantity,
                                'quantity_type'   => 'debit',
                                'type'            => 'OUTLET',
                                'item_type'       => 'semifinished',
                                'created'         => date('Y-m-d H:i:s')
                        ];
        				$this->Stock_ledger->saveAll($ledgerData['Stock_ledger']);
                        
                        $chk = true;
                    } else {
                        $chk = false;
                        $this->Flash->error(__('Damage could not be updated. Please, try again.'));
                    }
    
                    // $productamount = $quantity * $price;
                    // $amount += $productamount;
                    $amount += ($price*$quantity);
                }
            }
    
            if ($chk) {
                $indentData = array(
                    'location_id' => $location_id,
                    'location_name' => $location_name,
                    'damage_id' => $indentid,
                    'damage_date' => $damage_date,
                    'type' => 'OUTLET',
                    'item_type' => 'semifinished',
                    'amount' => $amount
                );
    
                if ($this->Inventory_damage->saveAll($indentData)) {
                    echo json_encode(['response_code' => 1, 'data' => 'Updated']);
                } else {
                    echo json_encode(['response_code' => 0, 'data' => 'Damage could not be updated. Please, try again.']);
                }
            } else {
                echo json_encode(['response_code' => 0, 'data' => 'Damage items could not be updated. Please, try again.']);
            }
        }
    }
    
    public function auditstock()
    {
        if (!$this->Session->check('Auth.Location.id')) {
            return $this->redirect(['controller' => 'dashboard', 'action' => 'login']);
        }
    
        $this->layout = "users";
    
        // Default date range (last 30 days)
        $startDate = date('m/d/Y', strtotime('-30 days'));
        $endDate = date('m/d/Y');
        
        // If form submitted, get date range
        if($this->request->is('post', 'put'))
        {
			$daterange= $this->request->data['daterange'];
            $dates = explode(" - ", $daterange);
            $start_date = trim($dates[0]);
            $startDate = date('m/d/Y', strtotime($start_date));
            $end_date = trim($dates[1]);
            $endDate = date('m/d/Y', strtotime($end_date));
            $startdate= date('Y-m-d', strtotime($start_date));
            $enddate= date('Y-m-d', strtotime($end_date));
        }else{
            $startdate= date('Y-m-d', strtotime($startDate));
            $enddate= date('Y-m-d', strtotime($endDate));
        } 
    
        // Auth User Info
        $authUser = $this->Session->read('Auth.Location');
        $storeId = $authUser['id'] ?? 0;
        
        $sdate=$startdate.' 00:00:01';
        $edate=$enddate.' 23:59:59';
       
       
        $preparedStocks = $this->Audit_stock->find('all', [
            'conditions' => [
                'Audit_stock.status' => 1,
                'Audit_stock.updateon BETWEEN ? AND ?' => [$sdate, $edate],
                'Audit_stock.item_type' => 'SKU',
                'Audit_stock.type' => 'Outlet',
                'Audit_stock.store_id' => $storeId
            ],
            'fields' => ['Audit_stock.*'], // Ensure ID is included
            'order' => ['Audit_stock.id' => 'DESC'],
            'recursive' => 1
        ]);
        

    
        // Pass to view
        $daterangeFormatted = $startDate . ' - ' . $endDate;
        $this->set(compact('daterangeFormatted', 'preparedStocks'));
    }

    public function create_audit_stock()
    {
        if (!$this->Session->check('Auth.Location.id')) {
            return $this->redirect(['controller' => 'dashboard', 'action' => 'login']);
        }
    
        $this->layout = "users";
    
        $today = date("Y-m-d");
        $currentTime = date("H:i");
        $this->set(compact('today', 'currentTime'));
    
        // Load SKU list
        $skus = $this->Inventory_sku->query("SELECT * FROM inventory_skus WHERE status = 1 ORDER BY name ASC");
        $this->set(compact('skus'));
    }
    
    public function add_audit_stock()
    {
        
        $data = $this->request->data;
        
        $remarks = trim($data['remarks']);
        $auditDate = $data['audit_date'] ?? $today;
        $auditTime = $data['audit_time'] ?? $currentTime;
    
        $createdTime = date('Y-m-d H:i:s');
            
        $authUser = $this->Session->read('Auth.Location');
        $storeId = $authUser['id'] ?? 0;
        
        $loc = $this->Location->find('first', [
            'conditions' => ['Location.id' => $storeId],
            'fields' => ['Location.name'],
            'recursive' => -1
        ]);
        $store_name = $loc['Location']['name'] ?? '';
    
        // ✅ Generate POS-Audit ID
        do {
            $pos_audit_id = substr(preg_replace('/\D/', '', sha1(uniqid())), 0, 12);
            if ($pos_audit_id[0] === '0') {
                $pos_audit_id[0] = rand(1,9);
            }
            $existsMain = $this->Audit_stock->find('count', [
                'conditions' => ['Audit_stock.pos_audit_id' => $pos_audit_id]
            ]);
        } while ($existsMain > 0);
    
        $auditto = $auditDate . ' ' . $auditTime . ':00';
        $currentServerTime = date('Y-m-d H:i:s');
    
        if (strtotime($auditto) < strtotime($currentServerTime)) {
            $auditto = $currentServerTime;
        }
    
        // ✅ Read selected items (from AJAX)
        $checkedData = json_decode($data['checkedData'], true);
    
        if (empty($checkedData)) {
            $this->response->body(json_encode(['status' => 'error', 'message' => 'No Items']));
            return $this->response;
        }
    
        // ✅ Save Header
        $header = [
            'pos_audit_id' => $pos_audit_id,
            'store_id' => $storeId,
            'store_name' => $store_name,
            'type' => 'Outlet',
            'remarks' => $remarks,
            'status' => 1,
            'select_all' => 0,
            'updateon' => $createdTime
        ];
        $this->Audit_stock->create();
        $this->Audit_stock->save($header);
    
        // ✅ Process each SKU
        foreach ($checkedData as $item) {
    
            $skuId = (int)$item['id'];
            $qty = floatval($item['quantity']);
            if ($qty <= 0) continue;
    
            $skuName = $item['sku_name'];
            $unitId = (int)$item['unit_id'];
            $unitName = $item['unit_name'];
            $sku_price = floatval($item['price']);
    
            // ✅ LAST AUDIT DATA
            $lastAudit = $this->Audit_stock_items->find('first', [
                'conditions' => [
                    'sku_id' => $skuId,
                    'item_type' => 'sku',
                    'type' => 'Outlet',
                    'status' => 1,
                    'store_id' => $storeId
                ],
                'order' => ['id DESC'],
                'fields' => ['physicalstock', 'auditto'],
                'recursive' => -1
            ]);
    
            $openingstock = !empty($lastAudit) ? floatval($lastAudit['Audit_stock_items']['physicalstock']) : 0;
            $auditfrom = !empty($lastAudit) ? $lastAudit['Audit_stock_items']['auditto'] : '2022-01-01 00:00:01';
    
            // ✅ GET purchase, consumption, damage
            $fetch_variance = "
                SELECT 
                    SUM(CASE WHEN quantity_type = 'credit' THEN quantity ELSE 0 END) AS purchase,
                    SUM(CASE WHEN record_type IN ('stockout','stockout_modifier','process_indent','stock_transfer_to_kitchen') THEN quantity ELSE 0 END) AS consumption,
                    (
                        SUM(CASE WHEN record_type IN ('damage','update_damage') AND quantity_type = 'debit' THEN quantity ELSE 0 END)
                        -
                        SUM(CASE WHEN record_type = 'reverse_damage' AND quantity_type = 'credit' THEN quantity ELSE 0 END)
                    ) AS damage
                FROM stock_ledger
                WHERE store_id = '$storeId'
                  AND item_type = 'SKU'
                  AND sku_id = $skuId
                  AND type = 'Outlet'
                  AND created BETWEEN '$auditfrom' AND '$auditto'
            ";
    
            $res = $this->Stock_ledger->query($fetch_variance);
            $purchase = floatval($res[0][0]['purchase'] ?? 0);
            $consumption = floatval($res[0][0]['consumption'] ?? 0);
            $damage = floatval($res[0][0]['damage'] ?? 0);
    
            $variance = round($qty - $openingstock - $purchase + $consumption + $damage, 2);
            $closingstock = round($openingstock + $purchase - $consumption - $damage, 2);
    
            // ✅ SAVE ITEM
            $auditItem = [
                'sku_id' => $skuId,
                'pos_audit_id' => $pos_audit_id,
                'store_id' => $storeId,
                'sku_name' => $skuName,
                'sku_unit' => $unitId,
                'sku_unit_name' => $unitName,
                'sku_price' => $sku_price,
                'openingstock' => $openingstock,
                'closingstock' => $closingstock,
                'physicalstock' => $qty,
                'variance' => $variance,
                'purchased' => $purchase,
                'consumption' => $consumption,
                'damage' => $damage,
                'auditfrom' => $auditfrom,
                'auditto' => $auditto,
                'remarks' => $remarks,
                'status' => 1,
                'type' => 'Outlet',
                'created' => $createdTime,
                'modified' => $createdTime
            ];
    
            $this->Audit_stock_items->create();
            $this->Audit_stock_items->save($auditItem);
    
            // ✅ INSERT Ledger entry (audit entry)
            $this->Stock_ledger->create();
            $this->Stock_ledger->save([
                'store_id' => $storeId,
                'sku_id' => $skuId,
                'sku_name' => $skuName,
                'sku_price' => $sku_price,
                'unit_id' => $unitId,
                'unit_name' => $unitName,
                'record_id' => $pos_audit_id,
                'record_type' => 'audit_stock',
                'quantity' => 0,
                'balance_stock' => $closingstock,
                'variance' => $variance,
                'actual_balance' => $qty,
                'variance_type' => 'audit',
                'variance_id' => $pos_audit_id,
                'type' => 'Outlet',
                'quantity_type' => 'audit',
                'created' => $auditto,
                'modified' => $createdTime
            ]);
        }
    
        echo json_encode(['status' => 'success']);
        exit;
        
    }
    
    public function view_audit()
	{
	    if (!$this->Session->check('Auth.Location.id')) {
            return $this->redirect(['controller' => 'dashboard', 'action' => 'login']);
        }
    
	    $this->layout = "users";
		$id=$_REQUEST['id'];
    
        $indents = $this->Audit_stock->query("SELECT * FROM pos_auditstock where pos_audit_id=$id");
        $this->set(compact('indents'));
        
		$items = $this->Audit_stock_items->query("SELECT * FROM pos_auditstock_items where pos_audit_id=$id and status=1");
        
        $this->set(compact('items'));
        
        
	}
	
	public function semi_auditstock()
    {
        if (!$this->Session->check('Auth.Location.id')) {
            return $this->redirect(['controller' => 'dashboard', 'action' => 'login']);
        }
    
        $this->layout = "users";
    
        // Default date range (last 30 days)
        $startDate = date('m/d/Y', strtotime('-30 days'));
        $endDate = date('m/d/Y');
        
        // If form submitted, get date range
        if($this->request->is('post', 'put'))
        {
			$daterange= $this->request->data['daterange'];
            $dates = explode(" - ", $daterange);
            $start_date = trim($dates[0]);
            $startDate = date('m/d/Y', strtotime($start_date));
            $end_date = trim($dates[1]);
            $endDate = date('m/d/Y', strtotime($end_date));
            $startdate= date('Y-m-d', strtotime($start_date));
            $enddate= date('Y-m-d', strtotime($end_date));
        }else{
            $startdate= date('Y-m-d', strtotime($startDate));
            $enddate= date('Y-m-d', strtotime($endDate));
        } 
    
        $sdate=$startdate.' 00:00:01';
        $edate=$enddate.' 23:59:59';
       
        $preparedStocks = $this->Audit_stock->find('all', [
            'conditions' => [
                'Audit_stock.status' => 1,
                'Audit_stock.updateon BETWEEN ? AND ?' => [$sdate, $edate],
                'Audit_stock.item_type' => 'Semifinished',
                'Audit_stock.type' => 'Outlet'
            ],
            'fields' => ['Audit_stock.*'], // Ensure ID is included
            'order' => ['Audit_stock.id' => 'DESC'],
            'recursive' => 1
        ]);

        // Pass to view
        $daterangeFormatted = $startDate . ' - ' . $endDate;
        $this->set(compact('daterangeFormatted', 'preparedStocks'));
    }

    public function semi_create_audit_stock()
    {
        if (!$this->Session->check('Auth.Location.id')) {
            return $this->redirect(['controller' => 'dashboard', 'action' => 'login']);
        }
    
        $this->layout = "users";
    
        $today = date("Y-m-d");
        $currentTime = date("H:i");
        $this->set(compact('today', 'currentTime'));
    
        // Load SKU list
        $skus = $this->Inventory_semifinished->query("SELECT * FROM inventory_semifinished WHERE status = 1 ORDER BY name ASC");
        $this->set(compact('skus'));
    
    }
    
    public function add_semi_auditstock()
    {
        $data = $this->request->data;
    
        $remarks = trim($data['remarks'] ?? '');
        $auditDate = $data['audit_date'] ?? $today;
        $auditTime = $data['audit_time'] ?? $currentTime;
        $createdTime = date('Y-m-d H:i:s');
    
        $authUser = $this->Session->read('Auth.Location');
        $storeId = $authUser['id'] ?? 0;
        $store_name = $authUser['name'] ?? '';
    
        // Generate unique 12 digit pos_audit_id
        do {
            $pos_audit_id = substr(preg_replace('/\D/', '', sha1(uniqid() . rand())), 0, 12);
            if ($pos_audit_id[0] == '0') {
                $pos_audit_id[0] = rand(1,9);
            }
            $existsMain = $this->Audit_stock->find('count', [
                'conditions' => ['Audit_stock.pos_audit_id' => $pos_audit_id],
                'recursive' => -1
            ]);
        } while ($existsMain > 0);
    
        $header = [
            'pos_audit_id' => $pos_audit_id,
            'store_id' => $storeId,
            'store_name' => $store_name,
            'remarks' => $remarks,
            'status' => 1,
            'item_type' => 'semifinished',
            'type' => 'Outlet',
            'select_all' => 0,
            'updateon' => $createdTime
        ];
    
        // ---------- NEW CHANGE HERE ----------
        $checkedData = json_decode($data['checkedData'], true); // <-- Read JSON
        // --------------------------------------
    
        $auditto = $auditDate . ' ' . $auditTime . ':00';
        $nowServer = date('Y-m-d H:i:s');
        if (strtotime($auditto) < strtotime($nowServer)) {
            $auditto = $nowServer;
        }
    
        foreach ($checkedData as $item) {
    
            $skuId = $item['id'];
            $qty = floatval($item['quantity']);
            $skuName = $item['sku_name'];
            $unitName = $item['unit_name'];
            $unitId = (int)$item['unit_id'];
            $sku_price = $item['price'];
    
            // Get last audit
            $lastAudit = $this->Audit_stock_items->find('first', [
                'conditions' => [
                    'sku_id' => $skuId,'item_type' => 'Semifinished','type' => 'Outlet','status' => 1,'store_id' => $storeId
                ],
                'order' => ['id DESC'],
                'recursive' => -1
            ]);
    
            $openingstock = 0;
            $auditfrom = '2022-01-01 00:00:01';
            if (!empty($lastAudit)) {
                $openingstock = floatval($lastAudit['Audit_stock_items']['physicalstock']);
                $auditfrom = $lastAudit['Audit_stock_items']['auditto'];
            }
    
            $ledgerQuery = "
                SELECT 
                    SUM(CASE WHEN quantity_type='credit' THEN quantity ELSE 0 END) AS purchase,
                    SUM(CASE WHEN record_type IN ('stockout','stockout_modifier','process_indent','stock_transfer_to_kitchen') THEN quantity ELSE 0 END) AS consumption,
                    (
                        SUM(CASE WHEN record_type IN ('damage','update_damage') AND quantity_type='debit' THEN quantity ELSE 0 END)
                        -
                        SUM(CASE WHEN record_type='reverse_damage' AND quantity_type='credit' THEN quantity ELSE 0 END)
                    ) AS damage
                FROM stock_ledger
                WHERE store_id='$storeId' AND item_type='Semifinished' AND sku_id=$skuId 
                AND type='Outlet' AND created BETWEEN '$auditfrom' AND '$auditto'
            ";
    
            $var = $this->Stock_ledger->query($ledgerQuery)[0][0];
            $purchase = floatval($var['purchase'] ?? 0);
            $consumption = floatval($var['consumption'] ?? 0);
            $damage = floatval($var['damage'] ?? 0);
    
            $variance = round($qty - $openingstock - $purchase + $consumption + $damage, 2);
            $closingstock = round($openingstock + $purchase - $consumption - $damage, 2);
    
            $this->Audit_stock_items->create();
            $this->Audit_stock_items->save([
                'sku_id' => $skuId,
                'pos_audit_id' => $pos_audit_id,
                'store_id' => $storeId,
                'sku_name' => $skuName,
                'item_type' => 'Semifinished',
                'type' => 'Outlet',
                'sku_unit' => $unitId,
                'sku_unit_name' => $unitName,
                'sku_price' => $sku_price,
                'openingstock' => $openingstock,
                'closingstock' => $closingstock,
                'physicalstock' => $qty,
                'variance' => $variance,
                'purchased' => $purchase,
                'consumption' => $consumption,
                'damage' => $damage,
                'auditfrom' => $auditfrom,
                'auditto' => $auditto,
                'remarks' => $remarks,
                'status' => 1,
                'created' => $createdTime,
                'modified' => $createdTime
            ]);
    
            // Insert audit ledger
            $this->Stock_ledger->create();
            $this->Stock_ledger->save([
                'store_id' => $storeId,
                'sku_id' => $skuId,
                'sku_name' => $skuName,
                'sku_price' => $sku_price,
                'unit_id' => $unitId,
                'unit_name' => $unitName,
                'record_id' => $pos_audit_id,
                'record_type' => 'audit_stock',
                'quantity' => 0,
                'balance_stock' => 0,
                'variance' => $variance,
                'actual_balance' => $qty,
                'variance_type' => 'audit',
                'variance_id' => $pos_audit_id,
                'item_type' => 'Semifinished',
                'type' => 'Outlet',
                'quantity_type' => 'audit',
                'created' => $auditto,
                'modified' => $createdTime
            ]);
        }
    
        // Save header at last
        $this->Audit_stock->create();
        if ($this->Audit_stock->save($header)) {
            echo "success";
            exit;
        } else {
            echo "failed";
            exit;
        }

    }
    
    public function semi_view_audit()
	{
	    if (!$this->Session->check('Auth.Location.id')) {
            return $this->redirect(['controller' => 'dashboard', 'action' => 'login']);
        }
    
	    $this->layout = "users";
		$id=$_REQUEST['id'];
    
        $indents = $this->Audit_stock->query("SELECT * FROM pos_auditstock where pos_audit_id=$id");
        $this->set(compact('indents'));
        
		$items = $this->Audit_stock_items->query("SELECT * FROM pos_auditstock_items where pos_audit_id=$id and status=1");
        
        $this->set(compact('items'));
        
        
	}
	
    //purchase cash
	public function direct_purchase()
    {
        if (!$this->Session->check('Auth.User.id')) {
            return $this->redirect(['controller' => 'dashboard', 'action' => 'login']);
        }

        $this->layout = "users";

        // --- Default date range: last 1 month
        $startDate = date('m/d/Y', strtotime('-1 month'));
        $endDate   = date('m/d/Y');
        $startdate = date('Y-m-d', strtotime('-1 month'));
        $enddate   = date('Y-m-d');

        // --- Handle date range from form
        if ($this->request->is(['post', 'put'])) {
            $daterange = $this->request->data['daterange'] ?? '';
            if (strpos($daterange, '-') !== false) {
                list($startRaw, $endRaw) = explode(" - ", $daterange);
                $startDate = trim($startRaw);
                $endDate   = trim($endRaw);
                $startdate = date('Y-m-d', strtotime($startDate));
                $enddate   = date('Y-m-d', strtotime($endDate));
            }
        }

        $authUser = $this->Session->read('Auth.User');
        $location_id = $authUser['id']; // Prefer using location_id, not user id
        
        // --- Build query conditions for internal indent
        $indentConditions = [
            'direct_purchase' => 1,
            'location_id' => $location_id,
            'status' => 4,
            "created >=" => $startdate . " 00:00:01",
            "created <=" => $enddate . " 23:59:59",
        ];

        // --- Fetch internal indent records
        $users = $this->Inventory_purchase_order->find('all', [
            'conditions' => $indentConditions,
            'order' => ['id' => 'DESC']
        ]);

        // --- Pass variables to view
        $this->set(compact('users'));
        $this->set('startdate', $startDate . ' - ' . $endDate);
    }

    
    public function create_direct_purchase()
    {
        // 🔐 Authentication check
        if (!$this->Session->check('Auth.User.id') || $this->Session->read('Auth.User.role') == 'admin') {
            return $this->redirect(['controller' => 'dashboard', 'action' => 'login']);
        }
    
        $this->layout = "users";
        $authUser = $this->Session->read('Auth.User');
    
        // ✅ Use location_id (or assign correctly)
        $location_id = $authUser['id']; // Prefer using location_id, not user id
    
        // ✅ Get vendors for this area
        $vendors = $this->Inventory_vendor->query("
            SELECT id, company 
            FROM inventory_vendors 
            WHERE status = 1 
              AND id IN (
                  SELECT vendor_id 
                  FROM inventory_vendor_service_areas 
                  WHERE area_id = $location_id
              )
            ORDER BY name ASC
        ");
    
        // ✅ Convert raw query result into CakePHP list (id => name)
        $vendoroptions = [];
        foreach ($vendors as $v) {
            $vendoroptions[$v['inventory_vendors']['id']] = $v['inventory_vendors']['company'];
        }
    
        // ✅ Fetch SKUs
        // $users = $this->Inventory_sku->find('all', [
        //     'conditions' => ['Inventory_sku.status' => 1],
        //     'order' => ['Inventory_sku.name' => 'DESC'],
        //     'recursive' => -1
        // ]);

        
        $users = $this->Inventory_sku->find('all', [
            'fields' => [
                'Inventory_sku.*',
                'StockLedger.actual_balance'
            ],
            'joins' => [
                [
                    'table' => 'stock_ledger',
                    'alias' => 'StockLedger',
                    'type' => 'LEFT',
                    'conditions' => [
                        'StockLedger.sku_id = Inventory_sku.id',
                        'StockLedger.store_id' => $location_id,
                        'StockLedger.type' => 'OUTLET',
                        'StockLedger.id = (
                            SELECT MAX(sl2.id)
                            FROM stock_ledger sl2
                            WHERE sl2.sku_id = Inventory_sku.id
                            AND sl2.store_id = ' . $location_id . '
                            AND sl2.type = "OUTLET"
                        )'
                    ]
                ]
            ],
            'conditions' => ['Inventory_sku.status' => 1],
            'order' => ['Inventory_sku.name ASC']
        ]);
    
        // ✅ Pass data to view
        $this->set(compact('vendoroptions', 'users'));
    }

    public function add_direct_purchase() 
    {
        $this->autoRender = false;
    
        // ✅ Check session
        if (!$this->Session->check('Auth.User.id')) {
            echo json_encode(['response_code' => 0, 'data' => 'User session expired.']);
            return;
        }
    
        $id = $this->Session->read('Auth.User.id');
        $name = $this->Session->read('Auth.User.name');
        $today = date('Y-m-d H:i:s');
    
        if (isset($_POST['checkedData']) && isset($_POST['vendor_id'])) {
            $checkeddatas = json_decode($_POST['checkedData'], true);
            $vendorId = $_POST['vendor_id'];
    
            // ✅ Fetch vendor info
            $vendor = $this->Inventory_vendor->find('first', [
                'conditions' => ['Inventory_vendor.id' => $vendorId],
                'fields' => ['id', 'company'],
                'recursive' => -1
            ]);
    
            if (empty($vendor)) {
                echo json_encode(['response_code' => 0, 'data' => 'Invalid vendor selected.']);
                return;
            }
    
            // ✅ Generate PO ID dynamically for the year (e.g. 2025000001)
            $year = date('Y');
            $lastPo = $this->Inventory_purchase_order->find('first', [
                'conditions' => ['p_o_id LIKE' => $year . '%'],
                'fields' => ['p_o_id'],
                'order' => ['p_o_id DESC'],
                'recursive' => -1
            ]);
    
            if (!empty($lastPo)) {
                $lastNumber = (int)substr($lastPo['Inventory_purchase_order']['p_o_id'], 4);
                $newNumber = str_pad($lastNumber + 1, 6, '0', STR_PAD_LEFT);
            } else {
                $newNumber = '000001';
            }
    
            $poId = $year . $newNumber;
    
            $amount = 0;
            $itemsSaved = true;
    
            // ✅ Loop through items
            foreach ($checkeddatas as $item) {
                $skuid = $item['id'];
                $price = $item['price'];
                $quantity = $item['sku_quantity'];
    
                // ✅ Calculate total tax %
                $product_taxes = $this->Inventory_sku_tax->query(
                    "SELECT SUM(tax_percentage) AS total_tax FROM inventory_sku_tax WHERE product_id = '$skuid' and status=1"
                );
                $total_tax = round($product_taxes[0][0]['total_tax'], 2);
                $tax_value = round(($price * $quantity) * ($total_tax / 100), 2);
                $skuamount = round(($price * $quantity) + $tax_value, 2);
    
                // ✅ Generate unique stockin_id
                do {
                    $txnid = rand(1, 9);
                    for ($i = 0; $i < 10; $i++) {
                        $txnid .= rand(0, 9);
                    }
    
                    $existing = $this->Inventory_purchase_order->find('first', [
                        'conditions' => ['stockin_id' => $txnid],
                        'recursive' => -1
                    ]);
                } while ($existing);
    
                // ✅ Prepare item data
                $itemData = [
                    'Inventory_purchase_order_item' => [
                        'p_o_id' => $poId,
                        'internal_indent_id' => 0,
                        'sku_id' => $skuid,
                        'sku_name' => $item['sku_name'],
                        'sku_unit' => $item['unit_id'],
                        'sku_unit_name' => $item['unit_name'],
                        'sku_price' => $price,
                        'sku_quantity' => $quantity,
                        'sku_total_tax_percentage' => $total_tax,
                        'sku_total_tax_value' => $tax_value,
                        'sku_amount' => $skuamount,
                        'approved_quantity' => $quantity,
                        'approved_total_tax_value' => $tax_value,
                        'approved_amount' => $skuamount,
                        'processed_quantity' => $quantity,
                        'processed_total_tax_value' => $tax_value,
                        'processed_amount' => $skuamount,
                        'stockin_quantity' => $quantity,
                        'stockin_total_tax_value' => $tax_value,
                        'stockin_amount' => $skuamount,
                        'status' => 4
                    ]
                ];
    
                if (!$this->Inventory_purchase_order_item->saveAll($itemData)) {
                    $itemsSaved = false;
                    break;
                }else{
                    
                    $ledgerprevdetails = $this->Stock_ledger->find('first', [
                        'conditions' => ['sku_id' => $skuid,'type'=>'OUTLET','item_type'=>'sku','store_id'=>$id],
                        'fields' => ['actual_balance'],
                        'order' => ['id DESC'],
                        'recursive' => -1
                    ]);
                    
                    $ledger_prev_quantity = !empty($ledgerprevdetails['Stock_ledger']['actual_balance']) 
                        ? $ledgerprevdetails['Stock_ledger']['actual_balance'] 
                        : 0;
                    
                    $newQuantity = $quantity + $ledger_prev_quantity;
                    
                    $ledgerData['Stock_ledger'] = [
                            'store_id'        => $id,
                            'sku_id'          => $skuid,
                            'sku_name'        => $item['sku_name'],
                            'sku_price'       => $price,
                            'unit_id'         => $item['unit_id'],
                            'unit_name'       => $item['unit_name'],
                            'record_id'       => $txnid,
                            'record_type'     => 'stockin',
                            'quantity'        => $quantity,
                            'balance_stock'   => $newQuantity,
                            'actual_balance'  => $newQuantity,
                            'quantity_type'   => 'credit',
                            'type'            => 'OUTLET',
                            'item_type'       => 'sku',
                            'created'         => date('Y-m-d H:i:s')
                    ];
                    
                    if(!$this->Stock_ledger->saveAll($ledgerData['Stock_ledger'])){
    				    echo json_encode(['response_code' => 0, 'data' => 'Failed to save ledger record.']);
    				}
    			}
    
                $amount += $skuamount;
            }
    
            // ✅ Save PO master record
            if ($itemsSaved) {
                $indentData = [
                    'p_o_id' => $poId,
                    'vendor_id' => $vendorId,
                    'vendor_name' => $vendor['Inventory_vendor']['company'],
                    'internal_indent_id' => 0,
                    'location_id' => $id,
                    'location_name' => $name,
                    'total_amount' => $amount,
                    'status' => 4,
                    'approved_by_id' => $id,
                    'approved_by_name' => $name,
                    'approved_on' => $today,
                    'approved_amount' => $amount,
                    'processed_by_id' => $id,
                    'processed_by_name' => $name,
                    'processed_on' => $today,
                    'processed_amount' => $amount,
                    'stockin_id' => $txnid,
                    'stockin_on' => $today,
                    'stockin_by_id' => $id,
                    'stockin_by_name' => $name,
                    'stockin_amount' => $amount,
                    'direct_purchase' => 1,
                    'invoice_no' => '0',
                    'po_date' => date('Y-m-d')
                ];
    
                $this->Inventory_purchase_order->create();
                if ($this->Inventory_purchase_order->save($indentData)) {
                    echo json_encode(['response_code' => 1, 'data' => 'Saved successfully.']);
                } else {
                    echo json_encode(['response_code' => 0, 'data' => 'Failed to save master record.']);
                }
            } else {
                echo json_encode(['response_code' => 0, 'data' => 'Failed to save one or more items.']);
            }
    
        } else {
            echo json_encode(['response_code' => 0, 'data' => 'Invalid request. Missing data.']);
        }
    }

    public function view_direct_purchase()
	{
	    if(!$this->Session->check('Auth.User.id')){
			$this->redirect(array('controller'=>'dashboard','action' => 'login'));		
		}
        
	    $this->layout = "users";
		$id=$_REQUEST['id'];
    
        $indents = $this->Inventory_purchase_order->query("SELECT * FROM inventory_purchase_order where p_o_id=$id");
        $this->set(compact('indents'));
        
		$items = $this->Inventory_purchase_order_item->query("SELECT * FROM inventory_purchase_order_items where p_o_id=$id and status!=0");
        
        $this->set(compact('items'));
    }
    
    public function semi_auditexport()
    {
        if (!$this->Session->check('Auth.Location.id')) {
            return $this->redirect(['controller' => 'dashboard', 'action' => 'login']);
        }
    
        $id = $_REQUEST['id'] ?? 0;
        if (empty($id)) {
            throw new BadRequestException('Invalid Audit ID.');
        }
    
        // 🔹 Fetch audit stock main info
        $indents = $this->Audit_stock->query("SELECT * FROM pos_auditstock WHERE pos_audit_id = $id");
    
        if (empty($indents)) {
            $this->Session->setFlash('No audit found for this record.', 'flash_error');
            return $this->redirect(['action' => 'semi_view_audit']);
        }
    
        $audit = $indents[0]['pos_auditstock'];
    
        // 🔹 Fetch audit stock items
        $items = $this->Audit_stock_items->query("
            SELECT * 
            FROM pos_auditstock_items 
            WHERE pos_audit_id = $id 
              AND status = 1 order by sku_name ASC
        ");
    
        if (empty($items)) {
            $this->Session->setFlash('No audit items found for this record.', 'flash_error');
            return $this->redirect(['action' => 'semi_view_audit', $id]);
        }
    
        // 🔹 Load PHPExcel
        App::import('Vendor', 'PHPExcel/PHPExcel');
        error_reporting(0);
        ini_set('display_errors', TRUE);
        ini_set('display_startup_errors', TRUE);
    
        $objPHPExcel = new PHPExcel();
        $objPHPExcel->getProperties()
            ->setCreator("Aether")
            ->setLastModifiedBy("Aether")
            ->setTitle("Audit Stock Report")
            ->setSubject("Audit Stock Report")
            ->setDescription("Exported semi-finished audit data.")
            ->setKeywords("audit stock excel")
            ->setCategory("Export File");
    
        $sheet = $objPHPExcel->setActiveSheetIndex(0);
    
        // 🔹 Compact header info (no big text)
        $sheet->setCellValue('A1', 'Audit ID:')
              ->setCellValueExplicit('B1', (string)$audit['pos_audit_id'], PHPExcel_Cell_DataType::TYPE_STRING)
              ->setCellValue('C1', 'Store Name:')
              ->setCellValue('D1', $audit['store_name']);
    
        $sheet->setCellValue('A2', 'Remarks:')
              ->setCellValue('B2', $audit['remarks'])
              ->setCellValue('C2', 'Audit Date:')
              ->setCellValue('D2', $audit['updateon']);
    
        // Apply light formatting
        $sheet->getStyle('A1:D2')->getFont()->setBold(true)->setSize(10);
        $sheet->getStyle('A1:D2')->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
    
        // 🔹 Column headers (starting at row 4)
        $headers = [
            'Sr No.', 'SKU Name', 'Unit', 'Price',
             'Physical Stock','Opening Stock',
             'Purchase','Consumption','Damage',
            'Closing Stock', 'Variance', 'Amount', 'Percentage',
            'Audit From', 'Audit To'
        ];
    
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . '4', $header);
            $sheet->getStyle($col . '4')->getFont()->setBold(true);
            $sheet->getStyle($col . '4')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
            $col++;
        }
    
        // 🔹 Fill data
        $row = 5;
        $count = 1;
        foreach ($items as $itm) {
            $data = $itm['pos_auditstock_items'];
            $percentage = 0;
            
            if ((float)$data['openingstock'] > 0) {
                $percentage = round(((float)$data['variance'] / (float)$data['openingstock']) * 100, 2);
            }
            
            $sheet->setCellValue("A$row", $count)
                  ->setCellValue("B$row", $data['sku_name'])
                  ->setCellValue("C$row", $data['sku_unit_name'])
                  ->setCellValue("D$row", $data['sku_price'])
                  ->setCellValue("E$row", (float)$data['physicalstock'])
                  ->setCellValue("F$row", (float)$data['openingstock'])
                  ->setCellValue("G$row", $data['purchased'])
                  ->setCellValue("H$row", $data['consumption'])
                  ->setCellValue("I$row", $data['damage'])
                  ->setCellValue("J$row", $data['closingstock'])
                  ->setCellValue("K$row", (float)$data['variance'])
                  ->setCellValue("L$row", (float)$data['variance'] * $data['sku_price'])
                  ->setCellValue("M$row", $percentage . '%')
                  ->setCellValue("N$row", $data['auditfrom'])
                  ->setCellValue("O$row", $data['auditto']);
                  
            $row++;
            $count++;
        }
    
        // 🔹 Auto size all columns
        foreach (range('A', 'H') as $columnID) {
            $sheet->getColumnDimension($columnID)->setAutoSize(true);
        }
    
        // 🔹 Sheet name
        $sheet->setTitle('Audit Report');
        $objPHPExcel->setActiveSheetIndex(0);
    
        // 🔹 Output headers for download
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="Semi_Finished_Audit_Report_' . $id . '.xls"');
        header('Cache-Control: max-age=0');
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
        header('Cache-Control: cache, must-revalidate');
        header('Pragma: public');
    
        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $objWriter->save('php://output');
        exit;
    }

	
	public function imprest()
    {
        if (!$this->Session->check('Auth.User.id')) {
            return $this->redirect(['controller' => 'dashboard', 'action' => 'login']);
        }

        $this->layout = "users";

        // --- Default date range: last 1 month
        $startDate = date('m/d/Y', strtotime('-1 month'));
        $endDate   = date('m/d/Y');
        $startdate = date('Y-m-d', strtotime('-1 month'));
        $enddate   = date('Y-m-d');

        // --- Handle date range from form
        if ($this->request->is(['post', 'put'])) {
            $daterange = $this->request->data['daterange'] ?? '';
            if (strpos($daterange, '-') !== false) {
                list($startRaw, $endRaw) = explode(" - ", $daterange);
                $startDate = trim($startRaw);
                $endDate   = trim($endRaw);
                $startdate = date('Y-m-d', strtotime($startDate));
                $enddate   = date('Y-m-d', strtotime($endDate));
            }
        }

        // --- Session values
        $authUser = $this->Session->read('Auth.User');
        $location_id = $authUser['id']; // Prefer using location_id, not user id
        
        // --- Build query conditions for internal indent
        $indentConditions = [
            'location_id' => $location_id,
            "created >=" => $startdate . " 00:00:01",
            "created <=" => $enddate . " 23:59:59",
        ];

        // --- Fetch internal indent records
        $users = $this->Account_imprest->find('all', [
            'conditions' => $indentConditions,
            'order' => ['id' => 'DESC']
        ]);

        // --- Pass variables to view
        $this->set(compact('users'));
        $this->set('startdate', $startDate . ' - ' . $endDate);
    }

	public function add_imprest() 
    {
        $this->autoRender = false;
    
        if (!$this->Session->check('Auth.User.id')) {
            echo json_encode(['response_code' => 0, 'data' => 'User session expired.']);
            return;
        }
    
        $id = $this->Session->read('Auth.User.id');
        $name = $this->Session->read('Auth.User.name');
        
        if (isset($_POST['amount'])) {
            $amount = $_POST['amount'];
            $description=$_POST['description'];
            
            // Validate location
            $location = $this->Location->find('first', [
                'conditions' => ['Location.id' => $id],
                'fields' => ['id', 'name'],
                'recursive' => -1
            ]);
    
            if (empty($location)) {
                echo json_encode(['response_code' => 0, 'data' => 'Invalid location selected.']);
                return;
            }
    
            $skuData = [
                'location_id' => $id,
                'location_name' => $name,
                'amount' => $amount,
                'description' => $description
            ];

            $this->Account_imprest->create(); // ensure new row
            if ($this->Account_imprest->save($skuData)) {
                echo json_encode(['response_code' => 1, 'data' => 'saved']);
            } else {
                echo json_encode(['response_code' => 0, 'data' => 'Failed to save data.']);
            }
        } else {
            echo json_encode(['response_code' => 0, 'data' => 'Invalid request. Missing data.']);
        }
    }
    
    public function get_imprest()
    {
        $this->autoRender = false;
        $id = $this->request->query('id');
        $data = $this->Account_imprest->findById($id);
        echo json_encode(['status' => !empty($data), 'data' => $data['Account_imprest'] ?? []]);
    }
    
    public function update_imprest()
    {
        $this->autoRender = false;
    
        // Get raw POST data
        $data = $this->request->data;
    
        // Wrap in model key if not already
        if (!isset($data['Account_imprest'])) {
            $data = ['Account_imprest' => $data];
        }
    
        // Add sync flags
        $data['Account_imprest']['sync'] = 0;
        $data['Account_imprest']['local_sync'] = 0;
    
        // Set ID for update
        $this->Account_imprest->id = $data['Account_imprest']['id'];
    
        // Save record
        $saved = $this->Account_imprest->save($data);
    
        echo json_encode(['status' => (bool)$saved]);
    }

    
    public function view_imprest()
    {
        $this->autoRender = false;
        $id = $this->request->query('id');
        $data = $this->Account_imprest->findById($id);
        echo json_encode(['status' => !empty($data), 'data' => $data['Account_imprest'] ?? []]);
    }
    
    public function imprest_change_status() 
    {
        $this->autoRender = false; 
    
        if ($this->request->is('post')) {
            $indentId = $this->request->data['_id']; 
            $currentStatus = $this->Account_imprest->field('status', ['id' => $indentId]);
            $newStatus = $currentStatus == 1 ? 0 : 1;
    
            $updated = $this->Account_imprest->updateAll(
                [
                    'Account_imprest.status' => $newStatus,
                    'Account_imprest.sync' => 0,
                    'Account_imprest.local_sync' => 0
                ],
                ['Account_imprest.id' => $indentId]
            );
    
            if ($updated) {
                echo json_encode(['status' => true]);
            } else {
                echo json_encode(['status' => false]);
            }
        }
    }
    
    public function audit_export()
    {
        if (!$this->Session->check('Auth.User.id')) {
            return $this->redirect(['controller' => 'dashboard', 'action' => 'login']);
        }
    
        $id = $_REQUEST['id'] ?? 0;
        if (empty($id)) {
            throw new BadRequestException('Invalid Audit ID.');
        }
    
        // 🔹 Fetch main audit record
        $indents = $this->Audit_stock->query("SELECT * FROM pos_auditstock WHERE pos_audit_id = $id");
    
        if (empty($indents)) {
            $this->Session->setFlash('No audit found for this record.', 'flash_error');
            return $this->redirect(['action' => 'view_audit']);
        }
    
        $audit = $indents[0]['pos_auditstock'];
    
        // 🔹 Fetch audit items
        $items = $this->Audit_stock_items->query("
            SELECT * 
            FROM pos_auditstock_items 
            WHERE pos_audit_id = $id 
              AND status = 1
            ORDER BY sku_name ASC
        ");
    
        if (empty($items)) {
            $this->Session->setFlash('No audit items found for this record.', 'flash_error');
            return $this->redirect(['action' => 'view_audit', $id]);
        }
    
        // 🔹 Load PHPExcel
        App::import('Vendor', 'PHPExcel/PHPExcel');
        error_reporting(0);
        ini_set('display_errors', TRUE);
        ini_set('display_startup_errors', TRUE);
    
        $objPHPExcel = new PHPExcel();
        $objPHPExcel->getProperties()
            ->setCreator("QuickMart Audit System")
            ->setLastModifiedBy("QuickMart")
            ->setTitle("Audit Stock Report")
            ->setSubject("Audit Stock Report")
            ->setDescription("Exported audit stock data.")
            ->setKeywords("audit stock excel")
            ->setCategory("Export File");
    
        $sheet = $objPHPExcel->setActiveSheetIndex(0);
    
        // 🔹 Header Info (Compact Layout)
        $sheet->setCellValue('A1', 'Audit ID:')
              ->setCellValueExplicit('B1', (string)$audit['pos_audit_id'], PHPExcel_Cell_DataType::TYPE_STRING)
              ->setCellValue('C1', 'Store Name:')
              ->setCellValue('D1', $audit['store_name']);
    
        $sheet->setCellValue('A2', 'Remarks:')
              ->setCellValue('B2', $audit['remarks'])
              ->setCellValue('C2', 'Audit Date:')
              ->setCellValue('D2', $audit['updateon']);
    
        // Format header info
        $sheet->getStyle('A1:D2')->getFont()->setBold(true)->setSize(10);
        $sheet->getStyle('A1:D2')->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
    
        // 🔹 Table Headers (Row 4)
        $headers = [
            'Sr No.', 'SKU Name', 'Unit', 'Price',
             'Physical Stock','Opening Stock',
             'Purchase','Consumption','Damage',
            'Closing Stock','Variance', 'Amount', 'Percentage',
            'Audit From', 'Audit To'
        ];
    
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . '4', $header);
            $sheet->getStyle($col . '4')->getFont()->setBold(true);
            $sheet->getStyle($col . '4')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
            $col++;
        }
    
        // 🔹 Fill Data Rows
        $row = 5;
        $count = 1;
        foreach ($items as $itm) {
            $data = $itm['pos_auditstock_items'];
            $opening = (float)$data['openingstock'];
            $variance = (float)$data['variance'];
            $price = (float)$data['sku_price'];
            $percentage = ($opening > 0) ? round(($variance / $opening) * 100, 2) : 0;
            
            $sheet->setCellValue("A$row", $count)
                  ->setCellValue("B$row", $data['sku_name'])
                  ->setCellValue("C$row", $data['sku_unit_name'])
                  ->setCellValue("D$row", $data['sku_price'])
                  ->setCellValue("E$row", (float)$data['physicalstock'])
                  ->setCellValue("F$row", $opening)
                  ->setCellValue("G$row", $data['purchased'])
                  ->setCellValue("H$row", $data['consumption'])
                  ->setCellValue("I$row", $data['damage'])
                  ->setCellValue("J$row", $data['closingstock'])
                  ->setCellValue("K$row", $variance)
                  ->setCellValue("L$row", $price * $variance)
                  ->setCellValue("M$row", $percentage . '%')
                  ->setCellValue("N$row", $data['auditfrom'])
                  ->setCellValue("O$row", $data['auditto']);
    
            $row++;
            $count++;
        }
    
        // 🔹 Auto-size columns
        foreach (range('A', 'J') as $columnID) {
            $sheet->getColumnDimension($columnID)->setAutoSize(true);
        }
    
        // 🔹 Sheet name and download setup
        $sheet->setTitle('Audit Report');
        $objPHPExcel->setActiveSheetIndex(0);
    
        // 🔹 Output headers
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="Audit_Report_' . $id . '.xls"');
        header('Cache-Control: max-age=0');
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
        header('Cache-Control: cache, must-revalidate');
        header('Pragma: public');
    
        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $objWriter->save('php://output');
        exit;
    }
    
    public function sku_stock_level() {
        if (!$this->Session->check('Auth.User.id')) {
            return $this->redirect(['controller' => 'dashboard', 'action' => 'login']);
        }
    
        $this->layout = "users";
    
        $today = date("Y-m-d");
        $currentTime = date("H:i", strtotime("+1 minutes"));

        $sel_date = $today;      // default
        $sel_time = $currentTime; // default

        $skuledger_startdate = date('Y-m-d', strtotime('-10 days'));
        $stocklevel_enddate   = $skuledger_enddate   = date('Y-m-d');
        $sku_id = null;
        
        $stocklevel_startdate = $sel_date.' '.$sel_time.':00';
        
        if ($this->request->is(['post', 'put'])) {
            if (isset($this->request->data['submit_stocklevel'])) {
                
                $sel_date=$this->request->data['sel_date'];
                $sel_time=$this->request->data['sel_time'];
            
                $stocklevel_startdate = $sel_date.' '.$sel_time.':00';
            }
    
            if (isset($this->request->data['submit_skutransactions'])) {
                $daterange = $this->request->data['daterange'] ?? '';
                $dates = explode(" - ", $daterange);
                if (count($dates) === 2) {
                    $skuledger_startdate = date('Y-m-d', strtotime(trim($dates[0])));
                    $skuledger_enddate = date('Y-m-d', strtotime(trim($dates[1])));
                }
                $sku_id = $this->request->data['SkuLedger']['sku'] ?? null;
            }
            
        }
        
        $this->set(compact('sel_date', 'sel_time'));

        $id = $this->Session->read('Auth.Location.id');
        
        $conditions = [
            'status' => 1,
            'type'   => 'OUTLET',
            'id' =>$id
        ];
        
    
        if (!empty($this->request->data['StockLevel']['cashier'])) {
            $store_id = $this->request->data['StockLevel']['cashier'];
            $transactions = $this->Stock_ledger->query("
                SELECT sl1.sku_id,sl1.sku_price,sl1.sku_name,s.stock,sl1.unit_name, sl1.actual_balance
                FROM stock_ledger sl1
                INNER JOIN (
                    SELECT sku_id, MAX(id) AS max_id
                    FROM stock_ledger
                    WHERE type = 'OUTLET'  AND item_type = 'SKU' 
                      AND store_id IN ($store_id)
                      AND created < '$stocklevel_startdate'
                    GROUP BY sku_id
                ) latest ON sl1.id = latest.max_id
                LEFT  JOIN inventory_skus s ON s.id = sl1.sku_id
                WHERE sl1.type = 'OUTLET' AND sl1.item_type = 'SKU' order by s.name asc
            ");
        } 
        $skus = $this->Inventory_sku->query("SELECT * FROM inventory_skus WHERE status=1 order by name asc");
        
        
        $skutransactions = [];
        
        if (!empty($sku_id)) {
            if($id){
                
                $_shiftRange = $this->getShiftDateRange($skuledger_startdate, $enddate, isset($store_id) ? $store_id : (isset($location_id) ? $location_id : null)); $startDatetime = $_shiftRange['startDatetime']; $endDatetime = $_shiftRange['endDatetime'];
                
                // $skutransactions = $this->Stock_ledger->query("
                    // SELECT * FROM stock_ledger 
                    // WHERE created BETWEEN '$startDatetime' AND '$endDatetime'
                    // AND type='OUTLET' AND sku_id = $sku_id and store_id IN ($id) and item_type = 'SKU'
                    // ORDER BY id DESC
                // ");
				
				$skutransactions = $this->Stock_ledger->query("SELECT sl.*, ipo.vendor_name, ii.warehouse_name
						FROM stock_ledger sl 
						LEFT JOIN inventory_purchase_order ipo
							ON ipo.stockin_id = sl.record_id
						LEFT JOIN inventory_indent ii ON ii.stockin_id = sl.record_id
						WHERE sl.created BETWEEN '$startDatetime' AND '$endDatetime'
						AND sl.type = 'OUTLET'
						AND sl.sku_id = $sku_id
						AND sl.store_id IN ($id)
						AND sl.item_type = 'SKU'
						ORDER BY sl.id DESC");
                
            }
        }
        
        $this->set('stocklevel_daterange', date('m/d/Y', strtotime($stocklevel_startdate)) . ' - ' . date('m/d/Y', strtotime($stocklevel_enddate)));
        $this->set('skuledger_daterange', date('m/d/Y', strtotime($skuledger_startdate)) . ' - ' . date('m/d/Y', strtotime($skuledger_enddate)));

            
        $users=$this->Location->find('all',array('conditions'=>$conditions));
                
	    $this->set(compact('users'));
    
        $this->set(compact('transactions', 'skus', 'skutransactions'));
    }
    
    
    public function sku_stock_levelexport()
    {
        $date_range = $_GET['sdate'] ?? '';
    
        if (!$date_range) {
            throw new BadRequestException('Invalid date range.');
        }
    
        $store_id = $this->Session->read('Auth.Location.id');
        
        // Fetch store name
        $store_data = $this->Location->find('first', [
            'conditions' => ['Location.id' => $store_id],
            'fields' => ['Location.name']
        ]);
        $store_name = $store_data['Location']['name'] ?? 'Unknown Store';
    
        // ===============================
        // Fetch Stock Ledger Data (Same as View)
        // ===============================
        $transactions = $this->Stock_ledger->query("
            SELECT sl1.sku_id, sl1.sku_price, sl1.sku_name, sl1.unit_name,
                   s.stock, sl1.actual_balance
            FROM stock_ledger sl1
            INNER JOIN (
                SELECT sku_id, MAX(id) AS max_id
                FROM stock_ledger
                WHERE type = 'Outlet' 
                  AND item_type = 'SKU' 
                  AND store_id = $store_id
                  AND created < '$date_range'
                GROUP BY sku_id
            ) latest ON sl1.id = latest.max_id
            LEFT JOIN inventory_skus s ON s.id = sl1.sku_id
            WHERE sl1.type = 'Outlet' AND sl1.item_type = 'SKU'
            ORDER BY s.name ASC
        ");
        
        // ===============================
        // Prepare Excel Export
        // ===============================
        App::import('Vendor', 'PHPExcel/PHPExcel');
        error_reporting(0);
        ini_set('display_errors', TRUE);
        ini_set('display_startup_errors', TRUE);
    
        if (PHP_SAPI == 'cli') {
            die('This example should only be run from a Web Browser');
        }
    
        $objPHPExcel = new PHPExcel();
        $objPHPExcel->getProperties()
            ->setCreator("Aether")
            ->setLastModifiedBy("Aether")
            ->setTitle("DC Stock Level Report")
            ->setSubject("Stock Report")
            ->setDescription("Exported OUTLET stock level data.")
            ->setKeywords("stock excel")
            ->setCategory("Export File");
    
        $sheet = $objPHPExcel->setActiveSheetIndex(0);
    
        // ===============================
        // Header Section
        // ===============================
        $sheet->setCellValue('A1', "SKU STOCK LEVEL REPORT");
        $sheet->mergeCells('A1:G1');
        $sheet->getStyle('A1:G1')->getFont()->setBold(true)->setSize(14);
    
        $sheet->setCellValue('A2', "Store: $store_name");
        $sheet->mergeCells('A2:G2');
        $sheet->getStyle('A2:G2')->getFont()->setBold(true)->setSize(12);
    
        $sheet->setCellValue('A3', "Date $date_range");
        $sheet->mergeCells('A3:G3');
        $sheet->getStyle('A3:G3')->getFont()->setBold(true)->setSize(11);
    
        // ===============================
        // Column Headers
        // ===============================
        $headers = ['Sr No.', 'SKU Name', 'Unit Name', 'Price', 'Required Qty', 'Available Qty', 'Value'];
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . '5', $header);
            $sheet->getStyle($col . '5')->getFont()->setBold(true);
            $sheet->getColumnDimension($col)->setAutoSize(true);
            $col++;
        }
    
        // ===============================
        // Fill Data Rows
        // ===============================
        $row = 6;
        $count = 1;
        foreach ($transactions as $txn) {
            $data  = $txn['sl1'];
            $stock = $txn['s']['stock'] ?? 0;
            $value = round($data['sku_price'] * $data['actual_balance'], 2);
    
            $sheet->setCellValue("A$row", $count);
            $sheet->setCellValue("B$row", $data['sku_name']);
            $sheet->setCellValue("C$row", $data['unit_name']);
            $sheet->setCellValue("D$row", (float)$data['sku_price']);
            $sheet->setCellValue("E$row", (float)$stock);
            $sheet->setCellValue("F$row", (float)$data['actual_balance']);
            $sheet->setCellValue("G$row", $value);
    
            $row++;
            $count++;
        }
    
        // ===============================
        // Style and Output
        // ===============================
        $sheet->getStyle("A5:G5")->getFont()->setBold(true);
        $sheet->setTitle('SKU Stock Level');
        $objPHPExcel->setActiveSheetIndex(0);
    
        // Output headers
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="SKU_Stock_Level_Report.xls"');
        header('Cache-Control: max-age=0');
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
        header('Cache-Control: cache, must-revalidate');
        header('Pragma: public');
    
        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $objWriter->save('php://output');
        exit;
    }
    
    public function po_grn_history()
    {
        if (!$this->Session->check('Auth.Location.id')) {
            return $this->redirect(['controller' => 'dashboard', 'action' => 'login']);
        }
    
        $this->layout = "users";
    
        $startDate = date('m/d/Y', strtotime('-1 month'));
        $endDate = date('m/d/Y');
    
        if ($this->request->is(['post', 'put'])) {
            $daterange = $this->request->data['daterange'];
            $dates = explode(" - ", $daterange);
            $start_date = trim($dates[0]);
            $end_date = trim($dates[1]);
    
            $startDate = date('m/d/Y', strtotime($start_date));
            $endDate = date('m/d/Y', strtotime($end_date));
    
            $startdate = date('Y-m-d', strtotime($start_date));
            $enddate = date('Y-m-d', strtotime($end_date));
        } else {
            $startdate = date('Y-m-d', strtotime($startDate));
            $enddate = date('Y-m-d', strtotime($endDate));
        }
    
        // Session values
        $locationId = $this->Session->read('Auth.Location.id');
        
        // Build condition based on role
        $conditions = "created BETWEEN '$startdate 00:00:01' AND '$enddate 23:59:59'";
        $conditions .= " AND stockin_by_id = '$locationId'";
        $conditions .= " AND status = '1'";
        
        $users = $this->Inventory_stockin->query("
            SELECT * FROM inventory_stockin 
            WHERE  $conditions 
            ORDER BY id DESC
        ");
    
        $yourStartDateValue = $startDate . ' - ' . $endDate;
        $this->set('startdate', $yourStartDateValue);
        
        $this->set(compact('users'));
    }
    
    
    public function po_pdf() {
        if (!$this->Session->check('Auth.Location.id')) {
            return $this->redirect(['controller' => 'dashboard', 'action' => 'login']);
        }
    
        $this->layout = "login"; // or use 'ajax' or a dedicated PDF layout
    
        if ($this->request->is(['post', 'put'])) {
            $po_id = $this->request->data['Report']['poid'];
            // Secure: Use bound parameters
            
            $po_id = addslashes($po_id); // minimal protection (better: use bind)
            
            $indents = $this->Inventory_stockin->query("
                SELECT 
                    inventory_stockin.*,
                    inventory_vendors.*,
                    locations.print_line1,
                    locations.print_line2,
                    locations.print_line3,
                    inventory_purchase_order.stockin_on as stockin_date
            
                FROM inventory_stockin
            
                LEFT JOIN inventory_vendors 
                    ON inventory_stockin.vendor_id = inventory_vendors.id
                LEFT JOIN inventory_purchase_order 
                    ON inventory_purchase_order.stockin_id = inventory_stockin.stockin_id
                LEFT JOIN locations 
                    ON locations.id = inventory_stockin.stockin_by_id
            
                WHERE inventory_stockin.stockin_id = '$po_id'
                AND inventory_stockin.status != 0
            ");
            
// 			$items = $this->Inventory_stockin_item->query("
// 				SELECT 
// 					inventory_stockin_items.*,
// 					inventory_purchase_order_items.stockin_discount_value,inventory_purchase_order_items.stockin_discount_value
// 				FROM inventory_stockin_items
// 				LEFT JOIN inventory_purchase_order_items 
// 					ON inventory_purchase_order_items.sku_id = inventory_stockin_items.sku_id
// 					AND inventory_purchase_order_items.p_o_id = inventory_stockin_items.p_o_id
				
// 				WHERE inventory_stockin_items.stockin_id = $po_id
// 				AND inventory_stockin_items.status != 0
// 				AND inventory_stockin_items.stockin_quantity != 0
// 			");

            $items = $this->Inventory_stockin_item->query("
				SELECT 
					inventory_stockin_items.*,
					inventory_purchase_order_items.stockin_discount_value,
					inventory_purchase_order_items.excise_duty_amt,
					inventory_purchase_order_items.addl_excise_duty_amt,
					inventory_purchase_order_items.vat_amt,
					inventory_purchase_order_items.tcs_amt,
					inventory_purchase_order_items.stockin_amount
				FROM inventory_stockin_items
				LEFT JOIN inventory_purchase_order_items 
					ON inventory_purchase_order_items.sku_id = inventory_stockin_items.sku_id
					AND inventory_purchase_order_items.p_o_id = inventory_stockin_items.p_o_id
				
				WHERE inventory_stockin_items.stockin_id = $po_id
				AND inventory_stockin_items.status != 0
				AND inventory_stockin_items.stockin_quantity != 0
			");
			
            $location_id=$indents[0]['inventory_stockin']['stockin_by_id'];
    
            // Get location details - default static for now
            $locationdetail = $this->Location->find('first', [
                'conditions' => ['Location.id' => $location_id]
            ]);
    
            // ================= GST TYPE =================
            $vendorState   = trim($indents[0]['inventory_vendors']['state']);
            $locationState = trim($locationdetail['Location']['state']);
            
            $isIGST = (strcasecmp($vendorState, $locationState) !== 0) ? 1 : 0;
            $isCGST = $isIGST ? 0 : 1;

            // Fetch the associated PO for discount/charge info (for direct stockin)
            $poData = [];
            $poItems = [];
            if (!empty($indents[0]['inventory_indent']['indent_id'])) {
                $iid = $indents[0]['inventory_indent']['indent_id'];
                $poRows = $this->Inventory_purchase_order->find('first', [
                    'conditions' => ['internal_indent_id' => $iid],
                    'recursive'  => -1
                ]);
                if (!empty($poRows)) {
                    $poData = $poRows['Inventory_purchase_order'];
                    $poItemRows = $this->Inventory_purchase_order_item->find('all', [
                        'conditions' => ['p_o_id' => $poData['p_o_id']],
                        'recursive'  => -1
                    ]);
                    $poItems = $poItemRows;
                }
            }
            
            // Send data to the view
            $this->set(compact('po_id', 'indents', 'items', 'locationdetail', 'poData', 'poItems'));
    
            // Optional: Force specific view if needed
            $this->render('po_pdf'); // e.g., pdf_template.ctp
        }
    }
    
	public function view_po_outlet()
	{
		if (!$this->Session->check('Auth.Location.id')) {
			return $this->redirect(['controller' => 'dashboard', 'action' => 'login']);
		}

		$this->layout = "users";

		$stockin_id = addslashes($_REQUEST['id']);

		// ── PO Header (just enough to get p_o_id) ────────────────────────────────
		$indents = $this->Inventory_purchase_order->query("
			SELECT * FROM inventory_purchase_order
			WHERE stockin_id = '$stockin_id'
			  AND status != 0
		");

		if (empty($indents)) {
			$this->Session->setFlash('Purchase Order not found.');
			return $this->redirect(['action' => 'po_grn_history']);
		}

		$p_o_id = $indents[0]['inventory_purchase_order']['p_o_id'];
		$stockin_date = $indents[0]['inventory_purchase_order']['stockin_on'];

		// ── Stockin Data — mirrors po_pdf() exactly ───────────────────────────────
		$stockinData = [];

		$stockinList = $this->Inventory_stockin->query("
			SELECT stockin_id FROM inventory_stockin
			WHERE p_o_id = '$p_o_id'
			  AND status != 0
			ORDER BY id ASC
		");

		foreach ($stockinList as $row) {

			$sid = addslashes($row['inventory_stockin']['stockin_id']);

			// po_pdf: $indents
			$stockinIndents = $this->Inventory_stockin->query("
				SELECT
					inventory_stockin.*,
					inventory_vendors.*,
					locations.print_line1,
					locations.print_line2,
					locations.print_line3
				FROM inventory_stockin
				LEFT JOIN inventory_vendors
					ON inventory_stockin.vendor_id = inventory_vendors.id
				LEFT JOIN locations
					ON locations.id = inventory_stockin.stockin_by_id
				WHERE inventory_stockin.stockin_id = '$sid'
				  AND inventory_stockin.status != 0
			");

			if (empty($stockinIndents)) continue;

			// po_pdf: $items
// 			$stockinItems = $this->Inventory_stockin_item->query("
// 				SELECT
// 					inventory_stockin_items.*,
// 					inventory_purchase_order_items.stockin_discount_value
// 				FROM inventory_stockin_items
// 				LEFT JOIN inventory_purchase_order_items
// 					ON  inventory_purchase_order_items.sku_id  = inventory_stockin_items.sku_id
// 					AND inventory_purchase_order_items.p_o_id  = inventory_stockin_items.p_o_id
// 				WHERE inventory_stockin_items.stockin_id = $sid
// 				  AND inventory_stockin_items.status      != 0
// 				  AND inventory_stockin_items.stockin_quantity != 0
// 			");
            
            $stockinItems = $this->Inventory_stockin_item->query("
				SELECT
					inventory_stockin_items.*,
					inventory_purchase_order_items.stockin_discount_value,
					inventory_purchase_order_items.excise_duty_amt,
					inventory_purchase_order_items.addl_excise_duty_amt,
					inventory_purchase_order_items.vat_amt,
					inventory_purchase_order_items.tcs_amt,
					inventory_purchase_order_items.stockin_amount
				FROM inventory_stockin_items
				LEFT JOIN inventory_purchase_order_items
					ON  inventory_purchase_order_items.sku_id  = inventory_stockin_items.sku_id
					AND inventory_purchase_order_items.p_o_id  = inventory_stockin_items.p_o_id
				WHERE inventory_stockin_items.stockin_id = $sid
				  AND inventory_stockin_items.status      != 0
				  AND inventory_stockin_items.stockin_quantity != 0
			");

			// po_pdf: $locationdetail
			$location_id    = $stockinIndents[0]['inventory_stockin']['stockin_by_id'];
			$locationdetail = $this->Location->find('first', [
				'conditions' => ['Location.id' => $location_id]
			]);

			// po_pdf: GST type
			$vendorState   = trim($stockinIndents[0]['inventory_vendors']['state']);
			$locationState = trim($locationdetail['Location']['state']);
			$isIGST        = (strcasecmp($vendorState, $locationState) !== 0) ? 1 : 0;

			$stockinData[] = [
				'indents'        => $stockinIndents,
				'items'          => $stockinItems,
				'locationdetail' => $locationdetail,
				'isIGST'         => $isIGST,
			];
		}

		$this->set(compact('stockinData', 'stockin_date'));
	}


	 /**
     * C1: Purchase Order Report v2
     * Full PO-level report with proper fields: PO No/ID, date, store, vendor,
     * status, item lines, qty, gross/discount/base/tax/tcs/total,
     * approved/processed/stockin amounts.
     * CSV export identical to view.
     */
    public function po_report_v2()
    {
        
        if (!$this->Session->check('Auth.User.id')) {
            return $this->redirect(['controller' => 'dashboard', 'action' => 'login']);
        }
        $this->layout = "users";

        $startDate = date('m/d/Y', strtotime(date('Y-m-01')));
        $endDate   = date('m/d/Y');

        if ($this->request->is(['post', 'put'])) {
            $daterange = $this->request->data['daterange'] ?? '';
            $dates = explode(" - ", $daterange);
            $startDate = date('m/d/Y', strtotime(trim($dates[0])));
            $endDate   = date('m/d/Y', strtotime(trim($dates[1])));
        }
        $startdate = date('Y-m-d', strtotime($startDate));
        $enddate   = date('Y-m-d', strtotime($endDate));
        $startDT   = $startdate . ' 00:00:00';
        $endDT     = $enddate   . ' 23:59:59';

        $authUser       = $this->Session->read('Auth.User');
        $userRole       = $authUser['role'] ?? '';
        $userLocationId = $authUser['location_ids'] ?? 0;
        $locationId     = $authUser['id'] ?? 0;
		
		$StoreLocationID = $this->Session->read('Auth.Location.id');

        $store_id  = !empty($this->request->data['Search']['store'])  ? (int)$this->request->data['Search']['store']  : null;
        $vendor_id = !empty($this->request->data['Search']['vendor']) ? (int)$this->request->data['Search']['vendor'] : null;
        $status_f  = isset($this->request->data['Search']['status'])  ? $this->request->data['Search']['status']      : '';

        $where = "WHERE ipo.status != 0
                    AND ipo.po_date BETWEEN '$startdate' AND '$enddate'";
        if ($store_id)  $where .= " AND ipo.location_id = $store_id";
        elseif ($userRole !== 'admin' && !empty($locationId))     $where .= " AND ipo.location_id IN ($locationId)";

        if ($vendor_id) $where .= " AND ipo.vendor_id = $vendor_id";
        if ($status_f !== '') $where .= " AND ipo.status = " . (int)$status_f;

        $statusLabels = [1 => 'Submitted', 2 => 'Approved', 3 => 'Processed', 4 => 'Stocked In'];
		
        $rows = $this->Inventory_purchase_order->query("
            SELECT
                ipo.p_o_id,
                ipo.po_date AS po_date,
                ipo.location_name AS store,
                ipo.vendor_name AS vendor,
                ipo.status,
                COUNT(ipoi.id) AS item_count,
                SUM(ipoi.sku_quantity) AS qty_total,
                SUM(ipoi.stockin_quantity) as stockin_qty,
                SUM(ipoi.sku_price * ipoi.sku_quantity) AS gross,
                SUM(COALESCE(ipoi.sku_discount_value,0)) AS discount,
                SUM(ipoi.sku_price * ipoi.sku_quantity - COALESCE(ipoi.sku_discount_value,0)) AS base,
                SUM(COALESCE(ipoi.sku_total_tax_value,0)) AS tax,
                SUM(COALESCE(ipoi.tcs_amount,0)) AS tcs,
                SUM(COALESCE(ipoi.excise_duty_amt,0)) AS excise_total,
                SUM(COALESCE(ipoi.addl_excise_duty_amt,0)) AS addl_excise_total,
                SUM(COALESCE(ipoi.vat_amt,0)) AS vat_total,
                SUM(COALESCE(ipoi.tcs_amt,0)) AS tcs_total,
                SUM(ipoi.sku_amount) AS total,
                SUM(ipoi.approved_amount) AS approved_amt,
                SUM(ipoi.approved_total_tax_value) AS approved_tax,
                SUM(ipoi.processed_amount) AS processed_amt,
                SUM(ipoi.processed_total_tax_value) AS processed_tax,
                SUM(ipoi.stockin_amount) AS stockin_amt,
                SUM(ipoi.stockin_total_tax_value) AS stockin_tax
            FROM inventory_purchase_order ipo
            LEFT JOIN inventory_purchase_order_items ipoi ON ipo.p_o_id = ipoi.p_o_id AND ipoi.status != 0
            $where
            GROUP BY ipo.p_o_id
            ORDER BY ipo.id DESC
        ");

        $pos = [];
        foreach ($rows as $r) {
            $pos[] = [
                'po_id'        => $r['ipo']['p_o_id'],
                'po_date'      => $r['ipo']['po_date'] ?? $r[0]['po_date'] ?? '',
                'store'        => $r['ipo']['store']   ?? $r[0]['store']   ?? '',
                'vendor'       => $r['ipo']['vendor']  ?? $r[0]['vendor']  ?? '',
                'status'       => $statusLabels[$r['ipo']['status'] ?? 0] ?? 'Unknown',
                'item_count'   => $r[0]['item_count']   ?? 0,
                'qty_total'    => $r[0]['qty_total']    ?? 0,
                'stockin_qty'  => $r[0]['stockin_qty']    ?? 0,
                'gross'        => $r[0]['gross']        ?? 0,
                'discount'     => $r[0]['discount']     ?? 0,
                'base'         => $r[0]['base']         ?? 0,
                'tax'          => $r[0]['tax']          ?? 0,
                'tcs'          => $r[0]['tcs']          ?? 0,
                'excise_total' => $r[0]['excise_total']      ?? 0,
                'addl_excise_total' => $r[0]['addl_excise_total'] ?? 0,
                'vat_total'    => $r[0]['vat_total']         ?? 0,
                'tcs_total'    => $r[0]['tcs_total']         ?? 0,
                'total'        => $r[0]['total']        ?? 0,
                'approved_amt' => (float)($r[0]['approved_amt'] ?? 0),
                'processed_amt'=> (float)($r[0]['processed_amt'] ?? 0),
                'stockin_amt'  => (float)($r[0]['stockin_amt']  ?? 0)
            ];
        }
		
        $vendorConditions = ['Inventory_vendor.status' => 1];
        $conditions = ['status' => 1]; // T14-fix: show all location types in purchase report store dropdown
        if ($userRole !== 'admin' && !empty($userLocationId)) {
            $conditions['id IN'] = array_map('trim', explode(',', $userLocationId)); 
        }
		elseif(!empty($StoreLocationID)){	
			$conditions['id IN'] = array_map('trim', explode(',', $StoreLocationID));

			$vendorConditions['Inventory_vendor.id IN'] =
				$this->Inventory_vendor_service_area->find('list', [
					'fields' => ['vendor_id'],
					'conditions' => ['area_id' => trim($StoreLocationID)]
				]);
		}
        $stores  = $this->Location->find('all', ['conditions' => $conditions]);
		
		$vendors = $this->Inventory_vendor->find('list', [
			'conditions' => $vendorConditions,
			'fields' => ['Inventory_vendor.id', 'Inventory_vendor.company'],
			'order'  => ['Inventory_vendor.company ASC']
		]);
		

        $this->set('startdate', $startDate . ' - ' . $endDate);
        $this->set(compact('stores', 'vendors', 'pos', 'statusLabels','StoreLocationID'));
    }

    /**
     * AJAX: Return item lines for a single PO (called on modal open)
     */
    public function po_report_v2_items()
    {
        if (!$this->Session->check('Auth.User.id')) {
            return $this->response->body(json_encode(['error' => 'Unauthorized']));
        }
        $this->autoRender = false;
        $this->response->type('application/json');

        $po_id = $this->request->query('po_id') ?? 0;
        if (!$po_id) {
            return $this->response->body(json_encode(['error' => 'Invalid PO']));
        }

        $items = $this->Inventory_purchase_order->query("
            SELECT
                ipoi.sku_name                                                                    AS item_name,
                ipoi.sku_quantity                                                                AS qty,
                ipoi.stockin_quantity                                                            AS stockin_qty,
                (ipoi.sku_price * ipoi.sku_quantity)                                             AS gross,
                COALESCE(ipoi.sku_discount_value, 0)                                             AS discount,
                (ipoi.sku_price * ipoi.sku_quantity - COALESCE(ipoi.sku_discount_value, 0))      AS base,
                COALESCE(ipoi.sku_total_tax_value, 0)                                            AS tax,
                COALESCE(ipoi.tcs_amount, 0)                                                     AS tcs,
                COALESCE(ipoi.excise_duty_amt, 0)                                                AS excise_total,
                COALESCE(ipoi.addl_excise_duty_amt, 0)                                           AS addl_excise_total,
                COALESCE(ipoi.vat_amt, 0)                                                        AS vat_total,
                COALESCE(ipoi.tcs_amt, 0)                                                        AS tcs_total,
                ipoi.sku_amount                                                                  AS total,
                ipoi.approved_amount   AS approved_amt,
                ipoi.processed_amount  AS processed_amt,
                ipoi.stockin_amount    AS stockin_amt
            FROM inventory_purchase_order_items ipoi
            WHERE ipoi.p_o_id = $po_id AND ipoi.status != 0
            ORDER BY ipoi.id ASC
        ");

        $result = [];
        foreach ($items as $row) {
            $r  = $row['ipoi'] ?? [];   // real table columns (sku_name etc.)
            $r0 = $row[0]      ?? [];   // computed/expression columns (gross, discount, base …)

            $result[] = [
                'item_name'     => $r['item_name']             ?? $r0['item_name']     ?? '',
                'qty'           => $r['qty']                   ?? $r0['qty']           ?? 0,
                'stockin_qty'   => $r['stockin_qty']           ?? $r0['stockin_qty']           ?? 0,
                'gross'         => $r0['gross']                ?? 0,   // computed → always in [0]
                'discount'      => $r0['discount']             ?? 0,
                'base'          => $r0['base']                 ?? 0,
                'tax'           => $r['tax']                   ?? $r0['tax']           ?? 0,
                'tcs'           => $r['tcs']                   ?? $r0['tcs']           ?? 0,
                'excise_total'  => $r['excise_total']          ?? $r0['excise_total']  ?? 0,
                'addl_excise_total' => $r['addl_excise_total'] ?? $r0['addl_excise_total'] ?? 0,
                'vat_total'     => $r['vat_total']             ?? $r0['vat_total']     ?? 0,
                'tcs_total'     => $r['tcs_total']             ?? $r0['tcs_total']     ?? 0,
                'total'         => $r['total']                 ?? $r0['total']         ?? 0,
                'approved_amt'  => $r['approved_amt']          ?? $r0['approved_amt']  ?? 0,
                'processed_amt' => $r['processed_amt']         ?? $r0['processed_amt'] ?? 0,
                'stockin_amt'   => $r['stockin_amt']           ?? $r0['stockin_amt']   ?? 0,
            ];
        }

        return $this->response->body(json_encode(['items' => $result]));
    }

    public function po_report_v2_export()
    {
        $this->autoRender = false;
        $date_range = $_GET['sdate'] ?? date('m/d/Y') . ' - ' . date('m/d/Y');
        $dates      = explode(" - ", $date_range);
        $startdate  = date('Y-m-d', strtotime(trim($dates[0])));
        $enddate    = date('Y-m-d', strtotime(trim($dates[1])));
        $startDT    = $startdate . ' 00:00:00';
        $endDT      = $enddate   . ' 23:59:59';
        $store_id   = !empty($_GET['store'])  ? (int)$_GET['store']  : null;
        $vendor_id  = !empty($_GET['vendor']) ? (int)$_GET['vendor'] : null;
        $status_f   = isset($_GET['status'])  ? $_GET['status']      : '';

        $authUser       = $this->Session->read('Auth.User');
        $userRole       = $authUser['role'] ?? '';
        $userLocationId = $authUser['location_ids'] ?? 0;
        $locationId     = $authUser['id'] ?? 0;

        $where = "WHERE ipo.status != 0
                    AND ipo.po_date BETWEEN '$startdate' AND '$enddate'";
        if ($store_id)  $where .= " AND ipo.location_id = $store_id";
        elseif ($userRole !== 'admin' && !empty($locationId))     $where .= " AND ipo.location_id IN ($locationId)";
        if ($vendor_id) $where .= " AND ipo.vendor_id = $vendor_id";
        if ($status_f !== '') $where .= " AND ipo.status = " . (int)$status_f;
    
        $statusLabels = [1 => 'Submitted', 2 => 'Approved', 3 => 'Processed', 4 => 'Stocked In'];

        // Fetch PO-level summary
        $rows = $this->Inventory_purchase_order->query("
            SELECT ipo.p_o_id, ipo.po_date AS po_date, ipo.location_name AS store,
                ipo.vendor_name AS vendor, ipo.status,
                COUNT(ipoi.id) AS item_count, SUM(ipoi.sku_quantity) AS qty_total,
                SUM(ipoi.stockin_quantity) AS stockin_qty_total,
                SUM(ipoi.sku_price * ipoi.sku_quantity) AS gross,
                SUM(COALESCE(ipoi.sku_discount_value,0)) AS discount,
                SUM(ipoi.sku_price * ipoi.sku_quantity - COALESCE(ipoi.sku_discount_value,0)) AS base,
                SUM(COALESCE(ipoi.sku_total_tax_value,0)) AS tax,
                SUM(COALESCE(ipoi.tcs_amount,0)) AS tcs,
                SUM(COALESCE(ipoi.excise_duty_amt,0)) AS excise_total,
                SUM(COALESCE(ipoi.addl_excise_duty_amt,0)) AS addl_excise_total,
                SUM(COALESCE(ipoi.vat_amt,0)) AS vat_total,
                SUM(COALESCE(ipoi.tcs_amt,0)) AS tcs_total,
                SUM(ipoi.sku_amount) AS total,
                SUM(ipoi.approved_amount) AS approved_amt,
                SUM(COALESCE(ipoi.processed_amount,0)) AS processed_amt,
                SUM(COALESCE(ipoi.stockin_amount,0)) AS stockin_amt
            FROM inventory_purchase_order ipo
            LEFT JOIN inventory_purchase_order_items ipoi ON ipo.p_o_id = ipoi.p_o_id AND ipoi.status != 0
            $where GROUP BY ipo.p_o_id ORDER BY ipo.id DESC
        ");

        // Collect all PO IDs to fetch item lines in one query
        $poIds = [];
        foreach ($rows as $r) {
            $poIds[] = $r['ipo']['p_o_id'];
        }

        // Fetch all item lines for these POs in one shot
        $itemsMap = [];
        if (!empty($poIds)) {
            $inList   = implode(',', $poIds);
            $itemRows = $this->Inventory_purchase_order->query("
                SELECT
                    ipoi.p_o_id,
                    ipoi.sku_name                                                                     AS item_name,
                    ipoi.sku_quantity                                                                  AS qty,
                    ipoi.stockin_quantity                                                             AS stockin_qty,
                    (ipoi.sku_price * ipoi.sku_quantity)                                              AS gross,
                    COALESCE(ipoi.sku_discount_value, 0)                                              AS discount,
                    (ipoi.sku_price * ipoi.sku_quantity - COALESCE(ipoi.sku_discount_value, 0))       AS base,
                    COALESCE(ipoi.sku_total_tax_value, 0)                                             AS tax,
                    COALESCE(ipoi.tcs_amount, 0)                                                      AS tcs,
                    COALESCE(ipoi.excise_duty_amt, 0)                                                 AS excise_total,
                    COALESCE(ipoi.addl_excise_duty_amt, 0)                                            AS addl_excise_total,
                    COALESCE(ipoi.vat_amt, 0)                                                         AS vat_total,
                    COALESCE(ipoi.tcs_amt, 0)                                                         AS tcs_total,
                    ipoi.sku_amount                                                                    AS total,
                    ipoi.approved_amount                                                               AS approved_amt,
                    ipoi.processed_amount                                                              AS processed_amt,
                    ipoi.stockin_amount                                                                AS stockin_amt
                FROM inventory_purchase_order_items ipoi
                WHERE ipoi.p_o_id IN ($inList) AND ipoi.status != 0
                ORDER BY ipoi.p_o_id, ipoi.id ASC
            ");

            foreach ($itemRows as $ir) {
                $poId = $ir['ipoi']['p_o_id'] ?? $ir[0]['p_o_id'] ?? null;
                if (!$poId) continue;
                $itemsMap[$poId][] = [
                    'item_name'     => $ir['ipoi']['item_name']    ?? $ir[0]['item_name']    ?? '',
                    'qty'           => $ir['ipoi']['qty']          ?? $ir[0]['qty']          ?? 0,
                    'stockin_qty'   => $ir['ipoi']['stockin_qty']  ?? $ir[0]['stockin_qty']  ?? 0,
                    'gross'         => $ir[0]['gross']             ?? 0,
                    'discount'      => $ir[0]['discount']          ?? 0,
                    'base'          => $ir[0]['base']              ?? 0,
                    'tax'           => $ir[0]['tax']               ?? 0,
                    'tcs'           => $ir[0]['tcs']               ?? 0,
                    'excise_total'  => $ir['excise_total']          ?? $ir[0]['excise_total']  ?? 0,
                    'addl_excise_total' => $ir['addl_excise_total'] ?? $ir[0]['addl_excise_total'] ?? 0,
                    'vat_total'     => $ir['vat_total']             ?? $ir[0]['vat_total']     ?? 0,
                    'tcs_total'     => $ir['tcs_total']             ?? $ir[0]['tcs_total']     ?? 0,
                    'total'         => $ir['ipoi']['total']        ?? $ir[0]['total']        ?? 0,
                    'approved_amt'  => $ir['ipoi']['approved_amt'] ?? $ir[0]['approved_amt'] ?? 0,
                    'processed_amt' => $ir['ipoi']['processed_amt']?? $ir[0]['processed_amt']?? 0,
                    'stockin_amt'   => $ir['ipoi']['stockin_amt']  ?? $ir[0]['stockin_amt']  ?? 0,
                ];
            }
        }

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="PO_Report_v2_' . $startdate . '_' . $enddate . '.csv"');
        $fp = fopen('php://output', 'w');

        // Header row
        fputcsv($fp, ['PO No','Date','Store','Vendor','Status','Items','Item Qty','Stockin Qty','Gross','Discount','Base','Tax','TCS','Excise','Addl. Excise','VAT','TCS','Total','Approved(incl tax)','Processed(incl tax)','StockedIn(incl tax)']);

        foreach ($rows as $r) {
            $poId = $r['ipo']['p_o_id'];

            // PO summary row
            fputcsv($fp, [
                $poId,
                $r['ipo']['po_date']  ?? '',
                $r['ipo']['store']    ?? '',
                $r['ipo']['vendor']   ?? '',
                $statusLabels[$r['ipo']['status'] ?? 0] ?? 'Unknown',
                $r[0]['item_count']   ?? 0,
                round($r[0]['qty_total']     ?? 0, 3),
                round($r[0]['stockin_qty_total']     ?? 0, 3),
                round($r[0]['gross']         ?? 0, 2),
                round($r[0]['discount']      ?? 0, 2),
                round($r[0]['base']          ?? 0, 2),
                round($r[0]['tax']           ?? 0, 2),
                round($r[0]['tcs']           ?? 0, 2),
                round($r[0]['excise_total']      ?? 0, 2),
                round($r[0]['addl_excise_total'] ?? 0, 2),
                round($r[0]['vat_total']         ?? 0, 2),
                round($r[0]['tcs_total']         ?? 0, 2),
                round($r[0]['total']         ?? 0, 2),
                round($r[0]['approved_amt']  ?? 0, 2),
                round($r[0]['processed_amt'] ?? 0, 2),
                round($r[0]['stockin_amt']   ?? 0, 2),
            ]);

            // Item detail rows — indented with 5 empty leading columns
            foreach ($itemsMap[$poId] ?? [] as $item) {
                fputcsv($fp, [
                    '',   // PO No
                    '',   // Date
                    '',   // Store
                    '',   // Vendor
                    '',   // Status
                    $item['item_name'],
                    round($item['qty'],          3),
                    round($item['stockin_qty'],  3),
                    round($item['gross'],        2),
                    round($item['discount'],     2),
                    round($item['base'],         2),
                    round($item['tax'],          2),
                    round($item['tcs'],          2),
                    round($item['excise_total'], 2),
                    round($item['addl_excise_total'], 2),
                    round($item['vat_total'],    2),
                    round($item['tcs_total'],    2),
                    round($item['total'],        2),
                    round($item['approved_amt'], 2),
                    round($item['processed_amt'],2),
                    round($item['stockin_amt'],  2),
                ]);
            }
        }

        fclose($fp);
        exit;
    }
    
    public function stock_transfer()
    {
        if(!$this->Session->check('Auth.User.id')){
            $this->redirect(array('controller'=>'dashboard','action' => 'login'));      
        }
        
        $this->layout = "users";
        
        $startDate = date('m/d/Y', strtotime('-12 months'));
        $endDate = date('m/d/Y');
        
        $location_id = $this->Session->read('Auth.Location.id');
    
        if($this->request->is('post', 'put'))
        {
            $daterange= $this->request->data['daterange'];
            $dates = explode(" - ", $daterange);
            $start_date = trim($dates[0]);
            $startDate = date('m/d/Y', strtotime($start_date));
            $end_date = trim($dates[1]);
            $endDate = date('m/d/Y', strtotime($end_date));
            $startdate= date('Y-m-d', strtotime($start_date));
            $enddate= date('Y-m-d', strtotime($end_date));
        }else{
            $startdate= date('Y-m-d', strtotime($startDate));
            $enddate= date('Y-m-d', strtotime($endDate));
        }   
        
        $_shiftRange = $this->getShiftDateRange($startdate, $enddate, isset($store_id) ? $store_id : (isset($location_id) ? $location_id : null)); $startDatetime = $_shiftRange['startDatetime']; $endDatetime = $_shiftRange['endDatetime'];
        
        $users = $this->Stock_transfer->query("SELECT * FROM stock_transfers where status=1 and created between '$startDatetime' and  '$endDatetime' and transfer_from=$location_id ORDER BY id desc");
        $yourStartDateValue=$startDate . ' - ' . $endDate;
        $this->set('startdate', $yourStartDateValue);
        
        $this->set(compact('users'));

    }

    public function create_stock_tranfer()
    {
        if (!$this->Session->check('Auth.User.id')) {
            return $this->redirect(['controller'=>'dashboard','action'=>'login']);
        }
    
        $this->layout = "users";
    
        $location_id = $this->Session->read('Auth.Location.id');
    
        $locations = $this->Location->find('all', [
            'conditions' => [
                'status' => 1,
                'id' => $location_id,
                'type' => 'Outlet'
            ]
        ]);
    
        $tolocations = $this->Location->find('all', [
            'conditions' => [
                'status' => 1,
                'id !=' => $location_id,
                'type' => 'Outlet'
            ]
        ]);
    
        $users = $this->Inventory_sku->find('all', [
        
            'fields' => [
                'Inventory_sku.*',
                'COALESCE(SUM(InventorySkuTax.tax_percentage),0) AS total_tax_percentage'
            ],
        
            'joins' => [
        
                [
                    'table' => 'inventory_sku_tax',
                    'alias' => 'InventorySkuTax',
                    'type' => 'LEFT',
                    'conditions' => [
                        'InventorySkuTax.product_id = Inventory_sku.id',
                        'InventorySkuTax.status' => 1
                    ]
                ]
        
            ],
        
            'conditions' => [
                'Inventory_sku.status' => 1
            ],
        
            'group' => ['Inventory_sku.id'],
        
            'order' => ['Inventory_sku.name ASC']
        
        ]);
    
        $this->set(compact('locations', 'tolocations', 'users'));
    }
    
    public function add_stock_transfer()
    {
        $this->autoRender = false;
    
        if (!empty($_POST['checkedData'])) {
    
            $datasource = $this->Stock_transfer->getDataSource();
            $datasource->begin();
    
            try {
    
                $checkeddatas   = json_decode($_POST['checkedData'], true);
    
                $transfer_from  = $_POST['tranferfrom'];
                $transfer_to    = $_POST['transferto'];
    
                $created_by     = $this->Session->read('Auth.Location.id');
                $created_by_name     = $this->Session->read('Auth.Location.name');
    
    
                /*
                ==========================
                GET LOCATION NAMES
                ==========================
                */
                $fromLocation = $this->Location->find('first', [
                    'conditions' => ['id' => $transfer_from,'status'=>1],
                    'fields' => ['name'],
                    'recursive' => -1
                ]);
    
                $toLocation = $this->Location->find('first', [
                    'conditions' => ['id' => $transfer_to,'status'=>1],
                    'fields' => ['name'],
                    'recursive' => -1
                ]);
    
                $transfer_from_name = !empty($fromLocation['Location']['name']) ? $fromLocation['Location']['name'] : '';
                $transfer_to_name   = !empty($toLocation['Location']['name']) ? $toLocation['Location']['name'] : '';
    
    
                /*
                ==========================
                GENERATE UNIQUE TRANSFER ID
                ==========================
                */
                do {
                    $txnid = 'TRF' . time() . rand(100,999);
    
                    $existing = $this->Stock_transfer->find('first', [
                        'conditions' => ['transfer_id' => $txnid]
                    ]);
    
                } while (!empty($existing));
    
    
                /*
                ==========================
                SAVE MAIN TRANSFER
                ==========================
                */
                $transferData = [
                    'transfer_id'        => $txnid,
                    'transfer_from'      => $transfer_from,
                    'transfer_from_name' => $transfer_from_name,
                    'transfer_to'        => $transfer_to,
                    'transfer_to_name'   => $transfer_to_name,
                    'created_by'         => $created_by,
                    'created_by_name'    => $created_by_name,
                    'status'             => 1,
                    'sync'               => 0,
                    'local_sync'         => 0
                ];
    
                $this->Stock_transfer->create();
    
                if (!$this->Stock_transfer->save($transferData)) {
                    throw new Exception('Transfer header not saved.');
                }
                
                $totalTax = 0;
                $totalAmount = 0;
    
                /*
                ==========================
                LOOP ITEMS
                ==========================
                */
                
                foreach ($checkeddatas as $checkeddata) {

                    $skuid      = $checkeddata['id'];
                    $price      = $checkeddata['price'];
                    $sku_name   = $checkeddata['sku_name'];
                    $sku_brand  = $checkeddata['sku_brand'];
                    $unit_name  = $checkeddata['unit_name'];
                    $unit_id    = $checkeddata['unit_id'];
                    $quantity   = $checkeddata['sku_quantity'];
                    $taxPercent = $checkeddata['tax_percentage'];
                
                    /*
                    ==========================
                    CHECK SOURCE STOCK
                    ==========================
                    */
                    $ledgerprevdetails = $this->Stock_ledger->find('first', [
                        'conditions' => [
                            'sku_id' => $skuid,
                            'type' => 'Outlet',
                            'item_type' => 'SKU',
                            'store_id' => $transfer_from
                        ],
                        'fields' => ['actual_balance'],
                        'order' => ['id DESC'],
                        'recursive' => -1
                    ]);
    
                    $ledger_prev_quantity = !empty($ledgerprevdetails['Stock_ledger']['actual_balance'])
                        ? $ledgerprevdetails['Stock_ledger']['actual_balance']
                        : 0;
    
                    /*
                    ==========================
                    CALCULATIONS
                    ==========================
                    */
                    $baseAmount = $price * $quantity;
                
                    $taxValue = ($baseAmount * $taxPercent) / 100;
                
                    $finalAmount = $baseAmount + $taxValue;
                
                    /*
                    ==========================
                    SAVE TRANSFER ITEM
                    ==========================
                    */
                    
                    $itemData = [
                        'transfer_id'     => $txnid,
                        'sku_id'          => $skuid,
                        'sku_name'        => $sku_name,
                        'sku_brand'       => $sku_brand,
                        'sku_unit'        => $unit_id,
                        'sku_unit_name'   => $unit_name,
                        'sku_price'       => $price,
                        'sku_quantity'    => $quantity,
                        'tax_percent'     => $taxPercent,
                        'tax_value'       => $taxValue,
                        'amount'          => $finalAmount,
                        'sync'            => 0,
                        'local_sync'      => 0
                    ];
    
                    $this->Stock_transfer_item->create();
    
                    if (!$this->Stock_transfer_item->save($itemData)) {
                        throw new Exception('Transfer item save failed.');
                    }
                    
                    $totalTax += $taxValue;
                    $totalAmount += $finalAmount;
    
                    /*
                    ==========================
                    DEBIT SOURCE LEDGER
                    ==========================
                    */
                    $newQuantity = $ledger_prev_quantity - $quantity;
                    
                    $ledgerData = [
                        'store_id'        => $transfer_from,
                        'sku_id'          => $skuid,
                        'sku_name'        => $sku_name,
                        'sku_price'       => $price,
                        'unit_id'         => $unit_id,
                        'unit_name'       => $unit_name,
                        'record_id'       => $txnid,
                        'record_type'     => 'stock_transfer_out',
                        'quantity'        => $quantity,
                        'balance_stock'   => $newQuantity,
                        'actual_balance'  => $newQuantity,
                        'quantity_type'   => 'debit',
                        'type'            => 'Outlet',
                        'item_type'       => 'SKU',
                        'created'         => date('Y-m-d H:i:s')
                    ];
    
                    $this->Stock_ledger->create();
    
                    if (!$this->Stock_ledger->save($ledgerData)) {
                        throw new Exception('Source ledger save failed.');
                    }
    
    
                    /*
                    ==========================
                    DESTINATION STOCK CHECK
                    ==========================
                    */
                    $ledgerprevdetails2 = $this->Stock_ledger->find('first', [
                        'conditions' => [
                            'sku_id' => $skuid,
                            'type' => 'Outlet',
                            'item_type' => 'SKU',
                            'store_id' => $transfer_to
                        ],
                        'fields' => ['actual_balance'],
                        'order' => ['id DESC'],
                        'recursive' => -1
                    ]);
    
                    $ledger_prev_quantity2 = !empty($ledgerprevdetails2['Stock_ledger']['actual_balance'])
                        ? $ledgerprevdetails2['Stock_ledger']['actual_balance']
                        : 0;
    
    
                    /*
                    ==========================
                    CREDIT DESTINATION
                    ==========================
                    */
                    $newQuantity2 = $ledger_prev_quantity2 + $quantity;
    
                    $ledgerData2 = [
                        'store_id'        => $transfer_to,
                        'sku_id'          => $skuid,
                        'sku_name'        => $sku_name,
                        'sku_price'       => $price,
                        'unit_id'         => $unit_id,
                        'unit_name'       => $unit_name,
                        'record_id'       => $txnid,
                        'record_type'     => 'stock_transfer_in',
                        'quantity'        => $quantity,
                        'balance_stock'   => $newQuantity2,
                        'actual_balance'  => $newQuantity2,
                        'quantity_type'   => 'credit',
                        'type'            => 'Outlet',
                        'item_type'       => 'SKU',
                        'created'         => date('Y-m-d H:i:s')
                    ];
    
                    $this->Stock_ledger->create();
    
                    if (!$this->Stock_ledger->save($ledgerData2)) {
                        throw new Exception('Destination ledger save failed.');
                    }
                }
                
                /*
                ==========================
                UPDATE HEADER TAX/AMOUNT
                ==========================
                */
                $this->Stock_transfer->id = $this->Stock_transfer->getLastInsertID();
                
                if (!$this->Stock_transfer->save([
                    'tax' => $totalTax,
                    'amount' => $totalAmount
                ])) {
                    throw new Exception('Failed to update transfer totals.');
                }
                /*
                ==========================
                COMMIT TRANSACTION
                ==========================
                */
                $datasource->commit();
    
                echo json_encode([
                    'response_code' => 1,
                    'data' => 'Stock Transfer Saved Successfully'
                ]);
    
            } catch (Exception $e) {
    
                $datasource->rollback();
    
                echo json_encode([
                    'response_code' => 0,
                    'data' => $e->getMessage()
                ]);
            }
        }
    }
    
    public function view_stock_transfer()
    {
        $this->layout = "users";
    
        $id = $_GET['id'];
    
        $transfer = $this->Stock_transfer->find('first', [
            'conditions' => ['transfer_id' => $id,'status'=>1]
        ]);
    
        $items = $this->Stock_transfer_item->find('all', [
            'conditions' => [
                'transfer_id' => $id,'status'=>1
            ]
        ]);
    
        $this->set(compact('transfer','items'));
    }
    
    public function stock_transfer_pdf()
    {
        if (!$this->Session->check('Auth.User.id')) {
            return $this->redirect(['action' => 'login']);
        }
    
        $this->layout = "login";
    
        if ($this->request->is(['post'])) {
    
            $transfer_id = $this->request->data['transfer_id'];
    
            $transfer = $this->Stock_transfer->find('first', [
                'conditions' => [
                    'transfer_id' => $transfer_id,'status'=>1
                ]
            ]);
    
            $items = $this->Stock_transfer_item->find('all', [
                'conditions' => [
                    'transfer_id' => $transfer_id,'status'=>1
                ]
            ]);
            
            $locationdetail = $this->Location->find('first', [
                'conditions' => [
                    'Location.id' => $transfer['Stock_transfer']['transfer_from'],'status'=>1
                ]
            ]);
    
            $this->set(compact('transfer', 'items', 'locationdetail'));
    
            $this->render('stock_transfer_pdf');
        }
    }
    
    public function delete_stock_transfer() {
        $this->autoRender = false; // Disable view rendering
    
        if ($this->request->is('post')) {
            $userId = $this->request->data['_id'];
    
            // Update user status
            $this->Stock_transfer->id = $userId;
            if ($this->Stock_transfer->save(['status' => 0,'sync' => 0,'local_sync' => 0])) {
                echo json_encode(['status' => true]);
            } else {
                echo json_encode(['status' => false]);
            }
        }
    }
    
    public function edit_stock_transfer()
    {
        if (!$this->Session->check('Auth.Location.id')) {
            return $this->redirect([
                'controller' => 'dashboard',
                'action' => 'login'
            ]);
        }
    
        $this->layout = "users";
    
        $transfer_id = $this->request->query('id');
    
        $location_id = $this->Session->read('Auth.Location.id');
    
        /*
        ==========================
        GET TRANSFER HEADER
        ==========================
        */
        $transfer = $this->Stock_transfer->find('first', [
            'conditions' => [
                'transfer_id' => $transfer_id,'status' =>1
            ]
        ]);
    
        /*
        ==========================
        GET TRANSFER ITEMS
        ==========================
        */
        $transferItems = $this->Stock_transfer_item->find('all', [
            'conditions' => [
                'transfer_id' => $transfer_id,'status' =>1
            ]
        ]);
    
        /*
        ==========================
        FROM LOCATION
        ==========================
        */
        $locations = $this->Location->find('all', [
            'conditions' => [
                'status' => 1,
                'id' => $location_id,
                'type' => 'Outlet'
            ]
        ]);
    
        /*
        ==========================
        TO LOCATIONS
        ==========================
        */
        $tolocations = $this->Location->find('all', [
            'conditions' => [
                'status' => 1,
                'id !=' => $location_id,
                'type' => 'Outlet'
            ]
        ]);
    
        /*
        ==========================
        GET SKU LIST WITH TAX
        ==========================
        */
        $users = $this->Inventory_sku->find('all', [
    
            'fields' => [
                'Inventory_sku.*',
                'COALESCE(SUM(InventorySkuTax.tax_percentage),0) AS total_tax_percentage'
            ],
    
            'joins' => [
    
                [
                    'table' => 'inventory_sku_tax',
                    'alias' => 'InventorySkuTax',
                    'type' => 'LEFT',
                    'conditions' => [
                        'InventorySkuTax.product_id = Inventory_sku.id',
                        'InventorySkuTax.status' => 1
                    ]
                ]
    
            ],
    
            'conditions' => [
                'Inventory_sku.status' => 1
            ],
    
            'group' => ['Inventory_sku.id'],
    
            'order' => ['Inventory_sku.name ASC']
    
        ]);
    
        /*
        ==========================
        SEND TO VIEW
        ==========================
        */
        $this->set(compact(
            'transfer',
            'transferItems',
            'locations',
            'tolocations',
            'users'
        ));
    }
    
    public function update_stock_transfer()
    {
        $this->autoRender = false;
    
        $datasource = $this->Stock_transfer->getDataSource();
        $datasource->begin();
    
        try {
    
            $transfer_id   = $this->request->data['transfer_id'];
            $transfer_from = $this->request->data['tranferfrom'];
            $transfer_to   = $this->request->data['transferto'];
    
            $checkeddatas = json_decode($this->request->data['checkedData'], true);
    
            /*
            ==========================
            GET LOCATION NAMES
            ==========================
            */
            $fromLocation = $this->Location->find('first', [
                'conditions' => ['id' => $transfer_from,'status'=>1],
                'fields' => ['name'],
                'recursive' => -1
            ]);
    
            $toLocation = $this->Location->find('first', [
                'conditions' => ['id' => $transfer_to,'status'=>1],
                'fields' => ['name'],
                'recursive' => -1
            ]);
    
            $transfer_from_name = !empty($fromLocation['Location']['name']) ? $fromLocation['Location']['name'] : '';
            $transfer_to_name   = !empty($toLocation['Location']['name']) ? $toLocation['Location']['name'] : '';
    
            /*
            ==========================
            GET OLD TRANSFER + ITEMS
            ==========================
            */
            $oldTransfer = $this->Stock_transfer->find('first', [
                'conditions' => ['transfer_id' => $transfer_id, 'status' => 1]
            ]);
    
            $oldItems = $this->Stock_transfer_item->find('all', [
                'conditions' => ['transfer_id' => $transfer_id, 'status' => 1]
            ]);
    
            /*
            ==========================
            REVERSE OLD LEDGER
            ==========================
            */
            foreach ($oldItems as $oldItem) {
                $sku_id   = $oldItem['Stock_transfer_item']['sku_id'];
                $qty      = $oldItem['Stock_transfer_item']['sku_quantity'];
                $price    = $oldItem['Stock_transfer_item']['sku_price'];
                $sku_name = $oldItem['Stock_transfer_item']['sku_name'];
                $unit_id  = $oldItem['Stock_transfer_item']['sku_unit'];
                $unit_nm  = $oldItem['Stock_transfer_item']['sku_unit_name'];
    
                // SOURCE REVERSE CREDIT
                $sourcePrev = $this->Stock_ledger->find('first', [
                    'conditions' => [
                        'sku_id' => $sku_id,
                        'store_id' => $oldTransfer['Stock_transfer']['transfer_from'],
                        'type' => 'Outlet',
                        'item_type' => 'SKU'
                    ],
                    'fields' => ['actual_balance'],
                    'order' => ['id DESC']
                ]);
    
                $sourceBal = $sourcePrev['Stock_ledger']['actual_balance'] ?? 0;
                $newSourceBal = $sourceBal + $qty;
    
                $this->Stock_ledger->create();
                $this->Stock_ledger->save([
                    'store_id' => $oldTransfer['Stock_transfer']['transfer_from'],
                    'sku_id' => $sku_id,
                    'sku_name' => $sku_name,
                    'sku_price' => $price,
                    'unit_id' => $unit_id,
                    'unit_name' => $unit_nm,
                    'record_id' => $transfer_id,
                    'record_type' => 'stock_transfer_reverse_in',
                    'quantity' => $qty,
                    'balance_stock' => $newSourceBal,
                    'actual_balance' => $newSourceBal,
                    'quantity_type' => 'credit',
                    'type' => 'Outlet',
                    'item_type' => 'SKU',
                    'created' => date('Y-m-d H:i:s')
                ]);
    
                // DEST REVERSE DEBIT
                $destPrev = $this->Stock_ledger->find('first', [
                    'conditions' => [
                        'sku_id' => $sku_id,
                        'store_id' => $oldTransfer['Stock_transfer']['transfer_to'],
                        'type' => 'Outlet',
                        'item_type' => 'SKU'
                    ],
                    'fields' => ['actual_balance'],
                    'order' => ['id DESC']
                ]);
    
                $destBal = $destPrev['Stock_ledger']['actual_balance'] ?? 0;
                $newDestBal = $destBal - $qty;
    
                $this->Stock_ledger->create();
                $this->Stock_ledger->save([
                    'store_id' => $oldTransfer['Stock_transfer']['transfer_to'],
                    'sku_id' => $sku_id,
                    'sku_name' => $sku_name,
                    'sku_price' => $price,
                    'unit_id' => $unit_id,
                    'unit_name' => $unit_nm,
                    'record_id' => $transfer_id,
                    'record_type' => 'stock_transfer_reverse_out',
                    'quantity' => $qty,
                    'balance_stock' => $newDestBal,
                    'actual_balance' => $newDestBal,
                    'quantity_type' => 'debit',
                    'type' => 'Outlet',
                    'item_type' => 'SKU',
                    'created' => date('Y-m-d H:i:s')
                ]);
            }
    
            /*
            ==========================
            SOFT DELETE OLD HEADER + ITEMS
            ==========================
            */
            $this->Stock_transfer_item->updateAll(
                ['status' => 0],
                ['transfer_id' => $transfer_id, 'status' => 1]
            );
    
            $this->Stock_transfer->updateAll(
                ['status' => 0],
                ['transfer_id' => $transfer_id, 'status' => 1]
            );
    
            /*
            ==========================
            INSERT NEW HEADER
            ==========================
            */
            $created_by      = $this->Session->read('Auth.Location.id');
            $created_by_name = $this->Session->read('Auth.Location.name');
    
            $this->Stock_transfer->create();
            $this->Stock_transfer->save([
                'transfer_id'        => $transfer_id,
                'transfer_from'      => $transfer_from,
                'transfer_from_name' => $transfer_from_name,
                'transfer_to'        => $transfer_to,
                'transfer_to_name'   => $transfer_to_name,
                'created_by'         => $created_by,
                'created_by_name'    => $created_by_name,
                'status'             => 1,
                'sync'               => 0,
                'local_sync'         => 0,
                'created'            => date('Y-m-d H:i:s')
            ]);
    
            $newTransferId = $this->Stock_transfer->id;
    
            $totalTax = 0;
            $totalAmount = 0;
    
            /*
            ==========================
            SAVE NEW ITEMS + LEDGER
            ==========================
            */
            foreach ($checkeddatas as $checkeddata) {
                $skuid      = $checkeddata['id'];
                $price      = $checkeddata['price'];
                $sku_name   = $checkeddata['sku_name'];
                $sku_brand  = $checkeddata['sku_brand'];
                $unit_name  = $checkeddata['unit_name'];
                $unit_id    = $checkeddata['unit_id'];
                $quantity   = $checkeddata['sku_quantity'];
                $taxPercent = $checkeddata['tax_percentage'];
    
                // CALCULATE TAX/TOTAL
                $baseAmount  = $price * $quantity;
                $taxValue    = ($baseAmount * $taxPercent) / 100;
                $finalAmount = $baseAmount + $taxValue;
    
                // SAVE ITEM
                $this->Stock_transfer_item->create();
                $this->Stock_transfer_item->save([
                    'transfer_id'   => $transfer_id,
                    'sku_id'        => $skuid,
                    'sku_name'      => $sku_name,
                    'sku_brand'     => $sku_brand,
                    'sku_unit'      => $unit_id,
                    'sku_unit_name' => $unit_name,
                    'sku_price'     => $price,
                    'sku_quantity'  => $quantity,
                    'tax_percent'   => $taxPercent,
                    'tax_value'     => $taxValue,
                    'amount'        => $finalAmount,
                    'status'        => 1,
                    'sync'          => 0,
                    'local_sync'    => 0
                ]);
    
                $totalTax += $taxValue;
                $totalAmount += $finalAmount;
    
                // SOURCE LEDGER DEBIT
                $sourcePrev = $this->Stock_ledger->find('first', [
                    'conditions' => [
                        'sku_id' => $skuid,
                        'store_id' => $transfer_from,
                        'type' => 'Outlet',
                        'item_type' => 'SKU'
                    ],
                    'fields' => ['actual_balance'],
                    'order' => ['id DESC']
                ]);
    
                $newSourceBal = ($sourcePrev['Stock_ledger']['actual_balance'] ?? 0) - $quantity;
    
                $this->Stock_ledger->create();
                $this->Stock_ledger->save([
                    'store_id' => $transfer_from,
                    'sku_id' => $skuid,
                    'sku_name' => $sku_name,
                    'sku_price' => $price,
                    'unit_id' => $unit_id,
                    'unit_name' => $unit_name,
                    'record_id' => $transfer_id,
                    'record_type' => 'stock_transfer_out',
                    'quantity' => $quantity,
                    'balance_stock' => $newSourceBal,
                    'actual_balance' => $newSourceBal,
                    'quantity_type' => 'debit',
                    'type' => 'Outlet',
                    'item_type' => 'SKU',
                    'created' => date('Y-m-d H:i:s')
                ]);
    
                // DESTINATION LEDGER CREDIT
                $destPrev = $this->Stock_ledger->find('first', [
                    'conditions' => [
                        'sku_id' => $skuid,
                        'store_id' => $transfer_to,
                        'type' => 'Outlet',
                        'item_type' => 'SKU'
                    ],
                    'fields' => ['actual_balance'],
                    'order' => ['id DESC']
                ]);
    
                $newDestBal = ($destPrev['Stock_ledger']['actual_balance'] ?? 0) + $quantity;
    
                $this->Stock_ledger->create();
                $this->Stock_ledger->save([
                    'store_id' => $transfer_to,
                    'sku_id' => $skuid,
                    'sku_name' => $sku_name,
                    'sku_price' => $price,
                    'unit_id' => $unit_id,
                    'unit_name' => $unit_name,
                    'record_id' => $transfer_id,
                    'record_type' => 'stock_transfer_in',
                    'quantity' => $quantity,
                    'balance_stock' => $newDestBal,
                    'actual_balance' => $newDestBal,
                    'quantity_type' => 'credit',
                    'type' => 'Outlet',
                    'item_type' => 'SKU',
                    'created' => date('Y-m-d H:i:s')
                ]);
            }
    
            // UPDATE TOTALS ON NEW HEADER
            $this->Stock_transfer->id = $newTransferId;
            $this->Stock_transfer->save([
                'tax' => $totalTax,
                'amount' => $totalAmount
            ]);
    
            $datasource->commit();
    
            echo json_encode([
                'status' => 1,
                'message' => 'Stock Transfer Updated Successfully'
            ]);
    
        } catch (Exception $e) {
    
            $datasource->rollback();
    
            echo json_encode([
                'status' => 0,
                'message' => $e->getMessage()
            ]);
        }
    }
    
    public function return_to_warehouse()
    {
        if (!$this->Session->check('Auth.User.id')) {
            $this->redirect([
                'controller' => 'dashboard',
                'action' => 'login'
            ]);
        }
    
        $this->layout = "users";
    
        $startDate = date('m/d/Y', strtotime('-12 months'));
        $endDate   = date('m/d/Y');
    
        $location_id = $this->Session->read('Auth.Location.id');
    
        if ($this->request->is('post', 'put')) {
    
            $daterange = $this->request->data['daterange'];
    
            $dates = explode(" - ", $daterange);
    
            $start_date = trim($dates[0]);
            $end_date   = trim($dates[1]);
    
            $startDate = date('m/d/Y', strtotime($start_date));
            $endDate   = date('m/d/Y', strtotime($end_date));
    
            $startdate = date('Y-m-d', strtotime($start_date));
            $enddate   = date('Y-m-d', strtotime($end_date));
    
        } else {
    
            $startdate = date('Y-m-d', strtotime($startDate));
            $enddate   = date('Y-m-d', strtotime($endDate));
        }
    
        $startDatetime = $startdate . ' 00:00:01';
        $endDatetime   = date('Y-m-d', strtotime($enddate . ' +1 day')) . ' 23:59:59';
    
    
        $users = $this->Inventory_return->query("
            SELECT * 
            FROM inventory_return 
            WHERE status = 1
            AND created BETWEEN '$startDatetime' AND '$endDatetime'
            AND location_id = $location_id
            ORDER BY id DESC
        ");
    
        $yourStartDateValue = $startDate . ' - ' . $endDate;
    
        $this->set('startdate', $yourStartDateValue);
    
        $this->set(compact('users'));
    }
    
    public function create_return_to_warehouse()
    {
        if (!$this->Session->check('Auth.User.id')) {
            return $this->redirect(['controller'=>'dashboard','action'=>'login']);
        }
    
        $this->layout = "users";
    
        $location_id = $this->Session->read('Auth.Location.id');
    
        $locations = $this->Location->find('all', [
            'conditions' => [
                'status' => 1,
                'type' => 'DC'
            ]
        ]);
    
        $users = $this->Inventory_sku->find('all', [
        
            'fields' => [
                'Inventory_sku.*',
                'COALESCE(SUM(InventorySkuTax.tax_percentage),0) AS total_tax_percentage'
            ],
        
            'joins' => [
        
                [
                    'table' => 'inventory_sku_tax',
                    'alias' => 'InventorySkuTax',
                    'type' => 'LEFT',
                    'conditions' => [
                        'InventorySkuTax.product_id = Inventory_sku.id',
                        'InventorySkuTax.status' => 1
                    ]
                ]
        
            ],
        
            'conditions' => [
                'Inventory_sku.status' => 1
            ],
        
            'group' => ['Inventory_sku.id'],
        
            'order' => ['Inventory_sku.name ASC']
        
        ]);
    
        $this->set(compact('locations', 'users'));
    }
    
    public function add_return_to_warehouse()
    {
        $this->autoRender = false;
    
        if (!empty($_POST['checkedData'])) {
    
            $datasource = $this->Inventory_return->getDataSource();
            $datasource->begin();
    
            try {
    
                $checkeddatas   = json_decode($_POST['checkedData'], true);
    
                $transfer_to    = $_POST['transferto'];
                $remarks    = $_POST['remarks'];
                
                $transfer_from = $created_by     = $this->Session->read('Auth.Location.id');
                $created_by_name     = $this->Session->read('Auth.Location.name');
    
    
                /*
                ==========================
                GET LOCATION NAMES
                ==========================
                */
                
                $toLocation = $this->Location->find('first', [
                    'conditions' => ['id' => $transfer_to,'status'=>1],
                    'fields' => ['name'],
                    'recursive' => -1
                ]);
    
                $transfer_to_name   = !empty($toLocation['Location']['name']) ? $toLocation['Location']['name'] : '';
    
                /*
                ==========================
                GENERATE UNIQUE TRANSFER ID
                ==========================
                */
                do {
                    $txnid = 'RTW' . time() . rand(100,999);
    
                    $existing = $this->Inventory_return->find('first', [
                        'conditions' => ['return_id' => $txnid]
                    ]);
    
                } while (!empty($existing));
    
            
                /*
                ==========================
                SAVE MAIN TRANSFER
                ==========================
                */
                $transferData = [
                    'return_id'        => $txnid,
                    'location_id'      => $created_by,
                    'location_name'    => $created_by_name,
                    'warehouse_id'     => $transfer_to,
                    'warehouse_name'   => $transfer_to_name,
                    'created_by'         => $created_by,
                    'created_by_name'    => $created_by_name,
                    'remarks'            => $remarks,
                    'status'             => 1,
                    'sync'               => 0,
                    'local_sync'         => 0
                ];
    
                $this->Inventory_return->create();
    
                if (!$this->Inventory_return->save($transferData)) {
                    throw new Exception('Return header not saved.');
                }
                
                $totalAmount = 0;
    
                /*
                ==========================
                LOOP ITEMS
                ==========================
                */
                
                foreach ($checkeddatas as $checkeddata) {

                    $skuid      = $checkeddata['id'];
                    $price      = $checkeddata['price'];
                    $sku_name   = $checkeddata['sku_name'];
                    $sku_brand  = $checkeddata['sku_brand'];
                    $unit_name  = $checkeddata['unit_name'];
                    $unit_id    = $checkeddata['unit_id'];
                    $quantity   = $checkeddata['sku_quantity'];
                    $taxPercent = $checkeddata['tax_percentage'];
                
                    /*
                    ==========================
                    CALCULATIONS
                    ==========================
                    */
                    $baseAmount = $price * $quantity;
                
                    $taxValue = ($baseAmount * $taxPercent) / 100;
                
                    $finalAmount = $baseAmount + $taxValue;
                
                    /*
                    ==========================
                    SAVE TRANSFER ITEM
                    ==========================
                    */
                    
                    $itemData = [
                        'return_id'     => $txnid,
                        'sku_id'          => $skuid,
                        'sku_name'        => $sku_name,
                        'sku_brand'       => $sku_brand,
                        'sku_unit'        => $unit_id,
                        'sku_unit_name'   => $unit_name,
                        'sku_price'       => $price,
                        'sku_quantity'    => $quantity,
                        'tax_percent'     => $taxPercent,
                        'tax_value'       => round($taxValue,2),
                        'amount'          => round($finalAmount,2),
                        'sync'            => 0,
                        'local_sync'      => 0
                    ];
    
                    $this->Inventory_return_items->create();
    
                    if (!$this->Inventory_return_items->save($itemData)) {
                        throw new Exception('Return item save failed.');
                    }
                    
                    $totalAmount += $finalAmount;
                }
                
                /*
                ==========================
                UPDATE HEADER TAX/AMOUNT
                ==========================
                */
                $this->Inventory_return->id = $this->Inventory_return->getLastInsertID();
                
                if (!$this->Inventory_return->save([
                    'total_amount' => round($totalAmount,2)
                ])) {
                    throw new Exception('Failed to update return totals.');
                }
                /*
                ==========================
                COMMIT TRANSACTION
                ==========================
                */
                $datasource->commit();
    
                echo json_encode([
                    'response_code' => 1,
                    'data' => 'Return Saved Successfully'
                ]);
    
            } catch (Exception $e) {
    
                $datasource->rollback();
    
                echo json_encode([
                    'response_code' => 0,
                    'data' => $e->getMessage()
                ]);
            }
        }
    }
    
    public function view_return_to_warehouse()
    {
        $this->layout = "users";
    
        $id = $_GET['id'];
    
        $transfer = $this->Inventory_return->find('first', [
            'conditions' => [
                'return_id' => $id,
                'status' => 1
            ]
        ]);
    
        $items = $this->Inventory_return_items->find('all', [
            'conditions' => [
                'return_id' => $id,
                'status' => 1
            ]
        ]);
    
        $this->set(compact('transfer', 'items'));
    }
    
    public function return_to_warehouse_pdf()
    {
        if (!$this->Session->check('Auth.User.id')) {
            return $this->redirect(['action' => 'login']);
        }
    
        $this->layout = "login";
    
        if ($this->request->is(['post'])) {
    
            $return_id = $this->request->data['return_id'];
    
            $transfer = $this->Inventory_return->find('first', [
                'conditions' => [
                    'return_id' => $return_id,
                    'status' => 1
                ]
            ]);
    
            $items = $this->Inventory_return_items->find('all', [
                'conditions' => [
                    'return_id' => $return_id,
                    'status' => 1
                ]
            ]);
    
            $locationdetail = $this->Location->find('first', [
                'conditions' => [
                    'Location.id' => $transfer['Inventory_return']['location_id']
                ]
            ]);
    
            $this->set(compact('transfer', 'items', 'locationdetail'));
    
            $this->render('return_to_warehouse_pdf');
        }
    }
    
    public function edit_return_to_warehouse()
    {
        if (!$this->Session->check('Auth.Location.id')) {
            return $this->redirect([
                'controller' => 'dashboard',
                'action' => 'login'
            ]);
        }
    
        $this->layout = "users";
    
        $return_id = $this->request->query('id');
    
        $location_id = $this->Session->read('Auth.Location.id');
    
    
        /*
        ==========================
        GET RETURN HEADER
        ==========================
        */
        $transfer = $this->Inventory_return->find('first', [
            'conditions' => [
                'return_id' => $return_id,
                'status' => 1
            ]
        ]);
    
    
        /*
        ==========================
        GET RETURN ITEMS
        ==========================
        */
        $transferItems = $this->Inventory_return_items->find('all', [
            'conditions' => [
                'return_id' => $return_id,
                'status' => 1
            ]
        ]);
    
    
        /*
        ==========================
        GET WAREHOUSES
        ==========================
        */
        $locations = $this->Location->find('all', [
            'conditions' => [
                'status' => 1,
                'type' => 'DC'
            ]
        ]);
    
    
        /*
        ==========================
        GET SKU LIST
        ==========================
        */
        $users = $this->Inventory_sku->find('all', [
    
            'fields' => [
                'Inventory_sku.*',
                'COALESCE(SUM(InventorySkuTax.tax_percentage),0) AS total_tax_percentage'
            ],
    
            'joins' => [
    
                [
                    'table' => 'inventory_sku_tax',
                    'alias' => 'InventorySkuTax',
                    'type' => 'LEFT',
                    'conditions' => [
                        'InventorySkuTax.product_id = Inventory_sku.id',
                        'InventorySkuTax.status' => 1
                    ]
                ]
    
            ],
    
            'conditions' => [
                'Inventory_sku.status' => 1
            ],
    
            'group' => ['Inventory_sku.id'],
    
            'order' => ['Inventory_sku.name ASC']
    
        ]);
    
    
        $this->set(compact(
            'transfer',
            'transferItems',
            'locations',
            'users'
        ));
    }
    
    public function update_return_to_warehouse()
    {
        $this->autoRender = false;
    
        $datasource = $this->Inventory_return->getDataSource();
        $datasource->begin();
    
        try {
    
            $return_id   = $this->request->data['return_id'];
            $warehouse_id = $this->request->data['transferto'];
            $remarks      = $this->request->data['remarks'];
    
            $checkeddatas = json_decode($this->request->data['checkedData'], true);
    
            $location_id   = $this->Session->read('Auth.Location.id');
            $location_name = $this->Session->read('Auth.Location.name');
    
    
            /*
            ==========================
            GET WAREHOUSE NAME
            ==========================
            */
            $warehouse = $this->Location->find('first', [
                'conditions' => [
                    'id' => $warehouse_id,
                    'status' => 1
                ],
                'fields' => ['name'],
                'recursive' => -1
            ]);
    
            $warehouse_name = $warehouse['Location']['name'] ?? '';
    
    
            /*
            ==========================
            GET OLD RETURN + ITEMS
            ==========================
            */
            $oldReturn = $this->Inventory_return->find('first', [
                'conditions' => [
                    'return_id' => $return_id,
                    'status' => 1
                ]
            ]);
            
            if($oldReturn['Inventory_return']['admin_status'] != 0){
                throw new Exception('Cannot edit after approval/rejection');
            }
    
            $oldItems = $this->Inventory_return_items->find('all', [
                'conditions' => [
                    'return_id' => $return_id,
                    'status' => 1
                ]
            ]);
    
            /*
            ==========================
            SOFT DELETE OLD RECORDS
            ==========================
            */
            $this->Inventory_return->updateAll(
                ['status' => 0],
                ['return_id' => $return_id]
            );
    
            $this->Inventory_return_items->updateAll(
                ['status' => 0],
                ['return_id' => $return_id]
            );
            
    
            /*
            ==========================
            INSERT NEW HEADER
            ==========================
            */
            $this->Inventory_return->create();
    
            $this->Inventory_return->save([
                'return_id'        => $return_id,
                'location_id'      => $location_id,
                'location_name'    => $location_name,
                'warehouse_id'     => $warehouse_id,
                'warehouse_name'   => $warehouse_name,
                'created_by'       => $location_id,
                'created_by_name'  => $location_name,
                'remarks'          => $remarks,
                'status'           => 1,
                'sync'             => 0,
                'local_sync'       => 0,
                'created'          => date('Y-m-d H:i:s')
            ]);
    
    
            $totalAmount = 0;
    
    
            /*
            ==========================
            SAVE NEW ITEMS + LEDGER
            ==========================
            */
            foreach ($checkeddatas as $checkeddata) {
    
                $skuid      = $checkeddata['id'];
                $price      = $checkeddata['price'];
                $sku_name   = $checkeddata['sku_name'];
                $sku_brand  = $checkeddata['sku_brand'];
                $unit_name  = $checkeddata['unit_name'];
                $unit_id    = $checkeddata['unit_id'];
                $quantity   = $checkeddata['sku_quantity'];
                $taxPercent = $checkeddata['tax_percentage'];
    
                $baseAmount = $price * $quantity;
                $taxValue   = ($baseAmount * $taxPercent) / 100;
                $finalAmount = $baseAmount + $taxValue;
    
                /*
                SAVE ITEM
                */
                $this->Inventory_return_items->create();
    
                $this->Inventory_return_items->save([
                    'return_id'       => $return_id,
                    'sku_id'          => $skuid,
                    'sku_name'        => $sku_name,
                    'sku_brand'       => $sku_brand,
                    'sku_unit'        => $unit_id,
                    'sku_unit_name'   => $unit_name,
                    'sku_price'       => $price,
                    'sku_quantity'    => $quantity,
                    'tax_percent'     => $taxPercent,
                    'tax_value'       => round($taxValue,2),
                    'amount'          => round($finalAmount,2),
                    'sync'            => 0,
                    'local_sync'      => 0
                ]);
    
                $totalAmount += $finalAmount;
            }
    
    
            /*
            ==========================
            UPDATE TOTAL AMOUNT
            ==========================
            */
            $this->Inventory_return->id = $this->Inventory_return->getLastInsertID();
    
            $this->Inventory_return->save([
                'total_amount' => round($totalAmount,2)
            ]);
    
    
            $datasource->commit();
    
            echo json_encode([
                'response_code' => 1,
                'data' => 'Return Updated Successfully'
            ]);
    
        } catch (Exception $e) {
    
            $datasource->rollback();
    
            echo json_encode([
                'response_code' => 0,
                'data' => $e->getMessage()
            ]);
        }
    }


// ════════════════════════════════════════════════════════════════════════
    // Direct Stockin
    // Lets a stockin user receive material directly — auto-creates Indent,
    // PO and Stockin records with identical ledger entries to normal flow.
    // ════════════════════════════════════════════════════════════════════════

    // public function direct_stockin() {
    //     if (!$this->Session->check('Auth.User.id')) {
    //         return $this->redirect(['controller' => 'dashboard', 'action' => 'login']);
    //     }
    //     $this->layout = "users";

    //     $authUser        = $this->Session->read('Auth.User');
    //     $userId          = $authUser['id'];
    //     $userName        = $authUser['name'];
    //     $locationIdsRaw  = $authUser['location_ids'] ?? '';
    //     // $locationIdSingle= $authUser['id']  ?? '';
    //     // Use location_id (not user id) as the primary location identifier
    //     $locationIdSingle= $authUser['location_id'] ?? $authUser['id'] ?? '';

    //     $locationName = $authUser['name'] ?? '';

    //     $locationIdsArray = !empty($locationIdsRaw)
    //         ? array_map('trim', is_array($locationIdsRaw) ? $locationIdsRaw : explode(',', $locationIdsRaw))
    //         : [];
    //     if (!empty($locationIdSingle)) {
    //         $locationIdsArray[] = trim($locationIdSingle);
    //     }
    //     $userLocationIds  = array_values(array_unique(array_filter($locationIdsArray)));
    //     $primaryLocationId = !empty($userLocationIds) ? intval($userLocationIds[0]) : 0;

    //     // Vendors serving this outlet location
    //     $vendorRows = $this->Inventory_vendor->query("
    //         SELECT id, name, company FROM inventory_vendors
    //         WHERE status = 1
    //           AND id IN (
    //             SELECT vendor_id FROM inventory_vendor_service_areas
    //             WHERE status = 1 AND is_deleted = 0 AND area_id = $primaryLocationId
    //           )
    //         ORDER BY COALESCE(NULLIF(company,''), name)
    //     ");

    //     // AJAX: return SKUs for selected vendor as JSON
    //     if ($this->request->is('post') && !empty($this->request->data['vendor_id'])) {
    //         $this->autoRender = false;
    //         $vid = intval($this->request->data['vendor_id']);

    //         // $skuRows = $this->Inventory_vendor_sku_served->query("
    //         //     SELECT vss.skus_id AS sku_id, vss.sku_name, vss.sku_unit,
    //         //           vss.sku_unit_name, vss.sku_price,
    //         //           COALESCE(
    //         //               (SELECT SUM(it.tax_percentage)
    //         //                 FROM inventory_sku_tax it
    //         //                 WHERE it.product_id = vss.skus_id AND it.status = 1),
    //         //           0) AS tax_pct
    //         //     FROM inventory_vendor_sku_served vss
    //         //     WHERE vss.vendor_id = $vid AND vss.status = 1
    //         //     ORDER BY vss.sku_name
    //         // ");
            
    //         $skuRows = $this->Inventory_vendor_sku_served->query("
    //             SELECT vss.skus_id AS sku_id, vss.sku_name, vss.sku_unit,
    //                   vss.sku_unit_name, vss.sku_price,
    //                   sk.excise_applicable, sk.has_variable_tax,
    //                   COALESCE(
    //                       (SELECT SUM(it.tax_percentage)
    //                         FROM inventory_sku_tax it
    //                         WHERE it.product_id = vss.skus_id AND it.status = 1),
    //                   0) AS tax_pct,
    //                   COALESCE(ise_loc.excise_per_unit,      ise_base.excise_per_unit,      0) AS excise_per_unit,
    //                   COALESCE(ise_loc.addl_excise_per_unit, ise_base.addl_excise_per_unit, 0) AS addl_excise_per_unit,
    //                   COALESCE(ise_loc.vat_percentage,       ise_base.vat_percentage,       0) AS vat_percentage,
    //                   COALESCE(ise_loc.tcs_percentage,       ise_base.tcs_percentage,       0) AS tcs_percentage
    //             FROM inventory_vendor_sku_served vss
    //             LEFT JOIN inventory_skus sk ON sk.id = vss.skus_id
    //             LEFT JOIN inventory_sku_excise ise_loc
    //                   ON ise_loc.sku_id = vss.skus_id AND ise_loc.location_id = $primaryLocationId AND ise_loc.status = 1
    //             LEFT JOIN inventory_sku_excise ise_base
    //                   ON ise_base.sku_id = vss.skus_id AND ise_base.location_id = 0 AND ise_base.status = 1
    //             WHERE vss.vendor_id = $vid AND vss.status = 1
    //             ORDER BY vss.sku_name
    //         ");

    //         $skus = [];
    //         foreach ($skuRows as $r) {
    //             $d  = $r['inventory_vendor_sku_served'] ?? $r['vss'] ?? $r[0];
    //             $sk = $r['inventory_skus'] ?? $r['sk'] ?? [];

    //             // Computed subquery columns (tax_pct) land in $r[0] in CakePHP raw query results
    //             $r0 = $r[0] ?? [];
                
    //             $taxPct    = floatval($r0['tax_pct']             ?? $d['tax_pct']             ?? 0);
    //             $excisePu  = floatval($r0['excise_per_unit']     ?? 0);
    //             $addlExPu  = floatval($r0['addl_excise_per_unit']?? 0);
    //             $vatPct    = floatval($r0['vat_percentage']      ?? 0);
    //             $tcsPct    = floatval($r0['tcs_percentage']      ?? 0);
    //             $basePrice = floatval($d['sku_price'] ?? $r0['sku_price'] ?? 0);
    //             // VAT base = base + excise + addl_excise
    //             $taxBasePu = $basePrice + $excisePu + $addlExPu;
    //             $vatPu     = round($taxBasePu * ($vatPct / 100), 4);
    //             // Landed = base + excise_only + VAT (addl_excise excluded from landed)
    //             $landedPu  = round($basePrice + $excisePu + $vatPu, 4);

    //             // $taxPct = floatval($r0['tax_pct'] ?? $d['tax_pct'] ?? 0);
    //             // $skus[] = [
    //             //     'sku_id'       => intval($d['sku_id'] ?? $r0['sku_id'] ?? 0),
    //             //     'sku_name'     => $d['sku_name'] ?? $r0['sku_name'] ?? '',
    //             //     'sku_unit'     => intval($d['sku_unit'] ?? $r0['sku_unit'] ?? 0),
    //             //     'sku_unit_name'=> $d['sku_unit_name'] ?? $r0['sku_unit_name'] ?? '',
    //             //     'sku_price'    => floatval($d['sku_price'] ?? $r0['sku_price'] ?? 0),
    //             //     'tax_pct'      => $taxPct,
    //             // ];
                
    //             $skus[] = [
    //                 'sku_id'              => intval($d['sku_id'] ?? $r0['sku_id'] ?? 0),
    //                 'sku_name'            => $d['sku_name'] ?? $r0['sku_name'] ?? '',
    //                 'sku_unit'            => intval($d['sku_unit'] ?? $r0['sku_unit'] ?? 0),
    //                 'sku_unit_name'       => $d['sku_unit_name'] ?? $r0['sku_unit_name'] ?? '',
    //                 'sku_price'           => $basePrice,
    //                 'tax_pct'             => ($excisePu > 0 || $vatPct > 0) ? 0 : $taxPct,
    //                 'excise_applicable'   => !empty($sk['excise_applicable'] ?? $r0['excise_applicable'] ?? 0),
    //                 'has_variable_tax'    => !empty($sk['has_variable_tax']  ?? $r0['has_variable_tax']  ?? 0),
    //                 'excise_per_unit'     => $excisePu,
    //                 'addl_excise_per_unit'=> $addlExPu,
    //                 'vat_percentage'      => $vatPct,
    //                 'tcs_percentage'      => $tcsPct,
    //                 'landed_price'        => $landedPu,
    //             ];
    //         }
    //         header('Content-Type: application/json');
    //         echo json_encode(['success' => true, 'skus' => $skus]);
    //         return;
    //     }

    //     $this->set(compact('vendorRows', 'locationName', 'primaryLocationId', 'userName'));
    // }
    
    public function direct_stockin() {
        if (!$this->Session->check('Auth.User.id')) {
            return $this->redirect(['controller' => 'dashboard', 'action' => 'login']);
        }
        $this->layout = "users";

        $authUser        = $this->Session->read('Auth.User');
        $userId          = $authUser['id'];
        $userName        = $authUser['name'];
        $locationIdsRaw  = $authUser['location_ids'] ?? '';
        // $locationIdSingle= $authUser['id']  ?? '';
        // Use location_id (not user id) as the primary location identifier
        $locationIdSingle= $authUser['location_id'] ?? $authUser['id'] ?? '';

        $locationName = $authUser['name'] ?? '';

        $locationIdsArray = !empty($locationIdsRaw)
            ? array_map('trim', is_array($locationIdsRaw) ? $locationIdsRaw : explode(',', $locationIdsRaw))
            : [];
        if (!empty($locationIdSingle)) {
            $locationIdsArray[] = trim($locationIdSingle);
        }
        $userLocationIds  = array_values(array_unique(array_filter($locationIdsArray)));
        $primaryLocationId = !empty($userLocationIds) ? intval($userLocationIds[0]) : 0;

        // Vendors serving this outlet location
        $vendorRows = $this->Inventory_vendor->query("
            SELECT id, name, company FROM inventory_vendors
            WHERE status = 1
              AND id IN (
                SELECT vendor_id FROM inventory_vendor_service_areas
                WHERE status = 1 AND is_deleted = 0 AND area_id = $primaryLocationId
              )
            ORDER BY COALESCE(NULLIF(company,''), name)
        ");

        // AJAX: return SKUs for selected vendor as JSON
        if ($this->request->is('post') && !empty($this->request->data['vendor_id'])) {
            $this->autoRender = false;
            $vid = intval($this->request->data['vendor_id']);

            // $skuRows = $this->Inventory_vendor_sku_served->query("
            //     SELECT vss.skus_id AS sku_id, vss.sku_name, vss.sku_unit,
            //           vss.sku_unit_name, vss.sku_price,
            //           COALESCE(
            //               (SELECT SUM(it.tax_percentage)
            //                 FROM inventory_sku_tax it
            //                 WHERE it.product_id = vss.skus_id AND it.status = 1),
            //           0) AS tax_pct
            //     FROM inventory_vendor_sku_served vss
            //     WHERE vss.vendor_id = $vid AND vss.status = 1
            //     ORDER BY vss.sku_name
            // ");
            
            $skuRows = $this->Inventory_vendor_sku_served->query("
                SELECT vss.skus_id AS sku_id, vss.sku_name, vss.sku_unit,
                       vss.sku_unit_name, vss.sku_price,
                       sk.excise_applicable, sk.has_variable_tax,
                       COALESCE(
                           (SELECT SUM(it.tax_percentage)
                            FROM inventory_sku_tax it
                            WHERE it.product_id = vss.skus_id AND it.status = 1),
                       0) AS tax_pct,
                       COALESCE(ise_loc.excise_per_unit,      ise_base.excise_per_unit,      0) AS excise_per_unit,
                       COALESCE(ise_loc.addl_excise_per_unit, ise_base.addl_excise_per_unit, 0) AS addl_excise_per_unit,
                       COALESCE(ise_loc.vat_percentage,       ise_base.vat_percentage,       0) AS vat_percentage,
                       COALESCE(ise_loc.tcs_percentage,       ise_base.tcs_percentage,       0) AS tcs_percentage
                FROM inventory_vendor_sku_served vss
                LEFT JOIN inventory_skus sk ON sk.id = vss.skus_id
                LEFT JOIN (
                    SELECT sku_id,
                           MAX(excise_per_unit) AS excise_per_unit,
                           MAX(addl_excise_per_unit) AS addl_excise_per_unit,
                           MAX(vat_percentage) AS vat_percentage,
                           MAX(tcs_percentage) AS tcs_percentage
                    FROM inventory_sku_excise
                    WHERE status = 1 AND location_id = $primaryLocationId
                    GROUP BY sku_id
                ) ise_loc ON ise_loc.sku_id = vss.skus_id
                LEFT JOIN (
                    SELECT sku_id,
                           MAX(excise_per_unit) AS excise_per_unit,
                           MAX(addl_excise_per_unit) AS addl_excise_per_unit,
                           MAX(vat_percentage) AS vat_percentage,
                           MAX(tcs_percentage) AS tcs_percentage
                    FROM inventory_sku_excise
                    WHERE status = 1 AND location_id = 0
                    GROUP BY sku_id
                ) ise_base ON ise_base.sku_id = vss.skus_id
                WHERE vss.vendor_id = $vid AND vss.status = 1
                ORDER BY vss.sku_name
            ");

            $skus = [];
            $seenSkuIds = [];
            foreach ($skuRows as $r) {
                $d  = $r['inventory_vendor_sku_served'] ?? $r['vss'] ?? $r[0];
                $sk = $r['inventory_skus'] ?? $r['sk'] ?? [];

                // Computed subquery columns (tax_pct) land in $r[0] in CakePHP raw query results
                $r0 = $r[0] ?? [];
                
                $skuId = intval($d['sku_id'] ?? $r0['sku_id'] ?? 0);
                if ($skuId > 0) {
                    if (isset($seenSkuIds[$skuId])) {
                        continue;
                    }
                    $seenSkuIds[$skuId] = true;
                }

                $taxPct    = floatval($r0['tax_pct']             ?? $d['tax_pct']             ?? 0);
                $excisePu  = floatval($r0['excise_per_unit']     ?? 0);
                $addlExPu  = floatval($r0['addl_excise_per_unit']?? 0);
                $vatPct    = floatval($r0['vat_percentage']      ?? 0);
                $tcsPct    = floatval($r0['tcs_percentage']      ?? 0);
                $basePrice = floatval($d['sku_price'] ?? $r0['sku_price'] ?? 0);
                // VAT base = base + excise + addl_excise
                $taxBasePu = $basePrice + $excisePu + $addlExPu;
                $vatPu     = round($taxBasePu * ($vatPct / 100), 4);
                // Landed = base + excise_only + VAT (addl_excise excluded from landed)
                $landedPu  = round($basePrice + $excisePu + $vatPu, 4);

                $skus[] = [
                    'sku_id'              => $skuId,
                    'sku_name'            => $d['sku_name'] ?? $r0['sku_name'] ?? '',
                    'sku_unit'            => intval($d['sku_unit'] ?? $r0['sku_unit'] ?? 0),
                    'sku_unit_name'       => $d['sku_unit_name'] ?? $r0['sku_unit_name'] ?? '',
                    'sku_price'           => $basePrice,
                    'tax_pct'             => ($excisePu > 0 || $vatPct > 0) ? 0 : $taxPct,
                    'excise_applicable'   => !empty($sk['excise_applicable'] ?? $r0['excise_applicable'] ?? 0),
                    'has_variable_tax'    => !empty($sk['has_variable_tax']  ?? $r0['has_variable_tax']  ?? 0),
                    'excise_per_unit'     => $excisePu,
                    'addl_excise_per_unit'=> $addlExPu,
                    'vat_percentage'      => $vatPct,
                    'tcs_percentage'      => $tcsPct,
                    'landed_price'        => $landedPu,
                ];
            }
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'skus' => $skus]);
            return;
        }

        $this->set(compact('vendorRows', 'locationName', 'primaryLocationId', 'userName'));
    }

    public function submit_direct_stockin() {
        $this->autoRender = false;
        if (!$this->Session->check('Auth.User.id')) {
            echo json_encode(['success' => false, 'error' => 'Not authenticated']);
            return;
        }

        if (!$this->request->is('post')) {
            echo json_encode(['success' => false, 'error' => 'Invalid request']);
            return;
        }

        $authUser        = $this->Session->read('Auth.User');
        $userId          = intval($authUser['id']);
        $userName        = $authUser['name'];
        $locationIdsRaw  = $authUser['location_ids'] ?? '';
        // $locationIdSingle= $authUser['id']  ?? '';
                // Use location_id (not user id) as the primary location identifier
        $locationIdSingle= $authUser['location_id'] ?? $authUser['id'] ?? '';

        $locationIdsArr  = !empty($locationIdsRaw)
            ? array_map('trim', is_array($locationIdsRaw) ? $locationIdsRaw : explode(',', $locationIdsRaw))
            : [];
        if (!empty($locationIdSingle)) {
            $locationIdsArr[] = trim($locationIdSingle);
        }
        $locationIdsArr    = array_values(array_unique(array_filter($locationIdsArr)));
        $primaryLocationId = !empty($locationIdsArr) ? intval($locationIdsArr[0]) : 0;

        // ── Parse input ───────────────────────────────────────────────────────
        $vendorId        = intval($this->request->data['vendor_id']   ?? 0);
        $invoiceNo       = trim($this->request->data['invoice_no']    ?? '');
        $items           = $this->request->data['items']              ?? [];
        $orderDiscInput  = floatval($this->request->data['order_disc_value'] ?? 0);
        $orderDiscType   = in_array($this->request->data['order_disc_type'] ?? 'flat', ['flat','percentage'])
                           ? $this->request->data['order_disc_type'] : 'flat';
        $stockinDateRaw  = trim($this->request->data['stockin_date'] ?? '');
        $stockinDate     = ($stockinDateRaw && strtotime($stockinDateRaw)) ? $stockinDateRaw : date('Y-m-d');
        $charges         = floatval($this->request->data['charges'] ?? 0);

        // ── Validation ────────────────────────────────────────────────────────
        if (!$vendorId) {
            echo json_encode(['success' => false, 'error' => 'Please select a vendor.']);
            return;
        }
        if ($invoiceNo === '') {
            echo json_encode(['success' => false, 'error' => 'Invoice number is mandatory.']);
            return;
        }
        if (!$primaryLocationId) {
            echo json_encode(['success' => false, 'error' => 'Unable to determine outlet location.']);
            return;
        }

        // Filter items: only those with qty > 0
        $activeItems = [];
        foreach ($items as $item) {
            $qty = floatval($item['qty'] ?? 0);
            if ($qty <= 0) continue;
            // $activeItems[] = [
            //     'sku_id'        => intval($item['sku_id']),
            //     'sku_name'      => trim($item['sku_name']      ?? ''),
            //     'sku_unit'      => intval($item['sku_unit']     ?? 0),
            //     'sku_unit_name' => trim($item['sku_unit_name']  ?? ''),
            //     'sku_price'     => floatval($item['sku_price']  ?? 0),
            //     'tax_pct'       => floatval($item['tax_pct']    ?? 0),
            //     'qty'           => $qty,
            //     'disc_input'    => floatval($item['disc_input'] ?? 0),
            //     'disc_type'     => in_array($item['disc_type'] ?? 'flat', ['flat','percentage'])
            //                         ? $item['disc_type'] : 'flat',
            // ];
            $activeItems[] = [
                'sku_id'              => intval($item['sku_id']),
                'sku_name'            => trim($item['sku_name']             ?? ''),
                'sku_unit'            => intval($item['sku_unit']            ?? 0),
                'sku_unit_name'       => trim($item['sku_unit_name']         ?? ''),
                'sku_price'           => floatval($item['sku_price']         ?? 0),
                'tax_pct'             => floatval($item['tax_pct']           ?? 0),
                'qty'                 => $qty,
                'disc_input'          => floatval($item['disc_input']        ?? 0),
                'disc_type'           => in_array($item['disc_type'] ?? 'flat', ['flat','percentage'])
                                          ? $item['disc_type'] : 'flat',
                'excise_per_unit'     => floatval($item['excise_per_unit']     ?? 0),
                'addl_excise_per_unit'=> floatval($item['addl_excise_per_unit']?? 0),
                'vat_percentage'      => floatval($item['vat_percentage']      ?? 0),
                'tcs_percentage'      => floatval($item['tcs_percentage']      ?? 0),
            ];
        }
        if (empty($activeItems)) {
            echo json_encode(['success' => false, 'error' => 'Please enter quantity for at least one item.']);
            return;
        }

        // Verify vendor exists and serves this location
        $vendorCheck = $this->Inventory_vendor->query("
            SELECT v.id, v.company FROM inventory_vendors v
            JOIN inventory_vendor_service_areas vsa ON vsa.vendor_id = v.id
            WHERE v.id = $vendorId AND v.status = 1
              AND vsa.status = 1 AND vsa.is_deleted = 0
              AND vsa.area_id = $primaryLocationId
            LIMIT 1
        ");
        if (empty($vendorCheck)) {
            echo json_encode(['success' => false, 'error' => 'Vendor not found or not serving this location.']);
            return;
        }
        
        $vendorName = $vendorCheck[0]['inventory_vendors']['company'] ?? $vendorCheck[0]['v']['company'] ?? $vendorCheck[0][0]['company'];

        // Location name
        $locRow = $this->Location->find('first', ['conditions' => ['id' => $primaryLocationId], 'recursive' => -1]);
        $locationName = $locRow ? $locRow['Location']['name'] : $userName;

        $db = $this->Inventory_indent->getDataSource();

        try {
            $db->begin();

            // ── Generate unique IDs ───────────────────────────────────────────
            // Indent ID (11-digit)
            do {
                $indentId = (string)rand(1,9);
                for ($i=0;$i<10;$i++) $indentId .= rand(0,9);
                $ex = $this->Inventory_indent->find('first', ['conditions'=>['indent_id'=>$indentId],'recursive'=>-1]);
            } while ($ex);

            // Stockin ID (11-digit) — generated early, used in all records
            do {
                $stockinId = (string)rand(1,9);
                for ($i=0;$i<10;$i++) $stockinId .= rand(0,9);
                $ex = $this->Inventory_stockin->find('first', ['conditions'=>['stockin_id'=>$stockinId],'recursive'=>-1]);
            } while ($ex);

            // PO ID (10-digit)
            do {
                $poId = (string)rand(1,9);
                for ($i=0;$i<9;$i++) $poId .= rand(0,9);
                $ex = $this->Inventory_purchase_order->find('first', ['conditions'=>['p_o_id'=>$poId,'status !='=>0],'recursive'=>-1]);
            } while ($ex);

            // Consignment ID (10-digit)
            do {
                $consignmentId = (string)rand(1,9);
                for ($i=0;$i<9;$i++) $consignmentId .= rand(0,9);
                $ex = $this->Inventory_indent->find('first', ['conditions'=>['consignment_id'=>$consignmentId],'recursive'=>-1]);
            } while ($ex);

            // Serial number: TGI{year+501}{5-digit-seq}
            $year   = date('Y') + 501;
            $prefix = 'TGI' . $year;
            $latest = $this->Inventory_indent->find('first', [
                'fields'     => ['serial_number'],
                'conditions' => ['Inventory_indent.serial_number LIKE' => $prefix . '%'],
                'order'      => ['Inventory_indent.id' => 'DESC']
            ]);
            $nextNum = 1;
            if (!empty($latest['Inventory_indent']['serial_number'])) {
                $nextNum = intval(substr($latest['Inventory_indent']['serial_number'], strlen($prefix))) + 1;
            }
            $serialNumber = $prefix . str_pad($nextNum, 5, '0', STR_PAD_LEFT);

            $now     = date('Y-m-d H:i:s');
            $today   = date('Y-m-d');
            $expiry  = date('Y-m-d', strtotime('+1 day'));

            // First pass: Calculate item discounts, subtotal, and order discount value
            $subtotal = 0;
            $totalItemDisc = 0;
            $itemDiscounts = [];

            foreach ($activeItems as $item) {
                $qty = $item['qty'];
                $price = $item['sku_price'];
                $baseAmount = $price * $qty;
                if ($item['disc_type'] === 'percentage') {
                    $discInput = min($item['disc_input'], 100);
                    $discValue = round($baseAmount * $discInput / 100, 2);
                } else {
                    $discInput = min($item['disc_input'], $baseAmount);
                    $discValue = round($discInput, 2);
                }
                $subtotal += $baseAmount;
                $totalItemDisc += $discValue;
                $itemDiscounts[$item['sku_id']] = $discValue;
            }

            $subtotalExclTax = $subtotal - $totalItemDisc;
            if ($orderDiscType === 'percentage') {
                $orderDiscValue = round($subtotalExclTax * min($orderDiscInput, 100) / 100, 2);
            } else {
                $orderDiscValue = min($orderDiscInput, $subtotalExclTax);
            }

            // Second pass: Distribute order discount proportionally and calculate taxes
            $computedItems    = [];
            $indentTotal      = 0;
            $sku_stockin_prices = [];

            foreach ($activeItems as $item) {
                $skuId    = $item['sku_id'];
                $qty      = $item['qty'];
                $price    = $item['sku_price'];
                $excisePu = floatval($item['excise_per_unit']      ?? 0);
                $addlExPu = floatval($item['addl_excise_per_unit'] ?? 0);
                $vatPct   = floatval($item['vat_percentage']       ?? 0);
                $tcsPct   = floatval($item['tcs_percentage']       ?? 0);
                $taxPct   = floatval($item['tax_pct'] ?? 0);

                $baseAmount = $price * $qty;
                $discValue = $itemDiscounts[$skuId];
                $afterItemDisc = $baseAmount - $discValue;

                // Distribute order discount proportionally
                $ordItemDisc = 0;
                if ($orderDiscValue > 0 && $subtotalExclTax > 0) {
                    $ordItemDisc = round($orderDiscValue * $afterItemDisc / $subtotalExclTax, 4);
                }
                $afterDisc = $afterItemDisc - $ordItemDisc;

                // Excise/VAT/TCS calculated on discounted base
                $exciseAmt = round($excisePu * $qty, 2);
                $addlExAmt = round($addlExPu * $qty, 2);
                // VAT base = base + excise + addl_excise
                $vatBase   = $afterDisc + $exciseAmt + $addlExAmt;
                $vatAmt    = round($vatBase * ($vatPct / 100), 2);
                // TCS base = base + excise + VAT (addl_excise excluded)
                $tcsAmt    = round(($afterDisc + $exciseAmt + $vatAmt) * ($tcsPct / 100), 2);
                // Landed = base + excise + VAT
                $landedAmt = round($afterDisc + $exciseAmt + $vatAmt, 2);

                // Standard % tax (GST/CGST/SGST, usually 0 for liquor)
                $taxValue  = round($afterDisc * ($taxPct / 100), 2);
                $skuAmount = round($landedAmt + $taxValue + $tcsAmt, 2);

                $unitPriceEffective = ($qty > 0) ? round($landedAmt / $qty, 6) : 0;
                $indentTotal       += $skuAmount;
                
                $computedItems[] = array_merge($item, [
                    'disc_value'         => $discValue,
                    'ord_item_disc'      => $ordItemDisc,
                    'after_item_disc'    => $afterItemDisc,
                    'after_disc'         => $afterDisc,
                    'excise_amt'         => $exciseAmt,
                    'addl_excise_amt'    => $addlExAmt,
                    'vat_amt'            => $vatAmt,
                    'tcs_amt'            => $tcsAmt,
                    'landed_amt'         => $landedAmt,
                    'unit_price_eff'     => $unitPriceEffective,
                    'tax_value'          => $taxValue,
                    'sku_amount'         => $skuAmount,
                ]);
            }

            $grandTotal = round($indentTotal + $charges, 2);
            $indentTotal = $indentTotal + $orderDiscValue; // Save pre-order-discount subtotal in total_amount
            
            // Aggregate tax totals for PO header
            $totalVatDuty  = round(array_sum(array_column($computedItems, 'vat_amt')), 2);
            $totalTcsDuty  = round(array_sum(array_column($computedItems, 'tcs_amt')), 2);
            $totalExcise   = round(array_sum(array_column($computedItems, 'excise_amt')), 2);
            $totalAddlExcise = round(array_sum(array_column($computedItems, 'addl_excise_amt')), 2);

            // ── STEP A: Indent ────────────────────────────────────────────────
            $this->Inventory_indent->create();
            $indentSave = $this->Inventory_indent->save([
                'Inventory_indent' => [
                    'indent_id'        => $indentId,
                    'location_id'      => $primaryLocationId,
                    'location_name'    => $locationName,
                    'warehouse_id'     => $primaryLocationId,
                    'warehouse_name'   => $locationName,
                    'expiry'           => $expiry,
                    'created_by'       => $userId,
                    'created_by_name'  => $locationName,
                    'total_amount'     => $indentTotal,
                    'approved_amount'  => $indentTotal,
                    'processed_amount' => $indentTotal,
                    'stockin_amount'   => $grandTotal,
                    'stockin_id'       => $stockinId,
                    'stockin_date'    => $stockinDate . ' 00:00:00',
                    'serial_number'    => $serialNumber,
                    'invoice_no'       => $invoiceNo,
                    'consignment_id'   => $consignmentId,
                    'approved_selected_date' => $today,
                    'approved_date'    => $now,
                    'processed_date'   => $now,
                    'status'           => 4,
                    'sync'             => 0,
                    'local_sync'       => 0,
                ]
            ]);
            if (!$indentSave) throw new Exception('Failed to create Indent record.');

            // Indent items
            foreach ($computedItems as $ci) {
                $this->Inventory_indent_items->create();
                $r = $this->Inventory_indent_items->save([
                    'Inventory_indent_items' => [
                        'indent_id'                => $indentId,
                        'item_type'                => 'sku',
                        'sku_id'                   => $ci['sku_id'],
                        'sku_name'                 => $ci['sku_name'],
                        'sku_unit'                 => $ci['sku_unit'],
                        'sku_unit_name'            => $ci['sku_unit_name'],
                        'sku_price'                => $ci['sku_price'],
                        'sku_quantity'             => $ci['qty'],
                        'sku_total_tax_percentage' => $ci['tax_pct'],
                        'sku_total_tax_value'      => $ci['tax_value'],
                        'sku_amount'               => $ci['sku_amount'],
                        'approved_price'           => $ci['sku_price'],
                        'approved_quantity'        => $ci['qty'],
                        'approved_tax_value'       => $ci['tax_value'],
                        'approved_amount'          => $ci['sku_amount'],
                        'processed_quantity'       => $ci['qty'],
                        'processed_tax_value'      => $ci['tax_value'],
                        'processed_amount'         => $ci['sku_amount'],
                        'stockin_quantity'         => $ci['qty'],
                        'stockin_tax_value'        => $ci['tax_value'],
                        'stockin_amount'           => $ci['sku_amount'],
                        'consignment_id'           => $consignmentId,
                        'status'                   => 3,
                        'sync'                     => 0,
                        'local_sync'               => 0,
                    ]
                ]);
                if (!$r) throw new Exception('Failed to create Indent item for SKU ' . $ci['sku_id']);
            }

            // ── STEP B: Purchase Order ────────────────────────────────────────
            $this->Inventory_purchase_order->create();
            $poSave = $this->Inventory_purchase_order->save([
                'Inventory_purchase_order' => [
                    'vendor_id'              => $vendorId,
                    'vendor_name'            => $vendorName,
                    'location_id'            => $primaryLocationId,
                    'location_name'          => $locationName,
                    'location_type'          => 'OUTLET',
                    'p_o_id'                 => $poId,
                    'internal_indent_id'     => $indentId,
                    'po_date'                => $stockinDate,
                    'direct_purchase'        => 1,
                    'charge'                 => $charges,
                    'discount_value'         => $orderDiscValue,
                    'total_amount'           => $indentTotal,
                    'approved_by_id'         => $primaryLocationId,
                    'approved_by_name'       => $locationName,
                    'approved_on'            => $now,
                    'approved_discount_value'=> $orderDiscValue,
                    'approved_charge'        => 0,
                    'approved_amount'        => $grandTotal,
                    'processed_on'           => $now,
                    // 'processed_by_id'        => $userId,
                    'processed_by_id'        => $primaryLocationId,
                    'processed_by_name'      => $locationName,
                    'processed_charge'       => 0,
                    'processed_discount_value'=> $orderDiscValue,
                    'processed_amount'       => $grandTotal,
                    'stockin_on'             => $now,
                    'stockin_by_id'          => $primaryLocationId,
                    'stockin_by_name'        => $locationName,
                    'stockin_charge'         => 0,
                    'stockin_discount_value' => $orderDiscValue,
                    'stockin_amount'         => $grandTotal,
                    'stockin_id'             => $stockinId,
                    'invoice_no'             => $invoiceNo,
                    'is_stockedin'           => 1,
                    'vat_total'              => $totalVatDuty,
                    'tcs_total'              => $totalTcsDuty,
                    'status'                 => 4,
                    'sync'                   => 0,
                    'local_sync'             => 0,
                ]
            ]);
            if (!$poSave) throw new Exception('Failed to create Purchase Order record.');

            // PO items
            foreach ($computedItems as $ci) {
                $this->Inventory_purchase_order_item->create();
                $r = $this->Inventory_purchase_order_item->save([
                    'Inventory_purchase_order_item' => [
                        'p_o_id'                   => $poId,
                        'internal_indent_id'       => $indentId,
                        'sku_id'                   => $ci['sku_id'],
                        'sku_name'                 => $ci['sku_name'],
                        'sku_unit'                 => $ci['sku_unit'],
                        'sku_unit_name'            => $ci['sku_unit_name'],
                        'sku_price'                => $ci['sku_price'],
                        'sku_quantity'             => $ci['qty'],
                        'sku_discount'             => $ci['disc_input'],
                        'sku_discount_type'        => $ci['disc_type'],
                        'sku_discount_value'       => $ci['disc_value'],
                        'sku_total_tax_percentage' => $ci['tax_pct'],
                        'sku_total_tax_value'      => $ci['tax_value'],
                        'sku_amount'               => $ci['sku_amount'],
                        'excise_duty_amt'          => $ci['excise_amt'],
                        'addl_excise_duty_amt'     => $ci['addl_excise_amt'],
                        'vat_percentage'           => $ci['vat_percentage'],
                        'vat_amt'                  => $ci['vat_amt'],
                        'tcs_percentage'           => $ci['tcs_percentage'],
                        'tcs_amt'                  => $ci['tcs_amt'],
                        'landed_amt'               => $ci['landed_amt'],
                        'approved_quantity'        => $ci['qty'],
                        'approve_discount'         => $ci['disc_input'],
                        'approve_discount_type'    => $ci['disc_type'],
                        'approve_discount_value'   => $ci['disc_value'],
                        'approved_total_tax_value' => $ci['tax_value'],
                        'approved_amount'          => $ci['sku_amount'],
                        'processed_quantity'       => $ci['qty'],
                        'process_discount'         => $ci['disc_input'],
                        'process_discount_type'    => $ci['disc_type'],
                        'process_discount_value'   => $ci['disc_value'],
                        'processed_total_tax_value'=> $ci['tax_value'],
                        'processed_amount'         => $ci['sku_amount'],
                        'stockin_quantity'         => $ci['qty'],
                        'stockin_discount'         => $ci['disc_input'],
                        'stockin_discount_type'    => $ci['disc_type'],
                        'stockin_discount_value'   => $ci['disc_value'],
                        'stockin_total_tax_value'  => $ci['tax_value'],
                        'stockin_amount'           => $ci['sku_amount'],
                        'is_stockedin'             => 1,
                        'status'                   => 4,
                        'sync'                     => 0,
                        'local_sync'               => 0,
                    ]
                ]);
                if (!$r) throw new Exception('Failed to create PO item for SKU ' . $ci['sku_id']);
            }

            // ── STEP C: Stockin record ────────────────────────────────────────
            $this->Inventory_stockin->create();
            $stockinSave = $this->Inventory_stockin->save([
                'Inventory_stockin' => [
                    'location_type'   => 'Outlet',
                    'vendor_id'       => $vendorId,
                    'vendor_name'     => $vendorName,
                    'p_o_id'          => $poId,
                    'indent_id'       => $indentId,
                    'stockin_by_id'   => $primaryLocationId,
                    'stockin_by_name' => $locationName,
                    'stockin_charge'  => 0,
                    'stockin_amount'  => $grandTotal,
                    'invoice_no'      => $invoiceNo,
                    'stockin_id'      => $stockinId,
                    'status'          => 1,
                    'sync'            => 0,
                    'local_sync'      => 0,
                ]
            ]);
            if (!$stockinSave) throw new Exception('Failed to create Stockin record.');

            // Stockin items
            foreach ($computedItems as $ci) {
                $this->Inventory_stockin_item->create();
                $r = $this->Inventory_stockin_item->save([
                    'Inventory_stockin_item' => [
                        'stockin_id'              => $stockinId,
                        'p_o_id'                  => $poId,
                        'sku_id'                  => $ci['sku_id'],
                        'sku_name'                => $ci['sku_name'],
                        'sku_unit'                => $ci['sku_unit'],
                        'sku_unit_name'           => $ci['sku_unit_name'],
                        'sku_price'               => $ci['unit_price_eff'],
                        'stockin_quantity'        => $ci['qty'],
                        'stockin_total_tax_value' => $ci['tax_value'],
                        'stockin_amount'          => $ci['sku_amount'],
                        'status'                  => 1,
                        'sync'                    => 0,
                        'local_sync'              => 0,
                    ]
                ]);
                if (!$r) throw new Exception('Failed to create Stockin item for SKU ' . $ci['sku_id']);
            }

            // ── STEP D: Stock ledger + avg price update per SKU ───────────────
            foreach ($computedItems as $ci) {
                $skuId          = $ci['sku_id'];
                $qty            = $ci['qty'];
                $unitPriceEff   = $ci['unit_price_eff'];

                // Current balance
                $prevRow = $this->Stock_ledger->find('first', [
                    'conditions' => ['sku_id' => $skuId, 'type' => 'OUTLET', 'item_type' => 'SKU', 'store_id' => $primaryLocationId],
                    'fields'     => ['actual_balance'],
                    'order'      => ['id DESC'],
                    'recursive'  => -1
                ]);
                $prevBalance = !empty($prevRow['Stock_ledger']['actual_balance'])
                    ? floatval($prevRow['Stock_ledger']['actual_balance']) : 0;
                $newBalance  = round($prevBalance + $qty, 4);

                // Credit entry — identical to stockin_po(): record_type='stockin', type='OUTLET'
                $this->Stock_ledger->create();
                $lr = $this->Stock_ledger->save([
                    'Stock_ledger' => [
                        'store_id'      => $primaryLocationId,
                        'sku_id'        => $skuId,
                        'sku_name'      => $ci['sku_name'],
                        'sku_price'     => round($unitPriceEff, 2),
                        'unit_id'       => $ci['sku_unit'],
                        'unit_name'     => $ci['sku_unit_name'],
                        'record_id'     => $stockinId,
                        'record_type'   => 'stockin',
                        'quantity'      => round($qty, 2),
                        'balance_stock' => $newBalance,
                        'actual_balance'=> $newBalance,
                        'quantity_type' => 'credit',
                        'item_type'     => 'SKU',
                        'type'          => 'OUTLET',
                        'sync'          => 0,
                        'local_sync'    => 0,
                        'created'       => $now,
                    ]
                ]);
                if (!$lr) throw new Exception('Failed to write stock ledger for SKU ' . $skuId);

                // Rolling avg of last 3 stockins — identical formula to stockin_po()
                $avgResult = $this->Inventory_sku->query("
                    SELECT SUM(sku_price * quantity) / NULLIF(SUM(quantity),0) AS avg_price
                    FROM (
                        SELECT sku_price, quantity FROM stock_ledger
                        WHERE sku_id = '$skuId'
                          AND record_type = 'stockin'
                          AND item_type = 'sku' and type='OUTLET'
                        ORDER BY id DESC LIMIT 3
                    ) AS last_3
                ");
                $avgStockinPrice = round(floatval($avgResult[0][0]['avg_price'] ?? $unitPriceEff), 2);
                $sku_stockin_prices[$skuId] = $avgStockinPrice;

                $this->Inventory_sku->query("
                    UPDATE inventory_skus SET
                        last_stockin_price = " . round($unitPriceEff, 2) . ",
                        avg_stockin_price  = $avgStockinPrice,
                        sync               = 0,
                        local_sync         = 0
                    WHERE id = '$skuId'
                ");
            }

            $db->commit();

            // ── STEP E: Food cost recalc — same call as stockin_po() ──────────
            $this->loadModel('Inventory_sku');
            $this->loadModel('Inventory_semifinished');
            $this->loadModel('Catalog_product');
            $this->loadModel('Inventory_linking');
            $this->_refreshCostsForStockin($sku_stockin_prices);

            header('Content-Type: application/json');
            echo json_encode([
                'success'       => true,
                'indent_id'     => $indentId,
                'serial_number' => $serialNumber,
                'po_id'         => $poId,
                'stockin_id'    => $stockinId,
            ]);

        } catch (Exception $e) {
            $db->rollback();
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }
    
    private function _refreshCostsForStockin($sku_stockin_prices)
    {
        if (empty($sku_stockin_prices)) return;

        $this->loadModel('Inventory_sku');
        $this->loadModel('Inventory_semifinished');
        $this->loadModel('Catalog_product');

        foreach ($sku_stockin_prices as $sku_id => $discounted_price) {
            if ($discounted_price <= 0) continue;

            $sku_id         = intval($sku_id);
            $discounted_price = floatval($discounted_price);

            // Step A: Update consumption_unit_price for same-unit SKUs
            $this->Inventory_sku->query(
                "UPDATE inventory_skus sk
                 SET sk.consumption_unit_price = $discounted_price,
                     sk.sync = 0, sk.local_sync = 0
                 WHERE sk.id = $sku_id
                   AND sk.status = 1
                   AND (sk.unit_name = sk.consumption_unit_name
                        OR sk.consumption_unit_name IS NULL
                        OR sk.consumption_unit_name = '')"
            );

            // Step B: Update consumption_unit_price for cross-unit SKUs
            // Apply same conversion factor logic as _refreshAllCosts Step 1a
            $this->Inventory_sku->query(
                "UPDATE inventory_skus sk
                 LEFT JOIN (
                     SELECT GREATEST(COALESCE(
                         (SELECT CAST(um.conversion_factor AS DECIMAL(15,6))
                          FROM catalog_unit_mapping um
                          WHERE um.unit_name        = sk2.unit_name
                            AND um.mapped_unit_name = sk2.consumption_unit_name
                            AND um.unit_name       != um.mapped_unit_name
                            AND um.status = 1
                          ORDER BY um.id DESC LIMIT 1),
                         NULLIF(CAST(sk2.consumption_factor AS DECIMAL(15,6)), 0),
                         1
                     ), 1) AS map_cf
                     FROM inventory_skus sk2
                     WHERE sk2.id = $sku_id
                 ) cf ON 1=1
                 SET sk.consumption_unit_price = ROUND($discounted_price / cf.map_cf, 6),
                     sk.consumption_factor = cf.map_cf,
                     sk.sync = 0, sk.local_sync = 0
                 WHERE sk.id = $sku_id
                   AND sk.status = 1
                   AND sk.unit_name != sk.consumption_unit_name
                   AND COALESCE(sk.consumption_unit_name, '') != ''"
            );
        }

        // Step C: Refresh SF costs (3 passes for nesting) — same as _refreshAllCosts
        $sfUpdate = "UPDATE inventory_semifinished isf
             LEFT JOIN v_semifinished_recipe_cost vsf ON vsf.id = isf.id
             SET isf.consumption_unit_price = COALESCE(vsf.unit_cost, isf.consumption_unit_price),
                 isf.sync = 0, isf.local_sync = 0
             WHERE isf.status = 1
               AND COALESCE(vsf.unit_cost, 0) > 0";
        $this->Inventory_semifinished->query($sfUpdate); // Pass 1
        $this->Inventory_semifinished->query($sfUpdate); // Pass 2
        $this->Inventory_semifinished->query($sfUpdate); // Pass 3

        // Step D: Refresh product food_cost
        $this->Catalog_product->query(
            "UPDATE catalog_product cp
             LEFT JOIN v_product_recipe_cost vpc ON vpc.id = cp.id
             SET cp.food_cost = COALESCE(vpc.recipe_cost, 0),
                 cp.sync = 0, cp.local_sync = 0
             WHERE cp.status = 1"
        );
    }
    
    
    private function _getEmployeeSalesData() {
        $startDate = date('m/d/Y');
        $endDate   = date('m/d/Y');

        if ($this->request->is('post', 'put')) {
            $daterange = $this->request->data['daterange'] ?? ($this->request->data['Search']['startdate'] ?? '');
        } else {
            $daterange = $this->request->query['daterange'] ?? ($this->request->query['sdate'] ?? '');
        }

        if (!empty($daterange) && strpos($daterange, ' - ') !== false) {
            $dates      = explode(" - ", $daterange);
            $start_date = trim($dates[0]);
            $startDate  = date('m/d/Y', strtotime($start_date));
            $end_date   = trim($dates[1]);
            $endDate    = date('m/d/Y', strtotime($end_date));
            $startdate  = date('Y-m-d', strtotime($start_date));
            $enddate    = date('Y-m-d', strtotime($end_date));
        } else {
            $startdate = date('Y-m-d', strtotime($startDate));
            $enddate   = date('Y-m-d', strtotime($endDate));
        }

        $_sConfig = $this->getLocationConfig(isset($store_id) ? $store_id : (isset($location_id) ? $location_id : null)); $sStart = $_sConfig['shift_start_time']; $sEnd = $_sConfig['shift_end_time'];

        $startDatetime = $startdate . ' ' . $sStart;
        $endDatetime   = date('Y-m-d', strtotime($enddate . ' +1 day')) . ' ' . $sEnd;
        $daterangeValue = date('m/d/Y', strtotime($startdate)) . ' - ' . date('m/d/Y', strtotime($enddate));

        $this->loadModel('Location');
        $this->loadModel('User');
        $this->loadModel('Order_transaction');
        $this->loadModel('Order_orderhistory');
        $this->loadModel('Order_payment');
        $this->loadModel('Order_tax');

        // Store location from session (for outlet login)
        $selectedStoreId = $this->Session->read('Auth.Location.id');
        if (empty($selectedStoreId)) {
            $selectedStoreId = $this->Session->read('Auth.User.location_id') ?? $this->Session->read('Auth.User.id');
        }
        $selectedStoreId = intval($selectedStoreId);

        // Fetch stores accessible to user
        $conditions = ['status' => 1, 'type' => 'Outlet'];
        if ($selectedStoreId > 0) {
            $conditions['id'] = $selectedStoreId;
        }
        $users = $this->Location->find('all', ['conditions' => $conditions, 'order' => ['name' => 'ASC']]);

        $storeCondition         = "";
        $storeConditionOt2      = "";
        $storeConditionTax      = "";
        $storeConditionOrderTax = "";
        $storeConditionPlain    = "";
        $oohFilter              = "";
        $otTaxFilter            = "";
        $storeName = "All Stores";

        if ($selectedStoreId > 0) {
            $storeCondition         = " AND ot.store_id = $selectedStoreId ";
            $storeConditionOt2      = " AND ot2.store_id = $selectedStoreId ";
            $storeConditionTax      = " AND tax.store_id = $selectedStoreId ";
            $storeConditionOrderTax = " AND order_tax.store_id = $selectedStoreId ";
            $storeConditionPlain    = " AND store_id = $selectedStoreId ";
            $oohFilter              = " AND ooh.store_id = $selectedStoreId ";
            $otTaxFilter            = " AND ot_tax.store_id = $selectedStoreId ";
            $storeLoc = $this->Location->find('first', ['conditions' => ['id' => $selectedStoreId]]);
            $storeName = $storeLoc['Location']['name'] ?? ('Store #' . $selectedStoreId);
        }

        $isSearched = false;
        if ($this->request->is('post') || $this->request->is('put') || !empty($this->request->query['daterange']) || !empty($this->request->query['sdate'])) {
            $isSearched = true;
        }

        if (!$isSearched) {
            $cashierSalesList = [];
            $grandTotals = [
                'bills_count' => 0, 'qty_sold' => 0, 'gross_sales' => 0, 'discount' => 0,
                'net_sales' => 0, 'tax' => 0, 'net_payable' => 0, 'nc_sales' => 0
            ];
            $allCategories = [];
            $categoryGrandTotals = [];
            $allDishTypesNoSc = [];
            $grandTotalsDtNoSc = [];

            return compact(
                'users', 'selectedStoreId', 'storeName', 'daterangeValue',
                'cashierSalesList', 'grandTotals', 'allCategories', 'categoryGrandTotals',
                'allDishTypesNoSc', 'grandTotalsDtNoSc', 'isSearched'
            );
        }

        // Fetch transaction metrics grouped by cashier ID from order_orderhistories (excluding NC orders / type 7)
        $cashierRows = $this->Order_transaction->query("
            SELECT 
                COALESCE(NULLIF(b_cashier.cashier_id, 0), ot.cashier_id) AS cashier_id,
                COUNT(DISTINCT ot.reference_id) AS bills_count,
                SUM(IFNULL(ot.price, 0)) AS gross_sales,
                SUM(IFNULL(ot.discount, 0)) AS discount,
                SUM(IFNULL(ot.total_tax, 0)) AS tax,
                SUM(IFNULL(ot.amount, 0)) AS net_payable
            FROM order_transactions ot
            LEFT JOIN (
                SELECT reference_id, store_id, MAX(cashier_id) AS cashier_id
                FROM order_orderhistories
                WHERE status = 2 AND cashier_id IS NOT NULL AND cashier_id > 0
                GROUP BY reference_id, store_id
            ) b_cashier ON b_cashier.reference_id = ot.reference_id AND b_cashier.store_id = ot.store_id
            WHERE ot.status = 2
              AND ot.created BETWEEN '$startDatetime' AND '$endDatetime'
              $storeCondition
              AND ot.reference_id NOT IN (
                  SELECT op_nc.reference_id FROM order_payments op_nc WHERE op_nc.type = 7 AND op_nc.status = 2
              )
            GROUP BY COALESCE(NULLIF(b_cashier.cashier_id, 0), ot.cashier_id)
            ORDER BY net_payable DESC
        ");

        $cashierRowsMap = [];
        foreach ($cashierRows as $row) {
            $cid = intval($row[0]['cashier_id'] ?? $row['b_cashier']['cashier_id'] ?? $row['ot']['cashier_id'] ?? 0);
            $cashierRowsMap[$cid] = [
                'bills_count' => intval($row[0]['bills_count'] ?? 0),
                'gross_sales' => floatval($row[0]['gross_sales'] ?? 0),
                'discount'    => floatval($row[0]['discount'] ?? 0),
                'tax'         => floatval($row[0]['tax'] ?? 0),
                'net_payable' => floatval($row[0]['net_payable'] ?? 0),
            ];
        }

        // Fetch Category Sales Breakdown grouped by orderhistory cashier_id (excluding NC orders / type 7)
        $catRows = $this->Order_orderhistory->query("
            SELECT 
                COALESCE(NULLIF(ooh.cashier_id, 0), ot.cashier_id) AS cashier_id,
                IFNULL(NULLIF(TRIM(ooh.category_name), ''), 'Other Dish') AS category_name,
                SUM(IFNULL(ooh.quantity, 0)) AS total_qty,
                SUM(IFNULL(ooh.total_price, 0)) AS total_cat_sales
            FROM order_orderhistories ooh
            JOIN order_transactions ot ON ot.reference_id = ooh.reference_id AND ot.status = 2
            WHERE ooh.status = 2
              AND ot.created BETWEEN '$startDatetime' AND '$endDatetime'
              $storeCondition
              AND ot.reference_id NOT IN (
                  SELECT op_nc.reference_id FROM order_payments op_nc WHERE op_nc.type = 7 AND op_nc.status = 2
              )
            GROUP BY COALESCE(NULLIF(ooh.cashier_id, 0), ot.cashier_id), IFNULL(NULLIF(TRIM(ooh.category_name), ''), 'Other Dish')
        ");

        $cashierCategoryData = [];
        $allCategoriesMap = [];
        $cashierQtyMap = [];
        foreach ($catRows as $cr) {
            $cid = intval($cr[0]['cashier_id'] ?? $cr['ooh']['cashier_id'] ?? $cr['ot']['cashier_id'] ?? 0);
            $cat = trim($cr[0]['category_name'] ?? 'Other Dish');
            $cSales = floatval($cr[0]['total_cat_sales'] ?? 0);
            $cQty   = floatval($cr[0]['total_qty'] ?? 0);

            $cashierCategoryData[$cid][$cat] = ($cashierCategoryData[$cid][$cat] ?? 0) + $cSales;
            $cashierQtyMap[$cid] = ($cashierQtyMap[$cid] ?? 0) + $cQty;
            $allCategoriesMap[$cat] = true;
        }
        $allCategories = array_keys($allCategoriesMap);
        sort($allCategories);

        // Payment breakdown grouped by cashier_id
        $storeConditionOp = "";
        if ($selectedStoreId > 0) {
            $storeConditionOp = " AND op.store_id = $selectedStoreId ";
        }

        $pmRows = $this->Order_payment->query("
            SELECT 
                COALESCE(NULLIF(b_cashier.cashier_id, 0), ot.cashier_id) AS cashier_id,
                op.type,
                op.type_value,
                SUM(IFNULL(op.amount, 0)) AS pm_amount
            FROM order_payments op
            LEFT JOIN (
                SELECT reference_id, store_id, MAX(cashier_id) AS cashier_id
                FROM order_transactions
                WHERE status = 2
                GROUP BY reference_id, store_id
            ) ot ON ot.reference_id = op.reference_id AND ot.store_id = op.store_id
            LEFT JOIN (
                SELECT reference_id, store_id, MAX(cashier_id) AS cashier_id
                FROM order_orderhistories
                WHERE status = 2 AND cashier_id IS NOT NULL AND cashier_id > 0
                GROUP BY reference_id, store_id
            ) b_cashier ON b_cashier.reference_id = op.reference_id AND b_cashier.store_id = op.store_id
            WHERE op.status = 2
              AND op.created BETWEEN '$startDatetime' AND '$endDatetime'
              $storeConditionOp
            GROUP BY COALESCE(NULLIF(b_cashier.cashier_id, 0), ot.cashier_id), op.type, op.type_value
        ");

        $cashierPaymentData = [];
        $cashierPlatformTax = [];
        foreach ($pmRows as $pmr) {
            $cid = intval($pmr[0]['cashier_id'] ?? $pmr['b_cashier']['cashier_id'] ?? $pmr['ot']['cashier_id'] ?? 0);
            $pType = intval($pmr['op']['type'] ?? $pmr[0]['type'] ?? 0);
            $tVal  = trim($pmr['op']['type_value'] ?? $pmr[0]['type_value'] ?? '');
            $amt   = floatval($pmr[0]['pm_amount'] ?? 0);

            if (!isset($cashierPaymentData[$cid])) {
                $cashierPaymentData[$cid] = [
                    'cash' => 0, 'card' => 0, 'upi' => 0, 'online' => 0, 'storecard' => 0, 'nc' => 0, 'custom' => []
                ];
                $cashierPlatformTax[$cid] = 0.0;
            }

            if ($pType == 1) $cashierPaymentData[$cid]['cash'] += $amt;
            elseif ($pType == 2) $cashierPaymentData[$cid]['card'] += $amt;
            elseif ($pType == 9) $cashierPaymentData[$cid]['online'] += $amt;
            elseif ($pType == 6) $cashierPaymentData[$cid]['upi'] += $amt;
            elseif ($pType == 7) $cashierPaymentData[$cid]['nc'] += $amt;
            elseif ($pType == 8) $cashierPaymentData[$cid]['storecard'] += $amt;
            else {
                $label = !empty($tVal) ? $tVal : ('Type ' . $pType);
                if ($pType == 11 && in_array(strtolower($label), ['swiggy', 'zomato'])) {
                    $pTax = round($amt * 5 / 105, 2);
                    $cashierPlatformTax[$cid] += $pTax;
                    $amt = round($amt - $pTax, 2);
                }
                $cashierPaymentData[$cid]['custom'][$label] = ($cashierPaymentData[$cid]['custom'][$label] ?? 0) + $amt;
            }
        }

        // Dish Type Sales WITHOUT Service Charge per Cashier
        $dtNoScRows = $this->Order_orderhistory->query("
            WITH item_amounts AS (
                SELECT 
                    ooh.id,
                    ooh.local_id,
                    ooh.reference_id,
                    ooh.store_id,
                    ooh.product_id,
                    ooh.quantity,
                    COALESCE(NULLIF(b_cashier.cashier_id, 0), ot0.cashier_id, ooh.cashier_id) AS cashier_id,
                    cp.dish_type,
                    (ooh.price * ooh.quantity) * (1 - IFNULL(ot_tax.discount_percentage, 0) / 100) AS item_amount
                FROM order_orderhistories ooh
                INNER JOIN catalog_product cp ON cp.id = ooh.product_id
                LEFT JOIN order_transactions ot0 ON ot0.reference_id = ooh.reference_id AND ot0.store_id = ooh.store_id AND ot0.status = 2
                LEFT JOIN (
                    SELECT reference_id, store_id, MAX(cashier_id) AS cashier_id
                    FROM order_orderhistories
                    WHERE status = 2 AND cashier_id IS NOT NULL AND cashier_id > 0
                    GROUP BY reference_id, store_id
                ) b_cashier ON b_cashier.reference_id = ooh.reference_id AND b_cashier.store_id = ooh.store_id
                LEFT JOIN (
                    SELECT orderhistory_id, MAX(discount_percentage) AS discount_percentage, store_id
                    FROM order_tax
                    WHERE order_tax.status != 0 $storeConditionOrderTax
                    GROUP BY orderhistory_id, store_id
                ) ot_tax ON ot_tax.orderhistory_id = COALESCE(NULLIF(ooh.local_id, ''), ooh.id) AND ot_tax.store_id = ooh.store_id
                WHERE ooh.created BETWEEN '$startDatetime' AND '$endDatetime'
                  AND ooh.status = 2
                  $oohFilter
                  AND ooh.reference_id NOT IN (
                      SELECT op_dw.reference_id FROM order_payments op_dw
                      WHERE op_dw.type = 7 AND op_dw.status = 2 $storeConditionPlain
                  )
            ),
            bill_totals AS (
                SELECT 
                    ot.reference_id,
                    ot.store_id,
                    (ot.price - ot.discount) AS bill_net,
                    SUM(ia.item_amount) AS bill_item_sum
                FROM order_transactions ot
                INNER JOIN item_amounts ia ON ot.reference_id = ia.reference_id AND ot.store_id = ia.store_id
                WHERE ot.status = 2
                  AND ot.created BETWEEN '$startDatetime' AND '$endDatetime'
                  $storeCondition
                  AND ot.reference_id NOT IN (
                      SELECT op_dw.reference_id FROM order_payments op_dw
                      WHERE op_dw.type = 7 AND op_dw.status = 2 $storeConditionPlain
                  )
                GROUP BY ot.reference_id, ot.store_id, ot.price, ot.discount
            )
            SELECT 
                ia.cashier_id,
                ia.dish_type,
                SUM(ia.quantity) AS total_qty,
                ROUND(SUM(CASE WHEN bt.bill_item_sum > 0 THEN (ia.item_amount / bt.bill_item_sum) * bt.bill_net ELSE 0 END), 2) AS total_amount
            FROM item_amounts ia
            INNER JOIN bill_totals bt ON ia.reference_id = bt.reference_id AND ia.store_id = bt.store_id
            GROUP BY ia.cashier_id, ia.dish_type
            ORDER BY ia.dish_type DESC
        ");

        $dishTypeMap = [
            '0' => 'Food',
            '1' => 'Beverage',
            '2' => 'Liquor',
            '3' => 'Others',
            0   => 'Food',
            1   => 'Beverage',
            2   => 'Liquor',
            3   => 'Others'
        ];

        $cashierDtNoSc = [];
        $allDishTypesNoScMap = [];
        foreach ($dtNoScRows as $dtr) {
            $cid     = intval($dtr['ia']['cashier_id'] ?? $dtr[0]['cashier_id'] ?? 0);
            $rawType = trim($dtr['ia']['dish_type'] ?? $dtr[0]['dish_type'] ?? '0');
            $dType   = $dishTypeMap[$rawType] ?? (is_numeric($rawType) ? ($dishTypeMap[(int)$rawType] ?? $rawType) : $rawType);
            $amt     = floatval($dtr[0]['total_amount'] ?? 0);

            $cashierDtNoSc[$cid][$dType] = ($cashierDtNoSc[$cid][$dType] ?? 0) + $amt;
            $allDishTypesNoScMap[$dType] = true;
        }
        $allDishTypesNoSc = array_keys($allDishTypesNoScMap);
        sort($allDishTypesNoSc);

        // Fetch subtotal & discount for ALL orders per cashier from order_orderhistories
        $subtotalRows = $this->Order_transaction->query("
            SELECT 
                COALESCE(NULLIF(b_cashier.cashier_id, 0), ot.cashier_id) AS cashier_id,
                SUM(IFNULL(ot.price, 0)) AS subtotal,
                SUM(IFNULL(ot.discount, 0)) AS discount
            FROM order_transactions ot
            LEFT JOIN (
                SELECT reference_id, store_id, MAX(cashier_id) AS cashier_id
                FROM order_orderhistories
                WHERE status = 2 AND cashier_id IS NOT NULL AND cashier_id > 0
                GROUP BY reference_id, store_id
            ) b_cashier ON b_cashier.reference_id = ot.reference_id AND b_cashier.store_id = ot.store_id
            WHERE ot.status = 2
              AND ot.created BETWEEN '$startDatetime' AND '$endDatetime'
              $storeCondition
            GROUP BY COALESCE(NULLIF(b_cashier.cashier_id, 0), ot.cashier_id)
        ");
        $cashierSubtotalMap = [];
        $cashierDiscountMap = [];
        foreach ($subtotalRows as $str) {
            $cid = intval($str[0]['cashier_id'] ?? $str['b_cashier']['cashier_id'] ?? $str['ot']['cashier_id'] ?? 0);
            $cashierSubtotalMap[$cid] = floatval($str[0]['subtotal'] ?? 0);
            $cashierDiscountMap[$cid] = floatval($str[0]['discount'] ?? 0);
        }

        // Union of every cashier id that appears ANYWHERE (incl. NC-only cashiers)
        $allCashierIds = array_filter(array_unique(array_merge(
            array_keys($cashierRowsMap),
            array_keys($cashierSubtotalMap),
            array_keys($cashierPaymentData),
            array_keys($cashierCategoryData),
            array_keys($cashierDtNoSc)
        )));

        $userNamesMap = [];
        if (!empty($allCashierIds)) {
            $uList = $this->User->find('all', [
                'conditions' => ['User.id IN' => array_values($allCashierIds)],
                'fields' => ['User.id', 'User.name', 'User.username']
            ]);
            foreach ($uList as $u) {
                $uName = !empty($u['User']['name']) ? $u['User']['name'] : $u['User']['username'];
                $userNamesMap[$u['User']['id']] = $uName;
            }
        }

        $cashierSalesList = [];
        $grandTotals = [
            'bills_count' => 0, 'qty_sold' => 0, 'gross_sales' => 0, 'discount' => 0,
            'net_sales' => 0, 'tax' => 0, 'net_payable' => 0, 'nc_sales' => 0
        ];
        $categoryGrandTotals = array_fill_keys($allCategories, 0.0);
        $grandTotalsDtNoSc   = array_fill_keys($allDishTypesNoSc, 0.0);

        foreach ($allCashierIds as $cid) {
            $cName = !empty($userNamesMap[$cid]) ? $userNamesMap[$cid] : ($cid > 0 ? ('Cashier #' . $cid) : 'General Cashier');

            $rr         = $cashierRowsMap[$cid] ?? ['bills_count' => 0, 'tax' => 0, 'net_payable' => 0, 'gross_sales' => 0, 'discount' => 0];
            $billsCount = intval($rr['bills_count']);
            $grossSales = floatval($cashierSubtotalMap[$cid] ?? $rr['gross_sales'] ?? 0);
            $discount   = floatval($cashierDiscountMap[$cid] ?? $rr['discount'] ?? 0);
            $netSales   = floatval($rr['gross_sales']) - floatval($rr['discount']);
            $tax        = floatval($rr['tax']);
            $netPayable = floatval($rr['net_payable']) - floatval($cashierPlatformTax[$cid] ?? 0);
            $qtySold    = floatval($cashierQtyMap[$cid] ?? 0);

            $pm = $cashierPaymentData[$cid] ?? [
                'cash' => 0, 'card' => 0, 'upi' => 0, 'online' => 0, 'storecard' => 0, 'nc' => 0, 'custom' => []
            ];
            $ncSales = $pm['nc'];
            unset($pm['nc']);

            $cats = [];
            foreach ($allCategories as $catName) {
                $v = floatval($cashierCategoryData[$cid][$catName] ?? 0);
                $cats[$catName] = $v;
                $categoryGrandTotals[$catName] += $v;
            }

            $dtNoScMap = [];
            foreach ($allDishTypesNoSc as $dtKey) {
                $v = floatval($cashierDtNoSc[$cid][$dtKey] ?? 0);
                $dtNoScMap[$dtKey] = $v;
                $grandTotalsDtNoSc[$dtKey] += $v;
            }

            $cashierSalesList[] = [
                'cashier_id'      => $cid,
                'cashier_name'    => $cName,
                'bills_count'     => $billsCount,
                'qty_sold'        => $qtySold,
                'gross_sales'     => $grossSales,
                'discount'        => $discount,
                'net_sales'       => $netSales,
                'tax'             => $tax,
                'net_payable'     => $netPayable,
                'nc_sales'        => $ncSales,
                'categories'      => $cats,
                'dish_type_no_sc' => $dtNoScMap,
                'payments'        => $pm,
            ];

            $grandTotals['bills_count'] += $billsCount;
            $grandTotals['qty_sold']    += $qtySold;
            $grandTotals['gross_sales'] += $grossSales;
            $grandTotals['discount']    += $discount;
            $grandTotals['net_sales']   += $netSales;
            $grandTotals['tax']         += $tax;
            $grandTotals['net_payable'] += $netPayable;
            $grandTotals['nc_sales']    += $ncSales;
        }

        return compact(
            'users', 'selectedStoreId', 'storeName', 'daterangeValue',
            'cashierSalesList', 'grandTotals', 'allCategories', 'categoryGrandTotals',
            'allDishTypesNoSc', 'grandTotalsDtNoSc', 'isSearched'
        );
    }

    public function employee_sales_report() {
        if (!$this->Session->check('Auth.User.id') && !$this->Session->check('Auth.Location.id')) {
            $this->redirect(array('controller' => 'dashboard', 'action' => 'login'));
        }
        $this->layout = "users";
        $data = $this->_getEmployeeSalesData();
        $this->set($data);
    }

    public function employee_sales_report_pdf() {
        if (!$this->Session->check('Auth.User.id') && !$this->Session->check('Auth.Location.id')) {
            $this->redirect(array('controller' => 'dashboard', 'action' => 'login'));
        }
        $this->layout = null;
        $data = $this->_getEmployeeSalesData();
        $this->set($data);
    }

    public function employee_sales_report_export() {
        if (!$this->Session->check('Auth.User.id') && !$this->Session->check('Auth.Location.id')) {
            $this->redirect(array('controller' => 'dashboard', 'action' => 'login'));
        }
        $this->autoRender = false;
        $data = $this->_getEmployeeSalesData();

        $filename = "employee_sales_report_" . date('Ymd_His') . ".csv";
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $out = fopen('php://output', 'w');

        // Title Header
        fputcsv($out, ['Employee / Cashier Sales Report']);
        fputcsv($out, ['Store:', $data['storeName'], 'Date Range:', $data['daterangeValue']]);
        fputcsv($out, []); // blank line

        // Section 1: Financial Summary
        fputcsv($out, ['--- 1. Cashier Sales & Financial Summary (DSR Style) ---']);
        fputcsv($out, ['#', 'Employee / Cashier', 'Bills Count', 'Qty Sold', 'Subtotal (INR)', 'Discount (INR)', 'Net Sales (INR)', 'Tax (INR)', 'Total Collection (INR)', 'NC Sales (INR)', 'Payment Breakdown']);

        $i = 1;
        foreach ($data['cashierSalesList'] as $cs) {
            $pmParts = [];
            if ($cs['payments']['cash'] > 0)      $pmParts[] = 'Cash: ' . number_format($cs['payments']['cash'], 2);
            if ($cs['payments']['card'] > 0)      $pmParts[] = 'Card: ' . number_format($cs['payments']['card'], 2);
            if ($cs['payments']['upi'] > 0)       $pmParts[] = 'UPI: ' . number_format($cs['payments']['upi'], 2);
            if ($cs['payments']['online'] > 0)    $pmParts[] = 'Online: ' . number_format($cs['payments']['online'], 2);
            if ($cs['payments']['storecard'] > 0) $pmParts[] = 'Storecard: ' . number_format($cs['payments']['storecard'], 2);
            if ($cs['payments']['nc'] > 0)        $pmParts[] = 'NC: ' . number_format($cs['payments']['nc'], 2);

            if (!empty($cs['payments']['custom'])) {
                foreach ($cs['payments']['custom'] as $cName => $cAmt) {
                    if ($cAmt > 0) {
                        $pmParts[] = $cName . ': ' . number_format($cAmt, 2);
                    }
                }
            }
            $paymentBreakdown = !empty($pmParts) ? implode(' | ', $pmParts) : 'No payments recorded';

            fputcsv($out, [
                $i++,
                $cs['cashier_name'],
                $cs['bills_count'],
                number_format($cs['qty_sold'], 2, '.', ''),
                number_format($cs['gross_sales'], 2, '.', ''),
                number_format($cs['discount'], 2, '.', ''),
                number_format($cs['net_sales'], 2, '.', ''),
                number_format($cs['tax'], 2, '.', ''),
                number_format($cs['net_payable'], 2, '.', ''),
                number_format($cs['nc_sales'], 2, '.', ''),
                $paymentBreakdown,
            ]);
        }
        $gt = $data['grandTotals'];
        fputcsv($out, [
            'TOTAL',
            '-',
            $gt['bills_count'],
            number_format($gt['qty_sold'], 2, '.', ''),
            number_format($gt['gross_sales'], 2, '.', ''),
            number_format($gt['discount'], 2, '.', ''),
            number_format($gt['net_sales'], 2, '.', ''),
            number_format($gt['tax'], 2, '.', ''),
            number_format($gt['net_payable'], 2, '.', ''),
            number_format($gt['nc_sales'], 2, '.', ''),
            '-',
        ]);

        fputcsv($out, []); // blank line

        // Section 2: Dish Type Sales Without Service Charge
        fputcsv($out, ['--- 2. Dish Type Sales Without Service Charge ---']);
        $dtNoHeader = ['#', 'Employee / Cashier'];
        foreach ($data['allDishTypesNoSc'] as $dtKey) {
            $dtNoHeader[] = $dtKey . ' (INR)';
        }
        $dtNoHeader[] = 'Total Without SC (INR)';
        fputcsv($out, $dtNoHeader);

        $n = 1;
        foreach ($data['cashierSalesList'] as $cs) {
            $rRow = [$n++, $cs['cashier_name']];
            $rSum = 0;
            foreach ($data['allDishTypesNoSc'] as $dtKey) {
                $val = floatval($cs['dish_type_no_sc'][$dtKey] ?? 0);
                $rSum += $val;
                $rRow[] = number_format($val, 2, '.', '');
            }
            $rRow[] = number_format($rSum, 2, '.', '');
            fputcsv($out, $rRow);
        }
        $dtNoTotRow = ['TOTAL WITHOUT SC', '-'];
        $totNoScSum = 0;
        foreach ($data['allDishTypesNoSc'] as $dtKey) {
            $val = floatval($data['grandTotalsDtNoSc'][$dtKey] ?? 0);
            $totNoScSum += $val;
            $dtNoTotRow[] = number_format($val, 2, '.', '');
        }
        $dtNoTotRow[] = number_format($totNoScSum, 2, '.', '');
        fputcsv($out, $dtNoTotRow);

        fclose($out);
        exit;
    }

}
