<?php
App::uses('AppModel', 'Model');
//App::uses('BlowfishPasswordHasher', 'Controller/Component/Auth');
/**
 * User Model
 *
 * @property Foodcourt $Foodcourt
 */
class User_module extends AppModel {

public $useDbConfig = 'default';

public $useTable = 'user_modules';

}
