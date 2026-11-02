<?php
App::uses('AppModel', 'Model');
//App::uses('BlowfishPasswordHasher', 'Controller/Component/Auth');
/**
 * User Model
 *
 * @property Foodcourt $Foodcourt
 */
class Audit_stock extends AppModel {

public $useDbConfig = 'default';

public $useTable = 'pos_auditstock';

}
