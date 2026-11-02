<?php
/**
 * Application level Controller
 *
 * This file is application-wide controller file. You can put all
 * application-wide controller-related methods here.
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       app.Controller
 * @since         CakePHP(tm) v 0.2.9
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

App::uses('Controller', 'Controller');

/**
 * Application Controller
 *
 * Add your application-wide methods in the class below, your controllers
 * will inherit them.
 *
 * @package     app.Controller
 * @link        http://book.cakephp.org/2.0/en/controllers.html#the-app-controller
 */
class AppController extends Controller {
public $uses = array('Inventory_return','Inventory_return_items','Inventory_stockin','Inventory_stockin_item','Sync_error','Order_orderhistory_json','Offer','Order_split_orderhistory','Order_split_transaction','Order_split_tax','Order_split_payment','Account_imprest','Catalog_product_branches','Catalog_category_branches','User_location','Catalog_unit_mapping','Inventory_linking','Kds_track','Kds_users_station_mapping','Kds_user','Inventory_semifinished_semifinished_linking','Inventory_damage_item','Inventory_damage','Inventory_indent_items','Inventory_indent','Inventory_subcategory','Inventory_category','Catalog_product_attribute','Order_customers','Order_modifier','Inventory_modifier_semifinished_linking','Inventory_modifier_sku_linking','Catalog_product_modifier_group_linkings','Catalog_modifier_group_linkings','Catalog_modifier_group','Catalog_modifier','Online_order','Temp_audit_stock','Temp_audit_stock_items','Audit_stock','Audit_stock_items','Stock_ledger','Catalog_package','Order_table_tracking','Stock_transfer','Stock_transfer_item','User_track','Order_waiter','Inventory_semifinished_stock','Inventory_sku_stock','Inventory_purchase_order','Inventory_purchase_order_item','Inventory_internal_indent_items','Inventory_internal_indent','Department','User','Card','Catalog_unit','Inventory_unit','Cardtracking','User_module','Catalog_category','Catalog_subcategory','Catalog_tax','Catalog_product','Catalog_product_addon','Order_orderhistory','Order_table','Order_transaction','Catalog_product_tax','Order_payment','Order_tax','Coupon','Location','Pos_printer','Online_booking','Inventory_sku','Inventory_tax','Inventory_sku_tax','Inventory_vendor','Inventory_vendor_sku_served','Inventory_vendor_service_area','Inventory_semifinished','Inventory_semifinished_tax','Inventory_semifinished_sku_linking','Inventory_product_semifinished_linking','Inventory_product_sku_linking','Catalog_product_tax_outlet','PaymentPartner','Table_category');
public $helpers = array('Form', 'Html', 'Js', 'Time','Cache','Session', 'Flash', 'Time');
public $components = array('Auth', 'Session','Cookie','RequestHandler','DebugKit.Toolbar', 'Flash');

//    public function isAuthorized($user) {
//        // Admin can access every action
//        if (isset($user['role']) && $user['role'] === 'admin') {
//            return true;
//        }
//
//
//        // Default deny
//        return false;
//    }
function isAuthorized() {
    if (!empty($this->params['prefix']) && $this->params['prefix'] == 'admin') {
        if ($this->Auth->user('role') != 'admin') {
            return false;
        }
    }
    return true;
}
    public function beforeFilter() {
        $this->Auth->allow('login');
        
        
         if ($this->request->controller == 'dashboard') {
            AuthComponent::$sessionKey = 'Auth.User'; 
            $this->Auth->loginAction = array( 'controller' => 'dashboard', 'action' => 'login' );
            $this->Auth->loginRedirect = array('controller' => 'dashboard', 'action' => 'home');
            $this->Auth->logoutRedirect = array( 'controller' => 'dashboard', 'action' => 'login' );
            //,'passwordHasher' => 'Blowfish' 
            $this->Auth->authenticate = array('Form' => array( 'userModel' => 'User', 'scope' => array('User.status' =>1) ));        
            $this->Auth->authorize = 'Controller';
        } 
        
        else {
            //$this->Auth->allow();           
        }
        
        
        
        
        
            // if($this->Session->check('Auth.User')){
            //     if (isset($this->params['prefix']) && $this->params['prefix'] == 'admin' && $this->Session->read('Auth.User.role')!= "admin" ) {
            //         $this->redirect(array('controller'=>'dashboard', 'action'=>'index',  'admin'=>FALSE ));
            //     }
            //     if (isset($this->params['prefix']) && $this->params['prefix'] == 'manager' && $this->Session->read('Auth.User.role')!= "manager" ) {
            //         $this->redirect(array('controller'=>'dashboard', 'action'=>'index',  'admin'=>TRUE ));
            //     }
            //     if (!isset($this->params['prefix']) && $this->Session->read('Auth.User.role')== "admin" )             {
            //         $this->redirect(array('controller'=>'dashboard', 'action'=>'index',  'admin'=>TRUE ));
            //     }else {
            //         return true;
                   
            //     }
            // }
    }
    
