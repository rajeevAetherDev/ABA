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
class ADSRController extends AppController {
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
    
    public function data()
    {
        $this->autoRender = false;
        
        header('Content-Type: application/json');
    
        /* ===================== SIMPLE TOKEN AUTH ===================== */
    
        $VALID_TOKEN = '8f92c1d4a7b6e3f5092d7a6b1c4e9';

        $headers = getallheaders();
        
        $token = $headers['X-API-TOKEN']
            ?? $_SERVER['HTTP_X_API_TOKEN']
            ?? '';
        
        if (empty($token)) {
            echo json_encode([
                'status' => false,
                'message' => 'API token missing'
            ]);
            exit;
        }
        
        if ($token !== $VALID_TOKEN) {
            echo json_encode([
                'status' => false,
                'message' => 'Invalid API token'
            ]);
            exit;
        }
        $cashierid = $_REQUEST['id'] ?? '';
        $startdate = $_REQUEST['from'] ?? date("Y-m-d");
        $enddate   = $_REQUEST['to'] ?? date("Y-m-d");
    
        // Shift based datetime
        $startDatetime = $startdate . ' ' . shiftstart;
        $endDatetime   = date('Y-m-d', strtotime($enddate . ' +1 day')) . ' ' . shiftend;
    
        if (empty($cashierid)) {
            echo json_encode([
                'status'  => false,
                'message' => 'Location Id Missing'
            ]);
            return;
        }
        
        if ($cashierid!=2) {
            echo json_encode([
                'status'  => false,
                'message' => 'Location Id Invalid'
            ]);
            return;
        }
    
        $users = $this->Location->find('first', [
            'conditions' => [
                'Location.id'     => $cashierid,
                'Location.status' => 1
            ]
        ]);
    
        if (empty($users)) {
            echo json_encode([
                'status'  => false,
                'message' => 'Location not found'
            ]);
            return;
        }
    
        $location_name   = $users['Location']['name'];
        $transactionitems = [];
    
        /* ===================== SPLIT TRANSACTIONS ===================== */
    
        $splittransactions = $this->Order_split_transaction->find('all', [
            'conditions' => [
                'Order_split_transaction.status'   => 2,
                'Order_split_transaction.store_id' => $cashierid,
                'Order_split_transaction.split_reference_id NOT IN (
                    SELECT split_reference_id 
                    FROM order_split_payments 
                    WHERE type = 7
                )',
                'Order_split_transaction.created BETWEEN ? AND ?' => [
                    $startDatetime,
                    $endDatetime
                ]
            ],
            'order' => ['Order_split_transaction.id' => 'DESC']
        ]);
    
        foreach ($splittransactions as $orders) {
    
            $ost = $orders['Order_split_transaction'];
    
            $reference_id       = $ost['reference_id'];
            $split_reference_id = $ost['split_reference_id'];
            $store_id           = $ost['store_id'];
    
            /* Payment Mode */
            $payment_mode = '';
            $pay_mode = $this->Order_split_payment->query("
                SELECT GROUP_CONCAT(type_value) AS payment
                FROM order_split_payments
                WHERE reference_id = '$reference_id'
                  AND split_reference_id = '$split_reference_id'
                  AND store_id = '$store_id'
                  AND status = 2
            ");
    
            if (!empty($pay_mode[0][0]['payment'])) {
                $payment_mode = $pay_mode[0][0]['payment'];
            }
    
            /* Total Quantity */
            $orderss = $this->Order_split_orderhistory->query("
                SELECT SUM(quantity) AS total_qty
                FROM order_split_orderhistories
                WHERE reference_id = '$reference_id'
                  AND split_reference_id = '$split_reference_id'
                  AND store_id = '$store_id'
                  AND status != 0
            ");
    
            $totalQty = $orderss[0][0]['total_qty'] ?? 0;
    
            $transactionitems[] = [
                'RECEIPT_NO'    => $ost['orderno'],
                'TIMESTAMP'     => $ost['created'],
                'RECEIPT_TYPE'  => ($ost['status'] == 0) ? 'Cancel' : 'Sales',
                'INVOICE_AMT'   => $ost['amount'],
                'TAX_AMT'       => $ost['total_tax'],
                'DISCOUNT_AMT'  => $ost['discount'],
                'NET_AMT'       => $ost['price'],
                'RETURN_AMT'    => '0',
                'RETURN_TAX'    => '0',
                'TOTAL_QTY'     => $totalQty,
                'PAYMENT_MODE'  => $payment_mode,
            ];
        }
    
        /* ===================== NORMAL TRANSACTIONS ===================== */
    
        $transactions = $this->Order_transaction->find('all', [
            'conditions' => [
                'Order_transaction.status'   => 2,
                'Order_transaction.is_split' => 0,
                'Order_transaction.store_id' => $cashierid,
                'Order_transaction.reference_id NOT IN (
                    SELECT reference_id 
                    FROM order_payments 
                    WHERE type = 7
                )',
                'Order_transaction.created BETWEEN ? AND ?' => [
                    $startDatetime,
                    $endDatetime
                ]
            ],
            'order' => ['Order_transaction.id' => 'DESC']
        ]);
    
        foreach ($transactions as $orders) {
    
            $ot = $orders['Order_transaction'];
    
            $reference_id = $ot['reference_id'];
            $store_id     = $ot['store_id'];
    
            /* Payment Mode */
            $payment_mode = '';
            $pay_mode = $this->Order_payment->query("
                SELECT GROUP_CONCAT(type_value) AS payment
                FROM order_payments
                WHERE reference_id = '$reference_id'
                  AND store_id = '$store_id'
                  AND status = 2
            ");
    
            if (!empty($pay_mode[0][0]['payment'])) {
                $payment_mode = $pay_mode[0][0]['payment'];
            }
    
            /* Total Quantity */
            $orderss = $this->Order_orderhistory->query("
                SELECT SUM(quantity) AS total_qty
                FROM order_orderhistories
                WHERE reference_id = '$reference_id'
                  AND store_id = '$store_id'
                  AND status != 0
            ");
    
            $totalQty = $orderss[0][0]['total_qty'] ?? 0;
    
            $transactionitems[] = [
                'RECEIPT_NO'    => $ot['orderno'],
                'TIMESTAMP'     => $ot['created'],
                'RECEIPT_TYPE'  => ($ot['status'] == 0) ? 'Cancel' : 'Sales',
                'INVOICE_AMT'   => $ot['amount'],
                'TAX_AMT'       => $ot['total_tax'],
                'DISCOUNT_AMT'  => $ot['discount'],
                'NET_AMT'       => $ot['price'],
                'RETURN_AMT'    => '0',
                'RETURN_TAX'    => '0',
                'TOTAL_QTY'     => $totalQty,
                'PAYMENT_MODE'  => $payment_mode,
            ];
        }
    
        /* ===================== SORT BY RECEIPT NUMBER ===================== */
        usort($transactionitems, function ($a, $b) {
    
            preg_match('/(\d+)$/', $a['RECEIPT_NO'], $ma);
            preg_match('/(\d+)$/', $b['RECEIPT_NO'], $mb);
    
            $numA = isset($ma[1]) ? (int)$ma[1] : 0;
            $numB = isset($mb[1]) ? (int)$mb[1] : 0;
    
            return $numB <=> $numA;
        });
    
        echo json_encode([
            'status'       => true,
            'message'      => 'success',
            'location_name' => $location_name,
            'transactions' => $transactionitems
        ]);
    }


}

