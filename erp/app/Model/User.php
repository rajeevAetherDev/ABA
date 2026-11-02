<?php
App::uses('AppModel', 'Model');
//App::uses('BlowfishPasswordHasher', 'Controller/Component/Auth');
/**
 * User Model
 *
 * @property Foodcourt $Foodcourt
 */
class User extends AppModel {

public $useDbConfig = 'default';

public $useTable = 'users';

}
