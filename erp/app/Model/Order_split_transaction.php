<?php
App::uses('AppModel', 'Model');
/**
 * Deliveryordertrack Model
 *
 * @property Customer $Customer
 * @property Vendor $Vendor
 */
class Order_split_transaction extends AppModel {

public $useDbConfig = 'default';

public $useTable = 'order_split_transactions';


}
