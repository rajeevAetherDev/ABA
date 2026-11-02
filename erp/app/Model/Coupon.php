<?php
App::uses('AppModel', 'Model');
//App::uses('BlowfishPasswordHasher', 'Controller/Component/Auth');
/**
 * User Model
 *
 * @property Foodcourt $Foodcourt
 */
class Coupon extends AppModel {

public $useDbConfig = 'default';

public $useTable = 'coupons';

}