    public function trackUser($ref_id,$users, $type, $remarks = [])
    {
        $this->loadModel('User_track');
    
        $this->User_track->create();
        $this->User_track->save([
            'reference_id'  => $ref_id ?? 0,
            'user_id'       => $users['User']['id'] ?? 0,
            'location_id'   => $users['User']['location_id'] ?? 0,
            'location_name' => $users['User']['location_name'] ?? '',
            'type'          => $type,
            'remarks'       => json_encode($remarks)
        ]);
    }

    /**
     * Get Location Configuration (Shift Start, Shift End, Shift Type, Service Charge Rate)
     * 
     * Rules:
     * - If $location_id is empty, null, array, or not a single numeric ID (All Stores mode):
     *   Shift times fall back to core.php config constants (shiftstart = " 06:00:00", shiftend = " 05:59:59").
     * - If $location_id is a single valid location ID:
     *   Shift times come from locations table (shift_start_time, shift_end_time, shift_type).
     * - Service charge rate ALWAYS comes from locations table when location_id is provided, else fallback to location setting or default.
     * 
     * @param mixed $location_id
     * @return array
     */
    public function getLocationConfig($location_id = null) {
        $defaultShiftStart = defined('shiftstart') ? trim(shiftstart) : '';
        $defaultShiftEnd   = defined('shiftend') ? trim(shiftend) : '';
        $defaultServiceCharge = defined('servicecharge') ? (float)servicecharge : 0;
        
        $config = [
            'shift_start_time'    => $defaultShiftStart,
            'shift_end_time'      => $defaultShiftEnd,
            'shift_type'          => 'next_day',
            'service_charge_rate' => $defaultServiceCharge,
            'is_single_store'     => false
        ];

        // Check if location_id is empty or array or multiple stores
        if (empty($location_id) || is_array($location_id) || !is_numeric($location_id) || (int)$location_id <= 0) {
            // Check if logged-in user is an outlet user bound to a single location_id in Session
            if (isset($this->Session) && $this->Session->check('Auth.User.location_id')) {
                $sessLoc = $this->Session->read('Auth.User.location_id');
                if (!empty($sessLoc) && is_numeric($sessLoc) && (int)$sessLoc > 0) {
                    $location_id = (int)$sessLoc;
                } else {
                    return $config;
                }
            } else {
                return $config;
            }
        }

        $location_id = (int)$location_id;
        $config['is_single_store'] = true;

        static $locCache = [];
        if (isset($locCache[$location_id])) {
            return $locCache[$location_id];
        }

        if (!isset($this->Location)) {
            $this->loadModel('Location');
        }

        $loc = $this->Location->find('first', [
            'conditions' => ['Location.id' => $location_id],
            'fields' => ['id', 'shift_start_time', 'shift_end_time', 'shift_type', 'service_charge_rate']
        ]);

        if (!empty($loc['Location'])) {
            if (!empty($loc['Location']['shift_start_time'])) {
                $config['shift_start_time'] = trim($loc['Location']['shift_start_time']);
            }
            if (!empty($loc['Location']['shift_end_time'])) {
                $config['shift_end_time'] = trim($loc['Location']['shift_end_time']);
            }
            if (!empty($loc['Location']['shift_type'])) {
                $config['shift_type'] = trim($loc['Location']['shift_type']);
            } else {
                $todayDate = date('Y-m-d');
                $startSec = strtotime($todayDate . ' ' . $config['shift_start_time']);
                $endSec   = strtotime($todayDate . ' ' . $config['shift_end_time']);
                $config['shift_type'] = ($endSec <= $startSec) ? 'next_day' : 'same_day';
            }
            if (isset($loc['Location']['service_charge_rate']) && $loc['Location']['service_charge_rate'] !== null && $loc['Location']['service_charge_rate'] !== '') {
                $config['service_charge_rate'] = (float)$loc['Location']['service_charge_rate'];
            }
        }

        $locCache[$location_id] = $config;
        return $config;
    }

