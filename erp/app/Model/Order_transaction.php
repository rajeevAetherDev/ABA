<?php
App::import('User','Model');
App::uses('User', 'Model');
// App::uses('AppModel', 'Model');
/**
 * Deliveryordertrack Model
 *
 * @property Customer $Customer
 * @property Vendor $Vendor
 */
class Order_transaction extends AppModel {

public $useDbConfig = 'default';

public $useTable = 'order_transactions';

public function beforeSave($options = array()) {
    
$User = new User();

	$user_id=$this->data[$this->alias]['cashier_id'];
	$series_id=sprintf("%02d", $user_id);
	$users=$User->find('first',array('conditions'=>array('id'=>$user_id)));
   	$series=strtoupper(substr($users['User']['username'],0,2)); 
    
     $orderTrans = $this->find('first', array('order'=>'Order_transaction.id DESC'));
     if($orderTrans['Order_transaction']['split_count']>1){
         $split_count=$orderTrans['Order_transaction']['split_count']-1;
     }else{
         $split_count=0;
     }
        $newstring = substr($orderTrans['Order_transaction']['orderno'], -8)  + $split_count + 1;
		$GOrder = sprintf("%08d", $newstring);

        $orderNumber = $series_id."".$series.date('Y').$GOrder;   
        //$orderNumber = $series.date('Y').$GOrder;   
            $this->data[$this->alias]['orderno'] = $orderNumber;
            return true;
        }  

}
