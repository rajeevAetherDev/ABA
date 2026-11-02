<?php
App::uses('AppModel', 'Model');
/**
 * Deliveryordertrack Model
 *
 * @property Customer $Customer
 * @property Vendor $Vendor
 */
class Order_customers extends AppModel {

public $useDbConfig = 'default';

public $useTable = 'order_customers';


}