    /**
     * Get Service Charge Rate from locations table for a given location_id.
     * @param int|null $location_id
     * @return float
     */
    public function getLocationServiceChargeRate($location_id = null) {
        $config = $this->getLocationConfig($location_id);
        return $config['service_charge_rate'];
    }

    /**
     * Resolve single location_id from transaction reference_id, user_id, or explicit store_id.
     * @param mixed $txnid
     * @param mixed $user_id
     * @param mixed $store_id
     * @return int|null
     */
    public function resolveLocationIdForTxn($txnid = null, $user_id = null, $store_id = null) {
        if (!empty($store_id) && is_numeric($store_id) && (int)$store_id > 0) {
            return (int)$store_id;
        }
        if (!empty($user_id) && is_numeric($user_id) && (int)$user_id > 0) {
            if (!isset($this->User)) {
                $this->loadModel('User');
            }
            $u = $this->User->find('first', [
                'conditions' => ['User.id' => (int)$user_id],
                'fields' => ['location_id'],
                'recursive' => -1
            ]);
            if (!empty($u['User']['location_id'])) {
                return (int)$u['User']['location_id'];
            }
        }
        if (!empty($txnid)) {
            $firstRef = is_array($txnid) ? reset($txnid) : $txnid;
            if (!isset($this->Order_table)) {
                $this->loadModel('Order_table');
            }
            $ot = $this->Order_table->find('first', [
                'conditions' => ['Order_table.reference_id' => $firstRef],
                'fields' => ['location_id'],
                'recursive' => -1
            ]);
            if (!empty($ot['Order_table']['location_id'])) {
                return (int)$ot['Order_table']['location_id'];
            }
            if (!isset($this->Order_transaction)) {
                $this->loadModel('Order_transaction');
            }
            $tr = $this->Order_transaction->find('first', [
                'conditions' => ['Order_transaction.reference_id' => $firstRef],
                'fields' => ['store_id'],
                'recursive' => -1
            ]);
            if (!empty($tr['Order_transaction']['store_id'])) {
                return (int)$tr['Order_transaction']['store_id'];
            }
            if (!isset($this->Order_orderhistory)) {
                $this->loadModel('Order_orderhistory');
            }
            $oh = $this->Order_orderhistory->find('first', [
                'conditions' => ['Order_orderhistory.reference_id' => $firstRef],
                'fields' => ['store_id'],
                'recursive' => -1
            ]);
            if (!empty($oh['Order_orderhistory']['store_id'])) {
                return (int)$oh['Order_orderhistory']['store_id'];
            }
        }
        return null;
    }

