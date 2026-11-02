<?php
App::uses('AppModel', 'Model');
//App::uses('BlowfishPasswordHasher', 'Controller/Component/Auth');
/**
 * User Model
 *
 * @property Foodcourt $Foodcourt
 */
class Kds_user extends AppModel {

public $useDbConfig = 'default';

public $useTable = 'kds_users';

}
