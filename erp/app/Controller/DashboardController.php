<?php

App::uses('UtilityController', 'Controller');
App::uses('AppController', 'Controller');
App::uses('CakeTime', 'Utility');

class DashboardController extends AppController {
    public $uses = array();

    public $components = array('Paginator');

    public function beforeFilter()
    {
        parent::beforeFilter();
        $this->layout = "dashboard";
        $this->response->disableCache();

        $this->Auth->allow();

        if ($this->Auth->user()) {

            $locations = [];
            $locations[] = $this->Session->read('Auth.User.location_id');
            $storeId = $this->Session->read('Auth.User.location_id');
            $locationsList = $this->Session->read('Auth.User.location_ids');
            if (!empty($locationsList)) {
                if (is_array($locationsList)) {
                    $locations = array_merge($locations, $locationsList);
                } else {
                    $locations = array_merge($locations, explode(',', $locationsList));
                }
            }

            $storeIdsArr = array_filter(array_unique($locations));

            $this->loadModel('Inventory_sku');

            $lowStockSkus = $this->Inventory_sku->getLowStockSkus($storeIdsArr);

            $skuIds = [];
            if (!empty($lowStockSkus)) {
                $skuIds = array_map(function($row) {
                    return (int)$row['l']['sku_id'];
                }, $lowStockSkus);
            }

            $limit = 4;
            $skuMessage = $this->buildLowStockList($lowStockSkus, $limit, 'sku');
            $controller = 'dashboard';

            $this->set(compact('skuMessage', 'skuIds', 'storeId', 'controller'));
        }
    }

    private function buildLowStockList($data, $limit = 4, $type = 'sku')
    {
        if (empty($data)) {
            return '';
        }

        $count = count($data);
        $html  = "<ul style='margin:0; padding-left:15px;'>";

        for ($i = 0; $i < min($limit, $count); $i++) {
            $item = $data[$i];

            $name = h($item['s']['name']);
            $requiredStock = (float)$item['s']['required_stock'];
            $availableStock = (float)$item['l']['last_balance'];

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
                $this->loadModel('Inventory_sku');
                $this->Inventory_sku->markAlertsAsViewed([$storeId], $skuIds);

                echo json_encode(['success' => true, 'message' => 'Alerts marked as viewed']);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid request']);
        }
    }

    public function create_pin()
    {
        $sendmessage= new UtilityController;

        $this->layout = "login";
        $id=$_REQUEST['id'];
        $this->set(compact('id'));

        $carddetail=$this->Card->find('first',array('conditions'=>array('Card.id'=>$id)));
        if(!$carddetail){
            $this->Flash->error(__('Card not found'));
        }

        if ($this->request->is(array('post', 'put'))) {
            $id=$this->data['User']['id'];
            $pin = $this->data['User']['pin'];
            $otpnew = $this->data['User']['otp'];
            if($id==''){
                $this->Flash->error(__('ID error'));
            }
            if($pin==''){
                $this->Flash->error(__('Pin could not be Blank'));
            }
            if($otpnew==''){
                $this->Flash->error(__('OTP could not be Blank'));
            }
            $cardotpchk=$this->Card->find('first',array('conditions'=>array('Card.id'=>$id,'Card.otp'=>$otpnew)));
            if(!$cardotpchk){
                $this->Flash->error(__('Wrong OTP'));
                return false;
            }

            $pinupdate = $this->Card->query("UPDATE cards SET pin='$pin',otp='0',sync='0',local_sync='0' WHERE id=$id");
            $this->Flash->success(__('Pin has been saved.'));
        }else{
            $name=$carddetail['Card']['name'];
            $mobile=$carddetail['Card']['mobile'];
            $otp = mt_rand(1000, 9999);
            $query=$this->Card->query("UPDATE cards SET otp='$otp',sync='0',local_sync='0' WHERE id = '$id'");

            $message = "Hi $name, please use $otp to login to PhyloPay.";
            $msg=$sendmessage->message($message,$mobile);

        }

    }

    public function home()
    {
        $this->layout = "users";

        if (!$this->Session->check('Auth.User.id')) {
            return $this->redirect(['controller' => 'dashboard', 'action' => 'login']);
        }

        $cardbal = $this->Card->query("
            SELECT
                SUM(cardbalance) AS wallet_bal,
                SUM(IF(status IN (2,1), 1, 0)) AS activecard
            FROM cards
        ");
        $wallet_balance  = $cardbal[0][0]['wallet_bal'] ?? 0;
        $tot_activecards = $cardbal[0][0]['activecard'] ?? 0;

        $cardtrackingUsers = $this->Cardtracking->query("
            SELECT COUNT(DISTINCT(customerphone)) AS customers
            FROM cardtracking
        ");
        $totalusers = $cardtrackingUsers[0][0]['customers'] ?? 0;

        $startDate = date('m/d/Y');
        $endDate = date('m/d/Y');
        $todayDate =$currentstartdate =$currentenddate = date("Y-m-d");

        if ($this->request->is(['post', 'put']) && !empty($this->request->data['daterange']))
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

        $_shiftRange = $this->getShiftDateRange($startdate, $enddate);
        $startDatetime = $_shiftRange['startDatetime'];
        $endDatetime   = $_shiftRange['endDatetime'];
        
        
        $current_shiftRange = $this->getShiftDateRange($currentstartdate, $currentenddate);
        $currentstartDatetime = $current_shiftRange['startDatetime'];
        $currentendDatetime   = $current_shiftRange['endDatetime'];

        $authUser = $this->Session->read('Auth.User');
        $userRole = $authUser['role'] ?? '';

        $locationIdsRaw   = $authUser['location_ids'] ?? '';
        $locationIdSingle = $authUser['location_id']  ?? '';

        $locationIdsArray = !empty($locationIdsRaw)
            ? array_map('trim', is_array($locationIdsRaw) ? $locationIdsRaw : explode(',', $locationIdsRaw))
            : [];

        if (!empty($locationIdSingle)) {
            $locationIdsArray[] = trim($locationIdSingle);
        }

        $userLocationIds = array_values(
            array_unique(
                array_map('intval', $locationIdsArray)
            )
        );

        $cond = '';
        if ($userRole !== 'admin') {
            if (empty($userLocationIds)) {
                $cond = "AND store_id IN (-1)";
            } else {
                $safeIds = implode(',', $userLocationIds);
                $cond = "AND store_id IN ($safeIds)";
            }
        }

        $open_tables_amount = $this->Order_orderhistory->query("
            SELECT SUM(total_price)+SUM(total_tax) AS open_table_amount,count(DISTINCT(table_number)) as open_table
            FROM order_orderhistories
            WHERE status = '1' and created BETWEEN '$currentstartDatetime' AND '$currentendDatetime' $cond
        ");

        $open_table_amount = $open_tables_amount[0][0]['open_table_amount'] ?? 0;
        $open_table = $open_tables_amount[0][0]['open_table'] ?? 0;

        $cardStats = $this->Card->query("
            SELECT
                SUM(IF(status IN (2,1), 1, 0)) AS activecard,
                SUM(IF(status = 0, 1, 0)) AS inactivecard
            FROM cards
            WHERE created BETWEEN '$startDatetime' AND '$endDatetime'
        ");
        $activecards   = $cardStats[0][0]['activecard'] ?? 0;
        $inactivecards = $cardStats[0][0]['inactivecard'] ?? 0;

        $cardtracking = $this->Cardtracking->query("
            SELECT
                SUM(IF(type = 1 AND purpose = 'add', amount, 0)) AS totadd,
                SUM(IF(type = 2 AND purpose = 'withdrawal', amount, 0)) AS totused,
                SUM(IF(type = 3, amount, 0)) AS totsec,
                SUM(IF(type = 4, amount, 0)) AS totref,
                COUNT(DISTINCT(customerphone)) AS customers
            FROM cardtracking
            WHERE created BETWEEN '$startDatetime' AND '$endDatetime'
        ");
        $totaladded    = $cardtracking[0][0]['totadd'] ?? 0;
        $totalused     = $cardtracking[0][0]['totused'] ?? 0;
        $totalsecurity = $cardtracking[0][0]['totsec'] ?? 0;
        $totalrefund   = $cardtracking[0][0]['totref'] ?? 0;
        $users         = $cardtracking[0][0]['customers'] ?? 0;

        $transactions = $this->Order_transaction->query("SELECT COUNT(id) as total_count,SUM(total_tax) AS total_tax,SUM(servicecharge) AS servicecharge,SUM(discount) AS discount,SUM(delivery_charge) AS delivery_charge,SUM(packing_charge) AS packing_charge,SUM(amount) AS amount,SUM(round) AS round,SUM(price) AS price,SUM(pax) AS pax  FROM `order_transactions` where  created BETWEEN '$startDatetime' AND '$endDatetime' $cond and status=2 and reference_id NOT IN (SELECT reference_id from order_payments WHERE type=7 and status=2)");
        $transactionsubtotal = $this->Order_transaction->query("SELECT SUM(price) AS price,SUM(discount) AS discount  FROM `order_transactions` where  created BETWEEN '$startDatetime' and '$endDatetime' and status=2 $cond");

        $taxable = $this->Order_orderhistory->query("SELECT sum(total_price) as total_taxable FROM `order_orderhistories` WHERE  status = 2 and created BETWEEN '$startDatetime' AND '$endDatetime' $cond and total_tax_percentage != 0  AND
            reference_id NOT IN (
                SELECT reference_id
                FROM order_payments
                WHERE type = 7
                  AND status = 2
            )");

        $payments = $this->Order_payment->query("SELECT SUM(amount) as total_amount,SUM(IF(type=1,amount,0)) AS cash,SUM(IF(type=2,amount,0)) AS card,SUM(IF(type=6,amount,0)) AS online,SUM(IF(type=7,amount,0)) AS no_charge_with_tax,SUM(IF(type=7,round(amount-(SELECT sum(total_tax) from order_transactions WHERE status!=0 and reference_id=order_payments.reference_id),2),0)) AS  no_charge,SUM(IF(type=8,amount,0)) AS storecard FROM `order_payments` where created BETWEEN '$startDatetime' AND '$endDatetime' $cond and status!=0");
        $transactionsnc = $this->Order_transaction->query("SELECT SUM(total_tax) AS total_tax FROM `order_transactions` where  created BETWEEN '$startDatetime' and '$endDatetime'  and status=2 $cond and reference_id IN (SELECT reference_id from order_payments WHERE type=7 and status=2 )");

        $yourStartDateValue=$startDate . ' - ' . $endDate;
        $this->set('startdate', $yourStartDateValue);

        $lastSyncData = $this->_getLastSyncTimes();
        $lastPushTime = $lastSyncData['push']['time'] ?? 'Never';
        $lastPullTime = $lastSyncData['pull']['time'] ?? 'Never';
        $lastPushBills = $lastSyncData['push']['bills_synced'] ?? 0;
        $lastPushFailed = $lastSyncData['push']['bills_failed'] ?? 0;

        $this->set(compact(
            'open_table_amount',
            'open_table',
            'transactions',
            'payments',
            'taxable',
            'discount',
            'wallet_balance',
            'tot_activecards',
            'activecards',
            'inactivecards',
            'totalusers',
            'totaladded',
            'totalused',
            'totalsecurity',
            'totalrefund',
            'users',
            'transactionsubtotal',
            'transactionsnc',
            'lastPushTime',
            'lastPullTime',
            'lastPushBills',
            'lastPushFailed'
        ));

    }

    public function login() {
        $this->layout = "login";
        $this->set('title', 'Login Page');

        if ($this->Session->check('Auth.User') || $this->Session->check('Auth.Location')) {
            if ($this->Session->check('Auth.User')) {
                return $this->redirect(['controller' => 'dashboard', 'action' => 'home']);
            }
            if ($this->Session->check('Auth.Location')) {
                return $this->redirect(['controller' => 'Outlet', 'action' => 'indent']);
            }
        }

        if ($this->request->is('post')) {

            $this->Auth->authenticate = [
                'Form' => [
                    'userModel' => 'User',
                    'fields'    => ['username' => 'username', 'password' => 'password']
                ]
            ];
            $this->Auth->constructAuthenticate();

            if ($this->Auth->login()) {
                $id = $this->Session->read('Auth.User.id');
                $role = $this->Session->read('Auth.User.role');
                if ($role != 'admin') {

                    $moduless = $this->User_module->query("SELECT GROUP_CONCAT(name) as modulenames FROM user_modules WHERE user_id=$id and status!=0");
                    $modules = $moduless[0][0]['modulenames'];
                    $this->Session->write('Auth.User.modules', $modules);

                    $locations = $this->User_location->query("SELECT GROUP_CONCAT(location_id) as location_ids FROM user_locations WHERE user_id=$id AND status=1");
                    $location_ids = $locations[0][0]['location_ids'];
                    $this->Session->write('Auth.User.location_ids', $location_ids);
                }

                return $this->redirect(['controller' => 'dashboard', 'action' => 'home']);
            }

            if (!empty($this->request->data['User'])) {
                $this->request->data['Location'] = $this->request->data['User'];
            }

            $this->Auth->logout();
            $this->Auth->authenticate = [
                'Form' => [
                    'userModel' => 'Location',
                    'fields'    => ['username' => 'username', 'password' => 'password'],
                    'scope'     => ['Location.type' => 'OUTLET']
                ]
            ];
            $this->Auth->constructAuthenticate();

            if ($this->Auth->login()) {
                $location = $this->Auth->user();
                $this->Session->write('Auth.Location', $location);
                return $this->redirect(['controller' => 'Outlet', 'action' => 'indent']);
            }

            $this->Flash->error(__('Invalid username or password, try again'));
        }
    }

    public function logout() {

        $this->Auth->logout();

        $this->Session->delete('Auth.User');
        $this->Session->delete('Auth.Location');

        return $this->redirect('/');
    }

    public function cashier_list() {
        if(!$this->Session->check('Auth.User.id')){
            $this->redirect(array('controller'=>'dashboard','action' => 'login'));
        }

        $this->layout = "users";

        $startDate = date('m/d/Y', strtotime('-12 months'));
        $endDate = date('m/d/Y');

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

        $authUser = $this->Session->read('Auth.User');
        $userRole = $authUser['role'] ?? '';
        $userLocationId = $authUser['location_ids'] ?? 0;

        $_shiftRange = $this->getShiftDateRange($startdate, $enddate);
        $startDatetime = $_shiftRange['startDatetime'];
        $endDatetime   = $_shiftRange['endDatetime'];

        $locationCondition = '';
        if ($userRole !== 'admin') {
            $locationCondition = "AND id IN (SELECT user_id from user_locations WHERE status=1 and location_id IN (" . $userLocationId . "))";
        }
        $users = $this->User->query("SELECT *,(SELECT GROUP_CONCAT(name) FROM `user_modules` WHERE user_id=users.id and status=1) as module,(SELECT GROUP_CONCAT(location_name) FROM `user_locations` WHERE user_id=users.id and status=1) as locations FROM `users` WHERE role IN ('cashier','manager') and created BETWEEN '$startDatetime' and '$endDatetime' $locationCondition order by id desc");

        $moduleLabels=moduleswithlabel;
        $yourStartDateValue=$startDate . ' - ' . $endDate;
        $this->set('startdate', $yourStartDateValue);

        $this->set(compact('users'));
        $this->set(compact('moduleLabels'));

    }

    public function cashier_change_status() {
        $this->autoRender = false;

        if ($this->request->is('post')) {
            $userId = $this->request->data['user_id'];

            $this->User->id = $userId;
            $newStatus = $this->User->field('status') == 1 ? 0 : 1;
            if ($this->User->save(['status' => $newStatus,'sync' => 0,'local_sync' => 0])) {
                echo json_encode(['status' => true]);
            } else {
                echo json_encode(['status' => false]);
            }
        }
    }

    public function cancel_booking() {
        $this->autoRender = false;

        if ($this->request->is('post')) {
            $id = $this->request->data['_id'];

            $this->Online_booking->id = $id;
            if ($this->Online_booking->save(['status' => 0,'sync' => 0,'local_sync' => 0])) {
                echo json_encode(['status' => true]);
            } else {
                echo json_encode(['status' => false]);
            }
        }
    }

    public function subadmin_change_status() {
        $this->autoRender = false;

        if ($this->request->is('post')) {
            $userId = $this->request->data['user_id'];

            $this->User->id = $userId;
            $newStatus = $this->User->field('status') == 1 ? 0 : 1;
            if ($this->User->save(['status' => $newStatus,'sync' => 0,'local_sync' => 0])) {
                echo json_encode(['status' => true]);
            } else {
                echo json_encode(['status' => false]);
            }
        }
    }

    public function card_change_status() {
        $this->autoRender = false;

        if ($this->request->is('post')) {
            $Id = $this->request->data['_id'];

            $currentStatus = $this->Card->field('status', ['id' => $Id]);
            $newStatus = in_array($currentStatus, [1, 2]) ? 0 : 1;

            $this->Card->id = $Id;

            if ($this->Card->save(['status' => $newStatus,'sync' => 0,'local_sync' => 0])) {
                echo json_encode(['status' => true]);
            } else {
                echo json_encode(['status' => false]);
            }
        }
    }

    public function category_change_status() {
        $this->autoRender = false;

        if ($this->request->is('post')) {
            $Id = $this->request->data['_id'];

            $this->Catalog_category->id = $Id;
            $newStatus = $this->Catalog_category->field('status') == 1 ? 0 : 1;

            if ($this->Catalog_category->save(['status' => $newStatus,'sync' => 0,'local_sync' => 0])) {
                echo json_encode(['status' => true]);
            } else {
                echo json_encode(['status' => false]);
            }
        }
    }

    public function subcategory_change_status() {
        $this->autoRender = false;

        if ($this->request->is('post')) {
            $Id = $this->request->data['_id'];

            $this->Catalog_subcategory->id = $Id;
            $newStatus = $this->Catalog_subcategory->field('status') == 1 ? 0 : 1;

            if ($this->Catalog_subcategory->save(['status' => $newStatus,'sync' => 0,'local_sync' => 0])) {
                echo json_encode(['status' => true]);
            } else {
                echo json_encode(['status' => false]);
            }
        }
    }

    public function tax_change_status() {
        $this->autoRender = false;

        if ($this->request->is('post')) {
            $Id = $this->request->data['_id'];

            $this->Catalog_tax->id = $Id;
            $newStatus = $this->Catalog_tax->field('status') == 1 ? 0 : 1;

            if ($this->Catalog_tax->save(['status' => $newStatus,'sync' => 0,'local_sync' => 0])) {
                echo json_encode(['status' => true]);
            } else {
                echo json_encode(['status' => false]);
            }
        }
    }

    public function unit_change_status() {
        $this->autoRender = false;

        if ($this->request->is('post')) {
            $Id = $this->request->data['_id'];

            $this->Catalog_unit->id = $Id;
            $newStatus = $this->Catalog_unit->field('status') == 1 ? 0 : 1;

            if ($this->Catalog_unit->save(['status' => $newStatus,'sync' => 0,'local_sync' => 0])) {
                echo json_encode(['status' => true]);
            } else {
                echo json_encode(['status' => false]);
            }
        }
    }

    public function product_change_status() {
        $this->autoRender = false;

        if ($this->request->is('post')) {
            $Id = $this->request->data['_id'];

            $this->Catalog_product->id = $Id;
            $newStatus = $this->Catalog_product->field('status') == 1 ? 0 : 1;

            if ($this->Catalog_product->save(['status' => $newStatus,'sync' => 0,'local_sync' => 0])) {
                echo json_encode(['status' => true]);
            } else {
                echo json_encode(['status' => false]);
            }
        }
    }

    public function sku_change_status() {
        $this->autoRender = false;

        if ($this->request->is('post')) {
            $Id = $this->request->data['_id'];

            $this->Inventory_sku->id = $Id;
            $newStatus = $this->Inventory_sku->field('status') == 1 ? 0 : 1;

            if ($this->Inventory_sku->save(['status' => $newStatus,'sync' => 0,'local_sync' => 0])) {
                echo json_encode(['status' => true]);
            } else {
                echo json_encode(['status' => false]);
            }
        }
    }

    public function semifinished_change_status() {
        $this->autoRender = false;

        if ($this->request->is('post')) {
            $Id = $this->request->data['_id'];

            $this->Inventory_semifinished->id = $Id;
            $newStatus = $this->Inventory_semifinished->field('status') == 1 ? 0 : 1;

            if ($this->Inventory_semifinished->save(['status' => $newStatus,'sync' => 0,'local_sync' => 0])) {
                $this->Inventory_semifinished_tax->updateAll(
                    [
                        'status' => $newStatus,
                        'sync' => 0,
                        'local_sync' => 0
                    ],
                    ['product_id' => $Id]
                );

                echo json_encode(['status' => true]);
            } else {
                echo json_encode(['status' => false]);
            }
        }
    }

    public function vendor_change_status() {
        $this->autoRender = false;

        if ($this->request->is('post')) {
            $userId = $this->request->data['_id'];

            $this->Inventory_vendor->id = $userId;
            $newStatus = $this->Inventory_vendor->field('status') == 1 ? 0 : 1;
            if ($this->Inventory_vendor->save(['status' => $newStatus,'sync' => 0,'local_sync' => 0])) {
                $this->Inventory_vendor_service_area->updateAll(
                    [
                        'status' => $newStatus,
                        'sync' => 0,
                        'local_sync' => 0
                    ],
                    ['vendor_id' => $userId]
                );

                $this->Inventory_vendor_sku_served->updateAll(
                    [
                        'status' => $newStatus,
                        'sync' => 0,
                        'local_sync' => 0
                    ],
                    ['vendor_id' => $userId]
                );

                echo json_encode(['status' => true]);
            } else {
                echo json_encode(['status' => false]);
            }
        }
    }

    public function indent_change_status() {
        $this->autoRender = false;

        if ($this->request->is('post')) {
            $userId = $this->request->data['_id'];

            $this->Inventory_internal_indent->id = $userId;
            $newStatus = $this->Inventory_internal_indent->field('status') == 1 ? 0 : 1;
            if ($this->Inventory_internal_indent->save(['status' => $newStatus,'sync' => 0,'local_sync' => 0])) {
                echo json_encode(['status' => true]);
            } else {
                echo json_encode(['status' => false]);
            }
        }
    }

    public function location_change_status() {
        $this->autoRender = false;

        if ($this->request->is('post')) {
            $Id = $this->request->data['_id'];

            $this->Location->id = $Id;
            $newStatus = $this->Location->field('status') == 1 ? 0 : 1;

            if ($this->Location->save(['status' => $newStatus,'sync' => 0,'local_sync' => 0])) {
                echo json_encode(['status' => true]);
            } else {
                echo json_encode(['status' => false]);
            }
        }
    }

    public function table_change_status() {
        $this->autoRender = false;

        if ($this->request->is('post')) {
            $Id = $this->request->data['_id'];

            $this->Order_table->id = $Id;

            $currentStatus = $this->Order_table->field('status', ['id' => $Id]);
            $newStatus = in_array($currentStatus, [1, 2,3]) ? 0 : 1;

            if ($this->Order_table->save(['status' => $newStatus,'sync' => 0,'local_sync' => 0])) {
                echo json_encode(['status' => true]);
            } else {
                echo json_encode(['status' => false]);
            }
        }
    }

    public function table_category_change_status() {
        $this->autoRender = false;

        if ($this->request->is('post')) {
            $Id = $this->request->data['_id'];

            $this->Table_category->id = $Id;

            $currentStatus = $this->Table_category->field('status', ['id' => $Id]);
            $newStatus = ($currentStatus == 1) ? 0 : 1;

            if ($this->Table_category->save(['status' => $newStatus, 'sync' => 0, 'local_sync' => 0])) {
                echo json_encode(['status' => true]);
            } else {
                echo json_encode(['status' => false]);
            }
        }
    }

    public function coupon_change_status() {
        $this->autoRender = false;

        if ($this->request->is('post')) {
            $Id = $this->request->data['_id'];

            $this->Coupon->id = $Id;

            $newStatus = $this->Coupon->field('status') == 1 ? 0 : 1;
            if ($this->Coupon->save(['status' => $newStatus,'sync' => 0,'local_sync' => 0])) {
                echo json_encode(['status' => true]);
            } else {
                echo json_encode(['status' => false]);
            }
        }
    }

    public function remove_subcategory() {
        $this->autoRender = false;

        if ($this->request->is('post')) {
            $Id = $this->request->data['_id'];

            $this->Catalog_subcategory->id = $Id;
            if ($this->Catalog_subcategory->save(['status' => 0,'sync' => 0,'local_sync' => 0])) {

                $categoryid = $this->Catalog_subcategory->query("SELECT cat_id FROM catalog_subcategory where id=$Id");
                $cat_id=$categoryid['0']['catalog_subcategory']['cat_id'];

                $subcategorychk=$this->Catalog_subcategory->query("SELECT * FROM catalog_subcategory where cat_id=$cat_id and status=1");
                if($subcategorychk){
                    $this->Catalog_category->query("UPDATE catalog_category SET is_subcategory=1,sync = 0,local_sync=0 WHERE id=$cat_id");
                }else{
                    $this->Catalog_category->query("UPDATE catalog_category SET is_subcategory=0,sync = 0,local_sync=0 WHERE id=$cat_id");
                }

                echo json_encode(['status' => true]);
            } else {
                echo json_encode(['status' => false]);
            }
        }
    }

    public function category_list() {
        if(!$this->Session->check('Auth.User.id')){
            $this->redirect(array('controller'=>'dashboard','action' => 'login'));
        }

        $this->layout = "users";

        $startDate = date('m/d/Y', strtotime('-12 months'));
        $endDate = date('m/d/Y');

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

        $_shiftRange = $this->getShiftDateRange($startdate, $enddate);
        $startDatetime = $_shiftRange['startDatetime'];
        $endDatetime   = $_shiftRange['endDatetime'];

        $users = $this->Catalog_category->find('all', array(
            'conditions' => array('created BETWEEN ? AND ?' => array($startDatetime , $endDatetime)
            ),
            'order' => array(
                'id' => 'DESC'
            )
        ));
        $yourStartDateValue=$startDate . ' - ' . $endDate;
        $this->set('startdate', $yourStartDateValue);

        $this->set(compact('users'));

    }

    public function subcategory_list() {
        if(!$this->Session->check('Auth.User.id')){
            $this->redirect(array('controller'=>'dashboard','action' => 'login'));
        }

        $this->layout = "users";

        $startDate = date('m/d/Y', strtotime('-12 months'));
        $endDate = date('m/d/Y');

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

        $_shiftRange = $this->getShiftDateRange($startdate, $enddate);
        $startDatetime = $_shiftRange['startDatetime'];
        $endDatetime   = $_shiftRange['endDatetime'];

        $users = $this->Catalog_subcategory->query("SELECT *,(SELECT name FROM catalog_category where id=catalog_subcategory.cat_id) as cat_name FROM catalog_subcategory where created between '$startDatetime' and  '$endDatetime' ORDER BY id desc");
        $yourStartDateValue=$startDate . ' - ' . $endDate;
        $this->set('startdate', $yourStartDateValue);

        $this->set(compact('users'));

    }

    public function card_list()
    {
        if(!$this->Session->check('Auth.User.id')){
            $this->redirect(array('controller'=>'dashboard','action' => 'login'));
        }

        $this->layout = "users";
        $startDate = date('m/d/Y', strtotime('-12 months'));
        $endDate = date('m/d/Y');

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

        $_shiftRange = $this->getShiftDateRange($startdate, $enddate);
        $startDatetime = $_shiftRange['startDatetime'];
        $endDatetime   = $_shiftRange['endDatetime'];

        $users = $this->Card->find('all', array(
            'conditions' => array(
                'Card.created BETWEEN ? AND ?' => array($startDatetime,$endDatetime)
            ),
            'order' => array(
                'Card.id' => 'DESC'
            )
        ));

        $yourStartDateValue=$startDate . ' - ' . $endDate;
        $this->set('startdate', $yourStartDateValue);
        $this->set(compact('users'));

    }

    public function card_listexport()
    {
        $date_range= $_GET['sdate'];
        $dates = explode(" - ", $date_range);
        $start_date = trim($dates[0]);
        $end_date = trim($dates[1]);
        $startdate= date('Y-m-d', strtotime($start_date));
        $enddate= date('Y-m-d', strtotime($end_date));

        $_shiftRange = $this->getShiftDateRange($startdate, $enddate);
        $startDatetime = $_shiftRange['startDatetime'];
        $endDatetime   = $_shiftRange['endDatetime'];

        $cards = $this->Card->find('all', array(
            'conditions' => array(
                'Card.created BETWEEN ? AND ?' => array($startDatetime, $endDatetime)
            ),
            'order' => array(
                'Card.id' => 'DESC'
            )
        ));

        $datas=array();

        foreach($cards as $card){
            $datas[]=array(
                'cardnumber'=>$card['Card']['cardnumber'],
                'cardbalance'=>$card['Card']['cardbalance'],
                'name'=>$card['Card']['name'],
                'mobile'=>$card['Card']['mobile'],
                'status'=>$card['Card']['status'],
                'created'=>$card['Card']['created']
            );
        }

        App::import('Vendor', 'PHPExcel/PHPExcel');
           error_reporting(0);
           ini_set('display_errors', TRUE);
           ini_set('display_startup_errors', TRUE);
           if (PHP_SAPI == 'cli')
                die('This example should only be run from a Web Browser');
           $objPHPExcel = new PHPExcel();

           $objPHPExcel->getProperties()->setCreator("Aether")
           ->setLastModifiedBy("Aether")
           ->setTitle("Office 2007 XLSX Aether Document")
           ->setSubject("Office 2007 XLSX Aether Document")
           ->setDescription("Aether Export Data File.")
           ->setKeywords("office 2007 openxml php")
           ->setCategory("AetherPos Export Data File");

           $objPHPExcel->setActiveSheetIndex(0)->setCellValue('A1', '   ' .'CARD LIST FROM '.$startdate.' TO '.$enddate);

            $objPHPExcel->setActiveSheetIndex(0)->setCellValue('A3', 'SR. NO.');
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue('B3', 'Card No.');
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue('C3', 'Card Balance');
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue('D3', 'Name');
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue('E3', 'Mobile');
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue('F3', 'Status');
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue('G3', 'Created');
            $objPHPExcel->getActiveSheet()->getStyle("A1:G3")->getFont()->setBold(true);
            $objPHPExcel->getActiveSheet()->mergeCells('A1:G1');

            $baseRow = 3;
            $r=1;

            foreach($datas as $dat){

                  if($dat['status']==0){
                      $status='InActive';
                  }else{
                      $status='Active';
                  }

                  $objPHPExcel->setActiveSheetIndex(0)->setCellValue('A'.($baseRow + $r),$r);
                  $objPHPExcel->setActiveSheetIndex(0)->setCellValue('B'.($baseRow + $r),$dat['cardnumber']);
                  $objPHPExcel->setActiveSheetIndex(0)->setCellValue('C'.($baseRow + $r),$dat['cardbalance']);
                  $objPHPExcel->setActiveSheetIndex(0)->setCellValue('D'.($baseRow + $r),$dat['name']);
                  $objPHPExcel->setActiveSheetIndex(0)->setCellValue('E'.($baseRow + $r),$dat['mobile']);
                  $objPHPExcel->setActiveSheetIndex(0)->setCellValue('F'.($baseRow + $r),$status);
                  $objPHPExcel->setActiveSheetIndex(0)->setCellValue('G'.($baseRow + $r),$dat['created']);

                           $r++;

                }

               $objPHPExcel->getActiveSheet()->setTitle('Export Data');

               $objPHPExcel->setActiveSheetIndex(0);

               header('Content-Type: application/vnd.ms-excel');
               header('Content-Disposition: attachment;filename="'.'CARD LIST.xls"');
               header('Cache-Control: max-age=0');

               header('Cache-Control: max-age=1');

               header ('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
               header ('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT');
               header ('Cache-Control: cache, must-revalidate');
               header ('Pragma: public');
               $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
               $objWriter->save('php://output');
               exit;

    }

    public function profile()
    {
        if(!$this->Session->check('Auth.User.id')){
            $this->redirect(array('controller'=>'dashboard','action' => 'login'));
        }

        $this->layout = "users";
        $id=$this->Session->read('Auth.User.empID');
        $userdetail=$this->Employee->find('first',array('conditions'=>array('Employee.empID'=>$id)));
        $this->set('userdetail', $userdetail);
    }

    public function create_cashier()
    {
        if(!$this->Session->check('Auth.User.id')){
            $this->redirect(array('controller'=>'dashboard','action' => 'login'));
        }

        $this->layout = "users";

        $authUser = $this->Session->read('Auth.User');
        $userRole = $authUser['role'] ?? '';
        $userLocationId = $authUser['location_ids'] ?? '';

        $conditions = ['status' => 1];

        if ($userRole !== 'admin' && !empty($userLocationId)) {
            $ids = array_filter(array_map('trim', explode(',', $userLocationId)));
            if (!empty($ids)) {
                $conditions['id IN'] = $ids;
            }
        }

        $modules=moduleswithlabel;
        $this->set(compact('modules'));

        $locations=$this->Location->find('all',['conditions' => $conditions]);
        $this->set(compact('locations'));

        if ($this->request->is(array('post', 'put'))) {

            $name = $this->data['User']['name'];
            if($name==''){
                $this->Flash->error(__('The Name could not be Blank'));
                return $this->redirect(array('action' => 'create_cashier'));
            }

            $mobile = $this->data['User']['mobile'];
            $username = $this->data['User']['username'];

            $userdetail=$this->User->find('first',array('conditions'=>array('User.username'=>$username)));
            if($userdetail){
                $this->Flash->error(__('Username already exist'));
                return $this->redirect(array('action' => 'create_cashier'));
            }
            $password =$this->data['User']['password'];
            $password = AuthComponent::password($password);

            $modules=moduleswithlabel;
            $checkedModules = [];

            $bill_location =$this->data['User']['bill_location'];
            $loc_type =$this->data['User']['loc_type'];

            $role =$this->data['User']['role'];
            $settlement =$this->data['User']['settlement'];

            if($role=='manager'){
                $pin =$this->data['User']['pin'] ?? 0;
            }else{
                $pin = 0;
            }

            if($loc_type==0){
                $location_ids =[];
            }else{
                $location_ids =$this->data['User']['location'];
            }

            $locationdetail = $this->Location->find('first', array(
                                'conditions' => array('Location.id' => $bill_location)
                            ));

            if (!empty($locationdetail)) {
                $locationnamee = $locationdetail['Location']['name'];
            }

            $data['User']=array(
                'name'=>$name,
                'username'=>$username,
                'password'=>$password,
                'mobile'=>$mobile,
                'can_settlement'=>$settlement,
                'role'=>$role,
                'pin'=>$pin,
                'location_id'=>$bill_location,
                'location_name'=>$locationnamee,
                'sync'=>0,
                'local_sync'=>0
            );

            if($this->User->saveAll($data['User']))
            {
                $lastInsertedID = $this->User->id;
                foreach($location_ids as $location_id){
                    $locationdetail=$this->Location->find('first',array('conditions'=>array('Location.id'=>$location_id)));
                    $locationname=$locationdetail['Location']['name'];

                    $isBill = (!empty($bill_location) && (
                        (is_array($bill_location) && in_array($location_id, $bill_location)) ||
                        (!is_array($bill_location) && $bill_location == $location_id)
                   )) ? 1 : 0;

                    $data['User_location']=array(
                        'user_id'=>$lastInsertedID,
                        'user_name'=>$username,
                        'location_id'=>$location_id,
                        'location_name'=>$locationname,
                        'billing_location'=>$isBill,
                        'sync'=>0,
                        'local_sync'=>0
                    );

                    if($this->User_location->saveAll($data['User_location']))
                    {

                    }else{
                        $this->Flash->error(__('The user Locations could not be saved. Please, try again.'));
                    }
                }

                if (!empty($bill_location)) {
                    $billLocations = is_array($bill_location) ? $bill_location : array($bill_location);

                    foreach ($billLocations as $billLoc) {
                        if (!in_array($billLoc, $location_ids)) {
                            $locationdetail = $this->Location->find('first', array(
                                'conditions' => array('Location.id' => $billLoc)
                            ));

                            if (!empty($locationdetail)) {
                                $locationname = $locationdetail['Location']['name'];

                                $data['User_location'] = array(
                                    'user_id'       => $lastInsertedID,
                                    'user_name'     => $username,
                                    'location_id'   => $billLoc,
                                    'location_name' => $locationname,
                                    'billing_location' => 1,
                                    'sync'=>0,
                                    'local_sync'=>0
                                );

                                $this->User_location->create();
                                if (!$this->User_location->save($data['User_location'])) {
                                    $this->Flash->error(__('The extra bill Location could not be saved. Please, try again.'));
                                }
                            }
                        }
                    }
                }

                foreach ($modules as $moduleKey => $moduleLabel) {
                    if (isset($this->data['User'][$moduleKey]) && $this->data['User'][$moduleKey]) {
                        $checkedModules[$moduleKey] = $this->data['User'][$moduleKey];
                        $data['User_module']=array(
                            'name'=>$moduleKey,
                            'user_id'=>$lastInsertedID,
                            'sync'=>0,
                            'local_sync'=>0
                        );
                        if($this->User_module->saveAll($data['User_module']))
                        {
                        }else{
                            $this->Flash->error(__('The user modules could not be saved. Please, try again.'));
                        }
                    }
                }

                $this->Flash->success(__('The User has been saved.'));
                return $this->redirect(array('action' => 'cashier_list'));
            } else {
                $this->Flash->error(__('The user could not be saved. Please, try again.'));
            }
        }

    }

    public function create_booking()
    {
        if(!$this->Session->check('Auth.User.id')){
            $this->redirect(array('controller'=>'dashboard','action' => 'login'));
        }
        $this->layout = "users";

        if ($_POST['bookdate']) {
            $bookdate=$_POST['bookdate'];
            $booktime=$_POST['booktime'];
        }else{
            $bookdate= date('Y-m-d');
            $currentHour = date('H');
            $defaultTime = '22';
            $booktime=($currentHour < 22) ? $defaultTime : $currentHour;
        }
        $newbookdate= date('d-m-Y', strtotime($bookdate));
        $this->set('bookdate', $newbookdate);
        $this->set('booktime', $booktime);
        $booking_time=$bookdate.' '.$booktime.':00:00';

        $adddatetime = new DateTime($booking_time);
        $adddatetime->modify('+4 hours');
        $add_booking_time = $adddatetime->format('Y-m-d H:i:s');

        $subdatetime = new DateTime($booking_time);
        $subdatetime->modify('-4 hours');
        $sub_booking_time = $subdatetime->format('Y-m-d H:i:s');

        $tables=$this->Order_table->query("SELECT *,(SELECT id FROM `online_bookings` WHERE booking_time BETWEEN '$sub_booking_time' and '$add_booking_time' and status=1 and table_number=order_tables.number) as bookstatus FROM `order_tables`");
        $this->set(compact('tables'));

    }

    public function book_now()
    {
        $this->layout = false;
        $id = $this->data['User']['id'];
        if ($id) {

            $bookdate = $this->data['User']['bookdate'];
            $booktime = $this->data['User']['booktime'];

            if($booktime==10){
                $booktime=22;
            }
            if($booktime==11){
                $booktime=23;
            }

            $booking_time=$bookdate.' '.$booktime.':00:00';

            $adddatetime = new DateTime($booking_time);
            $adddatetime->modify('+4 hours');
            $add_booking_time = $adddatetime->format('Y-m-d H:i:s');

            $subdatetime = new DateTime($booking_time);
            $subdatetime->modify('-4 hours');
            $sub_booking_time = $subdatetime->format('Y-m-d H:i:s');

            $table = $this->Online_booking->query("SELECT * FROM `online_bookings` WHERE booking_time BETWEEN '$sub_booking_time' and '$add_booking_time' and status=1 and table_number=$id");

            if(!$table){
                $ref_id = $this->data['User']['ref_id'];
                $pax = $this->data['User']['pax'];
                $cover_charge=$this->data['User']['cover_charge'];
                $name = $this->data['User']['name'];
                $mobile = $this->data['User']['phone'];
                $remarks = $this->data['User']['remarks'];
                $prepaid=$this->data['User']['prepaid'];
                $payment_type=1;
                if($prepaid==1){
                    $payment_type=2;
                }
                if($_POST['pay_type']){
                    $pay_type=$_POST['pay_type'];
                    if($pay_type==1){
                        $pay_type_value='cash';
                    }
                    if($pay_type==2){
                        $pay_type_value='card';
                    }
                    if($pay_type==6){
                        $pay_type_value='upi';
                    }
                }else{
                    $pay_type=0;
                    $pay_type_value='';
                }

                $booking_time=$bookdate.' '.$booktime.':00:00';
                $booking_time= date('Y-m-d H:i:s', strtotime($booking_time));
                $usrid = $this->Session->read('Auth.User.id');

                $data['Online_booking']=array(
                    'table_number'=>$id,
                    'reference_id'=>$ref_id,
                    'pax'=>$pax,
                    'cover_charge'=>$cover_charge,
                    'user_id'=>$usrid,
                    'name'=>$name,
                    'phone'=>$mobile,
                    'booking_time'=>$booking_time,
                    'payment_type'=>$payment_type,
                    'pay_option'=>$pay_type,
                    'pay_option_value'=>$pay_type_value,
                    'remarks'=>$remarks,
                    'sync'=>0,
                    'local_sync'=>0
                );

                if($this->Online_booking->saveAll($data['Online_booking']))
                {

                    $this->Flash->success(__('The Booking has been saved.'));
                    return $this->redirect(array('action' => 'create_booking'));
                }else{
                    $this->Flash->error(__('Table not booked,try again'));
                    return $this->redirect(array('action' => 'create_booking'));
                }
            }else{
                $this->Flash->error(__('Table Already Booked!! kindly try again with another table !'));
                return $this->redirect(array('action' => 'create_booking'));
            }
        }else{
            if($this->request->is('ajax'))
            {
                $bookdate = $this->request->data['bookdate'];
                $booktime = $this->request->data['booktime'];
                $number=$this->request->data['number'];

                $newbookdate= date('d-m-Y', strtotime($bookdate));
                $this->set('bookdate', $newbookdate);
                $this->set('booktime', $booktime);
                $booking_time=$bookdate.' '.$booktime.':00:00';

                $adddatetime = new DateTime($booking_time);
                $adddatetime->modify('+4 hours');
                $add_booking_time = $adddatetime->format('Y-m-d H:i:s');

                $subdatetime = new DateTime($booking_time);
                $subdatetime->modify('-4 hours');
                $sub_booking_time = $subdatetime->format('Y-m-d H:i:s');

                $tablesdata=$this->Order_table->query("SELECT *,(SELECT id FROM `online_bookings` WHERE booking_time BETWEEN '$sub_booking_time' and '$add_booking_time' and status=1 and table_number=$number) as bookstatus FROM `order_tables` where number=$number");
                if(!isset($tablesdata[0]['bookstatus'])){
                    $this->set(compact('tablesdata'));
                } else {
                    echo 'Table not Vacant!! Kindly refresh again';
                }
            } else {
                $this->Flash->error(__('Invalid Request'));
                return $this->redirect(array('action' => 'create_booking'));
            }
        }
   }

    public function edit_booking()
    {
        if(!$this->Session->check('Auth.User.id')){
            $this->redirect(array('controller'=>'dashboard','action' => 'login'));
        }
        $this->layout = "users";

        if ($_REQUEST['id']) {
            $id=$_REQUEST['id'];
            $tabledetails=$this->Online_booking->query("SELECT * FROM `online_bookings` WHERE id=$id");
            $booking_time=$tabledetails[0]['online_bookings']['booking_time'];
            $table_number=$tabledetails[0]['online_bookings']['table_number'];
            $name=$tabledetails[0]['online_bookings']['name'];
            $phone=$tabledetails[0]['online_bookings']['phone'];
            $cover_charge=$tabledetails[0]['online_bookings']['cover_charge'];
            $pax=$tabledetails[0]['online_bookings']['pax'];
            $booking_date= date('Y-m-d', strtotime($booking_time));
            $booking_times= date('H', strtotime($booking_time));

            $bookdate=$booking_date;
            $booktime=$booking_times;

            $payment_type=$tabledetails[0]['online_bookings']['payment_type'];
            $pay_option=$tabledetails[0]['online_bookings']['pay_option'];
            $pay_option_value=$tabledetails[0]['online_bookings']['pay_option_value'];
            $remarks=$tabledetails[0]['online_bookings']['remarks'];
            $usrid = $this->Session->read('Auth.User.id');

            $newbookdate= date('d-m-Y', strtotime($bookdate));
            $this->set('bookdate', $newbookdate);
            $this->set('booktime', $booktime);
            $this->set('table_number', $table_number);
            $this->set('user_id', $usrid);
            $this->set('name', $name);
            $this->set('phone', $phone);
            $this->set('cover_charge', $cover_charge);
            $this->set('pax', $pax);
            $this->set('booking_id', $id);
            $this->set('payment_type', $payment_type);
            $this->set('pay_option', $pay_option);
            $this->set('pay_option_value', $pay_option_value);
            $this->set('remarks', $remarks);

            $booking_time=$bookdate.' '.$booktime.':00:00';

            $adddatetime = new DateTime($booking_time);
            $adddatetime->modify('+4 hours');
            $add_booking_time = $adddatetime->format('Y-m-d H:i:s');

            $subdatetime = new DateTime($booking_time);
            $subdatetime->modify('-4 hours');
            $sub_booking_time = $subdatetime->format('Y-m-d H:i:s');

            $tables=$this->Order_table->query("SELECT *,(SELECT id FROM `online_bookings` WHERE booking_time BETWEEN '$sub_booking_time' and '$add_booking_time' and status=1 and table_number=order_tables.number) as bookstatus FROM `order_tables` where number!=$table_number");
            $this->set(compact('tables'));
        }else{
            $this->Flash->error(__('Invalid Request'));
            return $this->redirect(array('action' => 'create_booking'));
        }
    }

    public function edit_book_now()
    {
        $this->layout = false;
        $id = $this->data['User']['id'];
        if ($id) {
            $bookdate = $this->data['User']['bookdate'];
            $booktime = $this->data['User']['booktime'];

            if($booktime==10){
                $booktime=22;
            }
            if($booktime==11){
                $booktime=23;
            }

            $booking_time=$bookdate.' '.$booktime.':00:00';

            $adddatetime = new DateTime($booking_time);
            $adddatetime->modify('+4 hours');
            $add_booking_time = $adddatetime->format('Y-m-d H:i:s');

            $subdatetime = new DateTime($booking_time);
            $subdatetime->modify('-4 hours');
            $sub_booking_time = $subdatetime->format('Y-m-d H:i:s');

            $table = $this->Online_booking->query("SELECT * FROM `online_bookings` WHERE booking_time BETWEEN '$sub_booking_time' and '$add_booking_time' and status=1 and table_number=$id");

            if(!$table){
                $ref_id = $this->data['User']['ref_id'];
                $pax = $this->data['User']['pax'];
                $cover_charge=$this->data['User']['cover_charge'];
                $name = $this->data['User']['name'];
                $mobile = $this->data['User']['phone'];
                $remarks = $this->data['User']['remarks'];
                $payment_type=$this->data['User']['payment_type'];
                $pay_type=$this->data['User']['pay_option'];
                $pay_type_value=$this->data['User']['pay_option_value'];
                $booking_id=$this->data['User']['booking_id'];

                $booking_time= date('Y-m-d H:i:s', strtotime($booking_time));

                $data['Online_booking']=array(
                    'table_number'=>$id,
                    'reference_id'=>$ref_id,
                    'pax'=>$pax,
                    'cover_charge'=>$cover_charge,
                    'name'=>$name,
                    'phone'=>$mobile,
                    'booking_time'=>$booking_time,
                    'payment_type'=>$payment_type,
                    'pay_option'=>$pay_type,
                    'pay_option_value'=>$pay_type_value,
                    'remarks'=>$remarks,
                    'sync'=>0,
                    'local_sync' => 0
                );

                if($this->Online_booking->saveAll($data['Online_booking']))
                {
                    $lastInsertedID = $this->Online_booking->id;
                    $this->Online_booking->query("UPDATE online_bookings SET status='2',switch_to='$lastInsertedID',sync = 0,local_sync=0 WHERE id =". $booking_id);

                    $this->Flash->success(__('The Booking has been Updated.'));
                    return $this->redirect(array('action' => 'booking_list'));
                }else{
                    $this->Flash->error(__('Table not booked,try again'));
                    return $this->redirect(array('action' => 'booking_list'));
                }
            }else{
                $this->Flash->error(__('Table Already Booked!! kindly try again with another table !'));
                return $this->redirect(array('action' => 'edit_booking?id='.$booking_id));
            }
        }else{
            if($this->request->is('ajax'))
            {
                $booking_id = $this->request->data['booking_id'];
                $cover_charge = $this->request->data['cover_charge'];
                $pax = $this->request->data['pax'];
                $bookdate = $this->request->data['bookdate'];
                $booktime = $this->request->data['booktime'];
                $name = $this->request->data['name'];
                $phone = $this->request->data['phone'];
                $number=$this->request->data['number'];
                $payment_type=$this->request->data['payment_type'];
                $pay_option=$this->request->data['pay_option'];
                $pay_option_value=$this->request->data['pay_option_value'];
                $remarks=$this->request->data['remarks'];
                $prev_table_number=$this->request->data['prev_table_number'];
                $booking_id=$this->request->data['booking_id'];
                $newbookdate= date('d-m-Y', strtotime($bookdate));

                $this->set('bookdate', $newbookdate);
                $this->set('booktime', $booktime);
                $this->set('name', $name);
                $this->set('phone', $phone);
                $this->set('booking_id', $booking_id);
                $this->set('prev_table_number', $prev_table_number);
                $this->set('payment_type', $payment_type);
                $this->set('pay_option', $pay_option);
                $this->set('pay_option_value', $pay_option_value);
                $this->set('remarks', $remarks);

                $booking_time=$bookdate.' '.$booktime.':00:00';

                $adddatetime = new DateTime($booking_time);
                $adddatetime->modify('+4 hours');
                $add_booking_time = $adddatetime->format('Y-m-d H:i:s');

                $subdatetime = new DateTime($booking_time);
                $subdatetime->modify('-4 hours');
                $sub_booking_time = $subdatetime->format('Y-m-d H:i:s');

                $tablesdata=$this->Order_table->query("SELECT *,(SELECT id FROM `online_bookings` WHERE booking_time BETWEEN '$sub_booking_time' and '$add_booking_time' and status=1 and table_number=$number) as bookstatus FROM `order_tables` where number=$number");

                if(!isset($tablesdata[0]['bookstatus'])){
                    $this->set(compact('tablesdata'));
                } else {
                    echo 'Table not Vacant!! Kindly refresh again';
                }
            } else {
                $this->Flash->error(__('Invalid Request'));
                return $this->redirect(array('action' => 'booking_list'));
            }
        }
   }

    public function edit_cashier()
    {
        if(!$this->Session->check('Auth.User.id')){
            $this->redirect(array('controller'=>'dashboard','action' => 'login'));
        }

        $this->layout = "users";

        if ($this->data['User']['id']!='') {

            $id=$this->data['User']['id'];
            $name = $this->data['User']['name'];
            if($name==''){
                $this->Flash->error(__('The Name could not be Blank'));
                return $this->redirect(array('action' => 'edit_cashier?id='.$id));
            }

            $mobile = $this->data['User']['mobile'];
            $password = $this->data['User']['password'];
            $userdetails=$this->User->find('first',array('conditions'=>array('User.id'=>$id)));
            $username=$userdetails['User']['username'];

            $location_ids =$this->data['User']['location'];

            $role =$this->data['User']['role'];
            $settlement=$this->data['User']['settlement'];

            $bill_location =$this->data['User']['bill_location'];
            $loc_type =$this->data['User']['loc_type'];

            if($role=='manager'){
                $pin =$this->data['User']['pin'] ?? 0;
            }else{
                $pin = 0;
            }
            if($loc_type==0){
                $location_ids =[];
            }else{
                $location_ids =$this->data['User']['location'];
            }

            $locationdetail = $this->Location->find('first', array(
                'conditions' => array('Location.id' => $bill_location)
            ));

            if (!empty($locationdetail)) {
                $locationnamee = $locationdetail['Location']['name'];
            }

            $this->User->query("UPDATE users SET name ='$name',mobile='$mobile',role='$role',pin='$pin',can_settlement='$settlement',location_id='$bill_location',location_name='$locationnamee',sync = 0,local_sync=0 WHERE id =". $id);
            if($password){
                $password = AuthComponent::password($password);
                $this->User->query("UPDATE users SET password ='$password',sync = 0,local_sync=0 WHERE id =". $id);
            }

            $this->User_location->query("UPDATE user_locations SET status=0,sync = 0,local_sync=0 WHERE user_id =". $id);

            foreach($location_ids as $location_id){
                $locationdetail=$this->Location->find('first',array('conditions'=>array('Location.id'=>$location_id)));
                $locationname=$locationdetail['Location']['name'];

                $isBill = (!empty($bill_location) && (
                    (is_array($bill_location) && in_array($location_id, $bill_location)) ||
                    (!is_array($bill_location) && $bill_location == $location_id)
               )) ? 1 : 0;

                $data['User_location']=array(
                    'user_id'=>$id,
                    'user_name'=>$username,
                    'location_id'=>$location_id,
                    'location_name'=>$locationname,
                    'billing_location'=>$isBill,
                    'sync' => 0,
                    'local_sync' => 0
                );

                if($this->User_location->saveAll($data['User_location']))
                {
                }else{
                    $this->Flash->error(__('The user Locations could not be saved. Please, try again.'));
                }
            }

            if (!empty($bill_location)) {
                $billLocations = is_array($bill_location) ? $bill_location : array($bill_location);

                foreach ($billLocations as $billLoc) {
                    if (!in_array($billLoc, $location_ids)) {
                        $locationdetail = $this->Location->find('first', array(
                            'conditions' => array('Location.id' => $billLoc)
                        ));

                        if (!empty($locationdetail)) {
                            $locationname = $locationdetail['Location']['name'];

                            $data['User_location'] = array(
                                'user_id'       => $id,
                                'user_name'     => $username,
                                'location_id'   => $billLoc,
                                'location_name' => $locationname,
                                'billing_location' => 1,
                                'sync' => 0,
                                'local_sync' => 0
                            );

                            $this->User_location->create();
                            if (!$this->User_location->save($data['User_location'])) {
                                $this->Flash->error(__('The extra bill Location could not be saved. Please, try again.'));
                            }
                        }
                    }
                }
            }

            $modules=moduleswithlabel;
            $checkedModules = [];
            $this->User->query("UPDATE user_modules SET status=0,sync = 0,local_sync=0 WHERE user_id =". $id);

            foreach ($modules as $moduleKey => $moduleLabel) {

                if (isset($this->data['User'][$moduleKey]) && $this->data['User'][$moduleKey]) {
                    $checkedModules[$moduleKey] = $this->data['User'][$moduleKey];

                     $data['User_module']=array(
                            'name'=>$moduleKey,
                            'user_id'=>$id,
                            'sync' => 0,
                            'local_sync' => 0
                        );
                        if($this->User_module->saveAll($data['User_module']))
                        {
                        }else{
                            $this->Flash->error(__('The user modules could not be saved. Please, try again.'));
                        }
                }
            }

            $this->Flash->success(__('User has been Updated.'));
            return $this->redirect(array('action' => 'cashier_list'));
        }else{
            $id=$_REQUEST['id'];

            $userdetail=$this->User->find('first',array('conditions'=>array('User.id'=>$id)));
            $this->set(compact('userdetail'));

            $locations=$this->Location->find('all',array('conditions'=>array('status'=>1)));
            $this->set(compact('locations'));

            $selectedlocationss = $this->User_location->find('list', [
                'conditions' => ['User_location.status' => 1, 'User_location.user_id' => $id],
                'fields' => ['User_location.location_id', 'User_location.location_id']
            ]);

            $selectedLocationIds = array_keys($selectedlocationss);
            $hasMultiple = (count($selectedLocationIds) > 1) ? 1 : 0;

            $selectedbillinglocationss = $this->User_location->find('first', [
                'conditions' => ['User_location.status' => 1,'User_location.billing_location' => 1, 'User_location.user_id' => $id],
                'fields' => ['User_location.location_id', 'User_location.location_id']
            ]);

            if (!empty($selectedbillinglocationss)) {
                $billing_location = $selectedbillinglocationss['User_location']['location_id'];
            } else {

                $billing_location = !empty($selectedLocationIds) ? $selectedLocationIds[0] : null;
            }

            $this->set(compact('selectedLocationIds','hasMultiple','billing_location'));

            $selectemodule="SELECT name FROM user_modules where user_id=$id and status=1";
            $selectemodules=$this->User_module->query($selectemodule);
            $this->set(compact('selectemodules'));

            $modules=moduleswithlabel;
            $this->set(compact('modules'));
        }
    }

    public function create_category()
    {
        if(!$this->Session->check('Auth.User.id')){
            $this->redirect(array('controller'=>'dashboard','action' => 'login'));
        }

        $this->layout = "users";

        if ($this->request->is(array('post', 'put'))) {

            $name = $this->data['User']['name'];
            if($name==''){
                $this->Flash->error(__('The Name could not be Blank'));
                return $this->redirect(array('action' => 'create_category'));
            }

            if(isset($_POST['subcat'])){
                $is_subcat=1;
            }else{
                $is_subcat=0;
            }

            $image=$this->data['User']['image'];
            if (!empty($image['name'])) {
                $uploadPath = WWW_ROOT . 'uploads' . DS . 'category_images' . DS;
                $file=basename(time().$image['name']);
                $file = preg_replace('/[^A-Za-z0-9_\-\.]/', '_', $file);
                $uploadFile = $uploadPath . $file;

                if (move_uploaded_file($image['tmp_name'], $uploadFile)) {
                    $filename=$file;
                }else{
                    $filename='';
                }
            }

            $data['Catalog_category']=array(
                'name'=>$name,
                'is_subcategory'=>$is_subcat,
                'image'=>$filename,
                'sync' => 0,
                'local_sync' => 0
            );

            if($this->Catalog_category->saveAll($data['Catalog_category']))
            {
                $lastInsertedID = $this->Catalog_category->id;
                if(isset($_POST['subcat'])){
                    $subcats = $_POST['subcat'];
                    foreach ($subcats as $subcat) {
                        $data['Catalog_subcategory']=array(
                            'cat_id'=>$lastInsertedID,
                            'name'=>$subcat,
                            'sync' => 0,
                            'local_sync' => 0
                        );

                        if($this->Catalog_subcategory->saveAll($data['Catalog_subcategory']))
                        {

                        }else{
                            $this->Flash->error(__('Sub category could not be saved. Please, try again.'));
                        }
                    }
                }

                $this->Flash->success(__('Category has been saved.'));
                return $this->redirect(array('action' => 'category_list'));
            } else {
                $this->Flash->error(__('Category could not be saved. Please, try again.'));
            }
        }
    }

    public function edit_category()
    {
        if(!$this->Session->check('Auth.User.id')){
            $this->redirect(array('controller'=>'dashboard','action' => 'login'));
        }

        $this->layout = "users";
        $id=$_REQUEST['id'];

        $categories = $this->Catalog_category->query("SELECT * FROM catalog_category where id=$id");
        $this->set(compact('categories'));

        $subcategories=$this->Catalog_subcategory->query("SELECT * FROM catalog_subcategory where cat_id=$id and status=1");
        $this->set(compact('subcategories'));

        if ($this->request->is(array('post', 'put'))) {

            $id = $this->data['User']['id'];
            $name = $this->data['User']['name'];
            if($name==''){
                $this->Flash->error(__('The Name could not be Blank'));
                return $this->redirect(array('action' => 'edit_category?id='.$id));
            }

            $image=$this->data['User']['image'];
            if (!empty($image['name'])) {
                $uploadPath = WWW_ROOT . 'uploads' . DS . 'category_images' . DS;
                $file=basename(time().$image['name']);
                $file = preg_replace('/[^A-Za-z0-9_\-\.]/', '_', $file);
                $uploadFile = $uploadPath . $file;

                if (move_uploaded_file($image['tmp_name'], $uploadFile)) {
                    $filename=$file;
                }else{
                    $filename='';
                }
                $this->Catalog_category->query("UPDATE `catalog_category` SET image='$filename',sync = 0,local_sync=0 WHERE id=$id");
            }
            $this->Catalog_category->query("UPDATE `catalog_category` SET name='$name',sync = 0,local_sync=0 WHERE id=$id");

            $lastInsertedID = $id;
            if(isset($_POST['subcat'])){
                $subcats = $_POST['subcat'];
                foreach ($subcats as $subcat) {
                    $data['Catalog_subcategory']=array(
                        'cat_id'=>$lastInsertedID,
                        'name'=>$subcat,
                        'sync' => 0,
                        'local_sync' => 0
                    );

                    if($this->Catalog_subcategory->saveAll($data['Catalog_subcategory']))
                    {

                    }else{
                        $this->Flash->error(__('Sub category could not be saved. Please, try again.'));
                    }
                }
            }

            $subcategorychk=$this->Catalog_subcategory->query("SELECT * FROM catalog_subcategory where cat_id=$id and status=1");
            if($subcategorychk){
                $this->Catalog_category->query("UPDATE `catalog_category` SET is_subcategory='1',sync = 0,local_sync=0 WHERE id=$id");
            }else{
                $this->Catalog_category->query("UPDATE `catalog_category` SET is_subcategory='0',sync = 0,local_sync=0 WHERE id=$id");
            }

            $this->Flash->success(__('Category has been Updated.'));
            return $this->redirect(array('action' => 'category_list'));

        }
    }

    public function create_subcategory()
    {
        if(!$this->Session->check('Auth.User.id')){
            $this->redirect(array('controller'=>'dashboard','action' => 'login'));
        }

        $this->layout = "users";

        if ($this->request->is(array('post', 'put'))) {
            $subcategoriesname=$_POST['subcat'];
            $categoryid = $this->data['User']['category'];
            if($categoryid==''){
                $this->Flash->error(__('Select Category'));
                return $this->redirect(array('action' => 'create_subcategory'));
            }
            $filename='';
            $image=$this->data['User']['image'];
            if (!empty($image['name'])) {
                $uploadPath = WWW_ROOT . 'uploads' . DS . 'category_images' . DS;
                $file=basename(time().$image['name']);
                $file = preg_replace('/[^A-Za-z0-9_\-\.]/', '_', $file);
                $uploadFile = $uploadPath . $file;

                if (move_uploaded_file($image['tmp_name'], $uploadFile)) {
                    $filename=$file;
                }else{
                    $filename='';
                }
            }

            foreach($subcategoriesname as $subcategory){
                $data['Catalog_subcategory']=array(
                    'name'=>$subcategory,
                    'cat_id'=>$categoryid,
                    'image'=>$filename,
                    'sync' => 0,
                    'local_sync' => 0
                );

                if($this->Catalog_subcategory->saveAll($data['Catalog_subcategory']))
                {
                    $savechk=true;

                }else{
                    $savechk=false;
                }

            }

            if($savechk){
                $this->Catalog_category->query("UPDATE `catalog_category` SET is_subcategory='1',sync = 0,local_sync=0 WHERE id=$categoryid");

                $this->Flash->success(__('Sub Category has been saved.'));
                return $this->redirect(array('action' => 'subcategory_list'));
            } else {
                $this->Flash->error(__('Some sub Category could not be saved. Please, try again.'));
                return $this->redirect(array('action' => 'subcategory_list'));
            }
        }

        $categories=$this->Catalog_category->find('all',array('conditions'=>array('status'=>1)));
        $this->set(compact('categories'));

    }

    public function create_tax()
    {
        if(!$this->Session->check('Auth.User.id')){
            $this->redirect(array('controller'=>'dashboard','action' => 'login'));
        }

        $this->layout = "users";

        if ($this->request->is(array('post', 'put'))) {

            $name = $this->data['User']['name'];
            if($name==''){
                $this->Flash->error(__('The Name could not be Blank'));
                return $this->redirect(array('action' => 'create_tax'));
            }
            $tax = $this->data['User']['tax'];
            if($tax==''){
                $this->Flash->error(__('Tax Percentage could not be Blank'));
                return $this->redirect(array('action' => 'create_tax'));
            }

            $data['Catalog_tax']=array(
                'name'=>$name,
                'percentage'=>$tax,
                'sync' => 0,
                'local_sync' => 0
            );

            if($this->Catalog_tax->saveAll($data['Catalog_tax']))
            {
                $this->Flash->success(__('Tax has been saved.'));
                return $this->redirect(array('action' => 'tax_list'));
            } else {
                $this->Flash->error(__('Tax could not be saved. Please, try again.'));
            }
        }
    }

    public function edit_tax()
    {
        if(!$this->Session->check('Auth.User.id')){
            $this->redirect(array('controller'=>'dashboard','action' => 'login'));
        }

        $this->layout = "users";

        $id=$_REQUEST['id'];
        $taxes=$this->Catalog_tax->find('all',array('conditions'=>array('id'=>$id)));
        $this->set(compact('taxes'));

        if ($this->request->is(array('post', 'put'))) {

            $id = $this->data['User']['id'];
            $name = $this->data['User']['name'];
            if($name==''){
                $this->Flash->error(__('The Name could not be Blank'));
                return $this->redirect(array('action' => 'create_tax'));
            }
            $tax = $this->data['User']['tax'];
            if($tax==''){
                $this->Flash->error(__('Tax Percentage could not be Blank'));
                return $this->redirect(array('action' => 'create_tax'));
            }

            $this->Catalog_tax->query("UPDATE `catalog_tax` SET name='$name',percentage='$tax',sync = 0,local_sync=0 WHERE id=$id");

            $this->Flash->success(__('Tax has been updated.'));
            return $this->redirect(array('action' => 'tax_list'));
        }
    }

    public function create_location()
    {
        if(!$this->Session->check('Auth.User.id')){
            $this->redirect(array('controller'=>'dashboard','action' => 'login'));
        }

        $user_id=$this->Session->read('Auth.User.id');

        $this->layout = "users";

        $indiaStates=states;
        $shiftTimeOptions = [];
        foreach (range(0, 23) as $h) {
            foreach (range(0, 59, 5) as $m) {
                $t = sprintf('%02d:%02d:00', $h, $m);
                $shiftTimeOptions[$t] = date('h:i A', strtotime("2000-01-01 " . $t));
            }
        }
        foreach (['05:59:59' => '05:59 AM', '23:59:59' => '11:59 PM'] as $t => $lbl) {
            if (!isset($shiftTimeOptions[$t])) {
                $shiftTimeOptions[$t] = $lbl;
            }
        }
        ksort($shiftTimeOptions);
        $this->set(compact('indiaStates', 'shiftTimeOptions'));

        if ($this->request->is(array('post', 'put'))) {

            $name = $this->data['User']['name'];
            if($name==''){
                $this->Flash->error(__('The Name could not be Blank'));
                return $this->redirect(array('action' => 'create_location'));
            }

            $username = $this->data['User']['username'];
            $userdetail=$this->Location->find('first',array('conditions'=>array('Location.username'=>$username)));
            if($userdetail){
                $this->Flash->error(__('Username already exist'));
                return $this->redirect(array('action' => 'create_location'));
            }
            $password = $this->data['User']['password'];
            $password = AuthComponent::password($password);
            $type = $this->data['User']['type'];
            $state = $this->data['User']['state'];
            $reelo_merchant_id = isset($this->data['User']['reelo_merchant_id']) ? $this->data['User']['reelo_merchant_id'] : '';
            $reelo_customer_key = isset($this->data['User']['reelo_customer_key']) ? $this->data['User']['reelo_customer_key'] : '';
            $service_charge_rate = isset($this->data['User']['service_charge_rate']) ? $this->data['User']['service_charge_rate'] : 0;
            $print_line1 = $this->data['User']['print_line1'];
            $print_line2 = $this->data['User']['print_line2'];
            $print_line3 = $this->data['User']['print_line3'];
            $print_line4 = $this->data['User']['print_line4'];
            $print_line5 = $this->data['User']['print_line5'];
            $print_line6 = $this->data['User']['print_line6'];
            $print_line7 = $this->data['User']['print_line7'];
            $print_line8 = $this->data['User']['print_line8'];
            $print_line9 = $this->data['User']['print_line9'];
            $print_line10 = $this->data['User']['print_line10'];
            $footer_msg = $this->data['User']['footer_msg'];
            $footer_msg2 = $this->data['User']['footer_msg2'];
            $footer_msg3 = $this->data['User']['footer_msg3'];
            $currency_symbol = $this->data['User']['currency_symbol'];
            $shift_start_time = !empty($this->data['User']['shift_start_time']) ? trim($this->data['User']['shift_start_time']) : (defined('shiftstart') ? trim(shiftstart) : '06:00:00');
            $shift_end_time = !empty($this->data['User']['shift_end_time']) ? trim($this->data['User']['shift_end_time']) : (defined('shiftend') ? trim(shiftend) : '06:00:00');
            $shift_type = (strtotime($shift_end_time) <= strtotime($shift_start_time)) ? 'next_day' : 'same_day';

            $data['Location']=array(
                'name'=>$name,
                'username'=>$username,
                'password'=>$password,
                'type'=>$type,
                'state'=>$state,
                'reelo_merchant_id'=>$reelo_merchant_id,
                'reelo_customer_key'=>$reelo_customer_key,
                'service_charge_rate'=>$service_charge_rate,
                'print_line1'=>$print_line1,
                'print_line2'=>$print_line2,
                'print_line3'=>$print_line3,
                'print_line4'=>$print_line4,
                'print_line5'=>$print_line5,
                'print_line6'=>$print_line6,
                'print_line7'=>$print_line7,
                'print_line8'=>$print_line8,
                'print_line9'=>$print_line9,
                'print_line10'=>$print_line10,
                'footer_msg'=>$footer_msg,
                'footer_msg2'=>$footer_msg2,
                'footer_msg3'=>$footer_msg3,
                'currency_symbol'=>$currency_symbol,
                'shift_start_time'=>$shift_start_time,
                'shift_end_time'=>$shift_end_time,
                'shift_type'=>$shift_type,
                'sync' => 0,
                'local_sync' => 0
            );

            if($this->Location->saveAll($data['Location']))
            {
                $lastInsertedID = $this->Location->id;

                if($type=='DC' && ($this->Session->read('Auth.User.role') == 'admin')){
                    $this->User->query("UPDATE users SET location_id ='$lastInsertedID',location_name='$name',sync = 0,local_sync=0 WHERE id =". $user_id);
                    $user = $this->Session->read('Auth.User');
                    $user['location_id'] = $lastInsertedID;
                    $this->Session->write('Auth.User', $user);
                }
                $this->Flash->success(__('Location has been saved.'));
                return $this->redirect(array('action' => 'location_list'));
            } else {
                $this->Flash->error(__('Location could not be saved. Please, try again.'));
            }
        }
    }

    public function create_coupon()
    {
        if(!$this->Session->check('Auth.User.id')){
            $this->redirect(array('controller'=>'dashboard','action' => 'login'));
        }

        $this->layout = "users";

        if ($this->request->is(array('post', 'put'))) {

            $code = $this->data['User']['code'];
            if($code==''){
                $this->Flash->error(__('Coupon Code could not be Blank'));
                return $this->redirect(array('action' => 'create_coupon'));
            }
            $coupon_value = $this->data['User']['coupon_value'];
            $month=$this->data['User']['expiry_date']['month'];
            $day=$this->data['User']['expiry_date']['day'];
            $year=$this->data['User']['expiry_date']['year'];

            $date=$year.'-'.$month.'-'.$day;

            $data['Coupon']=array(
                'code'=>$code,
                'value'=>$coupon_value,
                'expiry_date'=>$date,
                'sync' => 0,
                'local_sync' => 0
            );

            if($this->Coupon->saveAll($data['Coupon']))
            {
                $this->Flash->success(__('Coupon has been saved.'));
                return $this->redirect(array('action' => 'coupon_list'));
            } else {
                $this->Flash->error(__('Coupon could not be saved. Please, try again.'));
            }
        }
    }

    public function edit_coupon()
    {
        if(!$this->Session->check('Auth.User.id')){
            $this->redirect(array('controller'=>'dashboard','action' => 'login'));
        }

        $this->layout = "users";

        if ($this->data['User']['id']!='') {
            $id=$this->data['User']['id'];
            $value = $this->data['User']['value'];
            if($value==''){
                $this->Flash->error(__('Coupon Value could not be Blank'));
                return $this->redirect(array('action' => 'edit_coupon?id='.$id));
            }

            $month=$this->data['User']['expiry']['month'];
            $day=$this->data['User']['expiry']['day'];
            $year=$this->data['User']['expiry']['year'];

            $date=$year.'-'.$month.'-'.$day;

            $this->Coupon->query("UPDATE coupons SET value ='$value',expiry_date='$date',sync = 0,local_sync=0 WHERE id =". $id);
            $this->Flash->success(__('Coupon has been Updated.'));
            return $this->redirect(array('action' => 'coupon_list'));
        }else{
            $id=$_REQUEST['id'];

            $coupons = $this->Coupon->query("SELECT * FROM coupons where id=$id");
            $this->set(compact('coupons'));
        }

    }

    public function create_table()
    {
        if(!$this->Session->check('Auth.User.id')){
            $this->redirect(array('controller'=>'dashboard','action' => 'login'));
        }

        $this->layout = "users";
        $authUser = $this->Session->read('Auth.User');
        $userRole = $authUser['role'] ?? '';
        $userLocationId = $authUser['location_ids'] ?? 0;

        $conditions = ['status' => 1];

        if ($userRole !== 'admin') {

            $userLocationIds = is_array($userLocationId) ? $userLocationId : [$userLocationId];
            $conditions['id IN'] = $userLocationIds;
        }

        $locations = $this->Location->find('all', [
            'conditions' => $conditions
        ]);

        $table_categories = $this->Table_category->find('all', [
            'conditions' => ['Table_category.status' => 1],
            'order' => ['Table_category.name' => 'ASC']
        ]);

        $this->set(compact('locations', 'table_categories'));
        if ($this->request->is(array('post', 'put'))) {

            $number = $this->data['User']['number'];
            if($number==''){
                $this->Flash->error(__('Table Number could not be Blank'));
                return $this->redirect(array('action' => 'create_table'));
            }
            $table_category_id = $this->data['User']['table_category_id'] ?? '';
            if($table_category_id==''){
                $this->Flash->error(__('Table Category could not be Blank'));
                return $this->redirect(array('action' => 'create_table'));
            }
            $cover_charge = $this->data['User']['cover_charge'];
            $weekend_charge=$this->data['User']['weekend_charge'];
            $pax = $this->data['User']['pax'];
            $waiter = $this->data['User']['waiter'];
            $customer = $this->data['User']['customer'];

            $location_id =$this->data['User']['location'];
            $locationdetail=$this->Location->find('first',array('conditions'=>array('Location.id'=>$location_id)));
            $locationname=$locationdetail['Location']['name'];

            $tables=$this->Order_table->find('all',array('conditions'=>array('number'=>$number,'location_id'=>$location_id)));
            if(!empty($tables)){
                $this->Flash->error(__('Table already exist.'));
                return $this->redirect(array('action' => 'table_list'));
            }
            do {
                $pin = uniqid();
            } while ($this->Order_table->find('first', array('fields' => 'Order_table.id', 'conditions' => array('Order_table.reference_id' => $pin))));

            $data['Order_table']=array(
                'number'=>$number,
                'table_category_id'=>$table_category_id,
                'reference_id'=>$pin,
                'cover_charge'=>$cover_charge,
                'weekend_charge'=>$weekend_charge,
                'pax'=>$pax,
                'is_waiter_required'=>$waiter,
                'is_customer_detail_required'=>$customer,
                'location_id'=>$location_id,
                'location_name'=>$locationname,
                'sync' => 0,
                'local_sync' => 0
            );

            if($this->Order_table->saveAll($data['Order_table']))
            {
                $this->Flash->success(__('Table has been saved.'));
                return $this->redirect(array('action' => 'table_list'));
            } else {
                $this->Flash->error(__('Table could not be saved. Please, try again.'));
            }
        }
    }

    public function edit_table()
    {
        if(!$this->Session->check('Auth.User.id')){
            $this->redirect(array('controller'=>'dashboard','action' => 'login'));
        }

        $this->layout = "users";

        $authUser = $this->Session->read('Auth.User');
        $userRole = $authUser['role'] ?? '';
        $userLocationId = $authUser['location_ids'] ?? 0;

        $conditions = ['status' => 1];

        if ($userRole !== 'admin') {

            $userLocationIds = is_array($userLocationId) ? $userLocationId : [$userLocationId];
            $conditions['id IN'] = $userLocationIds;
        }
        if ($this->data['User']['id']!='') {

            $id = $this->data['User']['id'];
            $table_category_id = $this->data['User']['table_category_id'] ?? '';
            if($table_category_id==''){
                $this->Flash->error(__('Table Category could not be Blank'));
                return $this->redirect(array('action' => 'edit_table', '?' => array('id' => $id)));
            }
            $cover_charge = $this->data['User']['cover_charge'];
            $pax = $this->data['User']['pax'];
            $weekend_charge = $this->data['User']['weekend_charge'];
            $waiter = $this->data['User']['waiter'];
            $customer = $this->data['User']['customer'];
            $location = $this->data['User']['location'];
            $locationdetail=$this->Location->find('first',array('conditions'=>array('Location.id'=>$location)));
            $locationname=$locationdetail['Location']['name'];

            $this->Order_table->query("UPDATE order_tables SET table_category_id ='$table_category_id',cover_charge ='$cover_charge',pax='$pax',weekend_charge='$weekend_charge',is_waiter_required='$waiter',is_customer_detail_required='$customer',location_id='$location',location_name='$locationname',sync = 0,local_sync=0 WHERE id =". $id);
            $this->Flash->success(__('Table has been Updated.'));
            return $this->redirect(array('action' => 'table_list'));
        }else{
            $id=$_REQUEST['id'];

            $userdetail=$this->Order_table->find('first',array('conditions'=>array('Order_table.id'=>$id)));
            $this->set(compact('userdetail'));

            $locations=$this->Location->find('all',array('conditions'=>$conditions));
            $table_categories = $this->Table_category->find('all', [
                'conditions' => ['Table_category.status' => 1],
                'order' => ['Table_category.name' => 'ASC']
            ]);
            $this->set(compact('locations', 'table_categories'));
        }
    }

    public function tax_list()
    {
        if(!$this->Session->check('Auth.User.id')){
            $this->redirect(array('controller'=>'dashboard','action' => 'login'));
        }

        $this->layout = "users";

        $startDate = date('m/d/Y', strtotime('-12 months'));
        $endDate = date('m/d/Y');

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

        $_shiftRange = $this->getShiftDateRange($startdate, $enddate);
        $startDatetime = $_shiftRange['startDatetime'];
        $endDatetime   = $_shiftRange['endDatetime'];

        $users = $this->Catalog_tax->query("SELECT * FROM catalog_tax where created between '$startDatetime' and  '$endDatetime' ORDER BY id desc");
        $yourStartDateValue=$startDate . ' - ' . $endDate;
        $this->set('startdate', $yourStartDateValue);

        $this->set(compact('users'));

    }

    public function unit_list()
    {
        if(!$this->Session->check('Auth.User.id')){
            $this->redirect(array('controller'=>'dashboard','action' => 'login'));
        }

        $this->layout = "users";

        $startDate = date('m/d/Y', strtotime('-12 months'));
        $endDate = date('m/d/Y');

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

        $_shiftRange = $this->getShiftDateRange($startdate, $enddate);
        $startDatetime = $_shiftRange['startDatetime'];
        $endDatetime   = $_shiftRange['endDatetime'];

        $users = $this->Catalog_unit->query("SELECT * FROM catalog_units where created between '$startDatetime' and  '$endDatetime' ORDER BY id desc");
        $yourStartDateValue=$startDate . ' - ' . $endDate;
        $this->set('startdate', $yourStartDateValue);

        $this->set(compact('users'));

    }

    public function create_unit()
    {
        if(!$this->Session->check('Auth.User.id')){
            $this->redirect(array('controller'=>'dashboard','action' => 'login'));
        }

        $this->layout = "users";

        if ($this->request->is(array('post', 'put'))) {

            $name = $this->data['User']['name'];
            if($name==''){
                $this->Flash->error(__('The Name could not be Blank'));
                return $this->redirect(array('action' => 'create_unit'));
            }
            $unit=$this->Catalog_unit->find('all',array('conditions'=>array('name'=>$name)));
            if($unit){
                $this->Flash->error(__('Unit Name already exist'));
                return $this->redirect(array('action' => 'unit_list'));
            }
            $data['Catalog_unit']=array(
                'name'=>$name,
                'sync' => 0,
                'local_sync' => 0
            );

            if($this->Catalog_unit->saveAll($data['Catalog_unit']))
            {
                $unit_id = $this->Catalog_unit->id;

                $data['Catalog_unit_mapping']=array(
                    'unit_id'=>$unit_id,
                    'unit_name'=>$name,
                    'mapped_unit_id'=>$unit_id,
                    'mapped_unit_name'=>$name,
                    'conversion_factor'=>1,
                    'sync' => 0,
                    'local_sync' => 0
                );
                if(!$this->Catalog_unit_mapping->saveAll($data['Catalog_unit_mapping']))
                {
                    $this->Flash->error(__('Unit could not be Mapped.'));
                }
                $this->Flash->success(__('Unit has been saved.'));
                return $this->redirect(array('action' => 'unit_list'));
            } else {
                $this->Flash->error(__('Unit could not be saved. Please, try again.'));
            }
        }
    }

    public function edit_unit()
    {
        if(!$this->Session->check('Auth.User.id')){
            $this->redirect(array('controller'=>'dashboard','action' => 'login'));
        }

        $this->layout = "users";

        $id=$_REQUEST['id'];
        $taxes=$this->Catalog_unit->find('all',array('conditions'=>array('id'=>$id)));
        $this->set(compact('taxes'));

        if ($this->request->is(array('post', 'put'))) {

            $id = $this->data['User']['id'];
            $name = $this->data['User']['name'];
            if($name==''){
                $this->Flash->error(__('The Name could not be Blank'));
                return $this->redirect(array('action' => 'edit_unit'));
            }
            $this->Catalog_unit->query("UPDATE `catalog_units` SET name='$name',sync = 0,local_sync=0 WHERE id=$id");

            $this->Catalog_unit_mapping->updateAll(
                [
                    'unit_name' => "'$name'",
                    'sync' => 0,
                    'local_sync' => 0
                ],
                ['Catalog_unit_mapping.unit_id' => $id]
            );

            $this->Catalog_unit_mapping->updateAll(
                [
                    'mapped_unit_name' => "'$name'",
                    'sync' => 0,
                    'local_sync' => 0
                ],
                ['Catalog_unit_mapping.mapped_unit_id' => $id]
            );

            $this->Flash->success(__('Unit has been updated.'));
            return $this->redirect(array('action' => 'unit_list'));
        }
    }

    public function location_list() {
        if(!$this->Session->check('Auth.User.id')){
            $this->redirect(array('controller'=>'dashboard','action' => 'login'));
        }
        $authUser = $this->Session->read('Auth.User');
        $userRole = $authUser['role'] ?? '';
        $userLocationId = $authUser['location_ids'] ?? 0;
        $this->layout = "users";
        $startDate = date('m/d/Y', strtotime('-12 months'));
        $endDate = date('m/d/Y');
        if($userRole == 'subadmin'){
            $id = $authUser['id'];
            $locations = $this->User_location->find('list', [
                'conditions' => ['status' => 1, 'user_id' => $id],
                'fields' => ['location_id']
            ]);
            $userLocationId = implode(',', array_values($locations));
        }

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

        $locationCondition = '';
        if ($userRole !== 'admin') {
            $locationCondition = "AND id IN (" . $userLocationId .")";
        }
        $_shiftRange = $this->getShiftDateRange($startdate, $enddate);
        $startDatetime = $_shiftRange['startDatetime'];
        $endDatetime   = $_shiftRange['endDatetime'];

        $users = $this->Location->query("SELECT * FROM locations where created between '$startDatetime' and  '$endDatetime' $locationCondition ORDER BY id desc");
        $yourStartDateValue=$startDate . ' - ' . $endDate;
        $this->set('startdate', $yourStartDateValue);

        $this->set(compact('users'));

    }

    public function coupon_list() {
        if(!$this->Session->check('Auth.User.id')){
            $this->redirect(array('controller'=>'dashboard','action' => 'login'));
        }

        $this->layout = "users";

        $startDate = date('m/d/Y', strtotime('-12 months'));
        $endDate = date('m/d/Y');

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

        $_shiftRange = $this->getShiftDateRange($startdate, $enddate);
        $startDatetime = $_shiftRange['startDatetime'];
        $endDatetime   = $_shiftRange['endDatetime'];

        $users = $this->Coupon->query("SELECT * FROM coupons where created between '$startDatetime' and  '$endDatetime' ORDER BY id desc");
        $yourStartDateValue=$startDate . ' - ' . $endDate;
        $this->set('startdate', $yourStartDateValue);

        $this->set(compact('users'));

    }

    public function table_list() {
        if (!$this->Session->check('Auth.User.id')) {
            return $this->redirect(['controller' => 'dashboard', 'action' => 'login']);
        }

        $this->layout = "users";

        $users = [];

        $authUser = $this->Session->read('Auth.User');
        $userRole = $authUser['role'] ?? '';
        $userLocationId = $authUser['location_ids'] ?? 0;

        $userLocationIds = is_array($userLocationId) ? $userLocationId : [$userLocationId];

        $placeholders = implode(',', array_fill(0, count($userLocationIds), '?'));
        $conditions = "1=1";

        if ($userRole !== 'admin' && !empty($userLocationIds)) {
            $conditions .= " AND order_tables.location_id IN ($placeholders)";
        }

        $users = $this->Order_table->query(
            "SELECT order_tables.*, table_categories.name as category_name FROM order_tables LEFT JOIN table_categories ON table_categories.id = order_tables.table_category_id WHERE $conditions ORDER BY order_tables.id DESC",
            $userLocationIds
        );

        $this->set(compact('users'));
    }

    public function table_category_list() {
        if (!$this->Session->check('Auth.User.id')) {
            return $this->redirect(['controller' => 'dashboard', 'action' => 'login']);
        }

        $this->layout = "users";

        $categories = $this->Table_category->find('all', [
            'order' => ['Table_category.id' => 'DESC']
        ]);

        $this->set(compact('categories'));
    }

    public function create_table_category() {
        if (!$this->Session->check('Auth.User.id')) {
            return $this->redirect(['controller' => 'dashboard', 'action' => 'login']);
        }

        $this->layout = "users";

        if ($this->request->is(['post', 'put'])) {
            $name = trim($this->data['User']['name'] ?? '');
            if ($name == '') {
                $this->Flash->error(__('Category Name cannot be blank.'));
                return $this->redirect(['action' => 'create_table_category']);
            }

            $existing = $this->Table_category->find('first', [
                'conditions' => ['Table_category.name' => $name]
            ]);
            if (!empty($existing)) {
                $this->Flash->error(__('Table Category already exists.'));
                return $this->redirect(['action' => 'create_table_category']);
            }

            $data = [
                'name' => $name,
                'status' => 1,
                'sync' => 0,
                'local_sync' => 0
            ];

            if ($this->Table_category->save($data)) {
                $this->Flash->success(__('Table Category has been created.'));
                return $this->redirect(['action' => 'table_category_list']);
            } else {
                $this->Flash->error(__('Could not save Table Category. Please, try again.'));
            }
        }
    }

    public function edit_table_category() {
        if (!$this->Session->check('Auth.User.id')) {
            return $this->redirect(['controller' => 'dashboard', 'action' => 'login']);
        }

        $this->layout = "users";

        if ($this->request->is(['post', 'put']) && !empty($this->data['User']['id'])) {
            $id = $this->data['User']['id'];
            $name = trim($this->data['User']['name'] ?? '');

            if ($name == '') {
                $this->Flash->error(__('Category Name cannot be blank.'));
                return $this->redirect(['action' => 'edit_table_category', '?' => ['id' => $id]]);
            }

            $existing = $this->Table_category->find('first', [
                'conditions' => [
                    'Table_category.name' => $name,
                    'Table_category.id !=' => $id
                ]
            ]);
            if (!empty($existing)) {
                $this->Flash->error(__('Another category with this name already exists.'));
                return $this->redirect(['action' => 'edit_table_category', '?' => ['id' => $id]]);
            }

            $this->Table_category->id = $id;
            if ($this->Table_category->save(['name' => $name, 'sync' => 0, 'local_sync' => 0])) {
                $this->Flash->success(__('Table Category has been updated.'));
                return $this->redirect(['action' => 'table_category_list']);
            } else {
                $this->Flash->error(__('Could not update Table Category. Please, try again.'));
            }
        } else {
            $id = $_REQUEST['id'] ?? '';
            $category = $this->Table_category->find('first', [
                'conditions' => ['Table_category.id' => $id]
            ]);

            if (empty($category)) {
                $this->Flash->error(__('Table Category not found.'));
                return $this->redirect(['action' => 'table_category_list']);
            }

            $this->set(compact('category'));
        }
    }

    public function booking_list() {
        if(!$this->Session->check('Auth.User.id')){
            $this->redirect(array('controller'=>'dashboard','action' => 'login'));
        }

        $this->layout = "users";

        $startDate = date('m/d/Y', strtotime('-7 days'));
        $endDate = date('m/d/Y');

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

        $_shiftRange = $this->getShiftDateRange($startdate, $enddate);
        $startDatetime = $_shiftRange['startDatetime'];
        $endDatetime   = $_shiftRange['endDatetime'];

        $users = $this->Online_booking->query("SELECT * FROM online_bookings where created between '$startDatetime' and  '$endDatetime' and status=1 ORDER BY id desc");
        $yourStartDateValue=$startDate . ' - ' . $endDate;
        $this->set('startdate', $yourStartDateValue);

        $this->set(compact('users'));

    }

    public function create_product() {
        if (!$this->Session->check('Auth.User.id')) {
            return $this->redirect(['controller' => 'dashboard', 'action' => 'login']);
        }

        $this->layout = "users";

        $categorys = $this->Catalog_category->find('all', ['conditions' => ['status' => 1]]);
        $options = [];

        foreach ($categorys as $category) {
            $cat_id = $category['Catalog_category']['id'];
            $cat_name = $category['Catalog_category']['name'];

            $subcategorys = $this->Catalog_subcategory->find('all', [
                'conditions' => ['cat_id' => $cat_id, 'status' => 1]
            ]);

            if ($subcategorys) {
                $options[$cat_name] = [];
                foreach ($subcategorys as $sub) {

                    $sub_id = $sub['Catalog_subcategory']['id'];
                    $sub_name = $sub['Catalog_subcategory']['name'];
                    $options[$cat_name]["sub_$sub_id"] = $sub_name;
                }
            } else {
                $options["cat_$cat_id"] = $cat_name;
            }
        }
        $locations=$this->Location->find('all',array('conditions'=>array('status'=>1)));
        $this->set(compact('locations'));

        $taxes = $this->Catalog_tax->find('all', array('conditions' => array('status' => 1)));

        $linkedGroupIds = $this->Inventory_linking->find('list', [
            'fields' => ['Inventory_linking.parent_id'],
            'conditions' => [
                'Inventory_linking.status' => 1,
                'Inventory_linking.parent_type' => 'Modifier_group',
                'Inventory_linking.component_type' => 'Modifier'
            ],
            'recursive' => -1
        ]);

        $modifiers = $this->Catalog_modifier_group->find('all', [
            'conditions' => [
                'Catalog_modifier_group.status' => 1,
                'Catalog_modifier_group.id' => array_values($linkedGroupIds)
            ],
            'recursive' => -1
        ]);

        $units = $this->Catalog_unit->find('all', ['conditions' => ['status' => 1]]);
        $kdss = $this->Pos_printer->find('all', ['conditions' => ['status' => 1]]);

        $this->set(compact('categorys', 'subcategorys', 'taxes', 'units', 'options', 'kdss','modifiers'));

        if ($this->request->is(['post', 'put'])) {
            $form = $this->request->data['Product'];

            if (empty($form['name'])) {
                $this->Flash->error(__('The product name cannot be blank.'));
                return $this->redirect(['action' => 'create_product']);
            }

            $category_id = null;
            $category_name = '';
            $subcategory_id = '';
            $subcategory_name = '';

            $categoryInput = $form['category'] ?? '';

            if (strpos($categoryInput, 'cat_') === 0) {
                $category_id = (int) str_replace('cat_', '', $categoryInput);
                $catData = $this->Catalog_category->findById($category_id);
                if ($catData) {
                    $category_name = $catData['Catalog_category']['name'];
                }

            } elseif (strpos($categoryInput, 'sub_') === 0) {
                $subcategory_id = (int) str_replace('sub_', '', $categoryInput);
                $subcatData = $this->Catalog_subcategory->findById($subcategory_id);
                if ($subcatData) {
                    $subcategory_name = $subcatData['Catalog_subcategory']['name'];
                    $category_id = $subcatData['Catalog_subcategory']['cat_id'];
                    $category_name = $this->Catalog_category->field('name', ['id' => $category_id]);
                }
            } else {

                $newcat = trim($categoryInput);
                $this->Catalog_category->create();
                if ($this->Catalog_category->save(['name' => $newcat,'sync'=>0,'local_sync'=>0])) {
                    $category_id = $this->Catalog_category->id;
                    $category_name = $newcat;
                } else {
                    $this->Flash->error(__('New category could not be saved. Please try again.'));
                    return;
                }
            }

            $unit_id = null;
            $unit_name = '';

            $unitInput = $form['unit'] ?? '';

            if (is_numeric($unitInput)) {

                $unit_id = (int)$unitInput;
                $unitData = $this->Catalog_unit->findById($unit_id);
                $unit_name = $unitData['Catalog_unit']['name'] ?? '';
            } else {

                $newunit = trim($unitInput);
                $this->Catalog_unit->create();
                if ($this->Catalog_unit->save(['name' => $newunit,
                            'sync' => 0,
                            'local_sync' => 0])) {
                    $unit_id = $this->Catalog_unit->id;
                    $unit_name = $newunit;
                } else {
                    $this->Flash->error(__('New unit could not be saved. Please try again.'));
                    return;
                }
            }

            $kds_id = null;
            $kds_name = '';
            $kds_input = $form['kds'] ?? '';
            $customKds = $_POST['custom_kds'] ?? [];

            if (is_numeric($kds_input)) {

                $kds_id = (int)$kds_input;
                $existing = $this->Pos_printer->find('first', [
                    'conditions' => ['Pos_printer.kds' => $kds_id],
                    'fields'     => ['Pos_printer.name'],
                    'recursive'  => -1
                ]);
                $kds_name = $existing['Pos_printer']['name'] ?? '';
            } elseif (!empty($customKds)) {

                $firstKds = reset($customKds);
                $newKdsName = trim($firstKds['name'] ?? '');
                $stores = !empty($firstKds['stores']) ? $firstKds['stores'] : [];
                if (empty($stores) && !empty($firstKds['store_id'])) {
                    $stores[] = ['store_id' => $firstKds['store_id'], 'ip' => $firstKds['ip'] ?? ''];
                }

                $cleanStores = [];
                $seenStores = [];
                foreach ($stores as $st) {
                    $sId = trim((string)($st['store_id'] ?? ''));
                    $sIp = trim((string)($st['ip'] ?? ''));
                    if ($sId !== '' && $sIp !== '' && !isset($seenStores[$sId])) {
                        $seenStores[$sId] = true;
                        $loc = $this->Location->findById($sId);
                        $cleanStores[] = [
                            'store_id'   => $sId,
                            'store_name' => $loc['Location']['name'] ?? '',
                            'ip'         => $sIp
                        ];
                    }
                }

                if (!empty($cleanStores)) {
                    $firstId = null;
                    foreach ($cleanStores as $cs) {
                        $this->Pos_printer->create();
                        $saved = $this->Pos_printer->save([
                            'name'       => $newKdsName,
                            'store_id'   => $cs['store_id'],
                            'store_name' => $cs['store_name'],
                            'ip'         => $cs['ip'],
                            'sync'       => 0,
                            'local_sync' => 0
                        ]);
                        if ($saved) {
                            if ($firstId === null) {
                                $firstId = $this->Pos_printer->id;
                                $this->Pos_printer->updateAll(
                                    [
                                        'Pos_printer.kds'        => (int)$firstId,
                                        'Pos_printer.sync'       => 0,
                                        'Pos_printer.local_sync' => 0
                                    ],
                                    ['Pos_printer.id' => (int)$firstId]
                                );
                            } else {
                                $newId = $this->Pos_printer->id;
                                $this->Pos_printer->updateAll(
                                    [
                                        'Pos_printer.kds'        => (int)$firstId,
                                        'Pos_printer.sync'       => 0,
                                        'Pos_printer.local_sync' => 0
                                    ],
                                    ['Pos_printer.id' => (int)$newId]
                                );
                            }
                        }
                    }
                    if ($firstId !== null) {
                        $kds_id = (int)$firstId;
                        $kds_name = $newKdsName;
                    }
                }
            }

            $filename = '';
            if (!empty($form['image']['name'])) {
                $uploadPath = WWW_ROOT . 'uploads' . DS . 'product_images' . DS;
                $filename = time() . '_' . preg_replace('/[^A-Za-z0-9_\-\.]/', '_', $form['image']['name']);
                $uploadFile = $uploadPath . $filename;
                if (!move_uploaded_file($form['image']['tmp_name'], $uploadFile)) {
                    $filename = '';
                }
            }

            $data = [
                'Catalog_product' => [
                    'name' => $form['name'],
                    'cat_id' => $category_id,
                    'cat_name' => $category_name,
                    'subcat_id' => $subcategory_id,
                    'subcat_name' => $subcategory_name,
                    'unit' => $unit_id,
                    'unit_name' => $unit_name,
                    'description' => $form['description'] ?? '',
                    'dish_type' => $form['dish_type'] ?? 0,
                    'food_type' => $form['food_type'] ?? 0,
                    'package' => $form['package'] ?? 0,
                    'preparation_time' => $form['preparation'] ?? 0,
                    'serve_time' => $form['serve_time'] ?? 0,
                    'moq' => $form['moq'] ?? 0,
                    'sort_order' => $form['sort_order'] ?? 0,
                    'recommended' => $form['recommended'] ?? '',
                    'variation' => $form['variation'] ?? '',
                    'price' => $form['price'] ?? 0,
                    'markup_price' => $form['markup_price'] ?? 0,
                    'allergy_tags' => $form['allergy_tags'] ?? '',
                    'weight' => $form['weight'] ?? 0,
                    'serves_people' => $form['serves_people'] ?? 0,
                    'kds' => $kds_id,
                    'image' => $filename,
                    'is_discountable' => (!empty($form['is_discountable']) && $form['is_discountable'] != '0') ? 1 : 0,
                    'sync' => 0,
                    'local_sync' => 0
                ]
            ];

            if ($this->Catalog_product->save($data)) {
                $productId = $this->Catalog_product->id;

                $finalTaxIds = [];

                if (!empty($form['custom_taxes'])) {
                    foreach ($form['custom_taxes'] as $customKey => $taxData) {
                        $name = trim($taxData['name'] ?? '');
                        $percentage = floatval($taxData['percentage'] ?? 0);
                        $isChecked = !empty($form['tax_ids'][$customKey]);

                        if ($name && $percentage && $isChecked) {
                            $existing = $this->Catalog_tax->find('first', ['conditions' => ['name' => $name, 'percentage' => $percentage]]);
                            $taxId = $existing['Catalog_tax']['id'] ?? null;

                            if (!$taxId) {
                                $this->Catalog_tax->create();
                                if ($this->Catalog_tax->save(['name' => $name, 'percentage' => $percentage,'sync' => 0,'local_sync' => 0])) {
                                    $taxId = $this->Catalog_tax->id;
                                }
                            }

                            if ($taxId) {
                                $finalTaxIds[$taxId] = ['name' => $name, 'percentage' => $percentage];
                            }
                        }
                    }
                }

                if (!empty($form['tax_ids'])) {
                    foreach ($form['tax_ids'] as $taxId => $selected) {
                        if (is_numeric($taxId) && $selected) {
                            $tax = $this->Catalog_tax->findById($taxId);
                            if ($tax) {
                                $finalTaxIds[$taxId] = [
                                    'name' => $tax['Catalog_tax']['name'],
                                    'percentage' => $tax['Catalog_tax']['percentage']
                                ];
                            }
                        }
                    }
                }

                if (!empty($finalTaxIds)) {
                    foreach ($finalTaxIds as $taxId => $tdata) {
                        $this->Catalog_product_tax->create();
                        $this->Catalog_product_tax->save([
                            'product_id' => $productId,
                            'product_name' => $form['name'],
                            'tax_id' => $taxId,
                            'tax_name' => $tdata['name'],
                            'tax_percentage' => $tdata['percentage'],
                            'sync' => 0,
                            'local_sync' => 0
                        ]);
                    }
                }

                if (!empty($form['modifiers'])) {
                    foreach ($form['modifiers'] as $modifierId => $selected) {
                        if ($selected) {
                            $modifier_id = $this->Catalog_modifier_group->findById($modifierId);
                            if ($modifier_id) {
                                $finalModifierIds[$modifierId] = [
                                    'id' => $modifier_id['Catalog_modifier_group']['id'],
                                    'name' => $modifier_id['Catalog_modifier_group']['name']
                                ];
                            }
                        }
                    }
                }

                if (!empty($finalModifierIds)) {
                    foreach ($finalModifierIds as $modifierId => $tdata) {
                        $this->Inventory_linking->create();
                        $this->Inventory_linking->save([
                            'parent_type'=>'Product',
                            'parent_id'=>$productId,
                            'parent_name'=>$form['name'],
                            'component_type'=>'Modifier_group',
                            'component_id'=>$modifierId,
                            'component_name'=>$tdata['name'],
                            'quantity'=>1,
                            'sync' => 0,
                            'local_sync' => 0
                        ]);
                    }
                }

                if (!empty($_POST['custom_attributes'])) {
                    foreach ($_POST['custom_attributes'] as $attr) {
                        $attrName = trim($attr['name'] ?? '');
                        $attrValue = trim($attr['value'] ?? '');
                        if ($attrName && $attrValue) {
                            $this->Catalog_product_attribute->create();
                            $this->Catalog_product_attribute->saveAll([
                                'product_id' => $productId,
                                'name' => $attrName,
                                'value' => $attrValue,
                                'sync' => 0,
                                'local_sync' => 0
                            ]);
                        }
                    }
                }

                $this->Flash->success(__('Product has been saved successfully.'));
                return $this->redirect(['action' => 'product_list']);
            } else {
                $this->Flash->error(__('Product could not be saved. Please try again.'));
            }
        }

    }

    public function product_list() {
        if(!$this->Session->check('Auth.User.id')){
            $this->redirect(array('controller'=>'dashboard','action' => 'login'));
        }

        $this->layout = "users";

        $startDate = date('m/d/Y', strtotime('-12 months'));
        $endDate = date('m/d/Y');

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
        $_shiftRange = $this->getShiftDateRange($startdate, $enddate);
        $startDatetime = $_shiftRange['startDatetime'];
        $endDatetime   = $_shiftRange['endDatetime'];

        $users = $this->Catalog_product->query(
            "SELECT catalog_product.*, COALESCE(vpc.recipe_cost, 0) as recipe_cost
             FROM catalog_product
             LEFT JOIN v_product_recipe_cost vpc ON vpc.id = catalog_product.id
             WHERE catalog_product.created BETWEEN '$startDatetime' AND '$endDatetime'
             ORDER BY catalog_product.id DESC"
        );
        $yourStartDateValue=$startDate . ' - ' . $endDate;
        $this->set('startdate', $yourStartDateValue);

        $this->set(compact('users'));

    }

    public function view_product()
    {
        if (!$this->Session->check('Auth.User.id')) {
            return $this->redirect(['controller' => 'dashboard', 'action' => 'login']);
        }

        $this->layout = 'users';

        $id = $_REQUEST['id'] ?? null;
        if (empty($id)) {
            $this->Flash->error('Invalid product ID.');
            return $this->redirect(['action' => 'product_list']);
        }

        $product = $this->Catalog_product->find('first', [
            'conditions' => ['Catalog_product.id' => $id],
            'joins' => [
                [
                    'table' => 'pos_printers',
                    'alias' => 'Kds',
                    'type' => 'LEFT',
                    'conditions' => ['Kds.kds = Catalog_product.kds', 'Kds.status' => 1]
                ]
            ],
            'fields' => [
                'Catalog_product.*',
                'Kds.name as kds_name'
            ]
        ]);
        if (empty($product)) {
            $this->Flash->error('Product not found.');
            return $this->redirect(['action' => 'product_list']);
        }

        $product = array_merge($product['Catalog_product'], [
            'kds_name'    => $product['Kds']['kds_name'] ?? ''
        ]);

        $productTaxData = $this->Catalog_product_tax->find('list', [
            'fields' => ['Catalog_product_tax.id', 'Catalog_product_tax.tax_id'],
            'conditions' => ['Catalog_product_tax.product_id' => $id,'Catalog_product_tax.status' => 1]
        ]);
        $selectedTaxIds = array_values($productTaxData);

        $taxes = $this->Catalog_product_tax->query("
            SELECT cpt.tax_id, CONCAT(ct.name, '(', ct.percentage, '%)') as taxname
            FROM catalog_product_tax cpt
            JOIN catalog_tax ct ON ct.id = cpt.tax_id
            WHERE cpt.product_id = $id and cpt.status=1
        ");

        $modifiers = $this->Inventory_linking->query("
            SELECT *
            FROM inventory_linking
            WHERE parent_id = $id and parent_type='Product' and component_type='Modifier_group' and status=1
        ");

        $attributes = $this->Catalog_product_attribute->query("
            SELECT name,value
            FROM catalog_product_attribute
            WHERE catalog_product_attribute.product_id = $id and catalog_product_attribute.status=1
        ");

        $this->set(compact('product', 'taxes', 'selectedTaxIds','attributes','modifiers'));
    }

    public function view_category()
    {
        if(!$this->Session->check('Auth.User.id')){
            $this->redirect(array('controller'=>'dashboard','action' => 'login'));
        }

        $this->layout = "users";
        $id=$_REQUEST['id'];

        $categories = $this->Catalog_category->query("SELECT * FROM catalog_category where id=$id and status=1");
        $this->set(compact('categories'));

        $subcategories=$this->Catalog_subcategory->query("SELECT * FROM catalog_subcategory where cat_id=$id and status=1");
        $this->set(compact('subcategories'));
    }

    public function view_location()
    {
        if(!$this->Session->check('Auth.User.id')){
            $this->redirect(array('controller'=>'dashboard','action' => 'login'));
        }

        $this->layout = "users";
        $id=$_REQUEST['id'];

        $locations = $this->Location->query("SELECT * FROM locations where id=$id and status=1");
        $this->set(compact('locations'));

    }

    public function edit_location()
    {
        if(!$this->Session->check('Auth.User.id')){
            $this->redirect(array('controller'=>'dashboard','action' => 'login'));
        }

        $this->layout = "users";

        $indiaStates=states;
        $shiftTimeOptions = [];
        foreach (range(0, 23) as $h) {
            foreach (range(0, 59, 5) as $m) {
                $t = sprintf('%02d:%02d:00', $h, $m);
                $shiftTimeOptions[$t] = date('h:i A', strtotime("2000-01-01 " . $t));
            }
        }
        foreach (['05:59:59' => '05:59 AM', '23:59:59' => '11:59 PM'] as $t => $lbl) {
            if (!isset($shiftTimeOptions[$t])) {
                $shiftTimeOptions[$t] = $lbl;
            }
        }
        ksort($shiftTimeOptions);
        $this->set(compact('indiaStates', 'shiftTimeOptions'));

        $user_id=$this->Session->read('Auth.User.id');
        if ($this->request->is(['post','put']) && !empty($this->data['User']['id'])) {
            $id=$this->data['User']['id'];
            $name = $this->data['User']['name'];
            if($name==''){
                $this->Flash->error(__('The Name could not be Blank'));
                return $this->redirect(array('action' => 'edit_location?id='.$id));
            }
            $username = $this->data['User']['username'];
            $password = $this->data['User']['password'];
            $type = $this->data['User']['type'];
            $state = $this->data['User']['state'];
            $reelo_merchant_id = isset($this->data['User']['reelo_merchant_id']) ? $this->data['User']['reelo_merchant_id'] : '';
            $reelo_customer_key = isset($this->data['User']['reelo_customer_key']) ? $this->data['User']['reelo_customer_key'] : '';
            $service_charge_rate = isset($this->data['User']['service_charge_rate']) ? $this->data['User']['service_charge_rate'] : 0;
            $print_line1 = $this->data['User']['print_line1'];
            $print_line2 = $this->data['User']['print_line2'];
            $print_line3 = $this->data['User']['print_line3'];
            $print_line4 = $this->data['User']['print_line4'];
            $print_line5 = $this->data['User']['print_line5'];
            $print_line6 = $this->data['User']['print_line6'];
            $print_line7 = $this->data['User']['print_line7'];
            $print_line8 = $this->data['User']['print_line8'];
            $print_line9 = $this->data['User']['print_line9'];
            $print_line10 = $this->data['User']['print_line10'];
            $footer_msg = $this->data['User']['footer_msg'];
            $footer_msg2 = $this->data['User']['footer_msg2'];
            $footer_msg3 = $this->data['User']['footer_msg3'];
            $currency_symbol = $this->data['User']['currency_symbol'];
            $shift_start_time = !empty($this->data['User']['shift_start_time']) ? trim($this->data['User']['shift_start_time']) : (defined('shiftstart') ? trim(shiftstart) : '06:00:00');
            $shift_end_time = !empty($this->data['User']['shift_end_time']) ? trim($this->data['User']['shift_end_time']) : (defined('shiftend') ? trim(shiftend) : '06:00:00');
            $shift_type = (strtotime($shift_end_time) <= strtotime($shift_start_time)) ? 'next_day' : 'same_day';

            if($password!=''){
                $password = AuthComponent::password($password);
                $this->Location->query("UPDATE locations SET password='$password',sync = 0,local_sync=0 WHERE id =". $id);
            }
            $this->Location->query("UPDATE locations SET username ='$username',type='$type',name ='$name',state='$state',reelo_merchant_id='$reelo_merchant_id',reelo_customer_key='$reelo_customer_key',service_charge_rate='$service_charge_rate',print_line1='$print_line1',print_line2='$print_line2',print_line3='$print_line3',print_line4='$print_line4',print_line5='$print_line5',print_line6='$print_line6',print_line7='$print_line7',print_line8='$print_line8',print_line9='$print_line9',print_line10='$print_line10',footer_msg='$footer_msg',footer_msg2='$footer_msg2',footer_msg3='$footer_msg3',currency_symbol='$currency_symbol',shift_start_time='$shift_start_time',shift_end_time='$shift_end_time',shift_type='$shift_type',sync = 0,local_sync=0 WHERE id =". $id);

            $this->Kds_user->query("UPDATE kds_users SET location_name ='$name',sync = 0,local_sync=0 WHERE location_id =". $id);
            $this->Kds_users_station_mapping->query("UPDATE kds_users_station_mapping SET station_store_name ='$name',sync = 0,local_sync=0 WHERE station_store_id =". $id);
            $this->Pos_printer->query("UPDATE pos_printers SET store_name ='$name',sync = 0,local_sync=0 WHERE store_id =". $id);
            $this->User_location->query("UPDATE user_locations SET location_name ='$name',sync = 0,local_sync=0 WHERE status=1 and location_id =". $id);
            $this->Inventory_vendor_service_area->query("UPDATE inventory_vendor_service_areas SET area_name ='$name',sync = 0,local_sync=0 WHERE is_deleted=0 and area_id =". $id);
            $this->Catalog_category_branches->query("UPDATE catalog_category_branches SET location_name ='$name',sync = 0,local_sync=0 WHERE location_id =". $id);
            $this->Catalog_product_branches->query("UPDATE catalog_product_branches SET location_name ='$name',sync = 0,local_sync=0 WHERE location_id =". $id);
            $this->Order_table->query("UPDATE order_tables SET location_name ='$name',sync = 0,local_sync=0 WHERE location_id =". $id);
            $this->Order_waiter->query("UPDATE order_waiters SET location_name ='$name',sync = 0,local_sync=0 WHERE location_id =". $id);

            if($type=='DC' && ($this->Session->read('Auth.User.role') == 'admin')){
                $this->User->query("UPDATE users SET location_id ='$id',location_name='$name',sync = 0,local_sync=0 WHERE id =". $user_id);
                $user = $this->Session->read('Auth.User');
                $user['location_id'] = $id;
                $this->Session->write('Auth.User', $user);
            }
            $this->Flash->success(__('Location has been Updated.'));
            return $this->redirect(array('action' => 'location_list'));
        }else{
            $id=$_REQUEST['id'];

            $locations = $this->Location->query("SELECT * FROM locations where id=$id and status=1");
            $this->set(compact('locations'));
        }

    }

    public function edit_product()
    {
        if (!$this->Session->check('Auth.User.id')) {
            $this->redirect(array('controller' => 'dashboard', 'action' => 'login'));
        }

        $this->layout = "users";
        $id = $_REQUEST['id'];

        $products = $this->Catalog_product->query("SELECT * FROM catalog_product WHERE id = $id and status=1");
        $categories = $this->Catalog_category->find('all', array('conditions' => array('status' => 1), 'order' => array('name' => 'asc')));

        $options = [];
        if (!empty($products[0]['catalog_product']['subcat_id'])) {
            $selectedcatId = $products[0]['catalog_product']['subcat_id'];
            $selectedType = 'sub';
        } else {
            $selectedcatId = $products[0]['catalog_product']['cat_id'];
            $selectedType = 'cat';
        }

        foreach ($categories as $category) {
            $cat_id = $category['Catalog_category']['id'];
            $cat_name = $category['Catalog_category']['name'];

            $subcategories = $this->Catalog_subcategory->find('all', array(
                'conditions' => array('cat_id' => $cat_id, 'status' => 1),
                'order' => array('name' => 'asc')
            ));

            if (!empty($subcategories)) {
                $options[$cat_name] = [];
                foreach ($subcategories as $subcategory) {
                    $subcat_id = $subcategory['Catalog_subcategory']['id'];
                    $subcat_name = $subcategory['Catalog_subcategory']['name'];
                    if ($subcat_id == $selectedcatId  && $selectedType == 'sub') {
                        $selectedcategory = $subcategory['Catalog_subcategory'];
                        $selectedcategory['_type'] = 'sub';
                    }
                    $options[$cat_name]["sub_$subcat_id"] = $subcat_name;

                }
            } else {
                if ($cat_id == $selectedcatId  && $selectedType == 'cat') {
                    $selectedcategory = $category['Catalog_category'];
                    $selectedcategory['_type'] = 'cat';
                }
                $options["cat_$cat_id"] = $cat_name;
            }
        }

        if (!empty($selectedcategory)) {
            $key_prefix = ($selectedcategory['_type'] == 'sub') ? 'sub_' : 'cat_';
            $options = array($key_prefix . $selectedcategory['id'] => $selectedcategory['name']) + $options;
        }

        $taxes = $this->Catalog_tax->find('all', array('conditions' => array('status' => 1)));
        $units = $this->Catalog_unit->find('all', array('conditions' => array('status' => 1)));
        $kdss = $this->Pos_printer->find('all', array('conditions' => array('status' => 1)));
        $locations=$this->Location->find('all',array('conditions'=>array('status'=>1)));
        $this->set(compact('locations'));

        $this->set(compact('categories', 'subcategories', 'taxes', 'units', 'options', 'selectedcatId', 'kdss'));
        $this->set(compact('products'));

        $product_taxes = $this->Catalog_product_tax->find('all', array('conditions' => array('product_id' => $id,'status' => 1), 'order' => array('id' => 'desc')));
        $this->set(compact('product_taxes'));

        $attributes = $this->Catalog_product_attribute->find('all', [
            'conditions' => ['product_id' => $id,'status' => 1],
            'order' => ['id' => 'desc']
        ]);

        $customAttributes = array_map(function($attr) {
            return [
                'name' => $attr['Catalog_product_attribute']['name'],
                'value' => $attr['Catalog_product_attribute']['value']
            ];
        }, $attributes);
        $this->set(compact('customAttributes'));

        if (!empty($this->data['User']['id'])) {
            $form = $this->data['User'];
            $id = $form['id'];
            $name = trim($form['name']);

            if ($name == '') {
                $this->Flash->error(__('The Name could not be blank.'));
                return $this->redirect(['action' => 'edit_product', '?' => ['id' => $id]]);
            }

            $category_id = null;
            $category_name = '';
            $subcategory_id = '';
            $subcategory_name = '';

            $categoryInput = $form['category'] ?? '';

            if (strpos($categoryInput, 'cat_') === 0) {
                $category_id = (int) str_replace('cat_', '', $categoryInput);
                $catData = $this->Catalog_category->findById($category_id);
                if ($catData) {
                    $category_name = $catData['Catalog_category']['name'];
                }
            }elseif (strpos($categoryInput, 'sub_') === 0) {
                $subcategory_id = (int) str_replace('sub_', '', $categoryInput);
                $subcatData = $this->Catalog_subcategory->findById($subcategory_id);
                if ($subcatData) {
                    $subcategory_name = $subcatData['Catalog_subcategory']['name'];
                    $category_id = $subcatData['Catalog_subcategory']['cat_id'];
                    $category_name = $this->Catalog_category->field('name', ['id' => $category_id]);
                }
            }else {

                $newcat = trim($categoryInput);
                $this->Catalog_category->create();
                if ($this->Catalog_category->save(['name' => $newcat,'sync' => 0,'local_sync' => 0])) {
                    $category_id = $this->Catalog_category->id;
                    $category_name = $newcat;
                } else {
                    $this->Flash->error(__('New category could not be saved. Please try again.'));
                    return;
                }
            }

            $unit_id = null;
            $unit_name = '';

            $unitInput = $form['unit'] ?? '';

            if (is_numeric($unitInput)) {
                $unit_id = (int)$unitInput;
                $unitData = $this->Catalog_unit->findById($unit_id);
                $unit_name = $unitData['Catalog_unit']['name'] ?? '';
            } else {
                $newunit = trim($unitInput);
                $this->Catalog_unit->create();
                if ($this->Catalog_unit->save(['name' => $newunit,'sync' => 0,'local_sync' => 0])) {
                    $unit_id = $this->Catalog_unit->id;
                    $unit_name = $newunit;
                } else {
                    $this->Flash->error(__('New unit could not be saved.'));
                    return;
                }
            }

            $kds_id = null;
            $kds_name = '';
            $kds_input = $form['kds'] ?? '';
            $customKds = $_POST['custom_kds'] ?? [];

            if (is_numeric($kds_input)) {
                $kds_id = (int)$kds_input;
                $existing = $this->Pos_printer->find('first', [
                    'conditions' => ['Pos_printer.kds' => $kds_id],
                    'fields'     => ['Pos_printer.name'],
                    'recursive'  => -1
                ]);
                $kds_name = $existing['Pos_printer']['name'] ?? '';
            } elseif (!empty($customKds)) {
                $firstKds = reset($customKds);
                $newKdsName = trim($firstKds['name'] ?? '');
                $stores = !empty($firstKds['stores']) ? $firstKds['stores'] : [];
                if (empty($stores) && !empty($firstKds['store_id'])) {
                    $stores[] = ['store_id' => $firstKds['store_id'], 'ip' => $firstKds['ip'] ?? ''];
                }

                $cleanStores = [];
                $seenStores = [];
                foreach ($stores as $st) {
                    $sId = trim((string)($st['store_id'] ?? ''));
                    $sIp = trim((string)($st['ip'] ?? ''));
                    if ($sId !== '' && $sIp !== '' && !isset($seenStores[$sId])) {
                        $seenStores[$sId] = true;
                        $loc = $this->Location->findById($sId);
                        $cleanStores[] = [
                            'store_id'   => $sId,
                            'store_name' => $loc['Location']['name'] ?? '',
                            'ip'         => $sIp
                        ];
                    }
                }

                if (!empty($cleanStores)) {
                    $firstId = null;
                    foreach ($cleanStores as $cs) {
                        $this->Pos_printer->create();
                        $saved = $this->Pos_printer->save([
                            'name'       => $newKdsName,
                            'store_id'   => $cs['store_id'],
                            'store_name' => $cs['store_name'],
                            'ip'         => $cs['ip'],
                            'sync'       => 0,
                            'local_sync' => 0
                        ]);
                        if ($saved) {
                            if ($firstId === null) {
                                $firstId = $this->Pos_printer->id;
                                $this->Pos_printer->updateAll(
                                    [
                                        'Pos_printer.kds'        => (int)$firstId,
                                        'Pos_printer.sync'       => 0,
                                        'Pos_printer.local_sync' => 0
                                    ],
                                    ['Pos_printer.id' => (int)$firstId]
                                );
                            } else {
                                $newId = $this->Pos_printer->id;
                                $this->Pos_printer->updateAll(
                                    [
                                        'Pos_printer.kds'        => (int)$firstId,
                                        'Pos_printer.sync'       => 0,
                                        'Pos_printer.local_sync' => 0
                                    ],
                                    ['Pos_printer.id' => (int)$newId]
                                );
                            }
                        }
                    }
                    if ($firstId !== null) {
                        $kds_id = (int)$firstId;
                        $kds_name = $newKdsName;
                    }
                }
            }

            $filename = '';
            if (!empty($form['image']['name'])) {
                $uploadPath = WWW_ROOT . 'uploads' . DS . 'product_images' . DS;
                $filename = time() . '_' . preg_replace('/[^A-Za-z0-9_\-\.]/', '_', $form['image']['name']);
                $uploadFile = $uploadPath . $filename;
                if (!move_uploaded_file($form['image']['tmp_name'], $uploadFile)) {
                    $filename = '';
                }
            }

            $productData = [
                'id' => $id,
                'name' => $name,
                'description' => $form['description'],
                'dish_type' => $form['dish_type'],
                'food_type' => $form['food_type'],
                'package' => $form['package'],
                'sort_order' => $form['sort_order'],
                'recommended' => $form['recommended'],
                'variation' => $form['variation'],
                'price' => $form['price'],
                'preparation_time' => $form['preparation'] ?? 0,
                'serve_time' => $form['serve_time'] ?? 0,
                'moq' => $form['moq'] ?? 0,
                'allergy_tags' => $form['allergy_tags'],
                'weight' => $form['weight'],
                'serves_people' => $form['serves_people'],
                'unit' => $unit_id,
                'unit_name' => $unit_name,
                'cat_id' => $category_id,
                'cat_name' => $category_name,
                'subcat_id' => $subcategory_id,
                'subcat_name' => $subcategory_name,
                'kds' => $kds_id,
                'is_discountable' => (!empty($form['is_discountable']) && $form['is_discountable'] != '0') ? 1 : 0,
                'sync' => 0,
                'local_sync' => 0
            ];

            if ($filename != '') {
                $productData['image'] = $filename;
            }

            $this->Catalog_product->save($productData);

            $package = $form['package'];
            if ($package == 0) {

                $this->Catalog_package->updateAll(
                    ['status' => 0,'sync' => 0,'local_sync' => 0],
                    ['main_product_id' => $id]
                );
            }

            $this->Catalog_product_tax->query("update catalog_product_tax set status=0,sync = 0,local_sync=0 WHERE product_id =" . $id);
            $finalTaxIds = [];
            if (!empty($_POST['User']['custom_taxes'])) {
                foreach ($_POST['User']['custom_taxes'] as $customKey => $taxData) {
                    $name = trim($taxData['name'] ?? '');
                    $percentage = floatval($taxData['percentage'] ?? 0);
                    $isChecked = !empty($_POST['User']['tax_ids'][$customKey]);

                    if ($name && $percentage && $isChecked) {
                        $existing = $this->Catalog_tax->find('first', ['conditions' => ['name' => $name, 'percentage' => $percentage]]);
                        $taxId = $existing['Catalog_tax']['id'] ?? null;

                        if (!$taxId) {
                            $this->Catalog_tax->create();
                            if ($this->Catalog_tax->save(['name' => $name, 'percentage' => $percentage,'sync' => 0,'local_sync' => 0])) {
                                $taxId = $this->Catalog_tax->id;
                            }
                        }

                        if ($taxId) {
                            $finalTaxIds[$taxId] = ['name' => $name, 'percentage' => $percentage];
                        }
                    }
                }
            }

            if (!empty($_POST['data']['tax_ids'])) {
                foreach ($_POST['data']['tax_ids'] as $taxId => $selected) {
                    if (is_numeric($taxId) && $selected) {
                        $tax = $this->Catalog_tax->findById($taxId);
                        if ($tax) {
                            $finalTaxIds[$taxId] = [
                                'name' => $tax['Catalog_tax']['name'],
                                'percentage' => $tax['Catalog_tax']['percentage']
                            ];
                        }
                    }
                }
            }
            foreach ($finalTaxIds as $taxId => $tdata) {
                $this->Catalog_product_tax->create();
                $this->Catalog_product_tax->save([
                    'product_id' => $id,
                    'product_name' => $form['name'],
                    'tax_id' => $taxId,
                    'tax_name' => $tdata['name'],
                    'tax_percentage' => $tdata['percentage'],
                    'sync' => 0,
                    'local_sync' => 0
                ]);
            }

            $this->Catalog_product_attribute->query("UPDATE catalog_product_attribute SET status=0,sync = 0,local_sync=0 WHERE product_id =" . $id);

            if (!empty($_POST['custom_attributes'])) {
                foreach ($_POST['custom_attributes'] as $attr) {
                    $attrName = trim($attr['name'] ?? '');
                    $attrValue = trim($attr['value'] ?? '');
                    if ($attrName && $attrValue) {
                        $this->Catalog_product_attribute->create();
                        $this->Catalog_product_attribute->saveAll([
                            'product_id' => $id,
                            'name' => $attrName,
                            'value' => $attrValue,
                            'sync' => 0,
                            'local_sync' => 0
                        ]);
                    }
                }
            }

            $this->Flash->success(__('Product has been Updated.'));
            return $this->redirect(array('action' => 'product_list'));
        }
    }
    
    public function view_subcategory()
    {
        if(!$this->Session->check('Auth.User.id')){
            $this->redirect(array('controller'=>'dashboard','action' => 'login'));
        }

        $this->layout = "users";
        $id=$_REQUEST['id'];

        $categories=$this->Catalog_subcategory->query("SELECT *,(SELECT name FROM `catalog_category` WHERE id=catalog_subcategory.cat_id) as catname FROM catalog_subcategory where id=$id");
        $this->set(compact('categories'));
    }

    public function edit_subcategory()
    {
        if(!$this->Session->check('Auth.User.id')){
            $this->redirect(array('controller'=>'dashboard','action' => 'login'));
        }

        $this->layout = "users";
        $id=$_REQUEST['id'];

        $subcategories=$this->Catalog_subcategory->query("SELECT *,(SELECT name FROM `catalog_category` WHERE id=catalog_subcategory.cat_id) as catname FROM catalog_subcategory where id=$id");
        $this->set(compact('subcategories'));

        $categories=$this->Catalog_category->find('all',array('conditions'=>array('status'=>1),'order' => array(
                'name' => 'asc'
            )));
        $this->set(compact('categories'));

        if ($this->request->is(array('post', 'put'))) {

            $id = $this->data['User']['id'];
            $name = $this->data['User']['name'];
            if($name==''){
                $this->Flash->error(__('The Name could not be Blank'));
                return $this->redirect(array('action' => 'edit_subcategory?id='.$id));
            }

            $image=$this->data['User']['image'];
            if (!empty($image['name'])) {
                $uploadPath = WWW_ROOT . 'uploads' . DS . 'category_images' . DS;
                $file=basename(time().$image['name']);
                $file = preg_replace('/[^A-Za-z0-9_\-\.]/', '_', $file);
                $uploadFile = $uploadPath . $file;

                if (move_uploaded_file($image['tmp_name'], $uploadFile)) {
                    $filename=$file;
                }else{
                    $filename='';
                }
                $this->Catalog_subcategory->query("UPDATE `catalog_subcategory` SET image='$filename',sync = 0,local_sync=0 WHERE id=$id");
            }
            $category=$this->data['User']['category'];

            $categoryid = $this->Catalog_subcategory->query("SELECT cat_id FROM catalog_subcategory where id=$id");
            $cat_id=$categoryid['0']['catalog_subcategory']['cat_id'];

            $this->Catalog_subcategory->query("UPDATE `catalog_subcategory` SET name='$name',cat_id=$category,sync = 0,local_sync=0 WHERE id=$id");
            $this->Catalog_category->query("UPDATE `catalog_category` SET is_subcategory=1,sync = 0,local_sync=0 WHERE id=$category");

            $subcategorychk=$this->Catalog_subcategory->query("SELECT * FROM catalog_subcategory where cat_id=$cat_id and status=1");
            if($subcategorychk){
                $this->Catalog_category->query("UPDATE catalog_category SET is_subcategory=1,sync = 0,local_sync=0 WHERE id=$cat_id");
            }else{
                $this->Catalog_category->query("UPDATE catalog_category SET is_subcategory=0,sync = 0,local_sync=0 WHERE id=$cat_id");
            }

            $this->Flash->success(__('Sub Category has been Updated.'));
            return $this->redirect(array('action' => 'subcategory_list'));

        }

    }

    public function create_sku()
    {
        if(!$this->Session->check('Auth.User.id')){
            $this->redirect(array('controller'=>'dashboard','action' => 'login'));
        }

        $this->layout = "users";

        $units=$this->Catalog_unit->find('all',array('conditions'=>array('status'=>1)));

        $taxes=$this->Inventory_tax->find('all',array('conditions'=>array('status'=>1)));

        $categorys = $this->Inventory_category->find('all', ['conditions' => ['status' => 1]]);

        $options = [];
        foreach ($categorys as $category) {
            $cat_id = $category['Inventory_category']['id'];
            $cat_name = $category['Inventory_category']['name'];

            $subcategorys = $this->Inventory_subcategory->find('all', [
                'conditions' => ['cat_id' => $cat_id, 'status' => 1]
            ]);

            if (!empty($subcategorys)) {
                foreach ($subcategorys as $sub) {
                    $sub_id = $sub['Inventory_subcategory']['id'];
                    $sub_name = $sub['Inventory_subcategory']['name'];
                    $options[$cat_name]["sub_$sub_id"] = $sub_name;
                }
            } else {
                $options["cat_$cat_id"] = $cat_name;
            }
        }
        $this->set(compact('categorys', 'subcategorys', 'units', 'taxes', 'options'));

        $all_locations_raw = $this->Inventory_sku->query(
            "SELECT locations.id, locations.name, locations.state FROM locations WHERE locations.status = 1 ORDER BY locations.id"
        );
        $all_locations_view = [];
        foreach ($all_locations_raw as $row) {
            $d = !empty($row['locations']) ? $row['locations'] : (isset($row[0]) ? $row[0] : $row);
            $all_locations_view[] = ['locations' => $d];
        }
        $this->set('all_locations', $all_locations_view);

        if ($this->request->is(array('post', 'put'))) {

            $name = $this->data['User']['name'];
            if($name==''){
                $this->Flash->error(__('The Name could not be Blank'));
                return $this->redirect(array('action' => 'create_sku'));
            }

            $name = $this->data['User']['name'];
            $brand = $this->data['User']['brand'];
            $unit = $this->data['User']['unit'];
            $item_type = $this->data['User']['item_type'];
            $price = $this->data['User']['price'];
            $remarks = $this->data['User']['remarks'];
            $conunit = $this->data['User']['conunit'];
            $factor = $this->data['User']['factor'];
            $conprice = $this->data['User']['conprice'];

            $category_id = null;
            $category_name = '';
            $subcategory_id = '';
            $subcategory_name = '';

            $categoryInput = $this->data['User']['category'] ?? '';

            if (strpos($categoryInput, 'cat_') === 0) {
                $category_id = (int) str_replace('cat_', '', $categoryInput);
                $catData = $this->Inventory_category->findById($category_id);
                if ($catData) {
                    $category_name = $catData['Inventory_category']['name'];
                }

            } elseif (strpos($categoryInput, 'sub_') === 0) {

                $subcategory_id = (int) str_replace('sub_', '', $categoryInput);
                $subcatData = $this->Inventory_subcategory->findById($subcategory_id);
                if ($subcatData) {
                    $subcategory_name = $subcatData['Inventory_subcategory']['name'];
                    $category_id = $subcatData['Inventory_subcategory']['cat_id'];
                    $category_name = $this->Inventory_category->field('name', ['id' => $category_id]);
                }
            } else {

                $newcat = trim($categoryInput);
                $this->Inventory_category->create();
                if ($this->Inventory_category->save(['name' => $newcat,
                    'sync' => 0,
                    'local_sync' => 0])) {
                    $category_id = $this->Inventory_category->id;
                    $category_name = $newcat;
                } else {
                    $this->Flash->error(__('New category could not be saved. Please try again.'));
                    return;
                }
            }

            $unitname=$this->Catalog_unit->find('first',array('conditions'=>array('id'=>$unit)));
            $conunitname=$this->Catalog_unit->find('first',array('conditions'=>array('id'=>$conunit)));

            $has_variable_tax_create = !empty($this->data['User']['has_variable_tax']) ? 1 : 0;
            $excise_applicable_create_flag = (!empty($this->data['User']['excise_applicable']) || $has_variable_tax_create) ? 1 : 0;
            $data['Inventory_sku']=array(
                'name'=>$name,
                'brand'=>$brand,
                'cat_id' => $category_id,
                'cat_name' => $category_name,
                'subcat_id' => $subcategory_id,
                'subcat_name' => $subcategory_name,
                'unit'=>$unit,
                'item_type' => $item_type,
                'unit_name'=>$unitname['Catalog_unit']['name'],
                'consumption_unit_id'=>$conunit,
                'consumption_unit_name'=>$conunitname['Catalog_unit']['name'],
                'consumption_factor'=>$factor,
                'consumption_unit_price'=>$conprice,
                'price'=>$price,
                'remarks'=>$remarks,
                'moq' => $this->data['User']['moq'] ?? 0,
                'stock' => $this->data['User']['stock'] ?? 0,
                'has_variable_tax' => $has_variable_tax_create,
                'excise_applicable' => $excise_applicable_create_flag,
                'sync' => 0,
                'local_sync' => 0
            );

            if($this->Inventory_sku->saveAll($data['Inventory_sku']))
            {
                $lastInsertedID = $this->Inventory_sku->id;

                $taxes=$this->Inventory_tax->find('all',array('conditions'=>array('status'=>1)));
                foreach ($taxes as $taxess) {
                    $taxID = $taxess['Inventory_tax']['id'];
                    if (!empty($this->request->data['User']['tax_' . $taxID])) {
                        $selectedTaxes[] = $this->request->data['User']['tax_' . $taxID];
                        $taxdetail=$this->Inventory_tax->find('first',array('conditions'=>array('id'=>$taxID)));
                        if($taxdetail){
                            $data['Inventory_sku_tax']=array(
                                'product_id'=>$lastInsertedID,
                                'product_name'=>$name,
                                'tax_id'=>$taxID,
                                'tax_name'=>$taxdetail['Inventory_tax']['name'],
                                'tax_percentage'=>$taxdetail['Inventory_tax']['percentage'],
                                'sync' => 0,
                                'local_sync' => 0
                            );

                            if($this->Inventory_sku_tax->saveAll($data['Inventory_sku_tax']))
                            {

                            }else{
                                $this->Flash->error(__('SKU tax could not be saved. Please, try again.'));
                            }
                        }
                    }
                }

                if(isset($_POST['custom_tax_name'])){

                    $taxnames=$_POST['custom_tax_name'];
                    $taxvalues=$_POST['custom_tax_value'];

                    for ($i = 0; $i < count($taxnames); $i++) {
                        $taxname = $taxnames[$i];
                        $percentage = $taxvalues[$i];

                        $newtaxdetail=$this->Inventory_tax->find('first',array('conditions'=>array('name'=>$taxname,'percentage'=>$percentage)));
                        if(empty($newtaxdetail)){
                            $data['Inventory_tax']=array(
                                'name'=>$taxname,
                                'percentage'=>$percentage,
                                'sync' => 0,
                                'local_sync' => 0
                            );

                            if($this->Inventory_tax->saveAll($data['Inventory_tax']))
                            {
                                $taxlastInsertedID = $this->Inventory_tax->id;

                            } else {
                                $this->Flash->error(__('New Tax could not be saved. Please, try again.'));
                            }
                        }

                        $data['Inventory_sku_tax']=array(
                            'product_id'=>$lastInsertedID,
                            'product_name'=>$name,
                            'tax_id'=>$taxlastInsertedID,
                            'tax_name'=>$taxname,
                            'tax_percentage'=>$percentage,
                            'sync' => 0,
                            'local_sync' => 0
                        );

                        if($this->Inventory_sku_tax->saveAll($data['Inventory_sku_tax']))
                        {

                        }else{
                            $this->Flash->error(__('Sku New tax could not be saved. Please, try again.'));
                        }

                    }
                }

                $excise_rates_post = isset($_POST['excise_rates']) ? $_POST['excise_rates'] : [];
                if ($excise_applicable_create_flag && !empty($excise_rates_post)) {
                    $sku_name_esc = addslashes($name);
                    foreach ($excise_rates_post as $loc_id => $rates) {
                        $loc_id  = intval($loc_id);
                        $epu     = floatval($rates['excise_per_unit']      ?? 0);
                        $aepu    = floatval($rates['addl_excise_per_unit'] ?? 0);
                        $vatp    = floatval($rates['vat_percentage']       ?? 0);
                        $tcsp    = floatval($rates['tcs_percentage']       ?? 0);
                        if ($loc_id === 0) {
                            $loc_name = 'Base/Default';
                        } else {
                            $loc_row  = $this->Inventory_sku->query("SELECT name FROM locations WHERE id=$loc_id LIMIT 1");
                            $loc_name = addslashes($loc_row[0]['locations']['name'] ?? '');
                        }
                        $this->Inventory_sku->query(
                            "INSERT INTO inventory_sku_excise (sku_id,sku_name,location_id,location_name,excise_per_unit,addl_excise_per_unit,vat_percentage,tcs_percentage,status,sync,local_sync)
                             VALUES ($lastInsertedID,'$sku_name_esc',$loc_id,'$loc_name',$epu,$aepu,$vatp,$tcsp,1,0,0)
                             ON DUPLICATE KEY UPDATE excise_per_unit=$epu,addl_excise_per_unit=$aepu,vat_percentage=$vatp,tcs_percentage=$tcsp,status=1"
                        );
                    }
                }
                $this->Flash->success(__('Sku has been saved.'));
                return $this->redirect(array('action' => 'sku_list'));
            } else {
                $this->Flash->error(__('Sku could not be saved. Please, try again.'));
            }
        }

    }

    public function sku_list()
    {
        if(!$this->Session->check('Auth.User.id')){
            $this->redirect(array('controller'=>'dashboard','action' => 'login'));
        }

        $this->layout = "users";

        $startDate = date('m/d/Y', strtotime('-12 months'));
        $endDate = date('m/d/Y');

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

        $_shiftRange = $this->getShiftDateRange($startdate, $enddate);
        $startDatetime = $_shiftRange['startDatetime'];
        $endDatetime   = $_shiftRange['endDatetime'];

        $users = $this->Inventory_sku->query("
            SELECT inv.*,(SELECT sum(tax_percentage) from inventory_sku_tax WHERE product_id=inv.id and status=1) as total_tax
            FROM inventory_skus inv
            WHERE inv.created BETWEEN '$startDatetime' AND '$endDatetime'
            ORDER BY inv.id DESC
        ");
        $yourStartDateValue=$startDate . ' - ' . $endDate;
        $this->set('startdate', $yourStartDateValue);

        $this->set(compact('users'));

    }

    public function edit_sku()
    {
        if(!$this->Session->check('Auth.User.id')){
            $this->redirect(array('controller'=>'dashboard','action' => 'login'));
        }

        $this->layout = "users";

        $id=$_REQUEST['id'];

        $products = $this->Inventory_sku->query("SELECT * FROM inventory_skus where id=$id");
        $this->set(compact('products'));

        $product_taxes=$this->Inventory_sku_tax->find('all',array('conditions'=>array('product_id'=>$id,'status' => 1),'order'=>array('id'=>'desc')));
        $this->set(compact('product_taxes'));

        $categories = $this->Inventory_category->find('all', array('conditions' => array('status' => 1), 'order' => array('name' => 'asc')));

        $units=$this->Catalog_unit->find('all',array('conditions'=>array('status'=>1)));

        $unit=$products[0]['inventory_skus']['unit'];
        $conunits=$this->Catalog_unit_mapping->find('all',array('conditions'=>array('status'=>1,'unit_id'=>$unit)));

        $taxes=$this->Inventory_tax->find('all',array('conditions'=>array('status'=>1)));
        $options = [];
        if (!empty($products[0]['inventory_skus']['subcat_id'])) {
            $selectedcatId = $products[0]['inventory_skus']['subcat_id'];
            $selectedType = 'sub';
        } else {
            $selectedcatId = $products[0]['inventory_skus']['cat_id'];
            $selectedType = 'cat';
        }

        $selectedcategory = null;
        foreach ($categories as $category) {
            $cat_id = $category['Inventory_category']['id'];
            $cat_name = $category['Inventory_category']['name'];

            $subcategories = $this->Inventory_subcategory->find('all', array(
                'conditions' => array('cat_id' => $cat_id, 'status' => 1),
                'order' => array('name' => 'asc')
            ));
            if (!empty($subcategories)) {
                $options[$cat_name] = [];
                foreach ($subcategories as $subcategory) {
                    $subcat_id = $subcategory['Inventory_subcategory']['id'];
                    $subcat_name = $subcategory['Inventory_subcategory']['name'];
                    if ($subcat_id == $selectedcatId  && $selectedType == 'sub') {
                        $selectedcategory = $subcategory['Inventory_subcategory'];
                        $selectedcategory['_type'] = 'sub';
                    }
                    $options[$cat_name]["sub_$subcat_id"] = $subcat_name;

                }
            } else {
                if ($cat_id == $selectedcatId && $selectedType == 'cat') {
                    $selectedcategory = $category['Inventory_category'];
                    $selectedcategory['_type'] = 'cat';
                }
                $options["cat_$cat_id"] = $cat_name;
            }
        }

        if (!empty($selectedcategory)) {
            $key_prefix = ($selectedcategory['_type'] == 'sub') ? 'sub_' : 'cat_';
            $options = array($key_prefix . $selectedcategory['id'] => $selectedcategory['name']) + $options;
        }

        $sku_excise_raw = $this->Inventory_sku->query(
            "SELECT inventory_sku_excise.* FROM inventory_sku_excise WHERE inventory_sku_excise.sku_id = $id AND inventory_sku_excise.status = 1"
        );

        $all_locations_raw2 = $this->Inventory_sku->query(
            "SELECT locations.id, locations.name, locations.state FROM locations WHERE locations.status = 1 ORDER BY locations.id"
        );

        $all_locations_view = [];
        foreach ($all_locations_raw2 as $row) {
            $d = !empty($row['locations']) ? $row['locations'] : (isset($row[0]) ? $row[0] : $row);
            $all_locations_view[] = ['locations' => $d];
        }
        $sku_excise_rates_view = [];
        foreach ($sku_excise_raw as $row) {
            $d = !empty($row['inventory_sku_excise']) ? $row['inventory_sku_excise'] : (isset($row[0]) ? $row[0] : $row);
            $sku_excise_rates_view[] = ['inventory_sku_excise' => $d];
        }
        $this->set('all_locations', $all_locations_view);
        $this->set('sku_excise_rates', $sku_excise_rates_view);
        $this->set(compact('categories', 'subcategories', 'taxes', 'units', 'options', 'selectedcatId','conunits'));

        if ($this->data['User']['id']!='') {

            $id=$this->data['User']['id'];
            $name = $this->data['User']['name'];
            if($name==''){
                $this->Flash->error(__('The Name could not be Blank'));
                return $this->redirect(array('action' => 'edit_sku?id='.$id));
            }
            $brand = $this->data['User']['brand'];
            $unit = $this->data['User']['unit'];
            $price = $this->data['User']['price'];
            $remarks = $this->data['User']['remarks'];
            $item_type = $this->data['User']['item_type'];
            $conunit = $this->data['User']['conunit'];
            $factor = $this->data['User']['factor'];
            $conprice = $this->data['User']['conprice'];
            $stock = $this->data['User']['stock'] ?? 0;

            $category_id = null;
            $category_name = '';
            $subcategory_id = '';
            $subcategory_name = '';

            $categoryInput = $this->data['User']['category'] ?? '';
            if (strpos($categoryInput, 'cat_') === 0) {
                $category_id = (int) str_replace('cat_', '', $categoryInput);
                $catData = $this->Inventory_category->findById($category_id);
                if ($catData) {
                    $category_name = $catData['Inventory_category']['name'];
                }
            }elseif (strpos($categoryInput, 'sub_') === 0) {
                $subcategory_id = (int) str_replace('sub_', '', $categoryInput);
                $subcatData = $this->Inventory_subcategory->findById($subcategory_id);
                if ($subcatData) {
                    $subcategory_name = $subcatData['Inventory_subcategory']['name'];
                    $category_id = $subcatData['Inventory_subcategory']['cat_id'];
                    $category_name = $this->Inventory_category->field('name', ['id' => $category_id]);
                }
            }else {

                $newcat = trim($categoryInput);
                $this->Inventory_category->create();
                if ($this->Inventory_category->save(['name' => $newcat,
                    'sync' => 0,
                    'local_sync' => 0])) {
                    $category_id = $this->Inventory_category->id;
                    $category_name = $newcat;
                } else {
                    $this->Flash->error(__('New category could not be saved. Please try again.'));
                    return;
                }
            }

            $unitname=$this->Catalog_unit->find('first',array('conditions'=>array('id'=>$unit)));
            $unit_name=$unitname['Catalog_unit']['name'];
            $moq=$this->data['User']['moq'];

            if($conunit){
                $conunitnames=$this->Catalog_unit->find('first',array('conditions'=>array('id'=>$conunit)));
                $conunitname=$conunitnames['Catalog_unit']['name'];
            }else{
                $conunit=0;
                $factor=0;
                $conprice=0;
                $conunitname='';
            }
             $excise_applicable  = !empty($this->data['User']['excise_applicable']) ? 1 : 0;
            $has_variable_tax   = !empty($this->data['User']['has_variable_tax'])   ? 1 : 0;

            if ($has_variable_tax) { $excise_applicable = 1; }

            $this->Inventory_sku->query("UPDATE inventory_skus SET consumption_unit_id='$conunit',consumption_unit_name='$conunitname',consumption_factor='$factor',consumption_unit_price='$conprice',item_type='$item_type',moq='$moq',stock='$stock', cat_id='$category_id',cat_name='$category_name',subcat_id='$subcategory_id',subcat_name='$subcategory_name',name ='$name',brand='$brand',unit='$unit',unit_name='$unit_name',price='$price',remarks='$remarks',excise_applicable='$excise_applicable',has_variable_tax='$has_variable_tax',sync = 0,local_sync=0 WHERE id =". $id);

            $excise_rates_post = isset($_POST['excise_rates']) ? $_POST['excise_rates'] : [];
            if ($excise_applicable && !empty($excise_rates_post)) {
                $sku_name_esc = addslashes($name);
                foreach ($excise_rates_post as $loc_id => $rates) {
                    $loc_id  = intval($loc_id);
                    $epu     = floatval($rates['excise_per_unit']      ?? 0);
                    $aepu    = floatval($rates['addl_excise_per_unit'] ?? 0);
                    $vatp    = floatval($rates['vat_percentage']       ?? 0);
                    $tcsp    = floatval($rates['tcs_percentage']       ?? 0);
                    if ($loc_id === 0) {
                        $loc_name = 'Base/Default';
                    } else {
                        $loc_row  = $this->Inventory_sku->query("SELECT name FROM locations WHERE id=$loc_id LIMIT 1");
                        $loc_name = addslashes($loc_row[0]['locations']['name'] ?? '');
                    }
                    $this->Inventory_sku->query(
                        "INSERT INTO inventory_sku_excise (sku_id,sku_name,location_id,location_name,excise_per_unit,addl_excise_per_unit,vat_percentage,tcs_percentage,status,sync,local_sync)
                         VALUES ($id,'$sku_name_esc',$loc_id,'$loc_name',$epu,$aepu,$vatp,$tcsp,1,0,0)
                         ON DUPLICATE KEY UPDATE excise_per_unit=$epu,addl_excise_per_unit=$aepu,vat_percentage=$vatp,tcs_percentage=$tcsp,status=1,sku_name='$sku_name_esc',location_name='$loc_name',sync=0,local_sync=0"
                    );
                }
            } elseif (!$excise_applicable) {
                $this->Inventory_sku->query("UPDATE inventory_sku_excise SET status=0 WHERE sku_id=$id");
            }

            $this->Inventory_sku_tax->query("UPDATE inventory_sku_tax set status=0,sync = 0,local_sync=0 WHERE product_id =". $id);

            $taxes=$this->Inventory_tax->find('all',array('conditions'=>array('status'=>1)));
            foreach ($taxes as $taxess) {
                $taxID = $taxess['Inventory_tax']['id'];
                if (!empty($this->request->data['User']['tax_' . $taxID])) {
                    $selectedTaxes[] = $this->request->data['User']['tax_' . $taxID];
                    $taxdetail=$this->Inventory_tax->find('first',array('conditions'=>array('id'=>$taxID)));
                    if($taxdetail){
                        $data['Inventory_sku_tax']=array(
                            'product_id'=>$id,
                            'product_name'=>$name,
                            'tax_id'=>$taxID,
                            'tax_name'=>$taxdetail['Inventory_tax']['name'],
                            'tax_percentage'=>$taxdetail['Inventory_tax']['percentage'],
                            'sync' => 0,
                            'local_sync' => 0
                        );

                        if($this->Inventory_sku_tax->saveAll($data['Inventory_sku_tax']))
                        {

                        }else{
                            $this->Flash->error(__('SKU tax could not be saved. Please, try again.'));
                        }
                    }
                }
            }

            if(isset($_POST['tax_name'])){

                $taxnames=$_POST['tax_name'];
                $taxvalues=$_POST['tax_value'];

                for ($i = 0; $i < count($taxnames); $i++) {
                    $taxname = $taxnames[$i];
                    $percentage = $taxvalues[$i];

                    $newtaxdetail=$this->Inventory_tax->find('first',array('conditions'=>array('name'=>$taxname,'percentage'=>$percentage)));
                    if(empty($newtaxdetail)){
                        $data['Inventory_tax']=array(
                            'name'=>$taxname,
                            'percentage'=>$percentage,
                            'sync' => 0,
                            'local_sync' => 0
                        );

                        if($this->Inventory_tax->saveAll($data['Inventory_tax']))
                        {
                            $taxlastInsertedID = $this->Inventory_tax->id;

                        } else {
                            $this->Flash->error(__('New Tax could not be saved. Please, try again.'));
                        }
                    }

                    $data['Inventory_sku_tax']=array(
                        'product_id'=>$id,
                        'product_name'=>$name,
                        'tax_id'=>$taxlastInsertedID,
                        'tax_name'=>$taxname,
                        'tax_percentage'=>$percentage,
                        'sync' => 0,
                        'local_sync' => 0
                    );

                    if($this->Inventory_sku_tax->saveAll($data['Inventory_sku_tax']))
                    {

                    }else{
                        $this->Flash->error(__('SKU New tax could not be saved. Please, try again.'));
                    }

                }
            }

            $this->_refreshAllCosts();

            $this->Flash->success(__('Sku has been Updated.'));
            return $this->redirect(array('action' => 'sku_list'));
        }

    }

    public function view_sku()
    {
        if(!$this->Session->check('Auth.User.id')){
            $this->redirect(array('controller'=>'dashboard','action' => 'login'));
        }
        $this->layout = "users";
        $id=$_REQUEST['id'];

        $products = $this->Inventory_sku->query("SELECT * FROM inventory_skus where id=$id");
        $this->set(compact('products'));

        $taxes=$this->Inventory_sku_tax->query("SELECT CONCAT(tax_name, '(', tax_percentage, '%)') as taxname ,id FROM inventory_sku_tax where product_id=$id and status=1");
        $this->set(compact('taxes'));

        $sku_excise_raw = $this->Inventory_sku->query(
            "SELECT inventory_sku_excise.* FROM inventory_sku_excise WHERE inventory_sku_excise.sku_id = $id AND inventory_sku_excise.status = 1"
        );
        $all_locations_raw = $this->Inventory_sku->query(
            "SELECT locations.id, locations.name, locations.state FROM locations WHERE locations.status = 1 ORDER BY locations.id"
        );
        $all_locations_view = [];
        foreach ($all_locations_raw as $row) {
            $d = !empty($row['locations']) ? $row['locations'] : (isset($row[0]) ? $row[0] : $row);
            $all_locations_view[] = ['locations' => $d];
        }
        $sku_excise_rates_view = [];
        foreach ($sku_excise_raw as $row) {
            $d = !empty($row['inventory_sku_excise']) ? $row['inventory_sku_excise'] : (isset($row[0]) ? $row[0] : $row);
            $sku_excise_rates_view[] = ['inventory_sku_excise' => $d];
        }
        $this->set('all_locations', $all_locations_view);
        $this->set('sku_excise_rates', $sku_excise_rates_view);
    }

    public function create_vendor()
    {
        if(!$this->Session->check('Auth.User.id')){
            $this->redirect(array('controller'=>'dashboard','action' => 'login'));
        }

        $this->layout = "users";

        $authUser = $this->Session->read('Auth.User');
        $userRole = $authUser['role'] ?? '';
        $userLocationId = $authUser['location_ids'] ?? 0;

        $userLocationIds = is_array($userLocationId)
            ? $userLocationId
            : array_map('trim', explode(',', $userLocationId));

        $locationCondition = ['status' => 1,'type !='=>'DC'];

        if ($userRole !== 'admin') {
            $locationCondition['id'] = $userLocationIds;
        }

        $locations = $this->Location->find('all', [
            'conditions' => [
                $locationCondition
            ]
        ]);

        $areas=$this->Location->find('all',array('conditions'=>$locationCondition));
        $this->set(compact('areas'));

        $indiaStates=states;
        $this->set(compact('indiaStates'));

        if ($this->request->is(array('post', 'put'))) {

            $name = $this->data['User']['name'];
            if($name==''){
                $this->Flash->error(__('The Name could not be Blank'));
                return $this->redirect(array('action' => 'create_vendor'));
            }

            $office_address = $this->data['User']['office_address'];
            $email = $this->data['User']['email'];
            $company = $this->data['User']['company'];
            $gst = $this->data['User']['gst'];
            $bank_ac = $this->data['User']['bank_ac'];
            $bank_name = $this->data['User']['bank_name'];
            $advance_percentage = $this->data['User']['advance_percentage'];
            $mobile = $this->data['User']['mobile'];
            $landline = $this->data['User']['landline'];
            $state = $this->data['User']['state'];
            $pan = $this->data['User']['pan'];
            $tan = $this->data['User']['tan'];
            $ifsc = $this->data['User']['ifsc'];
            $payment_days = $this->data['User']['payment_days'];
            $approval = $this->data['User']['approval'];

            $gstfilename='';
            $gst_image=$this->data['User']['gst_image'];
            if (!empty($gst_image['name'])) {
                $uploadPath = WWW_ROOT . 'uploads' . DS . 'vendor_images' . DS;
                $file=basename(time().$gst_image['name']);
                $file = preg_replace('/[^A-Za-z0-9_\-\.]/', '_', $file);
                $uploadFile = $uploadPath . $file;

                if (move_uploaded_file($gst_image['tmp_name'], $uploadFile)) {
                    $gstfilename=$file;
                }
            }

            $agreementfilename='';
            $agreement_image=$this->data['User']['agreement_image'];
            if (!empty($agreement_image['name'])) {
                $uploadPath = WWW_ROOT . 'uploads' . DS . 'vendor_images' . DS;
                $file=basename(time().$agreement_image['name']);
                $file = preg_replace('/[^A-Za-z0-9_\-\.]/', '_', $file);
                $uploadFile = $uploadPath . $file;

                if (move_uploaded_file($agreement_image['tmp_name'], $uploadFile)) {
                    $agreementfilename=$file;
                }
            }

            $panfilename='';
            $pan_image=$this->data['User']['pan_image'];
            if (!empty($pan_image['name'])) {
                $uploadPath = WWW_ROOT . 'uploads' . DS . 'vendor_images' . DS;
                $file=basename(time().$pan_image['name']);
                $file = preg_replace('/[^A-Za-z0-9_\-\.]/', '_', $file);
                $uploadFile = $uploadPath . $file;

                if (move_uploaded_file($pan_image['tmp_name'], $uploadFile)) {
                    $panfilename=$file;
                }
            }

            $tanfilename='';
            $tan_image=$this->data['User']['tan_image'];
            if (!empty($tan_image['name'])) {
                $uploadPath = WWW_ROOT . 'uploads' . DS . 'vendor_images' . DS;
                $file=basename(time().$tan_image['name']);
                $file = preg_replace('/[^A-Za-z0-9_\-\.]/', '_', $file);
                $uploadFile = $uploadPath . $file;

                if (move_uploaded_file($tan_image['tmp_name'], $uploadFile)) {
                    $tanfilename=$file;
                }
            }

            $fssaifilename='';
            $fssai_image=$this->data['User']['fssai_image'];
            if (!empty($fssai_image['name'])) {
                $uploadPath = WWW_ROOT . 'uploads' . DS . 'vendor_images' . DS;
                $file=basename(time().$fssai_image['name']);
                $file = preg_replace('/[^A-Za-z0-9_\-\.]/', '_', $file);
                $uploadFile = $uploadPath . $file;

                if (move_uploaded_file($fssai_image['tmp_name'], $uploadFile)) {
                    $fssaifilename=$file;
                }
            }

            $data['Inventory_vendor']=array(
                'name'=>$name,
                'mobile'=>$mobile,
                'landline'=>$landline,
                'email'=>$email,
                'office_address'=>$office_address,
                'state'=>$state,
                'company'=>$company,
                'pan'=>$pan,
                'gst'=>$gst,
                'tan'=>$tan,
                'bank_ac'=>$bank_ac,
                'ifsc'=>$ifsc,
                'bank_name'=>$bank_name,
                'advance_percentage'=>$advance_percentage,
                'payment_days'=>$payment_days,
                'pan_image'=>$panfilename,
                'gst_image'=>$gstfilename,
                'tan_image'=>$tanfilename,
                'agreement_image'=>$agreementfilename,
                'po_approval_required'=>$approval,
                'fssai_image'=>$fssaifilename,
                'sync' => 0,
                'local_sync' => 0
            );

            if($this->Inventory_vendor->saveAll($data['Inventory_vendor']))
            {
                $lastInsertedID = $this->Inventory_vendor->id;

                $areas=$this->Location->find('all',array('conditions'=>array('status'=>1)));
                foreach ($areas as $area) {
                    $ID = $area['Location']['id'];
                    if (!empty($this->request->data['User']['area_' . $ID])) {
                        $selectedarea[] = $this->request->data['User']['area_' . $ID];
                        $areadetail=$this->Location->find('first',array('conditions'=>array('id'=>$ID)));
                        if($areadetail){
                            $data['Inventory_vendor_service_area']=array(
                                'vendor_id'=>$lastInsertedID,
                                'area_id'=>$ID,
                                'area_name'=>$areadetail['Location']['name'],
                                'sync' => 0,
                                'local_sync' => 0
                            );

                            if($this->Inventory_vendor_service_area->saveAll($data['Inventory_vendor_service_area']))
                            {

                            }else{
                                $this->Flash->error(__('area could not be saved. Please, try again.'));
                            }
                        }
                    }
                }

                $this->Flash->success(__('Vendor has been saved.'));
                return $this->redirect(array('action' => 'map_vendor_with_sku?id='.$lastInsertedID));
            } else {
                $this->Flash->error(__('Sku could not be saved. Please, try again.'));
            }
        }

    }

    public function vendor_list()
    {
        if(!$this->Session->check('Auth.User.id')){
            $this->redirect(array('controller'=>'dashboard','action' => 'login'));
        }

        $this->layout = "users";

        $authUser = $this->Session->read('Auth.User');
        $userRole = $authUser['role'] ?? '';
        $userLocationId = $authUser['location_ids'] ?? 0;

        $startDate = date('m/d/Y', strtotime('-12 months'));
        $endDate = date('m/d/Y');

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

        $locationCondition = '';
        if ($userRole !== 'admin' && $userRole !== 'subadmin') {
            $locationCondition = "and id IN (SELECT vendor_id FROM `inventory_vendor_service_areas` WHERE area_id IN (" . $userLocationId . "))";
        }

        $_shiftRange = $this->getShiftDateRange($startdate, $enddate);
        $startDatetime = $_shiftRange['startDatetime'];
        $endDatetime   = $_shiftRange['endDatetime'];

        $users = $this->Inventory_vendor->query("SELECT * FROM inventory_vendors where created between '$startDatetime' and  '$endDatetime' $locationCondition ORDER BY id desc");
        $yourStartDateValue=$startDate . ' - ' . $endDate;
        $this->set('startdate', $yourStartDateValue);

        $this->set(compact('users'));

    }

    public function edit_vendor()
    {
        if(!$this->Session->check('Auth.User.id')){
            $this->redirect(array('controller'=>'dashboard','action' => 'login'));
        }

        $this->layout = "users";

        $authUser = $this->Session->read('Auth.User');
        $userRole = $authUser['role'] ?? '';
        $userLocationId = $authUser['location_ids'] ?? 0;

        $locationCondition = ['status' => 1,'type !='=>'DC'];

        $indiaStates=states;
        $this->set(compact('indiaStates'));

        if ($userRole !== 'admin') {

            $userLocationIds = is_array($userLocationId) ? $userLocationId : [$userLocationId];
            $locationCondition['id'] = $userLocationIds;
        }

        $areas=$this->Location->find('all',array('conditions'=>$locationCondition));
        $this->set(compact('areas'));
        $id=$_REQUEST['id'];

        $products = $this->Inventory_vendor->query("SELECT * FROM inventory_vendors where id=$id");
        $this->set(compact('products'));

        $vendor_areas=$this->Inventory_vendor_service_area->find('all',array('conditions'=>array('vendor_id'=>$id,'status'=>1),'order'=>array('id'=>'desc')));
        $this->set(compact('vendor_areas'));

        if ($this->data['User']['id']!='') {

            $id=$this->data['User']['id'];
            $name = $this->data['User']['name'];
            if($name==''){
                $this->Flash->error(__('The Name could not be Blank'));
                return $this->redirect(array('action' => 'edit_vendor?id='.$id));
            }
            $email = $this->data['User']['email'];
            $office_address = $this->data['User']['office_address'];
            $company = $this->data['User']['company'];
            $gst = $this->data['User']['gst'];
            $bank_ac = $this->data['User']['bank_ac'];
            $bank_name = $this->data['User']['bank_name'];
            $advance_percentage = $this->data['User']['advance_percentage'];
            $mobile = $this->data['User']['mobile'];
            $landline = $this->data['User']['landline'];
            $state = $this->data['User']['state'];
            $pan = $this->data['User']['pan'];
            $tan = $this->data['User']['tan'];
            $ifsc = $this->data['User']['ifsc'];
            $payment_days = $this->data['User']['payment_days'];
            $approval=$this->data['User']['approval'];

            $gst_image=$this->data['User']['gst_image'];
            if (!empty($gst_image['name'])) {
                $uploadPath = WWW_ROOT . 'uploads' . DS . 'vendor_images' . DS;
                $file=basename(time().$gst_image['name']);
                $file = preg_replace('/[^A-Za-z0-9_\-\.]/', '_', $file);
                $uploadFile = $uploadPath . $file;

                if (move_uploaded_file($gst_image['tmp_name'], $uploadFile)) {
                    $gstfilename=$file;
                    $this->Inventory_vendor->query("UPDATE inventory_vendors SET gst_image='$gstfilename',sync = 0,local_sync=0 WHERE id =". $id);
                }
            }

            $agreement_image=$this->data['User']['agreement_image'];
            if (!empty($agreement_image['name'])) {
                $uploadPath = WWW_ROOT . 'uploads' . DS . 'vendor_images' . DS;
                $file=basename(time().$agreement_image['name']);
                $file = preg_replace('/[^A-Za-z0-9_\-\.]/', '_', $file);
                $uploadFile = $uploadPath . $file;

                if (move_uploaded_file($agreement_image['tmp_name'], $uploadFile)) {
                    $agreementfilename=$file;
                    $this->Inventory_vendor->query("UPDATE inventory_vendors SET agreement_image='$agreementfilename',sync = 0,local_sync=0 WHERE id =". $id);
                }
            }

            $pan_image=$this->data['User']['pan_image'];
            if (!empty($pan_image['name'])) {
                $uploadPath = WWW_ROOT . 'uploads' . DS . 'vendor_images' . DS;
                $file=basename(time().$pan_image['name']);
                $file = preg_replace('/[^A-Za-z0-9_\-\.]/', '_', $file);
                $uploadFile = $uploadPath . $file;

                if (move_uploaded_file($pan_image['tmp_name'], $uploadFile)) {
                    $panfilename=$file;
                    $this->Inventory_vendor->query("UPDATE inventory_vendors SET pan_image='$panfilename',sync = 0,local_sync=0 WHERE id =". $id);
                }
            }

            $tan_image=$this->data['User']['tan_image'];
            if (!empty($tan_image['name'])) {
                $uploadPath = WWW_ROOT . 'uploads' . DS . 'vendor_images' . DS;
                $file=basename(time().$tan_image['name']);
                $file = preg_replace('/[^A-Za-z0-9_\-\.]/', '_', $file);
                $uploadFile = $uploadPath . $file;

                if (move_uploaded_file($tan_image['tmp_name'], $uploadFile)) {
                    $tanfilename=$file;
                    $this->Inventory_vendor->query("UPDATE inventory_vendors SET tan_image='$tanfilename',sync = 0,local_sync=0 WHERE id =". $id);
                }
            }

            $fssai_image=$this->data['User']['fssai_image'];
            if (!empty($fssai_image['name'])) {
                $uploadPath = WWW_ROOT . 'uploads' . DS . 'vendor_images' . DS;
                $file=basename(time().$fssai_image['name']);
                $file = preg_replace('/[^A-Za-z0-9_\-\.]/', '_', $file);
                $uploadFile = $uploadPath . $file;

                if (move_uploaded_file($fssai_image['tmp_name'], $uploadFile)) {
                    $fssaifilename=$file;
                    $this->Inventory_vendor->query("UPDATE inventory_vendors SET fssai_image='$fssaifilename',sync = 0,local_sync=0 WHERE id =". $id);
                }
            }

            $this->Inventory_vendor->query("UPDATE inventory_vendors SET po_approval_required='$approval',name ='$name',mobile='$mobile',landline='$landline',email='$email',office_address='$office_address',state='$state',company='$company',pan='$pan',gst='$gst',tan='$tan',bank_ac='$bank_ac',ifsc='$ifsc',bank_name='$bank_name',advance_percentage='$advance_percentage',payment_days='$payment_days',sync = 0,local_sync=0 WHERE id =". $id);

            $prevareas=$this->Inventory_vendor_service_area->query("update inventory_vendor_service_areas set status=0,sync = 0,local_sync=0 where vendor_id=$id");

            $areas=$this->Location->find('all',array('conditions'=>array('status'=>1)));
            foreach ($areas as $area) {
                $ID = $area['Location']['id'];
                if (!empty($this->request->data['User']['area_' . $ID])) {
                    $selectedarea[] = $this->request->data['User']['area_' . $ID];
                    $areadetail=$this->Location->find('first',array('conditions'=>array('id'=>$ID)));
                    if($areadetail){
                        $data['Inventory_vendor_service_area']=array(
                            'vendor_id'=>$id,
                            'area_id'=>$ID,
                            'area_name'=>$areadetail['Location']['name'],
                            'sync' => 0,
                            'local_sync' => 0
                        );

                        if($this->Inventory_vendor_service_area->saveAll($data['Inventory_vendor_service_area']))
                        {

                        }else{
                            $this->Flash->error(__('area could not be saved. Please, try again.'));
                        }
                    }
                }
            }

            $this->Flash->success(__('Vendor has been Updated.'));
            return $this->redirect(array('action' => 'vendor_list'));
        }

    }

    public function view_vendor()
    {
        if(!$this->Session->check('Auth.User.id')){
            $this->redirect(array('controller'=>'dashboard','action' => 'login'));
        }
        $this->layout = "users";
        $id=$_REQUEST['id'];

        $products = $this->Inventory_vendor->query("SELECT * FROM inventory_vendors where id=$id");
        $this->set(compact('products'));

        $areas=$this->Inventory_vendor_service_area->query("SELECT area_name,id FROM inventory_vendor_service_areas where vendor_id=$id and status=1");
        $this->set(compact('areas'));
    }
    
    public function map_vendor_with_sku() {
        if(!$this->Session->check('Auth.User.id')){
            $this->redirect(array('controller'=>'dashboard','action' => 'login'));      
        }
        $this->layout = "users";
         
        if ($this->request->is('post')) {
            $action = isset($_POST['action']) ? $_POST['action'] : '';

            if ($action == 'remove_sku') {
                $remove_sku_id = isset($_POST['remove_sku_id']) ? intval($_POST['remove_sku_id']) : 0;
                $vendor_id = isset($_POST['vendor_id']) ? intval($_POST['vendor_id']) : 0;

                if ($vendor_id > 0 && $remove_sku_id > 0) {
                    $this->Inventory_vendor_sku_served->query(
                        "UPDATE inventory_vendor_sku_served SET status=0, sync=0, local_sync=0 WHERE vendor_id = $vendor_id AND skus_id = $remove_sku_id"
                    );
                    echo json_encode(['status' => 'success', 'message' => 'SKU removed successfully']);
                } else {
                    echo json_encode(['status' => 'error', 'message' => 'Invalid parameters']);
                }
                exit;
            }

            if ($action == 'reset_all') {
                $vendor_id = isset($_POST['vendor_id']) ? intval($_POST['vendor_id']) : 0;
                if ($vendor_id > 0) {
                    $this->Inventory_vendor_sku_served->query(
                        "UPDATE inventory_vendor_sku_served SET status=0, sync=0, local_sync=0 WHERE vendor_id = $vendor_id"
                    );
                    echo json_encode(['status' => 'success', 'message' => 'All mappings reset successfully']);
                } else {
                    echo json_encode(['status' => 'error', 'message' => 'Invalid vendor ID']);
                }
                exit;
            }

            if (isset($_POST['checkedData'])) {
                $checkeddatas = json_decode($_POST['checkedData'], true);
                $vendor_id_reset = isset($_POST['vendor_id_reset']) ? intval($_POST['vendor_id_reset']) : 0;
                $target_vendor_id = isset($_POST['vendor_id']) ? intval($_POST['vendor_id']) : 0;

                if ($vendor_id_reset > 0) {
                    $this->Inventory_vendor_sku_served->query(
                        "UPDATE inventory_vendor_sku_served SET status=0, sync=0, local_sync=0 WHERE vendor_id = " . $vendor_id_reset
                    );
                }
                 
                if (is_array($checkeddatas)) {
                    foreach ($checkeddatas as $checkeddata) {
                        $skuid     = intval($checkeddata['id']);
                        $price     = floatval($checkeddata['price']);
                        $sku_name  = addslashes($checkeddata['sku_name']);
                        $sku_brand = addslashes($checkeddata['sku_brand']);
                        $unit_name = addslashes($checkeddata['unit_name']);
                        $unit_id   = addslashes($checkeddata['unit_id']);
                        $v_id      = intval($checkeddata['vendor_id']);
                        if ($v_id == 0 && $target_vendor_id > 0) {
                            $v_id = $target_vendor_id;
                        }
                        
                        if ($v_id > 0 && $skuid > 0) {
                            $existing = $this->Inventory_vendor_sku_served->query(
                                "SELECT id FROM inventory_vendor_sku_served WHERE vendor_id = $v_id AND skus_id = $skuid LIMIT 1"
                            );

                            if (!empty($existing)) {
                                $existing_id = $existing[0]['inventory_vendor_sku_served']['id'];
                                $this->Inventory_vendor_sku_served->query(
                                    "UPDATE inventory_vendor_sku_served 
                                     SET status=1, sku_price=$price, sku_name='$sku_name', sku_brand='$sku_brand', 
                                         sku_unit_name='$unit_name', sku_unit='$unit_id', sync=0, local_sync=0 
                                     WHERE id = $existing_id"
                                );
                            } else {
                                $this->Inventory_vendor_sku_served->query(
                                    "INSERT INTO inventory_vendor_sku_served (vendor_id, skus_id, sku_name, sku_brand, sku_unit_name, sku_unit, sku_price, status, sync, local_sync)
                                     VALUES ($v_id, $skuid, '$sku_name', '$sku_brand', '$unit_name', '$unit_id', $price, 1, 0, 0)"
                                );
                            }
                        }
                    }
                }

                if ($this->request->is('ajax')) {
                    echo json_encode(['status' => 'success', 'message' => 'SKUs mapped successfully']);
                    exit;
                }

                $this->Flash->success(__('SKUs mapped successfully.')); 
                return $this->redirect(['controller' => 'dashboard', 'action' => 'map_vendor_with_sku', '?' => ['id' => $target_vendor_id ? $target_vendor_id : $vendor_id_reset]]);
            }
        }

        $id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
        $vendor_info = null;
        $mapped_skus = array();
        $unmapped_skus = array();

        if ($id > 0) {
            $vendor_info_query = $this->Inventory_vendor->query("SELECT id, name, company FROM inventory_vendors WHERE id=$id LIMIT 1");
            if (!empty($vendor_info_query)) {
                $vendor_info = $vendor_info_query[0]['inventory_vendors'];
            }

            // High performance optimized query for Mapped SKUs
            $mapped_skus = $this->Inventory_sku->query("
                SELECT 
                    s.id AS sku_id,
                    s.name AS sku_name,
                    s.brand AS sku_brand,
                    s.cat_id AS cat_id,
                    COALESCE(c.name, s.cat_name, 'Uncategorized') AS cat_name,
                    s.subcat_id AS subcat_id,
                    s.subcat_name AS subcat_name,
                    s.unit_name AS sku_unit_name,
                    s.unit AS sku_unit,
                    v.sku_price AS vendor_price,
                    v.id AS served_id
                FROM inventory_vendor_sku_served v
                JOIN inventory_skus s ON s.id = v.skus_id AND s.status = 1
                LEFT JOIN inventory_category c ON c.id = s.cat_id
                WHERE v.vendor_id = $id AND v.status = 1
                ORDER BY s.name ASC
            ");

            // High performance optimized antijoin query for Unmapped SKUs
            $unmapped_skus = $this->Inventory_sku->query("
                SELECT 
                    s.id AS sku_id,
                    s.name AS sku_name,
                    s.brand AS sku_brand,
                    s.cat_id AS cat_id,
                    COALESCE(c.name, s.cat_name, 'Uncategorized') AS cat_name,
                    s.subcat_id AS subcat_id,
                    s.subcat_name AS subcat_name,
                    s.unit_name AS sku_unit_name,
                    s.unit AS sku_unit,
                    s.price AS base_price
                FROM inventory_skus s
                LEFT JOIN inventory_category c ON c.id = s.cat_id
                LEFT JOIN inventory_vendor_sku_served v ON v.skus_id = s.id AND v.vendor_id = $id AND v.status = 1
                WHERE s.status = 1 AND v.id IS NULL
                ORDER BY s.name ASC
            ");
        }

        $categories = $this->Inventory_sku->query("SELECT id, name FROM inventory_category WHERE status = 1 ORDER BY name ASC");

        $this->set(compact('id', 'vendor_info', 'mapped_skus', 'unmapped_skus', 'categories'));
    }
    
    public function create_semifinished()
    {
        if(!$this->Session->check('Auth.User.id')){
            $this->redirect(array('controller'=>'dashboard','action' => 'login'));
        }

        $this->layout = "users";

        $units=$this->Catalog_unit->find('all',array('conditions'=>array('status'=>1)));
        $taxes=$this->Inventory_tax->find('all',array('conditions'=>array('status'=>1)));

        $categorys = $this->Inventory_category->find('all', ['conditions' => ['status' => 1]]);

        $options = [];
        foreach ($categorys as $category) {
            $cat_id = $category['Inventory_category']['id'];
            $cat_name = $category['Inventory_category']['name'];

            $subcategorys = $this->Inventory_subcategory->find('all', [
                'conditions' => ['cat_id' => $cat_id, 'status' => 1]
            ]);

            if (!empty($subcategorys)) {
                foreach ($subcategorys as $sub) {
                    $sub_id = $sub['Inventory_subcategory']['id'];
                    $sub_name = $sub['Inventory_subcategory']['name'];
                    $options[$cat_name]["sub_$sub_id"] = $sub_name;
                }
            } else {
                $options["cat_$cat_id"] = $cat_name;
            }
        }
        $this->set(compact('categorys', 'subcategorys', 'units', 'taxes', 'options'));

        if ($this->request->is(array('post', 'put'))) {

            $name = $this->data['User']['name'];
            if($name==''){
                $this->Flash->error(__('The Name could not be Blank'));
                return $this->redirect(array('action' => 'create_semifinished'));
            }

            $name = $this->data['User']['name'];
            $unit = $this->data['User']['unit'];
            $price = $this->data['User']['price'];
            $remarks = $this->data['User']['remarks'];

            $conunit = $this->data['User']['conunit'];
            $factor = $this->data['User']['factor'];
            $conprice = $this->data['User']['conprice'];

            $category_id = null;
            $category_name = '';
            $subcategory_id = '';
            $subcategory_name = '';

            $categoryInput = $this->data['User']['category'] ?? '';

            if (strpos($categoryInput, 'cat_') === 0) {
                $category_id = (int) str_replace('cat_', '', $categoryInput);
                $catData = $this->Inventory_category->findById($category_id);
                if ($catData) {
                    $category_name = $catData['Inventory_category']['name'];
                }

            } elseif (strpos($categoryInput, 'sub_') === 0) {

                $subcategory_id = (int) str_replace('sub_', '', $categoryInput);
                $subcatData = $this->Inventory_subcategory->findById($subcategory_id);
                if ($subcatData) {
                    $subcategory_name = $subcatData['Inventory_subcategory']['name'];
                    $category_id = $subcatData['Inventory_subcategory']['cat_id'];
                    $category_name = $this->Inventory_category->field('name', ['id' => $category_id]);
                }
            } else {

                $newcat = trim($categoryInput);
                $this->Inventory_category->create();
                if ($this->Inventory_category->save(['name' => $newcat,
                    'sync' => 0,
                    'local_sync' => 0])) {
                    $category_id = $this->Inventory_category->id;
                    $category_name = $newcat;
                } else {
                    $this->Flash->error(__('New category could not be saved. Please try again.'));
                    return;
                }
            }

            $unitname=$this->Catalog_unit->find('first',array('conditions'=>array('id'=>$unit)));
            $conunitname=$this->Catalog_unit->find('first',array('conditions'=>array('id'=>$conunit)));

            $data['Inventory_semifinished']=array(
                'name'=>$name,
                'unit'=>$unit,
                'cat_id' => $category_id,
                'cat_name' => $category_name,
                'subcat_id' => $subcategory_id,
                'subcat_name' => $subcategory_name,
                'unit_name'=>$unitname['Catalog_unit']['name'],
                'price'=>$price,
                'consumption_unit_id'=>$conunit,
                'consumption_unit_name'=>$conunitname['Catalog_unit']['name'],
                'consumption_factor'=>$factor,
                'consumption_unit_price'=>$conprice,
                'remarks'=>$remarks,
                'moq' => $this->data['User']['moq'] ?? 0,
                'stock' => $this->data['User']['stock'] ?? 0,
                'sync' => 0,
                'local_sync' => 0
            );

            if($this->Inventory_semifinished->saveAll($data['Inventory_semifinished']))
            {
                $lastInsertedID = $this->Inventory_semifinished->id;

                $taxes=$this->Inventory_tax->find('all',array('conditions'=>array('status'=>1)));
                foreach ($taxes as $taxess) {
                    $taxID = $taxess['Inventory_tax']['id'];
                    if (!empty($this->request->data['User']['tax_' . $taxID])) {
                        $selectedTaxes[] = $this->request->data['User']['tax_' . $taxID];
                        $taxdetail=$this->Inventory_tax->find('first',array('conditions'=>array('id'=>$taxID)));
                        if($taxdetail){
                            $data['Inventory_semifinished_tax']=array(
                                'product_id'=>$lastInsertedID,
                                'product_name'=>$name,
                                'tax_id'=>$taxID,
                                'tax_name'=>$taxdetail['Inventory_tax']['name'],
                                'tax_percentage'=>$taxdetail['Inventory_tax']['percentage'],
                                'sync' => 0,
                                'local_sync' => 0
                            );

                            if($this->Inventory_semifinished_tax->saveAll($data['Inventory_semifinished_tax']))
                            {

                            }else{
                                $this->Flash->error(__('Semi-Finished tax could not be saved. Please, try again.'));
                            }
                        }
                    }
                }

                if(isset($_POST['custom_tax_name'])){

                    $taxnames=$_POST['custom_tax_name'];
                    $taxvalues=$_POST['custom_tax_value'];

                    for ($i = 0; $i < count($taxnames); $i++) {
                        $taxname = $taxnames[$i];
                        $percentage = $taxvalues[$i];

                        $newtaxdetail=$this->Inventory_tax->find('first',array('conditions'=>array('name'=>$taxname,'percentage'=>$percentage)));
                        if(empty($newtaxdetail)){
                            $data['Inventory_tax']=array(
                                'name'=>$taxname,
                                'percentage'=>$percentage,
                                'sync' => 0,
                                'local_sync' => 0
                            );

                            if($this->Inventory_tax->saveAll($data['Inventory_tax']))
                            {
                                $taxlastInsertedID = $this->Inventory_tax->id;

                            } else {
                                $this->Flash->error(__('New Tax could not be saved. Please, try again.'));
                            }
                        }

                        $data['Inventory_semifinished_tax']=array(
                            'product_id'=>$lastInsertedID,
                            'product_name'=>$name,
                            'tax_id'=>$taxlastInsertedID,
                            'tax_name'=>$taxname,
                            'tax_percentage'=>$percentage,
                            'sync' => 0,
                            'local_sync' => 0
                        );

                        if($this->Inventory_semifinished_tax->saveAll($data['Inventory_semifinished_tax']))
                        {

                        }else{
                            $this->Flash->error(__('Semi-Finished New tax could not be saved. Please, try again.'));
                        }

                    }
                }
                $this->Flash->success(__('Semi-Finished has been saved.'));
                return $this->redirect(array('action' => 'semifinished_list'));
            } else {
                $this->Flash->error(__('Semi-Finished could not be saved. Please, try again.'));
            }
        }

    }

    public function semifinished_list()
    {
        if(!$this->Session->check('Auth.User.id')){
            $this->redirect(array('controller'=>'dashboard','action' => 'login'));
        }

        $this->layout = "users";

        $startDate = date('m/d/Y', strtotime('-12 months'));
        $endDate = date('m/d/Y');

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

        $_shiftRange = $this->getShiftDateRange($startdate, $enddate);
        $startDatetime = $_shiftRange['startDatetime'];
        $endDatetime   = $_shiftRange['endDatetime'];

        $users = $this->Inventory_semifinished->query("
            SELECT inventory_semifinished.*,
              COALESCE(vsf.batch_cost, 0) as recipe_batch_cost,
              COALESCE(vsf.unit_cost, 0) as recipe_unit_cost,
              (SELECT COUNT(*) FROM inventory_linking il
               WHERE il.component_id = inventory_semifinished.id
                 AND il.component_type = 'Semifinished' AND il.status = 1
                 AND (inventory_semifinished.portion_weight_grams IS NULL OR inventory_semifinished.portion_weight_grams = 0)
                 AND (
                   (LOWER(inventory_semifinished.consumption_unit_name) IN ('por','portion','nos','no','pce','pcs','ea','each')
                    AND LOWER(il.component_unit_name) IN ('gms','gm','g','gram','grams','ml','millilitre'))
                   OR
                   (LOWER(inventory_semifinished.consumption_unit_name) IN ('gms','gm','g','gram','grams','ml','millilitre')
                    AND LOWER(il.component_unit_name) IN ('por','portion','nos','no','pce','pcs','ea','each'))
                 )
              ) AS has_conv_mismatch,
              (SELECT COUNT(*) FROM inventory_linking il2
               JOIN inventory_skus sk ON sk.id = il2.component_id AND il2.component_type = 'Sku'
               WHERE il2.parent_id = inventory_semifinished.id
                 AND il2.parent_type = 'Semifinished' AND il2.status = 1
                 AND LOWER(il2.component_unit_name) IN ('gms','gm','g','gram','grams','kg','kgs','ml','millilitre','l','ltr','litre','nos','no','pcs','pce','ea','each','por','portion','pc')
                 AND (LOWER(sk.consumption_unit_name) IN ('gms','gm','g','gram','grams','kg','kgs','ml','millilitre','l','ltr','litre','nos','no','pcs','pce','ea','each','por','portion','pc')
                   OR LOWER(sk.consumption_unit_name) LIKE '%gm%' OR LOWER(sk.consumption_unit_name) LIKE '%gram%'
                   OR LOWER(sk.consumption_unit_name) LIKE '%ml%' OR LOWER(sk.consumption_unit_name) LIKE '%litre%')
                 AND CASE
                   WHEN LOWER(il2.component_unit_name) IN ('gms','gm','g','gram','grams','kg','kgs') THEN 'weight'
                   WHEN LOWER(il2.component_unit_name) IN ('ml','millilitre','l','ltr','litre') THEN 'volume'
                   ELSE 'piece'
                 END != CASE
                   WHEN LOWER(sk.consumption_unit_name) IN ('gms','gm','g','gram','grams','kg','kgs')
                     OR LOWER(sk.consumption_unit_name) LIKE '%gm%' OR LOWER(sk.consumption_unit_name) LIKE '%gram%' THEN 'weight'
                   WHEN LOWER(sk.consumption_unit_name) IN ('ml','millilitre','l','ltr','litre')
                     OR LOWER(sk.consumption_unit_name) LIKE '%ml%' OR LOWER(sk.consumption_unit_name) LIKE '%litre%' THEN 'volume'
                   ELSE 'piece'
                 END
              ) AS has_recipe_unit_mismatch
            FROM inventory_semifinished
            LEFT JOIN v_semifinished_recipe_cost vsf ON vsf.id = inventory_semifinished.id
            WHERE inventory_semifinished.created BETWEEN '$startDatetime' AND '$endDatetime'
            ORDER BY inventory_semifinished.id DESC
        ");
        $yourStartDateValue=$startDate . ' - ' . $endDate;
        $this->set('startdate', $yourStartDateValue);

        $this->set(compact('users'));

    }

    public function edit_semifinished()
    {
        if(!$this->Session->check('Auth.User.id')){
            $this->redirect(array('controller'=>'dashboard','action' => 'login'));
        }

        $this->layout = "users";

        $units=$this->Catalog_unit->find('all',array('conditions'=>array('status'=>1)));
        $taxes=$this->Inventory_tax->find('all',array('conditions'=>array('status'=>1)));

        $id=$_REQUEST['id'];

        $products = $this->Inventory_semifinished->query("SELECT * FROM inventory_semifinished where id=$id and status=1");
        $this->set(compact('products'));

        $product_taxes=$this->Inventory_semifinished_tax->find('all',array('conditions'=>array('product_id'=>$id,'status'=>1),'order'=>array('id'=>'desc')));
        $this->set(compact('product_taxes'));

        $categories = $this->Inventory_category->find('all', array('conditions' => array('status' => 1), 'order' => array('name' => 'asc')));

        $unit=$products[0]['inventory_semifinished']['unit'];
        $conunits=$this->Catalog_unit_mapping->find('all',array('conditions'=>array('status'=>1,'unit_id'=>$unit)));

        $options = [];
        if (!empty($products[0]['inventory_semifinished']['subcat_id'])) {
            $selectedcatId = $products[0]['inventory_semifinished']['subcat_id'];
            $selectedType = 'sub';
        } else {
            $selectedcatId = $products[0]['inventory_semifinished']['cat_id'];
            $selectedType = 'cat';
        }

        $selectedcategory = null;

        foreach ($categories as $category) {
            $cat_id = $category['Inventory_category']['id'];
            $cat_name = $category['Inventory_category']['name'];
            $subcategories = $this->Inventory_subcategory->find('all', array(
                'conditions' => array('cat_id' => $cat_id, 'status' => 1),
                'order' => array('name' => 'asc')
            ));
            if (!empty($subcategories)) {
                $options[$cat_name] = [];
                foreach ($subcategories as $subcategory) {
                    $subcat_id = $subcategory['Inventory_subcategory']['id'];
                    $subcat_name = $subcategory['Inventory_subcategory']['name'];
                    if ($subcat_id == $selectedcatId  && $selectedType == 'sub') {
                        $selectedcategory = $subcategory['Inventory_subcategory'];
                        $selectedcategory['_type'] = 'sub';
                    }
                    $options[$cat_name]["sub_$subcat_id"] = $subcat_name;
                }
            } else {
                if ($cat_id == $selectedcatId  && $selectedType == 'cat') {
                    $selectedcategory = $category['Inventory_category'];
                    $selectedcategory['_type'] = 'cat';
                }
                $options["cat_$cat_id"] = $cat_name;
            }
        }
        if (!empty($selectedcategory)) {
            $key_prefix = ($selectedcategory['_type'] == 'sub') ? 'sub_' : 'cat_';
            $options = array($key_prefix . $selectedcategory['id'] => $selectedcategory['name']) + $options;
        }

        $this->set(compact('categories', 'subcategories', 'taxes', 'units', 'options', 'selectedcatId','conunits'));

        if ($this->data['User']['id']!='') {

            $id=$this->data['User']['id'];
            $name = $this->data['User']['name'];
            if($name==''){
                $this->Flash->error(__('The Name could not be Blank'));
                return $this->redirect(array('action' => 'edit_semifinished?id='.$id));
            }
            $unit = $this->data['User']['unit'];
            $price = $this->data['User']['price'];
            $remarks = $this->data['User']['remarks'];
            $moq= $this->data['User']['moq'];
            $stock= $this->data['User']['stock'];

            $conunit = $this->data['User']['conunit'];
            $factor = $this->data['User']['factor'];
            $conprice = $this->data['User']['conprice'];

            $category_id = null;
            $category_name = '';
            $subcategory_id = '';
            $subcategory_name = '';

            $categoryInput = $this->data['User']['category'] ?? '';

            if (strpos($categoryInput, 'cat_') === 0) {
                $category_id = (int) str_replace('cat_', '', $categoryInput);
                $catData = $this->Inventory_category->findById($category_id);
                if ($catData) {
                    $category_name = $catData['Inventory_category']['name'];
                }
            }elseif (strpos($categoryInput, 'sub_') === 0) {
                $subcategory_id = (int) str_replace('sub_', '', $categoryInput);
                $subcatData = $this->Inventory_subcategory->findById($subcategory_id);
                if ($subcatData) {
                    $subcategory_name = $subcatData['Inventory_subcategory']['name'];
                    $category_id = $subcatData['Inventory_subcategory']['cat_id'];
                    $category_name = $this->Inventory_category->field('name', ['id' => $category_id]);
                }
            }else {

                $newcat = trim($categoryInput);
                $this->Inventory_category->create();
                if ($this->Inventory_category->save(['name' => $newcat,'sync' => 0,'local_sync' => 0])) {
                    $category_id = $this->Inventory_category->id;
                    $category_name = $newcat;
                } else {
                    $this->Flash->error(__('New category could not be saved. Please try again.'));
                    return;
                }
            }

            $unitname=$this->Catalog_unit->find('first',array('conditions'=>array('id'=>$unit)));
            $unit_name=$unitname['Catalog_unit']['name'];

            if($conunit){
                $conunitnames=$this->Catalog_unit->find('first',array('conditions'=>array('id'=>$conunit)));
                $conunitname=$conunitnames['Catalog_unit']['name'];
            }else{
                $conunit=0;
                $factor=0;
                $conprice=0;
                $conunitname='';
            }

            $name = addslashes($name);
            $remarks = addslashes($remarks);
            $category_name = addslashes($category_name);
            $subcategory_name = addslashes($subcategory_name);

            $this->Inventory_semifinished->query("UPDATE inventory_semifinished SET consumption_unit_id='$conunit',consumption_unit_name='$conunitname',consumption_factor='$factor',consumption_unit_price='$conprice' ,moq='$moq',stock='$stock', cat_id='$category_id',cat_name='$category_name',subcat_id='$subcategory_id',subcat_name='$subcategory_name',name ='$name',unit='$unit',unit_name='$unit_name',price='$price',remarks='$remarks',sync = 0,local_sync=0 WHERE id =". $id);

            $this->Inventory_semifinished_tax->query("UPDATE inventory_semifinished_tax set status=0,sync=0,local_sync=0  WHERE product_id =". $id);

            $taxes=$this->Inventory_tax->find('all',array('conditions'=>array('status'=>1)));
            foreach ($taxes as $taxess) {
                $taxID = $taxess['Inventory_tax']['id'];
                if (!empty($this->request->data['User']['tax_' . $taxID])) {
                    $selectedTaxes[] = $this->request->data['User']['tax_' . $taxID];
                    $taxdetail=$this->Inventory_tax->find('first',array('conditions'=>array('id'=>$taxID)));
                    if($taxdetail){
                        $data['Inventory_semifinished_tax']=array(
                            'product_id'=>$id,
                            'product_name'=>$name,
                            'tax_id'=>$taxID,
                            'tax_name'=>$taxdetail['Inventory_tax']['name'],
                            'tax_percentage'=>$taxdetail['Inventory_tax']['percentage'],
                            'sync' => 0,
                            'local_sync' => 0
                        );

                        if($this->Inventory_semifinished_tax->saveAll($data['Inventory_semifinished_tax']))
                        {

                        }else{
                            $this->Flash->error(__('Semi-finished tax could not be saved. Please, try again.'));
                        }
                    }
                }
            }

            if(isset($_POST['tax_name'])){

                $taxnames=$_POST['tax_name'];
                $taxvalues=$_POST['tax_value'];

                for ($i = 0; $i < count($taxnames); $i++) {
                    $taxname = $taxnames[$i];
                    $percentage = $taxvalues[$i];

                    $newtaxdetail=$this->Inventory_tax->find('first',array('conditions'=>array('name'=>$taxname,'percentage'=>$percentage)));
                    if(empty($newtaxdetail)){
                        $data['Inventory_tax']=array(
                            'name'=>$taxname,
                            'percentage'=>$percentage,
                            'sync' => 0,
                            'local_sync' => 0
                        );

                        if($this->Inventory_tax->saveAll($data['Inventory_tax']))
                        {
                            $taxlastInsertedID = $this->Inventory_tax->id;

                        } else {
                            $this->Flash->error(__('New Tax could not be saved. Please, try again.'));
                        }
                    }

                    $data['Inventory_semifinished_tax']=array(
                        'product_id'=>$id,
                        'product_name'=>$name,
                        'tax_id'=>$taxlastInsertedID,
                        'tax_name'=>$taxname,
                        'tax_percentage'=>$percentage,
                        'sync' => 0,
                        'local_sync' => 0
                    );

                    if($this->Inventory_semifinished_tax->saveAll($data['Inventory_semifinished_tax']))
                    {

                    }else{
                        $this->Flash->error(__('Semi-finished New tax could not be saved. Please, try again.'));
                    }
                }
            }

            $this->_refreshAllCosts();

            $this->Flash->success(__('Semi-finished has been Updated.'));
            return $this->redirect(array('action' => 'semifinished_list'));
        }

    }

    public function view_semifinished()
    {
        if(!$this->Session->check('Auth.User.id')){
            $this->redirect(array('controller'=>'dashboard','action' => 'login'));
        }
        $this->layout = "users";
        $id=$_REQUEST['id'];

        $products = $this->Inventory_semifinished->query("SELECT * FROM inventory_semifinished where id=$id and status=1");
        $this->set(compact('products'));

        $taxes=$this->Inventory_semifinished_tax->query("SELECT CONCAT(tax_name, '(', tax_percentage, '%)') as taxname ,id FROM inventory_semifinished_tax where product_id=$id  and status=1");
        $this->set(compact('taxes'));
    }

    public function add_recipe()
    {
        if(!$this->Session->check('Auth.User.id')){
            $this->redirect(array('controller'=>'dashboard','action' => 'login'));
        }

        $this->layout = "users";
        $id=$_REQUEST['id'];

        $products = $this->Inventory_semifinished->query("SELECT * FROM inventory_semifinished where id=$id");
        $this->set(compact('products'));

        $recipes = $this->Inventory_linking->query("SELECT *,COALESCE(vsf.unit_cost, 0) AS live_price FROM inventory_linking LEFT JOIN v_semifinished_recipe_cost vsf ON vsf.id = CAST(inventory_linking.component_id AS UNSIGNED)
              where parent_id=$id and parent_type='Semifinished' and component_type='Semifinished' and status=1");

        $skurecipes = $this->Inventory_linking->query("SELECT inventory_linking.*, inventory_skus.consumption_unit_name,inventory_skus.consumption_unit_price as live_price FROM inventory_linking INNER JOIN inventory_skus ON inventory_skus.id = inventory_linking.component_id WHERE inventory_linking.parent_id = $id AND inventory_linking.parent_type = 'Semifinished' AND inventory_linking.component_type = 'Sku' AND inventory_linking.status = 1 AND inventory_skus.item_type = 1");
        $this->set(compact('recipes'));
        $this->set(compact('recipes','skurecipes'));

        $this->loadModel('Catalog_unit');

        $sf_base = $this->Inventory_semifinished->query(
            "SELECT isf.id, isf.unit AS sf_unit_id, isf.unit_name,
                    isf.consumption_unit_name, isf.portion_weight_grams
             FROM inventory_semifinished isf WHERE isf.id = $id LIMIT 1"
        );
        $sf_cost = isset($sf_base[0]) ? array_merge(
            $sf_base[0]['inventory_semifinished'] ?? [],
            $sf_base[0][0] ?? []
        ) : [];

        $sf_cost_view = $this->Inventory_semifinished->query(
            "SELECT COALESCE(batch_cost,0) AS batch_cost, COALESCE(unit_cost,0) AS unit_cost
             FROM v_semifinished_recipe_cost WHERE id = $id LIMIT 1"
        );
        $sf_cost['batch_cost'] = isset($sf_cost_view[0][0]['batch_cost']) ? floatval($sf_cost_view[0][0]['batch_cost']) : 0;
        $sf_cost['unit_cost']  = isset($sf_cost_view[0][0]['unit_cost'])  ? floatval($sf_cost_view[0][0]['unit_cost'])  : 0;

        $sfUnitId = isset($sf_base[0][0]['sf_unit_id']) ? intval($sf_base[0][0]['sf_unit_id']) : 0;

        $sfUnitMappingsRaw = $this->Catalog_unit->query(
            "SELECT cu2.name AS to_unit_name, cum.conversion_factor
             FROM catalog_unit_mapping cum
             JOIN catalog_units cu2 ON cu2.id = cum.mapped_unit_id
             WHERE cum.unit_id = $sfUnitId AND cum.status = 1"
        );
        $sfUnitMappings = [];
        foreach ($sfUnitMappingsRaw as $row) {
            $nm = $row[0]['to_unit_name'] ?? ($row['Catalog_unit']['to_unit_name'] ?? '');
            $cf = floatval($row[0]['conversion_factor'] ?? ($row['Catalog_unit_mapping']['conversion_factor'] ?? 1));
            if ($nm) $sfUnitMappings[$nm] = $cf;
        }

        $realMismatchCheck = $this->Inventory_semifinished->query(
            "SELECT COUNT(*) AS cnt
             FROM inventory_linking il
             JOIN inventory_semifinished isf ON isf.id = il.component_id AND il.component_type = 'Semifinished'
             WHERE il.component_id = $id AND il.status = 1
               AND (
                 (LOWER(isf.consumption_unit_name) IN ('por','portion','nos','no','pce','pcs','ea','each')
                  AND LOWER(il.component_unit_name) IN ('gms','gm','g','gram','grams','ml','millilitre'))
                 OR
                 (LOWER(isf.consumption_unit_name) IN ('gms','gm','g','gram','grams','ml','millilitre')
                  AND LOWER(il.component_unit_name) IN ('por','portion','nos','no','pce','pcs','ea','each'))
               )"
        );
        $sfNeedsConversion = (isset($realMismatchCheck[0][0]['cnt']) && intval($realMismatchCheck[0][0]['cnt']) > 0);
        $sfPortionWtSet    = (floatval($sf_cost['portion_weight_grams'] ?? 0) > 0);

        $this->set(compact('sf_cost', 'sfUnitMappings', 'sfNeedsConversion', 'sfPortionWtSet'));

        if ($this->request->is(array('post', 'put'))) {
            $product_id = $this->data['User']['id'];
            if($product_id==''){
                $this->Flash->error(__('Error'));
                return $this->redirect(array('action' => 'add_recipe?id='.$product_id));
            }

            $product_name = $this->data['User']['name'];
            $product_unit = $this->data['User']['unit'];
            $product_unit_name = $this->data['User']['unit_name'];

            $yield = $this->data['User']['yield'];

            $this->Inventory_semifinished->updateAll(
                ['Inventory_semifinished.yield' => $yield,'sync' => 0,'local_sync' => 0],
                ['Inventory_semifinished.id' => $product_id]
            );

            $selectedSemifinished=[];
            $semifinishedquantity=[];
            $semi_ids=[];
            $semi_quantities=[];
            $semifinisheds=$this->Inventory_semifinished->find('all',array('conditions'=>array('status'=>1)));
            foreach($semifinisheds as $semifinished){
                $semifinishedid = $semifinished['Inventory_semifinished']['id'];
                if (!empty($this->request->data['User']['semi_id_' . $semifinishedid])) {
                    $selectedSemifinished[] = $this->request->data['User']['semi_id_' . $semifinishedid];
                    $semifinishedquantity[]=$this->request->data['User']['semi_quantity_' . $semifinishedid];
                }
            }
            if(isset($_POST['select_semi_name'])){
                $semi_ids=$_POST['select_semi_name'];
                $semi_quantities=$_POST['semi_quantity'];
            }
            $mergedArraysemi = array_merge($selectedSemifinished, $semi_ids);
            $uniqueArraysemi = array_unique($mergedArraysemi);
            $semi_ids = array_values($uniqueArraysemi);

            $mergedArraysemi1 = array_merge($semifinishedquantity, $semi_quantities);
            $semi_quantities = array_values($mergedArraysemi1);

            if(isset($semi_ids)){
                $this->Inventory_linking->updateAll(
                    [
                        'Inventory_linking.status' => 0,
                        'Inventory_linking.sync' => 0,
                        'Inventory_linking.local_sync' => 0
                    ],
                    [
                        'Inventory_linking.parent_id' => $product_id,
                        'Inventory_linking.parent_type' => 'Semifinished',
                        'Inventory_linking.component_type' => 'Semifinished',
                    ]
                );

                for ($i = 0; $i < count($semi_ids); $i++) {
                    $semi_id = $semi_ids[$i];
                    $semi_quantity = $semi_quantities[$i];

                    $seminame=$this->Inventory_semifinished->find('first',array('conditions'=>array('id'=>$semi_id)));
                    $semi_name=$seminame['Inventory_semifinished']['name'];
                    $semi_unit=$seminame['Inventory_semifinished']['consumption_unit_id']?$seminame['Inventory_semifinished']['consumption_unit_id']:0;
                    $semi_unit_name=$seminame['Inventory_semifinished']['consumption_unit_name']?$seminame['Inventory_semifinished']['consumption_unit_name']:'';
                    $semi_unit_price=$seminame['Inventory_semifinished']['consumption_unit_price']?$seminame['Inventory_semifinished']['consumption_unit_price']:0;

                    $component_purchase_unit=$seminame['Inventory_semifinished']['unit']?$seminame['Inventory_semifinished']['unit']:0;
                    $component_purchase_unit_name=$seminame['Inventory_semifinished']['unit_name']?$seminame['Inventory_semifinished']['unit_name']:'';

                    $mappedUnits = $this->Catalog_unit_mapping->find('first', [
                        'conditions' => ['mapped_unit_id' => $semi_unit,'unit_id' => $component_purchase_unit, 'status' => 1],
                        'fields' => ['conversion_factor'],
                        'recursive' => -1,
                    ]);

                    $conversion_factor=$mappedUnits['Catalog_unit_mapping']['conversion_factor']?$mappedUnits['Catalog_unit_mapping']['conversion_factor']:0;
                    if($conversion_factor>0){
                       $component_purchase_unit_quantity=$semi_quantity/$conversion_factor;
                    }else{
                        $component_purchase_unit_quantity=0;
                    }

                    $data['Inventory_linking']=array(
                        'parent_type'=>'Semifinished',
                        'parent_id'=>$product_id,
                        'parent_name'=>$product_name,
                        'parent_yield'=>$yield,
                        'component_type'=>'Semifinished',
                        'component_id'=>$semi_id,
                        'component_name'=>$semi_name,
                        'quantity'=>$component_purchase_unit_quantity,
                        'component_unit'=>$semi_unit,
                        'component_unit_name'=>$semi_unit_name,
                        'component_price'=>$semi_unit_price,
                        'component_purchase_unit'=>$component_purchase_unit,
                        'component_purchase_unit_name'=>$component_purchase_unit_name,
                        'conversion_quantity'=>$semi_quantity,
                        'sync' => 0,
                        'local_sync' => 0
                    );

                    if($this->Inventory_linking->saveAll($data['Inventory_linking']))
                    {

                    }else{
                        $this->Flash->error(__('Semifinished mapping could not be saved. Please, try again.'));
                    }
                }
            }

            $selectedSku=[];
            $skuquantity=[];
            $sku_ids=[];
            $sku_quantities=[];
            $skus=$this->Inventory_sku->find('all',array('conditions'=>array('status'=>1)));
            foreach($skus as $sku){
                $skuid = $sku['Inventory_sku']['id'];
                if (!empty($this->request->data['User']['sku_id_' . $skuid])) {
                    $selectedSku[] = $this->request->data['User']['sku_id_' . $skuid];
                    $skuquantity[]=$this->request->data['User']['sku_quantity_' . $skuid];
                }
            }
            if(isset($_POST['select_sku_name'])){
                $sku_ids=$_POST['select_sku_name'];
                $sku_quantities=$_POST['sku_quantity'];
            }
            $mergedArraysku = array_merge($selectedSku, $sku_ids);
            $uniqueArraysku = array_unique($mergedArraysku);
            $sku_ids = array_values($uniqueArraysku);

            $mergedArraysku1 = array_merge($skuquantity, $sku_quantities);
            $sku_quantities = array_values($mergedArraysku1);

            if(isset($sku_ids)){
                $this->Inventory_linking->updateAll(
                    [
                        'Inventory_linking.status' => 0,
                        'Inventory_linking.sync' => 0,
                        'Inventory_linking.local_sync' => 0
                    ],
                    [
                        'Inventory_linking.parent_id' => $product_id,
                        'Inventory_linking.parent_type' => 'Semifinished',
                        'Inventory_linking.component_type' => 'Sku'
                    ]
                );

                for ($i = 0; $i < count($sku_ids); $i++) {
                    $sku_id = $sku_ids[$i];
                    $sku_quantity = $sku_quantities[$i];

                    $skuname=$this->Inventory_sku->find('first',array('conditions'=>array('id'=>$sku_id)));
                    $sku_name=$skuname['Inventory_sku']['name'];
                    $sku_unit=$skuname['Inventory_sku']['consumption_unit_id']?$skuname['Inventory_sku']['consumption_unit_id']:0;
                    $sku_unit_name=$skuname['Inventory_sku']['consumption_unit_name']?$skuname['Inventory_sku']['consumption_unit_name']:'';
                    $semi_unit_price=$skuname['Inventory_sku']['consumption_unit_price']?$skuname['Inventory_sku']['consumption_unit_price']:0;

                    $component_purchase_unit=$skuname['Inventory_sku']['unit']?$skuname['Inventory_sku']['unit']:0;
                    $component_purchase_unit_name=$skuname['Inventory_sku']['unit_name']?$skuname['Inventory_sku']['unit_name']:'';

                    $mappedUnits = $this->Catalog_unit_mapping->find('first', [
                        'conditions' => ['mapped_unit_id' => $sku_unit,'unit_id' => $component_purchase_unit, 'status' => 1],
                        'fields' => ['conversion_factor'],
                        'recursive' => -1,
                    ]);
                    $conversion_factor=$mappedUnits['Catalog_unit_mapping']['conversion_factor']?$mappedUnits['Catalog_unit_mapping']['conversion_factor']:0;

                    if($conversion_factor>0){
                       $component_purchase_unit_quantity=$sku_quantity/$conversion_factor;
                    }else{
                        $component_purchase_unit_quantity=0;
                    }

                    $data['Inventory_linking']=array(
                        'parent_type'=>'Semifinished',
                        'parent_id'=>$product_id,
                        'parent_name'=>$product_name,
                        'parent_yield'=>$yield,
                        'component_type'=>'Sku',
                        'component_id'=>$sku_id,
                        'component_name'=>$sku_name,
                        'quantity'=>$component_purchase_unit_quantity,
                        'component_unit'=>$sku_unit,
                        'component_unit_name'=>$sku_unit_name,
                        'component_price'=>$semi_unit_price,
                        'component_purchase_unit'=>$component_purchase_unit,
                        'component_purchase_unit_name'=>$component_purchase_unit_name,
                        'conversion_quantity'=>$sku_quantity,
                        'sync' => 0,
                        'local_sync' => 0
                    );

                    if($this->Inventory_linking->saveAll($data['Inventory_linking']))
                    {

                    }else{
                        $this->Flash->error(__('Sku mapping could not be saved. Please, try again.'));
                    }
                }
            }

            $this->_refreshAllCosts();

            $this->Flash->success(__('Recipe has been saved.'));
            return $this->redirect(array('action' => 'semifinished_list'));
        }
    }

    public function fetch_skus_options() {
        $this->autoRender = false;
        $skuss=array();
        $skus=$this->Inventory_sku->find('all',array('conditions'=>array('status'=>1)));
        foreach($skus as $sku){
            $skuss[]=$sku['Inventory_sku'];
        }
        $responseArray = array('response_code' => 1, 'data' => $skuss);
        echo json_encode($responseArray);

    }

    public function product_recipe()
    {
        if(!$this->Session->check('Auth.User.id')){
            $this->redirect(array('controller'=>'dashboard','action' => 'login'));
        }

        $this->layout = "users";
        $id=$_REQUEST['id'];

        $products = $this->Catalog_product->query("SELECT * FROM catalog_product where id=$id");
        $this->set(compact('products'));

        $recipes = $this->Inventory_linking->query(
            "SELECT inventory_linking.*,
                COALESCE(vsf.unit_cost, 0) AS live_price,
                ROUND(inventory_linking.conversion_quantity * COALESCE(vsf.unit_cost, 0), 2) AS line_cost
             FROM inventory_linking
             LEFT JOIN v_semifinished_recipe_cost vsf ON vsf.id = CAST(inventory_linking.component_id AS UNSIGNED)
             WHERE inventory_linking.parent_id=$id AND inventory_linking.parent_type='Product'
               AND inventory_linking.component_type='Semifinished' AND inventory_linking.status=1"
        );

        $skurecipes = $this->Inventory_linking->query(
            "SELECT inventory_linking.*,
                COALESCE(sk.consumption_unit_price, 0) AS live_price,
                ROUND(inventory_linking.conversion_quantity
                      * COALESCE(sk.consumption_unit_price, 0), 2) AS line_cost
             FROM inventory_linking
             LEFT JOIN inventory_skus sk
                    ON CAST(sk.id AS CHAR) = inventory_linking.component_id
             WHERE inventory_linking.parent_id=$id
               AND inventory_linking.parent_type='Product'
               AND inventory_linking.component_type='Sku'
               AND inventory_linking.status=1"
        );

        $recipe_total_cost = $this->Inventory_linking->query(
            "SELECT
                COALESCE(
                    (SELECT SUM(il.conversion_quantity
                                * COALESCE(sk.consumption_unit_price, 0))
                     FROM inventory_linking il
                     LEFT JOIN inventory_skus sk
                            ON CAST(sk.id AS CHAR) = il.component_id
                     WHERE il.parent_id=$id
                       AND il.parent_type='Product'
                       AND il.component_type='Sku'
                       AND il.status=1), 0)
                +
                COALESCE(
                    (SELECT SUM(il.conversion_quantity
                                * COALESCE(vsf.unit_cost, 0))
                     FROM inventory_linking il
                     LEFT JOIN v_semifinished_recipe_cost vsf
                            ON vsf.id = CAST(il.component_id AS UNSIGNED)
                     WHERE il.parent_id=$id
                       AND il.parent_type='Product'
                       AND il.component_type='Semifinished'
                       AND il.status=1), 0)
             AS total_cost"
        );
        $this->set(compact('recipes','skurecipes','recipe_total_cost'));

        if ($this->request->is(array('post', 'put'))) {
            $product_id = $this->data['User']['id'];
            if($product_id==''){
                $this->Flash->error(__('Product Error'));
                return $this->redirect(array('action' => 'product_recipe?id='.$semi_id));
            }

            $product_name = $this->data['User']['name'];
            $product_unit = $this->data['User']['unit'];
            $product_unit_name = $this->data['User']['unit_name'];
            $yield = 1;

            $this->Catalog_product->updateAll(
                ['Catalog_product.yield' => $yield,'sync' => 0,'local_sync' => 0],
                ['Catalog_product.id' => $product_id]
            );

            $selectedSemifinished=[];
            $semifinishedquantity=[];
            $semi_ids=[];
            $semi_quantities=[];
            $semifinisheds=$this->Inventory_semifinished->find('all',array('conditions'=>array('status'=>1)));
            foreach($semifinisheds as $semifinished){
                $semifinishedid = $semifinished['Inventory_semifinished']['id'];
                if (!empty($this->request->data['User']['semi_id_' . $semifinishedid])) {
                    $selectedSemifinished[] = $this->request->data['User']['semi_id_' . $semifinishedid];
                    $semifinishedquantity[]=$this->request->data['User']['semi_quantity_' . $semifinishedid];
                }
            }
            if(isset($_POST['select_semi_name'])){
                $semi_ids=$_POST['select_semi_name'];
                $semi_quantities=$_POST['semi_quantity'];
            }
            $mergedArraysemi = array_merge($selectedSemifinished, $semi_ids);
            $uniqueArraysemi = array_unique($mergedArraysemi);
            $semi_ids = array_values($uniqueArraysemi);

            $mergedArraysemi1 = array_merge($semifinishedquantity, $semi_quantities);
            $semi_quantities = array_values($mergedArraysemi1);
            if(isset($semi_ids)){
                $this->Inventory_linking->updateAll(
                    [
                        'Inventory_linking.sync' => 0,
                        'Inventory_linking.local_sync' => 0,
                        'Inventory_linking.status' => 0,
                    ],
                    [
                        'Inventory_linking.parent_id' => $product_id,
                        'Inventory_linking.parent_type' => 'Product',
                        'Inventory_linking.component_type' => 'Semifinished',
                    ]
                );

                for ($i = 0; $i < count($semi_ids); $i++) {
                    $semi_id = $semi_ids[$i];
                    $semi_quantity = $semi_quantities[$i];

                    $seminame=$this->Inventory_semifinished->find('first',array('conditions'=>array('id'=>$semi_id)));
                    $semi_name=$seminame['Inventory_semifinished']['name'];
                    $semi_unit=$seminame['Inventory_semifinished']['consumption_unit_id']?$seminame['Inventory_semifinished']['consumption_unit_id']:0;
                    $semi_unit_name=$seminame['Inventory_semifinished']['consumption_unit_name']?$seminame['Inventory_semifinished']['consumption_unit_name']:'';
                    $semi_unit_price=$seminame['Inventory_semifinished']['consumption_unit_price']?$seminame['Inventory_semifinished']['consumption_unit_price']:0;

                    $component_purchase_unit=$seminame['Inventory_semifinished']['unit']?$seminame['Inventory_semifinished']['unit']:0;
                    $component_purchase_unit_name=$seminame['Inventory_semifinished']['unit_name']?$seminame['Inventory_semifinished']['unit_name']:'';

                    $mappedUnits = $this->Catalog_unit_mapping->find('first', [
                        'conditions' => ['mapped_unit_id' => $semi_unit,'unit_id' => $component_purchase_unit, 'status' => 1],
                        'fields' => ['conversion_factor'],
                        'recursive' => -1,
                    ]);

                    $conversion_factor=$mappedUnits['Catalog_unit_mapping']['conversion_factor']?$mappedUnits['Catalog_unit_mapping']['conversion_factor']:0;
                    if($conversion_factor>0){
                       $component_purchase_unit_quantity=$semi_quantity/$conversion_factor;
                    }else{
                        $component_purchase_unit_quantity=0;
                    }

                    $data['Inventory_linking']=array(
                        'parent_type'=>'Product',
                        'parent_id'=>$product_id,
                        'parent_name'=>$product_name,
                        'parent_yield'=>$yield,
                        'component_type'=>'Semifinished',
                        'component_id'=>$semi_id,
                        'component_name'=>$semi_name,
                        'quantity'=>$component_purchase_unit_quantity,
                        'component_unit'=>$semi_unit,
                        'component_unit_name'=>$semi_unit_name,
                        'component_price'=>$semi_unit_price,
                        'component_purchase_unit'=>$component_purchase_unit,
                        'component_purchase_unit_name'=>$component_purchase_unit_name,
                        'conversion_quantity'=>$semi_quantity,
                        'status'=>1,
                        'sync' => 0,
                        'local_sync' => 0

                    );

                    $this->Inventory_linking->create();
                    if($this->Inventory_linking->save(['Inventory_linking' => $data['Inventory_linking']]))
                    {

                    }else{
                        $this->Flash->error(__('Semifinished mapping could not be saved. Please, try again.'));
                    }
                }
            }

            $selectedSku=[];
            $skuquantity=[];
            $sku_ids=[];
            $sku_quantities=[];
            $skus=$this->Inventory_sku->find('all',array('conditions'=>array('status'=>1)));
            foreach($skus as $sku){
                $skuid = $sku['Inventory_sku']['id'];
                if (!empty($this->request->data['User']['sku_id_' . $skuid])) {
                    $selectedSku[] = $this->request->data['User']['sku_id_' . $skuid];
                    $skuquantity[]=$this->request->data['User']['sku_quantity_' . $skuid];
                }
            }
            if(isset($_POST['select_sku_name'])){
                $sku_ids=$_POST['select_sku_name'];
                $sku_quantities=$_POST['sku_quantity'];
            }
            $mergedArraysku = array_merge($selectedSku, $sku_ids);
            $uniqueArraysku = array_unique($mergedArraysku);
            $sku_ids = array_values($uniqueArraysku);

            $mergedArraysku1 = array_merge($skuquantity, $sku_quantities);
            $sku_quantities = array_values($mergedArraysku1);

            if(isset($sku_ids)){

                $this->Inventory_linking->updateAll(
                    [
                        'Inventory_linking.sync' => 0,
                        'Inventory_linking.local_sync' => 0,
                        'Inventory_linking.status' => 0
                    ],
                    [
                        'Inventory_linking.parent_id' => $product_id,
                        'Inventory_linking.parent_type' => 'Product',
                        'Inventory_linking.component_type' => 'Sku',
                    ]
                );

                for ($i = 0; $i < count($sku_ids); $i++) {
                    $sku_id = $sku_ids[$i];
                    $sku_quantity = $sku_quantities[$i];

                    $skuname=$this->Inventory_sku->find('first',array('conditions'=>array('id'=>$sku_id)));
                    $sku_name=$skuname['Inventory_sku']['name'];
                    $sku_unit=$skuname['Inventory_sku']['consumption_unit_id']?$skuname['Inventory_sku']['consumption_unit_id']:0;
                    $sku_unit_name=$skuname['Inventory_sku']['consumption_unit_name']?$skuname['Inventory_sku']['consumption_unit_name']:'';
                    $semi_unit_price=$skuname['Inventory_sku']['consumption_unit_price']?$skuname['Inventory_sku']['consumption_unit_price']:0;

                    $component_purchase_unit=$skuname['Inventory_sku']['unit']?$skuname['Inventory_sku']['unit']:0;
                    $component_purchase_unit_name=$skuname['Inventory_sku']['unit_name']?$skuname['Inventory_sku']['unit_name']:'';

                    $mappedUnits = $this->Catalog_unit_mapping->find('first', [
                        'conditions' => ['mapped_unit_id' => $sku_unit,'unit_id' => $component_purchase_unit, 'status' => 1],
                        'fields' => ['conversion_factor'],
                        'recursive' => -1,
                    ]);
                    $conversion_factor=$mappedUnits['Catalog_unit_mapping']['conversion_factor']?$mappedUnits['Catalog_unit_mapping']['conversion_factor']:0;
                    if($conversion_factor>0){
                       $component_purchase_unit_quantity=$sku_quantity/$conversion_factor;
                    }else{
                        $component_purchase_unit_quantity=0;
                    }
                    $data['Inventory_linking']=array(
                        'parent_type'=>'Product',
                        'parent_id'=>$product_id,
                        'parent_name'=>$product_name,
                        'parent_yield'=>$yield,
                        'component_type'=>'Sku',
                        'component_id'=>$sku_id,
                        'component_name'=>$sku_name,
                        'quantity'=>$component_purchase_unit_quantity,
                        'component_unit'=>$sku_unit,
                        'component_unit_name'=>$sku_unit_name,
                        'component_price'=>$semi_unit_price,
                        'component_purchase_unit'=>$component_purchase_unit,
                        'component_purchase_unit_name'=>$component_purchase_unit_name,
                        'conversion_quantity'=>$sku_quantity,
                        'status'=>1,
                        'sync' => 0,
                        'local_sync' => 0
                    );

                    $this->Inventory_linking->create();
                    if($this->Inventory_linking->save(['Inventory_linking' => $data['Inventory_linking']]))
                    {

                    }else{
                        $this->Flash->error(__('Sku mapping could not be saved. Please, try again.'));
                    }
                }
            }

            $pCostResult = $this->Inventory_linking->query(
                "SELECT COALESCE(vpc.recipe_cost, 0) as rc FROM v_product_recipe_cost vpc WHERE vpc.id = {$product_id} LIMIT 1"
            );
            $newFoodCost = isset($pCostResult[0][0]['rc']) ? $pCostResult[0][0]['rc'] : 0;
            $this->Catalog_product->updateAll(
                ['Catalog_product.food_cost' => $newFoodCost, 'sync' => 0, 'local_sync' => 0],
                ['Catalog_product.id' => $product_id]
            );

            $this->_refreshAllCosts();

            $this->Flash->success(__('Recipe has been saved.'));
            return $this->redirect(array('action' => 'product_list'));
        }

    }

    public function export_recipe_costing()
    {
        if (!$this->Session->check('Auth.User.id')) {
            return $this->redirect(['controller' => 'dashboard', 'action' => 'login']);
        }

        $exportAll  = !empty($_POST['export_all']) && $_POST['export_all'] == '1';
        $idsParam   = isset($_POST['product_ids']) ? trim($_POST['product_ids']) : '';
        $productIds = [];

        if ($exportAll || $idsParam === '') {
            $rows = $this->Catalog_product->query(
                "SELECT id FROM catalog_product WHERE status = 1 ORDER BY id ASC"
            );
            foreach ($rows as $r) {
                $productIds[] = $r['catalog_product']['id'];
            }
        } else {
            foreach (explode(',', $idsParam) as $v) {
                $v = intval(trim($v));
                if ($v > 0) $productIds[] = $v;
            }
        }

        if (empty($productIds)) {
            $this->Flash->error('No products selected.');
            return $this->redirect(['action' => 'product_list']);
        }

        $products = [];
        foreach ($productIds as $pid) {
            $pRow = $this->Catalog_product->query(
                "SELECT id, name, price FROM catalog_product WHERE id = $pid LIMIT 1"
            );
            if (empty($pRow)) continue;
            $pName  = $pRow[0]['catalog_product']['name'];
            $pPrice = floatval($pRow[0]['catalog_product']['price']);

            $skuRows = $this->Inventory_linking->query(
                "SELECT il.component_name,
                        il.component_unit_name AS uom,
                        il.conversion_quantity AS req_qty,
                        COALESCE(sk.consumption_unit_price, 0) AS unit_cost,
                        COALESCE(NULLIF(CAST(sk.consumption_factor AS DECIMAL(15,4)), 0), 1) AS per_unit,
                        ROUND(il.conversion_quantity * COALESCE(sk.consumption_unit_price, 0), 4) AS line_cost,
                        'Sku' AS comp_type
                 FROM inventory_linking il
                 LEFT JOIN inventory_skus sk ON CAST(sk.id AS CHAR) = il.component_id
                 WHERE il.parent_id = $pid AND il.parent_type = 'Product'
                   AND il.component_type = 'Sku' AND il.status = 1"
            );

            $semiRows = $this->Inventory_linking->query(
                "SELECT il.component_name,
                        il.component_unit_name AS uom,
                        il.conversion_quantity AS req_qty,
                        COALESCE(vsf.unit_cost, 0) AS unit_cost,
                        1 AS per_unit,
                        ROUND(il.conversion_quantity * COALESCE(vsf.unit_cost, 0), 4) AS line_cost,
                        'Semifinished' AS comp_type
                 FROM inventory_linking il
                 LEFT JOIN v_semifinished_recipe_cost vsf ON vsf.id = CAST(il.component_id AS UNSIGNED)
                 LEFT JOIN inventory_semifinished sf ON sf.id = CAST(il.component_id AS UNSIGNED)
                 WHERE il.parent_id = $pid AND il.parent_type = 'Product'
                   AND il.component_type = 'Semifinished' AND il.status = 1"
            );

            $ingredients = [];
            foreach ($semiRows as $r) {

                $merged = array_merge($r['il'] ?? $r['inventory_linking'] ?? [], $r[0] ?? []);
                $ingredients[] = array_merge($merged, ['comp_type' => 'Semifinished']);
            }
            foreach ($skuRows as $r) {
                $merged = array_merge($r['il'] ?? $r['inventory_linking'] ?? [], $r[0] ?? []);
                $ingredients[] = array_merge($merged, ['comp_type' => 'Sku']);
            }

            $totalCost = array_sum(array_column($ingredients, 'line_cost'));

            $products[] = [
                'id'          => $pid,
                'name'        => $pName,
                'price'       => $pPrice,
                'ingredients' => $ingredients,
                'total_cost'  => $totalCost,
            ];
        }

        App::import('Vendor', 'PHPExcel/PHPExcel');
        $xls   = new PHPExcel();
        $sheet = $xls->getActiveSheet();
        $sheet->setTitle('Recipe Costing');

        $applyStyle = function($range, $style) use ($sheet) {
            $sheet->getStyle($range)->applyFromArray($style);
        };

        $sheet->getColumnDimension('A')->setWidth(6);
        $sheet->getColumnDimension('B')->setWidth(30);
        $sheet->getColumnDimension('C')->setWidth(32);
        $sheet->getColumnDimension('D')->setWidth(8);
        $sheet->getColumnDimension('E')->setWidth(10);
        $sheet->getColumnDimension('F')->setWidth(16);
        $sheet->getColumnDimension('G')->setWidth(10);
        $sheet->getColumnDimension('H')->setWidth(14);
        $sheet->getColumnDimension('I')->setWidth(14);
        $sheet->getColumnDimension('J')->setWidth(12);

        $sheet->setCellValue('A1', 'SR');
        $sheet->setCellValue('B1', 'NAME OF DISH');
        $sheet->setCellValue('C1', 'INGREDIENT');
        $sheet->setCellValue('D1', 'UOM');
        $sheet->setCellValue('E1', 'REQ QTY');
        $sheet->setCellValue('F1', 'UNIT COST');
        $sheet->setCellValue('G1', 'PER UNIT');
        $sheet->setCellValue('H1', 'COST PRICE');
        $sheet->setCellValue('I1', 'SELLING PRICE');
        $sheet->setCellValue('J1', 'MARGIN');

        $headerStyle = [
            'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 10],
            'fill'      => ['type' => PHPExcel_Style_Fill::FILL_SOLID, 'startcolor' => ['rgb' => '2F4F4F']],
            'alignment' => ['horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
                            'vertical'   => PHPExcel_Style_Alignment::VERTICAL_CENTER,
                            'wrap'       => true],
            'borders'   => ['allborders' => ['style' => PHPExcel_Style_Border::BORDER_THIN,
                                             'color' => ['rgb' => 'CCCCCC']]],
        ];
        $applyStyle('A1:J1', $headerStyle);
        $sheet->getRowDimension(1)->setRowHeight(20);

        $dishHeaderFill = ['type' => PHPExcel_Style_Fill::FILL_SOLID, 'startcolor' => ['rgb' => 'D9C09A']];
        $totalRowFill   = ['type' => PHPExcel_Style_Fill::FILL_SOLID, 'startcolor' => ['rgb' => 'F2E8D9']];
        $semiFont       = ['color' => ['rgb' => 'CC0000'], 'bold' => false];
        $thinBorder     = ['style' => PHPExcel_Style_Border::BORDER_THIN, 'color' => ['rgb' => 'CCCCCC']];
        $currencyFmt    = '"₹"#,##0.00';
        $numFmt         = '#,##0.####';

        $row = 2;
        $sr  = 1;

        foreach ($products as $prod) {

            $sheet->setCellValue("A$row", $sr++);
            $sheet->setCellValue("B$row", strtoupper($prod['name']));
            $sheet->setCellValue("H$row", $prod['total_cost']);
            $sheet->setCellValue("I$row", $prod['price']);
            $margin = round($prod['price'] - $prod['total_cost'], 2);
            $sheet->setCellValue("J$row", $margin);

            $sheet->getStyle("H$row")->getNumberFormat()->setFormatCode($currencyFmt);
            $sheet->getStyle("I$row")->getNumberFormat()->setFormatCode($currencyFmt);
            $sheet->getStyle("J$row")->getNumberFormat()->setFormatCode($currencyFmt);

            $applyStyle("A$row:J$row", [
                'font'      => ['bold' => true, 'size' => 10],
                'fill'      => $dishHeaderFill,
                'alignment' => ['vertical' => PHPExcel_Style_Alignment::VERTICAL_CENTER],
                'borders'   => ['allborders' => $thinBorder],
            ]);
            $sheet->getRowDimension($row)->setRowHeight(18);
            $row++;

            foreach ($prod['ingredients'] as $ing) {
                $isSemi = ($ing['comp_type'] === 'Semifinished');
                $sheet->setCellValue("C$row", $ing['component_name']);
                $sheet->setCellValue("D$row", strtoupper($ing['uom']));
                $sheet->setCellValue("E$row", floatval($ing['req_qty']));
                $sheet->setCellValue("F$row", floatval($ing['unit_cost'] ?? $ing['purchase_price'] ?? 0));
                $sheet->setCellValue("G$row", floatval($ing['per_unit']));
                $sheet->setCellValue("H$row", floatval($ing['line_cost']));

                $sheet->getStyle("F$row")->getNumberFormat()->setFormatCode($currencyFmt);
                $sheet->getStyle("H$row")->getNumberFormat()->setFormatCode($currencyFmt);
                $sheet->getStyle("E$row")->getNumberFormat()->setFormatCode($numFmt);
                $sheet->getStyle("G$row")->getNumberFormat()->setFormatCode($numFmt);

                $ingStyle = [
                    'borders'   => ['allborders' => $thinBorder],
                    'alignment' => ['vertical' => PHPExcel_Style_Alignment::VERTICAL_CENTER],
                ];
                if ($isSemi) {
                    $ingStyle['font'] = $semiFont;
                }
                $applyStyle("A$row:J$row", $ingStyle);
                $sheet->getRowDimension($row)->setRowHeight(16);
                $row++;
            }

            $sheet->setCellValue("G$row", 'TOTAL');
            $sheet->setCellValue("H$row", $prod['total_cost']);
            $sheet->getStyle("H$row")->getNumberFormat()->setFormatCode($currencyFmt);

            $applyStyle("A$row:J$row", [
                'font'      => ['bold' => true, 'size' => 10],
                'fill'      => $totalRowFill,
                'alignment' => ['horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_RIGHT,
                                'vertical'   => PHPExcel_Style_Alignment::VERTICAL_CENTER],
                'borders'   => ['allborders' => $thinBorder],
            ]);
            $sheet->getStyle("G$row")->getFont()->setBold(true);
            $sheet->getRowDimension($row)->setRowHeight(16);
            $row++;

            $row++;
        }

        $sheet->freezePane('A2');

        $filename = 'Recipe_Costing_' . date('Ymd_His') . '.xls';
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        $writer = PHPExcel_IOFactory::createWriter($xls, 'Excel5');
        $writer->save('php://output');
        exit;
    }

    public function product_list_export()
    {
        if (!$this->Session->check('Auth.User.id')) {
            return $this->redirect(['controller' => 'dashboard', 'action' => 'login']);
        }

        $this->autoRender = false;

        $rows = $this->Catalog_product->query(
            "SELECT catalog_product.*,
                    COALESCE(vpc.recipe_cost, 0) as recipe_cost
             FROM catalog_product
             LEFT JOIN v_product_recipe_cost vpc
                ON vpc.id = catalog_product.id
             WHERE catalog_product.status=1
             ORDER BY catalog_product.id DESC"
        );

        App::import('Vendor', 'PHPExcel', ['file' => 'PHPExcel' . DS . 'PHPExcel.php']);
        $xls = new PHPExcel();
        $sheet = $xls->getActiveSheet();
        $sheet->setTitle('Products');

        $headers = [
            'ID','Name','Category','SubCategory','Food Type',
            'Recipe Cost','Price','Margin','Cost %',
            'Discountable','Status','Created'
        ];

        foreach ($headers as $ci => $h) {
            $col = PHPExcel_Cell::stringFromColumnIndex($ci);
            $sheet->setCellValue($col . '1', $h);

            $sheet->getStyle($col . '1')->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => [
                    'type' => PHPExcel_Style_Fill::FILL_SOLID,
                    'startcolor' => ['rgb' => '4472C4']
                ]
            ]);

            $sheet->getColumnDimension($col)->setWidth(18);
        }

        $rowNum = 2;

        foreach ($rows as $r) {

            $product = $r['catalog_product'];
            $product['recipe_cost'] = $r[0]['recipe_cost'] ?? 0;

            $price = floatval($product['price']);
            $recipe_cost = floatval($product['recipe_cost']);

            $isValid = ($price > 0 && $recipe_cost > 0);

            $margin = $isValid ? number_format($price - $recipe_cost, 2) : '-';

            $cost_pct = $isValid ? round(($recipe_cost / $price) * 100, 1) : '-';

            if ($product['food_type'] == '3') {
                $foodType = 'Alcoholic';
            } elseif ($product['food_type'] == '2') {
                $foodType = 'Non-Alcoholic';
            } elseif ($product['food_type'] == '1') {
                $foodType = 'Non-Veg';
            } else {
                $foodType = 'Veg';
            }

            $rowData = [
                $product['id'],
                $product['name'],
                $product['cat_name'],
                $product['subcat_name'] ?: 'N/A',
                $foodType,
                number_format($recipe_cost, 2),
                $price,
                $margin,
                ($cost_pct !== '-' ? $cost_pct . '%' : '-'),
                (isset($product['is_discountable']) && $product['is_discountable'] == 1 ? 'Yes' : 'No'),
                $product['status'] ? 'Active' : 'Inactive',
                $product['created']
            ];

            foreach ($rowData as $ci => $val) {
                $sheet->setCellValue(
                    PHPExcel_Cell::stringFromColumnIndex($ci) . $rowNum,
                    $val
                );
            }

            $rowNum++;
        }

        $filename = 'products_' . date('Ymd_His') . '.xls';

        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        PHPExcel_IOFactory::createWriter($xls, 'Excel5')->save('php://output');
        exit;
    }

    public function recipe_integrity()
    {
        if (!$this->Session->check('Auth.User.id')) {
            return $this->redirect(['controller' => 'dashboard', 'action' => 'login']);
        }
        $this->layout = "users";

        $majorityUom = [];
        $uomRows = $this->Inventory_linking->query(
            "SELECT component_type, component_id, component_name, component_unit_name,
                    COUNT(*) as cnt
             FROM inventory_linking
             WHERE status=1 AND component_type IN ('Sku','Semifinished')
               AND component_unit_name IS NOT NULL AND component_unit_name != ''
             GROUP BY component_type, component_id, component_name, component_unit_name
             ORDER BY component_type, component_id, cnt DESC"
        );
        foreach ($uomRows as $ur) {
            $r = array_merge($ur['inventory_linking'] ?? $ur[0] ?? [], $ur[0] ?? []);
            $key = ($r['component_type'] ?? '') . '_' . ($r['component_id'] ?? '') . '_' . ($r['component_name'] ?? '');
            if (!isset($majorityUom[$key])) {
                $majorityUom[$key] = $r['component_unit_name'] ?? '';
            }
        }

        $rows = $this->Inventory_linking->query(
            "SELECT
                il.parent_type,
                il.parent_id,
                il.parent_name,
                il.component_type,
                il.component_id,
                il.component_name,
                il.conversion_quantity,
                il.component_unit_name,
                CASE il.component_type
                    WHEN 'Sku' THEN
                        CASE
                            WHEN il.component_id IS NULL OR il.component_id='0' OR il.component_id='' THEN 'MISSING_ID'
                            WHEN sk.id IS NULL THEN 'NOT_IN_TABLE'
                            WHEN COALESCE(sk.consumption_unit_price,0)=0 AND COALESCE(sk.price,0)=0 THEN 'ZERO_PRICE'
                            WHEN sk.name != il.component_name THEN 'NAME_MISMATCH'
                            ELSE 'OK'
                        END
                    WHEN 'Semifinished' THEN
                        CASE
                            WHEN il.component_id IS NULL OR il.component_id='0' OR il.component_id='' THEN 'MISSING_ID'
                            WHEN sf.id IS NULL THEN 'NOT_IN_TABLE'
                            WHEN COALESCE(sf.consumption_unit_price,0)=0 THEN 'ZERO_PRICE'
                            WHEN sf.name != il.component_name THEN 'NAME_MISMATCH'
                            ELSE 'OK'
                        END
                    ELSE 'SKIP'
                END AS status,
                CASE il.component_type WHEN 'Sku' THEN sk.name WHEN 'Semifinished' THEN sf.name END AS actual_name,
                CASE il.component_type WHEN 'Sku' THEN COALESCE(sk.consumption_unit_price,0) WHEN 'Semifinished' THEN COALESCE(sf.consumption_unit_price,0) END AS actual_price,
                CASE il.component_type WHEN 'Sku' THEN sk.unit_name WHEN 'Semifinished' THEN sf.unit_name END AS unit_name
             FROM inventory_linking il
             LEFT JOIN inventory_skus sk ON sk.id = CAST(il.component_id AS UNSIGNED) AND il.component_type='Sku'
             LEFT JOIN inventory_semifinished sf ON sf.id = CAST(il.component_id AS UNSIGNED) AND il.component_type='Semifinished'
             WHERE il.status = 1
               AND il.component_type IN ('Sku','Semifinished')
               AND (
                   il.component_id IS NULL OR il.component_id='0' OR il.component_id=''
                   OR (il.component_type='Sku' AND (sk.id IS NULL OR (COALESCE(sk.consumption_unit_price,0)=0 AND COALESCE(sk.price,0)=0) OR sk.name != il.component_name))
                   OR (il.component_type='Semifinished' AND (sf.id IS NULL OR COALESCE(sf.consumption_unit_price,0)=0 OR sf.name != il.component_name))
               )
             ORDER BY status, il.component_name, il.parent_name"
        );

        $issues = [];
        foreach ($rows as $r) {
            $row = array_merge($r['il'] ?? $r['inventory_linking'] ?? [], $r[0] ?? []);
            $issues[] = $row;
        }

        $summary = ['MISSING_ID' => 0, 'NOT_IN_TABLE' => 0, 'ZERO_PRICE' => 0, 'NAME_MISMATCH' => 0];
        foreach ($issues as $i) {
            $s = $i['status'] ?? 'OK';
            if (isset($summary[$s])) $summary[$s]++;
        }

        $this->set(compact('issues', 'summary', 'majorityUom'));
    }

    public function create_missing_ingredient()
    {
        if (!$this->Session->check('Auth.User.id')) {
            return $this->redirect(['controller' => 'dashboard', 'action' => 'login']);
        }
        $this->autoRender = false;

        $type        = $this->request->data['type']        ?? '';
        $name        = trim($this->request->data['name']        ?? '');
        $unit_name   = trim($this->request->data['unit_name']   ?? 'Kg');
        $cons_unit   = trim($this->request->data['cons_unit']   ?? 'Gms');
        $cons_factor = floatval($this->request->data['cons_factor'] ?? 1000);

        if (empty($name) || !in_array($type, ['Sku', 'Semifinished'])) {
            $this->Flash->error('Invalid request.');
            return $this->redirect(['action' => 'recipe_integrity']);
        }

        $unitRow = $this->Catalog_unit->find('first', [
            'conditions' => ['Catalog_unit.name' => $unit_name, 'Catalog_unit.status' => 1],
            'fields' => ['id'], 'recursive' => -1
        ]);
        $unit_id = $unitRow ? $unitRow['Catalog_unit']['id'] : 0;

        $consUnitRow = $this->Catalog_unit->find('first', [
            'conditions' => ['Catalog_unit.name' => $cons_unit, 'Catalog_unit.status' => 1],
            'fields' => ['id'], 'recursive' => -1
        ]);
        $cons_unit_id = $consUnitRow ? $consUnitRow['Catalog_unit']['id'] : 0;

        if ($type === 'Sku') {
            $this->loadModel('Inventory_sku');
            $this->Inventory_sku->create();
            $saved = $this->Inventory_sku->save(['Inventory_sku' => [
                'name' => $name, 'unit' => $unit_id, 'unit_name' => $unit_name,
                'consumption_unit_id' => $cons_unit_id, 'consumption_unit_name' => $cons_unit,
                'consumption_factor' => $cons_factor,
                'price' => 0,
                'consumption_unit_price' => 0,
                'status' => 1, 'sync' => 0, 'local_sync' => 0,
            ]]);
            $newId = $saved ? $this->Inventory_sku->getLastInsertID() : null;
        } else {
            $this->Inventory_semifinished->create();
            $saved = $this->Inventory_semifinished->save(['Inventory_semifinished' => [
                'name' => $name, 'unit' => $unit_id, 'unit_name' => $unit_name,
                'consumption_unit_name' => $cons_unit, 'consumption_factor' => $cons_factor,
                'price' => 0, 'consumption_unit_price' => 0,
                'status' => 1, 'sync' => 0, 'local_sync' => 0,
            ]]);
            $newId = $saved ? $this->Inventory_semifinished->getLastInsertID() : null;
        }

        if ($newId) {
            $escapedName = addslashes($name);
            $this->Inventory_linking->query(
                "UPDATE inventory_linking SET component_id='$newId', sync=0, local_sync=0
                 WHERE component_type='$type' AND component_name='$escapedName'
                   AND (component_id IS NULL OR component_id='0' OR component_id='')"
            );
            $this->Flash->success("$type \"$name\" created (ID $newId). Please set the price in the master.");
        } else {
            $this->Flash->error('Could not create the item. Please try again.');
        }

        return $this->redirect(['action' => 'recipe_integrity']);
    }

    public function recalculate_food_costs()
    {
        if (!$this->Session->check('Auth.User.id')) {
            return $this->redirect(['controller' => 'dashboard', 'action' => 'login']);
        }
        $this->autoRender = false;

        $this->loadModel('Inventory_sku');
        $this->loadModel('Inventory_semifinished');
        $this->loadModel('Catalog_product');

        $this->_refreshAllCosts();

        $this->Flash->success(__('Food costs recalculated for all products and semi-finished items.'));
        return $this->redirect(array('action' => 'product_list'));
    }

    private function _run_food_cost_recalc() {
        $this->loadModel('Inventory_sku');
        $this->loadModel('Inventory_semifinished');
        $this->loadModel('Catalog_product');
        $this->loadModel('Inventory_linking');

        $this->Inventory_sku->query(
            "UPDATE inventory_skus sk
             LEFT JOIN (
                 SELECT sk2.id AS sku_id,
                     COALESCE(
                         (SELECT CAST(sl.sku_price AS DECIMAL(15,6))
                          FROM stock_ledger sl
                          WHERE sl.sku_id = sk2.id AND sl.record_type = 'stockin'
                            AND CAST(sl.sku_price AS DECIMAL(15,6)) > 0
                          ORDER BY sl.id DESC LIMIT 1),
                         NULLIF(CAST(sk2.price AS DECIMAL(15,6)), 0)
                     )
                     / GREATEST(COALESCE(
                         (SELECT CAST(um.conversion_factor AS DECIMAL(15,6))
                          FROM catalog_unit_mapping um
                          WHERE um.unit_name = sk2.unit_name
                            AND um.mapped_unit_name = sk2.consumption_unit_name
                            AND um.unit_name != um.mapped_unit_name AND um.status = 1
                          ORDER BY um.id DESC LIMIT 1),
                         NULLIF(CAST(sk2.consumption_factor AS DECIMAL(15,6)), 0), 1
                     ), 1) AS new_price,
                     GREATEST(COALESCE(
                         (SELECT CAST(um.conversion_factor AS DECIMAL(15,6))
                          FROM catalog_unit_mapping um
                          WHERE um.unit_name = sk2.unit_name
                            AND um.mapped_unit_name = sk2.consumption_unit_name
                            AND um.unit_name != um.mapped_unit_name AND um.status = 1
                          ORDER BY um.id DESC LIMIT 1),
                         NULLIF(CAST(sk2.consumption_factor AS DECIMAL(15,6)), 0), 1
                     ), 1) AS map_cf
                 FROM inventory_skus sk2
                 WHERE sk2.unit_name != sk2.consumption_unit_name AND sk2.status = 1
                   AND sk2.consumption_unit_name IS NOT NULL AND sk2.consumption_unit_name != ''
             ) latest ON latest.sku_id = sk.id
             SET sk.consumption_unit_price = latest.new_price,
                 sk.consumption_factor = latest.map_cf, sk.sync = 0, sk.local_sync = 0
             WHERE sk.status = 1 AND sk.unit_name != sk.consumption_unit_name
               AND COALESCE(sk.consumption_unit_name, '') != '' AND latest.new_price > 0"
        );

        $this->Inventory_sku->query(
            "UPDATE inventory_skus sk
             INNER JOIN (
                 SELECT sl.sku_id, CAST(sl.sku_price AS DECIMAL(15,6)) AS new_price
                 FROM stock_ledger sl
                 JOIN inventory_skus sk2 ON sk2.id = sl.sku_id
                 WHERE sl.record_type = 'stockin' AND CAST(sl.sku_price AS DECIMAL(15,6)) > 0
                   AND (sk2.unit_name = sk2.consumption_unit_name
                        OR sk2.consumption_unit_name IS NULL OR sk2.consumption_unit_name = '')
                   AND sl.id = (
                       SELECT MAX(id) FROM stock_ledger sl2
                       WHERE sl2.sku_id = sl.sku_id AND sl2.record_type = 'stockin'
                         AND CAST(sl2.sku_price AS DECIMAL(15,6)) > 0)
             ) latest ON latest.sku_id = sk.id
             SET sk.consumption_unit_price = latest.new_price, sk.sync = 0, sk.local_sync = 0
             WHERE sk.status = 1 AND latest.new_price > 0"
        );

        $sfUpdate = "UPDATE inventory_semifinished isf
             LEFT JOIN v_semifinished_recipe_cost vsf ON vsf.id = isf.id
             SET isf.consumption_unit_price = COALESCE(vsf.unit_cost, isf.consumption_unit_price),
                 isf.sync = 0, isf.local_sync = 0
             WHERE isf.status = 1 AND COALESCE(vsf.unit_cost, 0) > 0";
        $this->Inventory_semifinished->query($sfUpdate);
        $this->Inventory_semifinished->query($sfUpdate);
        $this->Inventory_semifinished->query($sfUpdate);

        $this->Catalog_product->query(
            "UPDATE catalog_product cp
             LEFT JOIN v_product_recipe_cost vpc ON vpc.id = cp.id
             SET cp.food_cost = COALESCE(vpc.recipe_cost, 0),
                 cp.sync = 0, cp.local_sync = 0
             WHERE cp.status = 1"
        );

        $this->Inventory_semifinished->query(
            "UPDATE inventory_semifinished
             SET yield = consumption_factor, sync = 0, local_sync = 0
             WHERE status = 1 AND consumption_factor IS NOT NULL AND consumption_factor != ''
               AND CAST(consumption_factor AS DECIMAL(20,4)) > 0"
        );

        $this->Inventory_linking->query(
            "UPDATE inventory_linking il
             JOIN inventory_skus sk ON sk.id = CAST(il.component_id AS UNSIGNED)
             SET il.component_price = COALESCE(sk.consumption_unit_price, 0), il.modified = NOW()
             WHERE il.component_type = 'Sku' AND il.status = 1"
        );
        $this->Inventory_linking->query(
            "UPDATE inventory_linking il
             JOIN inventory_semifinished isf ON isf.id = CAST(il.component_id AS UNSIGNED)
             SET il.component_price = COALESCE(isf.consumption_unit_price, 0), il.modified = NOW()
             WHERE il.component_type = 'Semifinished' AND il.status = 1"
        );
        $this->Inventory_linking->query(
            "UPDATE inventory_linking il
             JOIN inventory_semifinished isf ON isf.id = CAST(il.parent_id AS UNSIGNED)
             SET il.parent_yield = isf.consumption_factor, il.modified = NOW()
             WHERE il.parent_type = 'Semifinished' AND il.status = 1
               AND isf.consumption_factor IS NOT NULL
               AND CAST(isf.consumption_factor AS DECIMAL(20,4)) > 0"
        );
    }

    public function package()
    {
        if (!$this->Session->check('Auth.User.id')) {
            return $this->redirect(['controller'=>'dashboard', 'action'=>'login']);
        }

        $this->layout = "users";
        $id = $_REQUEST['id'];

        $products = $this->Catalog_product->query("SELECT * FROM catalog_product WHERE id = $id");
        $this->set(compact('products'));

        $packages = $this->Catalog_package->query("SELECT * FROM catalog_package WHERE main_product_id = $id AND status = 1");
        $this->set(compact('packages'));

        if (!empty($_POST['select_semi_name']) && !empty($_POST['pro_price']) && !empty($_POST['pro_quantity'])) {
            $semi_ids = $_POST['select_semi_name'];
            $semi_prices = $_POST['pro_price'];
            $semi_quantities = $_POST['pro_quantity'];
            $semi_units = $_POST['pro_unit'];
            $product_id = $id;

            $this->Catalog_package->updateAll(['status' => 0,'sync' => 0,'local_sync' => 0], ['main_product_id' => $product_id]);

            $mainProduct = $this->Catalog_product->find('first', [
                'conditions' => ['id' => $product_id],
                'fields' => ['id', 'price', 'name', 'unit', 'unit_name']
            ]);

            if (empty($mainProduct)) {
                $this->Flash->error(__('Main product not found.'));
                return $this->redirect(['action' => 'product_list']);
            }

            $main_price = $mainProduct['Catalog_product']['price'];
            $main_name = $mainProduct['Catalog_product']['name'];
            $main_unit = $mainProduct['Catalog_product']['unit'];
            $main_unit_name = $mainProduct['Catalog_product']['unit_name'];

            foreach ($semi_ids as $i => $link_id) {
                $link_price = $semi_prices[$i];
                $link_qty = $semi_quantities[$i];

                if (empty($link_id) || $link_qty <= 0) continue;

                $linkProduct = $this->Catalog_product->find('first', [
                    'conditions' => ['id' => $link_id],
                    'fields' => ['id', 'name', 'unit', 'unit_name']
                ]);

                if (!empty($linkProduct)) {
                    $data['Catalog_package'] = [
                        'main_product_id' => $product_id,
                        'main_product_name' => $main_name,
                        'main_product_unit_id' => $main_unit,
                        'main_product_unit_name' => $main_unit_name,
                        'main_product_price' => $main_price,
                        'link_product_id' => $link_id,
                        'link_product_name' => $linkProduct['Catalog_product']['name'],
                        'link_product_unit_id' => $linkProduct['Catalog_product']['unit'],
                        'link_product_unit_name' => $linkProduct['Catalog_product']['unit_name'],
                        'link_product_price' => $link_price,
                        'link_quantity' => $link_qty,
                        'status' => 1,
                        'sync' => 0,
                        'local_sync' => 0
                    ];

                    $this->Catalog_package->create();
                    if (!$this->Catalog_package->save($data)) {
                        $this->Flash->error(__('Failed to save package item.'));
                    }
                }
            }

            $this->Catalog_product->updateAll(['package' => 1,'sync' => 0,'local_sync' => 0], ['id' => $product_id]);

            $this->Flash->success(__('Package saved successfully.'));
            return $this->redirect(['action' => 'product_list']);
        }
    }

    public function fetch_semifinished_options() {
        $this->autoRender = false;
        $semifinishedd=array();
        $semifinisheds = $this->Inventory_semifinished->find('all', [
            'conditions' => ['status' => 1],
            'order' => ['Inventory_semifinished.name' => 'ASC']
        ]);
        foreach($semifinisheds as $semifinished){
            $semifinishedd[]=$semifinished['Inventory_semifinished'];
        }
        $responseArray = array('response_code' => 1, 'data' => $semifinishedd);
        echo json_encode($responseArray);

    }

    public function fetch_sku_options() {
        $this->autoRender = false;
        $skuss=array();
        $skus = $this->Inventory_sku->find('all', [
            'conditions' => ['status' => 1, 'item_type' => 1],
            'order' => ['Inventory_sku.name' => 'ASC']
        ]);

        foreach($skus as $sku){
            $skuss[]=$sku['Inventory_sku'];
        }
        $responseArray = array('response_code' => 1, 'data' => $skuss);
        echo json_encode($responseArray);

    }

    public function bill_printpdf() {
        if(!$this->Session->check('Auth.User.id')){
            $this->redirect(array('controller'=>'dashboard','action' => 'login'));
        }

        $this->layout = "login";

        $id=  $_GET['id'];

        $transactions = $this->Order_transaction->query("
            SELECT
                order_transactions.*,
                locations.*,
                users.name as cashiername
            FROM
                order_transactions
            LEFT JOIN
                users ON users.id = order_transactions.cashier_id
            LEFT JOIN
                locations ON locations.id = users.location_id
            WHERE
                order_transactions.id = ".$id." and order_transactions.status!=0
        ");

        $reference_id=$transactions[0]['order_transactions']['reference_id'];
        $storeId=$transactions[0]['order_transactions']['store_id'];
        $cashier_id=$transactions[0]['order_transactions']['cashier_id'];
        $orderno=$transactions[0]['order_transactions']['orderno'];
        $discount=$transactions[0]['order_transactions']['discount'];
        $servicecharge=$transactions[0]['order_transactions']['servicecharge'];
        $delivery_charge=$transactions[0]['order_transactions']['delivery_charge'];
        $packing_charge=$transactions[0]['order_transactions']['packing_charge'];
        $amount=$transactions[0]['order_transactions']['amount'];
        $created=$transactions[0]['order_transactions']['created'];
        $table_number=$transactions[0]['order_transactions']['table_number'];
        $pax=$transactions[0]['order_transactions']['pax'];
        $price=$transactions[0]['order_transactions']['price'];
        $today= date('Y-m-d H:i:s');

        $name=$transactions[0]['locations']['name'];
        $print_line1=$transactions[0]['locations']['print_line1'];
        $print_line2=$transactions[0]['locations']['print_line2'];
        $print_line3=$transactions[0]['locations']['print_line3'];
        $print_line4=$transactions[0]['locations']['print_line4'];
        $print_line5=$transactions[0]['locations']['print_line5'];
        $print_line6=$transactions[0]['locations']['print_line6'];
        $print_line7=$transactions[0]['locations']['print_line7'];
        $print_line8=$transactions[0]['locations']['print_line8'];
        $print_line9=$transactions[0]['locations']['print_line9'];
        $print_line10=$transactions[0]['locations']['print_line10'];
        $footer_msg=$transactions[0]['locations']['footer_msg'];
        $currency_symbol=$transactions[0]['locations']['currency_symbol'];
        $cashiername=$transactions[0]['users']['cashiername'];

        $orderss = $this->Order_orderhistory->query("SELECT id,product_name,SUM(quantity) as quantity,sum(total_price) as total_price FROM `order_orderhistories` WHERE reference_id='$reference_id' and store_id = '$storeId' and status!=0 group BY product_id");
        $taxes = $this->Order_tax->query("SELECT tax_id,tax_name,tax_percentage,sum(tax_value) as tax_value FROM `order_tax` WHERE reference_id='$reference_id' and status!=0 GROUP by tax_id");
        $discper = $this->Order_tax->query("SELECT discount_percentage FROM `order_tax` WHERE reference_id='$reference_id' and discount_percentage!=0 and status!=0 limit 1");
        if(!empty($discper))
        {
            $discount_percentage=$discper[0]['order_tax']['discount_percentage'];
        }
        if(!isset($discount_percentage)){
            $discount_percentage="0";
        }

        $payments = $this->Order_payment->find('all', array('conditions'=>array('reference_id'=>$reference_id,'status !='=>0)));
        $mode=   $payments[0]['Order_payment']['type_value'];

        $this->set(compact('pax','currency_symbol','delivery_charge','packing_charge','mode','price','table_number','cashiername','servicecharge','amount','today','created','name','print_line1','print_line2','print_line3','print_line4','print_line5','print_line6','print_line7','print_line8','print_line9','print_line10','orderno','orderss','taxes','footer_msg','discount_percentage','discount'));

    }

    public function internal_indent_list()
    {
        if (!$this->Session->check('Auth.User.id')) {
            return $this->redirect(['controller' => 'dashboard', 'action' => 'login']);
        }

        $this->layout = "users";

        $startDate = date('m/d/Y', strtotime('-1 month'));
        $endDate   = date('m/d/Y');
        $startdate = date('Y-m-d', strtotime('-1 month'));
        $enddate   = date('Y-m-d');

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

        $_shiftRange = $this->getShiftDateRange($startdate, $enddate);
        $startDatetime = $_shiftRange['startDatetime'];
        $endDatetime   = $_shiftRange['endDatetime'];

        $authUser  = $this->Session->read('Auth.User');
        $userRole  = $authUser['role'] ?? '';
        $locationIds = $authUser['location_ids'] ?? '';

        $conditions2 = ['status' => 1];

        if ($userRole !== 'admin' && !empty($locationIds)) {
            $userLocationIds = Hash::extract(
                ['ids' => is_array($locationIds) ? $locationIds : explode(',', $locationIds)],
                'ids.{n}'
            );
            $conditions2['id IN'] = array_map('trim', $userLocationIds);
        }

        $stores = $this->Location->find('all', [
            'conditions' => $conditions2
        ]);
        $this->set(compact('stores'));

        $users=[];

        if ($this->request->is(['post', 'put'])) {

            $indentConditions = [
                'status' => 1,
                "created >=" => $startDatetime,
                "created <=" => $endDatetime,
            ];

            if ($userRole !== 'admin' && !empty($conditions2['id IN'])) {
                $indentConditions['location_id IN'] = $conditions2['id IN'];
            }

            if (!empty($this->request->data['Search']['cashier'])) {
                $cashierId = (int)$this->request->data['Search']['cashier'];
                $indentConditions['location_id'] = $cashierId;
            }

            $users = $this->Inventory_internal_indent->find('all', [
                'conditions' => $indentConditions,
                'order' => ['id' => 'DESC']
            ]);
        }

        $this->set(compact('users'));

        $openindentConditions = [
            'status' => 1,
            'is_completed' => 0
        ];
        if ($userRole !== 'admin' && !empty($conditions2['id IN'])) {
            $openindentConditions['location_id IN'] = $conditions2['id IN'];
        }
        if (!empty($this->request->data['Search']['cashier'])) {
            $cashierId = (int)$this->request->data['Search']['cashier'];
            $openindentConditions['location_id'] = $cashierId;
        }
        $open_indents = $this->Inventory_internal_indent->find('count', [
            'conditions' => $openindentConditions
        ]);
        $this->set(compact('open_indents'));

        $closedindentConditions = [
            'status' => 1,
            'is_completed' => 1
        ];
        if ($userRole !== 'admin' && !empty($conditions2['id IN'])) {
            $closedindentConditions['location_id IN'] = $conditions2['id IN'];
        }
        if (!empty($this->request->data['Search']['cashier'])) {
            $cashierId = (int)$this->request->data['Search']['cashier'];
            $closedindentConditions['location_id'] = $cashierId;
        }
        $closed_indents = $this->Inventory_internal_indent->find('count', [
            'conditions' => $closedindentConditions
        ]);

        $this->set(compact('open_indents'));
        $this->set(compact('closed_indents'));

        $this->set('startdate', $startDate . ' - ' . $endDate);
    }

    public function create_internal_indent()
    {
        if (!$this->Session->check('Auth.User.id')) {
            $this->redirect(['controller' => 'dashboard', 'action' => 'login']);
        }

        $this->layout = "users";

        if ($this->Session->read('Auth.User.role') == 'admin') {
            $locations = $this->Location->find('all', ['conditions' => ['status' => 1]]);
        } else {

            $authUser = $this->Session->read('Auth.User');
            $userRole = $authUser['role'] ?? '';
            $userLocationId = $authUser['location_ids'] ?? 0;

            $userLocationIds = is_array($userLocationId)
                ? $userLocationId
                : array_map('trim', explode(',', $userLocationId));

            $locationCondition = ['status' => 1];

            if ($userRole !== 'admin') {
                $locationCondition['id'] = $userLocationIds;
            }

            $locations = $this->Location->find('all', [
                'conditions' => [
                    $locationCondition
                ]
            ]);

        }

        $this->set(compact('locations'));

        $users = $this->Inventory_sku->find('all', [
            'fields' => [
                'Inventory_sku.*',
                'COALESCE(SUM(Inventory_sku_tax.tax_percentage),0) AS total_tax'
            ],
            'joins' => [
                [
                    'table' => 'inventory_sku_tax',
                    'alias' => 'Inventory_sku_tax',
                    'type' => 'LEFT',
                    'conditions' => [
                        'Inventory_sku_tax.product_id = Inventory_sku.id',
                        'Inventory_sku_tax.status' => 1
                    ]
                ]
            ],
            'conditions' => [
                'Inventory_sku.status' => 1
            ],
            'group' => ['Inventory_sku.id'],
            'order' => ['Inventory_sku.name ASC']
        ]);
        $this->set(compact('users'));
    }

    public function add_internal_indent()
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

            $location = $this->Location->find('first', [
                'conditions' => ['Location.id' => $location_id],
                'fields' => ['id', 'name'],
                'recursive' => -1
            ]);

            if (empty($location)) {
                echo json_encode(['response_code' => 0, 'data' => 'Invalid location selected.']);
                return;
            }

            do {

                $txnid = rand(1, 9);
                for ($i = 0; $i < 10; $i++) {
                    $txnid .= rand(0, 9);
                }

                $existing = $this->Inventory_internal_indent_items->find('first', [
                    'conditions' => ['indent_id' => $txnid],
                    'recursive' => -1
                ]);
            } while ($existing);

            $amount = 0;
            $itemsSaved = true;

            foreach ($checkeddatas as $item) {
                $skuid        = $item['id'];
                $landedPrice  = floatval($item['price']);
                $excisePu     = floatval($item['excise_per_unit']       ?? 0);
                $addlExcisePu = floatval($item['addl_excise_per_unit']  ?? 0);
                $quantity     = $item['sku_quantity'];

                $product_taxes=$this->Inventory_sku_tax->query("select SUM(tax_percentage) as total_tax from inventory_sku_tax where product_id='$skuid'  AND status = 1");
                $total_tax=round($product_taxes['0']['0']['total_tax'],2);
                $tax_value = round(($landedPrice * $quantity) * ($total_tax / 100), 2);
                $skuamount = round(($landedPrice * $quantity) + $tax_value, 2);
                $landed_amt_total = round($landedPrice * $quantity, 2);

                $skuData = [
                    'indent_id' => $txnid,
                    'sku_id' => $skuid,
                    'sku_name' => $item['sku_name'],
                    'sku_brand' => $item['sku_brand'],
                    'sku_unit' => $item['unit_id'],
                    'sku_unit_name' => $item['unit_name'],
                    'sku_price' => $landedPrice,
                    'sku_quantity' => $quantity,
                    'sku_total_tax_percentage' => $total_tax,
                    'sku_total_tax_value' => $tax_value,
                    'excise_per_unit' => $excisePu,
                    'addl_excise_per_unit' => $addlExcisePu,
                    'sku_amount' => $skuamount,
                    'landed_amt' => $landed_amt_total,
                    'sync' => 0,
                    'local_sync' => 0
                ];

                $this->Inventory_internal_indent_items->create();
                if (!$this->Inventory_internal_indent_items->save($skuData)) {
                    $itemsSaved = false;
                    break;
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
                    'sync' => 0,
                    'local_sync' => 0
                ];

                $this->Inventory_internal_indent->create();
                if ($this->Inventory_internal_indent->save($indentData)) {
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

    public function get_location_excise_rates()
    {
        $this->autoRender = false;
        header('Content-Type: application/json');
        if (!$this->Session->check('Auth.User.id')) {
            echo json_encode(['error' => 'not logged in']);
            return;
        }
        $location_id = intval($_GET['location_id'] ?? 0);
        if (!$location_id) {
            echo json_encode(['rates' => []]);
            return;
        }

        $rows = $this->Inventory_sku->query(
            "SELECT sku_id, location_id,
                    excise_per_unit, addl_excise_per_unit,
                    vat_percentage,  tcs_percentage
             FROM inventory_sku_excise
             WHERE (location_id = $location_id OR location_id = 0) AND status = 1
             ORDER BY sku_id ASC, location_id DESC"
        );
        $rates = [];
        foreach ($rows as $row) {
            $d = !empty($row['inventory_sku_excise']) ? $row['inventory_sku_excise'] : (isset($row[0]) ? $row[0] : $row);
            $sid = $d['sku_id'];

            if (!isset($rates[$sid])) {
                $rates[$sid] = [
                    'excise_per_unit'      => $d['excise_per_unit'],
                    'addl_excise_per_unit' => $d['addl_excise_per_unit'],
                    'vat_percentage'       => $d['vat_percentage'],
                    'tcs_percentage'       => $d['tcs_percentage']
                ];
            }
        }
        echo json_encode(['rates' => $rates]);
    }

    public function view_internal_indent()
    {
        if(!$this->Session->check('Auth.User.id')){
            $this->redirect(array('controller'=>'dashboard','action' => 'login'));
        }

        $this->layout = "users";
        $id=$_REQUEST['id'];

        $indents = $this->Inventory_internal_indent->query("SELECT * FROM inventory_internal_indent where indent_id=$id and status=1");
        $this->set(compact('indents'));

        $items = $this->Inventory_internal_indent_items->query("SELECT * FROM inventory_internal_indent_items where indent_id=$id and status=1");

        $this->set(compact('items'));

    }

    public function edit_internal_indent()
    {
        if (!$this->Session->check('Auth.User.id')) {
            return $this->redirect(['controller' => 'dashboard', 'action' => 'login']);
        }

        $this->layout = "users";
        $id = $_REQUEST['id'];

        $user_id = $this->Session->read('Auth.User.id');
        $role = $this->Session->read('Auth.User.role');
        $user_location = $this->Session->read('Auth.User.location_id');

        if ($role == 'admin') {
            $locations = $this->Location->find('list', ['conditions' => ['status' => 1]]);
        } else {
            $locations = $this->Location->find('list', [
                'conditions' => ['status' => 1, 'id' => $user_location]
            ]);
        }

        $user = "
            SELECT *,
                (
                    SELECT sku_quantity
                    FROM inventory_internal_indent_items
                    WHERE indent_id='$id'
                    AND sku_id = inventory_skus.id
                    AND status = 1
                ) as quantity,

                (
                    SELECT COALESCE(SUM(tax_percentage),0)
                    FROM inventory_sku_tax
                    WHERE product_id = inventory_skus.id
                    AND status = 1
                ) as total_tax

            FROM inventory_skus
            WHERE inventory_skus.status = 1
            ORDER BY inventory_skus.name ASC
        ";
        $users = $this->Inventory_sku->query($user);

        $indentData = $this->Inventory_internal_indent->find('first', [
            'conditions' => ['indent_id' => $id,'status' => 1],
            'recursive' => -1
        ]);

        $selected_location_id = $indentData['Inventory_internal_indent']['location_id'];

        $this->set(compact('users', 'id', 'locations', 'selected_location_id'));
    }

    public function update_internal_indent()
    {
        $this->autoRender = false;

        $id = $this->Session->read('Auth.User.id');
        $name = $this->Session->read('Auth.User.name');

        if (isset($_POST['checkedData'])) {
            $checkeddatas = json_decode($_POST['checkedData'], true);
            $indentid = $_POST['indent_id'];
            $location_id = isset($_POST['location_id']) ? $_POST['location_id'] : null;
            $location_name = '';

            $indentLock = $this->Inventory_internal_indent->field('po_locked', ['indent_id' => $indentid]);
            if (!empty($indentLock)) {
                echo json_encode(['response_code' => 0, 'data' => 'This indent is locked: a PO already exists. Cancel the PO first.']);
                return;
            }

            if (!empty($location_id)) {
                $loc = $this->Location->find('first', [
                    'conditions' => ['Location.id' => $location_id],
                    'fields' => ['Location.name'],
                    'recursive' => -1
                ]);
                $location_name = isset($loc['Location']['name']) ? $loc['Location']['name'] : '';
            }

            $this->Inventory_internal_indent_items->query("UPDATE inventory_internal_indent_items SET status=0,sync=0,local_sync=0 WHERE indent_id = " . (int)$indentid);
            $this->Inventory_internal_indent->query("UPDATE inventory_internal_indent SET status=0,sync=0,local_sync=0 WHERE indent_id = " . (int)$indentid);

            $amount = 0;
            $chk = false;

            foreach ($checkeddatas as $checkeddata) {
                $skuid = $checkeddata['id'];
                $price = $checkeddata['price'];
                $sku_name = $checkeddata['sku_name'];
                $sku_brand = $checkeddata['sku_brand'];
                $unit_name = $checkeddata['unit_name'];
                $unit_id = $checkeddata['unit_id'];
                $quantity = $checkeddata['sku_quantity'];
                $is_completed = $checkeddata['is_completed'];
                $completed_quantity = $checkeddata['completed_quantity'] ?? 0;

                $product_taxes=$this->Inventory_sku_tax->query("select SUM(tax_percentage) as total_tax from inventory_sku_tax where product_id='$skuid' and status=1");
                $total_tax=round($product_taxes['0']['0']['total_tax'],2);
                $tax_value = round(($price*$quantity) * ($total_tax / 100),2);
                $skuamount=round(($price*$quantity) + $tax_value,2);

                if (!empty($quantity)) {
                    if($completed_quantity>=$quantity){
                        $is_completed=1;
                    }else{
                        $is_completed=0;
                    }

                    $itemData = array(
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
                        'is_completed' => $is_completed,
                        'completed_quantity' => $completed_quantity,
                        'sync'      =>0,
                        'local_sync' =>0
                    );

                    if ($this->Inventory_internal_indent_items->saveAll($itemData)) {
                        $chk = true;
                    } else {
                        $chk = false;
                        $this->Flash->error(__('Internal indent could not be updated. Please, try again.'));
                    }

                    $amount += ($skuamount);
                }
            }

            if ($chk) {

                $checkindent = "
                    SELECT id
                    FROM inventory_internal_indent_items
                    WHERE status = 1
                      AND is_completed = 0
                      AND indent_id = " . (int)$indentid;

                $checkindentstatus = $this->Inventory_internal_indent_items->query($checkindent);

                if (!empty($checkindentstatus)) {

                    $indentcomplete = 0;
                } else {

                    $indentcomplete = 1;
                }

                $indentData = array(
                    'indent_id' => $indentid,
                    'created_by' => $id,
                    'created_by_name' => $name,
                    'location_id' => $location_id,
                    'location_name' => $location_name,
                    'total_amount' => $amount,
                    'is_updated' => 1,
                    'is_completed' => $indentcomplete,
                    'status'    => 1,
                    'sync'      =>0,
                    'local_sync' =>0
                );

                if ($this->Inventory_internal_indent->saveAll($indentData)) {
                    echo json_encode(['response_code' => 1, 'data' => 'Updated']);
                } else {
                    echo json_encode(['response_code' => 0, 'data' => 'Internal indent could not be updated. Please, try again.']);
                }
            } else {
                echo json_encode(['response_code' => 0, 'data' => 'Internal indent items could not be updated. Please, try again.']);
            }
        }
    }

    public function po_list()
    {

        if (!$this->Session->check('Auth.User.id')) {
            return $this->redirect(['controller' => 'dashboard', 'action' => 'login']);
        }

        $this->layout = "users";
        $authUser = $this->Session->read('Auth.User');
        $userRole = $authUser['role'] ?? '';
        $locationIds = $authUser['location_ids'] ?? '';

        $startDate = date('m/d/Y', strtotime('-1 month'));
        $endDate   = date('m/d/Y');
        $startDateFormatted = date('Y-m-d', strtotime('-1 month'));
        $endDateFormatted   = date('Y-m-d');

        if ($this->request->is(['post', 'put'])) {
            $daterange = $this->request->data['daterange'] ?? '';
            if (strpos($daterange, '-') !== false) {
                list($startRaw, $endRaw) = explode(" - ", $daterange);
                $startDate = trim($startRaw);
                $endDate   = trim($endRaw);
                $startDateFormatted = date('Y-m-d', strtotime($startDate));
                $endDateFormatted   = date('Y-m-d', strtotime($endDate));
            }
        }

        $conditions2 = ['status' => 1];

        if ($userRole !== 'admin' && !empty($locationIds)) {

            $userLocationIds = Hash::extract(
                ['ids' => is_array($locationIds) ? $locationIds : explode(',', $locationIds)],
                'ids.{n}'
            );
            $conditions2['id IN'] = array_map('trim', $userLocationIds);
        }

        $stores = $this->Location->find('all', [
            'conditions' => $conditions2
        ]);

        $_shiftRange = $this->getShiftDateRange($startDateFormatted, $endDateFormatted);
        $startDatetime = $_shiftRange['startDatetime'];
        $endDatetime   = $_shiftRange['endDatetime'];

        if ($this->request->is(['post', 'put'])) {

            $poConditions = [
                "status !=" => 0,
                "created >=" => $startDatetime,
                "created <=" => $endDatetime,
            ];

            if ($userRole !== 'admin' && !empty($conditions2['id IN'])) {
                $poConditions['location_id IN'] = $conditions2['id IN'];
            }

            if (!empty($this->request->data['Search']['cashier'])) {
                $cashierId = (int)$this->request->data['Search']['cashier'];
                $poConditions['location_id'] = $cashierId;
            }

            $purchaseOrders = $this->Inventory_purchase_order->find('all', [
                'conditions' => $poConditions,
                'order' => ['id' => 'DESC']
            ]);
        }

        $this->set(compact('stores', 'purchaseOrders'));

        $submittedpoConditions = [
            "status" => 1,
            "direct_purchase" => 0
        ];
        if ($userRole !== 'admin' && !empty($conditions2['id IN'])) {
            $submittedpoConditions['location_id IN'] = $conditions2['id IN'];
        }
        if (!empty($this->request->data['Search']['cashier'])) {
            $cashierId = (int)$this->request->data['Search']['cashier'];
            $submittedpoConditions['location_id'] = $cashierId;
        }
        $submittedpo = $this->Inventory_purchase_order->find('count', [
            'conditions' => $submittedpoConditions
        ]);

        $approvedpoConditions = [
            "status" => 2,
            "direct_purchase" => 0
        ];
        if ($userRole !== 'admin' && !empty($conditions2['id IN'])) {
            $approvedpoConditions['location_id IN'] = $conditions2['id IN'];
        }
        if (!empty($this->request->data['Search']['cashier'])) {
            $cashierId = (int)$this->request->data['Search']['cashier'];
            $approvedpoConditions['location_id'] = $cashierId;
        }
        $approvedpo = $this->Inventory_purchase_order->find('count', [
            'conditions' => $approvedpoConditions
        ]);

        $processedpoConditions = [
            "status" => 3,
            "direct_purchase" => 0
        ];
        if ($userRole !== 'admin' && !empty($conditions2['id IN'])) {
            $processedpoConditions['location_id IN'] = $conditions2['id IN'];
        }
        if (!empty($this->request->data['Search']['cashier'])) {
            $cashierId = (int)$this->request->data['Search']['cashier'];
            $processedpoConditions['location_id'] = $cashierId;
        }
        $processedpo = $this->Inventory_purchase_order->find('count', [
            'conditions' => $processedpoConditions
        ]);

        $stockedinpoConditions = [
            "status" => 4,
            "direct_purchase" => 0
        ];
        if ($userRole !== 'admin' && !empty($conditions2['id IN'])) {
            $stockedinpoConditions['location_id IN'] = $conditions2['id IN'];
        }
        if (!empty($this->request->data['Search']['cashier'])) {
            $cashierId = (int)$this->request->data['Search']['cashier'];
            $stockedinpoConditions['location_id'] = $cashierId;
        }
        $stockedinpo = $this->Inventory_purchase_order->find('count', [
            'conditions' => $stockedinpoConditions
        ]);
        $this->set(compact( 'submittedpo','approvedpo','processedpo','stockedinpo'));

        $this->set('startdate', $startDate . ' - ' . $endDate);
    }

    public function create_po()
    {
        if (!$this->Session->check('Auth.User.id')) {
            return $this->redirect(['controller' => 'dashboard', 'action' => 'login']);
        }

        $this->layout = "users";

        $authUser = $this->Session->read('Auth.User');
        $userRole = $authUser['role'] ?? '';
        $userLocationId = $authUser['location_ids'] ?? 0;
        $id = $authUser['id'];
        $name = $authUser['name'];

        $vendoroptions = $this->Inventory_vendor->find('list', [
            'conditions' => ['Inventory_vendor.status' => 1],
            'fields' => ['id', 'company'],
            'order' => ['name' => 'ASC'],
            'recursive' => -1
        ]);

        $userLocationIds = array_map('trim', explode(',', $userLocationId));

        $locationOptions = $this->Location->find('list', [
            'conditions' => ['status' => 1] + ($userRole !== 'admin' ? ['id' => $userLocationIds] : []),
            'fields' => ['id', 'name'],
            'order' => ['name' => 'ASC'],
            'recursive' => -1
        ]);
        $this->set(compact('vendoroptions', 'locationOptions'));

        if ($this->request->is(['post', 'put'])) {
            $data = $this->request->data['User'];
            $today = date('Y-m-d H:i:s');

            $locationId = (int) ($data['location'] ?? 0);
            $locationSave = $this->Auth->user('id');
            $locationdetail=$this->Location->find('first',array('conditions'=>array('Location.id'=>$locationId)));
            $locationname=$locationdetail['Location']['name'];

            $location_type=$locationdetail['Location']['type'];

            $indentId       = (int) $data['indent'];
            $vendorId       = (int) $data['vendor'];
            $prices         = $this->request->data('prices')      ?? [];
            $quantities     = $this->request->data('quantity')    ?? [];
            $excise_duties  = $this->request->data('excise_duty') ?? [];
            $addl_excises   = $this->request->data('addl_excise') ?? [];
            $vat_duties     = $this->request->data('vat_duty')    ?? [];
            $tcs_duties     = $this->request->data('tcs_duty')    ?? [];
            $vendor = $this->Inventory_vendor->find('first', [
                'conditions' => ['Inventory_vendor.id' => $vendorId],
                'recursive' => -1
            ]);

            $charge = $data['charge'] ?? 0;
            $po_date = !empty($_POST['po_date']) ? $_POST['po_date'] : date('Y-m-d');

            if (empty($vendor)) {
                $this->Flash->error(__('Invalid vendor selected.'));
                return;
            }
            $approval_amount=approval_amount;

            if($vendor['Inventory_vendor']['po_approval_required']){
                $postatus = 1;
            }else{
                $postatus = 2;
            }

            do {
                $poId = str_pad(mt_rand(1, 9999999999), 10, '0', STR_PAD_LEFT);
            } while ($this->Inventory_purchase_order->hasAny(['p_o_id' => $poId]));

            $totalAmount = 0;
            $totaldiscount=0;

            foreach ($quantities as $skuId => $qty) {
                $qty = (float) $qty;
                if ($qty <= 0) continue;

                $price = isset($prices[$skuId]) ? (float) $prices[$skuId] : 0;

                $sku = $this->Inventory_sku->find('first', [
                    'conditions' => ['Inventory_sku.id' => $skuId],
                    'recursive' => -1
                ]);

                if (empty($sku)) continue;

                $product_taxes=$this->Inventory_sku_tax->query("select SUM(tax_percentage) as total_tax from inventory_sku_tax where product_id='$skuId' and status=1");
                $total_tax=round($product_taxes['0']['0']['total_tax'],2);

                if($postatus==2){
                    $approved_quantity=$qty;
                    $approved_total_tax_value=$total_tax;
                    $approved_amount=$skuamount;
                }else{
                    $approved_quantity=0;
                    $approved_total_tax_value=0;
                    $approved_amount=0;
                }

                $baseAmount = $price * $qty;

                $itemDiscountType = $this->request->data['discount_type'][$skuId] ?? 'flat';

                $discountInput = isset($this->request->data['sku_discount'][$skuId])
                    ? (float)$this->request->data['sku_discount'][$skuId]
                    : 0;

                if ($itemDiscountType === 'percentage') {

                    if ($discountInput > 100) {
                        $discountInput = 100;
                    }

                    $itemDiscountValue = round(($baseAmount * $discountInput) / 100, 2);

                } else {

                    if ($discountInput > $baseAmount) {
                        $discountInput = $baseAmount;
                    }

                    $itemDiscountValue = $discountInput;
                }

                $amountAfterDiscount = $baseAmount - $itemDiscountValue;

                $exciseDutyPerUnit     = floatval($excise_duties[$skuId] ?? 0);
                $addlExciseDutyPerUnit = floatval($addl_excises[$skuId]  ?? 0);
                $vatPct                = floatval($vat_duties[$skuId]    ?? 0);
                $tcsPct                = floatval($tcs_duties[$skuId]    ?? 0);

                $exciseDutyAmt     = round($exciseDutyPerUnit     * $qty, 2);
                $addlExciseDutyAmt = round($addlExciseDutyPerUnit * $qty, 2);

                $vatBase   = $amountAfterDiscount + $exciseDutyAmt + $addlExciseDutyAmt;
                $vatAmt    = round($vatBase * ($vatPct / 100), 2);

                $tcsAmt    = round(($amountAfterDiscount + $exciseDutyAmt + $vatAmt) * ($tcsPct / 100), 2);

                $landedAmt = round($amountAfterDiscount + $exciseDutyAmt + $vatAmt, 2);

                $effective_tax = ($exciseDutyPerUnit > 0 || $vatPct > 0) ? 0 : $total_tax;
                $tax_value = round($landedAmt * ($effective_tax / 100), 2);

                $skuamountFinal = round($landedAmt + $tax_value + $tcsAmt, 2);

                $itemData = [
                    'Inventory_purchase_order_item' => [
                        'p_o_id' => $poId,
                        'internal_indent_id' => $indentId,
                        'sku_id' => $skuId,
                        'sku_name' => $sku['Inventory_sku']['name'],
                        'sku_unit' => $sku['Inventory_sku']['unit'],
                        'sku_unit_name' => $sku['Inventory_sku']['unit_name'],
                        'sku_price' => $price,
                        'sku_quantity' => $qty,
                        'sku_total_tax_percentage' => $total_tax,
                        'sku_total_tax_value' => $tax_value,
                        'excise_duty_amt'      => $exciseDutyAmt,
                        'addl_excise_duty_amt' => $addlExciseDutyAmt,
                        'vat_percentage'       => $vatPct,
                        'vat_amt'              => $vatAmt,
                        'tcs_percentage'       => $tcsPct,
                        'tcs_amt'              => $tcsAmt,
                        'excise_per_unit'      => $exciseDutyPerUnit,
                        'addl_excise_per_unit' => $addlExciseDutyPerUnit,
                        'landed_amt'           => $landedAmt,
                        'sku_discount_type' => $itemDiscountType,
                        'sku_discount' => $discountInput,
                        'sku_discount_value' => $itemDiscountValue,
                        'sku_amount' => $skuamountFinal,
                        'approved_quantity' => $approved_quantity,
                        'approved_total_tax_value' => $approved_total_tax_value,
                        'approved_amount' => ($postatus == 2) ? $skuamountFinal : 0,
                        'status' => $postatus,
                        'sync'      =>0,
                        'local_sync' =>0
                    ]
                ];

                if (!$this->Inventory_purchase_order_item->saveAll($itemData)) {
                    $this->Flash->error(__('Failed to save PO item.'));
                    return;
                }

                $totalAmount += $skuamountFinal;
                $totaldiscount += $itemDiscountValue;

            }

            if($totalAmount>$approval_amount){
                $postatus=1;
            }

            if($postatus==2){
                $approved_by_id=$id;
                $approved_by_name=$name;
                $approved_on=$today;
                $approved_amount=$totalAmount+$charge;
            }else{
                $approved_by_id=0;
                $approved_by_name='';
                $approved_on=NULL;
                $approved_amount=0;
            }

            $poData = [
                'Inventory_purchase_order' => [
                    'p_o_id' => $poId,
                    'vendor_id' => $vendorId,
                    'vendor_name' => $vendor['Inventory_vendor']['company'],
                    'internal_indent_id' => $indentId,
                    'location_id' => $locationId,
                    'location_name' => $locationname,
                    'location_type' => $location_type,
                    'charge' => $charge,
                    'total_amount' => $totalAmount+$charge,
                    'status' => $postatus,
                    'approved_by_id' => $approved_by_id,
                    'approved_by_name' => "$approved_by_name",
                    'approved_on' => $approved_on,
                    'approved_amount' => $approved_amount,
                    'sync'      =>0,
                    'local_sync' =>0,
                    'discount_value' => $totaldiscount,
                    'po_date' => $po_date

                ]
            ];

            $this->Inventory_purchase_order_item->query("UPDATE inventory_purchase_order_items set approved_quantity=0,approved_total_tax_value=0,approved_amount=0,status=$postatus,sync=0,local_sync=0 WHERE status!=0 and p_o_id ='$poId'");

            if ($this->Inventory_purchase_order->saveAll($poData)) {

                $indentItems = $this->Inventory_internal_indent_items->find('all', [
                    'conditions' => [
                        'Inventory_internal_indent_items.indent_id' => $indentId,
                        'Inventory_internal_indent_items.status' => 1
                    ],
                    'recursive' => -1
                ]);
                $allItemsCompleted = true;

                foreach ($indentItems as $item) {
                    $itemId    = $item['Inventory_internal_indent_items']['Id'];
                    $skuId     = $item['Inventory_internal_indent_items']['sku_id'];
                    $indentQty = (float)$item['Inventory_internal_indent_items']['sku_quantity'];

                    $poQtyData = $this->Inventory_purchase_order_item->find('first', [
                        'conditions' => [
                            'Inventory_purchase_order_item.internal_indent_id' => $indentId,
                            'Inventory_purchase_order_item.sku_id' => $skuId,
                            'Inventory_purchase_order_item.status !=' => 0
                        ],
                        'fields' => [
                            'SUM(Inventory_purchase_order_item.sku_quantity) as total_qty'
                        ],
                        'recursive' => -1
                    ]);

                    $totalPoQty = (float)($poQtyData[0]['total_qty'] ?? 0);

                    if ($totalPoQty > $indentQty) {
                        $totalPoQty = $indentQty;
                    }

                    $this->Inventory_internal_indent_items->updateAll(
                        [
                            'Inventory_internal_indent_items.completed_quantity' => $totalPoQty,
                            'Inventory_internal_indent_items.is_completed' =>
                                ($totalPoQty >= $indentQty) ? 1 : 0
                        ],
                        [
                            'Inventory_internal_indent_items.id' => $itemId,
                            'Inventory_internal_indent_items.status' => 1
                        ]
                    );

                    if ($totalPoQty < $indentQty) {
                        $allItemsCompleted = false;
                    }
                }

                if ($allItemsCompleted) {
                    $this->Inventory_internal_indent->updateAll(
                        [
                            'Inventory_internal_indent.is_completed' => 1,
                            'Inventory_internal_indent.modified' =>
                                "'" . date('Y-m-d H:i:s') . "'"
                        ],
                        [
                            'Inventory_internal_indent.indent_id' => $indentId,
                            'Inventory_internal_indent.status' => 1
                        ]
                    );
                }

                $this->Inventory_indent->query(
                    "UPDATE inventory_internal_indent SET po_locked=1, sync=0, local_sync=0 WHERE indent_id=" . (int)$indentId
                );

                $this->Flash->success(__('PO has been successfully created.'));
                return $this->redirect(['action' => 'po_list']);
            } else {
                $this->Flash->error(__('Failed to save PO. Please try again.'));
            }
        }
    }

    public function create_po_without_indent()
    {
        if (!$this->Session->check('Auth.User.id')) {
            return $this->redirect(['controller' => 'dashboard', 'action' => 'login']);
        }

        $this->layout = "users";

        $authUser = $this->Session->read('Auth.User');
        $userRole = $authUser['role'] ?? '';
        $userLocationId = $authUser['location_ids'] ?? 0;
        $id = $authUser['id'];
        $name = $authUser['name'];

        $userLocationIds = array_map('trim', explode(',', $userLocationId));

        $locationOptions = $this->Location->find('list', [
            'conditions' => ['status' => 1] + ($userRole !== 'admin' ? ['id' => $userLocationIds] : []),
            'fields' => ['id', 'name'],
            'order' => ['name' => 'ASC'],
            'recursive' => -1
        ]);
        $this->set(compact('locationOptions'));

        if ($this->request->is(['post', 'put'])) {
            $data = $this->request->data['User'];
            $today = date('Y-m-d H:i:s');

            $locationId = (int) ($data['location'] ?? 0);
            $locationdetail = $this->Location->find('first', array('conditions' => array('Location.id' => $locationId)));
            $locationname = $locationdetail['Location']['name'] ?? '';
            $location_type = $locationdetail['Location']['type'] ?? '';

            $vendorId       = (int) ($data['vendor'] ?? 0);
            $prices         = $this->request->data('prices')        ?? [];
            $quantities     = $this->request->data('quantity')      ?? [];
            $excise_duties  = $this->request->data('excise_duty')   ?? [];
            $addl_excises   = $this->request->data('addl_excise')   ?? [];
            $vat_duties     = $this->request->data('vat_duty')      ?? [];
            $tcs_duties     = $this->request->data('tcs_duty')      ?? [];
            $discount_types = $this->request->data('discount_type') ?? [];
            $sku_discounts  = $this->request->data('sku_discount')  ?? [];

            $vendor = $this->Inventory_vendor->find('first', [
                'conditions' => ['Inventory_vendor.id' => $vendorId],
                'recursive' => -1
            ]);

            $charge = (float)($data['charge'] ?? 0);
            $po_date = !empty($_POST['po_date']) ? $_POST['po_date'] : date('Y-m-d');

            if (empty($vendor)) {
                $this->Flash->error(__('Invalid vendor selected.'));
                return;
            }
            $approval_amount = defined('approval_amount') ? approval_amount : 50000;

            if (!empty($vendor['Inventory_vendor']['po_approval_required'])) {
                $postatus = 1;
            } else {
                $postatus = 2;
            }

            do {
                $poId = str_pad(mt_rand(1, 9999999999), 10, '0', STR_PAD_LEFT);
            } while ($this->Inventory_purchase_order->hasAny(['p_o_id' => $poId]));

            do {
                $autoIndentId = (string)mt_rand(10000000000, 99999999999);
                $existingIndent = $this->Inventory_internal_indent->hasAny(['indent_id' => $autoIndentId]);
            } while ($existingIndent);

            $totalAmount = 0;
            $totaldiscount = 0;
            $totalIndentAmount = 0;
            $hasValidItems = false;

            foreach ($quantities as $skuId => $qty) {
                $qty = (float) $qty;
                if ($qty <= 0) continue;
                $hasValidItems = true;

                $price = isset($prices[$skuId]) ? (float) $prices[$skuId] : 0;

                $sku = $this->Inventory_sku->find('first', [
                    'conditions' => ['Inventory_sku.id' => $skuId],
                    'recursive' => -1
                ]);

                if (empty($sku)) continue;

                $product_taxes = $this->Inventory_sku_tax->query("select SUM(tax_percentage) as total_tax from inventory_sku_tax where product_id='$skuId' and status=1");
                $total_tax = round($product_taxes[0][0]['total_tax'] ?? 0, 2);

                $indent_tax_value = round(($price * $qty) * ($total_tax / 100), 2);
                $indent_sku_amount = round(($price * $qty) + $indent_tax_value, 2);
                $indent_landed_amt = round($price * $qty, 2);

                $exciseDutyPerUnit     = floatval($excise_duties[$skuId] ?? 0);
                $addlExciseDutyPerUnit = floatval($addl_excises[$skuId]  ?? 0);

                $indentItemData = [
                    'Inventory_internal_indent_items' => [
                        'indent_id' => $autoIndentId,
                        'sku_id' => $skuId,
                        'sku_name' => $sku['Inventory_sku']['name'],
                        'sku_brand' => $sku['Inventory_sku']['brand'],
                        'sku_unit' => $sku['Inventory_sku']['unit'],
                        'sku_unit_name' => $sku['Inventory_sku']['unit_name'],
                        'sku_price' => $price,
                        'sku_quantity' => $qty,
                        'completed_quantity' => $qty,
                        'is_completed' => 1,
                        'sku_total_tax_percentage' => $total_tax,
                        'sku_total_tax_value' => $indent_tax_value,
                        'excise_per_unit' => $exciseDutyPerUnit,
                        'addl_excise_per_unit' => $addlExciseDutyPerUnit,
                        'sku_amount' => $indent_sku_amount,
                        'landed_amt' => $indent_landed_amt,
                        'status' => 1,
                        'sync' => 0,
                        'local_sync' => 0
                    ]
                ];
                $this->Inventory_internal_indent_items->create();
                $this->Inventory_internal_indent_items->save($indentItemData);

                $totalIndentAmount += $indent_sku_amount;

                $baseAmount = $price * $qty;
                $itemDiscountType = $discount_types[$skuId] ?? 'flat';
                $discountInput = isset($sku_discounts[$skuId]) ? (float)$sku_discounts[$skuId] : 0;

                if ($itemDiscountType === 'percentage') {
                    if ($discountInput > 100) $discountInput = 100;
                    $itemDiscountValue = round(($baseAmount * $discountInput) / 100, 2);
                } else {
                    if ($discountInput > $baseAmount) $discountInput = $baseAmount;
                    $itemDiscountValue = $discountInput;
                }

                $amountAfterDiscount = $baseAmount - $itemDiscountValue;

                $vatPct = floatval($vat_duties[$skuId] ?? 0);
                $tcsPct = floatval($tcs_duties[$skuId] ?? 0);

                $exciseDutyAmt     = round($exciseDutyPerUnit     * $qty, 2);
                $addlExciseDutyAmt = round($addlExciseDutyPerUnit * $qty, 2);

                $vatBase   = $amountAfterDiscount + $exciseDutyAmt + $addlExciseDutyAmt;
                $vatAmt    = round($vatBase * ($vatPct / 100), 2);

                $tcsAmt    = round(($amountAfterDiscount + $exciseDutyAmt + $vatAmt) * ($tcsPct / 100), 2);

                $landedAmt = round($amountAfterDiscount + $exciseDutyAmt + $vatAmt, 2);

                $effective_tax = ($exciseDutyPerUnit > 0 || $vatPct > 0) ? 0 : $total_tax;
                $tax_value = round($landedAmt * ($effective_tax / 100), 2);

                $skuamountFinal = round($landedAmt + $tax_value + $tcsAmt, 2);

                $approved_quantity = ($postatus == 2) ? $qty : 0;
                $approved_total_tax_value = ($postatus == 2) ? $tax_value : 0;
                $approved_amount = ($postatus == 2) ? $skuamountFinal : 0;

                $itemData = [
                    'Inventory_purchase_order_item' => [
                        'p_o_id' => $poId,
                        'internal_indent_id' => $autoIndentId,
                        'sku_id' => $skuId,
                        'sku_name' => $sku['Inventory_sku']['name'],
                        'sku_unit' => $sku['Inventory_sku']['unit'],
                        'sku_unit_name' => $sku['Inventory_sku']['unit_name'],
                        'sku_price' => $price,
                        'sku_quantity' => $qty,
                        'sku_total_tax_percentage' => $total_tax,
                        'sku_total_tax_value' => $tax_value,
                        'excise_duty_amt'      => $exciseDutyAmt,
                        'addl_excise_duty_amt' => $addlExciseDutyAmt,
                        'vat_percentage'       => $vatPct,
                        'vat_amt'              => $vatAmt,
                        'tcs_percentage'       => $tcsPct,
                        'tcs_amt'              => $tcsAmt,
                        'excise_per_unit'      => $exciseDutyPerUnit,
                        'addl_excise_per_unit' => $addlExciseDutyPerUnit,
                        'landed_amt'           => $landedAmt,
                        'sku_discount_type'    => $itemDiscountType,
                        'sku_discount'         => $discountInput,
                        'sku_discount_value'   => $itemDiscountValue,
                        'sku_amount'           => $skuamountFinal,
                        'approved_quantity'    => $approved_quantity,
                        'approved_total_tax_value' => $approved_total_tax_value,
                        'approved_amount'      => $approved_amount,
                        'status'               => $postatus,
                        'sync'                 => 0,
                        'local_sync'           => 0
                    ]
                ];

                $this->Inventory_purchase_order_item->create();
                $this->Inventory_purchase_order_item->save($itemData);

                $totalAmount += $skuamountFinal;
                $totaldiscount += $itemDiscountValue;
            }

            if (!$hasValidItems) {
                $this->Flash->error(__('Please enter quantity for at least one item.'));
                return;
            }

            if ($totalAmount > $approval_amount) {
                $postatus = 1;
            }

            if ($postatus == 2) {
                $approved_by_id = $id;
                $approved_by_name = $name;
                $approved_on = $today;
                $approved_amount = $totalAmount + $charge;
            } else {
                $approved_by_id = 0;
                $approved_by_name = '';
                $approved_on = NULL;
                $approved_amount = 0;
            }

            $indentMasterData = [
                'Inventory_internal_indent' => [
                    'indent_id' => $autoIndentId,
                    'created_by' => $id,
                    'created_by_name' => $name,
                    'total_amount' => $totalIndentAmount,
                    'location_id' => $locationId,
                    'location_name' => $locationname,
                    'is_completed' => 1,
                    'po_locked' => 1,
                    'status' => 1,
                    'sync' => 0,
                    'local_sync' => 0
                ]
            ];
            $this->Inventory_internal_indent->create();
            $this->Inventory_internal_indent->save($indentMasterData);

            $poData = [
                'Inventory_purchase_order' => [
                    'p_o_id' => $poId,
                    'vendor_id' => $vendorId,
                    'vendor_name' => $vendor['Inventory_vendor']['company'],
                    'internal_indent_id' => $autoIndentId,
                    'location_id' => $locationId,
                    'location_name' => $locationname,
                    'location_type' => $location_type,
                    'charge' => $charge,
                    'total_amount' => $totalAmount + $charge,
                    'status' => $postatus,
                    'approved_by_id' => $approved_by_id,
                    'approved_by_name' => "$approved_by_name",
                    'approved_on' => $approved_on,
                    'approved_amount' => $approved_amount,
                    'sync' => 0,
                    'local_sync' => 0,
                    'discount_value' => $totaldiscount,
                    'is_autogenerated_indent' => 1,
                    'po_date' => $po_date
                ]
            ];

            $this->Inventory_purchase_order->create();
            if ($this->Inventory_purchase_order->save($poData)) {
                $this->Flash->success(__('PO has been successfully created with auto-generated internal indent #' . $autoIndentId));
                return $this->redirect(['action' => 'po_list']);
            } else {
                $this->Flash->error(__('Failed to save PO. Please try again.'));
            }
        }
    }

    public function fetch_vendors_for_po_without_indent()
    {
        $this->autoRender = false;
        $this->layout = null;

        $locationId = (int)$this->request->data('location_id');
        if (!$locationId) {
            echo json_encode([]);
            return;
        }

        $locationdetail = $this->Location->find('first', [
            'conditions' => ['Location.id' => $locationId]
        ]);

        if (!empty($locationdetail) && $locationdetail['Location']['type'] == 'Outlet') {
            $vendors = $this->Inventory_vendor->query("
                SELECT DISTINCT v.id, v.company
                FROM inventory_vendors v
                INNER JOIN inventory_vendor_service_areas vsa
                    ON vsa.vendor_id = v.id
                WHERE vsa.area_id = $locationId
                  AND v.status = 1
                  AND vsa.status = 1
                ORDER BY v.company ASC
            ");

            if (empty($vendors)) {
                $vendors = $this->Inventory_vendor->query("
                    SELECT DISTINCT v.id, v.company
                    FROM inventory_vendors v
                    WHERE v.status = 1
                    ORDER BY v.company ASC
                ");
            }
        } else {
            $vendors = $this->Inventory_vendor->query("
                SELECT DISTINCT v.id, v.company
                FROM inventory_vendors v
                WHERE v.status = 1
                ORDER BY v.company ASC
            ");
        }

        $vendorList = [];
        foreach ($vendors as $row) {
            $vendorList[$row['v']['id']] = $row['v']['company'];
        }

        echo json_encode($vendorList);
    }

    public function fetch_vendor_assigned_items()
    {
        $this->autoRender = false;
        $this->layout = null;

        $vendorId   = (int)$this->request->data('vendor_id');
        $locationId = intval($this->request->data('location_id') ?? 0);

        if (!$vendorId) {
            echo '';
            return;
        }

        $vendor_items = $this->Inventory_vendor_sku_served->query("
            SELECT
                v.skus_id,
                v.sku_name,
                v.sku_brand,
                v.sku_unit_name,
                v.sku_price,
                tax.total_tax_percent AS tax_percent,
                COALESCE(ex_loc.excise_per_unit,      ex_base.excise_per_unit,      0) AS excise_pu,
                COALESCE(ex_loc.addl_excise_per_unit, ex_base.addl_excise_per_unit, 0) AS addl_excise_pu,
                COALESCE(ex_loc.vat_percentage,       ex_base.vat_percentage,       0) AS vat_pct,
                COALESCE(ex_loc.tcs_percentage,       ex_base.tcs_percentage,       0) AS tcs_pct,
                sk.excise_applicable,
                sk.has_variable_tax,
                sk.name,
                sk.brand,
                sk.unit_name
            FROM inventory_vendor_sku_served v
            JOIN inventory_skus sk ON sk.id = v.skus_id AND sk.status = 1
            LEFT JOIN (
                SELECT product_id, SUM(tax_percentage) AS total_tax_percent
                FROM inventory_sku_tax WHERE status = 1 GROUP BY product_id
            ) tax ON tax.product_id = v.skus_id
            LEFT JOIN inventory_sku_excise ex_loc
                ON ex_loc.sku_id = v.skus_id AND ex_loc.location_id = $locationId AND ex_loc.status = 1
            LEFT JOIN inventory_sku_excise ex_base
                ON ex_base.sku_id = v.skus_id AND ex_base.location_id = 0 AND ex_base.status = 1
            WHERE
                v.vendor_id = $vendorId
                AND v.status = 1
            ORDER BY sk.name ASC
        ");

        $vendorOptions = '';
        $x = 1;
        foreach ($vendor_items as $vendor_item) {
            $skuId        = $vendor_item['v']['skus_id'];
            $skuName      = !empty($vendor_item['sk']['name']) ? $vendor_item['sk']['name'] : $vendor_item['v']['sku_name'];
            $skuBrand     = !empty($vendor_item['sk']['brand']) ? $vendor_item['sk']['brand'] : $vendor_item['v']['sku_brand'];
            $skuUnitName  = !empty($vendor_item['sk']['unit_name']) ? $vendor_item['sk']['unit_name'] : $vendor_item['v']['sku_unit_name'];
            $price        = $vendor_item['v']['sku_price'];
            $excisePu     = floatval($vendor_item[0]['excise_pu']      ?? 0);
            $addlExcisePu = floatval($vendor_item[0]['addl_excise_pu'] ?? 0);
            $vatPct       = floatval($vendor_item[0]['vat_pct']        ?? 0);
            $tcsPct       = floatval($vendor_item[0]['tcs_pct']        ?? 0);
            $exciseApp    = !empty($vendor_item['sk']['excise_applicable']);
            $varTax       = !empty($vendor_item['sk']['has_variable_tax']);
            $displayTaxPct = $varTax ? 0 : floatval($vendor_item['tax']['tax_percent'] ?? $vendor_item[0]['tax_percent'] ?? 0);

            $vendorOptions .= "
            <tr>
                <td>".$x."</td>
                <td>".h($skuName)."</td>
                <td>".h($skuBrand)."</td>
                <td>".h($skuUnitName)."</td>
                <td><input type='number' name='prices[$skuId]' value='".$price."' class='form-control' step='any' /></td>
                <td>$displayTaxPct %</td>
                <td><input type='number' name='quantity[$skuId]' value='' class='form-control vendor-qty' step='any' min='0' placeholder='0' /></td>
                <td>
                    <input type='number' name='excise_duty[$skuId]' value='".$excisePu."'
                           class='form-control excise-input' step='any' min='0' style='width:80px'
                           placeholder='Excise' />
                </td>
                <td>
                    <input type='number' name='addl_excise[$skuId]' value='".$addlExcisePu."'
                           class='form-control addl-excise-input' step='any' min='0' style='width:80px'
                           placeholder='Addl.' />
                </td>
                <td>
                    <input type='number' name='vat_duty[$skuId]' value='".$vatPct."'
                           class='form-control vat-input' step='any' min='0' max='100' style='width:70px'
                           placeholder='VAT%'/>
                </td>
                <td>
                    <input type='number' name='tcs_duty[$skuId]' value='".$tcsPct."'
                           class='form-control tcs-input' step='any' min='0' max='100' style='width:70px'
                           placeholder='TCS%'/>
                </td>
                <td style='display:flex; gap:5px;'>
                    <select name='discount_type[$skuId]' class='form-control discount-type'
                            data-id='$skuId' style='width:150px'>
                        <option value='flat'>Flat</option>
                        <option value='percentage'>Percentage</option>
                    </select>
                    <input type='number' name='sku_discount[$skuId]' value='0'
                           class='form-control sku-discount' step='any' min='0'
                           placeholder='Amount' id='discount_$skuId' style='width:90px' />
                </td>
            </tr>";
            $x++;
        }
        echo $vendorOptions;
    }

    public function fetch_indents_by_location()
    {
        $this->autoRender = false;
        $this->layout = null;

        if ($this->request->is('ajax')) {
            $locationId = (int)$this->request->data('location_id');
            $this->loadModel('Inventory_internal_indent');

            $indents = $this->Inventory_internal_indent->find('list', [
                'conditions' => [
                    'location_id' => $locationId,
                    'status' => 1,
                    'is_completed' => 0,
                    'po_locked' => 0
                ],
                'fields' => ['indent_id', 'indent_id'],
                'order' => ['id' => 'DESC'],
                'recursive' => -1
            ]);

            echo json_encode($indents);
        }
    }

    public function fetch_vendors_by_indent()
    {
        $this->autoRender = false;
        $this->layout = null;

        $indentId = (int)$this->request->data('indent_id');
        $location_id = $this->request->data('location_id');

        if (!$indentId) {
            echo json_encode([]);
            return;
        }

        $skus = $this->Inventory_internal_indent_items->find('list', [
            'conditions' => [
                'indent_id' => $indentId,
                'status' => 1,
                'is_completed' => 0
            ],
            'fields' => ['sku_id', 'sku_id'],
            'recursive' => -1
        ]);

        if (empty($skus)) {
            echo json_encode([]);
            return;
        }

        $locationdetail = $this->Location->find('first', [
            'conditions' => ['Location.id' => $location_id]
        ]);
        if($locationdetail['Location']['type']=='Outlet'){

            $vendors = $this->Inventory_vendor->query("
                SELECT DISTINCT v.id, v.company
                FROM inventory_vendors v
                INNER JOIN inventory_vendor_sku_served vs
                    ON vs.vendor_id = v.id
                INNER JOIN inventory_vendor_service_areas vsa
                    ON vsa.vendor_id = v.id
                WHERE
                    vs.skus_id IN (" . implode(',', array_keys($skus)) . ")
                    and vsa.area_id = $location_id
                    AND vs.status = 1
                    AND v.status = 1
                ORDER BY v.company ASC
            ");
        }else{
            $vendors = $this->Inventory_vendor->query("
                SELECT DISTINCT v.id, v.company
                FROM inventory_vendors v
                INNER JOIN inventory_vendor_sku_served vs
                    ON vs.vendor_id = v.id
                WHERE
                    vs.skus_id IN (" . implode(',', array_keys($skus)) . ")
                    AND vs.status = 1
                    AND v.status = 1
                ORDER BY v.company ASC
            ");
        }

        $vendorList = [];
        foreach ($vendors as $row) {
            $vendorList[$row['v']['id']] = $row['v']['company'];
        }

        echo json_encode($vendorList);
    }

    public function fetch_indent_items()
    {
        $this->autoRender = false;
        $indentId = $this->request->data('indent_id');

        $indent_items=$this->Inventory_internal_indent_items->query("SELECT * FROM `inventory_internal_indent_items`  where indent_id=$indentId  and status=1 and is_completed=0");

        $x=1;
        foreach ($indent_items as $indent_item) {

            $qty=$indent_item['inventory_internal_indent_items']['sku_quantity']-$indent_item['inventory_internal_indent_items']['completed_quantity'];

            $vendorOptions .= "
            <tr>
                <td>".$x."</td>
                <td>".$indent_item['inventory_internal_indent_items']['sku_name']."</td>
                <td>".$indent_item['inventory_internal_indent_items']['sku_brand']."</td>
                <td>".$indent_item['inventory_internal_indent_items']['sku_unit_name']."</td>
                <td>".$indent_item['inventory_internal_indent_items']['sku_price']."</td>
                <td>".($indent_item['inventory_internal_indent_items']['sku_total_tax_percentage'] ?? 0)." %</td>
                <td>".$qty."</td>
            </tr>";
            $x++;
        }
        echo $vendorOptions;
    }

    public function fetch_vendor_items()
    {
        $this->autoRender = false;
        $vendorId   = $this->request->data('vendor_id');
        $indentId   = $this->request->data('indent_id');
        $locationId = intval($this->request->data('location_id') ?? 0);

        $vendor_items = $this->Inventory_internal_indent_items->query("
            SELECT
                i.*,
                v.sku_price AS price,
                tax.total_tax_percent AS tax_percent,
                COALESCE(ex_loc.excise_per_unit,      ex_base.excise_per_unit,      0) AS excise_pu,
                COALESCE(ex_loc.addl_excise_per_unit, ex_base.addl_excise_per_unit, 0) AS addl_excise_pu,
                COALESCE(ex_loc.vat_percentage,       ex_base.vat_percentage,       0) AS vat_pct,
                COALESCE(ex_loc.tcs_percentage,       ex_base.tcs_percentage,       0) AS tcs_pct,
                sk.excise_applicable,
                sk.has_variable_tax
            FROM inventory_internal_indent_items i

            JOIN inventory_vendor_sku_served v
                ON i.sku_id = v.skus_id
                AND v.vendor_id = ".$vendorId."
                AND v.status = 1

            LEFT JOIN (
                SELECT product_id, SUM(tax_percentage) AS total_tax_percent
                FROM inventory_sku_tax WHERE status = 1 GROUP BY product_id
            ) tax ON tax.product_id = i.sku_id
            LEFT JOIN inventory_skus sk ON sk.id = i.sku_id
            LEFT JOIN inventory_sku_excise ex_loc
                ON ex_loc.sku_id = i.sku_id AND ex_loc.location_id = $locationId AND ex_loc.status = 1
            LEFT JOIN inventory_sku_excise ex_base
                ON ex_base.sku_id = i.sku_id AND ex_base.location_id = 0 AND ex_base.status = 1
            WHERE
                i.indent_id = '".$indentId."'
                AND i.is_completed = 0
                AND i.status = 1
        ");

        $x = 1;
        foreach ($vendor_items as $vendor_item) {
            $quantity     = $vendor_item['i']['sku_quantity']
                          ? $vendor_item['i']['sku_quantity'] - $vendor_item['i']['completed_quantity']
                          : 0;
            $excisePu     = floatval($vendor_item[0]['excise_pu']      ?? 0);
            $addlExcisePu = floatval($vendor_item[0]['addl_excise_pu'] ?? 0);
            $vatPct       = floatval($vendor_item[0]['vat_pct']        ?? 0);
            $tcsPct       = floatval($vendor_item[0]['tcs_pct']        ?? 0);
            $skuId        = $vendor_item['i']['sku_id'];
            $exciseApp    = !empty($vendor_item['sk']['excise_applicable']);
            $varTax       = !empty($vendor_item['sk']['has_variable_tax']);

            $displayTaxPct = $varTax ? 0 : floatval($vendor_item['tax']['tax_percent'] ?? 0);

            $vendorOptions .= "
            <tr>
                <td>".$x."</td>
                <td>".$vendor_item['i']['sku_name']."</td>
                <td>".$vendor_item['i']['sku_brand']."</td>
                <td>".$vendor_item['i']['sku_unit_name']."</td>
                <td><input type='number' name='prices[$skuId]' value='".$vendor_item['v']['price']."' class='form-control' step='any' /></td>
                <td>$displayTaxPct %</td>
                <td><input type='number' name='quantity[$skuId]' value='".$quantity."' class='form-control vendor-qty' step='any' max='".$quantity."' data-max='".$quantity."' /></td>
                <td>
                    <input type='number' name='excise_duty[$skuId]' value='".$excisePu."'
                           class='form-control excise-input' step='any' min='0' style='width:80px'
                           placeholder='Excise' />
                </td>
                <td>
                    <input type='number' name='addl_excise[$skuId]' value='".$addlExcisePu."'
                           class='form-control addl-excise-input' step='any' min='0' style='width:80px'
                           placeholder='Addl.' />
                </td>
                <td>
                    <input type='number' name='vat_duty[$skuId]' value='".$vatPct."'
                           class='form-control vat-input' step='any' min='0' max='100' style='width:70px'
                           placeholder='VAT%'/>
                </td>
                <td>
                    <input type='number' name='tcs_duty[$skuId]' value='".$tcsPct."'
                           class='form-control tcs-input' step='any' min='0' max='100' style='width:70px'
                           placeholder='TCS%'/>
                </td>
                <td style='display:flex; gap:5px;'>
                    <select name='discount_type[$skuId]' class='form-control discount-type'
                            data-id='$skuId' style='width:150px'>
                        <option value='flat'>Flat</option>
                        <option value='percentage'>Percentage</option>
                    </select>
                    <input type='number' name='sku_discount[$skuId]' value='0'
                           class='form-control sku-discount' step='any' min='0'
                           placeholder='Amount' id='discount_$skuId' style='width:90px' />
                </td>
            </tr>";
            $x++;
        }
        echo $vendorOptions;
    }

    public function view_po()
    {
        if(!$this->Session->check('Auth.User.id')){
            $this->redirect(array('controller'=>'dashboard','action' => 'login'));
        }

        $this->layout = "users";
        $id=$_REQUEST['id'];

        $id_safe = addslashes($id);
        $indents = $this->Inventory_purchase_order->query("SELECT * FROM inventory_purchase_order where p_o_id='$id_safe' and status!=0");
        $this->set(compact('indents'));

        $items = $this->Inventory_purchase_order_item->query("SELECT * FROM inventory_purchase_order_items where p_o_id='$id_safe' and status!=0 and sku_quantity!=0 order by sku_name asc");

        $this->set(compact('items'));

        $stockinData = [];

        if ($indents[0]['inventory_purchase_order']['status'] == 4) {

            $stockins = $this->Inventory_stockin->find('all', [
                'conditions' => [
                    'p_o_id' => $id,
                    'status !=' => 0
                ],
                'order' => ['id ASC']
            ]);

            foreach ($stockins as $s) {

                $stockin_id = $s['Inventory_stockin']['stockin_id'];

                $stockin_items = $this->Inventory_stockin_item->find('all', [
                    'conditions' => [
                        'stockin_id' => $stockin_id,
                        'status !=' => 0
                    ],
                    'order' => ['sku_name ASC']
                ]);

                $stockinData[] = [
                    'stockin' => $s['Inventory_stockin'],
                    'items'   => $stockin_items
                ];
            }
        }

        $this->set(compact('stockinData'));
    }

    public function edit_po()
    {
        if(!$this->Session->check('Auth.User.id')){
            $this->redirect(array('controller'=>'dashboard','action' => 'login'));
        }
        $authUser = $this->Session->read('Auth.User');
        $userRole = $authUser['role'] ?? '';
        $userLocationId = $authUser['location_id'] ?? 0;
        $userid = $authUser['id'];
        $username = $authUser['name'];

        $this->layout = "users";
        $id=$_REQUEST['id'];

        $purchase_orders = $this->Inventory_purchase_order->query("SELECT * FROM inventory_purchase_order where p_o_id=$id");

        $internal_indent_id=$purchase_orders[0]['inventory_purchase_order']['internal_indent_id'];
        $vendorId=$purchase_orders[0]['inventory_purchase_order']['vendor_id'];

                $_edit_loc_id = intval($purchase_orders[0]['inventory_purchase_order']['location_id'] ?? 0);
        $is_auto_indent = !empty($purchase_orders[0]['inventory_purchase_order']['is_autogenerated_indent']);

        if ($is_auto_indent) {
            $raw_items = $this->Inventory_internal_indent_items->query("
                SELECT
                    v.skus_id AS sku_id,
                    s.name AS sku_name,
                    s.brand AS sku_brand,
                    s.unit AS sku_unit,
                    s.unit_name AS sku_unit_name,
                    v.sku_price AS vprice,
                    COALESCE(p.sku_price, i.sku_price, v.sku_price) AS psku_price,
                    p.sku_total_tax_value AS psku_total_tax_value,
                    p.sku_amount AS psku_amount,
                    COALESCE(p.sku_quantity, 0) AS psku_quantity,
                    COALESCE(i.sku_quantity, 0) AS sku_quantity,
                    COALESCE(i.completed_quantity, 0) AS completed_quantity,
                    COALESCE(i.is_completed, 0) AS is_completed,
                    p.sku_discount AS psku_discount,
                    p.sku_discount_type AS psku_discount_type,
                    COALESCE(p.excise_per_unit, i.excise_per_unit, eloc.excise_per_unit, ebase.excise_per_unit, 0) AS pexcise_pu,
                    COALESCE(p.addl_excise_per_unit, i.addl_excise_per_unit, eloc.addl_excise_per_unit, ebase.addl_excise_per_unit, 0) AS paddl_excise_pu,
                    COALESCE(p.vat_percentage, eloc.vat_percentage, ebase.vat_percentage, 0) AS pvat_pct,
                    COALESCE(p.tcs_percentage, eloc.tcs_percentage, ebase.tcs_percentage, 0) AS ptcs_pct,
                    p.excise_duty_amt AS pexcise_amt,
                    p.addl_excise_duty_amt AS paddl_excise_amt,
                    p.vat_amt AS pvat_amt,
                    p.tcs_amt AS ptcs_amt,
                    tax.ssku_tax
                FROM inventory_vendor_sku_served v
                JOIN inventory_skus s ON v.skus_id = s.id
                LEFT JOIN inventory_internal_indent_items i
                    ON i.indent_id = '$internal_indent_id'
                    AND i.sku_id = v.skus_id
                LEFT JOIN inventory_purchase_order_items p
                    ON p.p_o_id = '$id'
                    AND p.sku_id = v.skus_id
                    AND p.status = 1
                LEFT JOIN inventory_sku_excise eloc
                    ON eloc.sku_id = v.skus_id
                    AND eloc.location_id = $_edit_loc_id
                    AND eloc.status = 1
                LEFT JOIN inventory_sku_excise ebase
                    ON ebase.sku_id = v.skus_id
                    AND ebase.location_id = 0
                    AND ebase.status = 1
                LEFT JOIN (
                    SELECT product_id, SUM(tax_percentage) AS ssku_tax
                    FROM inventory_sku_tax
                    WHERE status = 1
                    GROUP BY product_id
                ) tax ON tax.product_id = v.skus_id
                WHERE v.vendor_id = $vendorId AND v.status = 1
                ORDER BY s.name ASC
            ");

            $vendor_items = [];
            foreach ($raw_items as $row) {
                $vendor_items[] = [
                    'i' => [
                        'sku_id'             => $row['v']['sku_id'] ?? $row[0]['sku_id'] ?? 0,
                        'sku_name'           => $row['s']['sku_name'] ?? $row[0]['sku_name'] ?? '',
                        'sku_brand'          => $row['s']['sku_brand'] ?? $row[0]['sku_brand'] ?? '',
                        'sku_unit'           => $row['s']['sku_unit'] ?? $row[0]['sku_unit'] ?? '',
                        'sku_unit_name'      => $row['s']['sku_unit_name'] ?? $row[0]['sku_unit_name'] ?? '',
                        'sku_quantity'       => $row['0']['sku_quantity'] ?? 0,
                        'completed_quantity' => $row['0']['completed_quantity'] ?? 0,
                        'is_completed'       => $row['0']['is_completed'] ?? 0,
                        'indent_id'          => $internal_indent_id
                    ],
                    'v' => [
                        'vprice' => $row['v']['vprice'] ?? $row[0]['vprice'] ?? 0
                    ],
                    'p' => [
                        'psku_price'           => $row['0']['psku_price'] ?? 0,
                        'psku_total_tax_value' => $row['p']['psku_total_tax_value'] ?? $row[0]['psku_total_tax_value'] ?? 0,
                        'psku_amount'          => $row['p']['psku_amount'] ?? $row[0]['psku_amount'] ?? 0,
                        'psku_quantity'        => $row['0']['psku_quantity'] ?? 0,
                        'psku_discount'        => $row['p']['psku_discount'] ?? $row[0]['psku_discount'] ?? 0,
                        'psku_discount_type'   => $row['p']['psku_discount_type'] ?? $row[0]['psku_discount_type'] ?? 'flat',
                        'pexcise_pu'           => $row['0']['pexcise_pu'] ?? 0,
                        'paddl_excise_pu'      => $row['0']['paddl_excise_pu'] ?? 0,
                        'pvat_pct'             => $row['0']['pvat_pct'] ?? 0,
                        'ptcs_pct'             => $row['0']['ptcs_pct'] ?? 0,
                        'pexcise_amt'          => $row['p']['pexcise_amt'] ?? $row[0]['pexcise_amt'] ?? 0,
                        'paddl_excise_amt'     => $row['p']['paddl_excise_amt'] ?? $row[0]['paddl_excise_amt'] ?? 0,
                        'pvat_amt'             => $row['p']['pvat_amt'] ?? $row[0]['pvat_amt'] ?? 0,
                        'ptcs_amt'             => $row['p']['ptcs_amt'] ?? $row[0]['ptcs_amt'] ?? 0
                    ],
                    'tax' => [
                        'ssku_tax' => $row['tax']['ssku_tax'] ?? $row[0]['ssku_tax'] ?? 0
                    ]
                ];
            }
        } else {
            $vendor_items = $this->Inventory_internal_indent_items->query("
                SELECT
                    i.*,
                    v.sku_price AS vprice,
                    p.sku_price AS psku_price,
                    p.sku_total_tax_value AS psku_total_tax_value,
                    p.sku_amount AS psku_amount,
                    p.sku_quantity AS psku_quantity,
                    p.sku_discount AS psku_discount,
                    p.sku_discount_type AS psku_discount_type,
                     -- Prioritize exact saved PO item rates, fall back to master sku_excise if item not yet created
                    COALESCE(p.excise_per_unit,      eloc.excise_per_unit,      ebase.excise_per_unit,      0) AS pexcise_pu,
                    COALESCE(p.addl_excise_per_unit, eloc.addl_excise_per_unit, ebase.addl_excise_per_unit, 0) AS paddl_excise_pu,
                    COALESCE(p.vat_percentage,       eloc.vat_percentage,       ebase.vat_percentage,       0) AS pvat_pct,
                    COALESCE(p.tcs_percentage,       eloc.tcs_percentage,       ebase.tcs_percentage,       0) AS ptcs_pct,
                    p.excise_duty_amt AS pexcise_amt,
                    p.addl_excise_duty_amt AS paddl_excise_amt,
                    p.vat_amt AS pvat_amt,
                    p.tcs_amt AS ptcs_amt,
                    tax.ssku_tax
                FROM inventory_internal_indent_items i

                JOIN inventory_vendor_sku_served v
                    ON i.sku_id = v.skus_id
                    AND v.vendor_id = $vendorId
                    AND v.status = 1

                LEFT JOIN inventory_purchase_order_items p
                    ON p.p_o_id = '$id'
                    AND p.internal_indent_id = i.indent_id
                    AND p.sku_id = i.sku_id
                    AND p.status = 1

                LEFT JOIN (
                    SELECT p_o_id, sku_id,
                           MAX(excise_per_unit) AS excise_per_unit,
                           MAX(addl_excise_per_unit) AS addl_excise_per_unit,
                           MAX(vat_percentage) AS vat_percentage,
                           MAX(tcs_percentage) AS tcs_percentage
                    FROM inventory_purchase_order_items
                    WHERE p_o_id = '$id'
                    GROUP BY p_o_id, sku_id
                ) p_any ON p_any.p_o_id = '$id' AND p_any.sku_id = i.sku_id

                LEFT JOIN inventory_sku_excise eloc
                    ON eloc.sku_id = i.sku_id
                    AND eloc.location_id = $_edit_loc_id
                    AND eloc.status = 1

                LEFT JOIN inventory_sku_excise ebase
                    ON ebase.sku_id = i.sku_id
                    AND ebase.location_id = 0
                    AND ebase.status = 1

                LEFT JOIN (
                    SELECT product_id, SUM(tax_percentage) AS ssku_tax
                    FROM inventory_sku_tax
                    WHERE status = 1
                    GROUP BY product_id
                ) tax ON tax.product_id = i.sku_id

                WHERE
                    i.indent_id = '$internal_indent_id'
                    AND i.status = 1

                ORDER BY i.sku_name ASC
            ");
        }

        $this->set(compact('vendor_items'));
        $this->set(compact('purchase_orders'));

        if ($this->request->is(array('post', 'put'))) {
            $today = date('Y-m-d H:i:s');

            $indent_id = $this->data['User']['indent'];
            $p_o_id = $this->data['User']['p_o_id'];
            $prices=$this->data['prices'];
            $quantities=$this->data['quantity'];
            $charge = $this->data['User']['charge'] ?? 0;
            $discounts = $this->data['discount'] ?? [];
            $discount_types = $this->data['discount_type'] ?? [];
            $excise_duties = $this->data['excise_duty'] ?? $_POST['excise_duty'] ?? [];
            $addl_excises  = $this->data['addl_excise'] ?? $_POST['addl_excise'] ?? [];
            $vat_duties    = $this->data['vat_duty']    ?? $_POST['vat_duty']    ?? [];
            $tcs_duties    = $this->data['tcs_duty']    ?? $_POST['tcs_duty']    ?? [];
            $po_date = !empty($_POST['po_date']) ? $_POST['po_date'] : date('Y-m-d');

            $amount=0;
            $productamount=0;
            $total_discount=0;

            $this->Inventory_purchase_order_item->query("UPDATE inventory_purchase_order_items set status=0,sync=0,local_sync=0 WHERE p_o_id =". $p_o_id);

            $vendor = $this->Inventory_vendor->find('first', [
                'conditions' => ['Inventory_vendor.id' => $vendorId],
                'recursive' => -1
            ]);

            if($vendor['Inventory_vendor']['po_approval_required']){
                $postatus = 1;
            }else{
                $postatus = 2;
            }

            foreach ($quantities as $sku_id => $quantity) {
                if ($quantity != 0) {
                    $price = isset($prices[$sku_id]) ? $prices[$sku_id] : 0;
                    $discount = isset($discounts[$sku_id]) ? $discounts[$sku_id] : 0;
                    $discount_type = isset($discount_types[$sku_id]) ? $discount_types[$sku_id] : 'flat';

                    $sku_details = $this->Inventory_sku->query("SELECT * FROM inventory_skus where id=$sku_id");

                    $product_taxes=$this->Inventory_sku_tax->query("select SUM(tax_percentage) as total_tax from inventory_sku_tax where product_id='$sku_id' and status =1 ");
                    $total_tax=round($product_taxes['0']['0']['total_tax'],2);

                    $base_amount = $price * $quantity;

                    if ($discount_type == 'percentage') {

                        if ($discount > 100) {
                            $discount = 100;
                        }

                        $discount_value = round(($base_amount * $discount) / 100,2);

                    } else {

                        if ($discount > $base_amount) {
                            $discount = $base_amount;
                        }

                        $discount_value = round($discount,2);
                    }

                    $after_discount = $base_amount - $discount_value;

                    if (isset($excise_duties[$sku_id]) || isset($vat_duties[$sku_id])) {
                        $_excise_pu   = floatval($excise_duties[$sku_id] ?? 0);
                        $_addl_exc_pu = floatval($addl_excises[$sku_id]  ?? 0);
                        $_vat_pct     = floatval($vat_duties[$sku_id]    ?? 0);
                        $_tcs_pct     = floatval($tcs_duties[$sku_id]    ?? 0);
                    } else {
                        $_excise_pu   = 0;
                        $_addl_exc_pu = 0;
                        $_vat_pct     = 0;
                        $_tcs_pct     = 0;

                        $_existing_poi = $this->Inventory_purchase_order_item->query(
                            "SELECT * FROM inventory_purchase_order_items WHERE status!=0 and p_o_id = '$p_o_id' AND sku_id = '$sku_id' AND (excise_per_unit > 0 OR vat_percentage > 0) ORDER BY id DESC LIMIT 1"
                        );
                        if (!empty($_existing_poi)) {
                            $_ep_row = $_existing_poi[0]['inventory_purchase_order_items'] ?? $_existing_poi[0][0] ?? [];
                            $_excise_pu   = floatval($_ep_row['excise_per_unit']      ?? 0);
                            $_addl_exc_pu = floatval($_ep_row['addl_excise_per_unit'] ?? 0);
                            $_vat_pct     = floatval($_ep_row['vat_percentage']       ?? 0);
                            $_tcs_pct     = floatval($_ep_row['tcs_percentage']       ?? 0);
                        }

                        if ($_excise_pu == 0 && $_vat_pct == 0) {
                            $_po_loc_id = intval($purchase_orders[0]['inventory_purchase_order']['location_id'] ?? 0);
                            $_exc_rows = $this->Inventory_purchase_order_item->query(
                                "SELECT * FROM inventory_sku_excise WHERE sku_id = $sku_id AND status = 1"
                            );
                            $_matched_row = null;
                            if (!empty($_exc_rows)) {
                                foreach ($_exc_rows as $_erow) {
                                    $_row_data = $_erow['inventory_sku_excise'] ?? $_erow[0] ?? [];
                                    if (intval($_row_data['location_id'] ?? -1) === $_po_loc_id) {
                                        $_matched_row = $_row_data;
                                        break;
                                    }
                                    if (intval($_row_data['location_id'] ?? -1) === 0) {
                                        $_matched_row = $_row_data;
                                    }
                                }
                            }
                            if ($_matched_row) {
                                $_excise_pu   = floatval($_matched_row['excise_per_unit']      ?? 0);
                                $_addl_exc_pu = floatval($_matched_row['addl_excise_per_unit'] ?? 0);
                                $_vat_pct     = floatval($_matched_row['vat_percentage']       ?? 0);
                                $_tcs_pct     = floatval($_matched_row['tcs_percentage']       ?? 0);
                            }
                        }
                    }

                    $_excise_amt     = round($_excise_pu   * $quantity, 2);
                    $_addl_excise_amt= round($_addl_exc_pu * $quantity, 2);
                    $_vat_base       = $after_discount + $_excise_amt + $_addl_excise_amt;
                    $_vat_amt        = round($_vat_base * ($_vat_pct / 100), 2);
                    $_landed_amt     = round($after_discount + $_excise_amt + $_vat_amt, 2);
                    $_tcs_base       = $_landed_amt;
                    $_tcs_amt        = round($_tcs_base * ($_tcs_pct / 100), 2);

                    $_eff_gst  = ($_excise_pu > 0 || $_vat_pct > 0) ? 0 : $total_tax;
                    $tax_value = round($_landed_amt * ($_eff_gst / 100), 2);

                    $skuamount = round($_landed_amt + $tax_value + $_tcs_amt, 2);

                    $data['Inventory_purchase_order_item']=array(
                        'p_o_id'=>$p_o_id,
                        'internal_indent_id'=>$indent_id,
                        'sku_id'=>$sku_id,
                        'sku_name'=>$sku_details[0]['inventory_skus']['name'],
                        'sku_unit'=>$sku_details[0]['inventory_skus']['unit'],
                        'sku_unit_name'=>$sku_details[0]['inventory_skus']['unit_name'],
                        'sku_price'=>$price,
                        'sku_quantity'=>$quantity,
                        'sku_total_tax_percentage' => $_eff_gst,
                        'sku_total_tax_value' => $tax_value,
                        'excise_per_unit'      => $_excise_pu,
                        'addl_excise_per_unit' => $_addl_exc_pu,
                        'excise_duty_amt'      => $_excise_amt,
                        'addl_excise_duty_amt' => $_addl_excise_amt,
                        'vat_percentage'       => $_vat_pct,
                        'vat_amt'              => $_vat_amt,
                        'tcs_percentage'       => $_tcs_pct,
                        'tcs_amt'              => $_tcs_amt,
                        'landed_amt'           => $_landed_amt,
                        'sku_amount'           => $skuamount,
                        'sync'                 => 0,
                        'local_sync'           => 0,
                        'sku_discount'         => $discount,
                        'sku_discount_type'    => $discount_type,
                        'sku_discount_value'   => $discount_value,
                    );
                    if($this->Inventory_purchase_order_item->saveAll($data['Inventory_purchase_order_item']))
                    {

                    }else{
                        $this->Flash->error(__('PO Items could not be saved. Please, try again.'));
                    }

                    $amount=$amount+$skuamount;
                    $total_discount = $total_discount + $discount_value;
                }
            }
            $approval_amount=approval_amount;
            if($amount>$approval_amount){
                $postatus=1;
            }

            if($postatus==2){
                $approved_by_id=$userid;
                $approved_by_name=$username;
                $approved_on=$today;
                $approved_amount=$amount+$charge;
            }else{
                $approved_by_id=0;
                $approved_by_name='';
                $approved_on=NULL;
                $approved_amount=0;
            }

            $total_amount = $amount + $charge;

            $this->Inventory_purchase_order->query("
                UPDATE inventory_purchase_order
                SET
                    status = '$postatus',
                    `charge` = '$charge',
                    total_amount = '$total_amount',
                    discount_value = '$total_discount',
                    po_date = '$po_date',
                    is_updated = 1,
                    sync = 0,
                    local_sync = 0
                WHERE status != 0
                  AND p_o_id = '$p_o_id'
            ");

            $this->Inventory_purchase_order_item->query("UPDATE inventory_purchase_order_items SET status='$postatus',sync = 0,local_sync=0 WHERE  status!=0 and p_o_id =". $p_o_id);

            $poMasterRecord = $this->Inventory_purchase_order->find('first', [
                'conditions' => ['Inventory_purchase_order.p_o_id' => $p_o_id],
                'fields' => ['Inventory_purchase_order.is_autogenerated_indent'],
                'recursive' => -1
            ]);
            $isAutoIndentPo = !empty($poMasterRecord['Inventory_purchase_order']['is_autogenerated_indent']);

            if ($isAutoIndentPo) {

                $totalIndentAmount = 0;

                foreach ($quantities as $sku_id => $qty) {
                    $qty = (float)$qty;
                    $price = isset($prices[$sku_id]) ? (float)$prices[$sku_id] : 0;

                    $existingIndentItem = $this->Inventory_internal_indent_items->find('first', [
                        'conditions' => [
                            'Inventory_internal_indent_items.indent_id' => $indent_id,
                            'Inventory_internal_indent_items.sku_id' => $sku_id
                        ],
                        'recursive' => -1
                    ]);

                    if ($qty > 0) {
                        $product_taxes = $this->Inventory_sku_tax->query("select SUM(tax_percentage) as total_tax from inventory_sku_tax where product_id='$sku_id' and status=1");
                        $total_tax = round($product_taxes[0][0]['total_tax'] ?? 0, 2);

                        $indent_tax_value = round(($price * $qty) * ($total_tax / 100), 2);
                        $indent_sku_amount = round(($price * $qty) + $indent_tax_value, 2);
                        $indent_landed_amt = round($price * $qty, 2);

                        $exciseDutyPerUnit     = floatval($excise_duties[$sku_id] ?? 0);
                        $addlExciseDutyPerUnit = floatval($addl_excises[$sku_id]  ?? 0);

                        if (!empty($existingIndentItem)) {
                            $this->Inventory_internal_indent_items->updateAll(
                                [
                                    'Inventory_internal_indent_items.sku_price' => $price,
                                    'Inventory_internal_indent_items.sku_quantity' => $qty,
                                    'Inventory_internal_indent_items.completed_quantity' => $qty,
                                    'Inventory_internal_indent_items.is_completed' => 1,
                                    'Inventory_internal_indent_items.sku_total_tax_percentage' => $total_tax,
                                    'Inventory_internal_indent_items.sku_total_tax_value' => $indent_tax_value,
                                    'Inventory_internal_indent_items.excise_per_unit' => $exciseDutyPerUnit,
                                    'Inventory_internal_indent_items.addl_excise_per_unit' => $addlExciseDutyPerUnit,
                                    'Inventory_internal_indent_items.sku_amount' => $indent_sku_amount,
                                    'Inventory_internal_indent_items.landed_amt' => $indent_landed_amt,
                                    'Inventory_internal_indent_items.status' => 1,
                                    'Inventory_internal_indent_items.sync' => 0,
                                    'Inventory_internal_indent_items.local_sync' => 0
                                ],
                                [
                                    'Inventory_internal_indent_items.Id' => $existingIndentItem['Inventory_internal_indent_items']['Id']
                                ]
                            );
                        } else {
                            $sku_details = $this->Inventory_sku->find('first', [
                                'conditions' => ['Inventory_sku.id' => $sku_id],
                                'recursive' => -1
                            ]);
                            $this->Inventory_internal_indent_items->create();
                            $this->Inventory_internal_indent_items->save([
                                'Inventory_internal_indent_items' => [
                                    'indent_id' => $indent_id,
                                    'sku_id' => $sku_id,
                                    'sku_name' => $sku_details['Inventory_sku']['name'] ?? '',
                                    'sku_brand' => $sku_details['Inventory_sku']['brand'] ?? '',
                                    'sku_unit' => $sku_details['Inventory_sku']['unit'] ?? '',
                                    'sku_unit_name' => $sku_details['Inventory_sku']['unit_name'] ?? '',
                                    'sku_price' => $price,
                                    'sku_quantity' => $qty,
                                    'completed_quantity' => $qty,
                                    'is_completed' => 1,
                                    'sku_total_tax_percentage' => $total_tax,
                                    'sku_total_tax_value' => $indent_tax_value,
                                    'excise_per_unit' => $exciseDutyPerUnit,
                                    'addl_excise_per_unit' => $addlExciseDutyPerUnit,
                                    'sku_amount' => $indent_sku_amount,
                                    'landed_amt' => $indent_landed_amt,
                                    'status' => 1,
                                    'sync' => 0,
                                    'local_sync' => 0
                                ]
                            ]);
                        }
                        $totalIndentAmount += $indent_sku_amount;
                    } else {
                        if (!empty($existingIndentItem)) {
                            $this->Inventory_internal_indent_items->updateAll(
                                [
                                    'Inventory_internal_indent_items.sku_quantity' => 0,
                                    'Inventory_internal_indent_items.completed_quantity' => 0,
                                    'Inventory_internal_indent_items.sku_amount' => 0,
                                    'Inventory_internal_indent_items.landed_amt' => 0,
                                    'Inventory_internal_indent_items.sku_total_tax_value' => 0,
                                    'Inventory_internal_indent_items.is_completed' => 0,
                                    'Inventory_internal_indent_items.status' => 0,
                                    'Inventory_internal_indent_items.sync' => 0,
                                    'Inventory_internal_indent_items.local_sync' => 0
                                ],
                                [
                                    'Inventory_internal_indent_items.Id' => $existingIndentItem['Inventory_internal_indent_items']['Id']
                                ]
                            );
                        }
                    }
                }

                $this->Inventory_internal_indent->updateAll(
                    [
                        'Inventory_internal_indent.total_amount' => $totalIndentAmount,
                        'Inventory_internal_indent.is_completed' => 1,
                        'Inventory_internal_indent.po_locked' => 1,
                        'Inventory_internal_indent.modified' => "'" . date('Y-m-d H:i:s') . "'",
                        'Inventory_internal_indent.sync' => 0,
                        'Inventory_internal_indent.local_sync' => 0
                    ],
                    [
                        'Inventory_internal_indent.indent_id' => $indent_id
                    ]
                );
            } else {
                $indentItems = $this->Inventory_internal_indent_items->find('all', [
                    'conditions' => [
                        'Inventory_internal_indent_items.indent_id' => $indent_id,
                        'Inventory_internal_indent_items.status' => 1
                    ],
                    'recursive' => -1
                ]);

                $allItemsCompleted = true;

                foreach ($indentItems as $item) {

                    $itemId    = $item['Inventory_internal_indent_items']['Id'];
                    $skuId     = $item['Inventory_internal_indent_items']['sku_id'];
                    $indentQty = (float)$item['Inventory_internal_indent_items']['sku_quantity'];

                    $poQtyData = $this->Inventory_purchase_order_item->find('first', [
                        'conditions' => [
                            'Inventory_purchase_order_item.internal_indent_id' => $indent_id,
                            'Inventory_purchase_order_item.sku_id' => $skuId,
                            'Inventory_purchase_order_item.status !=' => 0
                        ],
                        'fields' => [
                            'SUM(Inventory_purchase_order_item.sku_quantity) as total_qty'
                        ],
                        'recursive' => -1
                    ]);

                    $totalPoQty = (float)($poQtyData[0]['total_qty'] ?? 0);

                    if ($totalPoQty > $indentQty) {
                        $totalPoQty = $indentQty;
                    }

                    $this->Inventory_internal_indent_items->updateAll(
                        [
                            'Inventory_internal_indent_items.completed_quantity' => $totalPoQty,
                            'Inventory_internal_indent_items.is_completed' =>
                                ($totalPoQty >= $indentQty) ? 1 : 0
                        ],
                        [
                            'Inventory_internal_indent_items.id' => $itemId,
                            'Inventory_internal_indent_items.status' => 1
                        ]
                    );

                    if ($totalPoQty < $indentQty) {
                        $allItemsCompleted = false;
                    }
                }

                $this->Inventory_internal_indent->updateAll(
                    [
                        'Inventory_internal_indent.is_completed' =>
                            $allItemsCompleted ? 1 : 0,
                        'Inventory_internal_indent.modified' =>
                            "'" . date('Y-m-d H:i:s') . "'"
                    ],
                    [
                        'Inventory_internal_indent.indent_id' => $indent_id,
                        'Inventory_internal_indent.status' => 1
                    ]
                );
            }

            $this->Flash->success(__('PO has been Updated.'));
            return $this->redirect(array('action' => 'po_list'));
        }
    }

    public function process_po()
    {
        $this->autoRender = false;

        if ($this->request->is('post')) {
            $poid   = $this->request->data['_id'];
            $items  = $this->request->data['_items'];
            $charge = $this->request->data['_charge'] ?? 0;

            $today = date('Y-m-d H:i:s');
            $id    = $this->Session->read('Auth.User.id');

            $user = $this->User->find('first', [
                'conditions' => ['User.id' => $id],
                'fields'     => ['User.name'],
                'recursive'  => -1
            ]);
            $name = $user['User']['name'];

            $amount                       = 0;
            $total_processed_disc_value   = 0;

            $poHeader_p = $this->Inventory_purchase_order->find('first', [
                'conditions' => ['Inventory_purchase_order.p_o_id' => $poid],
                'fields'     => ['Inventory_purchase_order.location_id'],
                'recursive'  => -1
            ]);
            $po_location_id_p = isset($poHeader_p['Inventory_purchase_order']['location_id'])
                ? intval($poHeader_p['Inventory_purchase_order']['location_id']) : 0;

            $SkuExciseModel_p = ClassRegistry::init('Inventory_sku_excise');

            foreach ($items as $item) {
                $itemId   = (int)$item['item_id'];
                $quantity = (float)$item['quantity'];

                $processDiscType  = isset($item['discount_type']) ? $item['discount_type'] : 'flat';
                $processDiscInput = (float)($item['discount'] ?? 0);

                $editedExcisePu  = floatval($item['excise_per_unit'] ?? 0);
                $editedAddlPu    = floatval($item['addl_excise_per_unit'] ?? 0);
                $editedVatPct    = floatval($item['vat_percentage'] ?? 0);
                $editedTcsPct    = floatval($item['tcs_percentage'] ?? 0);

                $poItem = $this->Inventory_purchase_order_item->find('first', [
                    'conditions' => [
                        'Inventory_purchase_order_item.id'     => $itemId,
                        'Inventory_purchase_order_item.status' => 2
                    ],
                    'fields'    => ['Inventory_purchase_order_item.sku_id', 'sku_price', 'sku_quantity',
                                    'excise_duty_amt', 'addl_excise_duty_amt', 'vat_percentage', 'tcs_percentage',
                                    'excise_per_unit', 'addl_excise_per_unit', 'vat_amt', 'tcs_amt'],
                    'recursive' => -1
                ]);
                $sku_id          = $poItem['Inventory_purchase_order_item']['sku_id'];
                $price           = floatval($poItem['Inventory_purchase_order_item']['sku_price']);
                $origQty         = floatval($poItem['Inventory_purchase_order_item']['sku_quantity']);

                $excise_pu_p     = isset($item['excise_per_unit']) ? $editedExcisePu : floatval($poItem['Inventory_purchase_order_item']['excise_per_unit'] ?? 0);
                $addl_excise_pu_p= isset($item['addl_excise_per_unit']) ? $editedAddlPu : floatval($poItem['Inventory_purchase_order_item']['addl_excise_per_unit'] ?? 0);
                $vat_pct_p       = isset($item['vat_percentage']) ? $editedVatPct : floatval($poItem['Inventory_purchase_order_item']['vat_percentage']);
                $tcs_pct_p       = isset($item['tcs_percentage']) ? $editedTcsPct : floatval($poItem['Inventory_purchase_order_item']['tcs_percentage']);

                $taxResult = $this->Inventory_sku_tax->query(
                    "SELECT SUM(tax_percentage) AS total_tax FROM inventory_sku_tax WHERE product_id = '$sku_id' and status=1"
                );
                $total_tax = round($taxResult[0][0]['total_tax'], 2);

                $baseAmount = $price * $quantity;

                if ($processDiscType === 'percentage') {
                    if ($processDiscInput > 100) { $processDiscInput = 100; }
                    $processDiscValue = round(($baseAmount * $processDiscInput) / 100, 2);
                } else {
                    if ($processDiscInput > $baseAmount) { $processDiscInput = $baseAmount; }
                    $processDiscValue = $processDiscInput;
                }

                $baseP           = $baseAmount - $processDiscValue;

                $excise_line_p   = round($excise_pu_p * $quantity, 2);
                $addl_line_p     = round($addl_excise_pu_p * $quantity, 2);
                $vat_base_p      = $baseP + $excise_line_p + $addl_line_p;
                $vat_line_p      = round($vat_base_p * $vat_pct_p / 100, 2);
                $tcs_line_p      = round(($baseP + $excise_line_p + $vat_line_p) * $tcs_pct_p / 100, 2);
                $landed_p        = $baseP + $excise_line_p + $vat_line_p;

                $effective_tax_p = ($excise_pu_p > 0 || $vat_pct_p > 0) ? 0 : $total_tax;
                $tax_value       = round($landed_p * $effective_tax_p / 100, 2);
                $skuamount       = round($landed_p + $tax_value + $tcs_line_p, 2);

                $this->Inventory_purchase_order_item->updateAll(
                    [
                        'Inventory_purchase_order_item.processed_quantity'        => $quantity,
                        'Inventory_purchase_order_item.processed_total_tax_value' => $tax_value,
                        'Inventory_purchase_order_item.processed_amount'          => $skuamount,

                        'Inventory_purchase_order_item.process_discount'          => $processDiscInput,
                        'Inventory_purchase_order_item.process_discount_type'     => "'$processDiscType'",
                        'Inventory_purchase_order_item.process_discount_value'    => $processDiscValue,

                        'Inventory_purchase_order_item.excise_per_unit'          => $excise_pu_p,
                        'Inventory_purchase_order_item.addl_excise_per_unit'     => $addl_excise_pu_p,
                        'Inventory_purchase_order_item.vat_percentage'           => $vat_pct_p,
                        'Inventory_purchase_order_item.tcs_percentage'           => $tcs_pct_p,
                        'Inventory_purchase_order_item.excise_duty_amt'          => $excise_line_p,
                        'Inventory_purchase_order_item.addl_excise_duty_amt'     => $addl_line_p,
                        'Inventory_purchase_order_item.vat_amt'                  => $vat_line_p,
                        'Inventory_purchase_order_item.tcs_amt'                  => $tcs_line_p,
                        'Inventory_purchase_order_item.status'                    => 3,
                        'Inventory_purchase_order_item.sync'                      => 0,
                        'Inventory_purchase_order_item.local_sync'                => 0
                    ],
                    [
                        'Inventory_purchase_order_item.id'     => $itemId,
                        'Inventory_purchase_order_item.p_o_id' => $poid,
                        'Inventory_purchase_order_item.status' => 2
                    ]
                );

                $amount                     += $skuamount;
                $total_processed_disc_value += $processDiscValue;
            }

            $this->Inventory_purchase_order->updateAll(
                [
                    'Inventory_purchase_order.status'                   => 3,
                    'Inventory_purchase_order.processed_by_id'          => $id,
                    'Inventory_purchase_order.processed_by_name'        => "'$name'",
                    'Inventory_purchase_order.processed_on'             => "'$today'",
                    'Inventory_purchase_order.processed_charge'         => $charge,
                    'Inventory_purchase_order.processed_amount'         => $amount + $charge,

                    'Inventory_purchase_order.processed_discount_value' => $total_processed_disc_value,
                    'Inventory_purchase_order.sync'                     => 0,
                    'Inventory_purchase_order.local_sync'               => 0
                ],
                [
                    'Inventory_purchase_order.p_o_id' => $poid,
                    'Inventory_purchase_order.status' => 2
                ]
            );

            echo json_encode(['status' => true]);
            exit;
        }
    }

    public function approve_po()
    {
        $this->autoRender = false;

        if ($this->request->is('post')) {
            $poid   = $this->request->data['_id'];
            $items  = $this->request->data['_items'];
            $charge = $this->request->data['_charge'] ?? 0;

            $today = date('Y-m-d H:i:s');
            $id    = $this->Session->read('Auth.User.id');

            $user = $this->User->find('first', [
                'conditions' => ['User.id' => $id],
                'fields'     => ['User.name'],
                'recursive'  => -1
            ]);
            $name = $user['User']['name'];

            $amount                      = 0;
            $total_approved_disc_value   = 0;

            $poHeader_a = $this->Inventory_purchase_order->find('first', [
                'conditions' => ['Inventory_purchase_order.p_o_id' => $poid],
                'fields'     => ['Inventory_purchase_order.location_id'],
                'recursive'  => -1
            ]);
            $po_location_id_a = isset($poHeader_a['Inventory_purchase_order']['location_id'])
                ? intval($poHeader_a['Inventory_purchase_order']['location_id']) : 0;

            $SkuExciseModel_a = ClassRegistry::init('Inventory_sku_excise');

            foreach ($items as $item) {
                $itemId   = (int)$item['item_id'];
                $quantity = (float)$item['quantity'];

                $approveDiscType  = isset($item['discount_type']) ? $item['discount_type'] : 'flat';
                $approveDiscInput = (float)($item['discount'] ?? 0);

                $editedExcisePu  = floatval($item['excise_per_unit'] ?? 0);
                $editedAddlPu    = floatval($item['addl_excise_per_unit'] ?? 0);
                $editedVatPct    = floatval($item['vat_percentage'] ?? 0);
                $editedTcsPct    = floatval($item['tcs_percentage'] ?? 0);

                $poItem = $this->Inventory_purchase_order_item->find('first', [
                    'conditions' => [
                        'Inventory_purchase_order_item.id'     => $itemId,
                        'Inventory_purchase_order_item.status' => 1
                    ],
                    'fields'    => ['Inventory_purchase_order_item.sku_id', 'sku_price', 'sku_quantity',
                                    'excise_duty_amt', 'addl_excise_duty_amt', 'vat_percentage', 'tcs_percentage',
                                    'excise_per_unit', 'addl_excise_per_unit', 'vat_amt', 'tcs_amt'],
                    'recursive' => -1
                ]);
                $sku_id          = $poItem['Inventory_purchase_order_item']['sku_id'];
                $price           = floatval($poItem['Inventory_purchase_order_item']['sku_price']);
                $origQty         = floatval($poItem['Inventory_purchase_order_item']['sku_quantity']);

                $excise_pu_a     = isset($item['excise_per_unit']) ? $editedExcisePu : floatval($poItem['Inventory_purchase_order_item']['excise_per_unit'] ?? 0);
                $addl_excise_pu_a= isset($item['addl_excise_per_unit']) ? $editedAddlPu : floatval($poItem['Inventory_purchase_order_item']['addl_excise_per_unit'] ?? 0);
                $vat_pct_a       = isset($item['vat_percentage']) ? $editedVatPct : floatval($poItem['Inventory_purchase_order_item']['vat_percentage']);
                $tcs_pct_a       = isset($item['tcs_percentage']) ? $editedTcsPct : floatval($poItem['Inventory_purchase_order_item']['tcs_percentage']);

                $taxResult = $this->Inventory_sku_tax->query(
                    "SELECT SUM(tax_percentage) AS total_tax FROM inventory_sku_tax WHERE product_id = '$sku_id' and status=1"
                );
                $total_tax = round($taxResult[0][0]['total_tax'], 2);

                $baseAmount = $price * $quantity;

                if ($approveDiscType === 'percentage') {
                    if ($approveDiscInput > 100) { $approveDiscInput = 100; }
                    $approveDiscValue = round(($baseAmount * $approveDiscInput) / 100, 2);
                } else {
                    if ($approveDiscInput > $baseAmount) { $approveDiscInput = $baseAmount; }
                    $approveDiscValue = $approveDiscInput;
                }

                $base            = $baseAmount - $approveDiscValue;

                $excise_line_a   = round($excise_pu_a * $quantity, 2);
                $addl_line_a     = round($addl_excise_pu_a * $quantity, 2);
                $vat_base_a      = $base + $excise_line_a + $addl_line_a;
                $vat_line_a      = round($vat_base_a * $vat_pct_a / 100, 2);
                $tcs_line_a      = round(($base + $excise_line_a + $vat_line_a) * $tcs_pct_a / 100, 2);
                $landed_a        = $base + $excise_line_a + $vat_line_a;

                $effective_tax_a = ($excise_pu_a > 0 || $vat_pct_a > 0) ? 0 : $total_tax;
                $tax_value       = round($landed_a * $effective_tax_a / 100, 2);
                $skuamount       = round($landed_a + $tax_value + $tcs_line_a, 2);

                $this->Inventory_purchase_order_item->updateAll(
                    [
                        'Inventory_purchase_order_item.approved_quantity'        => $quantity,
                        'Inventory_purchase_order_item.approved_total_tax_value' => $tax_value,
                        'Inventory_purchase_order_item.approved_amount'          => $skuamount,

                        'Inventory_purchase_order_item.approve_discount'         => $approveDiscInput,
                        'Inventory_purchase_order_item.approve_discount_type'    => "'$approveDiscType'",
                        'Inventory_purchase_order_item.approve_discount_value'   => $approveDiscValue,

                        'Inventory_purchase_order_item.excise_per_unit'          => $excise_pu_a,
                        'Inventory_purchase_order_item.addl_excise_per_unit'     => $addl_excise_pu_a,
                        'Inventory_purchase_order_item.vat_percentage'           => $vat_pct_a,
                        'Inventory_purchase_order_item.tcs_percentage'           => $tcs_pct_a,
                        'Inventory_purchase_order_item.excise_duty_amt'          => $excise_line_a,
                        'Inventory_purchase_order_item.addl_excise_duty_amt'     => $addl_line_a,
                        'Inventory_purchase_order_item.vat_amt'                  => $vat_line_a,
                        'Inventory_purchase_order_item.tcs_amt'                  => $tcs_line_a,
                        'Inventory_purchase_order_item.status'                   => 2,
                        'Inventory_purchase_order_item.sync'                     => 0,
                        'Inventory_purchase_order_item.local_sync'               => 0
                    ],
                    [
                        'Inventory_purchase_order_item.id'     => $itemId,
                        'Inventory_purchase_order_item.p_o_id' => $poid,
                        'Inventory_purchase_order_item.status' => 1
                    ]
                );

                $amount                    += $skuamount;
                $total_approved_disc_value += $approveDiscValue;
            }

            $this->Inventory_purchase_order->updateAll(
                [
                    'Inventory_purchase_order.status'                  => 2,
                    'Inventory_purchase_order.approved_by_id'          => $id,
                    'Inventory_purchase_order.approved_by_name'        => "'$name'",
                    'Inventory_purchase_order.approved_on'             => "'$today'",
                    'Inventory_purchase_order.approved_charge'         => $charge,
                    'Inventory_purchase_order.approved_amount'         => $amount + $charge,

                    'Inventory_purchase_order.approved_discount_value' => $total_approved_disc_value,
                    'Inventory_purchase_order.sync'                    => 0,
                    'Inventory_purchase_order.local_sync'              => 0
                ],
                [
                    'Inventory_purchase_order.p_o_id' => $poid,
                    'Inventory_purchase_order.status' => 1
                ]
            );

            echo json_encode(['status' => true]);
            exit;
        }
    }

    public function po_change_status()
    {
        $this->autoRender = false;

        if (!$this->request->is('post')) {
            echo json_encode(['status' => false]);
            return;
        }

        $poId = (int)$this->request->data('_id');

        $po = $this->Inventory_purchase_order->find('first', [
            'conditions' => ['Inventory_purchase_order.id' => $poId],
            'recursive'  => -1
        ]);

        if (empty($po)) {
            echo json_encode(['status' => false]);
            return;
        }

        $indentId = $po['Inventory_purchase_order']['internal_indent_id'];
        $p_o_id   = $po['Inventory_purchase_order']['p_o_id'];

        $currentStatus = (int)$po['Inventory_purchase_order']['status'];
        $newStatus     = ($currentStatus == 1) ? 0 : 1;

        if ($newStatus == 1) {

            $poItems = $this->Inventory_purchase_order_item->find('all', [
                'conditions' => [
                    'Inventory_purchase_order_item.p_o_id' => $p_o_id
                ],
                'recursive' => -1
            ]);

            foreach ($poItems as $poItem) {

                $skuId = $poItem['Inventory_purchase_order_item']['sku_id'];
                $sku_name = $poItem['Inventory_purchase_order_item']['sku_name'];
                $poQty = (float)$poItem['Inventory_purchase_order_item']['sku_quantity'];

                $activeQtyData = $this->Inventory_purchase_order_item->find('first', [
                    'conditions' => [
                        'Inventory_purchase_order_item.internal_indent_id' => $indentId,
                        'Inventory_purchase_order_item.sku_id'             => $skuId,
                        'Inventory_purchase_order_item.status'             => 1,
                        'Inventory_purchase_order_item.p_o_id !='          => $p_o_id
                    ],
                    'fields' => [
                        'SUM(Inventory_purchase_order_item.sku_quantity) AS total_qty'
                    ],
                    'recursive' => -1
                ]);

                $activeQty = (float)($activeQtyData[0]['total_qty'] ?? 0);

                $indentItem = $this->Inventory_internal_indent_items->find('first', [
                    'conditions' => [
                        'Inventory_internal_indent_items.indent_id' => $indentId,
                        'Inventory_internal_indent_items.sku_id'    => $skuId
                    ],
                    'recursive' => -1
                ]);

                $indentQty = (float)$indentItem['Inventory_internal_indent_items']['sku_quantity'];

                if (($activeQty + $poQty) > $indentQty) {
                    echo json_encode([
                        'status'  => false,
                        'message' => "Cannot activate PO. SKU {$sku_name} exceeds indent quantity."
                    ]);
                    return;
                }
            }
        }

        $this->Inventory_purchase_order->save([
            'id'         => $poId,
            'status'     => $newStatus,
            'sync'       => 0,
            'local_sync' => 0
        ]);

        $this->Inventory_purchase_order_item->updateAll(
            [
                'Inventory_purchase_order_item.status'     => "'" . $newStatus . "'",
                'Inventory_purchase_order_item.sync'       => '0',
                'Inventory_purchase_order_item.local_sync' => '0'
            ],
            [
                'Inventory_purchase_order_item.p_o_id' => $p_o_id
            ]
        );

        $isAutoIndentPo = !empty($po['Inventory_purchase_order']['is_autogenerated_indent']);

        if ($isAutoIndentPo) {
            if ($newStatus == 0) {

                $this->Inventory_internal_indent_items->updateAll(
                    [
                        'Inventory_internal_indent_items.status' => 0,
                        'Inventory_internal_indent_items.is_completed' => 0,
                        'Inventory_internal_indent_items.completed_quantity' => 0,
                        'Inventory_internal_indent_items.sync' => 0,
                        'Inventory_internal_indent_items.local_sync' => 0
                    ],
                    [
                        'Inventory_internal_indent_items.indent_id' => $indentId
                    ]
                );
                $this->Inventory_internal_indent->updateAll(
                    [
                        'Inventory_internal_indent.status' => 0,
                        'Inventory_internal_indent.is_completed' => 0,
                        'Inventory_internal_indent.po_locked' => 0,
                        'Inventory_internal_indent.modified' => "'" . date('Y-m-d H:i:s') . "'",
                        'Inventory_internal_indent.sync' => 0,
                        'Inventory_internal_indent.local_sync' => 0
                    ],
                    [
                        'Inventory_internal_indent.indent_id' => $indentId
                    ]
                );
            } else {

                $this->Inventory_internal_indent_items->updateAll(
                    [
                        'Inventory_internal_indent_items.status' => 1,
                        'Inventory_internal_indent_items.is_completed' => 1,
                        'Inventory_internal_indent_items.completed_quantity' => 'Inventory_internal_indent_items.sku_quantity',
                        'Inventory_internal_indent_items.sync' => 0,
                        'Inventory_internal_indent_items.local_sync' => 0
                    ],
                    [
                        'Inventory_internal_indent_items.indent_id' => $indentId
                    ]
                );
                $this->Inventory_internal_indent->updateAll(
                    [
                        'Inventory_internal_indent.status' => 1,
                        'Inventory_internal_indent.is_completed' => 1,
                        'Inventory_internal_indent.po_locked' => 1,
                        'Inventory_internal_indent.modified' => "'" . date('Y-m-d H:i:s') . "'",
                        'Inventory_internal_indent.sync' => 0,
                        'Inventory_internal_indent.local_sync' => 0
                    ],
                    [
                        'Inventory_internal_indent.indent_id' => $indentId
                    ]
                );
            }
        } else {
            $indentItems = $this->Inventory_internal_indent_items->find('all', [
                'conditions' => [
                    'Inventory_internal_indent_items.indent_id' => $indentId,
                    'Inventory_internal_indent_items.status'    => 1
                ],
                'recursive' => -1
            ]);

            $allItemsCompleted = true;

            foreach ($indentItems as $item) {

                $itemId    = $item['Inventory_internal_indent_items']['Id'];
                $skuId     = $item['Inventory_internal_indent_items']['sku_id'];
                $indentQty = (float)$item['Inventory_internal_indent_items']['sku_quantity'];

                $poQtyData = $this->Inventory_purchase_order_item->find('first', [
                    'conditions' => [
                        'Inventory_purchase_order_item.internal_indent_id' => $indentId,
                        'Inventory_purchase_order_item.sku_id'             => $skuId,
                        'Inventory_purchase_order_item.status'             => 1
                    ],
                    'fields' => [
                        'SUM(Inventory_purchase_order_item.sku_quantity) AS total_qty'
                    ],
                    'recursive' => -1
                ]);

                $totalPoQty = (float)($poQtyData[0]['total_qty'] ?? 0);

                if ($totalPoQty > $indentQty) {
                    $totalPoQty = $indentQty;
                }

                $isCompleted = ($totalPoQty >= $indentQty) ? 1 : 0;

                $this->Inventory_internal_indent_items->updateAll(
                    [
                        'Inventory_internal_indent_items.completed_quantity' =>
                            "'" . $totalPoQty . "'",
                        'Inventory_internal_indent_items.is_completed' =>
                            "'" . $isCompleted . "'"
                    ],
                    [
                        'Inventory_internal_indent_items.Id' => $itemId
                    ]
                );

                if ($isCompleted == 0) {
                    $allItemsCompleted = false;
                }
            }

            $this->Inventory_internal_indent->updateAll(
                [
                    'Inventory_internal_indent.is_completed' =>
                        "'" . ($allItemsCompleted ? 1 : 0) . "'",
                    'Inventory_internal_indent.po_locked' => 0,
                    'Inventory_internal_indent.modified' =>
                        "'" . date('Y-m-d H:i:s') . "'"
                ],
                [
                    'Inventory_internal_indent.indent_id' => $indentId
                ]
            );
        }

        echo json_encode(['status' => true]);
    }

    public function stockin()
    {
        if (!$this->Session->check('Auth.User.id')) {
            return $this->redirect(['controller' => 'dashboard', 'action' => 'login']);
        }

        $authUser = $this->Session->read('Auth.User');
        $userRole = $authUser['role'] ?? '';
        $locationIdsRaw  = $authUser['location_ids'] ?? '';
        $locationIdSingle = $authUser['location_id'] ?? '';

        $locationIdsArray = !empty($locationIdsRaw)
            ? array_map('trim', is_array($locationIdsRaw) ? $locationIdsRaw : explode(',', $locationIdsRaw))
            : [];

        if (!empty($locationIdSingle)) {
            $locationIdsArray[] = trim($locationIdSingle);
        }

        $userLocationIds = array_values(array_unique($locationIdsArray));

        $this->layout = "users";
        $id = $_POST['po'] ?? null;
        $this->set(compact('id'));

        $conditions2 = ['status' => 1,'type' => 'DC'];
         if ($userRole !== 'admin' && !empty($userLocationIds)) {
            $conditions2['id IN'] = $userLocationIds;
        }
        $processedpoConditions = [
            "status" => 3,
            "direct_purchase" => 0,
            "location_type" => 'DC'
        ];
        if ($userRole !== 'admin' && !empty($userLocationIds)) {
            $processedpoConditions['location_id IN'] = $userLocationIds;
        }
        $processedpo = $this->Inventory_purchase_order->find('count', [
            'conditions' => $processedpoConditions
        ]);

        $stockedinpoConditions = [
            "status" => 4,
            "direct_purchase" => 0,
            "location_type" => 'DC'
        ];
        if ($userRole !== 'admin' && !empty($userLocationIds)) {
            $stockedinpoConditions['location_id IN'] = $userLocationIds;
        }
        $stockedinpo = $this->Inventory_purchase_order->find('count', [
            'conditions' => $stockedinpoConditions
        ]);
        $this->set(compact('processedpo','stockedinpo'));

        if ($id) {

            $id_safe = addslashes($id);
            $poQuerylocationchk = "SELECT * FROM inventory_purchase_order WHERE p_o_id = '$id_safe' and location_type='DC'";
            $indentschk = $this->Inventory_purchase_order->query($poQuerylocationchk);

            if (empty($indentschk)) {
                $this->set('po_error', 'PO Not Available for this location');
                return;
            }

            $poQuery = "SELECT * FROM inventory_purchase_order WHERE p_o_id = '$id_safe'";
            $indents = $this->Inventory_purchase_order->query($poQuery);

            if (empty($indents)) {
                $this->set('po_error', 'PO Not Found');
                return;
            }

            $po = $indents[0]['inventory_purchase_order'];

            if ($po['status'] == 4) {
                $this->set('po_error', 'PO Already Stocked IN');
                return;
            } elseif ($po['status'] < 3) {
                $this->set('po_error', 'PO is not Processed');
                return;
            } elseif ($po['status'] < 2) {
                $this->set('po_error', 'PO is not Approved');
                return;
            }

            if ($userRole !== 'admin' && !in_array($po['location_id'], $userLocationIds)) {
                $this->set('po_error', 'Location Not Found');
                return;
            }

            $po_location_id = intval($po['location_id']);
            $items = $this->Inventory_purchase_order_item->query(
                "SELECT inventory_purchase_order_items.*,
                        inventory_skus.excise_applicable,
                        inventory_skus.has_variable_tax
                 FROM inventory_purchase_order_items
                 LEFT JOIN inventory_skus ON inventory_skus.id = inventory_purchase_order_items.sku_id
                 WHERE inventory_purchase_order_items.p_o_id = '$id_safe'
                   AND inventory_purchase_order_items.status = 3"
            );
            $this->set(compact('indents', 'items'));
        }
    }

    public function stockin_po()
    {
        $this->autoRender = false;

        if ($this->request->is('post')) {
            $poid             = $this->request->data['_id'];
            $invoice          = trim($this->request->data['_invoice']);
            $charge           = trim($this->request->data['_charge']);
            $quantities       = $this->request->data['_quantities'];
            $discounts        = $this->request->data['_discounts']      ?? [];
            $discount_types   = $this->request->data['_discount_types'] ?? [];
            $excise_per_units = $this->request->data['_excise_per_units']  ?? [];
            $addl_excise_units= $this->request->data['_addl_excise_units'] ?? [];
            $vat_per_units    = $this->request->data['_vat_per_units']     ?? [];
            $tcs_per_units    = $this->request->data['_tcs_per_units']     ?? [];

            $stockin_date = $this->request->data['_stockin_date'] ?? [];
            $stockin_date = date('Y-m-d H:i:s', strtotime($stockin_date));

            $today    = date('Y-m-d H:i:s');
            $id       = $this->Session->read('Auth.User.id');

            $poid_safe = addslashes($poid);
            $poQuery  = "SELECT * FROM inventory_purchase_order WHERE p_o_id = '$poid_safe'";
            $indents  = $this->Inventory_purchase_order->query($poQuery);
            $store_id = $indents[0]['inventory_purchase_order']['location_id'];
            $name     = $indents[0]['inventory_purchase_order']['location_name'];

            do {
                $txnid = rand(1, 9);
                for ($i = 0; $i < 10; $i++) {
                    $txnid .= rand(0, 9);
                }
                $existing = $this->Inventory_purchase_order->find('first', [
                    'conditions' => ['stockin_id' => $txnid, 'status !=' => 0]
                ]);
            } while ($existing);

            $stockin_skus = $this->Inventory_purchase_order_item->find('all', [
                'conditions' => ['p_o_id' => $poid, 'status !=' => 0]
            ]);

            $amount                     = 0;
            $total_stockin_disc_value   = 0;
            $total_excise_duty          = 0;
            $total_addl_excise_duty     = 0;
            $total_vat_duty             = 0;
            $total_tcs_duty             = 0;
            $sku_stockin_prices = [];

            foreach ($stockin_skus as $stockin_sku) {
                $sku    = $stockin_sku['Inventory_purchase_order_item'];
                $sku_id = $sku['sku_id'];

                if (!isset($quantities[$sku_id])) continue;

                $qty = floatval($quantities[$sku_id]);

                $stockinDiscType  = isset($discount_types[$sku_id]) ? $discount_types[$sku_id] : 'flat';
                $stockinDiscInput = isset($discounts[$sku_id])      ? floatval($discounts[$sku_id]) : 0;

                $poItem = $this->Inventory_purchase_order_item->find('first', [
                    'conditions' => [
                        'Inventory_purchase_order_item.sku_id'    => $sku_id,
                        'Inventory_purchase_order_item.p_o_id'    => $poid,
                        'Inventory_purchase_order_item.status !=' => 0
                    ],
                    'fields'    => ['sku_price', 'excise_per_unit', 'addl_excise_per_unit', 'vat_percentage', 'tcs_percentage'],
                    'recursive' => -1
                ]);
                $price = $poItem['Inventory_purchase_order_item']['sku_price'];

                $product_taxes = $this->Inventory_sku_tax->query(
                    "SELECT SUM(tax_percentage) AS total_tax FROM inventory_sku_tax WHERE product_id='$sku_id' AND status=1"
                );
                $total_tax = round($product_taxes[0][0]['total_tax'], 2);

                $base_amount = $price * $qty;

                if ($stockinDiscType === 'percentage') {
                    if ($stockinDiscInput > 100) {
                        $stockinDiscInput = 100;
                    }
                    $stockinDiscValue = round(($base_amount * $stockinDiscInput) / 100, 2);
                } else {
                    if ($stockinDiscInput > $base_amount) {
                        $stockinDiscInput = $base_amount;
                    }
                    $stockinDiscValue = $stockinDiscInput;
                }

                $after_discount = $base_amount - $stockinDiscValue;
                $unit_price_after_dis = $qty > 0 ? $after_discount / $qty : 0;

                $excise_pu      = isset($excise_per_units[$sku_id]) ? floatval($excise_per_units[$sku_id]) : floatval($poItem['Inventory_purchase_order_item']['excise_per_unit'] ?? 0);
                $addl_excise_pu = isset($addl_excise_units[$sku_id]) ? floatval($addl_excise_units[$sku_id]) : floatval($poItem['Inventory_purchase_order_item']['addl_excise_per_unit'] ?? 0);
                $vat_pct_si     = isset($vat_per_units[$sku_id]) ? floatval($vat_per_units[$sku_id]) : floatval($poItem['Inventory_purchase_order_item']['vat_percentage'] ?? 0);
                $tcs_pct_si     = isset($tcs_per_units[$sku_id]) ? floatval($tcs_per_units[$sku_id]) : floatval($poItem['Inventory_purchase_order_item']['tcs_percentage'] ?? 0);

                $excise_line      = round($excise_pu * $qty, 4);
                $addl_excise_line = round($addl_excise_pu * $qty, 4);

                $tax_base     = $after_discount + $excise_line + $addl_excise_line;

                $effective_gst = ($excise_pu > 0 || $vat_pct_si > 0) ? 0 : $total_tax;
                $tax_value    = round($tax_base * ($effective_gst / 100), 2);

                $vat_line     = round($tax_base * ($vat_pct_si / 100), 2);

                $tcs_base_si  = $after_discount + $excise_line + $vat_line;
                $tcs_line     = round($tcs_base_si * ($tcs_pct_si / 100), 2);

                $skuamount    = round($after_discount + $excise_line + $vat_line + $tcs_line + $tax_value, 2);

                $this->Inventory_purchase_order_item->updateAll(
                    [
                        'status'                => 4,
                        'sync'                  => 0,
                        'local_sync'            => 0,
                        'stockin_quantity'      => $qty,
                        'stockin_total_tax_value' => $tax_value,
                        'stockin_amount'        => $skuamount,

                        'stockin_discount'      => $stockinDiscInput,
                        'stockin_discount_type' => "'$stockinDiscType'",
                        'stockin_discount_value'=> $stockinDiscValue,

                        'excise_per_unit'       => $excise_pu,
                        'addl_excise_per_unit'  => $addl_excise_pu,
                        'vat_percentage'        => $vat_pct_si,
                        'tcs_percentage'        => $tcs_pct_si,
                        'excise_duty_amt'       => $excise_line,
                        'addl_excise_duty_amt'  => $addl_excise_line,
                        'vat_amt'               => $vat_line,
                        'tcs_amt'               => $tcs_line
                    ],
                    [
                        'p_o_id'    => $poid,
                        'sku_id'    => $sku_id,
                        'status !=' => 0
                    ]
                );

                $amount                   += $skuamount;
                $total_stockin_disc_value += $stockinDiscValue;
                $total_excise_duty        += $excise_line;
                $total_addl_excise_duty   += $addl_excise_line;
                $total_vat_duty           += $vat_line;
                $total_tcs_duty           += $tcs_line;

                $ledgerprevdetails = $this->Stock_ledger->find('first', [
                    'conditions' => ['sku_id' => $sku_id, 'type' => 'DC', 'item_type' => 'SKU', 'store_id' => $store_id],
                    'fields'     => ['actual_balance'],
                    'order'      => ['id DESC'],
                    'recursive'  => -1
                ]);

                $ledger_prev_quantity = !empty($ledgerprevdetails['Stock_ledger']['actual_balance'])
                    ? $ledgerprevdetails['Stock_ledger']['actual_balance']
                    : 0;

                $newQuantity = $qty + $ledger_prev_quantity;

                $ledgerData['Stock_ledger'] = [
                    'store_id'       => $store_id,
                    'sku_id'         => $sku_id,
                    'sku_name'       => $sku['sku_name'],
                    'sku_price'      => round($landed_per_unit, 4),
                    'unit_id'        => $sku['sku_unit'],
                    'unit_name'      => $sku['sku_unit_name'],
                    'record_id'      => $txnid,
                    'record_type'    => 'stockin',
                    'quantity'       => round($qty,2),
                    'balance_stock'  => round($newQuantity,2),
                    'actual_balance' => round($newQuantity,2),
                    'quantity_type'  => 'credit',
                    'type'           => 'DC',
                    'sync'           => 0,
                    'local_sync'     => 0,
                    'created'        => date('Y-m-d H:i:s')
                ];
                $this->Stock_ledger->saveAll($ledgerData['Stock_ledger']);

                $avg_result = $this->Inventory_sku->query("
                    SELECT SUM(sku_price * quantity) / SUM(quantity) AS avg_price
                    FROM (
                        SELECT sku_price, quantity
                        FROM stock_ledger
                        WHERE sku_id = '$sku_id'
                        AND record_type = 'stockin'
                        AND item_type = 'sku'
                        ORDER BY id DESC LIMIT 3
                    ) AS last_3_txn
                ");
                $avg_stockin_price = $avg_result[0][0]['avg_price'] ?? 0;

                $tax_base_pu     = $unit_price_after_dis + $excise_pu + $addl_excise_pu;
                $vat_pu          = round($tax_base_pu * ($vat_pct_si / 100), 4);
                $landed_per_unit = round($tax_base_pu + $vat_pu, 4);

                $this->Inventory_sku->query("UPDATE inventory_skus SET
                    last_stockin_price = round($landed_per_unit, 4),
                    avg_stockin_price  = round($avg_stockin_price, 2),
                    sync               = 0,
                    local_sync         = 0
                    WHERE id = '$sku_id'");

                $sku_stockin_prices[$sku_id] = $avg_stockin_price;
            }

           $amount = $charge + $amount;

            $this->Inventory_purchase_order->query("UPDATE inventory_purchase_order SET
                stockin_id             = '$txnid',
                status                 = 4,
                stockin_on             = '$stockin_date',
                stockin_by_id          = $id,
                stockin_by_name        = '$name',
                stockin_charge         = '$charge',
                stockin_amount         = '$amount',
                invoice_no             = '$invoice',
                stockin_discount_value = '$total_stockin_disc_value',
                excise_duty_total      = '$total_excise_duty',
                addl_excise_duty_total = '$total_addl_excise_duty',
                vat_total              = '$total_vat_duty',
                tcs_total              = '$total_tcs_duty',
                sync                   = 0,
                local_sync             = 0
                WHERE p_o_id = '$poid' AND status != 0");

            $this->loadModel('Inventory_sku');
            $this->loadModel('Inventory_semifinished');
            $this->loadModel('Catalog_product');
            $this->loadModel('Inventory_linking');

            $this->_refreshCostsForStockin($sku_stockin_prices);

            echo json_encode(['status' => true]);
        }
    }

    public function po_pdf() {

        if (!$this->Session->check('Auth.User.id')) {
            return $this->redirect(['controller' => 'dashboard', 'action' => 'login']);
        }

        $this->layout = "login";

        if ($this->request->is(['post','put'])) {

            $po_id = $this->request->data['Report']['poid'];

            $indents = $this->Inventory_purchase_order->query(
                "SELECT * FROM inventory_purchase_order
                 JOIN inventory_vendors ON inventory_purchase_order.vendor_id = inventory_vendors.id
                 WHERE inventory_purchase_order.p_o_id = ?",
                [$po_id]
            );

            $items = $this->Inventory_purchase_order_item->query(
                "SELECT * FROM inventory_purchase_order_items
                 WHERE p_o_id = ? AND status != 0",
                [$po_id]
            );

            $status      = $indents[0]['inventory_purchase_order']['status'];
            $location_id = $indents[0]['inventory_purchase_order']['location_id'];

            $locationdetail = $this->Location->find('first', [
                'conditions' => ['Location.id' => $location_id]
            ]);

            switch ($status) {
                case 2:
                    $qtyCol      = 'approved_quantity';
                    $taxCol      = 'approved_total_tax_value';
                    $discountCol = 'approve_discount_value';
                    break;
                case 3:
                    $qtyCol      = 'processed_quantity';
                    $taxCol      = 'processed_total_tax_value';
                    $discountCol = 'process_discount_value';
                    break;
                case 4:
                    $qtyCol      = 'stockin_quantity';
                    $taxCol      = 'stockin_total_tax_value';
                    $discountCol = 'stockin_discount_value';
                    break;
                default:
                    $qtyCol      = 'sku_quantity';
                    $taxCol      = 'sku_total_tax_value';
                    $discountCol = 'sku_discount_value';
            }

            $vendorState   = trim($indents[0]['inventory_vendors']['state']);
            $locationState = trim($locationdetail['Location']['state']);

            $isIGST = (strcasecmp($vendorState, $locationState) !== 0) ? 1 : 0;
            $isCGST = $isIGST ? 0 : 1;

            $taxSummary = $this->Inventory_purchase_order_item->query("
                SELECT
                    CONCAT(sku_total_tax_percentage,'%') AS tax_name,
                    SUM(sku_price * {$qtyCol} - {$discountCol}) AS taxable,

                    CASE
                        WHEN sku_total_tax_percentage > 0 AND {$isCGST} = 1
                        THEN SUM({$taxCol}) / 2
                        ELSE 0
                    END AS cgst,

                    CASE
                        WHEN sku_total_tax_percentage > 0 AND {$isCGST} = 1
                        THEN SUM({$taxCol}) / 2
                        ELSE 0
                    END AS sgst,

                    CASE
                        WHEN sku_total_tax_percentage > 0 AND {$isIGST} = 1
                        THEN SUM({$taxCol})
                        ELSE 0
                    END AS igst

                FROM inventory_purchase_order_items
                WHERE p_o_id = ?
                  AND status != 0
                GROUP BY sku_total_tax_percentage
                ORDER BY sku_total_tax_percentage
            ", [$po_id]);

            $this->set(compact(
                'po_id', 'indents', 'items', 'locationdetail',
                'taxSummary', 'status', 'isIGST', 'discountCol'
            ));

            $this->render('po_pdf');
        }
    }

    public function waiter_list() {
        if(!$this->Session->check('Auth.User.id')){
            $this->redirect(array('controller'=>'dashboard','action' => 'login'));
        }

        $this->layout = "users";

        $startDate = date('m/d/Y', strtotime('-12 months'));
        $endDate = date('m/d/Y');

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

        $authUser = $this->Session->read('Auth.User');
        $userRole = $authUser['role'] ?? '';

        $locationIdsRaw  = $authUser['location_ids'] ?? '';
        $locationIdSingle = $authUser['location_id'] ?? '';

        $locationIdsArray = !empty($locationIdsRaw)
            ? array_map('trim', is_array($locationIdsRaw) ? $locationIdsRaw : explode(',', $locationIdsRaw))
            : [];

        if (!empty($locationIdSingle)) {
            $locationIdsArray[] = trim($locationIdSingle);
        }

        $userLocationIds = array_values(array_unique($locationIdsArray));

        $locationCondition = '';
        if ($userRole !== 'admin') {
            $locationCondition = "AND location_id IN (" . implode(',', $userLocationIds) . ")";
        }

        $_shiftRange = $this->getShiftDateRange($startdate, $enddate);
        $startDatetime = $_shiftRange['startDatetime'];
        $endDatetime   = $_shiftRange['endDatetime'];

        $users = $this->Order_waiter->query("SELECT * FROM order_waiters where created between '$startDatetime' and  '$endDatetime' $locationCondition ORDER BY id desc");
        $yourStartDateValue=$startDate . ' - ' . $endDate;
        $this->set('startdate', $yourStartDateValue);

        $this->set(compact('users'));

    }
    public function create_waiter()
    {
        if(!$this->Session->check('Auth.User.id')){
            $this->redirect(array('controller'=>'dashboard','action' => 'login'));
        }

        $this->layout = "users";

        $authUser = $this->Session->read('Auth.User');
        $userRole = $authUser['role'] ?? '';
        $userLocationId = $authUser['location_ids'] ?? 0;

        $locationCondition = ['status' => 1];

        if ($userRole !== 'admin') {
            $userLocationIds = is_array($userLocationId) ? $userLocationId : [$userLocationId];
            $locationCondition['id'] = $userLocationIds;
        }
        $locations=$this->Location->find('all',array('conditions'=>array($locationCondition)));
        $this->set(compact('locations'));

        if ($this->request->is(array('post', 'put'))) {

            $name = $this->data['User']['name'];
            if($name==''){
                $this->Flash->error(__('Name could not be Blank'));
                return $this->redirect(array('action' => 'create_waiter'));
            }
            $phone = $this->data['User']['phone'];
            $pin=$this->data['User']['pin'];
            $pin_req = $this->data['User']['pin_req'];

            $namecheck=$this->Order_waiter->find('all',array('conditions'=>array('name'=>$name)));
            if(!empty($namecheck)){
                $this->Flash->error(__('Waiter Name already exist.'));
                return $this->redirect(array('action' => 'waiter_list'));
            }
            $phonecheck=$this->Order_waiter->find('all',array('conditions'=>array('phone'=>$phone)));
            if(!empty($phonecheck)){
                $this->Flash->error(__('Waiter Phone already exist.'));
                return $this->redirect(array('action' => 'waiter_list'));
            }
            $pincheck=$this->Order_waiter->find('all',array('conditions'=>array('pin_number'=>$pin)));
            if(!empty($pincheck)){
                $this->Flash->error(__('Waiter Pin already exist.'));
                return $this->redirect(array('action' => 'waiter_list'));
            }
            $location_id =$this->data['User']['location'];
            $locationdetail=$this->Location->find('first',array('conditions'=>array('Location.id'=>$location_id)));
            $locationname=$locationdetail['Location']['name'];

            $data['Order_waiter']=array(
                'name'=>$name,
                'phone'=>$phone,
                'location_id'=>$location_id,
                'location_name'=>$locationname,
                'pin_number'=>$pin,
                'is_pin_required'=>$pin_req,
                'sync'      =>0,
                'local_sync' =>0
            );

            if($this->Order_waiter->saveAll($data['Order_waiter']))
            {
                $this->Flash->success(__('Waiter has been saved.'));
                return $this->redirect(array('action' => 'waiter_list'));
            } else {
                $this->Flash->error(__('Waiter could not be saved. Please, try again.'));
            }
        }
    }

    public function waiter_change_status() {
        $this->autoRender = false;

        if ($this->request->is('post')) {
            $Id = $this->request->data['_id'];

            $this->Order_waiter->id = $Id;

            $currentStatus = $this->Order_waiter->field('status', ['id' => $Id]);
            $newStatus = in_array($currentStatus, [1]) ? 0 : 1;

            if ($this->Order_waiter->save(['status' => $newStatus,'sync' => 0,'local_sync' => 0])) {
                echo json_encode(['status' => true]);
            } else {
                echo json_encode(['status' => false]);
            }
        }
    }

    public function edit_waiter()
    {
        if(!$this->Session->check('Auth.User.id')){
            $this->redirect(array('controller'=>'dashboard','action' => 'login'));
        }

        $this->layout = "users";

        if ($this->data['User']['id']!='') {

            $id = $this->data['User']['id'];
            $name = $this->data['User']['name'];
            $phone = $this->data['User']['phone'];
            $pin = $this->data['User']['pin'];
            $is_pin_required = $this->data['User']['pin_req'];

            $phonecheck=$this->Order_waiter->find('all',array('conditions'=>array('phone'=>$phone,'id !='=>$id)));
            if(!empty($phonecheck)){
                $this->Flash->error(__('Waiter Phone already exist.'));
                return $this->redirect(array('action' => 'waiter_list'));
            }
            $pincheck=$this->Order_waiter->find('all',array('conditions'=>array('pin_number'=>$pin,'id !='=>$id)));
            if(!empty($pincheck)){
                $this->Flash->error(__('Waiter Pin already exist.'));
                return $this->redirect(array('action' => 'waiter_list'));
            }
            $location = $this->data['User']['location'];
            $locationdetail=$this->Location->find('first',array('conditions'=>array('Location.id'=>$location)));
            $locationname=$locationdetail['Location']['name'];

            $this->Order_table->query("UPDATE order_waiters SET name ='$name',phone='$phone',location_id ='$location',location_name='$locationname',pin_number='$pin',is_pin_required='$is_pin_required',sync = 0,local_sync = 0 WHERE id =". $id);
            $this->Flash->success(__('Waiter has been Updated.'));
            return $this->redirect(array('action' => 'waiter_list'));
        }else{
            $id=$_REQUEST['id'];

            $authUser = $this->Session->read('Auth.User');
            $userRole = $authUser['role'] ?? '';

            $locationIdsRaw   = $authUser['location_ids'] ?? '';
            $locationIdSingle = $authUser['location_id']  ?? '';

            $locationIdsArray = !empty($locationIdsRaw)
                ? array_map('trim', is_array($locationIdsRaw) ? $locationIdsRaw : explode(',', $locationIdsRaw))
                : [];

            if (!empty($locationIdSingle)) {
                $locationIdsArray[] = trim($locationIdSingle);
            }

            $userLocationIds = array_values(
                array_unique(
                    array_map('intval', $locationIdsArray)
                )
            );

            $userdetail=$this->Order_waiter->find('first',array('conditions'=>array('Order_waiter.id'=>$id)));
            $this->set(compact('userdetail'));

            $locationCondition = ['status' => 1];

            if ($userRole !== 'admin') {

                if (empty($userLocationIds)) {

                    $locationCondition['id'] = -1;
                } else {
                    $locationCondition['id'] = $userLocationIds;
                }
            }
            $locations=$this->Location->find('all',array('conditions'=>array($locationCondition)));
            $this->set(compact('locations'));
        }

    }

    public function kds()
    {
        if(!$this->Session->check('Auth.User.id')){
            $this->redirect(array('controller'=>'dashboard','action' => 'login'));
        }

        $this->layout = "users";

        $startDate = date('m/d/Y', strtotime('-12 months'));
        $endDate = date('m/d/Y');

        if($this->request->is('post') || $this->request->is('put'))
        {
            $daterange = $this->request->data['daterange'];
            $dates = explode(" - ", $daterange);
            $start_date = trim($dates[0]);
            $startDate = date('m/d/Y', strtotime($start_date));
            $end_date = trim($dates[1]);
            $endDate = date('m/d/Y', strtotime($end_date));
            $startdate = date('Y-m-d', strtotime($start_date));
            $enddate = date('Y-m-d', strtotime($end_date));
        } else {
            $startdate = date('Y-m-d', strtotime($startDate));
            $enddate = date('Y-m-d', strtotime($endDate));
        }

        $authUser = $this->Session->read('Auth.User');
        $userRole = $authUser['role'] ?? '';

        $locationIdsRaw   = $authUser['location_ids'] ?? '';
        $locationIdSingle = $authUser['location_id']  ?? '';

        $locationIdsArray = !empty($locationIdsRaw)
            ? array_map('trim', is_array($locationIdsRaw) ? $locationIdsRaw : explode(',', $locationIdsRaw))
            : [];

        if (!empty($locationIdSingle)) {
            $locationIdsArray[] = trim($locationIdSingle);
        }

        $userLocationIds = array_values(
            array_unique(
                array_map('intval', $locationIdsArray)
            )
        );

        $_shiftRange = $this->getShiftDateRange($startdate, $enddate);
        $startDatetime = $_shiftRange['startDatetime'];
        $endDatetime   = $_shiftRange['endDatetime'];

        $conditions = [
            'Pos_printer.created BETWEEN ? AND ?' => [$startDatetime, $endDatetime]
        ];

        if ($userRole !== 'admin') {
            if (empty($userLocationIds)) {

                $conditions['store_id'] = -1;
            } else {
                $conditions['store_id'] = $userLocationIds;
            }
        }

        $users = $this->Pos_printer->find('all', [
            'conditions' => $conditions,
            'joins' => [
                [
                    'table' => 'locations',
                    'alias' => 'Location',
                    'type' => 'LEFT',
                    'conditions' => ['Location.id = Pos_printer.store_id']
                ]
            ],
            'fields' => [
                'Pos_printer.*',
                'Location.name'
            ],
            'order' => ['Pos_printer.id' => 'DESC']
        ]);

        foreach ($users as &$u) {
            if (empty($u['Pos_printer']['store_name']) && !empty($u['Location']['name'])) {
                $u['Pos_printer']['store_name'] = $u['Location']['name'];
            }
        }
        unset($u);

        // Group rows sharing the same KDS group key so each KDS only appears once in the list
        $groupedUsers = array();
        foreach ($users as $u) {
            $kdsVal = !empty($u['Pos_printer']['kds']) ? (int)$u['Pos_printer']['kds'] : 0;
            $nameVal = strtolower(trim($u['Pos_printer']['name'] ?? ''));
            $key = ($kdsVal > 0) ? ($kdsVal . '_' . $nameVal) : ('id_' . $u['Pos_printer']['id']);

            $storeName = !empty($u['Pos_printer']['store_name']) ? trim($u['Pos_printer']['store_name']) : '';
            $ip = trim($u['Pos_printer']['ip'] ?? '');

            if (!isset($groupedUsers[$key])) {
                $groupedUsers[$key] = $u;
                $groupedUsers[$key]['stores_list'] = array();
                $groupedUsers[$key]['ips_list'] = array();
                if ($storeName !== '') {
                    $groupedUsers[$key]['stores_list'][] = $storeName;
                }
                if ($ip !== '') {
                    $groupedUsers[$key]['ips_list'][] = $ip;
                }
            } else {
                if ($storeName !== '' && !in_array($storeName, $groupedUsers[$key]['stores_list'])) {
                    $groupedUsers[$key]['stores_list'][] = $storeName;
                }
                if ($ip !== '' && !in_array($ip, $groupedUsers[$key]['ips_list'])) {
                    $groupedUsers[$key]['ips_list'][] = $ip;
                }
                if (!empty($u['Pos_printer']['status'])) {
                    $groupedUsers[$key]['Pos_printer']['status'] = 1;
                }
            }
        }

        foreach ($groupedUsers as &$gu) {
            if (!empty($gu['stores_list'])) {
                $gu['Pos_printer']['store_name'] = implode(', ', $gu['stores_list']);
            }
            if (!empty($gu['ips_list'])) {
                $gu['Pos_printer']['ip'] = implode(', ', $gu['ips_list']);
            }
        }
        unset($gu);

        $users = array_values($groupedUsers);

        $yourStartDateValue = $startDate . ' - ' . $endDate;
        $this->set('startdate', $yourStartDateValue);
        $this->set(compact('users'));
    }

    public function create_kds()
    {
        if(!$this->Session->check('Auth.User.id')){
            $this->redirect(array('controller'=>'dashboard','action' => 'login'));
        }

        $this->layout = "users";

        $authUser = $this->Session->read('Auth.User');
        $userRole = $authUser['role'] ?? '';

        $locationIdsRaw   = $authUser['location_ids'] ?? '';
        $locationIdSingle = $authUser['location_id']  ?? '';

        $locationIdsArray = !empty($locationIdsRaw)
            ? array_map('trim', is_array($locationIdsRaw) ? $locationIdsRaw : explode(',', $locationIdsRaw))
            : [];

        if (!empty($locationIdSingle)) {
            $locationIdsArray[] = trim($locationIdSingle);
        }

        $userLocationIds = array_values(
            array_unique(
                array_map('intval', $locationIdsArray)
            )
        );

        $locationCondition = ['status' => 1];

        if ($userRole !== 'admin') {
            if (empty($userLocationIds)) {
                $locationCondition['id'] = -1;
            } else {
                $locationCondition['id'] = $userLocationIds;
            }
        }

        $locations = $this->Location->find('all', array('conditions' => $locationCondition));
        $this->set(compact('locations'));

        if ($this->request->is('post') || $this->request->is('put')) {

            $name = trim($this->request->data['User']['name'] ?? '');
            if(empty($name)){
                $this->Flash->error(__('The Name could not be Blank'));
                return $this->redirect(array('action' => 'create_kds'));
            }

            // New multi-store payload: kds_rows[idx][store_id|ip]
            // (inputs are named kds_rows[...] so they sit at top-level, not under User)
            $rows = $this->request->data['kds_rows'] ?? $this->request->data['User']['kds_rows'] ?? array();
            if (empty($rows) && !empty($_POST['kds_rows'])) {
                $rows = $_POST['kds_rows'];
            }

            // Backward compat: old single-ip form posted User[ip] with no store
            if (empty($rows)) {
                $legacyIp = trim($this->request->data['User']['ip'] ?? '');
                if ($legacyIp !== '') {
                    $rows = array(array('store_id' => '', 'ip' => $legacyIp));
                }
            }

            if (empty($rows)) {
                $this->Flash->error(__('Please add at least 1 store with IP.'));
                return $this->redirect(array('action' => 'create_kds'));
            }

            // Normalize + validate: at least 1 store + 1 ip, unique stores, ip required
            $clean = array();
            $seenStores = array();
            foreach ($rows as $r) {
                $storeId = trim(isset($r['store_id']) ? (string)$r['store_id'] : '');
                $ip = trim(isset($r['ip']) ? (string)$r['ip'] : '');
                if ($storeId === '' || $ip === '') {
                    $this->Flash->error(__('Each store row needs a store selection and IP.'));
                    return $this->redirect(array('action' => 'create_kds'));
                }
                if (isset($seenStores[$storeId])) {
                    $this->Flash->error(__('Duplicate store selected. Please select each store only once.'));
                    return $this->redirect(array('action' => 'create_kds'));
                }
                $seenStores[$storeId] = true;
                $clean[] = array('store_id' => $storeId, 'ip' => $ip);
            }

            if (empty($clean)) {
                $this->Flash->error(__('Please add at least 1 store with IP.'));
                return $this->redirect(array('action' => 'create_kds'));
            }

            // Resolve store_name for each store_id
            foreach ($clean as $i => $c) {
                $loc = $this->Location->findById($c['store_id']);
                if (empty($loc['Location']['id'])) {
                    $this->Flash->error(__('Invalid store selected.'));
                    return $this->redirect(array('action' => 'create_kds'));
                }
                $clean[$i]['store_name'] = $loc['Location']['name'];
            }

            // Block DB duplicates: same KDS name + same store must not already exist
            foreach ($clean as $c) {
                $exists = $this->Pos_printer->find('count', array(
                    'conditions' => array(
                        'Pos_printer.name' => $name,
                        'Pos_printer.store_id' => $c['store_id']
                    ),
                    'recursive' => -1
                ));
                if (!empty($exists)) {
                    $this->Flash->error(__('This KDS name already exists for store "' . $c['store_name'] . '".'));
                    return $this->redirect(array('action' => 'create_kds'));
                }
            }

            // Save one pos_printers row per store, sharing the same name.
            // Group them via kds = first inserted id (1 KDS name usable in N locations).
            $firstId = null;
            foreach ($clean as $c) {
                $this->Pos_printer->create();
                $saved = $this->Pos_printer->save(array(
                    'name'       => $name,
                    'store_id'   => $c['store_id'],
                    'store_name' => $c['store_name'],
                    'ip'         => $c['ip'],
                    'sync'       => 0,
                    'local_sync' => 0
                ));
                if (!$saved) {
                    $this->Flash->error(__('Kds could not be saved. Please, try again.'));
                    return $this->redirect(array('action' => 'create_kds'));
                }
                if ($firstId === null) {
                    $firstId = $this->Pos_printer->id;
                    $this->Pos_printer->updateAll(
                        array(
                            'Pos_printer.kds'        => $firstId,
                            'Pos_printer.sync'       => 0,
                            'Pos_printer.local_sync' => 0
                        ),
                        array('Pos_printer.id' => $firstId)
                    );
                } else {
                    $newId = $this->Pos_printer->id;
                    $this->Pos_printer->updateAll(
                        array(
                            'Pos_printer.kds'        => $firstId,
                            'Pos_printer.sync'       => 0,
                            'Pos_printer.local_sync' => 0
                        ),
                        array('Pos_printer.id' => $newId)
                    );
                }
            }

            $this->Flash->success(__('Kds has been saved.'));
            return $this->redirect(array('action' => 'kds'));
        }
    }

    public function kds_change_status()
    {
        $this->autoRender = false;

        if ($this->request->is('post')) {
            $Id = $this->request->data['_id'];

            $this->Pos_printer->id = $Id;
            $newStatus = $this->Pos_printer->field('status') == 1 ? 0 : 1;

            $printer = $this->Pos_printer->find('first', [
                'conditions' => ['Pos_printer.id' => $Id],
                'fields' => ['id', 'kds', 'store_id', 'name'],
                'recursive' => -1
            ]);

            if (!empty($printer)) {
                $kdsKey = (!empty($printer['Pos_printer']['kds']) && $printer['Pos_printer']['kds'] > 0)
                    ? (int)$printer['Pos_printer']['kds']
                    : (int)$Id;
                $kdsName = $printer['Pos_printer']['name'];

                $groupConditions = [
                    'OR' => [
                        [
                            'Pos_printer.kds' => $kdsKey,
                            'Pos_printer.name' => $kdsName
                        ],
                        ['Pos_printer.id' => $Id]
                    ]
                ];

                // Synchronize status for all rows in this KDS group
                $this->Pos_printer->updateAll(
                    ['Pos_printer.status' => $newStatus, 'Pos_printer.sync' => 0, 'Pos_printer.local_sync' => 0],
                    $groupConditions
                );

                $groupPrinters = $this->Pos_printer->find('all', [
                    'conditions' => $groupConditions,
                    'fields' => ['id', 'store_id'],
                    'recursive' => -1
                ]);

                foreach ($groupPrinters as $gp) {
                    $storeId = $gp['Pos_printer']['store_id'];
                    $this->Kds_users_station_mapping->updateAll(
                        [
                            'Kds_users_station_mapping.status' => $newStatus,
                            'Kds_users_station_mapping.sync' => 0,
                            'Kds_users_station_mapping.local_sync' => 0
                        ],
                        [
                            'Kds_users_station_mapping.station_store_id' => $storeId,
                            'OR' => [
                                'Kds_users_station_mapping.station_id' => $kdsKey,
                                'Kds_users_station_mapping.station_id' => $gp['Pos_printer']['id']
                            ]
                        ]
                    );

                    if ($newStatus == 0) {
                        $this->Kds_user->query("
                            UPDATE kds_users ku
                            SET ku.status = 0, ku.sync = 0, ku.local_sync = 0
                            WHERE ku.location_id = '$storeId'
                              AND ku.status = 1
                              AND NOT EXISTS (
                                  SELECT 1 FROM kds_users_station_mapping kusm
                                  JOIN pos_printers pp 
                                    ON (pp.kds = kusm.station_id OR pp.id = kusm.station_id)
                                   AND pp.store_id = kusm.station_store_id
                                   AND pp.status = 1
                                  WHERE kusm.kds_id = ku.id AND kusm.status = 1
                              )
                        ");
                    } else {
                        $this->Kds_user->query("
                            UPDATE kds_users ku
                            SET ku.status = 1, ku.sync = 0, ku.local_sync = 0
                            WHERE ku.location_id = '$storeId'
                              AND ku.status = 0
                              AND EXISTS (
                                  SELECT 1 FROM kds_users_station_mapping kusm
                                  JOIN pos_printers pp 
                                    ON (pp.kds = kusm.station_id OR pp.id = kusm.station_id)
                                   AND pp.store_id = kusm.station_store_id
                                   AND pp.status = 1
                                  WHERE kusm.kds_id = ku.id AND kusm.status = 1
                              )
                        ");
                    }
                }

                echo json_encode(['status' => true]);
            } else {
                echo json_encode(['status' => false]);
            }
        }
    }

    public function edit_kds($id = null)
    {
        if (!$this->Session->check('Auth.User.id')) {
            return $this->redirect(array('controller' => 'dashboard', 'action' => 'login'));
        }

        $this->layout = "users";

        if (empty($id)) {
            $this->Flash->error(__('Invalid KDS.'));
            return $this->redirect(array('action' => 'kds'));
        }

        $authUser = $this->Session->read('Auth.User');
        $userRole = $authUser['role'] ?? '';

        $locationIdsRaw   = $authUser['location_ids'] ?? '';
        $locationIdSingle = $authUser['location_id']  ?? '';

        $locationIdsArray = !empty($locationIdsRaw)
            ? array_map('trim', is_array($locationIdsRaw) ? $locationIdsRaw : explode(',', $locationIdsRaw))
            : [];

        if (!empty($locationIdSingle)) {
            $locationIdsArray[] = trim($locationIdSingle);
        }

        $userLocationIds = array_values(
            array_unique(
                array_map('intval', $locationIdsArray)
            )
        );

        $locationCondition = ['status' => 1];

        if ($userRole !== 'admin') {
            if (empty($userLocationIds)) {
                $locationCondition['id'] = -1;
            } else {
                $locationCondition['id'] = $userLocationIds;
            }
        }

        $locations = $this->Location->find('all', array('conditions' => $locationCondition));
        $this->set(compact('locations'));

        $kdsDetail = $this->Pos_printer->findById($id);
        if (empty($kdsDetail['Pos_printer']['id'])) {
            $this->Flash->error(__('Invalid KDS.'));
            return $this->redirect(array('action' => 'kds'));
        }

        $currentName = $kdsDetail['Pos_printer']['name'];
        $groupId = (!empty($kdsDetail['Pos_printer']['kds']) && $kdsDetail['Pos_printer']['kds'] > 0)
            ? (int)$kdsDetail['Pos_printer']['kds']
            : (int)$kdsDetail['Pos_printer']['id'];

        // Find all rows belonging to this KDS
        $kdsRows = $this->Pos_printer->find('all', array(
            'conditions' => array(
                'OR' => array(
                    array(
                        'Pos_printer.kds' => $groupId,
                        'Pos_printer.name' => $currentName
                    ),
                    array('Pos_printer.id' => $id)
                )
            ),
            'order' => array('Pos_printer.id' => 'ASC'),
            'recursive' => -1
        ));

        // Ensure all stores in existing kdsRows are in locations list
        $loadedLocIds = Hash::extract($locations, '{n}.Location.id');
        $missingLocIds = array();
        foreach ($kdsRows as $kr) {
            $sId = $kr['Pos_printer']['store_id'];
            if (!empty($sId) && !in_array($sId, $loadedLocIds)) {
                $missingLocIds[] = $sId;
            }
        }
        if (!empty($missingLocIds)) {
            $extraLocs = $this->Location->find('all', array(
                'conditions' => array('Location.id' => $missingLocIds)
            ));
            $locations = array_merge($locations, $extraLocs);
            $this->set(compact('locations'));
        }

        $existingIds = Hash::extract($kdsRows, '{n}.Pos_printer.id');
        $this->set(compact('kdsDetail', 'kdsRows', 'groupId', 'id'));

        if ($this->request->is('post') || $this->request->is('put')) {
            $name = trim($this->request->data['User']['name'] ?? '');
            if ($name === '') {
                $this->Flash->error(__('The Name could not be Blank'));
                return $this->redirect(array('action' => 'edit_kds', $id));
            }

            // Multi-store payload: kds_rows[idx][store_id|ip|id]
            $rows = $this->request->data['kds_rows'] ?? $this->request->data['User']['kds_rows'] ?? array();
            if (empty($rows) && !empty($_POST['kds_rows'])) {
                $rows = $_POST['kds_rows'];
            }

            // Backward compat / fallback for single store submission
            if (empty($rows)) {
                $singleStore = trim((string)($this->request->data['User']['store_id'] ?? ''));
                $singleIp = trim($this->request->data['User']['ip'] ?? '');
                if ($singleStore !== '' || $singleIp !== '') {
                    $rows = array(array('id' => $id, 'store_id' => $singleStore, 'ip' => $singleIp));
                }
            }

            if (empty($rows)) {
                $this->Flash->error(__('Please add at least 1 store with IP.'));
                return $this->redirect(array('action' => 'edit_kds', $id));
            }

            // Normalize + validate: at least 1 store + 1 ip, unique stores, ip required
            $clean = array();
            $seenStores = array();
            foreach ($rows as $r) {
                $storeId = trim(isset($r['store_id']) ? (string)$r['store_id'] : '');
                $ip = trim(isset($r['ip']) ? (string)$r['ip'] : '');
                $rowId = !empty($r['id']) ? (int)$r['id'] : null;

                if ($storeId === '' || $ip === '') {
                    $this->Flash->error(__('Each store row needs a store selection and IP.'));
                    return $this->redirect(array('action' => 'edit_kds', $id));
                }
                if (isset($seenStores[$storeId])) {
                    $this->Flash->error(__('Duplicate store selected. Please select each store only once.'));
                    return $this->redirect(array('action' => 'edit_kds', $id));
                }
                $seenStores[$storeId] = true;
                $clean[] = array('id' => $rowId, 'store_id' => $storeId, 'ip' => $ip);
            }

            if (empty($clean)) {
                $this->Flash->error(__('Please add at least 1 store with IP.'));
                return $this->redirect(array('action' => 'edit_kds', $id));
            }

            // Resolve store_name for each store_id
            foreach ($clean as $i => $c) {
                $loc = $this->Location->findById($c['store_id']);
                if (empty($loc['Location']['id'])) {
                    $this->Flash->error(__('Invalid store selected.'));
                    return $this->redirect(array('action' => 'edit_kds', $id));
                }
                $clean[$i]['store_name'] = $loc['Location']['name'];
            }

            // Block DB duplicates: same KDS name + same store must not already exist outside this KDS group
            foreach ($clean as $c) {
                $dupConditions = array(
                    'Pos_printer.name' => $name,
                    'Pos_printer.store_id' => $c['store_id']
                );
                if (!empty($existingIds)) {
                    $dupConditions['NOT'] = array('Pos_printer.id' => $existingIds);
                }
                $duplicate = $this->Pos_printer->find('count', array(
                    'conditions' => $dupConditions,
                    'recursive' => -1
                ));
                if (!empty($duplicate)) {
                    $this->Flash->error(__('This KDS name already exists for store "' . $c['store_name'] . '".'));
                    return $this->redirect(array('action' => 'edit_kds', $id));
                }
            }

            $submittedIds = array();
            $db = $this->Pos_printer->getDataSource();
            $availableExistingIds = $existingIds;

            foreach ($clean as $c) {
                if (!empty($c['id']) && in_array($c['id'], $availableExistingIds)) {
                    // Update existing row
                    $this->Pos_printer->id = $c['id'];
                    $saved = $this->Pos_printer->save(array(
                        'name'       => $name,
                        'store_id'   => $c['store_id'],
                        'store_name' => $c['store_name'],
                        'ip'         => $c['ip'],
                        'kds'        => $groupId,
                        'sync'       => 0,
                        'local_sync' => 0
                    ));
                    if (!$saved) {
                        $this->Flash->error(__('Kds could not be updated. Please, try again.'));
                        return $this->redirect(array('action' => 'edit_kds', $id));
                    }
                    $submittedIds[] = (int)$c['id'];
                    $availableExistingIds = array_diff($availableExistingIds, array($c['id']));
                } else {
                    // Add new row to this KDS group
                    $this->Pos_printer->create();
                    $saved = $this->Pos_printer->save(array(
                        'name'       => $name,
                        'store_id'   => $c['store_id'],
                        'store_name' => $c['store_name'],
                        'ip'         => $c['ip'],
                        'kds'        => $groupId,
                        'status'     => 1,
                        'sync'       => 0,
                        'local_sync' => 0
                    ));
                    if (!$saved) {
                        $this->Flash->error(__('Kds could not be updated. Please, try again.'));
                        return $this->redirect(array('action' => 'edit_kds', $id));
                    }
                    $submittedIds[] = (int)$this->Pos_printer->id;
                }
            }

            // Delete removed rows from this KDS group
            $toDelete = array_diff($existingIds, $submittedIds);
            if (!empty($toDelete)) {
                foreach ($toDelete as $delId) {
                    $delRow = $this->Pos_printer->findById($delId);
                    if (!empty($delRow)) {
                        $delStoreId = $delRow['Pos_printer']['store_id'];
                        $this->Kds_users_station_mapping->deleteAll(array(
                            'station_id' => $groupId,
                            'station_store_id' => $delStoreId
                        ), false);
                    }
                    $this->Pos_printer->delete($delId);
                }
            }

            // Synchronize name and kds group key across all active rows of this group
            if (!empty($submittedIds)) {
                $this->Pos_printer->updateAll(
                    array(
                        'Pos_printer.name'       => $db->value($name, 'string'),
                        'Pos_printer.kds'        => $groupId,
                        'Pos_printer.sync'       => 0,
                        'Pos_printer.local_sync' => 0
                    ),
                    array('Pos_printer.id' => $submittedIds)
                );
            }

            // Keep station_name synchronized in Kds_users_station_mapping
            $this->Kds_users_station_mapping->updateAll(
                array(
                    'Kds_users_station_mapping.station_name' => $db->value($name, 'string'),
                    'Kds_users_station_mapping.sync'         => 0,
                    'Kds_users_station_mapping.local_sync'    => 0
                ),
                array('Kds_users_station_mapping.station_id' => $groupId)
            );

            $this->Flash->success(__('Kds has been updated.'));
            return $this->redirect(array('action' => 'kds'));
        }
    }
    

    public function fetch_pro_options() {
        $this->autoRender = false;
        $this->response->type('json');

        $products = $this->Catalog_product->find('all', [
            'conditions' => ['Catalog_product.package' => 0, 'Catalog_product.status' => 1],
            'fields' => ['Catalog_product.id', 'Catalog_product.name', 'Catalog_product.unit_name', 'Catalog_product.price'],
            'order' => ['Catalog_product.name' => 'asc']
        ]);

        $data = [];
        foreach ($products as $product) {
            $data[] = [
                'id' => $product['Catalog_product']['id'],
                'name' => $product['Catalog_product']['name'],
                'price' => $product['Catalog_product']['price'],
                'unit_name' => $product['Catalog_product']['unit_name']
            ];
        }

        echo json_encode(['response_code' => 1, 'data' => $data]);
        exit;
    }

    public function modifiers() {
        if(!$this->Session->check('Auth.User.id')){
            $this->redirect(array('controller'=>'dashboard','action' => 'login'));
        }

        $this->layout = "users";

        $startDate = date('m/d/Y', strtotime('-12 months'));
        $endDate = date('m/d/Y');

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

        $_shiftRange = $this->getShiftDateRange($startdate, $enddate);
        $startDatetime = $_shiftRange['startDatetime'];
        $endDatetime   = $_shiftRange['endDatetime'];

        $users = $this->Catalog_modifier->query("SELECT * FROM catalog_modifier where created between '$startDatetime' and  '$endDatetime' ORDER BY id desc");
        $yourStartDateValue=$startDate . ' - ' . $endDate;
        $this->set('startdate', $yourStartDateValue);

        $this->set(compact('users'));

    }

    public function create_modifier()
    {
        if(!$this->Session->check('Auth.User.id')){
            $this->redirect(array('controller'=>'dashboard','action' => 'login'));
        }

        $this->layout = "users";

        if ($this->request->is(array('post', 'put'))) {

            $name = $this->data['User']['name'];
            if($name==''){
                $this->Flash->error(__('The Name could not be Blank'));
                return $this->redirect(array('action' => 'create_modifier'));
            }

            $name = $this->data['User']['name'];
            $description = $this->data['User']['description'];
            $food_type = $this->data['User']['food_type'];
            $sort_order = $this->data['User']['sort_order'];
            $price = $this->data['User']['price'];
            $weight = $this->data['User']['weight'];

            $data['Catalog_modifier']=array(
                'name'=>$name,
                'description'=>$description,
                'food_type'=>$food_type,
                'sort_order'=>$sort_order,
                'price'=>$price,
                'weight'=>$weight,
                'sync'=>0,
                'local_sync'=>0
            );

            if($this->Catalog_modifier->saveAll($data['Catalog_modifier']))
            {
                $this->Flash->success(__('Modifier has been saved.'));
                return $this->redirect(array('action' => 'modifiers'));
            } else {
                $this->Flash->error(__('Modifier could not be saved. Please, try again.'));
            }
        }

    }

    public function modifier_change_status()
    {
        $this->autoRender = false;

        if ($this->request->is('post')) {
            $Id = $this->request->data['_id'];

            $this->Catalog_modifier->id = $Id;
            $newStatus = $this->Catalog_modifier->field('status') == 1 ? 0 : 1;

            if ($this->Catalog_modifier->save(['status' => $newStatus,'sync' => 0,'local_sync' => 0])) {
                echo json_encode(['status' => true]);
            } else {
                echo json_encode(['status' => false]);
            }
        }
    }

    public function view_modifier()
    {
        if(!$this->Session->check('Auth.User.id')){
            $this->redirect(array('controller'=>'dashboard','action' => 'login'));
        }
        $this->layout = "users";
        $id=$_REQUEST['id'];

        $products = $this->Catalog_modifier->query("SELECT * FROM catalog_modifier where id=$id");
        $this->set(compact('products'));

    }

    public function modifiers_group()
    {
        if(!$this->Session->check('Auth.User.id')){
            $this->redirect(array('controller'=>'dashboard','action' => 'login'));
        }

        $this->layout = "users";

        $startDate = date('m/d/Y', strtotime('-12 months'));
        $endDate = date('m/d/Y');

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

        $_shiftRange = $this->getShiftDateRange($startdate, $enddate);
        $startDatetime = $_shiftRange['startDatetime'];
        $endDatetime   = $_shiftRange['endDatetime'];

        $users = $this->Catalog_modifier_group->query("SELECT * FROM catalog_modifier_group where  created between '$startDatetime' and  '$endDatetime' ORDER BY id desc");
        $yourStartDateValue=$startDate . ' - ' . $endDate;
        $this->set('startdate', $yourStartDateValue);

        $this->set(compact('users'));

    }

    public function create_modifiers_group()
    {
        if(!$this->Session->check('Auth.User.id')){
            $this->redirect(array('controller'=>'dashboard','action' => 'login'));
        }

        $this->layout = "users";

        if ($this->request->is(array('post', 'put'))) {

            $name = $this->data['User']['name'];
            if($name==''){
                $this->Flash->error(__('The Name could not be Blank'));
                return $this->redirect(array('action' => 'create_modifiers_group'));
            }
            if(isset($this->data['User']['is_default'])){
                $is_default=$this->data['User']['is_default'];
            }else{
                $is_default=0;
            }

            $name = $this->data['User']['name'];
            $description = $this->data['User']['description'];
            $group_type = $this->data['User']['group_type'];
            $sort_order = $this->data['User']['sort_order'];
            $min = $this->data['User']['min']?$this->data['User']['min']:0;
            $max = $this->data['User']['max']?$this->data['User']['max']:0;

            $data['Catalog_modifier_group']=array(
                'name'=>$name,
                'description'=>$description,
                'group_type'=>$group_type,
                'sort_order'=>$sort_order,
                'is_default'=>$is_default,
                'min'=>$min,
                'max'=>$max,
                'sync'=>0,
                'local_sync'=>0
            );

            if($this->Catalog_modifier_group->saveAll($data['Catalog_modifier_group']))
            {
                $last_id = $this->Catalog_modifier_group->getLastInsertID();
                $modifier_ids = $this->request->data['modifier_id'];

                if (!empty($modifier_ids)) {
                    $this->Inventory_linking->updateAll(
                        [
                            'Inventory_linking.sync' => 0,
                            'Inventory_linking.local_sync' => 0
                        ],
                        [
                            'Inventory_linking.parent_id' => $last_id,
                            'Inventory_linking.parent_type' => 'Modifier_group',
                            'Inventory_linking.component_type' => 'Modifier',
                        ]
                    );

                    $linkData = [];

                    foreach ($modifier_ids as $modid) {

                        $modifier = $this->Catalog_modifier->find('first', [
                            'conditions' => ['Catalog_modifier.id' => $modid],
                            'fields' => ['name'],
                            'recursive' => -1
                        ]);

                        $mod_name = $modifier['Catalog_modifier']['name'] ?? '';

                        $data['Inventory_linking']=array(
                            'parent_type'=>'Modifier_group',
                            'parent_id'=>$last_id,
                            'parent_name'=>$name,
                            'component_type'=>'Modifier',
                            'component_id'=>$modid,
                            'component_name'=>$mod_name,
                            'quantity'=>1,
                            'sync'=>0,
                            'local_sync'=>0
                        );

                        if ($this->Inventory_linking->saveAll($data['Inventory_linking'])) {

                        } else {
                            $this->Flash->error(__('Modifiers could not be grouped. Please try again.'));
                        }
                    }

                }
                $this->Flash->success(__('Modifier Group has been saved.'));
                return $this->redirect(array('action' => 'modifiers_group'));
            } else {
                $this->Flash->error(__('Modifier Group could not be saved. Please, try again.'));
            }
        }

    }

    public function modifiers_group_change_status()
    {
        $this->autoRender = false;

        if ($this->request->is('post')) {
            $Id = $this->request->data['_id'];

            $this->Catalog_modifier_group->id = $Id;
            $currentStatus = $this->Catalog_modifier_group->field('status');
            $newStatus = $currentStatus == 1 ? 0 : 1;

            $this->Catalog_modifier_group->begin();

            if ($this->Catalog_modifier_group->save(['status' => $newStatus, 'sync' => 0, 'local_sync' => 0])) {

                $this->Inventory_linking->updateAll(
                    [
                        'Inventory_linking.status' => $newStatus,
                        'Inventory_linking.sync' => 0,
                        'Inventory_linking.local_sync' => 0
                    ],
                    [
                        'Inventory_linking.parent_id' => $Id,
                        'Inventory_linking.parent_type' => 'Modifier_group',
                        'Inventory_linking.component_type' => 'Modifier'
                    ]
                );

                $this->Inventory_linking->updateAll(
                    [
                        'Inventory_linking.status' => $newStatus,
                        'Inventory_linking.sync' => 0,
                        'Inventory_linking.local_sync' => 0
                    ],
                    [
                        'Inventory_linking.component_id' => $Id,
                        'Inventory_linking.parent_type' => 'Product',
                        'Inventory_linking.component_type' => 'Modifier_group'
                    ]
                );

                $this->Catalog_modifier_group->commit();
                echo json_encode(['status' => true]);
            } else {
                $this->Catalog_modifier_group->rollback();
                echo json_encode(['status' => false]);
            }
        }
    }

    public function view_modifiers_group()
    {
        if(!$this->Session->check('Auth.User.id')){
            $this->redirect(array('controller'=>'dashboard','action' => 'login'));
        }
        $this->layout = "users";
        $id=$_REQUEST['id'];

        $products = $this->Catalog_modifier_group->query("SELECT * FROM catalog_modifier_group where id=$id");
        $this->set(compact('products'));

        $modifier_linking = $this->Inventory_linking->query("SELECT * FROM inventory_linking where parent_id=$id and parent_type='Modifier_group' and component_type='Modifier' and status='1'");
        $this->set(compact('modifier_linking'));

    }

    public function fetch_modifiers_options()
    {
        $this->autoRender = false;

        $modifiers = $this->Catalog_modifier->find('all', [
            'conditions' => ['Catalog_modifier.status' => 1],
            'fields' => ['id', 'name', 'price']
        ]);

        $data = array_map(function ($item) {
            return $item['Catalog_modifier'];
        }, $modifiers);

        echo json_encode(['response_code' => 1, 'data' => $data]);
    }

    public function product_modifier()
    {
        if(!$this->Session->check('Auth.User.id')){
            $this->redirect(array('controller'=>'dashboard','action' => 'login'));
        }

        $this->layout = "users";
        $id=$_REQUEST['id'];

        $products = $this->Catalog_product->query("SELECT * FROM catalog_product where id=$id");
        $this->set(compact('products'));

        $modifiers = $this->Inventory_linking->query("SELECT * FROM inventory_linking where parent_id=$id and parent_type='Product' and component_type='Modifier_group' and status=1");
        $this->set(compact('modifiers'));

       if ($this->request->is(['post', 'put'])) {
            $data = $this->request->data['User'];

            $product_id = $data['id'] ?? null;
            $product_name = $data['name'] ?? '';
            $product_unit = $data['unit'] ?? '';
            $product_unit_name = $data['unit_name'] ?? '';

            if (empty($product_id)) {
                $this->Flash->error(__('Product Error'));
                return $this->redirect(['action' => 'product_modifier', '?' => ['id' => $product_id]]);
            }

            $selectedModifiers = [];

            $allModifiers = $this->Catalog_modifier_group->find('list', [
                'fields' => ['id'],
                'conditions' => ['status' => 1],
                'recursive' => -1
            ]);

            foreach ($allModifiers as $modifier_id => $val) {
                if (!empty($data['semi_id_' . $modifier_id])) {
                    $selectedModifiers[] = $data['semi_id_' . $modifier_id];
                }
            }

            $jsModifiers = $_POST['select_sku_name'] ?? [];

            $modifier_ids = array_values(array_unique(array_merge($selectedModifiers, $jsModifiers)));

            $this->Inventory_linking->updateAll(
                [
                    'Inventory_linking.status' => 0,
                    'Inventory_linking.sync' => 0,
                    'Inventory_linking.local_sync' => 0
                ],
                [
                    'Inventory_linking.parent_id' => $product_id,
                    'Inventory_linking.parent_type' => 'Product',
                    'Inventory_linking.component_type' => 'Modifier_group'
                ]
            );

            $linkData = [];

            foreach ($modifier_ids as $mod_id) {
                $mod = $this->Catalog_modifier_group->find('first', [
                    'conditions' => ['id' => $mod_id],
                    'fields' => ['id', 'name'],
                    'recursive' => -1
                ]);

                if (!$mod) continue;

                $linkData[] = [
                    'parent_type'=>'Product',
                    'parent_id'=>$product_id,
                    'parent_name'=>$product_name,
                    'component_type'=>'Modifier_group',
                    'component_id'=>$mod_id,
                    'component_name'=>$mod['Catalog_modifier_group']['name'],
                    'quantity'=>1,
                    'sync'=>0,
                    'local_sync'=>0
                ];
            }

            if (!empty($linkData)) {
                if ($this->Inventory_linking->saveAll($linkData)) {
                    $this->Flash->success(__('Group modifiers have been saved successfully.'));
                } else {
                    $this->Flash->error(__('Some group modifiers could not be saved.'));
                }
            } else {
                $this->Flash->success(__('No modifiers selected. Existing mappings have been cleared.'));
            }

            return $this->redirect(['action' => 'product_list']);
        }

    }

    public function fetch_modifiers_group_options()
    {
        $this->autoRender = false;

        $linkedGroupIds = $this->Inventory_linking->find('list', [
            'fields' => ['Inventory_linking.parent_id', 'Inventory_linking.parent_id'],
            'conditions' => [
                'Inventory_linking.status' => 1,
                'Inventory_linking.parent_type' => 'Modifier_group',
                'Inventory_linking.component_type' => 'Modifier'
            ],
            'recursive' => -1
        ]);

        $modifiers = $this->Catalog_modifier_group->find('all', [
            'conditions' => [
                'Catalog_modifier_group.status' => 1,
                'Catalog_modifier_group.id' => array_keys($linkedGroupIds)
            ],
            'fields' => ['Catalog_modifier_group.id', 'Catalog_modifier_group.name'],
            'order' => ['Catalog_modifier_group.name' => 'ASC']
        ]);

        $data = array_map(function ($item) {
            return $item['Catalog_modifier_group'];
        }, $modifiers);

        echo json_encode(['response_code' => 1, 'data' => $data]);
    }

    public function edit_modifiers()
    {

        if (!$this->Session->check('Auth.User.id')) {
            return $this->redirect(['controller' => 'dashboard', 'action' => 'login']);
        }

        $this->layout = 'users';

        $id = $this->request->query('id') ?? null;

        if (!$id) {
            $this->Flash->error(__('Modifier ID not provided.'));
            return $this->redirect(['action' => 'modifiers']);
        }

        $modifier = $this->Catalog_modifier->findById($id);
        if (!$modifier) {
            $this->Flash->error(__('Modifier not found.'));
            return $this->redirect(['action' => 'modifiers']);
        }

        if ($this->request->is(['post', 'put'])) {
            $this->Catalog_modifier->id = $id;

            $data = $this->request->data;
            $data['Catalog_modifier']['sync'] = 0;
            $data['Catalog_modifier']['local_sync'] = 0;

            if ($this->Catalog_modifier->save($data)) {
                $this->Flash->success(__('Modifier has been updated successfully.'));
                return $this->redirect(['action' => 'modifiers']);
            } else {
                $this->Flash->error(__('Failed to update modifier. Please try again.'));
            }
        }

        $this->request->data = $modifier;
        $this->set(compact('modifier'));
    }

     public function edit_modifier_group()
    {
        $this->layout = 'users';

        if (!$this->Session->check('Auth.User.id')) {
            return $this->redirect(['controller' => 'dashboard', 'action' => 'login']);
        }

        $groupId = $this->request->query('id') ?? null;

        if (!$groupId) {
            $this->Flash->error(__('Invalid Modifier Group ID.'));
            return $this->redirect(['action' => 'modifiers_group']);
        }

        $products = $this->Catalog_modifier_group->find('all', [
            'conditions' => ['Catalog_modifier_group.id' => $groupId],
            'limit' => 1
        ]);
        $modifier_linking = $this->Inventory_linking->find('all', [
            'conditions' => ['parent_id' => $groupId,'parent_type' => 'Modifier_group','component_type' => 'Modifier','status' => '1']
        ]);
        $this->set(compact('products', 'modifier_linking'));

        if ($this->request->is(['post', 'put'])) {
            $data = $this->request->data;
            if($data['Catalog_modifier_group']['group_type']==0){
                $min=$data['Catalog_modifier_group']['min'];
                $max=$data['Catalog_modifier_group']['max'];
            }else{
                $min=0;
                $max=0;
            }
            $groupData = [
                'id' => $data['Catalog_modifier_group']['id'],
                'name' => $data['Catalog_modifier_group']['name'],
                'group_type' => $data['Catalog_modifier_group']['group_type'],
                'description' => $data['Catalog_modifier_group']['description'],
                'sort_order' => $data['Catalog_modifier_group']['sort_order'],
                'min' => $min,
                'max' => $max,
                'sync' => 0,
                'local_sync' => 0,
                'is_default' => $data['Catalog_modifier_group']['is_default']?$data['Catalog_modifier_group']['is_default']:0
            ];

            if ($this->Catalog_modifier_group->save($groupData)) {

                $this->Inventory_linking->updateAll(
                    [
                        'Inventory_linking.status' => 0,
                        'Inventory_linking.sync' => 0,
                        'Inventory_linking.local_sync' => 0
                    ],
                    [
                        'Inventory_linking.parent_id' => $groupId,
                        'Inventory_linking.parent_type' => 'Modifier_group',
                        'Inventory_linking.component_type' => 'Modifier'
                    ]
                );

                $modifierIds = $data['modifier_id'] ?? [];
                $modifierIds = array_unique(array_filter($modifierIds));
                $linkData = [];

                foreach ($modifierIds as $modId) {
                    if (empty($modId)) continue;

                    $mod = $this->Catalog_modifier->find('first', [
                        'conditions' => ['Catalog_modifier.id' => $modId],
                        'fields' => ['name'],
                        'recursive' => -1
                    ]);
                    $modName = $mod['Catalog_modifier']['name'] ?? '';

                    $linkData[] = [
                        'parent_type'=>'Modifier_group',
                        'parent_id'=>$groupId,
                        'parent_name'=>$groupData['name'],
                        'component_type'=>'Modifier',
                        'component_id'=>$modId,
                        'component_name'=>$modName,
                        'quantity'=>1,
                        'sync'=>0,
                        'local_sync'=>0
                    ];
                }

                if (!empty($linkData)) {
                    $this->Inventory_linking->saveAll($linkData);
                }

                $this->Flash->success(__('Modifier Group updated successfully.'));
                return $this->redirect(['action' => 'modifiers_group']);
            } else {
                $this->Flash->error(__('Failed to update Modifier Group.'));
            }
        }
    }

    public function modifier_recipe()
    {
        if(!$this->Session->check('Auth.User.id')){
            $this->redirect(array('controller'=>'dashboard','action' => 'login'));
        }

        $this->layout = "users";
        $id=$_REQUEST['id'];

        $products = $this->Catalog_modifier->query("SELECT * FROM catalog_modifier where id=$id order by name ASC");
        $this->set(compact('products'));

        $recipes = $this->Inventory_linking->query("SELECT * FROM inventory_linking where parent_id=$id and parent_type='Modifier' and component_type='Semifinished' and status=1");
        $skurecipes = $this->Inventory_linking->query("SELECT * FROM inventory_linking where parent_id=$id and parent_type='Modifier' and component_type='Sku' and status=1");
        $this->set(compact('recipes','skurecipes'));

        if ($this->request->is(array('post', 'put'))) {
            $product_id = $this->data['User']['id'];
            $product_name = $this->data['User']['name'];

            $selectedSemifinished=[];
            $semifinishedquantity=[];
            $semi_ids=[];
            $semi_quantities=[];
            $semifinisheds=$this->Inventory_semifinished->find('all',array('conditions'=>array('status'=>1)));
            foreach($semifinisheds as $semifinished){
                $semifinishedid = $semifinished['Inventory_semifinished']['id'];
                if (!empty($this->request->data['User']['semi_id_' . $semifinishedid])) {
                    $selectedSemifinished[] = $this->request->data['User']['semi_id_' . $semifinishedid];
                    $semifinishedquantity[]=$this->request->data['User']['semi_quantity_' . $semifinishedid];
                }
            }
            if(isset($_POST['select_semi_name'])){
                $semi_ids=$_POST['select_semi_name'];
                $semi_quantities=$_POST['semi_quantity'];
            }
            $mergedArraysemi = array_merge($selectedSemifinished, $semi_ids);
            $uniqueArraysemi = array_unique($mergedArraysemi);
            $semi_ids = array_values($uniqueArraysemi);

            $mergedArraysemi1 = array_merge($semifinishedquantity, $semi_quantities);
            $semi_quantities = array_values($mergedArraysemi1);

            if(isset($semi_ids)){
                $this->Inventory_linking->updateAll(
                    [
                        'Inventory_linking.status' => 0,
                        'Inventory_linking.sync' => 0,
                        'Inventory_linking.local_sync' => 0
                    ],
                    [
                        'Inventory_linking.parent_id' => $product_id,
                        'Inventory_linking.parent_type' => 'Modifier',
                        'Inventory_linking.component_type' => 'Semifinished'
                    ]
                );

                for ($i = 0; $i < count($semi_ids); $i++) {
                    $semi_id = $semi_ids[$i];
                    $semi_quantity = $semi_quantities[$i];

                    $seminame=$this->Inventory_semifinished->find('first',array('conditions'=>array('id'=>$semi_id)));
                    $semi_name=$seminame['Inventory_semifinished']['name'];
                    $semi_unit=$seminame['Inventory_semifinished']['unit'];
                    $semi_unit_name=$seminame['Inventory_semifinished']['unit_name'];

                    $data['Inventory_linking']=array(
                        'parent_type'=>'Modifier',
                        'parent_id'=>$product_id,
                        'parent_name'=>$product_name,
                        'component_type'=>'Semifinished',
                        'component_id'=>$semi_id,
                        'component_name'=>$semi_name,
                        'quantity'=>$semi_quantity,
                        'component_unit'=>$semi_unit,
                        'component_unit_name'=>$semi_unit_name,
                        'sync'=>0,
                        'local_sync'=>0
                    );

                    if($this->Inventory_linking->saveAll($data['Inventory_linking']))
                    {

                    }else{
                        $this->Flash->error(__('Semifinished mapping could not be saved. Please, try again.'));
                    }
                }
            }

            $selectedSku=[];
            $skuquantity=[];
            $sku_ids=[];
            $sku_quantities=[];
            $skus=$this->Inventory_sku->find('all',array('conditions'=>array('status'=>1)));
            foreach($skus as $sku){
                $skuid = $sku['Inventory_sku']['id'];
                if (!empty($this->request->data['User']['sku_id_' . $skuid])) {
                    $selectedSku[] = $this->request->data['User']['sku_id_' . $skuid];
                    $skuquantity[]=$this->request->data['User']['sku_quantity_' . $skuid];
                }
            }
            if(isset($_POST['select_sku_name'])){
                $sku_ids=$_POST['select_sku_name'];
                $sku_quantities=$_POST['sku_quantity'];
            }
            $mergedArraysku = array_merge($selectedSku, $sku_ids);
            $uniqueArraysku = array_unique($mergedArraysku);
            $sku_ids = array_values($uniqueArraysku);

            $mergedArraysku1 = array_merge($skuquantity, $sku_quantities);
            $sku_quantities = array_values($mergedArraysku1);

            if(isset($sku_ids)){
                $this->Inventory_linking->updateAll(
                    [
                        'Inventory_linking.status' => 0,
                        'Inventory_linking.sync' => 0,
                        'Inventory_linking.local_sync' => 0
                    ],
                    [
                        'Inventory_linking.parent_id' => $product_id,
                        'Inventory_linking.parent_type' => 'Modifier',
                        'Inventory_linking.component_type' => 'Sku'
                    ]
                );

                for ($i = 0; $i < count($sku_ids); $i++) {
                    $sku_id = $sku_ids[$i];
                    $sku_quantity = $sku_quantities[$i];

                    $skuname=$this->Inventory_sku->find('first',array('conditions'=>array('id'=>$sku_id)));
                    $sku_name=$skuname['Inventory_sku']['name'];
                    $sku_unit=$skuname['Inventory_sku']['unit'];
                    $sku_unit_name=$skuname['Inventory_sku']['unit_name'];

                    $data['Inventory_linking']=array(
                        'parent_type'=>'Modifier',
                        'parent_id'=>$product_id,
                        'parent_name'=>$product_name,
                        'component_type'=>'Sku',
                        'component_id'=>$sku_id,
                        'component_name'=>$sku_name,
                        'quantity'=>$sku_quantity,
                        'component_unit'=>$sku_unit,
                        'component_unit_name'=>$sku_unit_name,
                        'sync'=>0,
                        'local_sync'=>0
                    );

                    if($this->Inventory_linking->saveAll($data['Inventory_linking']))
                    {

                    }else{
                        $this->Flash->error(__('Sku mapping could not be saved. Please, try again.'));
                    }
                }
            }

            $this->_run_food_cost_recalc();
            $this->Flash->success(__('Recipe has been saved.'));
            return $this->redirect(array('action' => 'modifiers'));
        }

    }

    public function inv_category_list()
    {
        if(!$this->Session->check('Auth.User.id')){
            $this->redirect(array('controller'=>'dashboard','action' => 'login'));
        }

        $this->layout = "users";

        $startDate = date('m/d/Y', strtotime('-12 months'));
        $endDate = date('m/d/Y');

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

        $_shiftRange = $this->getShiftDateRange($startdate, $enddate);
        $startDatetime = $_shiftRange['startDatetime'];
        $endDatetime   = $_shiftRange['endDatetime'];

        $users = $this->Inventory_category->find('all', array(
            'conditions' => array('created BETWEEN ? AND ?' => array($startDatetime, $endDatetime)
            ),
            'order' => array(
                'id' => 'DESC'
            )
        ));
        $yourStartDateValue=$startDate . ' - ' . $endDate;
        $this->set('startdate', $yourStartDateValue);

        $this->set(compact('users'));

    }

    public function inv_subcategory_list()
    {
        if(!$this->Session->check('Auth.User.id')){
            $this->redirect(array('controller'=>'dashboard','action' => 'login'));
        }

        $this->layout = "users";

        $startDate = date('m/d/Y', strtotime('-12 months'));
        $endDate = date('m/d/Y');

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

        $_shiftRange = $this->getShiftDateRange($startdate, $enddate);
        $startDatetime = $_shiftRange['startDatetime'];
        $endDatetime   = $_shiftRange['endDatetime'];

        $users = $this->Inventory_subcategory->query("SELECT *,(SELECT name FROM inventory_category where id=inventory_subcategory.cat_id) as cat_name FROM inventory_subcategory where created between '$startDatetime' and  '$endDatetime' ORDER BY id desc");
        $yourStartDateValue=$startDate . ' - ' . $endDate;
        $this->set('startdate', $yourStartDateValue);

        $this->set(compact('users'));

    }

    public function inv_create_category()
    {
        if(!$this->Session->check('Auth.User.id')){
            $this->redirect(array('controller'=>'dashboard','action' => 'login'));
        }

        $this->layout = "users";

        if ($this->request->is(array('post', 'put'))) {

            $name = $this->data['User']['name'];
            if($name==''){
                $this->Flash->error(__('The Name could not be Blank'));
                return $this->redirect(array('action' => 'create_category'));
            }

            if(isset($_POST['subcat'])){
                $is_subcat=1;
            }else{
                $is_subcat=0;
            }

            $image=$this->data['User']['image'];
            if (!empty($image['name'])) {
                $uploadPath = WWW_ROOT . 'uploads' . DS . 'category_images' . DS;
                $file=basename(time().$image['name']);
                $file = preg_replace('/[^A-Za-z0-9_\-\.]/', '_', $file);
                $uploadFile = $uploadPath . $file;

                if (move_uploaded_file($image['tmp_name'], $uploadFile)) {
                    $filename=$file;
                }else{
                    $filename='';
                }
            }

            $data['Inventory_category']=array(
                'name'=>$name,
                'is_subcategory'=>$is_subcat,
                'image'=>$filename,
                'sync'=>0,
                'local_sync'=>0
            );

            if($this->Inventory_category->saveAll($data['Inventory_category']))
            {
                $lastInsertedID = $this->Inventory_category->id;
                if(isset($_POST['subcat'])){
                    $subcats = $_POST['subcat'];
                    foreach ($subcats as $subcat) {
                        $data['Inventory_subcategory']=array(
                            'cat_id'=>$lastInsertedID,
                            'name'=>$subcat,
                            'sync'=>0,
                            'local_sync'=>0
                        );

                        if($this->Inventory_subcategory->saveAll($data['Inventory_subcategory']))
                        {

                        }else{
                            $this->Flash->error(__('Sub category could not be saved. Please, try again.'));
                        }
                    }
                }

                $this->Flash->success(__('Category has been saved.'));
                return $this->redirect(array('action' => 'inv_category_list'));
            } else {
                $this->Flash->error(__('Category could not be saved. Please, try again.'));
            }
        }
    }

    public function inv_edit_category()
    {
        if(!$this->Session->check('Auth.User.id')){
            $this->redirect(array('controller'=>'dashboard','action' => 'login'));
        }

        $this->layout = "users";
        $id=$_REQUEST['id'];

        $categories = $this->Inventory_category->query("SELECT * FROM inventory_category where id=$id");
        $this->set(compact('categories'));

        $subcategories=$this->Inventory_subcategory->query("SELECT * FROM inventory_subcategory where cat_id=$id and status=1");
        $this->set(compact('subcategories'));

        if ($this->request->is(array('post', 'put'))) {

            $id = $this->data['User']['id'];
            $name = $this->data['User']['name'];
            if($name==''){
                $this->Flash->error(__('The Name could not be Blank'));
                return $this->redirect(array('action' => 'inv_edit_category?id='.$id));
            }

            $image=$this->data['User']['image'];
            if (!empty($image['name'])) {
                $uploadPath = WWW_ROOT . 'uploads' . DS . 'category_images' . DS;
                $file=basename(time().$image['name']);
                $file = preg_replace('/[^A-Za-z0-9_\-\.]/', '_', $file);
                $uploadFile = $uploadPath . $file;

                if (move_uploaded_file($image['tmp_name'], $uploadFile)) {
                    $filename=$file;
                }else{
                    $filename='';
                }
                $this->Inventory_category->query("UPDATE `inventory_category` SET image='$filename',sync = 0,local_sync=0 WHERE id=$id");
            }
            $this->Inventory_category->query("UPDATE `inventory_category` SET name='$name',sync = 0,local_sync=0 WHERE id=$id");

            $lastInsertedID = $id;
            if(isset($_POST['subcat'])){
                $subcats = $_POST['subcat'];
                foreach ($subcats as $subcat) {
                    $data['Inventory_subcategory']=array(
                        'cat_id'=>$lastInsertedID,
                        'name'=>$subcat,
                        'sync'=>0,
                        'local_sync'=>0
                    );

                    if($this->Inventory_subcategory->saveAll($data['Inventory_subcategory']))
                    {

                    }else{
                        $this->Flash->error(__('Sub category could not be saved. Please, try again.'));
                    }
                }
            }

            $subcategorychk=$this->Inventory_subcategory->query("SELECT * FROM inventory_subcategory where cat_id=$id and status=1");
            if($subcategorychk){
                $this->Inventory_category->query("UPDATE `inventory_category` SET is_subcategory='1',sync = 0,local_sync=0 WHERE id=$id");
            }else{
                $this->Inventory_category->query("UPDATE `inventory_category` SET is_subcategory='0',sync = 0,local_sync=0 WHERE id=$id");
            }

            $this->Flash->success(__('Category has been Updated.'));
            return $this->redirect(array('action' => 'inv_category_list'));

        }
    }

    public function inv_remove_subcategory()
    {
        $this->autoRender = false;

        if ($this->request->is('post')) {
            $Id = $this->request->data['_id'];

            $this->Inventory_subcategory->id = $Id;
            if ($this->Inventory_subcategory->save(['status' => 0,'sync'=>0,'local_sync'=>0])) {

                $categoryid = $this->Inventory_subcategory->query("SELECT cat_id FROM inventory_subcategory where id=$Id");
                $cat_id=$categoryid['0']['inventory_subcategory']['cat_id'];

                $subcategorychk=$this->Inventory_subcategory->query("SELECT * FROM inventory_subcategory where cat_id=$cat_id and status=1");
                if($subcategorychk){
                    $this->Inventory_category->query("UPDATE inventory_category SET is_subcategory=1,sync = 0,local_sync=0 WHERE id=$cat_id");
                }else{
                    $this->Inventory_category->query("UPDATE inventory_category SET is_subcategory=0,sync = 0,local_sync=0 WHERE id=$cat_id");
                }

                echo json_encode(['status' => true]);
            } else {
                echo json_encode(['status' => false]);
            }
        }
    }

    public function inv_create_subcategory()
    {
        if(!$this->Session->check('Auth.User.id')){
            $this->redirect(array('controller'=>'dashboard','action' => 'login'));
        }

        $this->layout = "users";

        if ($this->request->is(array('post', 'put'))) {
            $subcategoriesname=$_POST['subcat'];
            $categoryid = $this->data['User']['category'];
            if($categoryid==''){
                $this->Flash->error(__('Select Category'));
                return $this->redirect(array('action' => 'inv_create_subcategory'));
            }

            $image=$this->data['User']['image'];
            if (!empty($image['name'])) {
                $uploadPath = WWW_ROOT . 'uploads' . DS . 'category_images' . DS;
                $file=basename(time().$image['name']);
                $file = preg_replace('/[^A-Za-z0-9_\-\.]/', '_', $file);
                $uploadFile = $uploadPath . $file;

                if (move_uploaded_file($image['tmp_name'], $uploadFile)) {
                    $filename=$file;
                }else{
                    $filename='';
                }
            }

            foreach($subcategoriesname as $subcategory){
                $data['Inventory_subcategory']=array(
                    'name'=>$subcategory,
                    'cat_id'=>$categoryid,
                    'image'=>$filename,
                    'sync'=>0,
                    'local_sync'=>0
                );

                if($this->Inventory_subcategory->saveAll($data['Inventory_subcategory']))
                {
                    $savechk=true;

                }else{
                    $savechk=false;
                }

            }

            if($savechk){
                $this->Inventory_category->query("UPDATE `inventory_category` SET is_subcategory='1',sync = 0,local_sync=0 WHERE id=$categoryid");

                $this->Flash->success(__('Sub Category has been saved.'));
                return $this->redirect(array('action' => 'inv_subcategory_list'));
            } else {
                $this->Flash->error(__('Some sub Category could not be saved. Please, try again.'));
                return $this->redirect(array('action' => 'inv_subcategory_list'));
            }
        }

        $categories=$this->Inventory_category->find('all',array('conditions'=>array('status'=>1)));
        $this->set(compact('categories'));

    }

    public function inv_category_change_status()
    {
        $this->autoRender = false;

        if ($this->request->is('post')) {
            $Id = $this->request->data['_id'];

            $this->Inventory_category->id = $Id;
            $newStatus = $this->Inventory_category->field('status') == 1 ? 0 : 1;

            if ($this->Inventory_category->save(['status' => $newStatus,'sync' => 0,'local_sync' => 0])) {
                $this->Inventory_subcategory->updateAll(
                    [
                        'Inventory_subcategory.status' => $newStatus,
                        'Inventory_subcategory.sync' => 0,
                        'Inventory_subcategory.local_sync' => 0
                    ],
                    ['Inventory_subcategory.cat_id' => $Id]
                );

                echo json_encode(['status' => true]);
            } else {
                echo json_encode(['status' => false]);
            }
        }
    }

    public function inv_subcategory_change_status()
    {
        $this->autoRender = false;

        if ($this->request->is('post')) {
            $Id = $this->request->data['_id'];

            $this->Inventory_subcategory->id = $Id;
            $newStatus = $this->Inventory_subcategory->field('status') == 1 ? 0 : 1;

            if ($this->Inventory_subcategory->save(['status' => $newStatus,'sync' => 0,'local_sync' => 0])) {
                echo json_encode(['status' => true]);
            } else {
                echo json_encode(['status' => false]);
            }
        }
    }

    public function inv_view_category()
    {
        if(!$this->Session->check('Auth.User.id')){
            $this->redirect(array('controller'=>'dashboard','action' => 'login'));
        }

        $this->layout = "users";
        $id=$_REQUEST['id'];

        $categories = $this->Inventory_category->query("SELECT * FROM inventory_category where id=$id");
        $this->set(compact('categories'));

        $subcategories=$this->Inventory_subcategory->query("SELECT * FROM inventory_subcategory where cat_id=$id and status=1");
        $this->set(compact('subcategories'));
    }

    public function inv_edit_subcategory()
    {
        if(!$this->Session->check('Auth.User.id')){
            $this->redirect(array('controller'=>'dashboard','action' => 'login'));
        }

        $this->layout = "users";
        $id=$_REQUEST['id'];

        $subcategories=$this->Inventory_subcategory->query("SELECT *,(SELECT name FROM `inventory_category` WHERE id=inventory_subcategory.cat_id) as catname FROM inventory_subcategory where id=$id");
        $this->set(compact('subcategories'));

        $categories=$this->Inventory_category->find('all',array('conditions'=>array('status'=>1),'order' => array(
                'name' => 'asc'
            )));
        $this->set(compact('categories'));

        if ($this->request->is(array('post', 'put'))) {

            $id = $this->data['User']['id'];
            $name = $this->data['User']['name'];
            if($name==''){
                $this->Flash->error(__('The Name could not be Blank'));
                return $this->redirect(array('action' => 'inv_edit_subcategory?id='.$id));
            }

            $image=$this->data['User']['image'];
            if (!empty($image['name'])) {
                $uploadPath = WWW_ROOT . 'uploads' . DS . 'category_images' . DS;
                $file=basename(time().$image['name']);
                $file = preg_replace('/[^A-Za-z0-9_\-\.]/', '_', $file);
                $uploadFile = $uploadPath . $file;

                if (move_uploaded_file($image['tmp_name'], $uploadFile)) {
                    $filename=$file;
                }else{
                    $filename='';
                }
                $this->Inventory_subcategory->query("UPDATE `inventory_subcategory` SET image='$filename',sync = 0,local_sync=0 WHERE id=$id");
            }
            $category=$this->data['User']['category'];

            $categoryid = $this->Inventory_subcategory->query("SELECT cat_id FROM inventory_subcategory where id=$id");
            $cat_id=$categoryid['0']['inventory_subcategory']['cat_id'];

            $this->Inventory_subcategory->query("UPDATE `inventory_subcategory` SET name='$name',cat_id=$category,sync = 0,local_sync=0 WHERE id=$id");
            $this->Inventory_category->query("UPDATE `inventory_category` SET is_subcategory=1,sync = 0,local_sync=0 WHERE id=$category");

            $subcategorychk=$this->Inventory_subcategory->query("SELECT * FROM inventory_subcategory where cat_id=$cat_id and status=1");
            if($subcategorychk){
                $this->Inventory_category->query("UPDATE inventory_category SET is_subcategory=1,sync = 0,local_sync=0 WHERE id=$cat_id");
            }else{
                $this->Inventory_category->query("UPDATE inventory_category SET is_subcategory=0,sync = 0,local_sync=0 WHERE id=$cat_id");
            }

            $this->Flash->success(__('Sub Category has been Updated.'));
            return $this->redirect(array('action' => 'inv_subcategory_list'));

        }

    }

    public function inv_view_subcategory()
    {
        if(!$this->Session->check('Auth.User.id')){
            $this->redirect(array('controller'=>'dashboard','action' => 'login'));
        }

        $this->layout = "users";
        $id=$_REQUEST['id'];

        $categories=$this->Inventory_subcategory->query("SELECT *,(SELECT name FROM `inventory_category` WHERE id=inventory_subcategory.cat_id) as catname FROM inventory_subcategory where id=$id");
        $this->set(compact('categories'));
    }

    public function indent()
    {
        if (!$this->Session->check('Auth.User.id')) {
            return $this->redirect(['controller' => 'dashboard', 'action' => 'login']);
        }

        $this->layout = "users";

        $startDate = date('m/d/Y', strtotime('-1 month'));
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

        $_shiftRange = $this->getShiftDateRange($startdate, $enddate);
        $startDatetime = $_shiftRange['startDatetime'];
        $endDatetime   = $_shiftRange['endDatetime'];

        $locationId = $this->Session->read('Auth.User.location_ids');
        $userRole = $this->Session->read('Auth.User.role');

        $conditions = "created BETWEEN '$startDatetime' and  '$endDatetime'";
        if ($userRole !== 'admin') {
            $locationIds = implode(',', array_map('trim', explode(',', $locationId)));
            $conditions .= " AND warehouse_id IN ($locationIds)";
        }

        $submittedIndents = $this->Inventory_indent->query("
            SELECT * FROM inventory_indent
            WHERE $conditions AND status = 1
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

        $yourStartDateValue = $startDate . ' - ' . $endDate;
        $this->set('startdate', $yourStartDateValue);

        $newconditions='';
        if ($userRole !== 'admin') {
            $newconditions .= " warehouse_id = '$locationId' and";
        }
        $submitted_indents = $this->Inventory_indent->query("
            SELECT count(id) as total FROM inventory_indent
            WHERE $newconditions status IN (1)
        ");
        $submitted_indent=$submitted_indents[0][0]['total'];

        $approved_indents = $this->Inventory_indent->query("
            SELECT count(id) as total FROM inventory_indent
            WHERE $newconditions status = 2
        ");
        $approved_indent=$approved_indents[0][0]['total'];

        $processed_indents = $this->Inventory_indent->query("
            SELECT count(id) as total FROM inventory_indent
            WHERE $newconditions status = 3
        ");
        $processed_indent=$processed_indents[0][0]['total'];

        $stockedin_indents = $this->Inventory_indent->query("
            SELECT count(id) as total FROM inventory_indent
            WHERE $newconditions status = 4
        ");
        $stockedin_indent=$stockedin_indents[0][0]['total'];
        $this->set(compact('submittedIndents', 'approvedIndents', 'processedIndents','submitted_indent','approved_indent','processed_indent','stockedin_indent'));
    }

    public function approve_indent()
    {
        if (!$this->Session->check('Auth.User.id')) {
            return $this->redirect(['controller' => 'dashboard', 'action' => 'login']);
        }

        $this->layout = "users";
        $id = $_REQUEST['id'];

        $user = "
            SELECT
                *
            FROM
                inventory_indent_items
            WHERE
                inventory_indent_items.indent_id = $id
                AND inventory_indent_items.sku_quantity > 0
                AND inventory_indent_items.status = 1
            ORDER BY
                inventory_indent_items.sku_name DESC
        ";

        $users = $this->Inventory_indent_items->query($user);

        $indentData = $this->Inventory_indent->find('first', [
            'conditions' => ['indent_id' => $id],
            'recursive' => -1
        ]);

        $selected_location_id = $indentData['Inventory_indent']['location_id'];
        $selected_expiry = $indentData['Inventory_indent']['expiry'];

        $this->set(compact('users', 'id', 'selected_location_id'));
    }

    public function update_approve_indent()
    {
        $this->autoRender = false;

        $userId = $this->Session->read('Auth.User.id');
        $userName = $this->Session->read('Auth.User.name');

        if (isset($_POST['checkedData'])) {
            $checkeddatas = json_decode($_POST['checkedData'], true);
            $indentId = $_POST['indent_id'];
            $expiry = $_POST['expiry'];
            $remarks = $_POST['remarks'];
            $vehicle = $_POST['vehicle'];
            $location_id = $_POST['location_id'] ?? null;

            $amount = 0;
            $success = false;

            foreach ($checkeddatas as $checkeddata) {
                $skuId = $checkeddata['id'];

                $price = floatval($checkeddata['price']);
                $approvedQty = $checkeddata['sku_quantity'];
                $item_type = isset($checkeddata['item_type']) ? $checkeddata['item_type'] : 'sku';

                if ($item_type == 'semifinished') {
                    $product_taxes = $this->Inventory_semifinished_tax->query("
                        SELECT SUM(tax_percentage) AS total_tax
                        FROM inventory_semifinished_tax
                        WHERE product_id='$skuId' AND status = 1
                    ");
                } else {
                    $product_taxes = $this->Inventory_sku_tax->query("
                        SELECT SUM(tax_percentage) AS total_tax
                        FROM inventory_sku_tax
                        WHERE product_id='$skuId' AND status = 1
                    ");
                }
                $total_tax = isset($product_taxes[0][0]['total_tax']) ? round($product_taxes[0][0]['total_tax'], 2) : 0;
                $tax_value = round(($price * $approvedQty) * ($total_tax / 100), 2);
                $skuamount = round(($price * $approvedQty) + $tax_value, 2);

                $this->Inventory_indent_items->updateAll(
                    [
                        'approved_price' => $price,
                        'approved_quantity' => $approvedQty,
                        'approved_tax_value' => $tax_value,
                        'approved_amount' => $skuamount,
                        'sync' => 0,
                        'local_sync' => 0,
                        'status'=> 2
                    ],
                    [
                        'indent_id' => $indentId,
                        'sku_id' => $skuId,
                        'status' => 1
                    ]
                );

                $amount += $skuamount;
                $success = true;
            }

            if ($success) {
                $vehicle_value = $vehicle ? "'$vehicle'" : "''";
                $remarks_value = $remarks ? "'$remarks'" : "''";
                $expiry_value = $expiry ? "'$expiry'" : "''";
                $currentDate = date('Y-m-d H:i:s');

                $this->Inventory_indent->updateAll(
                    [
                        'approved_amount' => $amount,
                        'vehicle_no' => $vehicle_value,
                        'remarks' => $remarks_value,
                        'approved_selected_date' => $expiry_value,
                        'approved_date' => "'$currentDate'",
                        'status' => 2,
                        'sync' => 0,
                        'local_sync' => 0
                    ],
                    ['indent_id' => $indentId,
                        'status' => 1]
                );

                echo json_encode(['response_code' => 1, 'data' => 'Approved']);
            } else {
                echo json_encode(['response_code' => 0, 'data' => 'Indent items could not be updated.']);
            }
        }
    }

    public function process_indent()
    {
        if (!$this->Session->check('Auth.User.id')) {
            return $this->redirect(['controller' => 'outlet', 'action' => 'login']);
        }

        $this->layout = "users";
        $id = $_REQUEST['id'];

        $user = "
            SELECT
                *
            FROM
                inventory_indent_items
            WHERE
                inventory_indent_items.indent_id = $id
                AND inventory_indent_items.sku_quantity > 0
                AND inventory_indent_items.status = 2
            ORDER BY
                inventory_indent_items.sku_name DESC
        ";

        $users = $this->Inventory_indent_items->query($user);

        $indentData = $this->Inventory_indent->find('first', [
            'conditions' => ['indent_id' => $id],
            'recursive' => -1
        ]);

        $selected_location_id = $indentData['Inventory_indent']['location_id'];
        $selected_expiry = $indentData['Inventory_indent']['expiry'];

        $this->set(compact('users', 'id', 'selected_location_id'));
    }

    public function update_process_indent()
    {
        $this->autoRender = false;

        $userId = $this->Session->read('Auth.User.id');
        $locationId = $this->Session->read('Auth.User.location_id');
        $userName = $this->Session->read('Auth.User.name');

        if (isset($_POST['checkedData'])) {
            $checkeddatas = json_decode($_POST['checkedData'], true);
            $indentId = $_POST['indent_id'];
            $location_id = $_POST['location_id'] ?? null;

            $amount = 0;
            $success = false;

            do {
                $consignment_id = rand(1, 9);
                for ($i = 1; $i < 10; $i++) {
                    $consignment_id .= rand(0, 9);
                }

                $existing = $this->Inventory_indent->find('first', [
                    'conditions' => ['consignment_id' => $consignment_id],
                    'recursive' => -1
                ]);
            } while ($existing);

            $year = date('Y') + 501;

            $prefix = 'TGI' . $year;

            $latest = $this->Inventory_indent->find('first', [
                'fields' => ['serial_number'],
                'conditions' => ['Inventory_indent.serial_number LIKE' => $prefix . '%'],
                'order' => ['Inventory_indent.id' => 'DESC']
            ]);

            $nextNumber = 1;
            if (!empty($latest) && !empty($latest['Inventory_indent']['serial_number'])) {
                $lastCode = $latest['Inventory_indent']['serial_number'];
                $numberPart = (int)substr($lastCode, strlen($prefix));
                $nextNumber = $numberPart + 1;
            }

            $serial_number = $prefix . str_pad($nextNumber, 5, '0', STR_PAD_LEFT);

            foreach ($checkeddatas as $checkeddata) {
                $skuId = $checkeddata['id'];
                $approvedQty = $checkeddata['sku_quantity'];
                $price = isset($checkeddata['price']) ? floatval($checkeddata['price']) : 0;
                $item_type = isset($checkeddata['item_type']) ? $checkeddata['item_type'] : 'sku';

                if ($item_type == 'semifinished') {
                    $product_taxes = $this->Inventory_semifinished_tax->query("
                        SELECT SUM(tax_percentage) AS total_tax
                        FROM inventory_semifinished_tax
                        WHERE product_id='$skuId' AND status = 1
                    ");
                } else {
                    $product_taxes = $this->Inventory_sku_tax->query("
                        SELECT SUM(tax_percentage) AS total_tax
                        FROM inventory_sku_tax
                        WHERE product_id='$skuId' AND status = 1
                    ");
                }
                $total_tax = isset($product_taxes[0][0]['total_tax']) ? round($product_taxes[0][0]['total_tax'], 2) : 0;
                $tax_value = round(($price * $approvedQty) * ($total_tax / 100), 2);
                $skuamount = round(($price * $approvedQty) + $tax_value, 2);

                $this->Inventory_indent_items->updateAll(
                    [
                        'processed_quantity' => $approvedQty,
                        'consignment_id' => $consignment_id,
                        'processed_tax_value' => $tax_value,
                        'processed_amount' => $skuamount,
                        'sync' => 0,
                        'local_sync' => 0,
                        'status' => 3
                    ],
                    [
                        'indent_id' => $indentId,
                        'sku_id' => $skuId,
                        'status' => 2
                    ]
                );

                $amount += ($skuamount);

                if ($item_type == 'semifinished') {

                    $sku = $this->Inventory_semifinished->find('first', [
                        'conditions' => ['Inventory_semifinished.id' => $skuId],
                        'recursive' => -1
                    ]);

                    $ledgerprevdetails = $this->Stock_ledger->find('first', [
                        'conditions' => ['sku_id' => $skuId,'type'=>'DC','item_type'=>'semifinished','store_id'=>$locationId],
                        'fields' => ['actual_balance'],
                        'order' => ['id DESC'],
                        'recursive' => -1
                    ]);

                    $ledger_prev_quantity = !empty($ledgerprevdetails['Stock_ledger']['actual_balance'])
                        ? $ledgerprevdetails['Stock_ledger']['actual_balance']
                        : 0;
                    $newQuantity =  $ledger_prev_quantity-$approvedQty;

                    $ledgerData['Stock_ledger'] = [
                            'store_id'        => $locationId,
                            'sku_id'          => $skuId,
                            'sku_name'        => $sku['Inventory_semifinished']['name'],
                            'sku_price'       => $sku['Inventory_semifinished']['price'],
                            'unit_id'         => $sku['Inventory_semifinished']['unit'],
                            'unit_name'       => $sku['Inventory_semifinished']['unit_name'],
                            'record_id'       => $indentId,
                            'record_type'     => 'process_indent',
                            'quantity'        => $approvedQty,
                            'balance_stock'   => $newQuantity,
                            'actual_balance'  => $newQuantity,
                            'quantity_type'   => 'debit',
                            'item_type'       => 'semifinished',
                            'type'            => 'DC',
                            'created'         => date('Y-m-d H:i:s'),
                            'sync'=>0,
                            'local_sync'=>0
                    ];
                    $this->Stock_ledger->saveAll($ledgerData['Stock_ledger']);

                    $ingredients = $this->Inventory_linking->find('all', [
                        'conditions' => ['parent_id' => $skuId,'parent_type' => 'Semifinished','component_type' => 'Sku', 'status' => 1]
                    ]);

                    foreach ($ingredients as $ingredient) {
                        $sku_id = $ingredient['Inventory_linking']['component_id'];
                        $sku_quantity= $ingredient['Inventory_linking']['quantity']*$approvedQty;

                        $ledgerprevdetails = $this->Stock_ledger->find('first', [
                            'conditions' => ['sku_id' => $sku_id,'store_id' =>$locationId,'item_type'=>'SKU' ,'type' =>'DC' ],
                            'fields' => ['actual_balance'],
                            'order' => ['id DESC'],
                            'recursive' => -1
                        ]);

                        $ledger_prev_quantity = !empty($ledgerprevdetails['Stock_ledger']['actual_balance'])
                            ? $ledgerprevdetails['Stock_ledger']['actual_balance']
                            : 0;

                        $newQuantity = $ledger_prev_quantity-$sku_quantity;

                        $sku = $this->Inventory_sku->find('first', [
                            'conditions' => ['Inventory_sku.id' => $sku_id],
                            'recursive' => -1
                        ]);

                        $ledgerData['Stock_ledger'] = [
                                'store_id'        => $locationId,
                                'sku_id'          => $ingredient['Inventory_linking']['component_id'],
                                'sku_name'        => $ingredient['Inventory_linking']['component_name'],
                                'sku_price'       => $sku['Inventory_sku']['price'],
                                'unit_id'         => $ingredient['Inventory_linking']['component_unit'],
                                'unit_name'       => $ingredient['Inventory_linking']['component_unit_name'],
                                'record_id'       => $indentId,
                                'record_type'     => 'process_indent',
                                'quantity'        => $sku_quantity,
                                'balance_stock'   => $newQuantity,
                                'actual_balance'  => $newQuantity,
                                'quantity_type'   => 'debit',
                                'item_type'       => 'SKU',
                                'type'            => 'DC',
                                'created'         => date('Y-m-d H:i:s'),
                                'sync'=>0,
                                'local_sync'=>0
                        ];
                        if(!$this->Stock_ledger->saveAll($ledgerData['Stock_ledger'])){
                            $success=false;
                        }
                    }
                }else{

                    $sku = $this->Inventory_sku->find('first', [
                        'conditions' => ['Inventory_sku.id' => $skuId],
                        'recursive' => -1
                    ]);

                    $ledgerprevdetails = $this->Stock_ledger->find('first', [
                        'conditions' => ['sku_id' => $skuId,'store_id' =>$locationId,'item_type'=>'SKU' ,'type' =>'DC' ],
                        'fields' => ['actual_balance'],
                        'order' => ['id DESC'],
                        'recursive' => -1
                    ]);

                    $ledger_prev_quantity = !empty($ledgerprevdetails['Stock_ledger']['actual_balance'])
                        ? $ledgerprevdetails['Stock_ledger']['actual_balance']
                        : 0;

                    $newQuantity = $ledger_prev_quantity-$approvedQty;

                    $ledgerData['Stock_ledger'] = [
                            'store_id'        => $locationId,
                            'sku_id'          => $sku['Inventory_sku']['id'],
                            'sku_name'        => $sku['Inventory_sku']['name'],
                            'sku_price'       => $sku['Inventory_sku']['price'],
                            'unit_id'         => $sku['Inventory_sku']['unit'],
                            'unit_name'       => $sku['Inventory_sku']['unit_name'],
                            'record_id'       => $indentId,
                            'record_type'     => 'process_indent',
                            'quantity'        => $approvedQty,
                            'balance_stock'   => $newQuantity,
                            'actual_balance'  => $newQuantity,
                            'quantity_type'   => 'debit',
                            'item_type'       => 'SKU',
                            'type'            => 'DC',
                            'created'         => date('Y-m-d H:i:s'),
                            'sync'=>0,
                            'local_sync'=>0
                    ];
                    if(!$this->Stock_ledger->saveAll($ledgerData['Stock_ledger'])){
                        $success=false;
                    }

                }
                $success = true;
            }

            if ($success) {
                $currentDate = date('Y-m-d H:i:s');

                $this->Inventory_indent->updateAll(
                    [
                        'processed_amount' => $amount,
                        'processed_date' => "'$currentDate'",
                        'consignment_id' => $consignment_id,
                        'serial_number' => "'$serial_number'",
                        'status' => 3,
                        'sync' => 0,
                        'local_sync' => 0
                    ],
                    ['indent_id' => $indentId,
                        'status' => 2]
                );

                echo json_encode(['response_code' => 1, 'data' => 'Processed']);
            } else {
                echo json_encode(['response_code' => 0, 'data' => 'Indent items could not be updated.']);
            }
        } else {
            echo json_encode(['response_code' => 0, 'data' => 'Invalid request.']);
        }
    }

    public function edit_indent()
    {
        if (!$this->Session->check('Auth.User.id')) {
            return $this->redirect(['controller' => 'outlet', 'action' => 'login']);
        }

        $this->layout = "users";
        $id = $_REQUEST['id'];

        $role = $this->Session->read('Auth.User.role');
        $user_location = $this->Session->read('Auth.Location.id');

        $user = "SELECT *,
            (SELECT sku_quantity FROM inventory_indent_items
             WHERE indent_id='$id' AND sku_id=inventory_skus.id) as quantity
            FROM inventory_skus
            WHERE inventory_skus.status=1
            ORDER BY inventory_skus.name DESC;";
        $users = $this->Inventory_sku->query($user);

        $indentData = $this->Inventory_indent->find('first', [
            'conditions' => ['indent_id' => $id],
            'recursive' => -1
        ]);

        $selected_location_id = $indentData['Inventory_indent']['location_id'];
        $selected_expiry = $indentData['Inventory_indent']['expiry'];

        $this->set(compact('users', 'id', 'locations', 'selected_location_id', 'selected_expiry'));
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
            $location_id = isset($_POST['location_id']) ? $_POST['location_id'] : null;
            $location_name = '';

            if (!empty($location_id)) {
                $loc = $this->Location->find('first', [
                    'conditions' => ['Location.id' => $location_id],
                    'fields' => ['Location.name'],
                    'recursive' => -1
                ]);
                $location_name = isset($loc['Location']['name']) ? $loc['Location']['name'] : '';
            }

            $this->Inventory_indent_items->query("UPDATE inventory_indent_items SET status=0,sync=0,local_sync=0 WHERE indent_id = " . (int)$indentid);
            $this->Inventory_indent->query("UPDATE FROM inventory_indent SET status=0,sync=0,local_sync=0 WHERE indent_id = " . (int)$indentid);

            $amount = 0;
            $chk = false;

            foreach ($checkeddatas as $checkeddata) {
                $skuid = $checkeddata['id'];
                $price = $checkeddata['price'];
                $sku_name = $checkeddata['sku_name'];
                $sku_brand = $checkeddata['sku_brand'];
                $unit_name = $checkeddata['unit_name'];
                $unit_id = $checkeddata['unit_id'];
                $quantity = $checkeddata['sku_quantity'];

                if (!empty($quantity)) {
                    $itemData = array(
                        'indent_id' => $indentid,
                        'sku_id' => $skuid,
                        'sku_name' => $sku_name,
                        'sku_brand' => $sku_brand,
                        'sku_unit' => $unit_id,
                        'sku_unit_name' => $unit_name,
                        'sku_price' => $price,
                        'sku_quantity' => $quantity,
                        'sync'=>0,
                        'local_sync'=>0
                    );

                    if ($this->Inventory_indent_items->saveAll($itemData)) {
                        $chk = true;
                    } else {
                        $chk = false;
                        $this->Flash->error(__('Internal indent could not be updated. Please, try again.'));
                    }

                    $productamount = $quantity * $price;
                    $amount += $productamount;
                }
            }

            if ($chk) {
                $indentData = array(
                    'indent_id' => $indentid,
                    'expiry' =>$expiry,
                    'created_by' => $id,
                    'created_by_name' => $name,
                    'location_id' => $location_id,
                    'location_name' => $location_name,
                    'total_amount' => $amount,
                    'is_updated' => 1,
                    'sync'=>0,
                    'local_sync'=>0
                );

                if ($this->Inventory_indent->saveAll($indentData)) {
                    echo json_encode(['response_code' => 1, 'data' => 'Updated']);
                } else {
                    echo json_encode(['response_code' => 0, 'data' => 'Internal indent could not be updated. Please, try again.']);
                }
            } else {
                echo json_encode(['response_code' => 0, 'data' => 'Internal indent items could not be updated. Please, try again.']);
            }
        }
    }

    public function grn()
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

        $locationId = $this->Session->read('Auth.User.location_id');
        $userRole = $this->Session->read('Auth.User.role');

        $_shiftRange = $this->getShiftDateRange($startdate, $enddate);
        $startDatetime = $_shiftRange['startDatetime'];
        $endDatetime   = $_shiftRange['endDatetime'];

        $conditions = "created BETWEEN '$startDatetime' and  '$endDatetime'";
        if ($userRole !== 'admin') {
            $conditions .= " AND warehouse_id = '$locationId'";
        }

        $submittedIndents = $this->Inventory_indent->query("
            SELECT * FROM inventory_indent
            WHERE $conditions AND status = 3
            ORDER BY id DESC
        ");
        $approvedIndents = $this->Inventory_indent->query("
            SELECT * FROM inventory_indent
            WHERE $conditions AND status = 4
            ORDER BY id DESC
        ");
        $yourStartDateValue = $startDate . ' - ' . $endDate;
        $this->set('startdate', $yourStartDateValue);

        $newconditions='';
        if ($userRole !== 'admin') {
            $newconditions .= " warehouse_id = '$locationId' and";
        }
        $submitted_indents = $this->Inventory_indent->query("
            SELECT count(id) as total FROM inventory_indent
            WHERE $newconditions status IN (1)
        ");
        $submitted_indent=$submitted_indents[0][0]['total'];

        $approved_indents = $this->Inventory_indent->query("
            SELECT count(id) as total FROM inventory_indent
            WHERE $newconditions status = 2
        ");
        $approved_indent=$approved_indents[0][0]['total'];

        $processed_indents = $this->Inventory_indent->query("
            SELECT count(id) as total FROM inventory_indent
            WHERE $newconditions status = 3
        ");
        $processed_indent=$processed_indents[0][0]['total'];

        $stockedin_indents = $this->Inventory_indent->query("
            SELECT count(id) as total FROM inventory_indent
            WHERE $newconditions status = 4
        ");
        $stockedin_indent=$stockedin_indents[0][0]['total'];
        $this->set(compact('submitted_indent','approved_indent','processed_indent','stockedin_indent'));

        $this->set(compact('submittedIndents', 'approvedIndents'));
    }

    public function indent_pdf() {
        if (!$this->Session->check('Auth.User.id')) {
            return $this->redirect(['controller' => 'dashboard', 'action' => 'login']);
        }

        $this->layout = "login";

        if ($this->request->is(['post', 'put'])) {
            $po_id = $this->request->data['Report']['poid'];

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

            $locationdetail = $this->Location->find('first', [
                'conditions' => ['Location.id' => $location_id]
            ]);

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

                    if (!empty($poItems)) {
                        foreach ($poItems as $pi) {
                            $skuId = $pi['Inventory_purchase_order_item']['sku_id'];
                            $poItemMap[$skuId] = $pi['Inventory_purchase_order_item'];
                        }
                    }
                }
            }

            $this->set(compact('po_id', 'indents', 'items', 'locationdetail', 'poData', 'poItems', 'poItemMap'));

            $this->render('indent_pdf');
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

        $_shiftRange = $this->getShiftDateRange($startdate, $enddate);
        $startDatetime = $_shiftRange['startDatetime'];
        $endDatetime   = $_shiftRange['endDatetime'];

        $userRole = $this->Session->read('Auth.User.role');
        $locationId = $this->Session->read('Auth.User.location_id');

        $conditions = "created BETWEEN '$startDatetime' and  '$endDatetime'";
        if ($userRole !== 'admin') {
            $conditions .= " AND location_id = '$locationId'";
        }

        $users = $this->Inventory_damage->query("
            SELECT * FROM inventory_damage
            WHERE $conditions and item_type='SKU' and type='Warehouse'
            ORDER BY id DESC
        ");

        $yourStartDateValue = $startDate . ' - ' . $endDate;
        $this->set('startdate', $yourStartDateValue);
        $this->set(compact('users'));
    }

    public function damage_change_status()
    {
        $this->autoRender = false;

        if ($this->request->is('post')) {
            $userId = $this->request->data['_id'];

            $this->Inventory_damage->id = $userId;
            $newStatus = $this->Inventory_damage->field('status') == 1 ? 0 : 1;
            if ($this->Inventory_damage->save(['status' => $newStatus,'sync'=>0,'local_sync'=>0])) {
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

        if ($this->Session->read('Auth.User.role') == 'admin') {
            $locations = $this->Location->find('all', ['conditions' => ['status' => 1,'type' => 'dc']]);
        } else {
            $location_id = $this->Session->read('Auth.User.location_id');
            $locations = $this->Location->find('all', [
                'conditions' => ['status' => 1, 'id' => $location_id,'type' => 'dc']
            ]);
        }

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

        $id = $this->Session->read('Auth.User.id');
        $name = $this->Session->read('Auth.User.name');

        if (isset($_POST['checkedData']) && isset($_POST['location_id'])) {
            $checkeddatas = json_decode($_POST['checkedData'], true);
            $location_id = (int)$_POST['location_id'];
            $damage_date = $_POST['damage_date'];

            $location = $this->Location->find('first', [
                'conditions' => ['Location.id' => $location_id],
                'fields' => ['id', 'name'],
                'recursive' => -1
            ]);

            if (empty($location)) {
                echo json_encode(['response_code' => 0, 'data' => 'Invalid location selected.']);
                return;
            }

            do {

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
                    'amount' => $price*$quantity,
                    'sync'=>0,
                    'local_sync'=>0
                ];
                $this->Inventory_damage_item->create();
                if (!$this->Inventory_damage_item->save($skuData)) {
                    $itemsSaved = false;
                    break;
                }

                $amount=$amount+($price*$quantity);

                $ledgerprevdetails = $this->Stock_ledger->find('first', [
                    'conditions' => ['sku_id' => $skuid,'type'=>'DC','item_type'=>'SKU','store_id'=>$location_id],
                    'fields' => ['actual_balance'],
                    'order' => ['id DESC'],
                    'recursive' => -1
                ]);

                $ledger_prev_quantity = !empty($ledgerprevdetails['Stock_ledger']['actual_balance'])
                    ? $ledgerprevdetails['Stock_ledger']['actual_balance']
                    : 0;

                $newQuantity = $ledger_prev_quantity - $quantity;

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
                        'type'            => 'DC',
                        'created'         => date('Y-m-d H:i:s'),
                        'sync'=>0,
                        'local_sync'=>0
                ];
                $this->Stock_ledger->saveAll($ledgerData['Stock_ledger']);

            }

            if ($itemsSaved) {
                $damageData = [
                    'location_id' => $location_id,
                    'location_name' => $location['Location']['name'],
                    'damage_id' => $txnid,
                    'damage_date' => $damage_date,
                    'type' => 'Warehouse',
                    'item_type' => 'SKU',
                    'amount' => $amount,
                    'sync'=>0,
                    'local_sync'=>0
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

        $user_id = $this->Session->read('Auth.User.id');
        $role = $this->Session->read('Auth.User.role');
        $user_location = $this->Session->read('Auth.User.location_id');

        if ($role == 'admin') {
            $locations = $this->Location->find('list', ['conditions' => ['status' => 1,'type' => 'dc']]);
        } else {
            $locations = $this->Location->find('list', [
                'conditions' => ['status' => 1, 'id' => $user_location,'type' => 'dc']
            ]);
        }

        $user = "SELECT *,
            (SELECT quantity FROM inventory_damage_items
             WHERE damage_id='$id' AND item_id=inventory_skus.id and status=1) as quantity
            FROM inventory_skus
            WHERE inventory_skus.status=1
            ORDER BY inventory_skus.name ASC";

        $users = $this->Inventory_sku->query($user);

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

        $id = $this->Session->read('Auth.User.id');
        $name = $this->Session->read('Auth.User.name');

        if (isset($_POST['checkedData'])) {
            $checkeddatas = json_decode($_POST['checkedData'], true);
            $indentid = $_POST['indent_id'];
            $damage_date = $_POST['damage_date'];
            $location_id = isset($_POST['location_id']) ? $_POST['location_id'] : null;
            $location_name = '';

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
                $damagedata = $lastdata['Inventory_damage_item'];
                $skuid       = $damagedata['item_id'];
                $price       = $damagedata['item_price'];
                $sku_name    = $damagedata['item_name'];
                $sku_brand   = isset($damagedata['sku_brand']) ? $damagedata['sku_brand'] : '';
                $unit_name   = $damagedata['unit_name'];
                $unit_id     = $damagedata['unit_id'];
                $cat_id      = $damagedata['item_category_id'];
                $cat_name    = $damagedata['item_category_name'];
                $quantity    = $damagedata['quantity'];

                if (!empty($quantity)) {
                    $ledgerprevdetails = $this->Stock_ledger->find('first', [
                        'conditions' => [
                            'sku_id' => $skuid,
                            'type' => 'DC',
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
                            'type'            => 'DC',
                            'created'         => date('Y-m-d H:i:s'),
                            'sync'=>0,
                            'local_sync'=>0
                        ]
                    ];

                    $this->Stock_ledger->create();
                    $this->Stock_ledger->save($ledgerData['Stock_ledger']);
                }
            }

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
                        'amount' => $price*$quantity,
                        'sync'=>0,
                        'local_sync'=>0
                    );

                    if ($this->Inventory_damage_item->saveAll($itemData)) {

                        $ledgerprevdetails = $this->Stock_ledger->find('first', [
                            'conditions' => ['sku_id' => $skuid,'type'=>'DC','item_type'=>'SKU','store_id'=>$location_id],
                            'fields' => ['actual_balance'],
                            'order' => ['id DESC'],
                            'recursive' => -1
                        ]);

                        $ledger_prev_quantity = !empty($ledgerprevdetails['Stock_ledger']['actual_balance'])
                            ? $ledgerprevdetails['Stock_ledger']['actual_balance']
                            : 0;

                        $newQuantity = $ledger_prev_quantity - $quantity;

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
                                'type'            => 'DC',
                                'created'         => date('Y-m-d H:i:s'),
                                'sync'=>0,
                                'local_sync'=>0
                        ];
                        $this->Stock_ledger->saveAll($ledgerData['Stock_ledger']);

                        $chk = true;
                    } else {
                        $chk = false;
                        $this->Flash->error(__('Damage could not be updated. Please, try again.'));
                    }

                    $amount += ($price*$quantity);
                }
            }

            if ($chk) {
                $indentData = array(
                    'location_id' => $location_id,
                    'location_name' => $location_name,
                    'damage_id' => $indentid,
                    'damage_date' => $damage_date,
                    'type' => 'Warehouse',
                    'item_type' => 'SKU',
                    'amount' => $amount,
                    'sync'=>0,
                    'local_sync'=>0
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

        $_shiftRange = $this->getShiftDateRange($startdate, $enddate);
        $startDatetime = $_shiftRange['startDatetime'];
        $endDatetime   = $_shiftRange['endDatetime'];

        $userRole = $this->Session->read('Auth.User.role');
        $locationId = $this->Session->read('Auth.User.location_id');

        $conditions = "created BETWEEN '$startDatetime' and  '$endDatetime'";
        if ($userRole !== 'admin') {
            $conditions .= " AND location_id = '$locationId'";
        }

        $users = $this->Inventory_damage->query("
            SELECT * FROM inventory_damage
            WHERE $conditions and item_type='semifinished' and type='Warehouse' and status=1
            ORDER BY id DESC
        ");

        $yourStartDateValue = $startDate . ' - ' . $endDate;
        $this->set('startdate', $yourStartDateValue);
        $this->set(compact('users'));
    }

    public function semi_damage_change_status()
    {
        $this->autoRender = false;

        if ($this->request->is('post')) {
            $userId = $this->request->data['_id'];

            $this->Inventory_damage->id = $userId;
            $newStatus = $this->Inventory_damage->field('status') == 1 ? 0 : 1;
            if ($this->Inventory_damage->save(['status' => $newStatus,'sync'=>0,'local_sync'=>0])) {
                echo json_encode(['status' => true]);
            } else {
                echo json_encode(['status' => false]);
            }
        }
    }

    public function semi_create_damage()
    {
        if (!$this->Session->check('Auth.User.id')) {
            $this->redirect(['controller' => 'dashboard', 'action' => 'login']);
        }

        $this->layout = "users";

        if ($this->Session->read('Auth.User.role') == 'admin') {
            $locations = $this->Location->find('all', ['conditions' => ['status' => 1,'type' => 'dc']]);
        } else {
            $location_id = $this->Session->read('Auth.User.location_id');
            $locations = $this->Location->find('all', [
                'conditions' => ['status' => 1, 'id' => $location_id,'type' => 'dc']
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

            $location = $this->Location->find('first', [
                'conditions' => ['Location.id' => $location_id],
                'fields' => ['id', 'name'],
                'recursive' => -1
            ]);

            if (empty($location)) {
                echo json_encode(['response_code' => 0, 'data' => 'Invalid location selected.']);
                return;
            }

            do {

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
                    'amount' => $price*$quantity,
                    'sync'=>0,
                    'local_sync'=>0
                ];
                $this->Inventory_damage_item->create();
                if (!$this->Inventory_damage_item->save($skuData)) {
                    $itemsSaved = false;
                    break;
                }

                $amount=$amount+($price*$quantity);

                $ledgerprevdetails = $this->Stock_ledger->find('first', [
                    'conditions' => ['sku_id' => $skuid,'type'=>'DC','item_type'=>'semifinished','store_id'=>$location_id],
                    'fields' => ['actual_balance'],
                    'order' => ['id DESC'],
                    'recursive' => -1
                ]);

                $ledger_prev_quantity = !empty($ledgerprevdetails['Stock_ledger']['actual_balance'])
                    ? $ledgerprevdetails['Stock_ledger']['actual_balance']
                    : 0;

                $newQuantity = $ledger_prev_quantity - $quantity;

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
                        'type'            => 'DC',
                        'item_type'       => 'semifinished',
                        'created'         => date('Y-m-d H:i:s'),
                        'sync'=>0,
                        'local_sync'=>0
                ];
                $this->Stock_ledger->saveAll($ledgerData['Stock_ledger']);

            }

            if ($itemsSaved) {
                $damageData = [
                    'location_id' => $location_id,
                    'location_name' => $location['Location']['name'],
                    'damage_id' => $txnid,
                    'damage_date' => $damage_date,
                    'type' => 'Warehouse',
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

    public function semi_edit_damage()
    {
        if (!$this->Session->check('Auth.User.id')) {
            return $this->redirect(['controller' => 'dashboard', 'action' => 'login']);
        }

        $this->layout = "users";
        $id = $_REQUEST['id'];

        $user_id = $this->Session->read('Auth.User.id');
        $role = $this->Session->read('Auth.User.role');
        $user_location = $this->Session->read('Auth.User.location_id');

        if ($role == 'admin') {
            $locations = $this->Location->find('list', ['conditions' => ['status' => 1,'type' => 'dc']]);
        } else {
            $locations = $this->Location->find('list', [
                'conditions' => ['status' => 1, 'id' => $user_location,'type' => 'dc']
            ]);
        }

        $user = "SELECT *,
            (SELECT quantity FROM inventory_damage_items
             WHERE damage_id='$id' AND item_id=inventory_semifinished.id and status=1) as quantity
            FROM inventory_semifinished
            WHERE inventory_semifinished.status=1
            ORDER BY inventory_semifinished.name DESC";

        $users = $this->Inventory_semifinished->query($user);

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
                $damagedata = $lastdata['Inventory_damage_item'];
                $skuid       = $damagedata['item_id'];
                $price       = $damagedata['item_price'];
                $sku_name    = $damagedata['item_name'];
                $sku_brand   = isset($damagedata['sku_brand']) ? $damagedata['sku_brand'] : '';
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
                            'type' => 'DC',
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
                            'type'            => 'DC',
                            'created'         => date('Y-m-d H:i:s'),
                            'sync'=>0,
                            'local_sync'=>0
                        ]
                    ];

                    $this->Stock_ledger->create();
                    $this->Stock_ledger->save($ledgerData['Stock_ledger']);
                }
            }

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
                        'amount' => $price*$quantity,
                        'sync'=>0,
                        'local_sync'=>0
                    );

                    if ($this->Inventory_damage_item->saveAll($itemData)) {

                        $ledgerprevdetails = $this->Stock_ledger->find('first', [
                            'conditions' => ['sku_id' => $skuid,'type'=>'DC','item_type'=>'semifinished','store_id'=>$location_id],
                            'fields' => ['actual_balance'],
                            'order' => ['id DESC'],
                            'recursive' => -1
                        ]);

                        $ledger_prev_quantity = !empty($ledgerprevdetails['Stock_ledger']['actual_balance'])
                            ? $ledgerprevdetails['Stock_ledger']['actual_balance']
                            : 0;

                        $newQuantity = $ledger_prev_quantity - $quantity;

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
                                'type'            => 'DC',
                                'item_type'       => 'semifinished',
                                'created'         => date('Y-m-d H:i:s'),
                                'sync'=>0,
                                'local_sync'=>0
                        ];
                        $this->Stock_ledger->saveAll($ledgerData['Stock_ledger']);

                        $chk = true;
                    } else {
                        $chk = false;
                        $this->Flash->error(__('Damage could not be updated. Please, try again.'));
                    }

                    $amount += ($price*$quantity);
                }
            }

            if ($chk) {
                $indentData = array(
                    'location_id' => $location_id,
                    'location_name' => $location_name,
                    'damage_id' => $indentid,
                    'damage_date' => $damage_date,
                    'type' => 'Warehouse',
                    'item_type' => 'semifinished',
                    'amount' => $amount,
                    'sync'=>0,
                    'local_sync'=>0
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
        if (!$this->Session->check('Auth.User.id')) {
            return $this->redirect(['controller' => 'dashboard', 'action' => 'login']);
        }

        $this->layout = "users";

        $startDate = date('m/d/Y', strtotime('-30 days'));
        $endDate = date('m/d/Y');

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

        $authUser = $this->Session->read('Auth.User');
        $userRole = $authUser['role'] ?? '';
        $userLocationId = $authUser['location_id'] ?? 0;

        $baseConditions = [

            'auditto <=' => $enddate
        ];

        if ($userRole !== 'admin') {

        }

        $_shiftRange = $this->getShiftDateRange($startdate, $enddate);
        $startDatetime = $_shiftRange['startDatetime'];
        $endDatetime   = $_shiftRange['endDatetime'];

        $preparedStocks = $this->Audit_stock->find('all', [
            'conditions' => [
                'Audit_stock.status' => 1,
                'Audit_stock.updateon BETWEEN ? AND ?' => [$startDatetime, $endDatetime],
                'Audit_stock.item_type' => 'SKU',
                'Audit_stock.type' => 'DC'
            ],
            'fields' => ['Audit_stock.*'],
            'order' => ['Audit_stock.id' => 'DESC'],
            'recursive' => 1
        ]);

        $daterangeFormatted = $startDate . ' - ' . $endDate;
        $this->set(compact('daterangeFormatted', 'preparedStocks'));
    }

    public function create_audit_stock()
    {
        if (!$this->Session->check('Auth.User.id')) {
            return $this->redirect(['controller' => 'dashboard', 'action' => 'login']);
        }

        $this->layout = "users";

        $userId = $this->Session->read('Auth.User.id');
        $today = date("Y-m-d");
        $currentTime = date("H:i");
        $this->set(compact('today', 'currentTime'));

        $skus = $this->Inventory_sku->query("SELECT * FROM inventory_skus WHERE status = 1 ORDER BY name ASC");
        $this->set(compact('skus'));
    }

    public function add_auditstock()
    {
        $data = $this->request->data;

        $remarks = trim($data['remarks']);
        $auditDate = $data['audit_date'] ?? $today;
        $auditTime = $data['audit_time'] ?? $currentTime;
        $createdTime = date('Y-m-d H:i:s');

        $authUser = $this->Session->read('Auth.User');
        $storeId = $authUser['location_id'] ?? 0;

        $loc = $this->Location->find('first', [
            'conditions' => ['Location.id' => $storeId],
            'fields' => ['Location.name'],
            'recursive' => -1
        ]);
        $store_name = $loc['Location']['name'] ?? '';

        do {
            $pos_audit_id = substr(preg_replace('/\D/', '', sha1(uniqid() . rand())), 0, 12);
            if ($pos_audit_id[0] == '0') {
                $pos_audit_id[0] = rand(1,9);
            }
            $exists = $this->Audit_stock->find('count', [
                'conditions' => ['Audit_stock.pos_audit_id' => $pos_audit_id],
                'recursive' => -1
            ]);
        } while ($exists > 0);

        $header = [
            'pos_audit_id' => $pos_audit_id,
            'store_id' => $storeId,
            'store_name' => $store_name,
            'remarks' => $remarks,
            'status' => 1,
            'select_all' => 0,
            'updateon' => $createdTime
        ];

        $checkedData = json_decode($data['checkedData'], true);

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

            $lastAudit = $this->Audit_stock_items->find('first', [
                'conditions' => ['sku_id' => $skuId, 'item_type' => 'sku', 'type' => 'DC', 'status' => 1, 'store_id' => $storeId],
                'order' => ['id DESC'],
                'recursive' => -1
            ]);

            $openingstock = 0;
            $auditfrom = '2025-01-01 00:00:01';
            if (!empty($lastAudit)) {
                $openingstock = floatval($lastAudit['Audit_stock_items']['physicalstock']);
                $auditfrom = $lastAudit['Audit_stock_items']['auditto'];
            }

            $var = $this->Stock_ledger->query("
                SELECT
                    SUM(CASE WHEN quantity_type='credit' THEN quantity ELSE 0 END) AS purchase,
                    SUM(CASE WHEN record_type IN ('stockout','stockout_modifier','process_indent','stock_transfer_to_kitchen') THEN quantity ELSE 0 END) AS consumption,
                    (
                        SUM(CASE WHEN record_type IN ('damage','update_damage') AND quantity_type='debit' THEN quantity ELSE 0 END)
                        -
                        SUM(CASE WHEN record_type='reverse_damage' AND quantity_type='credit' THEN quantity ELSE 0 END)
                    ) AS damage
                FROM stock_ledger
                WHERE store_id='$storeId' AND item_type='SKU' AND sku_id=$skuId
                AND type='DC' AND created BETWEEN '$auditfrom' AND '$auditto'
            ")[0][0];

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
                'sku_unit' => $unitId,
                'sku_unit_name' => $unitName,
                'sku_price'=>$sku_price,
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
                'modified' => $createdTime,
                'sync'=>0,
                'local_sync'=>0
            ]);

            $this->Stock_ledger->create();
            $this->Stock_ledger->save([
                'store_id' => $storeId,
                'sku_id' => $skuId,
                'sku_name' => $skuName,
                'sku_price'=>$sku_price,
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
                'type' => 'DC',
                'quantity_type' => 'audit',
                'created' => $auditto,
                'modified' => $createdTime,
                'sync'=>0,
                'local_sync'=>0
            ]);
        }

        $this->Audit_stock->create();
        if ($this->Audit_stock->save($header)) {
            echo "success";
            exit;
        } else {
            echo "failed";
            exit;
        }
    }

    public function view_audit()
    {
        if(!$this->Session->check('Auth.User.id')){
            $this->redirect(array('controller'=>'dashboard','action' => 'login'));
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
        if (!$this->Session->check('Auth.User.id')) {
            return $this->redirect(['controller' => 'dashboard', 'action' => 'login']);
        }

        $this->layout = "users";

        $startDate = date('m/d/Y', strtotime('-7 days'));
        $endDate = date('m/d/Y');

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

        $authUser = $this->Session->read('Auth.User');
        $userRole = $authUser['role'] ?? '';
        $userLocationId = $authUser['location_id'] ?? 0;

        $baseConditions = [

            'auditto <=' => $enddate
        ];

        if ($userRole !== 'admin') {

        }

        $_shiftRange = $this->getShiftDateRange($startdate, $enddate);
        $startDatetime = $_shiftRange['startDatetime'];
        $endDatetime   = $_shiftRange['endDatetime'];

        $preparedStocks = $this->Audit_stock->find('all', [
            'conditions' => [
                'Audit_stock.status' => 1,
                'Audit_stock.updateon BETWEEN ? AND ?' => [$startDatetime, $endDatetime],
                'Audit_stock.item_type' => 'Semifinished',
                'Audit_stock.type' => 'DC'
            ],
            'fields' => ['Audit_stock.*'],
            'order' => ['Audit_stock.id' => 'DESC'],
            'recursive' => 1
        ]);

        $daterangeFormatted = $startDate . ' - ' . $endDate;
        $this->set(compact('daterangeFormatted', 'startdate', 'enddate', 'preparedStocks'));
    }

    public function semi_create_audit_stock()
    {
        if (!$this->Session->check('Auth.User.id')) {
            return $this->redirect(['controller' => 'dashboard', 'action' => 'login']);
        }

        $this->layout = "users";

        $userId = $this->Session->read('Auth.User.id');
        $today = date("Y-m-d");
        $currentTime = date("H:i");
        $this->set(compact('today', 'currentTime'));

        $skus = $this->Inventory_semifinished->query("SELECT * FROM inventory_semifinished WHERE status = 1 ORDER BY name ASC");
        $this->set(compact('skus'));

        if ($this->request->is(['post', 'put'])) {
            $data = $this->request->data;

            $remarks = trim($data['remarks']);
            $auditDate = $data['audit_date'] ?? $today;
            $auditTime = $data['audit_time'] ?? $currentTime;
            $submitAll = $data['submit_all_items'] ?? 'no';
            $createdTime = date('Y-m-d H:i:s');

            $authUser = $this->Session->read('Auth.User');
            $storeId = $authUser['location_id'] ?? 0;
            $store_name = $authUser['location_name'] ?? 0;

            do {

                $pos_audit_id = preg_replace('/\D/', '', substr(sha1(uniqid() . rand()), 0, 32));
                $pos_audit_id = substr($pos_audit_id, 0, 12);
                if ($pos_audit_id[0] === '0') {
                    $pos_audit_id[0] = strval(rand(1, 9));
                }

                $existsMain = $this->Audit_stock->find('count', [
                    'conditions' => ['Audit_stock.pos_audit_id' => $pos_audit_id],
                    'recursive' => -1
                ]);

                $existsTemp = $this->Temp_audit_stock->find('count', [
                    'conditions' => ['Temp_audit_stock.pos_audit_id' => $pos_audit_id],
                    'recursive' => -1
                ]);

            } while ($existsMain > 0 || $existsTemp > 0);

            $selectAll = ($submitAll === 'yes') ? 1 : 0;

            $header = [
                'pos_audit_id' => $pos_audit_id,
                'store_id' => $storeId,
                'store_name' => $store_name,
                'remarks' => $remarks,
                'status' => 1,
                'item_type' => 'semifinished',
                'type' => 'DC',
                'select_all' => $selectAll,
                'updateon' => $createdTime
            ];

            $quantities = $data['quantity'] ?? [];

            $items = [];

            if (isset($data['prepare_submit'])) {
                foreach ($quantities as $skuId => $qty) {
                    $qty = floatval($qty);
                    if ($qty <= 0) continue;

                    $lastAudit = $this->Audit_stock_items->find('first', [
                        'conditions' => ['sku_id' => $skuId,'item_type' => 'Semifinished','type' => 'DC', 'status' => 1],
                        'order' => ['id DESC'],
                        'fields' => ['physicalstock', 'auditto'],
                        'recursive' => -1
                    ]);

                    $openingstock = 0;
                    $auditfrom = '2025-01-01 00:00:01';

                    if (!empty($lastAudit)) {
                        $openingstock = floatval($lastAudit['Audit_stock_items']['physicalstock']);
                        $auditfrom = $lastAudit['Audit_stock_items']['auditto'];
                    }

                    $variance = $qty - $openingstock;
                    $auditto = $auditDate . ' ' . $auditTime . ':00';

                    $skuName = $data['sku_name'][$skuId] ?? '';
                    $unitId = (int)($data['unit_id'][$skuId] ?? 0);
                    $unitName = $data['unit_name'][$skuId] ?? '';
                    $sku_price=$data['price'][$skuId] ?? 0;

                    $auditItem = [
                        'sku_id' => $skuId,
                        'pos_audit_id' => $pos_audit_id,
                        'sku_name' => $skuName,
                        'item_type' => 'Semifinished',
                        'type' => 'DC',
                        'sku_unit' => $unitId,
                        'sku_unit_name' => $unitName,
                        'sku_price'=>$sku_price,
                        'openingstock' => $openingstock,
                        'closingstock' => 0,
                        'physicalstock' => $qty,
                        'variance' => $variance,
                        'auditfrom' => $auditfrom,
                        'auditto' => $auditto,
                        'remarks' => $remarks,
                        'status' => 1,
                        'created' => $createdTime,
                        'modified' => $createdTime,
                        'sync'=>0,
                        'local_sync'=>0
                    ];

                    $this->Audit_stock_items->create();
                    $this->Audit_stock_items->save($auditItem);

                    $skuquantityForLedger = $qty;

                    $ledgerEntries = $this->Stock_ledger->find('all', [
                        'conditions' => [
                            'store_id' => $storeId,
                            'sku_id' => $skuId,
                            'item_type'=>'Semifinished',
                            'type' => 'DC',
                            'record_type !=' => 'audit_stock',
                            'created >' => $auditto
                        ],
                        'order' => ['created ASC'],
                        'recursive' => -1
                    ]);

                    if (!empty($ledgerEntries)) {
                        foreach ($ledgerEntries as $entry) {
                            $ledger = $entry['Stock_ledger'];
                            $prevQuantity = floatval($ledger['quantity']);
                            $quantityType = $ledger['quantity_type'];

                            $newBalance = ($quantityType === 'credit')
                                ? $skuquantityForLedger + $prevQuantity
                                : $skuquantityForLedger - $prevQuantity;

                            $this->Stock_ledger->id = $ledger['id'];
                            $this->Stock_ledger->save([
                                'variance' => $variance,
                                'actual_balance' => $newBalance,
                                'variance_type' => 'back_audit',
                                'variance_id' => $pos_audit_id,
                                'modified' => $createdTime,
                                'sync'=>0,
                                'local_sync'=>0
                            ]);

                            $skuquantityForLedger = $newBalance;
                        }
                    } else {

                        $this->Stock_ledger->create();
                        $this->Stock_ledger->save([
                            'store_id' => $storeId,
                            'sku_id' => $skuId,
                            'sku_name' => $skuName,
                            'sku_price'=>$sku_price,
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
                            'type' => 'DC',
                            'quantity_type' => 'audit',
                            'created' => $auditto,
                            'modified' => $createdTime,
                            'sync'=>0,
                            'local_sync'=>0
                        ]);
                    }
                }

                if ($this->Audit_stock->save($header)) {
                    $this->Flash->success(__('Audit submitted successfully.'));
                    return $this->redirect(['action' => 'semi_auditstock']);
                } else {
                    $this->Flash->error(__('Failed to submit audit.'));
                }
            }
        }
    }

    public function semi_view_audit()
    {
        if(!$this->Session->check('Auth.User.id')){
            $this->redirect(array('controller'=>'dashboard','action' => 'login'));
        }

        $this->layout = "users";
        $id=$_REQUEST['id'];

        $indents = $this->Audit_stock->query("SELECT * FROM pos_auditstock where pos_audit_id=$id");
        $this->set(compact('indents'));

        $items = $this->Audit_stock_items->query("SELECT * FROM pos_auditstock_items where pos_audit_id=$id and status=1");

        $this->set(compact('items'));

    }

    public function kds_users()
    {
        if(!$this->Session->check('Auth.User.id')){
            $this->redirect(array('controller'=>'dashboard','action' => 'login'));
        }

        $this->layout = "users";

        $startDate = date('m/d/Y', strtotime('-12 months'));
        $endDate = date('m/d/Y');

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

        $authUser = $this->Session->read('Auth.User');
        $userRole = $authUser['role'] ?? '';
        $userLocationId = $authUser['location_ids'] ?? 0;

        $_shiftRange = $this->getShiftDateRange($startdate, $enddate);
        $startDatetime = $_shiftRange['startDatetime'];
        $endDatetime   = $_shiftRange['endDatetime'];

        $params = [
            'start' => "$startDatetime",
            'end'   => "$endDatetime"
        ];

        $locationCondition = "";
        if ($userRole !== 'admin') {
            $locationCondition = " AND ku.location_id = :location_id";
            $params['location_id'] = $userLocationId;
        }

        // Sync kds_users_station_mapping status with pos_printers status
        $this->Kds_users_station_mapping->query("
            UPDATE kds_users_station_mapping kusm
            JOIN pos_printers pp 
              ON (pp.kds = kusm.station_id OR pp.id = kusm.station_id)
             AND pp.store_id = kusm.station_store_id
            SET kusm.status = pp.status, kusm.sync = 0, kusm.local_sync = 0
            WHERE kusm.status != pp.status
        ");

        // Deactivate any KDS user who has zero active stations left
        $this->Kds_user->query("
            UPDATE kds_users ku
            SET ku.status = 0, ku.sync = 0, ku.local_sync = 0
            WHERE ku.status = 1
              AND NOT EXISTS (
                  SELECT 1 FROM kds_users_station_mapping kusm
                  JOIN pos_printers pp 
                    ON (pp.kds = kusm.station_id OR pp.id = kusm.station_id)
                   AND pp.store_id = kusm.station_store_id
                   AND pp.status = 1
                  WHERE kusm.kds_id = ku.id AND kusm.status = 1
              )
        ");

        $sql = "
            SELECT ku.*,
                   GROUP_CONCAT(DISTINCT pp.name SEPARATOR ', ') AS stations
            FROM kds_users ku
            LEFT JOIN kds_users_station_mapping kusm 
                ON kusm.kds_id = ku.id 
                AND kusm.status = 1
            LEFT JOIN pos_printers pp 
                ON (pp.kds = kusm.station_id OR pp.id = kusm.station_id)
                AND pp.store_id = ku.location_id
                AND pp.status = 1
            WHERE 1=1
                  $locationCondition
                  AND ku.created BETWEEN :start AND :end
            GROUP BY ku.id ORDER BY ku.id DESC
        ";

        $users = $this->Kds_user->query($sql, $params);

        $yourStartDateValue=$startDate . ' - ' . $endDate;
        $this->set('startdate', $yourStartDateValue);

        $this->set(compact('users'));

    }

    public function create_kds_users()
    {
        if(!$this->Session->check('Auth.User.id')){
            $this->redirect(array('controller'=>'dashboard','action' => 'login'));
        }

        $this->layout = "users";

        $authUser = $this->Session->read('Auth.User');
        $userRole = $authUser['role'] ?? '';
        $userLocationId = $authUser['location_ids'] ?? 0;

        $conditions = ['status' => 1];
        if ($userRole !== 'admin') {
            $conditions['id IN'] = (array)$userLocationId;
        }

        $locations = $this->Location->find('all', [
            'conditions' => $conditions
        ]);
        $this->set(compact('locations'));

        $stations = $this->Pos_printer->find('all', array('conditions' => array('status' => 1)));
        $this->set(compact('stations'));

        if ($this->request->is(array('post', 'put'))) {

            $username = $this->data['User']['username'];
            $userdetail=$this->Kds_user->find('first',array('conditions'=>array('username'=>$username)));
            if($userdetail){
                $this->Flash->error(__('Username already exist'));
                return $this->redirect(array('action' => 'create_kds_users'));
            }

            $password = $this->data['User']['password'];
            $password = AuthComponent::password($password);
            $location = $this->data['User']['location'];
            $role = $this->data['User']['role'];
            $locationdetail=$this->Location->find('first',array('conditions'=>array('Location.id'=>$location)));
            $locationname=$locationdetail['Location']['name'];
            $data['Kds_user']=array(
                'username'=>$username,
                'password'=>$password,
                'role'=>$role,
                'location_id'=>$location,
                'location_name'=>$locationname,
                'sync'=>0,
                'local_sync'=>0
            );

            if($this->Kds_user->save($data['Kds_user']))
            {
                $kds_id = $this->Kds_user->id;

                $stationIds = !empty($this->request->data['station_ids']) ? $this->request->data['station_ids'] : (!empty($_POST['station_ids']) ? $_POST['station_ids'] : []);
                if (!empty($stationIds) && is_array($stationIds)) {
                    foreach ($stationIds as $station_id) {
                        $stationKey = (int)$station_id;
                        $stationdetail = $this->Pos_printer->find('first', [
                            'conditions' => array(
                                'Pos_printer.kds' => $stationKey,
                                'Pos_printer.store_id' => $location,
                                'Pos_printer.status' => 1
                            ),
                            'recursive' => -1
                        ]);

                        if (!$stationdetail) {
                            continue;
                        }
                        // Always store shared group key so 1 KDS works across stores.
                        $groupKey = (int)$stationdetail['Pos_printer']['kds'];

                        $data['Kds_users_station_mapping'] = [
                            'kds_id' => $kds_id,
                            'kds_username' => $username,
                            'station_id' => $groupKey,
                            'station_name' => $stationdetail['Pos_printer']['name'],
                            'station_store_id' => $location,
                            'station_store_name' => $locationname,
                            'sync'=>0,
                            'local_sync'=>0
                        ];

                        if (!$this->Kds_users_station_mapping->saveAll($data['Kds_users_station_mapping'])) {
                            $this->Flash->error(__('KDS User Station Mapping could not be saved. Please, try again.'));
                        }
                    }
                }

                $this->Flash->success(__('Kds User has been saved.'));
                return $this->redirect(array('action' => 'kds_users'));
            } else {
                $this->Flash->error(__('kds User could not be saved. Please, try again.'));
            }
        }
    }

    public function kds_users_change_status()
    {
        $this->autoRender = false;

        if ($this->request->is('post')) {
            $Id = $this->request->data['_id'];

            $this->Kds_user->id = $Id;
            $newStatus = $this->Kds_user->field('status') == 1 ? 0 : 1;

            if ($newStatus == 1) {
                $activeStation = $this->Kds_users_station_mapping->find('first', [
                    'conditions' => [
                        'Kds_users_station_mapping.kds_id' => $Id,
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
                    'recursive' => -1
                ]);

                if (empty($activeStation)) {
                    echo json_encode(['status' => false, 'message' => 'Cannot activate user: all assigned KDS stations are inactive.']);
                    return;
                }
            }

            if ($this->Kds_user->save(['status' => $newStatus, 'sync' => 0, 'local_sync' => 0])) {
                echo json_encode(['status' => true]);
            } else {
                echo json_encode(['status' => false]);
            }
        }
    }

    public function get_stations_by_location()
    {
        $this->autoRender = false;
        $locationId = $this->request->query('location_id');

        if (!$locationId) {
            echo json_encode(['status' => false, 'message' => 'Location ID missing']);
            return;
        }

        // Only stations assigned to this store, deduped by shared group key
        // (1 KDS name usable in N locations). Resolve IP at runtime via
        // WHERE kds=group AND store_id=current store.
        $stations = $this->Pos_printer->find('all', [
            'conditions' => [
                'Pos_printer.status' => 1,
                'Pos_printer.store_id' => $locationId
            ],
            'fields' => ['id', 'kds', 'name', 'ip', 'store_id'],
            'recursive' => -1
        ]);

        $data = [];
        $seen = [];
        foreach ($stations as $station) {
            $groupKey = (int)$station['Pos_printer']['kds'];
            if (isset($seen[$groupKey])) {
                continue;
            }
            $seen[$groupKey] = true;
            $data[] = [
                'id' => $groupKey,
                'name' => $station['Pos_printer']['name'],
                'ip' => $station['Pos_printer']['ip'] ?? '',
                'store_id' => $station['Pos_printer']['store_id'] ?? $locationId
            ];
        }

        echo json_encode(['status' => true, 'stations' => $data]);
    }

    public function edit_kds_users()
    {
        if (!$this->Session->check('Auth.User.id')) {
            return $this->redirect(['controller' => 'dashboard', 'action' => 'login']);
        }

        $this->layout = 'users';
        $stations = $this->Pos_printer->find('all', array('conditions' => array('status' => 1)));
        $this->set(compact('stations'));

        if ($this->request->is('post')) {

            $data = $this->request->data['Kds_user'];

            $id       = $data['id'] ?? null;
            $username = trim($data['username'] ?? '');
            $password = trim($data['password'] ?? '');
            $location = (int)($data['location'] ?? 0);
            $role     = $data['role'] ?? '';
            $stations = $this->request->data['station_ids'] ?? [];

            if (!$id || $username === '') {
                $this->Flash->error(__('Invalid data.'));
                return $this->redirect(['action' => 'kds_users']);
            }

            $locationDetail = $this->Location->find('first', [
                'conditions' => ['Location.id' => $location],
                'recursive' => -1
            ]);

            $storeName = $locationDetail['Location']['name'] ?? '';

            $updateData = [
                'username'      => $username,
                'role'          => $role,
                'location_id'      => $location,
                'location_name'    => $storeName,
                'sync'          => 0,
                'local_sync'    => 0
            ];

            $this->Kds_user->id = $id;
            $this->Kds_user->save($updateData);

            if (!empty($password)) {
                $this->Kds_user->saveField(
                    'password',
                    AuthComponent::password($password)
                );
            }

            $this->Kds_users_station_mapping->updateAll(
                [
                    'Kds_users_station_mapping.status' => 0,
                    'Kds_users_station_mapping.sync' => 0,
                    'Kds_users_station_mapping.local_sync' => 0
                ],
                ['Kds_users_station_mapping.kds_id' => $id]
            );

            if (!empty($stations)) {
                foreach ($stations as $stationId) {

                    $stationKey = (int)$stationId;
                    $station = $this->Pos_printer->find('first', [
                        'conditions' => array(
                            'Pos_printer.kds' => $stationKey,
                            'Pos_printer.store_id' => $location,
                            'Pos_printer.status' => 1
                        ),
                        'recursive' => -1
                    ]);

                    if ($station) {
                        $groupKey = (int)$station['Pos_printer']['kds'];
                        $this->Kds_users_station_mapping->create();
                        $this->Kds_users_station_mapping->save([
                            'kds_id'                 => $id,
                            'kds_username'           => $username,
                            'station_id'             => $groupKey,
                            'station_name'           => $station['Pos_printer']['name'],
                            'station_store_id'       => $location,
                            'station_store_name'     => $storeName,
                            'status'                 => 1,
                            'sync'=>0,
                            'local_sync'=>0
                        ]);
                    }
                }
            }

            $this->Flash->success(__('KDS User updated successfully.'));
            return $this->redirect(['action' => 'kds_users']);
        }

        $id = $this->request->query('id');
        if (!$id) {
            $this->Flash->error(__('Invalid user ID.'));
            return $this->redirect(['action' => 'kds_users']);
        }

        $userDetail = $this->Kds_user->find('first', [
            'conditions' => ['id' => $id],
            'recursive' => -1
        ]);
        $this->set(compact('userDetail'));

        $locations = $this->Location->find('all', [
            'conditions' => ['status' => 1],
            'recursive' => -1
        ]);
        $this->set(compact('locations'));

        $stationMappings = $this->Kds_users_station_mapping->find('list', [
            'fields' => ['Kds_users_station_mapping.station_id'],
            'conditions' => [
                'Kds_users_station_mapping.kds_id' => $id,
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
            'recursive' => -1
        ]);
        $this->set('selectedStations', array_values($stationMappings));

        $stations = [];
        if (!empty($userDetail['Kds_user']['location_id'])) {
            $stations = $this->Pos_printer->find('all', [
                'conditions' => [
                    'Pos_printer.status' => 1,
                    'Pos_printer.store_id' => $userDetail['Kds_user']['location_id']
                ],
                'fields' => ['id', 'kds', 'name', 'ip', 'store_id'],
                'recursive' => -1
            ]);
        }
        $this->set(compact('stations'));
    }

     public function unit_mapping()
    {
        if(!$this->Session->check('Auth.User.id')){
            $this->redirect(array('controller'=>'dashboard','action' => 'login'));
        }

        $this->layout = "users";

        $startDate = date('m/d/Y', strtotime('-12 months'));
        $endDate = date('m/d/Y');

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

        $_shiftRange = $this->getShiftDateRange($startdate, $enddate);
        $startDatetime = $_shiftRange['startDatetime'];
        $endDatetime   = $_shiftRange['endDatetime'];

        $users = $this->Catalog_unit_mapping->query("SELECT * FROM catalog_unit_mapping where created between '$startDatetime' and  '$endDatetime' ORDER BY id desc");
        $yourStartDateValue=$startDate . ' - ' . $endDate;
        $this->set('startdate', $yourStartDateValue);

        $this->set(compact('users'));

    }

    public function create_unit_mapping()
    {
        if(!$this->Session->check('Auth.User.id')){
            $this->redirect(array('controller'=>'dashboard','action' => 'login'));
        }

        $this->layout = "users";

        $units = $this->Catalog_unit->find('all', ['conditions' => ['status' => 1]]);
        $this->set(compact('units'));

        if ($this->request->is(array('post', 'put'))) {

            $unit = $this->data['User']['unit'];
            if($unit==''){
                $this->Flash->error(__('The Unit could not be Blank'));
                return $this->redirect(array('action' => 'create_unit_mapping'));
            }
            $unit_id = (int)$unit;
            $unitData = $this->Catalog_unit->findById($unit_id);
            $unit_name = $unitData['Catalog_unit']['name'] ?? '';

            $con_unit = $this->data['User']['con_unit'];
            if($con_unit==''){
                $this->Flash->error(__('The Unit could not be Blank'));
                return $this->redirect(array('action' => 'create_unit_mapping'));
            }
            $con_unit_id = (int)$con_unit;
            $con_unitData = $this->Catalog_unit->findById($con_unit_id);
            $con_unit_name = $con_unitData['Catalog_unit']['name'] ?? '';

            $factor = $this->data['User']['factor'];

            $data['Catalog_unit_mapping']=array(
                'unit_id'=>$unit,
                'unit_name'=>$unit_name,
                'mapped_unit_id'=>$con_unit,
                'mapped_unit_name'=>$con_unit_name,
                'conversion_factor'=>$factor,
                'sync'=>0,
                'local_sync'=>0
            );

            if($this->Catalog_unit_mapping->saveAll($data['Catalog_unit_mapping']))
            {

                $this->_refreshAllCosts();

                $this->Flash->success(__('Unit has been mapped.'));
                return $this->redirect(array('action' => 'unit_mapping'));
            } else {
                $this->Flash->error(__('Unit could not be mapped. Please, try again.'));
            }
        }
    }

    public function edit_unit_mapping() {
        if (!$this->Session->check('Auth.User.id')) {
            $this->redirect(['controller' => 'dashboard', 'action' => 'login']);
        }

        $this->layout = "users";

        $id = $this->request->query('id');

        $units = $this->Catalog_unit->find('all', ['conditions' => ['status' => 1]]);
        $this->set(compact('units'));

        $mapping = $this->Catalog_unit_mapping->findById($id);
        if (!$mapping) {
            $this->Flash->error('Mapping not found');
            return $this->redirect(['action' => 'unit_mapping']);
        }
        $this->set('products', [$mapping]);

        if ($this->request->is(['post', 'put'])) {

            $unit_id = $this->data['User']['unit'];
            $unitData = $this->Catalog_unit->findById($unit_id);
            $unit_name = $unitData['Catalog_unit']['name'] ?? '';

            $con_unit_id = $this->data['User']['con_unit'];
            $con_unitData = $this->Catalog_unit->findById($con_unit_id);
            $con_unit_name = $con_unitData['Catalog_unit']['name'] ?? '';

            $this->Catalog_unit_mapping->id = $id;
            $data = [
                'unit_id' => $this->request->data['User']['unit'],
                'unit_name' => $unit_name,
                'mapped_unit_id' => $this->request->data['User']['con_unit'],
                'mapped_unit_name' => $con_unit_name,
                'conversion_factor' => $this->request->data['User']['factor'],
                'sync' =>0,
                'local_sync' =>0
            ];

            if ($this->Catalog_unit_mapping->save($data)) {

                $this->_refreshAllCosts();

                $this->Flash->success('Unit mapping has been updated.');
                return $this->redirect(['action' => 'unit_mapping']);
            } else {
                $this->Flash->error('Unable to update unit mapping.');
            }
        } else {

            $this->request->data['User'] = [
                'unit' => $mapping['Catalog_unit_mapping']['unit_id'],
                'con_unit' => $mapping['Catalog_unit_mapping']['mapped_unit_id'],
                'factor' => $mapping['Catalog_unit_mapping']['factor'],
            ];
        }
    }

    public function unit_mapping_change_status() {
        $this->autoRender = false;

        if ($this->request->is('post')) {
            $Id = $this->request->data['_id'];

            $this->Catalog_unit_mapping->id = $Id;
            $newStatus = $this->Catalog_unit_mapping->field('status') == 1 ? 0 : 1;

            if ($this->Catalog_unit_mapping->save(['status' => $newStatus,'sync' => 0,'local_sync'=>0])) {
                echo json_encode(['status' => true]);
            } else {
                echo json_encode(['status' => false]);
            }
        }
    }

    public function get_mapped_units() {
        $this->autoRender = false;

        $unit_id = $this->request->query('unit_id');

        if (!$unit_id) {
            echo json_encode([]);
            return;
        }

        $mappedUnits = $this->Catalog_unit_mapping->find('all', [
            'conditions' => ['unit_id' => $unit_id, 'status' => 1],
            'fields' => ['mapped_unit_id', 'mapped_unit_name'],
            'recursive' => -1,
        ]);

        $result = [];
        foreach ($mappedUnits as $mu) {
            $result[] = [
                'mapped_unit_id' => $mu['Catalog_unit_mapping']['mapped_unit_id'],
                'mapped_unit_name' => $mu['Catalog_unit_mapping']['mapped_unit_name'],
            ];
        }

        echo json_encode($result);
    }

    public function get_conversion_factor() {
        $this->autoRender = false;

        $unit_id = $this->request->query('unit_id');
        $mapped_unit_id = $this->request->query('mapped_unit_id');

        if (!$unit_id || !$mapped_unit_id) {
            echo json_encode([]);
            return;
        }

        $mapping = $this->Catalog_unit_mapping->find('first', [
            'conditions' => [
                'unit_id' => $unit_id,
                'mapped_unit_id' => $mapped_unit_id,
                'status' => 1
            ],
            'fields' => ['conversion_factor'],
            'recursive' => -1,
        ]);

        if ($mapping) {
            echo json_encode(['factor' => $mapping['Catalog_unit_mapping']['conversion_factor']]);
        } else {
            echo json_encode(['factor' => null]);
        }
    }

    public function store_category() {
        if(!$this->Session->check('Auth.User.id')){
            $this->redirect(array('controller'=>'dashboard','action' => 'login'));
        }
        $this->layout = "users";

        $authUser = $this->Session->read('Auth.User');
        $userRole = $authUser['role'] ?? '';
        $userLocationId = $authUser['location_ids'] ?? 0;

        if($userRole == 'subadmin'){
            $id = $authUser['id'];
            $userLocationId = $this->User_location->find('list', [
                'conditions' => ['status' => 1, 'user_id' => $id],
                'fields' => ['location_id']
            ]);
        }

        $conditions = [
            'status' => 1,
            'type'   => 'Outlet'
        ];

        if ($userRole !== 'admin') {

            $userLocationIds = is_array($userLocationId) ? $userLocationId : [$userLocationId];
            $conditions['id IN'] = $userLocationIds;
        }
        $cashier_users = $this->Location->find('all', [
            'conditions' => $conditions
        ]);
        $this->set(compact('cashier_users'));

        $location_id = null;
        if (isset($this->request->data['Search'])) {
            $location_id= $this->request->data['Search']['cashier'];
        }
        if (isset($this->request->query['location_id'])) {
            $location_id = $this->request->query['location_id'];
            $this->request->data['Search']['cashier'] = $location_id;
        }

        $users = $this->Catalog_category_branches->find('all', array(
            'conditions' => array('location_id' =>$location_id, 'status' => 1),
            'order' => array(
                'id' => 'DESC'
            )
        ));

        $this->set(compact('users'));

    }

    public function manage_store_category() {
        if(!$this->Session->check('Auth.User.id')){
            $this->redirect(array('controller'=>'dashboard','action' => 'login'));
        }

        $authUser = $this->Session->read('Auth.User');
        $userRole = $authUser['role'] ?? '';
        $userLocationId = $authUser['location_id'] ?? 0;

        $this->layout = "users";

        $locationCondition = '';
        if ($userRole !== 'admin' || $userRole !== 'subadmin') {
            $locationCondition = "id = " . (int)$userLocationId;
        }

        $locationId = isset($this->request->query['loc_id']) ? $this->request->query['loc_id'] : null;
        $existingCategories = array();

        if ($locationId) {

            $locationname = $this->Location->field('name', array('id' => $locationId));

            $this->set(compact('locationname'));
            $this->set(compact('locationId'));

            $existingCategories = $this->Catalog_category_branches->find('list', array(
                'fields' => ['category_id', 'category_id'],
                'conditions' => array(
                    'location_id' => $locationId,
                    'status' => 1
                )
            ));
            if(!empty($existingCategories)){
                $allCategories = $this->Catalog_category->find('all', [
                    'fields' => ['id', 'name'],
                    'conditions' => ['status' => 1, 'NOT' => ['id' => $existingCategories]]
                ]);
            }
            else{
                $allCategories = $this->Catalog_category->find('all', [
                    'fields' => ['id', 'name'],
                    'conditions' => ['status' => 1]
                ]);
            }

            $this->set(compact('allCategories'));
        }

    }

    public function assign_categories_to_store(){
        $this->autoRender = false; 
    
        if ($this->request->is('post')) {
            $requestData = !empty($this->request->data) ? $this->request->data : [];
            if (empty($requestData['category_ids'])) {
                $rawInput = $this->request->input();
                if (!empty($rawInput)) {
                    $jsonData = json_decode($rawInput, true);
                    if (is_array($jsonData)) {
                        $requestData = array_merge($requestData, $jsonData);
                    }
                }
            }

            $category_ids = isset($requestData['category_ids']) ? (array)$requestData['category_ids'] : [];
            $store_id = isset($requestData['store_id']) ? $requestData['store_id'] : null;
            $storename = isset($requestData['store_name']) ? $requestData['store_name'] : '';

            if (empty($category_ids)) {
                $this->response->type('json');
                echo json_encode(['status' => false, 'message' => 'No categories selected']);
                return;
            }

            $categories = $this->Catalog_category->find('list', [
                'fields' => ['id', 'name'],
                'conditions' => ['id' => $category_ids]
            ]);

            $saveData = [];
            foreach ($category_ids as $catId) {
                $category_name = isset($categories[$catId]) ? $categories[$catId] : '';
                $saveData[] = [
                    'location_id' => $store_id,       
                    'category_id' => (int)$catId,   
                    'category_name' => $category_name,   
                    'location_name' => $storename,
                    'sync' => 0,
                    'local_sync' => 0
                ];
            }

            $this->response->type('json');
            if (!empty($saveData) && $this->Catalog_category_branches->saveAll($saveData)) {
                $this->Flash->success(__('Categories saved successfully.'));
                echo json_encode(['status' => true]);
            } else {
                echo json_encode(['status' => false]);
            }
        }
    }

    public function change_store_category_status() {
        $this->autoRender = false;

        if ($this->request->is('post')) {
            $categoryId = $this->request->data['_id'];
            $location_id = $this->request->data['loc_row_id'];
              $category =  $this->Catalog_category_branches->updateAll(
                    ['status' => 0,'sync' => 0,'local_sync' => 0],
                    ['category_id' => $categoryId, 'location_id' => $location_id]
                );
                if ($category) {
                    echo json_encode(['status' => true]);
                } else {
                    echo json_encode(['status' => false]);
                }

        }
    }

    public function store_products() {
        $authUser = $this->Session->read('Auth.User');
        $userRole = $authUser['role'] ?? '';
        $userLocationId = $authUser['location_ids'] ?? 0;

        if($userRole == 'subadmin'){
            $id = $authUser['id'];
            $userLocationId = $this->User_location->find('list', [
                'conditions' => ['status' => 1, 'user_id' => $id],
                'fields' => ['location_id']
            ]);
        }

        $this->layout = "users";
        $this->loadModel('Catalog_product_branches');

        $locationCondition = [
            'status' => 1,
            'type'   => 'Outlet'
        ];

        if ($userRole !== 'admin') {

            $userLocationIds = is_array($userLocationId) ? $userLocationId : [$userLocationId];
            $locationCondition['id'] = $userLocationIds;
        }

        $cashier_users = $this->Location->find('all', [
            'conditions' => $locationCondition
        ]);

        $this->set(compact('cashier_users'));
        $location_id = null;
        if (isset($this->request->data['Search']['cashier'])) {
            $location_id = $this->request->data['Search']['cashier'];
        }
        if (isset($this->request->query['location_id'])) {
            $location_id = $this->request->query['location_id'];
            $this->request->data['Search']['cashier'] = $location_id;
        }

        $assignedCategories = [];

        $assignedCategories = $this->Catalog_product_branches->find('all', [
            'conditions' => ['location_id' => $location_id, 'status' => 1],
            'order' => ['id' => 'DESC']
        ]);
        $this->set(compact('assignedCategories'));
    }

    public function manage_store_products() {
        if(!$this->Session->check('Auth.User.id')){
            $this->redirect(array('controller'=>'dashboard','action' => 'login'));
        }

        $authUser = $this->Session->read('Auth.User');
        $this->layout = "users";

        $this->loadModel('Catalog_product_branches');

        $locationId = isset($this->request->query['loc_id']) ? $this->request->query['loc_id'] : null;
        if ($locationId) {
            $locationname = $this->Location->field('name', array('id' => $locationId));
            $this->set(compact('locationId'));
            $this->set(compact('locationname'));
        }

        $existingProducts = $this->Catalog_product_branches->find('list', array(
                'fields' => ['product_id', 'product_id'],
                'conditions' => array(
                    'location_id' => $locationId,
                    'status' => 1
                )
            ));

        if(!empty($existingProducts)){
            $products = $this->Catalog_product->find('all', [
                'fields' => ['id', 'name', 'cat_id', 'cat_name', 'subcat_id', 'subcat_name','unit', 'unit_name','price'],
                'conditions' => ['status' => 1, 'NOT' => ['id' => $existingProducts]]
            ]);
        } else {
            $products = $this->Catalog_product->find('all', [
                'fields' => ['id', 'name', 'cat_id', 'cat_name', 'subcat_id', 'subcat_name','unit', 'unit_name','price'],
                'conditions' => ['status' => 1]
            ]);
        }

        $this->set(compact('products'));

    }

    public function assign_products_to_store(){
        $this->autoRender = false; 
        
        if ($this->request->is('post')) {
            $this->loadModel('Catalog_product_branches');

            $requestData = !empty($this->request->data) ? $this->request->data : [];
            if (empty($requestData['product_ids'])) {
                $rawInput = $this->request->input();
                if (!empty($rawInput)) {
                    $jsonData = json_decode($rawInput, true);
                    if (is_array($jsonData)) {
                        $requestData = array_merge($requestData, $jsonData);
                    }
                }
            }

            $product_ids = isset($requestData['product_ids']) ? (array)$requestData['product_ids'] : [];
            $store_id = isset($requestData['store_id']) ? $requestData['store_id'] : null;
            $storename = isset($requestData['store_name']) ? $requestData['store_name'] : '';
            $prices = isset($requestData['prices']) ? (array)$requestData['prices'] : [];

            if (empty($product_ids)) {
                $this->response->type('json');
                echo json_encode(['status' => false, 'message' => 'No products selected']);
                return;
            }

            $products = $this->Catalog_product->find('all', [
                'fields' => ['id', 'name', 'cat_id', 'cat_name', 'subcat_id', 'subcat_name','unit', 'unit_name','price'],
                'conditions' => ['status' => 1, 'id' => $product_ids]
            ]);

            $productsById = [];
            foreach ($products as $p) {
                $productsById[$p['Catalog_product']['id']] = $p['Catalog_product'];
            }

            $saveData = [];
            foreach ($product_ids as $prodId) {
                if (isset($productsById[$prodId])) {
                    $product = $productsById[$prodId];
                    $price = isset($prices[$prodId]) ? floatval($prices[$prodId]) : $product['price'];

                    $saveData[] = [
                        'location_id' => $store_id,       
                        'location_name' => $storename,
                        'product_id' => (int)$prodId,   
                        'product_name' => $product['name'],   
                        'cat_id' => $product['cat_id'],
                        'cat_name' => $product['cat_name'],
                        'subcat_id' => $product['subcat_id'],
                        'subcat_name' => $product['subcat_name'],
                        'unit' => $product['unit'],
                        'unit_name' => $product['unit_name'],
                        'price' => $price,
                        'sync' => 0,
                        'local_sync' => 0
                    ];
                }
            }

            $this->response->type('json');
            if (!empty($saveData) && $this->Catalog_product_branches->saveAll($saveData)) {
                $this->Flash->success(__('Products assigned successfully.'));
                echo json_encode(['status' => true]);
            } else {
                echo json_encode(['status' => false]);
            }
        }
    }

    public function change_store_product_status() {
        $this->autoRender = false;

        $this->loadModel('Catalog_product_branches');
        if ($this->request->is('post')) {
            $product_id = $this->request->data['product_id'];
            $location_id = $this->request->data['loc_row_id'];
              $category =  $this->Catalog_product_branches->updateAll(
                    ['status' => 0,'sync' => 0,'local_sync' => 0],
                    ['product_id' => $product_id, 'location_id' => $location_id]
                );
                if ($category) {
                    echo json_encode(['status' => true]);
                } else {
                    echo json_encode(['status' => false]);
                }

        }
    }

    public function copy_store_categories() {
        $this->autoRender = false;

        if ($this->request->is('post')) {
             $current_store_id = $this->request->data['store_id'];
             $copy_from_store = $this->request->data['copy_from_store'];

            $existingCategories = $this->Catalog_category_branches->find('list', array(
                'fields' => ['category_id', 'category_id'],
                'conditions' => array(
                    'location_id' => $current_store_id,
                    'status' => 1
                )
            ));
            if(!empty($existingCategories)){
                $data = $this->Catalog_category_branches->find('all', array(
                    'conditions' => array('location_id' =>$copy_from_store, 'status' => 1, 'NOT' => ['category_id' => $existingCategories]),
                    'order' => array(
                        'id' => 'DESC')
                ));

            }
            else{
                $data = $this->Catalog_category_branches->find('all', array(
                    'conditions' => array('location_id' =>$copy_from_store, 'status' => 1),
                    'order' => array(
                        'id' => 'DESC')
                ));
            }

            $locationname = $this->Location->field('name', array('id' => $current_store_id));

            $saveData = [];
            foreach ($data as $item) {
                $saveData[] = [
                    'location_id' => $current_store_id,
                    'category_id' => $item['Catalog_category_branches']['category_id'],
                    'category_name' => $item['Catalog_category_branches']['category_name'],
                    'location_name' => $locationname,
                    'sync'=>0,
                    'local_sync'=>0
                ];
            }
            if (!empty($saveData)) {

                if ($this->Catalog_category_branches->saveAll($saveData)) {
                    $this->Flash->success(__('Categories copied successfully.'));
                    $this->redirect(array('action' => 'store_category','?'=>array('location_id'=>$current_store_id)));
                    $this->autoRender = false;
                } else {
                    $this->Flash->error(__('Categories could not be copied. Please, try again.'));
                    $this->redirect(array('action' => 'store_category','?'=>array('location_id'=>$current_store_id)));
                }

            } else {
                $this->Flash->success(__('No categories to copy from the selected store.'));
                $this->redirect(array('action' => 'store_category','?'=>array('location_id'=>$current_store_id)));
            }
        }
    }

    public function copy_store_products() {
        $this->autoRender = false;
        $this->loadModel('Catalog_product_branches');

        if ($this->request->is('post')) {
             $current_store_id = $this->request->data['store_id'];
             $copy_from_store = $this->request->data['copy_from_store'];

            $existingProducts = $this->Catalog_product_branches->find('list', array(
                'fields' => ['product_id', 'product_id'],
                'conditions' => array(
                    'location_id' => $current_store_id,
                    'status' => 1
                )
            ));

            if(!empty($existingProducts)){
                $data = $this->Catalog_product_branches->find('all', array(
                    'conditions' => array('location_id' =>$copy_from_store, 'status' => 1, 'NOT' => ['product_id' => $existingProducts]),
                    'order' => array(
                        'id' => 'DESC'
                    )
                ));

            }else{
                $data = $this->Catalog_product_branches->find('all', array(
                    'conditions' => array('location_id' =>$copy_from_store, 'status' => 1),
                    'order' => array(
                        'id' => 'DESC'
                    )
                ));
            }

            $locationname = $this->Location->field('name', array('id' => $current_store_id));
            $saveData = [];
            foreach ($data as $item) {
                $saveData[] = [
                    'location_id' => $current_store_id,
                    'location_name' => $locationname,
                    'product_id' => $item['Catalog_product_branches']['product_id'],
                    'product_name' => $item['Catalog_product_branches']['product_name'],
                    'cat_id' => $item['Catalog_product_branches']['cat_id'],
                    'cat_name' => $item['Catalog_product_branches']['cat_name'],
                    'subcat_id' => $item['Catalog_product_branches']['subcat_id'],
                    'subcat_name' => $item['Catalog_product_branches']['subcat_name'],
                    'price' => $item['Catalog_product_branches']['price'],
                    'unit' => $item['Catalog_product_branches']['unit'],
                    'unit_name' => $item['Catalog_product_branches']['unit_name'],
                    'sync'=>0,
                    'local_sync'=>0
                ];
            }
            if (!empty($saveData)) {

                if ($this->Catalog_product_branches->saveAll($saveData)) {
                    $this->Flash->success(__('Products copied successfully.'));
                    $this->redirect(array('action' => 'store_products','?'=>array('location_id'=>$current_store_id)));
                    $this->autoRender = false;
                } else {
                    $this->Flash->error(__('Products could not be copied. Please, try again.'));
                    $this->redirect(array('action' => 'store_products','?'=>array('location_id'=>$current_store_id)));
                }

            } else {
                $this->Flash->success(__('No Products to copy from the selected store.'));
                $this->redirect(array('action' => 'store_products','?'=>array('location_id'=>$current_store_id)));
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

        $indents = $this->Audit_stock->query("SELECT * FROM pos_auditstock WHERE pos_audit_id = $id");

        if (empty($indents)) {
            $this->Session->setFlash('No audit found for this record.', 'flash_error');
            return $this->redirect(['action' => 'view_audit']);
        }

        $audit = $indents[0]['pos_auditstock'];

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

        $sheet->setCellValue('A1', 'Audit ID:')
              ->setCellValueExplicit('B1', (string)$audit['pos_audit_id'], PHPExcel_Cell_DataType::TYPE_STRING)
              ->setCellValue('C1', 'Store Name:')
              ->setCellValue('D1', $audit['store_name']);

        $sheet->setCellValue('A2', 'Remarks:')
              ->setCellValue('B2', $audit['remarks'])
              ->setCellValue('C2', 'Audit Date:')
              ->setCellValue('D2', $audit['updateon']);

        $sheet->getStyle('A1:D2')->getFont()->setBold(true)->setSize(10);
        $sheet->getStyle('A1:D2')->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);

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

        foreach (range('A', 'J') as $columnID) {
            $sheet->getColumnDimension($columnID)->setAutoSize(true);
        }

        $sheet->setTitle('Audit Report');
        $objPHPExcel->setActiveSheetIndex(0);

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

    public function create_offers() {
        if(!$this->Session->check('Auth.User.id')){
            $this->redirect(array('controller'=>'dashboard','action' => 'login'));
        }
        $this->layout = "users";

        $startDate = date("Y-m-d");
        $startTime = date("H:i");

        $authUser = $this->Session->read('Auth.User');
        $userRole = $authUser['role'] ?? '';
        $userLocationId = $authUser['location_ids'] ?? '';

        $endTimestamp = strtotime("+1 hour");
        $endDate = date("Y-m-d", $endTimestamp);
        $endTime = date("H:i", $endTimestamp);

        $this->set(compact(
            'startDate',
            'startTime',
            'endDate',
            'endTime'
        ));

        $categorys = $this->Catalog_category->find('all', ['conditions' => ['status' => 1]]);
        $options = [];

        foreach ($categorys as $category) {
            $cat_id = $category['Catalog_category']['id'];
            $cat_name = $category['Catalog_category']['name'];

            $subcategorys = $this->Catalog_subcategory->find('all', [
                'conditions' => ['cat_id' => $cat_id, 'status' => 1]
            ]);

            if ($subcategorys) {
                $options[$cat_name] = [];
                foreach ($subcategorys as $sub) {

                    $sub_id = $sub['Catalog_subcategory']['id'];
                    $sub_name = $sub['Catalog_subcategory']['name'];
                    $options[$cat_name]["sub_$sub_id"] = $sub_name;
                }
            } else {
                $options["cat_$cat_id"] = $cat_name;
            }
        }
        $this->set(compact('options'));

        $user="SELECT * FROM `catalog_product`  where status=1 order by catalog_product.name ASC";
        $users=$this->Catalog_product->query($user);
        $this->set(compact('users'));
        $this->set(compact('id'));

        $conditions = ['status' => 1];

        if ($userRole !== 'admin' && !empty($userLocationId)) {
            $ids = array_filter(array_map('trim', explode(',', $userLocationId)));
            if (!empty($ids)) {
                $conditions['id IN'] = $ids;
            }
        }

        $locations=$this->Location->find('all',['conditions' => $conditions]);
        $this->set(compact('locations'));

        if ($this->request->is('post')) {
            $offer_name = $this->request->data('offer_name');
            $offer_type = $this->request->data('offer_type');
            $apply_type = $this->request->data('type');

            $start_date = $this->request->data('start_date');
            $start_time = $this->request->data('start_time');
            $end_date   = $this->request->data('end_date');
            $end_time   = $this->request->data('end_time');
            $days       = $this->request->data('days') ? $this->request->data('days') : 'Monday,Tuesday,Wednesday,Thursday,Friday,Saturday,Sunday';
            $location   = $this->request->data('location');
            if (!is_array($location)) {
                $location = [$location];
            }

            $offer_start = $start_date . " " . $start_time;
            $offer_end   = $end_date   . " " . $end_time;

            $checkedData = json_decode($this->request->data('checkedData'), true);
            $categoryInput = $this->request->data('category');

            $itemsToSave = [];
            $category_id = null;
            $category_name = null;
            $subcategory_id = null;
            $subcategory_name = null;

            if ($apply_type == 'category') {

                if (strpos($categoryInput, 'cat_') === 0) {

                    $category_id = (int) str_replace('cat_', '', $categoryInput);

                    $cat = $this->Catalog_category->findById($category_id);
                    if ($cat) {
                        $category_name = $cat['Catalog_category']['name'];
                    }

                    $products = $this->Catalog_product->find('all', [
                        'conditions' => ['cat_id' => $category_id, 'status' => 1]
                    ]);

                    foreach ($products as $p) {
                        $itemsToSave[] = [
                            'sku_id'      => $p['Catalog_product']['id'],
                            'sku_name'    => $p['Catalog_product']['name'],
                            'cat_id'      => $category_id,
                            'cat_name'    => $category_name,
                            'subcat_id'   => null,
                            'subcat_name' => null
                        ];
                    }
                }

                elseif (strpos($categoryInput, 'sub_') === 0) {

                    $subcategory_id = (int) str_replace('sub_', '', $categoryInput);

                    $subcat = $this->Catalog_subcategory->findById($subcategory_id);

                    if ($subcat) {
                        $subcategory_name = $subcat['Catalog_subcategory']['name'];
                        $category_id      = $subcat['Catalog_subcategory']['cat_id'];
                        $category_name    = $this->Catalog_category->field('name', ['id' => $category_id]);
                    }

                    $products = $this->Catalog_product->find('all', [
                        'conditions' => [
                            'cat_id'    => $category_id,
                            'subcat_id' => $subcategory_id,
                            'status'    => 1
                        ]
                    ]);

                    foreach ($products as $p) {
                        $itemsToSave[] = [
                            'sku_id'      => $p['Catalog_product']['id'],
                            'sku_name'    => $p['Catalog_product']['name'],
                            'cat_id'      => $category_id,
                            'cat_name'    => $category_name,
                            'subcat_id'   => $subcategory_id,
                            'subcat_name' => $subcategory_name
                        ];
                    }
                }
            }elseif ($apply_type == 'item') {

                foreach ($checkedData as $i) {
                    $itemsToSave[] = [
                        'sku_id'        => $i['id'],
                        'sku_name'      => $i['sku_name'],
                        'cat_id'        => $i['cat_id'],
                        'cat_name'      => $i['cat_name'],
                        'subcat_id'     => $i['subcat_id'],
                        'subcat_name'   => $i['subcat_name']
                    ];
                }
            }

            do {
                $txnid = rand(1, 9);
                for ($i = 0; $i < 10; $i++) {
                    $txnid .= rand(0, 9);
                }

                $existing = $this->Offer->find('first', [
                    'conditions' => ['offer_id' => $txnid]
                ]);
            } while ($existing);

            if($itemsToSave){

                foreach ($itemsToSave as $product) {

                    foreach ($location as $locId) {

                        $locationdetail = $this->Location->find('first', array(
                            'conditions' => array('Location.id' => $locId)
                        ));

                        if (!empty($locationdetail)) {
                            $location_name = $locationdetail['Location']['name'];
                        }

                        $saveData = [
                            'Offer' => [
                                'offer_id'     => $txnid,

                                'name'         => $offer_name,
                                'offer_type'   => $offer_type,
                                'item_type'    => $apply_type,

                                'location_id'  => $locId,
                                'location_name'=> $location_name,

                                'cat_id'       => $product['cat_id'],
                                'cat_name'     => $product['cat_name'],
                                'subcat_id'    => $product['subcat_id'],
                                'subcat_name'  => $product['subcat_name'],

                                'sku_id'       => $product['sku_id'],
                                'sku_name'     => $product['sku_name'],

                                'start_date'   => $start_date,
                                'start_time'   => $start_time,
                                'end_date'     => $end_date,
                                'end_time'     => $end_time,
                                'days'         => $days,

                                'status'       => 1,
                                'sync'=>0,
                                'local_sync'=>0
                            ]
                        ];

                        $this->Offer->create();
                        $this->Offer->save($saveData);
                    }
                }

                echo json_encode([
                    'status' => true,
                    'msg'    => "Offer Saved Successfully"
                ]);
                exit;
            }else{
                echo json_encode([
                    'status' => false,
                    'msg'    => "No Item found"
                ]);
                exit;
            }

        }
    }

    public function offers() {
        if(!$this->Session->check('Auth.User.id')){
            $this->redirect(array('controller'=>'dashboard','action' => 'login'));
        }
        $authUser = $this->Session->read('Auth.User');
        $userRole = $authUser['role'] ?? '';
        $userLocationId = $authUser['location_ids'] ?? 0;
        $this->layout = "users";
        $startDate = date('m/d/Y', strtotime('-12 months'));
        $endDate = date('m/d/Y');

        if($userRole == 'subadmin'){
            $id = $authUser['id'];
            $locations = $this->User_location->find('list', [
                'conditions' => ['status' => 1, 'user_id' => $id],
                'fields' => ['location_id']
            ]);
            $userLocationId = implode(',', array_values($locations));
        }

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

        $locationCondition = '';
        if ($userRole !== 'admin') {
            $locationCondition = "AND location_id IN (" . $userLocationId .")";
        }

        $_shiftRange = $this->getShiftDateRange($startdate, $enddate);
        $startDatetime = $_shiftRange['startDatetime'];
        $endDatetime   = $_shiftRange['endDatetime'];

        $users = $this->Offer->query("
            SELECT
                offer_id,
                name,
                offer_type,
                start_date,
                end_date,
                start_time,
                end_time,
                MAX(days) AS days,
                created,
                MAX(status) AS offer_status
            FROM pos_offers
            WHERE created BETWEEN '$startDatetime' AND '$endDatetime' and status=1
            $locationCondition
            GROUP BY offer_id
            ORDER BY MAX(id) DESC
        ");
        $yourStartDateValue=$startDate . ' - ' . $endDate;
        $this->set('startdate', $yourStartDateValue);

        $this->set(compact('users'));

    }

    public function offer_change_status() {
        $this->autoRender = false;

        if ($this->request->is('post')) {
            $Id = $this->request->data['_id'];

            $current = $this->Offer->find('first', [
                'conditions' => ['Offer.offer_id' => $Id],
                'fields' => ['Offer.status']
            ]);

            if ($current) {

                $this->Offer->updateAll(
                    [
                        'Offer.status' => 0,
                        'Offer.sync'       => 0,
                        'Offer.local_sync' => 0
                    ],
                    ['Offer.offer_id' => $Id]
                );

                echo json_encode(['status' => true]);
            }else {
                echo json_encode(['status' => false]);
            }
        }
    }

    public function view_offer()
    {
        if(!$this->Session->check('Auth.User.id')){
            $this->redirect(array('controller'=>'dashboard','action' => 'login'));
        }

        $this->layout = "users";
        $id=$_REQUEST['id'];

        $indents = $this->Offer->query("SELECT
            offer_id,
            GROUP_CONCAT(DISTINCT location_name ORDER BY location_name SEPARATOR ', ') AS locations,
            MAX(name)        AS name,
            MAX(sku_name)        AS sku_name,
            MAX(cat_name)        AS cat_name,
            MAX(subcat_name)        AS subcat_name,
            MAX(item_type) AS item_type,
            MAX(offer_type) AS offer_type,
            MAX(start_date) AS start_date,
            MAX(end_date)   AS end_date,
            MAX(start_time) AS start_time,
            MAX(end_time)   AS end_time,
            MAX(days)       AS days
        FROM pos_offers
        WHERE status = 1
          AND offer_id = '$id'
        GROUP BY offer_id;
        ");

        $items = $this->Offer->query("
            SELECT
                sku_name,
                cat_name,
                subcat_name
            FROM pos_offers
            WHERE status = 1
              AND offer_id = '$id' group by sku_id
            ORDER BY sku_name ASC
        ");

        $this->set(compact('indents', 'items'));

    }

    public function export_offer_details()
    {
        if (!$this->Session->check('Auth.User.id')) {
            $this->redirect(array('controller' => 'dashboard', 'action' => 'login'));
        }

        $this->autoRender = false;
        $id = isset($_REQUEST['id']) ? trim($_REQUEST['id']) : '';

        if (empty($id)) {
            $this->Session->setFlash(__('Invalid Offer ID'));
            return $this->redirect($this->referer());
        }

        $indents = $this->Offer->query("SELECT
            offer_id,
            GROUP_CONCAT(DISTINCT location_name ORDER BY location_name SEPARATOR ', ') AS locations,
            MAX(name)        AS name,
            MAX(sku_name)    AS sku_name,
            MAX(cat_name)    AS cat_name,
            MAX(subcat_name) AS subcat_name,
            MAX(item_type)   AS item_type,
            MAX(offer_type)  AS offer_type,
            MAX(start_date)  AS start_date,
            MAX(end_date)    AS end_date,
            MAX(start_time)  AS start_time,
            MAX(end_time)    AS end_time,
            MAX(days)        AS days
        FROM pos_offers
        WHERE status = 1
          AND offer_id = '$id'
        GROUP BY offer_id;
        ");

        $items = $this->Offer->query("
            SELECT
                sku_name,
                cat_name,
                subcat_name
            FROM pos_offers
            WHERE status = 1
              AND offer_id = '$id' GROUP BY sku_id
            ORDER BY sku_name ASC
        ");

        if (empty($indents)) {
            $this->Session->setFlash(__('Offer details not found.'));
            return $this->redirect($this->referer());
        }

        App::import('Vendor', 'PHPExcel/PHPExcel');
        $objPHPExcel = new PHPExcel();
        $objPHPExcel->getProperties()->setCreator("POS System")
            ->setTitle("Offer Details - " . $id);

        $sheet = $objPHPExcel->getActiveSheet();
        $sheet->setTitle('Offer Details');

        $offerName = isset($indents[0][0]['name']) ? $indents[0][0]['name'] : '';
        $sheet->setCellValue('A1', 'OFFER DETAILS - ' . strtoupper($offerName));
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->mergeCells('A1:D1');

        $sheet->setCellValue('A3', 'Offer Information');
        $sheet->getStyle('A3')->getFont()->setBold(true)->setSize(12);

        $offerData = isset($indents[0][0]) ? $indents[0][0] : array();
        $offerIdVal = isset($indents[0]['pos_offers']['offer_id']) ? $indents[0]['pos_offers']['offer_id'] : $id;

        $daysVal = isset($offerData['days']) && !empty($offerData['days']) ? ($offerData['days'] === 'full_week' || count(explode(',', $offerData['days'])) >= 7 ? 'Full Week' : $offerData['days']) : 'Full Week';
        $infoRows = array(
            array('Offer ID', $offerIdVal, 'Name', isset($offerData['name']) ? $offerData['name'] : ''),
            array('Offer Type', isset($offerData['offer_type']) ? $offerData['offer_type'] : '', 'Location Name', isset($offerData['locations']) ? $offerData['locations'] : ''),
            array('Type', isset($offerData['item_type']) ? $offerData['item_type'] : '', 'Start Date', isset($offerData['start_date']) ? $offerData['start_date'] : ''),
            array('End Date', isset($offerData['end_date']) ? $offerData['end_date'] : '', 'Start Time', isset($offerData['start_time']) ? $offerData['start_time'] : ''),
            array('End Time', isset($offerData['end_time']) ? $offerData['end_time'] : '', 'Applicable Days', $daysVal)
        );

        $rowNum = 4;
        foreach ($infoRows as $info) {
            $sheet->setCellValue('A' . $rowNum, $info[0]);
            $sheet->setCellValue('B' . $rowNum, $info[1]);
            $sheet->setCellValue('C' . $rowNum, $info[2]);
            $sheet->setCellValue('D' . $rowNum, $info[3]);

            $sheet->getStyle('A' . $rowNum)->getFont()->setBold(true);
            $sheet->getStyle('C' . $rowNum)->getFont()->setBold(true);

            $sheet->getStyle('A' . $rowNum)->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID);
            $sheet->getStyle('A' . $rowNum)->getFill()->getStartColor()->setRGB('F2F2F2');
            if (!empty($info[2])) {
                $sheet->getStyle('C' . $rowNum)->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID);
                $sheet->getStyle('C' . $rowNum)->getFill()->getStartColor()->setRGB('F2F2F2');
            }

            $rowNum++;
        }

        $rowNum++;

        $sheet->setCellValue('A' . $rowNum, 'Offer Items');
        $sheet->getStyle('A' . $rowNum)->getFont()->setBold(true)->setSize(12);
        $rowNum++;

        $sheet->setCellValue('A' . $rowNum, '#');
        $sheet->setCellValue('B' . $rowNum, 'Item Name');
        $sheet->setCellValue('C' . $rowNum, 'Category');
        $sheet->setCellValue('D' . $rowNum, 'Sub Category');

        $headerStyle = $sheet->getStyle('A' . $rowNum . ':D' . $rowNum);
        $headerStyle->getFont()->setBold(true);
        $headerStyle->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID);
        $headerStyle->getFill()->getStartColor()->setRGB('D9EAD3');
        $rowNum++;

        $srNo = 1;
        if (!empty($items)) {
            foreach ($items as $item) {
                $skuName    = isset($item['pos_offers']['sku_name']) ? $item['pos_offers']['sku_name'] : '';
                $catName    = isset($item['pos_offers']['cat_name']) ? $item['pos_offers']['cat_name'] : '';
                $subcatName = isset($item['pos_offers']['subcat_name']) ? $item['pos_offers']['subcat_name'] : '';

                $sheet->setCellValue('A' . $rowNum, $srNo);
                $sheet->setCellValue('B' . $rowNum, $skuName);
                $sheet->setCellValue('C' . $rowNum, $catName);
                $sheet->setCellValue('D' . $rowNum, $subcatName);

                $srNo++;
                $rowNum++;
            }
        } else {
            $sheet->setCellValue('A' . $rowNum, 'No items found for this offer.');
            $sheet->mergeCells('A' . $rowNum . ':D' . $rowNum);
            $rowNum++;
        }

        foreach (range('A', 'D') as $colLetter) {
            $sheet->getColumnDimension($colLetter)->setAutoSize(true);
        }

        $filename = 'Offer_Details_' . $id . '_' . date('Ymd_His') . '.xls';
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        header('Cache-Control: max-age=1');
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
        header('Cache-Control: cache, must-revalidate');
        header('Pragma: public');

        $writer = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $writer->save('php://output');
        exit;
    }

    public function edit_offer()
    {

        if (!$this->Session->check('Auth.User.id')) {
            return $this->redirect(['controller' => 'dashboard', 'action' => 'login']);
        }

        $this->layout = "users";

        $offer_id = $this->request->query('id');
        if (empty($offer_id)) {
            return $this->redirect(['action' => 'offers']);
        }

        $offerRows = $this->Offer->find('all', [
            'conditions' => [
                'Offer.offer_id' => $offer_id,
                'Offer.status'   => 1
            ]
        ]);

        if (empty($offerRows)) {
            return $this->redirect(['action' => 'offers']);
        }

        $offer = $offerRows[0]['Offer'];

        $selectedSkuIds = array_unique(
            array_filter(
                Hash::extract($offerRows, '{n}.Offer.sku_id')
            )
        );

        $allLocations = $this->Location->find('list', [
            'conditions' => ['Location.status' => 1],
            'order'      => ['Location.name' => 'ASC']
        ]);

        $selectedLocationIds = array_unique(
            array_filter(
                Hash::extract($offerRows, '{n}.Offer.location_id')
            )
        );

        $locations = [];

        foreach ($selectedLocationIds as $locId) {
            if (isset($allLocations[$locId])) {
                $locations[$locId] = $allLocations[$locId];
                unset($allLocations[$locId]);
            }
        }

        foreach ($allLocations as $id => $name) {
            $locations[$id] = $name;
        }

        $this->set(compact('locations', 'selectedLocationIds'));

        $this->set([
            'offer'          => $offer,
            'offerRows'      => $offerRows,
            'selectedSkuIds' => $selectedSkuIds,
            'startDate'      => $offer['start_date'],
            'startTime'      => $offer['start_time'],
            'endDate'        => $offer['end_date'],
            'endTime'        => $offer['end_time']
        ]);

        $options = [];

        $categories = $this->Catalog_category->find('all', [
            'conditions' => ['Catalog_category.status' => 1],
            'order'      => ['Catalog_category.name' => 'ASC']
        ]);

        foreach ($categories as $cat) {
            $catId   = $cat['Catalog_category']['id'];
            $catName = $cat['Catalog_category']['name'];

            $subs = $this->Catalog_subcategory->find('all', [
                'conditions' => [
                    'Catalog_subcategory.cat_id' => $catId,
                    'Catalog_subcategory.status' => 1
                ],
                'order' => ['Catalog_subcategory.name' => 'ASC']
            ]);

            if (!empty($subs)) {

                $options[$catName] = [];
                foreach ($subs as $s) {

                    $options[$catName]['sub_' . $s['Catalog_subcategory']['id']] =
                        $s['Catalog_subcategory']['name'];
                }
            } else {

                $options['cat_' . $catId] = $catName;
            }
        }

        $selectedCategory = null;

        if (!empty($offer['subcat_id'])) {

            $selectedCategory = 'sub_' . $offer['subcat_id'];
        } elseif (!empty($offer['cat_id'])) {

            $selectedCategory = 'cat_' . $offer['cat_id'];
        }

        $this->set(compact('options', 'selectedCategory'));

        $users = $this->Catalog_product->find('all', [
            'conditions' => ['Catalog_product.status' => 1],
            'order'      => ['Catalog_product.name' => 'ASC']
        ]);
        $this->set(compact('users'));

        if ($this->request->is('post')) {

            $applyType  = $this->request->data('type');
            $checkedData = json_decode($this->request->data('checkedData'), true);
            $location = $this->request->data('location');

            if (!is_array($location)) {
                $location = [$location];
            }

            if (empty($location)) {
                echo json_encode(['status' => false, 'msg' => 'Location required']);
                return;
            }
            $itemsToSave = [];

            if ($applyType === 'category') {

                $categoryInput = $this->request->data('category');

                if (strpos($categoryInput, 'cat_') === 0) {

                    $category_id = (int) str_replace('cat_', '', $categoryInput);

                    $cat = $this->Catalog_category->findById($category_id);
                    $category_name = $cat ? $cat['Catalog_category']['name'] : null;

                    $products = $this->Catalog_product->find('all', [
                        'conditions' => [
                            'cat_id' => $category_id,
                            'status' => 1
                        ]
                    ]);

                    foreach ($products as $p) {
                        $itemsToSave[] = [
                            'sku_id'      => $p['Catalog_product']['id'],
                            'sku_name'    => $p['Catalog_product']['name'],
                            'cat_id'      => $category_id,
                            'cat_name'    => $category_name,
                            'subcat_id'   => null,
                            'subcat_name' => null
                        ];
                    }
                }

                elseif (strpos($categoryInput, 'sub_') === 0) {

                    $subcategory_id = (int) str_replace('sub_', '', $categoryInput);

                    $subcat = $this->Catalog_subcategory->findById($subcategory_id);

                    if ($subcat) {
                        $subcategory_name = $subcat['Catalog_subcategory']['name'];
                        $category_id      = $subcat['Catalog_subcategory']['cat_id'];
                        $category_name    = $this->Catalog_category->field('name', ['id' => $category_id]);
                    }

                    $products = $this->Catalog_product->find('all', [
                        'conditions' => [
                            'cat_id'    => $category_id,
                            'subcat_id' => $subcategory_id,
                            'status'    => 1
                        ]
                    ]);

                    foreach ($products as $p) {
                        $itemsToSave[] = [
                            'sku_id'      => $p['Catalog_product']['id'],
                            'sku_name'    => $p['Catalog_product']['name'],
                            'cat_id'      => $category_id,
                            'cat_name'    => $category_name,
                            'subcat_id'   => $subcategory_id,
                            'subcat_name' => $subcategory_name
                        ];
                    }
                }
            }

            elseif ($applyType === 'item' && !empty($checkedData)) {

                foreach ($checkedData as $i) {
                    $itemsToSave[] = [
                        'sku_id'      => $i['id'],
                        'sku_name'    => $i['sku_name'],
                        'cat_id'      => $i['cat_id'],
                        'cat_name'    => $i['cat_name'],
                        'subcat_id'   => $i['subcat_id'],
                        'subcat_name' => $i['subcat_name']
                    ];
                }
            }

            if($itemsToSave){

                $this->Offer->updateAll(
                    [
                        'Offer.status'     => 0,
                        'Offer.sync'       => 0,
                        'Offer.local_sync' => 0
                    ],
                    [
                        'Offer.offer_id' => $offer_id
                    ]
                );

                foreach ($itemsToSave as $product) {

                    foreach ($location as $locId) {

                        $locationdetail = $this->Location->find('first', [
                            'conditions' => ['Location.id' => $locId]
                        ]);

                        $location_name = !empty($locationdetail)
                            ? $locationdetail['Location']['name']
                            : null;

                        $this->Offer->create();
                        $this->Offer->save([
                            'Offer' => [
                                'offer_id'      => $offer_id,
                                'name'          => $this->request->data('offer_name'),
                                'offer_type'    => $this->request->data('offer_type'),
                                'item_type'     => $applyType,

                                'location_id'   => $locId,
                                'location_name' => $location_name,

                                'cat_id'        => $product['cat_id'],
                                'cat_name'      => $product['cat_name'],
                                'subcat_id'     => $product['subcat_id'],
                                'subcat_name'   => $product['subcat_name'],

                                'sku_id'        => $product['sku_id'],
                                'sku_name'      => $product['sku_name'],

                                'start_date'    => $this->request->data('start_date'),
                                'start_time'    => $this->request->data('start_time'),
                                'end_date'      => $this->request->data('end_date'),
                                'end_time'      => $this->request->data('end_time'),
                                'days'          => $this->request->data('days') ? $this->request->data('days') : 'Monday,Tuesday,Wednesday,Thursday,Friday,Saturday,Sunday',

                                'status'        => 1,
                                'sync'          => 0,
                                'local_sync'    => 0
                            ]
                        ]);
                    }
                }
                echo json_encode([
                    'status' => true,
                    'msg'    => "Offer Updated Successfully"
                ]);
                exit;
            }else{
                echo json_encode([
                    'status' => false,
                    'msg'    => "No Item found"
                ]);
                exit;
            }

        }
    }

    public function po_approve_list()
    {

        if (!$this->Session->check('Auth.User.id')) {
            return $this->redirect(['controller' => 'dashboard', 'action' => 'login']);
        }

        $this->layout = "users";
        $authUser = $this->Session->read('Auth.User');
        $userRole = $authUser['role'] ?? '';
        $locationIds = $authUser['location_ids'] ?? '';

        $startDate = date('m/d/Y', strtotime('-1 month'));
        $endDate   = date('m/d/Y');
        $startDateFormatted = date('Y-m-d', strtotime('-1 month'));
        $endDateFormatted   = date('Y-m-d');

        if ($this->request->is(['post', 'put'])) {
            $daterange = $this->request->data['daterange'] ?? '';
            if (strpos($daterange, '-') !== false) {
                list($startRaw, $endRaw) = explode(" - ", $daterange);
                $startDate = trim($startRaw);
                $endDate   = trim($endRaw);
                $startDateFormatted = date('Y-m-d', strtotime($startDate));
                $endDateFormatted   = date('Y-m-d', strtotime($endDate));
            }
        }

        $conditions2 = ['status' => 1];

        if ($userRole !== 'admin' && !empty($locationIds)) {

            $userLocationIds = Hash::extract(
                ['ids' => is_array($locationIds) ? $locationIds : explode(',', $locationIds)],
                'ids.{n}'
            );
            $conditions2['id IN'] = array_map('trim', $userLocationIds);
        }

        $stores = $this->Location->find('all', [
            'conditions' => $conditions2
        ]);

        $_shiftRange = $this->getShiftDateRange($startDateFormatted, $endDateFormatted);
        $startDatetime = $_shiftRange['startDatetime'];
        $endDatetime   = $_shiftRange['endDatetime'];

        $poConditions = [
            "direct_purchase" => 0,
            "created >=" => $startDatetime,
            "created <=" => $endDatetime,
        ];

        if ($userRole !== 'admin' && !empty($conditions2['id IN'])) {
            $poConditions['location_id IN'] = $conditions2['id IN'];
        }

        if (!empty($this->request->data['Search']['cashier'])) {
            $cashierId = (int)$this->request->data['Search']['cashier'];
            $poConditions['location_id'] = $cashierId;
        }

        $purchaseOrders = $this->Inventory_purchase_order->find('all', [
            'conditions' => $poConditions,
            'order' => ['id' => 'DESC']
        ]);

        $this->set(compact('stores', 'purchaseOrders'));
        $this->set('startdate', $startDate . ' - ' . $endDate);
    }

    public function view_approve_po()
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

    public function update_approve_po()
    {
        $this->autoRender = false;

        if ($this->request->is('post')) {
            $poid = $this->request->data['_id'];
            $items = $this->request->data['_items'];

            $today = date('Y-m-d H:i:s');
            $id = $this->Session->read('Auth.User.id');

            $user = $this->User->find('first', [
                'conditions' => ['User.id' => $id],
                'fields' => ['User.name'],
                'recursive' => -1
            ]);
            $name = $user['User']['name'];

            $amount = 0;

            foreach ($items as $item) {
                $itemId = (int)$item['item_id'];
                $quantity = (float)$item['quantity'];

                $poItem = $this->Inventory_purchase_order_item->find('first', [
                    'conditions' => ['Inventory_purchase_order_item.id' => $itemId,'Inventory_purchase_order_item.status' => 1],
                    'fields' => ['Inventory_purchase_order_item.sku_id','sku_price'],
                    'recursive' => -1
                ]);
                $sku_id = $poItem['Inventory_purchase_order_item']['sku_id'];
                $price = $poItem['Inventory_purchase_order_item']['sku_price'];

                $taxResult = $this->Inventory_sku_tax->query(
                    "SELECT SUM(tax_percentage) AS total_tax FROM inventory_sku_tax WHERE product_id = '$sku_id' and status=1"
                );
                $total_tax = round($taxResult[0][0]['total_tax'], 2);

                $tax_value = round(($price * $quantity) * ($total_tax / 100), 2);
                $skuamount = round(($price * $quantity) + $tax_value, 2);

                $this->Inventory_purchase_order_item->updateAll(
                    [
                        'Inventory_purchase_order_item.approved_quantity' => $quantity,
                        'Inventory_purchase_order_item.approved_total_tax_value' => $tax_value,
                        'Inventory_purchase_order_item.approved_amount' => $skuamount,
                        'Inventory_purchase_order_item.status' => 2,
                        'Inventory_purchase_order_item.sync' => 0,
                        'Inventory_purchase_order_item.local_sync' => 0
                    ],
                    [
                        'Inventory_purchase_order_item.id' => $itemId,
                        'Inventory_purchase_order_item.p_o_id' => $poid,
                        'Inventory_purchase_order_item.status' => 1
                    ]
                );

                $amount += $skuamount;
            }

            $this->Inventory_purchase_order->updateAll(
                [
                    'Inventory_purchase_order.status' => 2,
                    'Inventory_purchase_order.approved_by_id' => $id,
                    'Inventory_purchase_order.approved_by_name' => "'$name'",
                    'Inventory_purchase_order.approved_on' => "'$today'",
                    'Inventory_purchase_order.approved_amount' => $amount,
                    'Inventory_purchase_order.sync' => 0,
                    'Inventory_purchase_order.local_sync' => 0
                ],
                [
                    'Inventory_purchase_order.p_o_id' => $poid,
                    'Inventory_purchase_order.status' => 1
                ]
            );

            echo json_encode(['status' => true]);
            exit;
        }

    }

    public function update_location_publish()
    {
        $this->autoRender = false;

        if ($this->request->is('post')) {

            $store_id = $this->request->data('store_id');

            if (!empty($store_id)) {

                $this->Location->updateAll(
                    ['Location.is_publish' => '0'],
                    ['Location.id' => $store_id]
                );
            } else {

                $this->Location->updateAll(
                    ['Location.is_publish' => '0']
                );
            }

            echo json_encode([
                'success' => true
            ]);
        }
    }

    private function _getLastSyncTimes()
    {
        $filePath = TMP . 'sync_status.json';
        if (!file_exists($filePath)) {
            return ['push' => null, 'pull' => null];
        }
        return json_decode(file_get_contents($filePath), true) ?: ['push' => null, 'pull' => null];
    }

    public function sync_urbanpiper()
    {
        $this->autoRender = false;

        if (!$this->Session->check('Auth.User.id')) {
            $responseArray = array('response_code' => 0, 'response_message' => 'Session expired, please logout and login again');
            echo json_encode($responseArray);
            return;
        }

        if (!$this->request->is('post') || empty($this->request->data['store_id'])) {
            $responseArray = array('response_code' => 0, 'response_message' => 'Invalid request or missing Store ID');
            echo json_encode($responseArray);
            return;
        }

        $store_id = $this->request->data['store_id'];

        $this->loadModel('Catalog_product');
        $this->loadModel('Catalog_product_tax');
        $this->loadModel('Catalog_category');

        $products = array();
        $categories = array();

        $fetchProducts = "SELECT
            cp.id,
            cp.name as product_name,
            cp.cat_id,
            cp.cat_name,
            cp.subcat_id,
            cp.subcat_name,
            cp.description,
            cp.food_type,
            cpb.price as price,
            cp.markup_price,
            cp.weight,
            cp.serves_people,
            cp.image,
            cp.created
            FROM catalog_product cp
            INNER JOIN catalog_product_branches cpb ON cp.id = cpb.product_id
            WHERE cp.status = 1
            AND cpb.status = 1
            AND cpb.location_id = " . $store_id . "
            AND cp.price != 0 and cp.food_type IN (0,1)
            ORDER BY cp.id ASC";

        $productResult = $this->Catalog_product->query($fetchProducts);

        if (!$productResult) {
            $responseArray = array('response_code' => 0, 'response_message' => 'Database I/O error: Unable to fetch products');
            echo json_encode($responseArray);
            return;
        }

        foreach ($productResult as $row) {
            $product_id = $row['cp']['id'];
            $product_name = $row['cp']['product_name'];
            $cat_id = $row['cp']['cat_id'];
            $cat_name = $row['cp']['cat_name'];
            $price = $up_price = $row['cpb']['price'] ?? 0;
            $markup_price = $row['cp']['markup_price'];

            $description = $row['cp']['description'];

            if ($row['cp']['food_type'] == 0) {
                $food_type = 1;
            } elseif ($row['cp']['food_type'] == 1) {
                $food_type = 2;
            } else {
                $food_type = 1;
            }

            $image = $row['cp']['image'];
            $weight = $row['cp']['weight'];

            $taxQuery = "SELECT tax_name, tax_percentage
                        FROM catalog_product_tax
                        WHERE status!=0 and product_id = " . $product_id;
            $taxResult = $this->Catalog_product_tax->query($taxQuery);

            $tax_name = '';
            if (!empty($taxResult)) {
                $taxes = array();
                foreach ($taxResult as $tax) {
                    $taxes[] = $tax['catalog_product_tax']['tax_name'] . ' (' . $tax['catalog_product_tax']['tax_percentage'] . '%)';
                }
                $tax_name = implode(', ', $taxes);
            }
            $productImageUrl = '';
            if ($image != '' && $image != 'NULL') {
                $productImageUrl = Router::url(
                    '/uploads/product_images/' . $image,
                    true
                );
            }
            $products[] = array(
                'id' => $product_id,
                'product_name' => $product_name,
                'price' => $price,
                'markup_price' => $markup_price,
                'up_price' => $up_price,
                'cat_id' => $cat_id,
                'cat_name' => $cat_name,
                'description' => $description,
                'image' => $productImageUrl,
                'food_type' => $food_type,
                'weight' => $weight,
                'tax_name' => $tax_name
            );
        }

        $fetchCategories = "SELECT
            cc.id,
            cc.name as category_name,
            cc.image
            FROM catalog_category cc
            INNER JOIN catalog_category_branches ccb ON cc.id = ccb.category_id
            WHERE cc.status = 1
            AND ccb.status = 1
            AND ccb.location_id = " . $store_id . "
            ORDER BY cc.name ASC";

        $categoryResult = $this->Catalog_category->query($fetchCategories);

        if (!$categoryResult) {
            $responseArray = array('response_code' => 0, 'response_message' => 'Database I/O error: Unable to fetch categories');
            echo json_encode($responseArray);
            return;
        }

        foreach ($categoryResult as $row) {
            $categoryImageUrl = '';
            if ($row['cc']['image'] != '' && $row['cc']['image'] != 'NULL') {
                $categoryImageUrl = Router::url(
                    '/uploads/category_images/' . $row['cc']['image'],
                    true
                );
            }
            $categories[] = array(
                'id' => $row['cc']['id'],
                'category_name' => $row['cc']['category_name'],
                'description' => '',
                'image' => $categoryImageUrl
            );
        }

        $upoptiongroups = array();
        $upoptions = array();

        $upcategories = array();
        $upproducts = array();
        $taxprod = array();
        $uptaxes = array();
        $upcharges = array();

        if (!empty($products) && !empty($categories)) {

            $upcategories = [];
            $upproducts = [];
            $taxprod = [];

            $usedCategoryIds = [];

            foreach ($products as $prod) {

                $usedCategoryIds[] = $prod['cat_id'];
                $taxprod[] = $prod['id'];

                $upproducts[] = array(
                    "ref_id" => $prod['id'],
                    "title" => $prod['product_name'],
                    "available" => true,
                    "description" => !empty($prod['description']) ? $prod['description'] : "",
                    "sold_at_store" => true,
                    "sort_order" => 0,
                    "price" => floatval($prod['up_price']),
                    "markup_price" => floatval($prod['up_price']),
                    "current_stock" => -1,
                    "recommended" => false,
                    "food_type" => intval($prod['food_type']),
                    "category_ref_ids" => array($prod['cat_id']),
                    "fulfillment_modes" => array("delivery"),
                    "img_url" => $prod['image']
                );
            }

            $usedCategoryIds = array_unique($usedCategoryIds);

            foreach ($categories as $categ) {

                if (in_array($categ['id'], $usedCategoryIds)) {

                    $upcategories[] = array(
                        "ref_id" => $categ['id'],
                        "name" => $categ['category_name'],
                        "description" => "",
                        "sort_order" => 0,
                        "active" => true,
                        "img_url" => $categ['image'],
                        "parent_ref_id" => ""
                    );
                }
            }

            $totalTaxPercentage = 0;
            if (!empty($taxprod)) {
                $firstProductId = $taxprod[0];
                $taxQuery = "SELECT SUM(tax_percentage) as total_tax
                            FROM catalog_product_tax
                            WHERE status=1 and product_id = " . $firstProductId;
                $taxResult = $this->Catalog_product_tax->query($taxQuery);
                if (!empty($taxResult)) {
                    $totalTaxPercentage = floatval($taxResult[0][0]['total_tax']);
                }
            }

            $halfTax = $totalTaxPercentage / 2;

            if ($totalTaxPercentage > 0) {
                $structure_cgst = array("value" => $halfTax);
                $uptaxes[] = array(
                    "code" => "CGST_P",
                    "title" => "CGST",
                    "active" => true,
                    "description" => "CGST",
                    "structure" => $structure_cgst,
                    "item_ref_ids" => $taxprod
                );

                $structure_sgst = array("value" => $halfTax);
                $uptaxes[] = array(
                    "code" => "SGST_P",
                    "title" => "SGST",
                    "active" => true,
                    "description" => "SGST",
                    "structure" => $structure_sgst,
                    "item_ref_ids" => $taxprod
                );
            }

            $structurechgdcf = array("value" => 20);
            $upcharges[] = array(
                "code" => "DC_F",
                "title" => "Delivery Charge",
                "active" => true,
                "description" => "Delivery Charge",
                "structure" => $structurechgdcf,
                "fulfillment_modes" => array('delivery'),
                "item_ref_ids" => $taxprod
            );

            $structurechgpcp = array(
                "applicable_on" => "order.order_subtotal",
                "value" => 10
            );
            $upcharges[] = array(
                "code" => "PC_P",
                "title" => "Packaging Charge",
                "active" => true,
                "description" => "Packaging Charge",
                "structure" => $structurechgpcp,
                "fulfillment_modes" => array('delivery'),
                "item_ref_ids" => $taxprod
            );
        } else {
            echo json_encode(array('response_code' => 0, 'response_message' => 'No Data To Sync'));
            return;
        }
        $data = array();
        if (!empty($upcategories)) {
            $data["categories"] = $upcategories;
        }
        if (!empty($upproducts)) {
            $data["items"] = $upproducts;
        }
        if (!empty($upoptiongroups)) {
            $data["option_groups"] = $upoptiongroups;
        }
        if (!empty($upoptions)) {
            $data["options"] = $upoptions;
        }
        if (!empty($uptaxes)) {
            $data["taxes"] = $uptaxes;
        }
        if (!empty($upcharges)) {
            $data["charges"] = $upcharges;
        }

        $data["flush_items"] = true;
        $data["flush_options"] = true;

        $url = "https://api.urbanpiper.com/external/api/v1/inventory/locations/" . $store_id . "/";

        $retry_attempt = 0;
        $retry_status = 0;
        $json = json_encode($data);

        $limitQuery = "SELECT status, retry_attempt FROM urbanpiper_limits WHERE event_code = 'stop_api' LIMIT 1";
        $limitResult = $this->Catalog_product->query($limitQuery);

        if (!empty($limitResult)) {
            $retry_attempt = intval($limitResult[0]['urbanpiper_limits']['retry_attempt']);
            $retry_status = intval($limitResult[0]['urbanpiper_limits']['status']);
        }

        if ($retry_attempt < 3 && $retry_status == 0) {
            $json = json_encode($data);
            App::uses('HttpSocket', 'Network/Http');
            $http = new HttpSocket();

            $request = array(
                'header' => array(
                    'Authorization' => 'apikey biz_adm_egGcwzZpmqZk:1dda18a8837267a9ae5d630ed67fe220f517b911',
                    'Content-Type' => 'application/json'
                )
            );

            try {
                $response = $http->post($url, $json, $request);
                $httpcode = $http->response['status']['code'];

                if ($httpcode >= 500) {
                    $retry_attempt++;
                    $error = array(
                        'status' => 'error',
                        'reference' => 'NA',
                        'error' => $httpcode . ' error'
                    );
                    $response = json_encode($error);

                    if ($retry_attempt == 3) {
                        $updateQuery = "UPDATE urbanpiper_limits
                                       SET status = 1,
                                           event_name = 'Catalog Configuration Response',
                                           retry_attempt = 0,
                                           remarks = '" . addslashes($response) . "',
                                           modified = NOW()
                                       WHERE event_code = 'stop_api'";
                    } else {
                        $updateQuery = "UPDATE urbanpiper_limits
                                       SET event_name = 'Catalog Configuration Response',
                                           retry_attempt = " . $retry_attempt . ",
                                           remarks = '" . addslashes($response) . "',
                                           modified = NOW()
                                       WHERE event_code = 'stop_api'";
                    }
                    $this->Catalog_product->query($updateQuery);
                }

                if ($httpcode == 401) {
                    $error = array(
                        'status' => 'error',
                        'reference' => 'NA',
                        'error' => $httpcode . ' error - Authentication failed'
                    );
                    $response = json_encode($error);
                }

                $responseEscaped = addslashes($response);
                $insertQuery = "INSERT INTO urbanpiper_response
                               (response, event, event_type, created)
                               VALUES ('" . $responseEscaped . "', 'Catalog Configuration Response', '2', NOW())";
                $this->Catalog_product->query($insertQuery);

                $responseData = json_decode($response);
                if (isset($responseData->status) && $responseData->status == 'success') {
                    echo json_encode(array('response_code' => 1, 'response_message' => 'Status: ' . $responseData->status . ', Reference Id: ' . $responseData->reference));
                } else {
                    echo $response;
                }

            } catch (Exception $e) {
                echo json_encode(array('response_code' => 0, 'response_message' => "Error: " . $e->getMessage()));
            }

        } else {
            echo json_encode(array('response_code' => 0, 'response_message' => "Sorry, your API is blocked. Please reset retry counter in urbanpiper_limits table."));
        }
    }

    private function _spoModels() {
        $this->loadModel('Service_purchase_order');
        $this->loadModel('Service_purchase_order_item');
        $this->loadModel('Inventory_vendor');
    }

    private function _spoGenerateId() {
        do {
            $id = 'SPO' . date('Ymd') . rand(100, 999);
            $exists = $this->Service_purchase_order->find('first', array(
                'conditions' => array('Service_purchase_order.spo_id' => $id),
                'fields'     => array('Service_purchase_order.id'),
            ));
        } while ($exists);
        return $id;
    }

    private function _spoStatusLabel($s) {
        $map = array(0=>'Cancelled', 1=>'Submitted', 2=>'Approved', 3=>'Processed');
        return isset($map[$s]) ? $map[$s] : 'Unknown';
    }

    private function _spoServices() {
        $rows = $this->Service_purchase_order->query(
            "SELECT id, name FROM services WHERE status=1 ORDER BY name ASC"
        );
        $out = array();
        foreach ($rows as $r) {
            $k = isset($r['services']['id']) ? $r['services']['id'] : $r[0]['id'];
            $v = isset($r['services']['name']) ? $r['services']['name'] : $r[0]['name'];
            $out[$k] = $v;
        }
        return $out;
    }

    private function _spoVendors() {
        $rows = $this->Inventory_vendor->find('list', array(
            'conditions' => array('Inventory_vendor.status' => 1),
            'fields'     => array('Inventory_vendor.id', 'Inventory_vendor.company'),
            'order'      => array('Inventory_vendor.company ASC'),
        ));
        return $rows;
    }

    private function _spoStores() {
        $this->loadModel('Location');
        $authUser = $this->Session->read('Auth.User');
        $role     = $authUser['role'] ?? '';
        $locIds   = $authUser['location_ids'] ?? 0;
        $conds    = array('Location.status' => 1);
        if ($role !== 'admin' && $locIds) {
            $conds['Location.id'] = array_map('trim', explode(',', $locIds));
        }
        return $this->Location->find('all', array('conditions' => $conds));
    }

    public function service_po_list() {
        if (!$this->Session->check('Auth.User.id')) {
            return $this->redirect(array('controller'=>'dashboard','action'=>'login'));
        }
        $this->layout = 'users';
        $this->_spoModels();

        $authUser = $this->Session->read('Auth.User');
        $role     = $authUser['role'] ?? '';
        $locIds   = $authUser['location_ids'] ?? 0;

        $startDate = date('Y-m-01');
        $endDate   = date('Y-m-d');

        if ($this->request->is(array('post','put'))) {
            $dr = $this->request->data('daterange');
            if ($dr) {
                $parts = explode(' - ', $dr);
                $startDate = date('Y-m-d', strtotime(trim($parts[0])));
                $endDate   = date('Y-m-d', strtotime(trim($parts[1])));
            }
        }

        $storeFilter  = $this->request->data('Search.store')  ?: null;
        $vendorFilter = $this->request->data('Search.vendor') ?: null;
        $statusFilter = $this->request->data('Search.status');

        $where  = "WHERE spo.status != 0";
        $where .= " AND DATE(spo.created) BETWEEN '$startDate' AND '$endDate'";
        if ($storeFilter)  $where .= " AND spo.location_id = " . (int)$storeFilter;
        elseif ($role !== 'admin' && $locIds) $where .= " AND spo.location_id IN ($locIds)";
        if ($vendorFilter) $where .= " AND spo.vendor_id = " . (int)$vendorFilter;
        if ($statusFilter !== null && $statusFilter !== '') $where .= " AND spo.status = " . (int)$statusFilter;

        $rows = $this->Service_purchase_order->query("
            SELECT spo.id, spo.spo_id, spo.vendor_name, spo.location_name,
                   spo.service_type_name, spo.total_amount, spo.approved_amount,
                   spo.processed_amount, spo.status, spo.created, spo.created_by_name,
                   spo.period_from, spo.period_to
            FROM service_purchase_orders spo
            $where
            ORDER BY spo.id DESC
        ");

        $spos = array();
        foreach ($rows as $r) {
            $row = isset($r['spo']) ? $r['spo'] : $r['service_purchase_orders'];
            $spos[] = array(
                'id'               => $row['id'],
                'spo_id'           => $row['spo_id'],
                'vendor'           => $row['vendor_name'],
                'store'            => $row['location_name'],
                'service_type'     => $row['service_type_name'],
                'total_amount'     => $row['total_amount'],
                'approved_amount'  => $row['approved_amount'],
                'processed_amount' => $row['processed_amount'],
                'status'           => (int)$row['status'],
                'status_label'     => $this->_spoStatusLabel((int)$row['status']),
                'created'          => $row['created'],
                'created_by'       => $row['created_by_name'],
                'period_from'      => $row['period_from'],
                'period_to'        => $row['period_to'],
            );
        }

        $stores  = $this->_spoStores();
        $vendors = $this->_spoVendors();
        $this->set(compact('spos','stores','vendors','startDate','endDate'));
    }

    public function create_service_po() {
        if (!$this->Session->check('Auth.User.id')) {
            return $this->redirect(array('controller'=>'dashboard','action'=>'login'));
        }
        $this->layout = 'users';
        $this->_spoModels();
        $services = $this->_spoServices();
        $vendors  = $this->_spoVendors();
        $stores   = $this->_spoStores();
        $this->set(compact('services','vendors','stores'));
    }

    public function add_service_po() {
        $this->autoRender = false;
        $this->_spoModels();

        if (!$this->request->is('post')) {
            echo json_encode(array('status'=>false,'message'=>'POST required'));
            return;
        }

        $authUser    = $this->Session->read('Auth.User');
        $data        = $this->request->data;
        $items       = isset($data['items']) ? $data['items'] : array();

        if (empty($items)) {
            echo json_encode(array('status'=>false,'message'=>'At least one line item required'));
            return;
        }

        $vendorId   = (int)($data['vendor_id'] ?? 0);
        $vendorRow  = $this->Inventory_vendor->find('first', array(
            'conditions' => array('Inventory_vendor.id' => $vendorId),
            'fields'     => array('Inventory_vendor.id','Inventory_vendor.company'),
        ));
        $vendorName = $vendorRow ? $vendorRow['Inventory_vendor']['company'] : '';

        $serviceTypeId   = (int)($data['service_type_id'] ?? 0);
        $serviceRow      = $this->Service_purchase_order->query(
            "SELECT name FROM services WHERE id=" . (int)$serviceTypeId . " LIMIT 1"
        );
        $serviceTypeName = !empty($serviceRow) ? ($serviceRow[0]['services']['name'] ?? $serviceRow[0][0]['name'] ?? '') : '';

        $storeId   = (int)($data['location_id'] ?? 0);
        $this->loadModel('Location');
        $locRow    = $this->Location->find('first', array('conditions'=>array('Location.id'=>$storeId),'fields'=>array('Location.id','Location.name')));
        $storeName = $locRow ? $locRow['Location']['name'] : '';

        $spoId = $this->_spoGenerateId();

        $subtotal  = 0; $totalTax = 0; $totalDiscount = 0;
        foreach ($items as $item) {
            $qty      = (float)($item['quantity'] ?? 1);
            $price    = (float)($item['unit_price'] ?? 0);
            $disc     = (float)($item['discount_value'] ?? 0);
            $taxPct   = (float)($item['tax_percentage'] ?? 0);
            $base     = ($qty * $price) - $disc;
            $taxVal   = round($base * $taxPct / 100, 2);
            $subtotal      += $qty * $price;
            $totalDiscount += $disc;
            $totalTax      += $taxVal;
        }
        $totalAmount = round($subtotal - $totalDiscount + $totalTax, 2);

        $header = array('Service_purchase_order' => array(
            'spo_id'            => $spoId,
            'vendor_id'         => $vendorId,
            'vendor_name'       => $vendorName,
            'location_id'       => $storeId,
            'location_name'     => $storeName,
            'service_type_id'   => $serviceTypeId,
            'service_type_name' => $serviceTypeName,
            'description'       => trim($data['description'] ?? ''),
            'period_from'       => $data['period_from'] ?: null,
            'period_to'         => $data['period_to']   ?: null,
            'subtotal'          => round($subtotal, 2),
            'discount'          => round($totalDiscount, 2),
            'total_tax'         => round($totalTax, 2),
            'total_amount'      => $totalAmount,
            'remarks'           => trim($data['remarks'] ?? ''),
            'status'            => 1,
            'created_by'        => $authUser['id'] ?? 0,
            'created_by_name'   => $authUser['name'] ?? '',
        ));

        $this->Service_purchase_order->create();
        if (!$this->Service_purchase_order->save($header)) {
            echo json_encode(array('status'=>false,'message'=>'Failed to save SPO header'));
            return;
        }

        foreach ($items as $item) {
            $qty     = (float)($item['quantity'] ?? 1);
            $price   = (float)($item['unit_price'] ?? 0);
            $disc    = (float)($item['discount_value'] ?? 0);
            $taxPct  = (float)($item['tax_percentage'] ?? 0);
            $base    = ($qty * $price) - $disc;
            $taxVal  = round($base * $taxPct / 100, 2);
            $total   = round($base + $taxVal, 2);

            $this->Service_purchase_order_item->create();
            $this->Service_purchase_order_item->save(array('Service_purchase_order_item' => array(
                'spo_id'         => $spoId,
                'service_name'   => trim($item['service_name'] ?? ''),
                'description'    => trim($item['description'] ?? ''),
                'unit'           => trim($item['unit'] ?? 'Lump Sum'),
                'quantity'       => $qty,
                'unit_price'     => $price,
                'discount_value' => $disc,
                'tax_percentage' => $taxPct,
                'tax_value'      => $taxVal,
                'total_amount'   => $total,
                'status'         => 1,
            )));
        }

        echo json_encode(array('status'=>true,'spo_id'=>$spoId,'message'=>'Service PO created successfully'));
    }

    public function view_service_po() {
        if (!$this->Session->check('Auth.User.id')) {
            return $this->redirect(array('controller'=>'dashboard','action'=>'login'));
        }
        $this->layout = 'users';
        $this->_spoModels();

        $spoId = isset($_GET['id']) ? trim($_GET['id']) : '';
        if (!$spoId) { $this->redirect(array('action'=>'service_po_list')); return; }

        $spo   = $this->Service_purchase_order->find('first', array(
            'conditions' => array('Service_purchase_order.spo_id' => $spoId),
        ));
        $items = $this->Service_purchase_order_item->find('all', array(
            'conditions' => array('Service_purchase_order_item.spo_id' => $spoId,
                                  'Service_purchase_order_item.status' => 1),
        ));

        if (empty($spo)) { $this->redirect(array('action'=>'service_po_list')); return; }
        $this->set(compact('spo','items'));
    }

    public function edit_service_po() {
        if (!$this->Session->check('Auth.User.id')) {
            return $this->redirect(array('controller'=>'dashboard','action'=>'login'));
        }
        $this->layout = 'users';
        $this->_spoModels();

        $spoId = isset($_GET['id']) ? trim($_GET['id']) : '';
        $spo   = $this->Service_purchase_order->find('first', array(
            'conditions' => array('Service_purchase_order.spo_id' => $spoId,
                                  'Service_purchase_order.status'  => 1),
        ));
        if (empty($spo)) {
            $this->Session->setFlash('SPO not found or cannot be edited (already approved/processed).');
            $this->redirect(array('action'=>'service_po_list')); return;
        }
        $items    = $this->Service_purchase_order_item->find('all', array(
            'conditions' => array('Service_purchase_order_item.spo_id' => $spoId,
                                  'Service_purchase_order_item.status'  => 1),
        ));
        $services = $this->_spoServices();
        $vendors  = $this->_spoVendors();
        $stores   = $this->_spoStores();
        $this->set(compact('spo','items','services','vendors','stores'));
    }

    public function update_service_po() {
        $this->autoRender = false;
        $this->_spoModels();

        if (!$this->request->is('post')) {
            echo json_encode(array('status'=>false,'message'=>'POST required'));
            return;
        }

        $data  = $this->request->data;
        $spoId = trim($data['spo_id'] ?? '');
        $spo   = $this->Service_purchase_order->find('first', array(
            'conditions' => array('Service_purchase_order.spo_id' => $spoId,
                                  'Service_purchase_order.status'  => 1),
        ));
        if (empty($spo)) {
            echo json_encode(array('status'=>false,'message'=>'SPO not found or not editable'));
            return;
        }

        $items = isset($data['items']) ? $data['items'] : array();
        if (empty($items)) {
            echo json_encode(array('status'=>false,'message'=>'At least one line item required'));
            return;
        }

        $vendorId   = (int)($data['vendor_id'] ?? 0);
        $vendorRow  = $this->Inventory_vendor->find('first', array(
            'conditions' => array('Inventory_vendor.id' => $vendorId),
            'fields'     => array('Inventory_vendor.id','Inventory_vendor.company'),
        ));
        $vendorName = $vendorRow ? $vendorRow['Inventory_vendor']['company'] : '';

        $serviceTypeId = (int)($data['service_type_id'] ?? 0);
        $serviceRow    = $this->Service_purchase_order->query(
            "SELECT name FROM services WHERE id=" . (int)$serviceTypeId . " LIMIT 1"
        );
        $serviceTypeName = !empty($serviceRow) ? ($serviceRow[0]['services']['name'] ?? $serviceRow[0][0]['name'] ?? '') : '';

        $storeId = (int)($data['location_id'] ?? 0);
        $this->loadModel('Location');
        $locRow  = $this->Location->find('first', array('conditions'=>array('Location.id'=>$storeId)));
        $storeName = $locRow ? $locRow['Location']['name'] : '';

        $subtotal = 0; $totalTax = 0; $totalDiscount = 0;
        foreach ($items as $item) {
            $qty  = (float)($item['quantity'] ?? 1);
            $price= (float)($item['unit_price'] ?? 0);
            $disc = (float)($item['discount_value'] ?? 0);
            $taxPct=(float)($item['tax_percentage'] ?? 0);
            $base = ($qty * $price) - $disc;
            $subtotal      += $qty * $price;
            $totalDiscount += $disc;
            $totalTax      += round($base * $taxPct / 100, 2);
        }
        $totalAmount = round($subtotal - $totalDiscount + $totalTax, 2);

        $this->Service_purchase_order->id = $spo['Service_purchase_order']['id'];
        $this->Service_purchase_order->save(array('Service_purchase_order' => array(
            'vendor_id'         => $vendorId,
            'vendor_name'       => $vendorName,
            'location_id'       => $storeId,
            'location_name'     => $storeName,
            'service_type_id'   => $serviceTypeId,
            'service_type_name' => $serviceTypeName,
            'description'       => trim($data['description'] ?? ''),
            'period_from'       => $data['period_from'] ?: null,
            'period_to'         => $data['period_to']   ?: null,
            'subtotal'          => round($subtotal, 2),
            'discount'          => round($totalDiscount, 2),
            'total_tax'         => round($totalTax, 2),
            'total_amount'      => $totalAmount,
            'remarks'           => trim($data['remarks'] ?? ''),
        )));

        $this->Service_purchase_order_item->updateAll(
            array('Service_purchase_order_item.status' => 0),
            array('Service_purchase_order_item.spo_id' => $spoId)
        );

        foreach ($items as $item) {
            $qty    = (float)($item['quantity'] ?? 1);
            $price  = (float)($item['unit_price'] ?? 0);
            $disc   = (float)($item['discount_value'] ?? 0);
            $taxPct = (float)($item['tax_percentage'] ?? 0);
            $base   = ($qty * $price) - $disc;
            $taxVal = round($base * $taxPct / 100, 2);
            $this->Service_purchase_order_item->create();
            $this->Service_purchase_order_item->save(array('Service_purchase_order_item' => array(
                'spo_id'         => $spoId,
                'service_name'   => trim($item['service_name'] ?? ''),
                'description'    => trim($item['description'] ?? ''),
                'unit'           => trim($item['unit'] ?? 'Lump Sum'),
                'quantity'       => $qty,
                'unit_price'     => $price,
                'discount_value' => $disc,
                'tax_percentage' => $taxPct,
                'tax_value'      => $taxVal,
                'total_amount'   => round($base + $taxVal, 2),
                'status'         => 1,
            )));
        }

        echo json_encode(array('status'=>true,'message'=>'SPO updated successfully'));
    }

    public function approve_service_po() {
        $this->autoRender = false;
        $this->_spoModels();

        if (!$this->request->is('post')) {
            echo json_encode(array('status'=>false,'message'=>'POST required'));
            return;
        }

        $authUser = $this->Session->read('Auth.User');
        $data     = $this->request->data;
        $spoId    = trim($data['spo_id'] ?? '');

        $spo = $this->Service_purchase_order->find('first', array(
            'conditions' => array('Service_purchase_order.spo_id' => $spoId,
                                  'Service_purchase_order.status'  => 1),
        ));
        if (empty($spo)) {
            echo json_encode(array('status'=>false,'message'=>'SPO not found or not in Submitted state'));
            return;
        }

        $approvedItems = isset($data['items']) ? $data['items'] : array();
        $totalApproved = 0;

        foreach ($approvedItems as $itemId => $approvedAmt) {
            $amt = (float)$approvedAmt;
            $this->Service_purchase_order_item->id = (int)$itemId;
            $this->Service_purchase_order_item->save(array(
                'Service_purchase_order_item' => array('approved_amount' => $amt)
            ));
            $totalApproved += $amt;
        }

        $this->Service_purchase_order->id = $spo['Service_purchase_order']['id'];
        $this->Service_purchase_order->save(array('Service_purchase_order' => array(
            'status'           => 2,
            'approved_by_id'   => $authUser['id'] ?? 0,
            'approved_by_name' => $authUser['name'] ?? '',
            'approved_on'      => date('Y-m-d H:i:s'),
            'approved_amount'  => round($totalApproved, 2),
        )));

        echo json_encode(array('status'=>true,'message'=>'SPO approved successfully'));
    }

    public function process_service_po() {
        $this->autoRender = false;
        $this->_spoModels();

        if (!$this->request->is('post')) {
            echo json_encode(array('status'=>false,'message'=>'POST required'));
            return;
        }

        $authUser  = $this->Session->read('Auth.User');
        $data      = $this->request->data;
        $spoId     = trim($data['spo_id'] ?? '');
        $invoiceNo = trim($data['invoice_no'] ?? '');

        $spo = $this->Service_purchase_order->find('first', array(
            'conditions' => array('Service_purchase_order.spo_id' => $spoId,
                                  'Service_purchase_order.status'  => 2),
        ));
        if (empty($spo)) {
            echo json_encode(array('status'=>false,'message'=>'SPO not found or not in Approved state'));
            return;
        }

        $processedItems = isset($data['items']) ? $data['items'] : array();
        $totalProcessed = 0;

        foreach ($processedItems as $itemId => $processedAmt) {
            $amt = (float)$processedAmt;
            $this->Service_purchase_order_item->id = (int)$itemId;
            $this->Service_purchase_order_item->save(array(
                'Service_purchase_order_item' => array('processed_amount' => $amt)
            ));
            $totalProcessed += $amt;
        }

        $this->Service_purchase_order->id = $spo['Service_purchase_order']['id'];
        $this->Service_purchase_order->save(array('Service_purchase_order' => array(
            'status'            => 3,
            'invoice_no'        => $invoiceNo,
            'invoice_date'      => $data['invoice_date'] ?: null,
            'processed_by_id'   => $authUser['id'] ?? 0,
            'processed_by_name' => $authUser['name'] ?? '',
            'processed_on'      => date('Y-m-d H:i:s'),
            'processed_amount'  => round($totalProcessed, 2),
        )));

        echo json_encode(array('status'=>true,'message'=>'SPO processed successfully'));
    }

    public function vendor_wise_purchase_items()
    {
        if (!$this->Session->check('Auth.User.id')) {
            return $this->response->body(json_encode(['error' => 'Unauthorized']));
        }
        $this->autoRender = false;
        $this->response->type('application/json');

        $vendor_id = (int)($this->request->query('vendor_id') ?? 0);
        $startDT   = trim($this->request->query('start') ?? '');
        $endDT     = trim($this->request->query('end')   ?? '');
        $store_id  = (int)($this->request->query('store_id') ?? 0);

        if (!$vendor_id || !$startDT || !$endDT) {
            return $this->response->body(json_encode(['error' => 'Invalid parameters']));
        }

        $startdate = date('Y-m-d', strtotime($startDT));
        $enddate   = date('Y-m-d', strtotime($endDT));

        $authUser       = $this->Session->read('Auth.User');
        $userRole       = $authUser['role'] ?? '';
        $userLocationId = $authUser['location_ids'] ?? 0;

        $where = "WHERE ipo.status != 0 AND ipoi.status != 0
                    AND ipo.vendor_id = $vendor_id
                    AND ipo.po_date BETWEEN '$startdate' AND '$enddate'";
        if ($store_id) $where .= " AND ipo.location_id = $store_id";
        elseif ($userRole !== 'admin') $where .= " AND ipo.location_id IN ($userLocationId)";

        $rows = $this->Inventory_purchase_order->query("
            SELECT
                ipoi.sku_id,
                ipoi.sku_name,
                SUM(ipoi.sku_quantity)                                                              AS qty,
                SUM(ipoi.sku_price * ipoi.sku_quantity - COALESCE(ipoi.sku_discount_value, 0))     AS base,
                SUM(COALESCE(ipoi.sku_total_tax_value, 0))                                          AS tax,
                SUM(COALESCE(ipoi.excise_duty_amt,0))                                               AS excise_total,
                SUM(COALESCE(ipoi.addl_excise_duty_amt,0))                                          AS addl_excise_total,
                SUM(COALESCE(ipoi.vat_amt,0))                                                       AS vat_total,
                SUM(COALESCE(ipoi.tcs_amt,0))                                                       AS tcs_total,
                SUM(ipoi.sku_amount)                                                                AS total,
                SUM(CASE WHEN isku.expense_type = 'CAPEX' THEN ipoi.sku_amount ELSE 0 END)         AS capex_total,
                SUM(CASE WHEN isku.expense_type = 'OPEX'  THEN ipoi.sku_amount ELSE 0 END)         AS opex_total,
                SUM(CASE WHEN isku.expense_type NOT IN ('CAPEX','OPEX') THEN ipoi.sku_amount ELSE 0 END) AS unclassified_total
            FROM inventory_purchase_order ipo
            LEFT JOIN inventory_purchase_order_items ipoi ON ipo.p_o_id = ipoi.p_o_id
            LEFT JOIN inventory_skus isku ON isku.id = ipoi.sku_id
            $where
            GROUP BY ipoi.sku_id, ipoi.sku_name
            ORDER BY total DESC
        ");

        $result = [];
        foreach ($rows as $row) {

            $item_name = $row['ipoi']['sku_name'] ?? $row[0]['sku_name'] ?? '';

            if ($item_name === '') {
                $item_name = $row['Inventory_purchase_order_items']['sku_name'] ?? '';
            }

            $result[] = [
                'item_name'    => $item_name,
                'qty'          => $row[0]['qty']               ?? 0,
                'base'         => $row[0]['base']              ?? 0,
                'tax'          => $row[0]['tax']               ?? 0,
                'excise_total' => $row[0]['excise_total']      ?? 0,
                'addl_excise_total' => $row[0]['addl_excise_total'] ?? 0,
                'vat_total'    => $row[0]['vat_total']         ?? 0,
                'tcs_total'    => $row[0]['tcs_total']         ?? 0,
                'total'        => $row[0]['total']             ?? 0,
                'capex_total'  => $row[0]['capex_total']       ?? 0,
                'opex_total'   => $row[0]['opex_total']        ?? 0,
                'unclassified' => $row[0]['unclassified_total'] ?? 0,
            ];
        }

        if (empty($result)) {
            $raw = !empty($rows) ? $rows[0] : [];
            return $this->response->body(json_encode([
                'items' => [],
                'debug_raw_first_row' => $raw,
                'debug_row_count' => count($rows)
            ]));
        }

        return $this->response->body(json_encode(['items' => $result]));
    }

    public function item_wise_purchase_vendors()
    {
        if (!$this->Session->check('Auth.User.id')) {
            return $this->response->body(json_encode(['error' => 'Unauthorized']));
        }
        $this->autoRender = false;
        $this->response->type('application/json');

        $sku_id   = (int)($this->request->query('sku_id')   ?? 0);
        $startDT  = trim($this->request->query('start')     ?? '');
        $endDT    = trim($this->request->query('end')       ?? '');
        $store_id = (int)($this->request->query('store_id') ?? 0);

        if (!$sku_id || !$startDT || !$endDT) {
            return $this->response->body(json_encode(['error' => 'Invalid parameters']));
        }

        $startdate = date('Y-m-d', strtotime($startDT));
        $enddate   = date('Y-m-d', strtotime($endDT));

        $authUser       = $this->Session->read('Auth.User');
        $userRole       = $authUser['role'] ?? '';
        $userLocationId = $authUser['location_ids'] ?? 0;

        $where = "WHERE ipo.status != 0 AND ipoi.status != 0
                    AND ipoi.sku_id = $sku_id
                    AND ipo.po_date BETWEEN '$startdate' AND '$enddate'";
        if ($store_id) $where .= " AND ipo.location_id = $store_id";
        elseif ($userRole !== 'admin') $where .= " AND ipo.location_id IN ($userLocationId)";

        $rows = $this->Inventory_purchase_order->query("
            SELECT
                ipo.vendor_id,
                ipo.vendor_name AS vendor,
                COUNT(DISTINCT ipo.p_o_id) AS po_count,
                SUM(ipoi.sku_quantity) AS qty,
                ROUND(SUM(ipoi.sku_price * ipoi.sku_quantity) / NULLIF(SUM(ipoi.sku_quantity),0), 4) AS weighted_avg_rate,
                SUM(ipoi.sku_price * ipoi.sku_quantity - COALESCE(ipoi.sku_discount_value,0)) AS base,
                SUM(COALESCE(ipoi.sku_total_tax_value,0)) AS tax,
                SUM(COALESCE(ipoi.excise_duty_amt,0)) AS excise_total,
                SUM(COALESCE(ipoi.addl_excise_duty_amt,0)) AS addl_excise_total,
                SUM(COALESCE(ipoi.vat_amt,0)) AS vat_total,
                SUM(COALESCE(ipoi.tcs_amt,0)) AS tcs_total,
                SUM(ipoi.sku_amount) AS total
            FROM inventory_purchase_order ipo
            LEFT JOIN inventory_purchase_order_items ipoi ON ipo.p_o_id = ipoi.p_o_id
            $where
            GROUP BY ipo.vendor_id, ipo.vendor_name
            ORDER BY total DESC
        ");

        $result = [];
        foreach ($rows as $row) {
            $vendor = $row['ipo']['vendor'] ?? $row[0]['vendor'] ?? '';
            if ($vendor === '') {
                $vendor = $row['Inventory_purchase_order']['vendor'] ?? '';
            }
            $result[] = [
                'vendor'       => $vendor,
                'po_count'     => $row[0]['po_count']       ?? 0,
                'qty'          => $row[0]['qty']             ?? 0,
                'weighted_avg' => $row[0]['weighted_avg_rate'] ?? 0,
                'base'         => $row[0]['base']            ?? 0,
                'tax'          => $row[0]['tax']             ?? 0,
                'excise_total' => $row[0]['excise_total']      ?? 0,
                'addl_excise_total' => $row[0]['addl_excise_total'] ?? 0,
                'vat_total'    => $row[0]['vat_total']         ?? 0,
                'tcs_total'    => $row[0]['tcs_total']         ?? 0,
                'total'        => $row[0]['total']           ?? 0,
            ];
        }

        if (empty($result)) {
            $raw = !empty($rows) ? $rows[0] : [];
            return $this->response->body(json_encode([
                'items' => [],
                'debug_raw_first_row' => $raw,
                'debug_row_count'     => count($rows)
            ]));
        }

        return $this->response->body(json_encode(['items' => $result]));
    }

    public function export_semifinished_recipe_costing()
    {
        if (!$this->Session->check('Auth.User.id')) {
            return $this->redirect(['controller' => 'dashboard', 'action' => 'login']);
        }

        $exportAll  = !empty($_POST['export_all']) && $_POST['export_all'] == '1';
        $idsParam   = isset($_POST['semifinished_ids']) ? trim($_POST['semifinished_ids']) : '';
        $ids = [];

        if ($exportAll || $idsParam === '') {
            $rows = $this->Inventory_semifinished->query(
                "SELECT id FROM inventory_semifinished WHERE status = 1 ORDER BY id ASC"
            );
            foreach ($rows as $r) {
                $ids[] = $r['inventory_semifinished']['id'];
            }
        } else {
            foreach (explode(',', $idsParam) as $v) {
                $v = intval(trim($v));
                if ($v > 0) $ids[] = $v;
            }
        }

        if (empty($ids)) {
            $this->Flash->error('No records selected.');
            return $this->redirect(['action' => 'semifinished_list']);
        }

        $items = [];

        foreach ($ids as $id) {

            $row = $this->Inventory_semifinished->query(
                "SELECT id, name, price FROM inventory_semifinished WHERE id = $id LIMIT 1"
            );
            if (empty($row)) continue;

            $name  = $row[0]['inventory_semifinished']['name'];
            $price = floatval($row[0]['inventory_semifinished']['price']);

            $skuRows = $this->Inventory_linking->query(
                "SELECT il.component_name,
                        il.component_unit_name AS uom,
                        il.conversion_quantity AS req_qty,
                        COALESCE(sk.consumption_unit_price, 0) AS unit_cost,
                        COALESCE(NULLIF(sk.consumption_factor,0),1) AS per_unit,
                        ROUND(il.conversion_quantity * COALESCE(sk.consumption_unit_price, 0), 4) AS line_cost,
                        'Sku' AS comp_type
                 FROM inventory_linking il
                 LEFT JOIN inventory_skus sk ON sk.id = il.component_id
                 WHERE il.parent_id = $id
                 AND il.parent_type = 'Semifinished'
                 AND il.component_type = 'Sku'
                 AND il.status = 1"
            );

            $semiRows = $this->Inventory_linking->query(
                "SELECT il.component_name,
                        il.component_unit_name AS uom,
                        il.conversion_quantity AS req_qty,
                        COALESCE(vsf.unit_cost,0) AS unit_cost,
                        1 AS per_unit,
                        ROUND(il.conversion_quantity * COALESCE(vsf.unit_cost,0), 4) AS line_cost,
                        'Semifinished' AS comp_type
                 FROM inventory_linking il
                 LEFT JOIN v_semifinished_recipe_cost vsf ON vsf.id = il.component_id
                 WHERE il.parent_id = $id
                 AND il.parent_type = 'Semifinished'
                 AND il.component_type = 'Semifinished'
                 AND il.status = 1"
            );

            $ingredients = [];

            foreach ($semiRows as $r) {
                $merged = array_merge($r['il'] ?? [], $r[0] ?? []);
                $ingredients[] = array_merge($merged, ['comp_type' => 'Semifinished']);
            }

            foreach ($skuRows as $r) {
                $merged = array_merge($r['il'] ?? [], $r[0] ?? []);
                $ingredients[] = array_merge($merged, ['comp_type' => 'Sku']);
            }

            $totalCost = array_sum(array_column($ingredients, 'line_cost'));

            $items[] = [
                'id' => $id,
                'name' => $name,
                'price' => $price,
                'ingredients' => $ingredients,
                'total_cost' => $totalCost,
            ];
        }

        App::import('Vendor', 'PHPExcel/PHPExcel');
        $xls = new PHPExcel();
        $sheet = $xls->getActiveSheet();
        $sheet->setTitle('SemiFinished Costing');

        $semiFill = [
            'type' => PHPExcel_Style_Fill::FILL_SOLID,
            'startcolor' => ['rgb' => 'FFF2CC']
        ];

        $headers = ['SR','NAME','INGREDIENT','UOM','REQ QTY','UNIT COST','PER UNIT','COST PRICE','SELLING PRICE','MARGIN'];

        $col = 'A';
        foreach ($headers as $h) {
            $sheet->setCellValue($col.'1', $h);
            $col++;
        }

        $row = 2;
        $sr = 1;

        foreach ($items as $item) {

            $sheet->setCellValue("A$row", $item['id']);
            $sheet->setCellValue("B$row", $item['name']);
            $sheet->setCellValue("H$row", $item['total_cost']);
            $sheet->setCellValue("I$row", $item['price']);
            $sheet->setCellValue("J$row", $item['price'] - $item['total_cost']);
            $row++;

            foreach ($item['ingredients'] as $ing) {

                $isSemi = ($ing['comp_type'] === 'Semifinished');

                $sheet->setCellValue("C$row", $ing['component_name']);
                $sheet->setCellValue("D$row", $ing['uom']);
                $sheet->setCellValue("E$row", $ing['req_qty']);
                $sheet->setCellValue("F$row", round($ing['unit_cost'], 2));
                $sheet->setCellValue("G$row", $ing['per_unit']);
                $sheet->setCellValue("H$row", $ing['line_cost']);

                if ($isSemi) {
                    $sheet->getStyle("A$row:J$row")->applyFromArray([
                        'fill' => $semiFill,
                        'font' => ['bold' => true]
                    ]);
                }
                $row++;
            }

            $sheet->setCellValue("G$row", 'TOTAL');
            $sheet->setCellValue("H$row", $item['total_cost']);
            $row += 2;
        }

        $filename = 'SemiFinished_Costing_' . date('Ymd_His') . '.xls';

        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="'.$filename.'"');
        header('Cache-Control: max-age=0');

        PHPExcel_IOFactory::createWriter($xls, 'Excel5')->save('php://output');
        exit;
    }

    public function internal_indent_pdf()
    {
        if (!$this->Session->check('Auth.User.id')) {
            return $this->redirect(['controller' => 'dashboard', 'action' => 'login']);
        }

        $this->layout = false;

        $id = (int)$_GET['id'];

        $indents = $this->Inventory_internal_indent->find('all', [
            'conditions' => ['indent_id' => $id, 'status' => 1]
        ]);

        $items = $this->Inventory_internal_indent_items->find('all', [
            'conditions' => ['indent_id' => $id, 'status' => 1]
        ]);

        $location_id = $indents[0]['Inventory_internal_indent']['location_id'];

        $locationdetail = $this->Location->find('first', [
            'conditions' => ['Location.id' => $location_id]
        ]);

        $this->set(compact('indents', 'items','locationdetail'));
    }

    public function outlet_tax_list()
    {
        if (!$this->Session->check('Auth.User.id')) {
            return $this->redirect(['controller' => 'dashboard', 'action' => 'login']);
        }

        $this->layout = 'users';

        $this->loadModel('Catalog_product_tax_outlet');
        $this->loadModel('Catalog_tax');
        $products = $this->Catalog_product->find('all', [
            'conditions' => ['status' => 1],
            'fields'     => ['id', 'name', 'cat_name'],
            'order'      => ['cat_name', 'name'],
            'recursive'  => -1
        ]);
        $outlets = $this->Location->find('all', [
            'conditions' => ['status' => 1, 'type' => 'Outlet'],
            'fields'     => ['id', 'name', 'state'],
            'order'      => ['name'],
            'recursive'  => -1
        ]);
        $taxes = $this->Catalog_tax->find('all', [
            'conditions' => ['status' => 1],
            'fields'     => ['id', 'name', 'percentage'],
            'recursive'  => -1
        ]);
        $overrides = $this->Catalog_product_tax_outlet->find('all', [
            'conditions' => ['status' => 1],
            'order'      => ['product_name', 'outlet_name'],
            'recursive'  => -1
        ]);

        $this->set(compact('products', 'outlets', 'taxes', 'overrides'));
    }

    public function delete_outlet_tax()
    {
        $this->autoRender = false;

        if (!$this->Session->check('Auth.User.id')) {
            echo json_encode(['status' => false, 'message' => 'Unauthorized']);
            return;
        }

        if (!$this->request->is('post')) {
            echo json_encode(['status' => false, 'message' => 'POST required']);
            return;
        }

        $this->loadModel('Catalog_product_tax_outlet');

        $ids = $this->request->data('ids');

        if (empty($ids)) {
            echo json_encode(['status' => false, 'message' => 'No IDs provided']);
            return;
        }

        $ids = array_filter(array_map('intval', (array)$ids));

        if (empty($ids)) {
            echo json_encode(['status' => false, 'message' => 'Invalid IDs']);
            return;
        }

        $this->Catalog_product_tax_outlet->updateAll(
            [
                'Catalog_product_tax_outlet.status'   => 0,
                'Catalog_product_tax_outlet.modified' => "'" . date('Y-m-d H:i:s') . "'"
            ],
            ['Catalog_product_tax_outlet.id' => $ids]
        );

        echo json_encode([
            'status'  => true,
            'message' => count($ids) . ' override(s) deleted successfully'
        ]);
    }

    public function add_outlet_tax()
    {
        if (!$this->Session->check('Auth.User.id')) {
            return $this->redirect(['controller' => 'dashboard', 'action' => 'login']);
        }

        $this->layout = 'users';

        $this->loadModel('Catalog_tax');

        $outlets = $this->Location->find('all', [
            'conditions' => ['status' => 1, 'type' => 'Outlet'],
            'fields'     => ['id', 'name', 'state'],
            'order'      => ['name'],
            'recursive'  => -1,
        ]);

        $taxes = $this->Catalog_tax->find('all', [
            'conditions' => ['status' => 1],
            'fields'     => ['id', 'name', 'percentage'],
            'recursive'  => -1,
        ]);

        $this->set(compact('outlets', 'taxes'));
    }

    public function get_categories_by_outlet()
    {
        $this->autoRender = false;

        if (!$this->Session->check('Auth.User.id')) {
            echo json_encode(['status' => false, 'message' => 'Unauthorized']);
            return;
        }

        $this->loadModel('Catalog_category_branches');

        if (!$this->request->is('post')) {
            echo json_encode(['status' => false, 'message' => 'POST required']);
            return;
        }

        $locationId = intval($this->request->data('location_id'));

        if (!$locationId) {
            echo json_encode(['status' => false, 'message' => 'Invalid location_id']);
            return;
        }

        $rows = $this->Catalog_category_branches->find('all', [
            'conditions' => [
                'location_id' => $locationId,
                'status'      => 1,
            ],
            'fields'    => ['category_id', 'category_name'],
            'order'     => ['category_name'],
            'recursive' => -1,
        ]);

        $seen = [];
        $data = [];
        foreach ($rows as $row) {
            $cid = $row['Catalog_category_branches']['category_id'];
            if (!isset($seen[$cid])) {
                $seen[$cid] = true;
                $data[] = [
                    'category_id'   => $cid,
                    'category_name' => $row['Catalog_category_branches']['category_name'],
                ];
            }
        }

        echo json_encode(['status' => true, 'data' => $data]);
    }

    public function get_products_by_categories()
    {
        $this->autoRender = false;

        if (!$this->Session->check('Auth.User.id')) {
            echo json_encode(['status' => false, 'message' => 'Unauthorized']);
            return;
        }

        $this->loadModel('Catalog_product_branches');
        $this->loadModel('Catalog_product_tax');

        if (!$this->request->is('post')) {
            echo json_encode(['status' => false, 'message' => 'POST required']);
            return;
        }

        $locationId = intval($this->request->data('location_id'));
        $catIds     = $this->request->data('cat_ids');

        if (!$locationId) {
            echo json_encode(['status' => false, 'message' => 'Invalid location_id']);
            return;
        }

        if (empty($catIds) || !is_array($catIds)) {
            echo json_encode(['status' => false, 'message' => 'No categories provided']);
            return;
        }

        $catIds = array_filter(array_map('intval', $catIds));

        $rows = $this->Catalog_product_branches->find('all', [
            'conditions' => [
                'location_id' => $locationId,
                'cat_id'      => $catIds,
                'status'      => 1,
            ],
            'fields'    => ['product_id', 'product_name', 'price', 'cat_name'],
            'order'     => ['product_name'],
            'recursive' => -1,
        ]);

        if (empty($rows)) {
            echo json_encode(['status' => true, 'data' => []]);
            return;
        }

        $productIds = array_unique(array_map(function ($row) {
            return $row['Catalog_product_branches']['product_id'];
        }, $rows));

        $outletTaxRows = $this->Catalog_product_tax->query("
            SELECT
                product_id,
                GROUP_CONCAT(
                    CONCAT(tax_name, ' (', tax_percentage, '%)')
                    ORDER BY tax_name SEPARATOR ', '
                ) AS taxes
            FROM catalog_product_tax_outlet
            WHERE status = 1
              AND outlet_id = " . $locationId . "
              AND product_id IN (" . implode(',', $productIds) . ")
            GROUP BY product_id
        ");

        $taxRows = $this->Catalog_product_tax->query("
            SELECT
                product_id,
                GROUP_CONCAT(
                    CONCAT(tax_name, ' (', tax_percentage, '%)')
                    ORDER BY tax_name SEPARATOR ', '
                ) AS taxes
            FROM catalog_product_tax
            WHERE status = 1
              AND product_id IN (" . implode(',', $productIds) . ")
            GROUP BY product_id
        ");

        $outletTaxMap = [];
        foreach ($outletTaxRows as $t) {
            $pid = $t['catalog_product_tax_outlet']['product_id']
                ?? $t[0]['product_id'];
            $outletTaxMap[$pid] = $t[0]['taxes'];
        }

        $taxMap = [];
        foreach ($taxRows as $t) {
            $pid = $t['catalog_product_tax']['product_id'];
            $taxMap[$pid] = $t[0]['taxes'];
        }

        $data = [];
        foreach ($rows as $row) {
            $r   = $row['Catalog_product_branches'];
            $pid = $r['product_id'];

            if (isset($outletTaxMap[$pid])) {
                $taxes = $outletTaxMap[$pid];
            } elseif (isset($taxMap[$pid])) {
                $taxes = $taxMap[$pid];
            } else {
                $taxes = null;
            }

            $data[] = [
                'product_id'   => $pid,
                'product_name' => $r['product_name'],
                'price'        => $r['price'],
                'cat_name'     => $r['cat_name'],
                'taxes'        => $taxes,
            ];
        }

        echo json_encode(['status' => true, 'data' => $data]);
    }

    public function save_outlet_tax()
    {
        $this->autoRender = false;
        if (!$this->Session->check('Auth.User.id')) {
            echo json_encode(['status' => false, 'message' => 'Unauthorized']);
            return;
        }

        $this->loadModel('Catalog_product_tax_outlet');
        $this->loadModel('Catalog_tax');
        $this->loadModel('Catalog_product_branches');
        $this->loadModel('Location');

        if (!$this->request->is('post')) {
            echo json_encode(['status' => false, 'message' => 'POST required']);
            return;
        }

        $d = json_decode(file_get_contents('php://input'), true);
        if (empty($d)) {
            $d = $this->request->data;
        }

        $productId = intval($d['product_id']   ?? 0);
        $outletId  = intval($d['outlet_id']    ?? 0);
        $effFrom   = !empty($d['effective_from']) ? $d['effective_from'] : null;
        $taxes     = !empty($d['taxes']) && is_array($d['taxes']) ? $d['taxes'] : [];

        if (!$productId || !$outletId || empty($taxes)) {
            echo json_encode(['status' => false,
                'message' => 'Product, outlet, and at least one tax are required']);
            return;
        }

        $this->loadModel('Catalog_product_branches');
        $productRow = $this->Catalog_product_branches->find('first', [
            'conditions' => ['product_id' => $productId, 'location_id' => $outletId],
            'fields'     => ['product_name'],
            'recursive'  => -1,
        ]);

        $outletRow = $this->Location->find('first', [
            'conditions' => ['id' => $outletId],
            'fields'     => ['name'],
            'recursive'  => -1,
        ]);

        $existing = $this->Catalog_product_tax_outlet->find('first', [
            'conditions' => [
                'product_id' => $productId,
                'outlet_id'  => $outletId,
                'status'  => 1
            ],
            'recursive' => -1,
        ]);

        if (!empty($existing['Catalog_product_tax_outlet']['id'])) {
            $this->Catalog_product_tax_outlet->updateAll(
                [
                    'Catalog_product_tax_outlet.status'   => 0,
                    'Catalog_product_tax_outlet.modified' => "'" . date('Y-m-d H:i:s') . "'"
                ],
                [
                    'Catalog_product_tax_outlet.product_id' => $productId,
                    'Catalog_product_tax_outlet.outlet_id'  => $outletId,
                    'Catalog_product_tax_outlet.status'  => 1,
                ]
            );
        }

        $now    = date('Y-m-d H:i:s');
        $saved  = 0;
        $failed = 0;

        foreach ($taxes as $taxEntry) {
            $taxId  = intval($taxEntry['tax_id']           ?? 0);
            $taxPct = floatval($taxEntry['tax_percentage'] ?? 0);

            if (!$taxId) {
                $failed++;
                continue;
            }

            $taxRow = $this->Catalog_tax->find('first', [
                'conditions' => ['id' => $taxId],
                'fields'     => ['name', 'percentage'],
                'recursive'  => -1,
            ]);

            $row = [
                'product_id'     => $productId,
                'product_name'   => $productRow['Catalog_product_branches']['product_name'] ?? '',
                'outlet_id'      => $outletId,
                'outlet_name'    => $outletRow['Location']['name']                           ?? '',
                'tax_id'         => $taxId,
                'tax_name'       => $taxRow['Catalog_tax']['name']                           ?? '',
                'tax_percentage' => $taxPct,
                'effective_from' => $effFrom,
                'status'         => 1,
                'sync'           => 0,
                'local_sync'     => 0,
                'created'        => $now,
                'modified'       => $now
            ];

            $this->Catalog_product_tax_outlet->create();
            if ($this->Catalog_product_tax_outlet->save(['Catalog_product_tax_outlet' => $row])) {
                $saved++;
            } else {
                $failed++;
            }

        }
        if ($failed === 0) {
            echo json_encode(['status' => true,
                'message' => "{$saved} tax(es) saved for product #{$productId}"]);
        } else {
            echo json_encode(['status' => false,
                'message' => "{$saved} saved, {$failed} failed for product #{$productId}"]);
        }
    }

    public function services()
    {
        if (!$this->Session->check('Auth.User.id')) {
            return $this->redirect(['controller' => 'dashboard', 'action' => 'login']);
        }

        $this->loadModel('Service');
        $this->layout = "users";

        $services = $this->Service->find('all', [
            'order' => ['Service.id' => 'DESC']
        ]);

        $this->set(compact('services'));
    }

    public function add_service()
    {
        if (!$this->Session->check('Auth.User.id')) {
            return $this->redirect(['controller' => 'dashboard', 'action' => 'login']);
        }

        $this->loadModel('Service');
        $this->layout = "users";
        if ($this->request->is('post')) {

            $this->Service->create();

            if ($this->Service->save($this->request->data)) {
                $this->Flash->success(__('Service has been saved.'));
                return $this->redirect(array('action' => 'services'));
            } else {
                $this->Session->setFlash('Error adding service');
            }
        }
    }

    public function edit_service()
    {
        if (!$this->Session->check('Auth.User.id')) {
            return $this->redirect(['controller' => 'dashboard', 'action' => 'login']);
        }

        $this->loadModel('Service');
        $this->layout = "users";

        $id = $this->request->query('id');

        if (!$id) {
            return $this->redirect(['action' => 'services']);
        }

        $service = $this->Service->findById($id);

        if (!$service) {
            return $this->redirect(['action' => 'services']);
        }

        if ($this->request->is(['post', 'put'])) {

            $this->Service->id = $id;

            if ($this->Service->save($this->request->data)) {
                $this->Flash->success(__('Service updated'));
                return $this->redirect(array('action' => 'services'));
            } else {
                $this->Session->setFlash('Update failed');
            }
        }

        $this->request->data = $service;
    }

    public function delete_service()
    {
        $this->autoRender = false;

        $this->loadModel('Service');
        if ($this->request->is('post')) {

            $id = $this->request->data['_id'];

            if (!$id) {
                echo json_encode(['status' => false, 'msg' => 'No ID']);
                return;
            }

            $service = $this->Service->findById($id);

            if (!$service) {
                echo json_encode(['status' => false, 'msg' => 'Not found']);
                return;
            }

            $currentStatus = $service['Service']['status'];
            $newStatus = ($currentStatus == 1) ? 0 : 1;

            $this->Service->id = $id;

            if ($this->Service->saveField('status', $newStatus)) {
                echo json_encode(['status' => true]);
            } else {
                echo json_encode(['status' => false, 'msg' => 'Save failed']);
            }
        }
    }

    public function stock_transfer()
    {
        if(!$this->Session->check('Auth.User.id')){
            $this->redirect(array('controller'=>'dashboard','action' => 'login'));
        }

        $this->layout = "users";

        $startDate = date('m/d/Y', strtotime('-12 months'));
        $endDate = date('m/d/Y');

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

        $_shiftRange = $this->getShiftDateRange($startdate, $enddate);
        $startDatetime = $_shiftRange['startDatetime'];
        $endDatetime   = $_shiftRange['endDatetime'];

        $users = $this->Stock_transfer->query("SELECT * FROM stock_transfers where status=1 and created between '$startDatetime' and  '$endDatetime' ORDER BY id desc");
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

        $location_id = $this->Session->read('Auth.User.id');

        $locations = $this->Location->find('all', [
            'conditions' => [
                'status' => 1,
                'type' => 'Outlet'
            ]
        ]);

        $tolocations = $this->Location->find('all', [
            'conditions' => [
                'status' => 1,
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

                $created_by     = $this->Session->read('Auth.User.id');
                $created_by_name     = $this->Session->read('Auth.User.name');

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

                do {
                    $txnid = 'TRF' . time() . rand(100,999);

                    $existing = $this->Stock_transfer->find('first', [
                        'conditions' => ['transfer_id' => $txnid]
                    ]);

                } while (!empty($existing));

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

                foreach ($checkeddatas as $checkeddata) {

                    $skuid      = $checkeddata['id'];
                    $price      = $checkeddata['price'];
                    $sku_name   = $checkeddata['sku_name'];
                    $sku_brand  = $checkeddata['sku_brand'];
                    $unit_name  = $checkeddata['unit_name'];
                    $unit_id    = $checkeddata['unit_id'];
                    $quantity   = $checkeddata['sku_quantity'];
                    $taxPercent = $checkeddata['tax_percentage'];

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

                    $baseAmount = $price * $quantity;

                    $taxValue = ($baseAmount * $taxPercent) / 100;

                    $finalAmount = $baseAmount + $taxValue;

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

                $this->Stock_transfer->id = $this->Stock_transfer->getLastInsertID();

                if (!$this->Stock_transfer->save([
                    'tax' => $totalTax,
                    'amount' => $totalAmount
                ])) {
                    throw new Exception('Failed to update transfer totals.');
                }

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

    public function edit_stock_transfer()
    {
        if (!$this->Session->check('Auth.User.id')) {
            return $this->redirect([
                'controller' => 'dashboard',
                'action' => 'login'
            ]);
        }

        $this->layout = "users";

        $transfer_id = $this->request->query('id');

        $transfer = $this->Stock_transfer->find('first', [
            'conditions' => [
                'transfer_id' => $transfer_id,'status' =>1
            ]
        ]);

        $transferItems = $this->Stock_transfer_item->find('all', [
            'conditions' => [
                'transfer_id' => $transfer_id,'status' =>1
            ]
        ]);

        $locations = $this->Location->find('all', [
            'conditions' => [
                'status' => 1,
                'type' => 'Outlet'
            ]
        ]);

        $tolocations = $this->Location->find('all', [
            'conditions' => [
                'status' => 1,
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

            $oldTransfer = $this->Stock_transfer->find('first', [
                'conditions' => ['transfer_id' => $transfer_id, 'status' => 1]
            ]);

            $oldItems = $this->Stock_transfer_item->find('all', [
                'conditions' => ['transfer_id' => $transfer_id, 'status' => 1]
            ]);

            foreach ($oldItems as $oldItem) {
                $sku_id   = $oldItem['Stock_transfer_item']['sku_id'];
                $qty      = $oldItem['Stock_transfer_item']['sku_quantity'];
                $price    = $oldItem['Stock_transfer_item']['sku_price'];
                $sku_name = $oldItem['Stock_transfer_item']['sku_name'];
                $unit_id  = $oldItem['Stock_transfer_item']['sku_unit'];
                $unit_nm  = $oldItem['Stock_transfer_item']['sku_unit_name'];

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

            $this->Stock_transfer_item->updateAll(
                ['status' => 0],
                ['transfer_id' => $transfer_id, 'status' => 1]
            );

            $this->Stock_transfer->updateAll(
                ['status' => 0],
                ['transfer_id' => $transfer_id, 'status' => 1]
            );

            $created_by      = $this->Session->read('Auth.User.id');
            $created_by_name = $this->Session->read('Auth.User.name');

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

            foreach ($checkeddatas as $checkeddata) {
                $skuid      = $checkeddata['id'];
                $price      = $checkeddata['price'];
                $sku_name   = $checkeddata['sku_name'];
                $sku_brand  = $checkeddata['sku_brand'];
                $unit_name  = $checkeddata['unit_name'];
                $unit_id    = $checkeddata['unit_id'];
                $quantity   = $checkeddata['sku_quantity'];
                $taxPercent = $checkeddata['tax_percentage'];

                $baseAmount  = $price * $quantity;
                $taxValue    = ($baseAmount * $taxPercent) / 100;
                $finalAmount = $baseAmount + $taxValue;

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

	private function _refreshAllCosts()
	{
		if (!$this->Session->check('Auth.User.id')) {
			return $this->redirect(['controller' => 'dashboard', 'action' => 'login']);
		}
		$this->autoRender = false;

		$this->loadModel('Inventory_sku');
		$this->loadModel('Inventory_semifinished');
		$this->loadModel('Catalog_product');

		$this->Inventory_sku->query(
			"UPDATE inventory_skus sk
			 LEFT JOIN (
				 SELECT sk2.id AS sku_id,
					 CASE
						 WHEN NULLIF(CAST(sk2.avg_stockin_price AS DECIMAL(15,6)), 0) IS NOT NULL
							 THEN CAST(sk2.avg_stockin_price AS DECIMAL(15,6))
						 WHEN EXISTS (
							 SELECT 1 FROM stock_ledger sl
							 WHERE sl.sku_id      = sk2.id
							   AND sl.record_type = 'stockin'
							   AND sl.item_type   = 'sku'
						 )
							 THEN NULLIF(CAST(sk2.price AS DECIMAL(15,6)), 0)
						 ELSE NULL
					 END
					 / GREATEST(COALESCE(
						 (SELECT CAST(um.conversion_factor AS DECIMAL(15,6))
						  FROM catalog_unit_mapping um
						  WHERE um.unit_name        = sk2.unit_name
							AND um.mapped_unit_name = sk2.consumption_unit_name
							AND um.unit_name       != um.mapped_unit_name
							AND um.status = 1
						  ORDER BY um.id DESC LIMIT 1),
						 NULLIF(CAST(sk2.consumption_factor AS DECIMAL(15,6)), 0),
						 1
					 ), 1) AS new_price,
					 GREATEST(COALESCE(
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
				 WHERE sk2.unit_name != sk2.consumption_unit_name
				   AND sk2.status = 1
				   AND sk2.consumption_unit_name IS NOT NULL
				   AND sk2.consumption_unit_name != ''
			 ) latest ON latest.sku_id = sk.id
			 SET sk.consumption_unit_price = latest.new_price,
				 sk.consumption_factor     = latest.map_cf,
				 sk.sync = 0, sk.local_sync = 0
			 WHERE sk.status = 1
			   AND sk.unit_name != sk.consumption_unit_name
			   AND COALESCE(sk.consumption_unit_name, '') != ''
			   AND latest.new_price > 0"
		);

		$this->Inventory_sku->query(
			"UPDATE inventory_skus sk
			 SET sk.consumption_unit_price = CASE
					 WHEN NULLIF(CAST(sk.avg_stockin_price AS DECIMAL(15,6)), 0) IS NOT NULL
						 THEN CAST(sk.avg_stockin_price AS DECIMAL(15,6))
					 WHEN EXISTS (
						 SELECT 1 FROM stock_ledger sl
						 WHERE sl.sku_id      = sk.id
						   AND sl.record_type = 'stockin'
						   AND sl.item_type   = 'sku'
					 )
						 THEN CAST(sk.price AS DECIMAL(15,6))
					 ELSE sk.consumption_unit_price
				 END,
				 sk.sync = 0, sk.local_sync = 0
			 WHERE sk.status = 1
			   AND (sk.unit_name = sk.consumption_unit_name
					OR sk.consumption_unit_name IS NULL
					OR sk.consumption_unit_name = '')
			   AND CASE
					 WHEN NULLIF(CAST(sk.avg_stockin_price AS DECIMAL(15,6)), 0) IS NOT NULL
						 THEN 1
					 WHEN EXISTS (
						 SELECT 1 FROM stock_ledger sl
						 WHERE sl.sku_id      = sk.id
						   AND sl.record_type = 'stockin'
						   AND sl.item_type   = 'sku'
					 )
						 THEN 1
					 ELSE 0
				   END = 1"
		);

		$sfUpdate = "UPDATE inventory_semifinished isf
			 LEFT JOIN v_semifinished_recipe_cost vsf ON vsf.id = isf.id
			 SET isf.consumption_unit_price = COALESCE(vsf.unit_cost, isf.consumption_unit_price),
				 isf.sync = 0,
				 isf.local_sync = 0
			 WHERE isf.status = 1
			   AND COALESCE(vsf.unit_cost, 0) > 0";
		$this->Inventory_semifinished->query($sfUpdate);
		$this->Inventory_semifinished->query($sfUpdate);
		$this->Inventory_semifinished->query($sfUpdate);

		$this->Catalog_product->query(
			"UPDATE catalog_product cp
			 LEFT JOIN v_product_recipe_cost vpc ON vpc.id = cp.id
			 SET cp.food_cost = COALESCE(vpc.recipe_cost, 0),
				 cp.sync = 0,
				 cp.local_sync = 0
			 WHERE cp.status = 1"
		);

		$this->Flash->success(__('Food costs recalculated for all products and semi-finished items.'));
		return $this->redirect(array('action' => 'product_list'));
	}

	public function refresh_cost_for_dish_type_2_skus() {
		$this->autoRender = false;

		$this->loadModel('Inventory_sku');
		$this->loadModel('Inventory_semifinished');
		$this->loadModel('Catalog_product');

		$sku_rows = $this->Inventory_sku->query("SELECT id as sku_id FROM `inventory_skus` WHERE last_stockin_price =0 and status =1");

		if (empty($sku_rows)) {
			echo json_encode(['status' => false, 'message' => 'No SKUs found for dish_type=2 products']);
			return;
		}

		$sku_stockin_prices = [];
		$skipped            = [];
		$processed          = [];

		foreach ($sku_rows as $row) {
			$sku_id = intval($row['inventory_skus']['sku_id']);

			$last_result = $this->Inventory_sku->query("
				SELECT sku_price
				FROM stock_ledger
				WHERE sku_id = '$sku_id'
				  AND record_type = 'stockin'
				  AND item_type = 'sku'
				ORDER BY id DESC LIMIT 1
			");
			$last_stockin_price = floatval($last_result[0]['stock_ledger']['sku_price'] ?? 0);

			$avg_result = $this->Inventory_sku->query("
				SELECT SUM(sku_price * quantity) / SUM(quantity) AS avg_price
				FROM (
					SELECT sku_price, quantity
					FROM stock_ledger
					WHERE sku_id = '$sku_id'
					  AND record_type = 'stockin'
					  AND item_type = 'sku'
					ORDER BY id DESC LIMIT 3
				) AS last_3_txn
			");
			$avg_stockin_price = floatval($avg_result[0][0]['avg_price'] ?? 0);

			if ($avg_stockin_price > 0 || $last_stockin_price > 0) {
				$this->Inventory_sku->query("
					UPDATE inventory_skus
					SET last_stockin_price = " . round($last_stockin_price, 2) . ",
						avg_stockin_price  = " . round($avg_stockin_price, 2) . ",
						sync               = 0,
						local_sync         = 0
					WHERE id = $sku_id
					  AND status = 1
				");
			}

			$sku_stockin_prices[$sku_id] = round($avg_stockin_price, 2);
			$processed[] = [
				'sku_id'             => $sku_id,
				'last_stockin_price' => round($last_stockin_price, 2),
				'avg_price'          => round($avg_stockin_price, 2),
			];
		}

		if (!empty($sku_stockin_prices)) {
			$this->_refreshCostsForStockin($sku_stockin_prices);
		}

		echo json_encode([
			'status'           => true,
			'total_found'      => count($sku_rows),
			'total_processed'  => count($processed),
			'total_skipped'    => count($skipped),
			'processed'        => $processed,
			'skipped'          => $skipped,
			'message'          => 'Bulk cost refresh completed'
		]);
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

        $sfUpdate = "UPDATE inventory_semifinished isf
             LEFT JOIN v_semifinished_recipe_cost vsf ON vsf.id = isf.id
             SET isf.consumption_unit_price = COALESCE(vsf.unit_cost, isf.consumption_unit_price),
                 isf.sync = 0, isf.local_sync = 0
             WHERE isf.status = 1
               AND COALESCE(vsf.unit_cost, 0) > 0";
        $this->Inventory_semifinished->query($sfUpdate);
        $this->Inventory_semifinished->query($sfUpdate);
        $this->Inventory_semifinished->query($sfUpdate);

        $this->Catalog_product->query(
            "UPDATE catalog_product cp
             LEFT JOIN v_product_recipe_cost vpc ON vpc.id = cp.id
             SET cp.food_cost = COALESCE(vpc.recipe_cost, 0),
                 cp.sync = 0, cp.local_sync = 0
             WHERE cp.status = 1"
        );
    }

    public function sku_list_export()
    {
        if (!$this->Session->check('Auth.User.id')) {
            return $this->redirect(['controller' => 'dashboard', 'action' => 'login']);
        }
        $this->autoRender = false;

        $startDate = date('m/d/Y', strtotime('-12 months'));
        $endDate   = date('m/d/Y');

        if (!empty($this->request->query['sdate'])) {
            $daterange = $this->request->query['sdate'];
            $dates     = explode(" - ", $daterange);
            if (count($dates) === 2) {
                $startDate = trim($dates[0]);
                $endDate   = trim($dates[1]);
            }
        }

        $startdate     = date('Y-m-d', strtotime($startDate));
        $enddate       = date('Y-m-d', strtotime($endDate));
        $_shiftRange = $this->getShiftDateRange($startdate, $enddate);
        $startDatetime = $_shiftRange['startDatetime'];
        $endDatetime   = $_shiftRange['endDatetime'];

        $users = $this->Inventory_sku->query("
            SELECT inv.*,(SELECT sum(tax_percentage) from inventory_sku_tax WHERE product_id=inv.id and status=1) as total_tax
            FROM inventory_skus inv
            WHERE inv.created BETWEEN '$startDatetime' AND '$endDatetime'
            ORDER BY inv.id ASC
        ");

        App::import('Vendor', 'PHPExcel', ['file' => 'PHPExcel' . DS . 'PHPExcel.php']);
        $xls   = new PHPExcel();
        $sheet = $xls->getActiveSheet();
        $sheet->setTitle('SKU List');

        $headers = [
            'ID', 'Name', 'Category', 'Sub Category', 'Unit Name',
            'Price', 'Total Tax Percentage', 'Last Stock-in Price', 'AVG Price',
            'Required Stock', 'Status', 'Created'
        ];

        foreach ($headers as $ci => $h) {
            $col = PHPExcel_Cell::stringFromColumnIndex($ci);
            $sheet->setCellValue($col . '1', $h);
            $sheet->getStyle($col . '1')->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => [
                    'type'       => PHPExcel_Style_Fill::FILL_SOLID,
                    'startcolor' => ['rgb' => '4472C4']
                ]
            ]);
            $sheet->getColumnDimension($col)->setWidth(20);
        }

        $rowNum = 2;
        foreach ($users as $user) {
            $inv    = $user['inv'];
            $status = ($inv['status'] == 1) ? 'Active' : 'Inactive';

            $rowData = [
                $inv['id'],
                $inv['name'],
                $inv['cat_name'],
                $inv['subcat_name'],
                $inv['unit_name'],
                $inv['price'],
                $user[0]['total_tax'] ?? 0,
                $inv['last_stockin_price'] ?? '-',
                $inv['avg_stockin_price']  ?? '-',
                $inv['stock'],
                $status,
                $inv['created'],
            ];

            foreach ($rowData as $ci => $val) {
                $sheet->setCellValue(
                    PHPExcel_Cell::stringFromColumnIndex($ci) . $rowNum,
                    $val
                );
            }
            $rowNum++;
        }

        $filename = 'sku_list_' . date('Ymd_His') . '.xls';
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        PHPExcel_IOFactory::createWriter($xls, 'Excel5')->save('php://output');
        exit;
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

        $_shiftRange   = $this->getShiftDateRange($startdate, $enddate);
        $startDatetime = $_shiftRange['startDatetime'];
        $endDatetime   = $_shiftRange['endDatetime'];

        $authUser = $this->Session->read('Auth.User');
        $userRole = $authUser['role'] ?? '';
        $userLocationId = $authUser['location_ids'] ?? 0;

        $locationInSql = '(' . implode(',', array_map('trim', explode(',', $userLocationId))) . ')';
        $locationCondition = ($userRole !== 'admin') ? "AND warehouse_id IN $locationInSql" : '';

        $users = $this->Inventory_return->query("
            SELECT *
            FROM inventory_return
            WHERE status = 1
            $locationCondition
            AND created BETWEEN '$startDatetime' AND '$endDatetime'
            ORDER BY id DESC
        ");

        $yourStartDateValue = $startDate . ' - ' . $endDate;

        $this->set('startdate', $yourStartDateValue);

        $this->set(compact('users'));
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

    public function approve_return_to_warehouse()
    {
        if (!$this->Session->check('Auth.User.id')) {
            return $this->redirect([
                'controller' => 'dashboard',
                'action' => 'login'
            ]);
        }

        $this->layout = "users";

        $return_id = $this->request->query('id');

        $transfer = $this->Inventory_return->find('first', [
            'conditions' => [
                'return_id' => $return_id,
                'status' => 1
            ]
        ]);

        $transferItems = $this->Inventory_return_items->find('all', [
            'conditions' => [
                'return_id' => $return_id,
                'status' => 1,
                'sku_quantity >' => 0
            ]
        ]);

        $this->set(compact(
            'transfer',
            'transferItems'
        ));
    }

    public function approve_reject_return()
    {
        $this->autoRender = false;

        $datasource = $this->Inventory_return->getDataSource();
        $datasource->begin();

        try {

            $return_id = $this->request->data['return_id'];
            $status = $this->request->data['status'];
            $remarks = $this->request->data['remarks'];

            $items = json_decode($this->request->data['items'], true);

            $returnData = $this->Inventory_return->find('first', [
                'conditions' => [
                    'return_id' => $return_id,
                    'status' => 1
                ]
            ]);

            if (!$returnData) {
                throw new Exception("Return not found.");
            }

            $warehouse_id = $returnData['Inventory_return']['warehouse_id'];
            $transfer_from = $returnData['Inventory_return']['location_id'];

            if ($status == 'rejected') {
                $admin_status=2;
            }else{
                $admin_status=1;
            }

            $grandTotal = 0;

            foreach ($items as $item) {

                $sku_id = $item['sku_id'];
                $qty = $item['qty'];
                $tax_value = $item['tax_value'];
                $total = $item['total'];

                $this->Inventory_return_items->updateAll(
                    [
                        'admin_updated_quantity' => $qty,
                        'admin_updated_tax' => $tax_value,
                        'admin_updated_amount' => $total,
                        'admin_status' => $admin_status
                    ],
                    [
                        'return_id' => $return_id,
                        'sku_id' => $sku_id,
                        'status' => 1
                    ]
                );

                $grandTotal += $total;

                $returnItem = $this->Inventory_return_items->find('first', [
                    'conditions' => [
                        'return_id' => $return_id,
                        'sku_id' => $sku_id,
                        'status'=>1
                    ]
                ]);

                if($admin_status==1){

                    $ledgerprevdetails = $this->Stock_ledger->find('first', [
                        'conditions' => [
                            'sku_id' => $sku_id,
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

                    $newQuantity = $ledger_prev_quantity - $qty;
                    $sku_name=$returnItem['Inventory_return_items']['sku_name'];
                    $price=$returnItem['Inventory_return_items']['sku_price'];
                    $unit_id=$returnItem['Inventory_return_items']['sku_unit'];
                    $unit_name=$returnItem['Inventory_return_items']['sku_unit_name'];

                    $ledgerData = [
                        'store_id'        => $transfer_from,
                        'sku_id'          => $sku_id,
                        'sku_name'        => $sku_name,
                        'sku_price'       => $price,
                        'unit_id'         => $unit_id,
                        'unit_name'       => $unit_name,
                        'record_id'       => $return_id,
                        'record_type'     => 'return_to_warehouse',
                        'quantity'        => $qty,
                        'balance_stock'   => round($newQuantity,2),
                        'actual_balance'  => round($newQuantity,2),
                        'quantity_type'   => 'debit',
                        'type'            => 'Outlet',
                        'item_type'       => 'SKU',
                        'created'         => date('Y-m-d H:i:s')
                    ];

                    $this->Stock_ledger->create();

                    if (!$this->Stock_ledger->save($ledgerData)) {
                        throw new Exception('Source ledger save failed.');
                    }

                    $prevLedger = $this->Stock_ledger->find('first', [
                        'conditions' => [
                            'sku_id' => $sku_id,
                            'store_id' => $warehouse_id,
                            'type' => 'DC',
                            'item_type' => 'SKU'
                        ],
                        'order' => ['id DESC']
                    ]);

                    $prevBalance = $prevLedger['Stock_ledger']['actual_balance'] ?? 0;

                    $newBalance = $prevBalance + $qty;

                    $this->Stock_ledger->create();

                    $this->Stock_ledger->save([
                        'store_id' => $warehouse_id,
                        'sku_id' => $sku_id,
                        'sku_name' => $returnItem['Inventory_return_items']['sku_name'],
                        'sku_price' => $returnItem['Inventory_return_items']['sku_price'],
                        'unit_id' => $returnItem['Inventory_return_items']['sku_unit'],
                        'unit_name' => $returnItem['Inventory_return_items']['sku_unit_name'],
                        'record_id' => $return_id,
                        'record_type' => 'return_approved',
                        'quantity' => $qty,
                        'balance_stock' => round($newBalance,2),
                        'actual_balance' => round($newBalance,2),
                        'quantity_type' => 'credit',
                        'type' => 'DC',
                        'item_type' => 'SKU',
                        'created' => date('Y-m-d H:i:s')
                    ]);
                }
            }

            $this->Inventory_return->updateAll(
                [
                    'admin_status' => $admin_status,
                    'admin_remarks' => "'" . addslashes($remarks) . "'",
                    'admin_updated_amount' => round($grandTotal, 2),
                    'admin_updated_date' => "'" . date('Y-m-d H:i:s') . "'"
                ],
                [
                    'return_id' => $return_id,
                    'status' => 1
                ]
            );

            $datasource->commit();

            echo json_encode([
                'response_code' => 1,
                'data' => $status == 'approved' ? 'Return Approved Successfully' : 'Return Rejected Successfully'
            ]);

        } catch (Exception $e) {

            $datasource->rollback();

            echo json_encode([
                'response_code' => 0,
                'data' => $e->getMessage()
            ]);
        }
    }

    private function _getMaterialUsageData($startdate, $enddate, $startDatetime, $endDatetime, $locationCondition, $damageLocCond) {

        $salesRows = $this->Order_orderhistory->query("
            SELECT
                o.product_id,
                MAX(o.product_name) AS product_name,
                SUM(CASE WHEN rc.is_nc = 0 THEN o.quantity ELSE 0 END) AS billed_qty,
                SUM(CASE WHEN rc.is_nc = 1 THEN o.quantity ELSE 0 END) AS nc_qty
            FROM order_orderhistories o
            JOIN (
                SELECT reference_id, store_id,
                    MAX(CASE WHEN type = 7 THEN 1 ELSE 0 END) AS is_nc
                FROM order_payments
                WHERE status = 2
                GROUP BY reference_id, store_id
            ) rc ON rc.reference_id = o.reference_id AND rc.store_id = o.store_id
            WHERE o.status = 2
              AND o.created BETWEEN '$startDatetime' AND '$endDatetime'
              $locationCondition
            GROUP BY o.product_id
            HAVING SUM(o.quantity) > 0
        ");

        $productSales = [];
        foreach ($salesRows as $row) {
            $pid = intval($row['o']['product_id'] ?? $row[0]['product_id']);
            $productSales[$pid] = [
                'name'   => $row['o']['product_name'] ?? $row[0]['product_name'],
                'billed' => floatval($row['o']['billed_qty'] ?? $row[0]['billed_qty']),
                'nc'     => floatval($row['o']['nc_qty']     ?? $row[0]['nc_qty']),
            ];
        }

        $productIdList = implode(',', array_map('intval', array_keys($productSales) ?: [0]));

        $productLinkRows = $this->Inventory_sku->query("
            SELECT parent_id, component_type, component_id, component_name,
                   quantity, conversion_quantity, status
            FROM inventory_linking
            WHERE parent_type = 'Product'
              AND component_type IN ('Sku', 'Semifinished')
              AND status = 1
              AND parent_id IN ($productIdList)
        ");

        $productLinks = [];
        $allComponentSkuIds = [];
        foreach ($productLinkRows as $r) {
            $d = $r['inventory_linking'] ?? $r[0];
            $pid = intval($d['parent_id']);
            $productLinks[$pid][] = $d;
            if ($d['component_type'] === 'Sku') {
                $allComponentSkuIds[] = intval($d['component_id']);
            }
        }

        $sfInfoRows = $this->Inventory_sku->query("
            SELECT id, name, consumption_factor FROM inventory_semifinished WHERE status = 1
        ");
        $sfInfo = [];
        foreach ($sfInfoRows as $r) {
            $d = $r['inventory_semifinished'] ?? $r[0];
            $sfInfo[intval($d['id'])] = [
                'name'               => $d['name'],
                'consumption_factor' => floatval($d['consumption_factor'] ?: 1),
            ];
        }

        $sfLinkRows = $this->Inventory_sku->query("
            SELECT parent_id, component_type, component_id, component_name, quantity, conversion_quantity
            FROM inventory_linking
            WHERE parent_type = 'Semifinished'
              AND component_type IN ('Sku', 'Semifinished')
              AND status = 1
        ");
        $sfLinks = [];
        foreach ($sfLinkRows as $r) {
            $d = $r['inventory_linking'] ?? $r[0];
            $sfid = intval($d['parent_id']);
            $sfLinks[$sfid][] = $d;
            if ($d['component_type'] === 'Sku') {
                $allComponentSkuIds[] = intval($d['component_id']);
            }
        }

        $skuConsumptionFactors = [];
        $allComponentSkuIds = array_unique($allComponentSkuIds);
        if (!empty($allComponentSkuIds)) {
            $cfIdStr = implode(',', array_map('intval', $allComponentSkuIds));
            $cfRows  = $this->Inventory_sku->query("
                SELECT id, consumption_factor FROM inventory_skus WHERE id IN ($cfIdStr)
            ");
            foreach ($cfRows as $cfRow) {
                $cf = $cfRow['inventory_skus'] ?? $cfRow[0];
                $skuConsumptionFactors[intval($cf['id'])] = max(1.0, floatval($cf['consumption_factor'] ?: 1));
            }
        }

        $expandSF = null;
        $expandSF = function($sfId, $sfLinks, $sfInfo, $skuCF, $depth = 0) use (&$expandSF) {
            if ($depth > 6 || empty($sfLinks[$sfId])) return [];
            $skuQty = [];
            foreach ($sfLinks[$sfId] as $link) {
                if ($link['component_type'] === 'Sku') {
                    $skuid   = intval($link['component_id']);
                    $convQty = floatval($link['conversion_quantity']);
                    $cf      = max(1.0, $skuCF[$skuid] ?? 1.0);
                    $skuQty[$skuid] = ($skuQty[$skuid] ?? 0) + ($convQty / $cf);
                } elseif ($link['component_type'] === 'Semifinished') {
                    $nestedId       = intval($link['component_id']);
                    $nestedConvQty  = floatval($link['conversion_quantity']);
                    $nestedYield    = max(1.0, floatval($sfInfo[$nestedId]['consumption_factor'] ?? 1));
                    $nestedFraction = $nestedConvQty / $nestedYield;
                    $nestedSkus     = $expandSF($nestedId, $sfLinks, $sfInfo, $skuCF, $depth + 1);
                    foreach ($nestedSkus as $skuid => $qPerNestedBatch) {
                        $skuQty[$skuid] = ($skuQty[$skuid] ?? 0) + $nestedFraction * $qPerNestedBatch;
                    }
                }
            }
            return $skuQty;
        };

        $skuConsumption = [];
        foreach ($productSales as $pid => $sale) {
            $billedQty = $sale['billed'];
            $ncQty     = $sale['nc'];
            if (($billedQty + $ncQty) == 0) continue;

            $skuPerProduct = [];
            foreach ($productLinks[$pid] ?? [] as $link) {
                if ($link['component_type'] === 'Sku') {
                    $skuid    = intval($link['component_id']);
                    $convQty  = floatval($link['conversion_quantity']);
                    $cf       = max(1.0, $skuConsumptionFactors[$skuid] ?? 1.0);
                    $skuPerProduct[$skuid] = ($skuPerProduct[$skuid] ?? 0) + ($convQty / $cf);
                } elseif ($link['component_type'] === 'Semifinished') {
                    $sfId       = intval($link['component_id']);
                    $sfConvQty  = floatval($link['conversion_quantity']);
                    $sfYield    = max(1.0, floatval($sfInfo[$sfId]['consumption_factor'] ?? 1));
                    $sfFraction = $sfConvQty / $sfYield;
                    $sfSkuAmounts = $expandSF($sfId, $sfLinks, $sfInfo, $skuConsumptionFactors);
                    foreach ($sfSkuAmounts as $skuid => $qtyPerBatch) {
                        $skuPerProduct[$skuid] = ($skuPerProduct[$skuid] ?? 0) + $sfFraction * $qtyPerBatch;
                    }
                }
            }

            foreach ($skuPerProduct as $skuid => $qtyPerProduct) {
                if (!isset($skuConsumption[$skuid])) {
                    $skuConsumption[$skuid] = ['billed' => 0, 'nc' => 0];
                }
                $skuConsumption[$skuid]['billed'] += $qtyPerProduct * $billedQty;
                $skuConsumption[$skuid]['nc']     += $qtyPerProduct * $ncQty;
            }
        }

        $skuDamage = [];
        $damageRows = $this->Inventory_sku->query("
            SELECT di.item_id, SUM(CAST(di.quantity AS DECIMAL(15,4))) AS damage_qty
            FROM inventory_damage_items di
            JOIN inventory_damage d ON d.damage_id = di.damage_id
            WHERE d.status = 1
              AND d.damage_date BETWEEN '$startdate' AND '$enddate'
              $damageLocCond
            GROUP BY di.item_id
        ");
        foreach ($damageRows as $r) {
            $d = $r['inventory_damage_items'] ?? $r[0];
            $skuDamage[intval($d['item_id'])] = floatval($d['damage_qty']);
        }

        $allSkuIds = array_unique(array_merge(array_keys($skuConsumption), array_keys($skuDamage)));
        $skuMaster = [];
        if (!empty($allSkuIds)) {
            $skuIdStr = implode(',', array_map('intval', $allSkuIds));
            $skuRows  = $this->Inventory_sku->query("
                SELECT id, name,cat_name,subcat_name,unit_name, avg_stockin_price, last_stockin_price, price,(SELECT sum(tax_percentage) from inventory_sku_tax WHERE product_id=inventory_skus.id and status=1) as total_tax,(SELECT GROUP_CONCAT(CONCAT(tax_name, '(', tax_percentage, '%)') ORDER BY tax_id SEPARATOR ', ') from inventory_sku_tax WHERE product_id=inventory_skus.id and status=1) as tax_slabs
                FROM inventory_skus WHERE id IN ($skuIdStr)
            ");
            foreach ($skuRows as $r) {
                $d = $r['inventory_skus'] ?? $r[0];
                $avgStockin  = floatval($d['avg_stockin_price']  ?? 0);
                $lastStockin = floatval($d['last_stockin_price'] ?? 0);
                $basePrice   = floatval($d['price']              ?? 0);

                $totalTax    = floatval($r[0]['total_tax']          ?? 0);
                $avgPrice = $avgStockin > 0 ? $avgStockin : ($lastStockin > 0 ? $lastStockin : $basePrice);
                $taxSlabs = $r[0]['tax_slabs']          ?? '';

                $hasTax   = !empty($taxSlabs);
                $taxValue = $hasTax ? round($avgPrice * $totalTax / 100, 2) : null;

                $skuMaster[intval($d['id'])] = [
                    'name'      => $d['name'],
                    'total_tax' => $r[0]['total_tax'] ?? 0,
                    'tax_slabs'             => ($r[0]['tax_slabs'] ?? ''),
                    'cat_name' => $d['cat_name'] ?? '',
                    'subcat_name' => $d['subcat_name'] ?? '',
                    'unit_name' => $d['unit_name'],
                    'avg_price_without_tax' => round($avgPrice,2),
                    'tax_value'             => $taxValue,
                    'avg_price'             => round($avgPrice + ($taxValue ?? 0), 2),
                ];
            }
        }

        $reportRows = [];
        foreach ($allSkuIds as $skuid) {
            if (!isset($skuMaster[$skuid])) continue;
            $billed = round($skuConsumption[$skuid]['billed'] ?? 0, 4);
            $nc     = round($skuConsumption[$skuid]['nc']     ?? 0, 4);
            $damage = round($skuDamage[$skuid]                ?? 0, 4);
            $total  = $billed + $nc + $damage;
            if ($total == 0) continue;
            $avgPx  = $skuMaster[$skuid]['avg_price'];
            $reportRows[] = [
                'sku_id'             => $skuid,
                'sku_name'           => $skuMaster[$skuid]['name'],
                'cat_name'           => $skuMaster[$skuid]['cat_name'],
                'subcat_name'        => $skuMaster[$skuid]['subcat_name'],
                'tax_slabs'          => $skuMaster[$skuid]['tax_slabs'],
                'total_tax'          => $skuMaster[$skuid]['total_tax'],
                'tax_value'          => $skuMaster[$skuid]['tax_value'],
                'unit_name'          => $skuMaster[$skuid]['unit_name'],
                'avg_price'          => $avgPx,
                'billed_consumption' => $billed,
                'nc_consumption'     => $nc,
                'damage'             => $damage,
                'total_consumption'  => $total,
                'total_cost'         => round($total * $skuMaster[$skuid]['avg_price'], 2),
            ];
        }
        usort($reportRows, function($a, $b) { return strcmp($a['sku_name'], $b['sku_name']); });
        return $reportRows;
    }

    public function edit_subadmin()
    {
        if(!$this->Session->check('Auth.User.id')){
            $this->redirect(array('controller'=>'dashboard','action' => 'login'));
        }

        $this->layout = "users";

        if ($this->data['User']['id'] != '') {

            $id   = $this->data['User']['id'];
            $name = $this->data['User']['name'];

            if ($name == '') {
                $this->Flash->error(__('The Name could not be Blank'));
                return $this->redirect(array('action' => 'edit_subadmin?id=' . $id));
            }

            $mobile       = $this->data['User']['mobile'];
            $password     = $this->data['User']['password'];
            $location_ids = $this->data['User']['location'];
            $role         = $this->data['User']['role'];

            $userdetails = $this->User->find('first', array('conditions' => array('User.id' => $id)));
            $username    = $userdetails['User']['username'];

            $this->User->query("UPDATE users SET name='$name', mobile='$mobile', sync=0, role='$role', local_sync=0 WHERE id=" . $id);
            if ($password) {
                $password = AuthComponent::password($password);
                $this->User->query("UPDATE users SET password='$password', sync=0, local_sync=0 WHERE id=" . $id);
            }

            $this->User_location->query("UPDATE user_locations SET status=0, sync=0, local_sync=0 WHERE user_id=" . $id);
            foreach ($location_ids as $location_id) {
                $locationdetail = $this->Location->find('first', array('conditions' => array('Location.id' => $location_id)));
                $locationname   = $locationdetail['Location']['name'];

                $data['User_location'] = array(
                    'user_id'       => $id,
                    'user_name'     => $username,
                    'location_id'   => $location_id,
                    'location_name' => $locationname,
                    'sync'          => 0,
                    'local_sync'    => 0
                );
                if ($this->User_location->saveAll($data['User_location'])) {
                    $this->User->query("UPDATE users SET location_id='$location_id', location_name='$locationname', sync=0, local_sync=0 WHERE id=" . $id);
                } else {
                    $this->Flash->error(__('The user Locations could not be saved. Please, try again.'));
                }
            }

            $this->User->query("UPDATE user_modules SET status=0, sync=0, local_sync=0 WHERE user_id=" . $id);

            $submittedModules = $this->data['modules'] ?? [];
            $submittedSubs    = $this->data['subs']    ?? [];

            $selectedModules = array_keys(array_filter($submittedModules));

            foreach ($selectedModules as $moduleKey) {
                if (!isset(subadminmoduleswithlabel[$moduleKey])) continue;

                $this->User_module->create();
                $data['User_module'] = array(
                    'name'       => $moduleKey,
                    'user_id'    => $id,
                    'sync'       => 0,
                    'local_sync' => 0
                );
                if (!$this->User_module->saveAll($data['User_module'])) {
                    $this->Flash->error(__('The user modules could not be saved. Please, try again.'));
                }
            }

            $allSubmenus = subadminsubmenus;
            foreach ($submittedSubs as $parentKey => $subs) {
                if (!in_array($parentKey, $selectedModules)) continue;
                if (!isset($allSubmenus[$parentKey])) continue;

                foreach ($subs as $subKey => $val) {
                    if (!$val) continue;
                    if (!isset($allSubmenus[$parentKey][$subKey])) continue;

                    $this->User_module->create();
                    $data['User_module'] = array(
                        'name'       => $parentKey . ':' . $subKey,
                        'user_id'    => $id,
                        'sync'       => 0,
                        'local_sync' => 0
                    );
                    if (!$this->User_module->saveAll($data['User_module'])) {
                        $this->Flash->error(__('The user modules could not be saved. Please, try again.'));
                    }
                }
            }

            $this->Flash->success(__('Sub admin has been Updated.'));
            return $this->redirect(array('action' => 'subadmin_list'));

        } else {

            $id = $_REQUEST['id'];

            $userdetail = $this->User->find('first', array('conditions' => array('User.id' => $id)));
            $this->set(compact('userdetail'));

            $locations = $this->Location->find('all', array('conditions' => array('status' => 1)));
            $this->set(compact('locations'));

            $selectedlocationss = $this->User_location->find('list', array(
                'conditions' => array('User_location.status' => 1, 'User_location.user_id' => $id),
                'fields'     => array('User_location.location_id', 'User_location.location_id')
            ));
            $selectedLocationIds = array_keys($selectedlocationss);
            $this->set(compact('selectedLocationIds'));

            $selectemodule  = "SELECT name FROM user_modules WHERE user_id=$id AND status=1";
            $selectemodules = $this->User_module->query($selectemodule);
            $this->set(compact('selectemodules'));

            $modules = subadminmoduleswithlabel;
            $this->set(compact('modules'));
            $this->set('allSubmenus', subadminsubmenus);
        }
    }

    public function subadmin_list() {
        if(!$this->Session->check('Auth.User.id')){
            $this->redirect(array('controller'=>'dashboard','action' => 'login'));
        }

        $this->layout = "users";

        $startDate = date('m/d/Y', strtotime('-12 months'));
        $endDate = date('m/d/Y');

        $authUser = $this->Session->read('Auth.User');
        $userRole = $authUser['role'] ?? '';
        $userLocationId = $authUser['location_ids'] ?? 0;
        $locationCondition = '';
        if ($userRole !== 'admin') {
            $locationCondition = "AND id IN (SELECT user_id from user_locations WHERE status=1 and location_id IN (" . $userLocationId . "))";
        }
        if($this->request->is('post', 'put'))
        {
            $daterange  = $this->request->data['daterange'];
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

        $_shiftRange = $this->getShiftDateRange($startdate, $enddate);
        $startDatetime = $_shiftRange['startDatetime'];
        $endDatetime   = $_shiftRange['endDatetime'];

        $users = $this->User->query("
            SELECT *,
                (SELECT GROUP_CONCAT(name ORDER BY name SEPARATOR ',')
                 FROM user_modules
                 WHERE user_id = users.id
                   AND status = 1
                   AND name NOT LIKE '%:%'
                ) as module,
                (SELECT GROUP_CONCAT(location_name)
                 FROM user_locations
                 WHERE user_id = users.id
                   AND status = 1
                ) as locations
            FROM users
            WHERE role IN ('subadmin','kitchen')
            $locationCondition
            AND created BETWEEN '$startDatetime' AND '$endDatetime'
            ORDER BY id DESC
        ");

        $moduleLabels      = subadminmoduleswithlabel;
        $yourStartDateValue = $startDate . ' - ' . $endDate;
        $this->set('startdate', $yourStartDateValue);
        $this->set(compact('users'));
        $this->set(compact('moduleLabels'));

        $locationLabels = $this->Location->find('list', [
            'fields' => ['Location.id', 'Location.name'],
            'conditions' => ['Location.status' => 1]
        ]);
        $this->set(compact('locationLabels'));
    }

    public function create_subadmin()
    {
        if(!$this->Session->check('Auth.User.id')){
            $this->redirect(array('controller'=>'dashboard','action' => 'login'));
        }

        $this->layout = "users";

        $authUser = $this->Session->read('Auth.User');
        $userRole = $authUser['role'] ?? '';
        $userLocationId = $authUser['location_ids'] ?? '';

        $conditions = ['status' => 1];

        if ($userRole !== 'admin' && !empty($userLocationId)) {
            $ids = array_filter(array_map('trim', explode(',', $userLocationId)));
            if (!empty($ids)) {
                $conditions['id IN'] = $ids;
            }
        }

        $modules = subadminmoduleswithlabel;
        $this->set(compact('modules'));
        $this->set('allSubmenus', subadminsubmenus);

        $locations = $this->Location->find('all', ['conditions' => $conditions]);
        $this->set(compact('locations'));

        if ($this->request->is(array('post', 'put'))) {

            $name = $this->data['User']['name'];
            if ($name == '') {
                $this->Flash->error(__('The Name could not be Blank'));
                return $this->redirect(array('action' => 'create_subadmin'));
            }

            $mobile   = $this->data['User']['mobile'];
            $username = $this->data['User']['username'];

            $userdetail = $this->User->find('first', array('conditions' => array('User.username' => $username)));
            if ($userdetail) {
                $this->Flash->error(__('Username already exist'));
                return $this->redirect(array('action' => 'create_subadmin'));
            }

            $password     = AuthComponent::password($this->data['User']['password']);
            $location_ids = $this->data['User']['location'];
            $role         = $this->data['User']['role'];

            $data['User'] = array(
                'name'       => $name,
                'username'   => $username,
                'password'   => $password,
                'mobile'     => $mobile,
                'role'       => $role,
                'sync'       => 0,
                'local_sync' => 0
            );

            if ($this->User->saveAll($data['User'])) {
                $lastInsertedID = $this->User->id;

                foreach ($location_ids as $location_id) {
                    $locationdetail = $this->Location->find('first', array('conditions' => array('Location.id' => $location_id)));
                    $locationname   = $locationdetail['Location']['name'];

                    $data['User_location'] = array(
                        'user_id'       => $lastInsertedID,
                        'user_name'     => $username,
                        'location_id'   => $location_id,
                        'location_name' => $locationname,
                        'sync'          => 0,
                        'local_sync'    => 0
                    );
                    if ($this->User_location->saveAll($data['User_location'])) {
                        $this->User->query("UPDATE users SET location_id='$location_id', location_name='$locationname', sync=0, local_sync=0 WHERE id=" . $lastInsertedID);
                    } else {
                        $this->Flash->error(__('The user Locations could not be saved. Please, try again.'));
                    }
                }

                $submittedModules = $this->data['modules'] ?? [];
                $submittedSubs    = $this->data['subs']    ?? [];

                $selectedModules = array_keys(array_filter($submittedModules));

                foreach ($selectedModules as $moduleKey) {
                    if (!isset(subadminmoduleswithlabel[$moduleKey])) continue;

                    $this->User_module->create();
                    $data['User_module'] = array(
                        'name'       => $moduleKey,
                        'user_id'    => $lastInsertedID,
                        'sync'       => 0,
                        'local_sync' => 0
                    );
                    if (!$this->User_module->saveAll($data['User_module'])) {
                        $this->Flash->error(__('The user modules could not be saved. Please, try again.'));
                    }
                }

                $allSubmenus = subadminsubmenus;
                foreach ($submittedSubs as $parentKey => $subs) {
                    if (!in_array($parentKey, $selectedModules)) continue;
                    if (!isset($allSubmenus[$parentKey])) continue;

                    foreach ($subs as $subKey => $val) {
                        if (!$val) continue;
                        if (!isset($allSubmenus[$parentKey][$subKey])) continue;

                        $this->User_module->create();
                        $data['User_module'] = array(
                            'name'       => $parentKey . ':' . $subKey,
                            'user_id'    => $lastInsertedID,
                            'sync'       => 0,
                            'local_sync' => 0
                        );
                        if (!$this->User_module->saveAll($data['User_module'])) {
                            $this->Flash->error(__('The user modules could not be saved. Please, try again.'));
                        }
                    }
                }

                $this->Flash->success(__('The User has been saved.'));
                return $this->redirect(array('action' => 'subadmin_list'));

            } else {
                $this->Flash->error(__('The user could not be saved. Please, try again.'));
            }
        }
    }

    public function replace_sku()
    {
        if(!$this->Session->check('Auth.User.id')){
            $this->redirect(array('controller'=>'dashboard','action' => 'login'));
        }
        $this->layout = "users";

        $this->loadModel('Inventory_sku');
        $this->loadModel('Inventory_linking');
        $this->loadModel('Catalog_unit_mapping');

        if ($this->request->is(array('post', 'put'))) {
            $sku_a_id = isset($this->request->data['Replace']['sku_a']) ? trim($this->request->data['Replace']['sku_a']) : '';
            $sku_b_id = isset($this->request->data['Replace']['sku_b']) ? trim($this->request->data['Replace']['sku_b']) : '';

            if (!empty($sku_a_id) && !empty($sku_b_id) && $sku_a_id != $sku_b_id) {

                $sku_a = $this->Inventory_sku->find('first', array(
                    'conditions' => array('id' => $sku_a_id)
                ));

                $sku_b = $this->Inventory_sku->find('first', array(
                    'conditions' => array('id' => $sku_b_id, 'status' => 1)
                ));

                if (!empty($sku_b)) {

                    $sku_b_name                   = $sku_b['Inventory_sku']['name'];
                    $sku_b_consumption_unit_id    = $sku_b['Inventory_sku']['consumption_unit_id']    ?: 0;
                    $sku_b_consumption_unit_name  = $sku_b['Inventory_sku']['consumption_unit_name']  ?: '';
                    $sku_b_consumption_unit_price = $sku_b['Inventory_sku']['consumption_unit_price'] ?: 0;
                    $sku_b_purchase_unit_id       = $sku_b['Inventory_sku']['unit']                   ?: 0;
                    $sku_b_purchase_unit_name     = $sku_b['Inventory_sku']['unit_name']              ?: '';

                    $mappedUnits = $this->Catalog_unit_mapping->find('first', array(
                        'conditions' => array(
                            'mapped_unit_id' => $sku_b_consumption_unit_id,
                            'unit_id'        => $sku_b_purchase_unit_id,
                            'status'         => 1
                        ),
                        'fields'    => array('conversion_factor'),
                        'recursive' => -1
                    ));
                    $conversion_factor = !empty($mappedUnits['Catalog_unit_mapping']['conversion_factor'])
                        ? floatval($mappedUnits['Catalog_unit_mapping']['conversion_factor'])
                        : 0;

                    $affected_links = $this->Inventory_linking->find('all', array(
                        'conditions' => array(
                            'Inventory_linking.component_type' => 'Sku',
                            'Inventory_linking.component_id'   => $sku_a_id,
                            'Inventory_linking.status'         => 1
                        )
                    ));

                    $affected_count = count($affected_links);

                    if ($affected_count > 0) {

                        $all_saved = true;

                        foreach ($affected_links as $link) {
                            $row = $link['Inventory_linking'];

                            $softDeleted = $this->Inventory_linking->updateAll(
                                array(
                                    'Inventory_linking.status'     => 0,
                                    'Inventory_linking.sync'       => 0,
                                    'Inventory_linking.local_sync' => 0,
                                    'Inventory_linking.modified'   => "'" . date('Y-m-d H:i:s') . "'"
                                ),
                                array('Inventory_linking.id' => $row['id'])
                            );

                            if ($softDeleted === false) {
                                $all_saved = false;
                                continue;
                            }

                            $conversion_qty    = floatval($row['conversion_quantity']);
                            $purchase_unit_qty = ($conversion_factor > 0)
                                ? $conversion_qty / $conversion_factor
                                : 0;

                            $newRecord = array(

                                'parent_type'      => $row['parent_type'],
                                'parent_id'        => $row['parent_id'],
                                'parent_name'      => $row['parent_name'],
                                'parent_yield'     => $row['parent_yield'],

                                'component_type'   => $row['component_type'],

                                'component_id'                   => $sku_b_id,
                                'component_name'                 => $sku_b_name,
                                'component_unit'                 => $sku_b_consumption_unit_id,
                                'component_unit_name'            => $sku_b_consumption_unit_name,
                                'component_price'                => $sku_b_consumption_unit_price,
                                'component_purchase_unit'        => $sku_b_purchase_unit_id,
                                'component_purchase_unit_name'   => $sku_b_purchase_unit_name,
                                'quantity'                       => $purchase_unit_qty,

                                'conversion_quantity' => $row['conversion_quantity'],

                                'status'      => 1,
                                'sync'        => 0,
                                'local_sync'  => 0

                            );

                            $this->Inventory_linking->create();
                            if (!$this->Inventory_linking->save($newRecord)) {
                                $all_saved = false;
                            }
                        }

                        if (method_exists($this, '_refreshAllCosts')) {
                            $this->_refreshAllCosts();
                        }

                        $sku_a_name = !empty($sku_a) ? $sku_a['Inventory_sku']['name'] : 'SKU #' . $sku_a_id;

                        if ($all_saved) {
                            $this->Flash->success(__(
                                '{0} recipe link(s) updated: "{1}" replaced with "{2}" successfully.',
                                array($affected_count, $sku_a_name, $sku_b_name)
                            ));
                        } else {
                            $this->Flash->error(__('Some links could not be updated. Please check and try again.'));
                        }

                    } else {
                        $this->Flash->error(__('No active recipe links found for the selected SKU A.'));
                    }

                } else {
                    $this->Flash->error(__('Invalid or inactive Replacement SKU (SKU B).'));
                }

            } else {
                $this->Flash->error(__('Please select two different valid SKUs.'));
            }

            return $this->redirect(array('action' => 'replace_sku'));
        }

        $skus = $this->Inventory_sku->find('list', array(
            'fields'     => array('id', 'name'),
            'conditions' => array('status' => 1),
            'order'      => array('name' => 'asc')
        ));

        $this->set(compact('skus'));
    }

    public function preview_sku_links()
    {
        if (!$this->Session->check('Auth.User.id')) {
            return $this->redirect(array('controller' => 'dashboard', 'action' => 'login'));
        }

        $this->autoRender = false;
        $this->response->type('application/json');

        $this->loadModel('Inventory_linking');

        $sku_a_id = isset($this->request->data['sku_a_id']) ? trim($this->request->data['sku_a_id']) : '';

        if (empty($sku_a_id)) {
            echo json_encode(['count' => 0, 'links' => []]);
            return;
        }

        $links = $this->Inventory_linking->find('all', array(
            'conditions' => array(
                'Inventory_linking.component_type' => 'Sku',
                'Inventory_linking.component_id'   => $sku_a_id,
                'Inventory_linking.status'         => 1
            ),
            'fields' => array(
                'parent_type', 'parent_name', 'quantity',
                'component_unit_name'
            ),
            'order' => array('parent_name' => 'asc')
        ));

        $result = [];
        foreach ($links as $link) {
            $result[] = array(
                'parent_type'         => $link['Inventory_linking']['parent_type'],
                'parent_name'         => $link['Inventory_linking']['parent_name'],
                'quantity'            => $link['Inventory_linking']['quantity'],
                'component_unit_name' => $link['Inventory_linking']['component_unit_name']
            );
        }

        echo json_encode(['count' => count($result), 'links' => $result]);
    }

    public function category_list_export() {
        $startdate = isset($this->request->query['sdate']) ? $this->request->query['sdate'] : date('Y-m-d');

        $users = $this->Catalog_category->find('all', [
            'order' => ['Catalog_category.created' => 'DESC']
        ]);

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="category_list_' . $startdate . '.csv"');

        $output = fopen('php://output', 'w');
        fputcsv($output, ['ID', 'Name', 'Status', 'Created']);

        foreach ($users as $user) {
            $status = $user['Catalog_category']['status'] == 1 ? 'Active' : 'Inactive';
            fputcsv($output, [
                $user['Catalog_category']['id'],
                $user['Catalog_category']['name'],
                $status,
                $user['Catalog_category']['created']
            ]);
        }

        fclose($output);
        exit;
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

        $authUser       = $this->Session->read('Auth.User');
        $userRole       = $authUser['role'] ?? '';
        $userLocationId = $authUser['location_ids'] ?? 0;

        $selectedStoreId = 0;
        if (!empty($this->request->data['Search']['cashier'])) {
            $selectedStoreId = intval($this->request->data['Search']['cashier']);
        } elseif (!empty($this->request->data['cashier'])) {
            $selectedStoreId = intval($this->request->data['cashier']);
        } elseif (!empty($this->request->query['cashier'])) {
            $selectedStoreId = intval($this->request->query['cashier']);
        }

        $location_id = $selectedStoreId > 0 ? $selectedStoreId : null;
        $shiftRange = $this->getShiftDateRange($startdate, $enddate, $location_id);
        $startDatetime = $shiftRange['startDatetime'];
        $endDatetime   = $shiftRange['endDatetime'];
        $daterangeValue = date('m/d/Y', strtotime($startdate)) . ' - ' . date('m/d/Y', strtotime($enddate));

        $this->loadModel('Location');
        $this->loadModel('User');
        $this->loadModel('Order_transaction');
        $this->loadModel('Order_orderhistory');
        $this->loadModel('Order_payment');
        $this->loadModel('Order_tax');

        $conditions = ['status' => 1, 'type' => 'Outlet'];
        if ($userRole !== 'admin' && !empty($userLocationId)) {
            $userLocationIds = Hash::extract(
                ['ids' => is_array($userLocationId) ? $userLocationId : explode(',', $userLocationId)],
                'ids.{n}'
            );
            $conditions['id IN'] = array_map('trim', array_filter($userLocationIds));
        }
        $users = $this->Location->find('all', ['conditions' => $conditions, 'order' => ['name' => 'ASC']]);

        $selectedStoreId = 0;
        if (!empty($this->request->data['Search']['cashier'])) {
            $selectedStoreId = intval($this->request->data['Search']['cashier']);
        } elseif (!empty($this->request->data['cashier'])) {
            $selectedStoreId = intval($this->request->data['cashier']);
        } elseif (!empty($this->request->query['cashier'])) {
            $selectedStoreId = intval($this->request->query['cashier']);
        }

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
        } elseif ($userRole !== 'admin' && !empty($userLocationId)) {
            $locIdsStr = is_array($userLocationId) ? implode(',', $userLocationId) : $userLocationId;
            if (!empty($locIdsStr)) {
                $storeCondition         = " AND ot.store_id IN ($locIdsStr) ";
                $storeConditionOt2      = " AND ot2.store_id IN ($locIdsStr) ";
                $storeConditionTax      = " AND tax.store_id IN ($locIdsStr) ";
                $storeConditionOrderTax = " AND order_tax.store_id IN ($locIdsStr) ";
                $storeConditionPlain    = " AND store_id IN ($locIdsStr) ";
                $oohFilter              = " AND ooh.store_id IN ($locIdsStr) ";
                $otTaxFilter            = " AND ot_tax.store_id IN ($locIdsStr) ";
            }
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

        $storeConditionOp = "";
        if ($selectedStoreId > 0) {
            $storeConditionOp = " AND op.store_id = $selectedStoreId ";
        } elseif ($userRole !== 'admin' && !empty($userLocationId)) {
            $locIdsStr = is_array($userLocationId) ? implode(',', $userLocationId) : $userLocationId;
            if (!empty($locIdsStr)) {
                $storeConditionOp = " AND op.store_id IN ($locIdsStr) ";
            }
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

    public function payment_partner_change_status() {
        $this->autoRender = false;

        if ($this->request->is('post')) {
            $Id = isset($this->request->data['_id']) ? $this->request->data['_id'] : null;
            if (!empty($Id)) {
                $this->loadModel('PaymentPartner');
                $this->PaymentPartner->id = $Id;
                $currentStatus = $this->PaymentPartner->field('status');
                $newStatus = ($currentStatus == 1) ? 0 : 1;

                if ($this->PaymentPartner->save(['status' => $newStatus, 'sync' => 0, 'local_sync' => 0])) {
                    echo json_encode(['status' => true]);
                    return;
                }
            }
            echo json_encode(['status' => false]);
        }
    }

    public function create_payment_partner() {
        if(!$this->Session->check('Auth.User.id')){
            return $this->redirect(array('controller'=>'dashboard','action' => 'login'));
        }

        $this->layout = "users";
        $this->loadModel('PaymentPartner');

        if ($this->request->is(array('post', 'put'))) {
            $name = isset($this->request->data['PaymentPartner']['name']) ? trim($this->request->data['PaymentPartner']['name']) : '';
            if (empty($name)) {
                $this->Flash->error(__('Payment partner name cannot be blank.'));
                return $this->redirect(array('action' => 'create_payment_partner'));
            }

            $existing = $this->PaymentPartner->find('first', array('conditions' => array('PaymentPartner.name' => $name)));
            if ($existing) {
                $this->Flash->error(__('Payment partner name already exists.'));
                return $this->redirect(array('action' => 'create_payment_partner'));
            }

            $saveData = array(
                'name' => $name,
                'status' => 1,
                'sync' => 0,
                'local_sync' => 0
            );

            $this->PaymentPartner->create();
            if ($this->PaymentPartner->save($saveData)) {
                $this->Flash->success(__('Payment partner created successfully.'));
                return $this->redirect(array('action' => 'payment_partner_list'));
            } else {
                $this->Flash->error(__('Unable to save payment partner. Please try again.'));
            }
        }
    }

    public function payment_partner_list() {
        if(!$this->Session->check('Auth.User.id')){
            return $this->redirect(array('controller'=>'dashboard','action' => 'login'));
        }
        $this->layout = "users";
        $this->loadModel('PaymentPartner');

        $partners = $this->PaymentPartner->find('all', array(
            'order' => array('PaymentPartner.id' => 'DESC')
        ));

        $this->set('partners', $partners);
    }

    public function editing_management()
    {
        if (!$this->Session->check('Auth.User.id')) {
            return $this->redirect(array('controller' => 'dashboard', 'action' => 'login'));
        }

        $this->layout = "users";
        $this->loadModel('Editing_management');
        $this->loadModel('Location');

        $authUser = $this->Session->read('Auth.User');
        $userRole = $authUser['role'] ?? '';
        $userLocationId = $authUser['location_ids'] ?? '';

        $startDate = date('m/d/Y', strtotime('-30 days'));
        $endDate = date('m/d/Y');
        $selectedStore = '';
        $selectedStatus = '';

        if ($this->request->is(array('post', 'put'))) {
            if (!empty($this->request->data['daterange'])) {
                $daterange = $this->request->data['daterange'];
                $dates = explode(" - ", $daterange);
                if (count($dates) == 2) {
                    $startDate = date('m/d/Y', strtotime(trim($dates[0])));
                    $endDate = date('m/d/Y', strtotime(trim($dates[1])));
                }
            }
            if (isset($this->request->data['store_id'])) {
                $selectedStore = $this->request->data['store_id'];
            }
            if (isset($this->request->data['status'])) {
                $selectedStatus = $this->request->data['status'];
            }
        } elseif (!empty($this->request->query('daterange'))) {
            $daterange = $this->request->query('daterange');
            $dates = explode(" - ", $daterange);
            if (count($dates) == 2) {
                $startDate = date('m/d/Y', strtotime(trim($dates[0])));
                $endDate = date('m/d/Y', strtotime(trim($dates[1])));
            }
            if (isset($this->request->query['store_id'])) {
                $selectedStore = $this->request->query['store_id'];
            }
            if (isset($this->request->query['status'])) {
                $selectedStatus = $this->request->query['status'];
            }
        }

        $startdate = date('Y-m-d', strtotime($startDate));
        $enddate   = date('Y-m-d', strtotime($endDate));

        $_shiftRange   = $this->getShiftDateRange($startdate, $enddate);
        $startDatetime = $_shiftRange['startDatetime'];
        $endDatetime   = $_shiftRange['endDatetime'];

        $conditions = array(
            'Editing_management.created BETWEEN ? AND ?' => array($startDatetime, $endDatetime)
        );

        if ($userRole !== 'admin' && !empty($userLocationId)) {
            $allowedLocationIds = array_filter(array_map('trim', explode(',', $userLocationId)));
            if (!empty($allowedLocationIds)) {
                $conditions['Editing_management.store_id IN'] = $allowedLocationIds;
            }
        }

        if ($selectedStore !== '' && $selectedStore !== null) {
            $conditions['Editing_management.store_id'] = $selectedStore;
        }

        if ($selectedStatus !== '' && $selectedStatus !== null) {
            $conditions['Editing_management.status'] = (int)$selectedStatus;
        }

        $requests = $this->Editing_management->find('all', array(
            'conditions' => $conditions,
            'order' => array('Editing_management.id DESC')
        ));

        $locConditions = array('Location.status' => 1);
        if ($userRole !== 'admin' && !empty($userLocationId)) {
            $allowedLocationIds = array_filter(array_map('trim', explode(',', $userLocationId)));
            if (!empty($allowedLocationIds)) {
                $locConditions['Location.id IN'] = $allowedLocationIds;
            }
        }
        $locations = $this->Location->find('all', array(
            'conditions' => $locConditions,
            'order' => array('Location.name ASC')
        ));

        $yourStartDateValue = $startDate . ' - ' . $endDate;
        $this->set('startdate', $yourStartDateValue);
        $this->set(compact('requests', 'locations', 'selectedStore', 'selectedStatus', 'userRole'));
    }

    public function editing_management_update_status()
    {
        $this->autoRender = false;

        if (!$this->Session->check('Auth.User.id')) {
            echo json_encode(array('status' => false, 'message' => 'Unauthorized session'));
            return;
        }

        if ($this->request->is('post')) {
            $this->loadModel('Editing_management');

            $id = $this->request->data['id'] ?? $this->request->data['_id'] ?? '';
            $status = $this->request->data['status'] ?? '';
            $remarks = $this->request->data['remarks'] ?? '';

            if (empty($id) || empty($status)) {
                echo json_encode(array('status' => false, 'message' => 'Invalid parameters'));
                return;
            }

            $currentUserId = $this->Session->read('Auth.User.id');
            $currentUserName = $this->Session->read('Auth.User.name') ?? $this->Session->read('Auth.User.username') ?? 'Admin';

            $record = $this->Editing_management->find('first', array(
                'conditions' => array('Editing_management.id' => $id)
            ));

            if (empty($record)) {
                echo json_encode(array('status' => false, 'message' => 'Record not found'));
                return;
            }

            $updateData = array(
                'id'             => $id,
                'status'         => (int)$status,
                'action_by'      => $currentUserId,
                'action_by_name' => $currentUserName,
                'action_date'    => date('Y-m-d H:i:s'),
                'modified'       => date('Y-m-d H:i:s'),
                'sync'           => 0,
                'local_sync'     => 0
            );

            if ($this->Editing_management->save($updateData)) {
                $statusLabel = ((int)$status === 2) ? 'Approved' : 'Rejected';
                echo json_encode(array(
                    'status'  => true,
                    'message' => "Request has been {$statusLabel} successfully."
                ));
            } else {
                echo json_encode(array('status' => false, 'message' => 'Failed to update status'));
            }
        }
    }

    public function editing_management_export()
    {
        if (!$this->Session->check('Auth.User.id')) {
            return $this->redirect(array('controller' => 'dashboard', 'action' => 'login'));
        }

        $this->autoRender = false;
        $this->loadModel('Editing_management');

        $authUser = $this->Session->read('Auth.User');
        $userRole = $authUser['role'] ?? '';
        $userLocationId = $authUser['location_ids'] ?? '';

        $sdate = $this->request->query('sdate') ?? '';
        $selectedStore = $this->request->query('store_id') ?? '';
        $selectedStatus = $this->request->query('status') ?? '';

        if (!empty($sdate)) {
            $dates = explode(" - ", $sdate);
            $start_date = trim($dates[0]);
            $end_date = trim($dates[1]);
            $startdate = date('Y-m-d', strtotime($start_date));
            $enddate = date('Y-m-d', strtotime($end_date));
        } else {
            $startdate = date('Y-m-d', strtotime('-30 days'));
            $enddate = date('Y-m-d');
        }

        $_shiftRange   = $this->getShiftDateRange($startdate, $enddate);
        $startDatetime = $_shiftRange['startDatetime'];
        $endDatetime   = $_shiftRange['endDatetime'];

        $conditions = array(
            'Editing_management.created BETWEEN ? AND ?' => array($startDatetime, $endDatetime)
        );

        if ($userRole !== 'admin' && !empty($userLocationId)) {
            $allowedLocationIds = array_filter(array_map('trim', explode(',', $userLocationId)));
            if (!empty($allowedLocationIds)) {
                $conditions['Editing_management.store_id IN'] = $allowedLocationIds;
            }
        }

        if ($selectedStore !== '' && $selectedStore !== null) {
            $conditions['Editing_management.store_id'] = $selectedStore;
        }

        if ($selectedStatus !== '' && $selectedStatus !== null) {
            $conditions['Editing_management.status'] = (int)$selectedStatus;
        }

        $requests = $this->Editing_management->find('all', array(
            'conditions' => $conditions,
            'order' => array('Editing_management.id DESC')
        ));

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="editing_management_' . $startdate . '_to_' . $enddate . '.csv"');

        $output = fopen('php://output', 'w');
        fputcsv($output, array('ID', 'Reference ID', 'Store ID', 'Store Name', 'Cashier ID', 'Cashier Name', 'Remarks', 'Status', 'Action By', 'Action Date', 'Created Date'));
        $i=1;
        foreach ($requests as $r) {
            $statusNum = (int)$r['Editing_management']['status'];
            $statusText = 'Pending';
            if ($statusNum === 2) $statusText = 'Approved';
            elseif ($statusNum === 3) $statusText = 'Rejected';

            fputcsv($output, array(
                $i,
                $r['Editing_management']['reference_id'],
                $r['Editing_management']['store_id'],
                $r['Editing_management']['store_name'],
                $r['Editing_management']['cashier_id'],
                $r['Editing_management']['cashier_name'],
                $r['Editing_management']['remarks'],
                $statusText,
                $r['Editing_management']['action_by_name'] ?? '-',
                $r['Editing_management']['action_date'] ?? '-',
                $r['Editing_management']['created']
            ));
            $i++;
        }

        fclose($output);
        exit;
    }
}