    /**
     * Helper to build shift start and end datetimes for date range queries.
     * Automatically attempts to resolve location_id from parameter, request, or session.
     * 
     * @param string $startdate Format Y-m-d
     * @param string $enddate Format Y-m-d
     * @param mixed $location_id Location ID or null/array
     * @return array
     */
    public function getShiftDateRange($startdate, $enddate, $location_id = null) {
        if (empty($location_id)) {
            if (!empty($this->request->data['Search']['store']) && is_numeric($this->request->data['Search']['store'])) {
                $location_id = $this->request->data['Search']['store'];
            } elseif (!empty($this->request->data['Search']['cashier']) && is_numeric($this->request->data['Search']['cashier'])) {
                $location_id = $this->request->data['Search']['cashier'];
            } elseif (!empty($this->request->data['Search']['location_id']) && is_numeric($this->request->data['Search']['location_id'])) {
                $location_id = $this->request->data['Search']['location_id'];
            } elseif (!empty($this->request->data['Search']['location']) && is_numeric($this->request->data['Search']['location'])) {
                $location_id = $this->request->data['Search']['location'];
            } elseif (!empty($this->request->data['store_id']) && is_numeric($this->request->data['store_id'])) {
                $location_id = $this->request->data['store_id'];
            } elseif (!empty($this->request->data['store']) && is_numeric($this->request->data['store'])) {
                $location_id = $this->request->data['store'];
            } elseif (!empty($this->request->data['location_id']) && is_numeric($this->request->data['location_id'])) {
                $location_id = $this->request->data['location_id'];
            } elseif (!empty($this->request->query['store_id']) && is_numeric($this->request->query['store_id'])) {
                $location_id = $this->request->query['store_id'];
            } elseif (!empty($this->request->query['store']) && is_numeric($this->request->query['store'])) {
                $location_id = $this->request->query['store'];
            } elseif (!empty($this->request->query['location_id']) && is_numeric($this->request->query['location_id'])) {
                $location_id = $this->request->query['location_id'];
            } elseif (!empty($_GET['store_id']) && is_numeric($_GET['store_id'])) {
                $location_id = $_GET['store_id'];
            } elseif (!empty($_GET['store']) && is_numeric($_GET['store'])) {
                $location_id = $_GET['store'];
            } elseif (!empty($_GET['location_id']) && is_numeric($_GET['location_id'])) {
                $location_id = $_GET['location_id'];
            } elseif (!empty($this->request->data['cashier']) && is_numeric($this->request->data['cashier'])) {
                $location_id = $this->request->data['cashier'];
            } elseif (!empty($this->request->query['cashier']) && is_numeric($this->request->query['cashier'])) {
                $location_id = $this->request->query['cashier'];
            } elseif (!empty($_GET['cashier']) && is_numeric($_GET['cashier'])) {
                $location_id = $_GET['cashier'];
            } elseif (!empty($this->request->data['StockLevel']['cashier']) && is_numeric($this->request->data['StockLevel']['cashier'])) {
                $location_id = $this->request->data['StockLevel']['cashier'];
            }
        }

        $config = $this->getLocationConfig($location_id);
        
        $startDatetime = $startdate . ' ' . $config['shift_start_time'];
        if ($config['shift_type'] === 'same_day') {
            $endDatetime = $enddate . ' ' . $config['shift_end_time'];
        } else {
            $endDatetime = date('Y-m-d', strtotime($enddate . ' +1 day')) . ' ' . $config['shift_end_time'];
        }
        
        return [
            'startDatetime'       => $startDatetime,
            'endDatetime'         => $endDatetime,
            'shift_start_time'    => $config['shift_start_time'],
            'shift_end_time'      => $config['shift_end_time'],
            'shift_type'          => $config['shift_type'],
            'service_charge_rate' => $config['service_charge_rate'],
            'is_single_store'     => $config['is_single_store']
        ];
    }

    /**
     * Get active store location IDs array dynamically from database.
     * @return array
     */
    public function getSyncStoreIds() {
        if (!isset($this->Location)) {
            $this->loadModel('Location');
        }
        static $cachedStoreIds = null;
        if ($cachedStoreIds !== null) {
            return $cachedStoreIds;
        }
        $locations = $this->Location->find('list', [
            'fields' => ['id', 'id'],
            'conditions' => ['status' => 1]
        ]);
        $cachedStoreIds = !empty($locations) ? array_values(array_map('intval', $locations)) : [1];
        return $cachedStoreIds;
    }

    /**
     * Get primary active store location ID dynamically from database.
     * @return int
     */
    public function getSyncStoreIdVal() {
        $ids = $this->getSyncStoreIds();
        return !empty($ids) ? (int)$ids[0] : 1;
    }
}

