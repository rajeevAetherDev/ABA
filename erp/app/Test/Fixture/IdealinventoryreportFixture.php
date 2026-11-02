<?php
/**
 * Idealinventoryreport Fixture
 */
class IdealinventoryreportFixture extends CakeTestFixture {

/**
 * Fields
 *
 * @var array
 */
	public $fields = array(
		'id' => array('type' => 'biginteger', 'null' => false, 'default' => null, 'length' => 10, 'unsigned' => false, 'key' => 'primary'),
		'user_id' => array('type' => 'integer', 'null' => false, 'default' => null, 'length' => 5, 'unsigned' => false),
		'ingredient_id' => array('type' => 'integer', 'null' => false, 'default' => null, 'length' => 5, 'unsigned' => false),
		'category' => array('type' => 'string', 'null' => false, 'default' => null, 'length' => 50, 'collate' => 'latin1_swedish_ci', 'charset' => 'latin1'),
		'openingstock' => array('type' => 'float', 'null' => false, 'default' => null, 'unsigned' => false),
		'stockin' => array('type' => 'float', 'null' => false, 'default' => null, 'unsigned' => false),
		'transferin' => array('type' => 'float', 'null' => false, 'default' => null, 'unsigned' => false),
		'transferout' => array('type' => 'float', 'null' => false, 'default' => null, 'unsigned' => false),
		'totalstock' => array('type' => 'float', 'null' => false, 'default' => null, 'unsigned' => false),
		'closingstock' => array('type' => 'float', 'null' => false, 'default' => null, 'unsigned' => false),
		'subproductstock' => array('type' => 'float', 'null' => false, 'default' => null, 'unsigned' => false),
		'totalclosing' => array('type' => 'float', 'null' => false, 'default' => null, 'unsigned' => false),
		'idealconsumption' => array('type' => 'float', 'null' => false, 'default' => null, 'unsigned' => false),
		'actualconsumption' => array('type' => 'float', 'null' => false, 'default' => null, 'unsigned' => false),
		'damage' => array('type' => 'float', 'null' => false, 'default' => null, 'unsigned' => false),
		'varience' => array('type' => 'float', 'null' => false, 'default' => null, 'unsigned' => false),
		'created' => array('type' => 'datetime', 'null' => false, 'default' => null),
		'indexes' => array(
			'PRIMARY' => array('column' => 'id', 'unique' => 1)
		),
		'tableParameters' => array('charset' => 'latin1', 'collate' => 'latin1_swedish_ci', 'engine' => 'InnoDB')
	);

/**
 * Records
 *
 * @var array
 */
	public $records = array(
		array(
			'id' => 1,
			'user_id' => 1,
			'ingredient_id' => 1,
			'category' => 'Lorem ipsum dolor sit amet',
			'openingstock' => 1,
			'stockin' => 1,
			'transferin' => 1,
			'transferout' => 1,
			'totalstock' => 1,
			'closingstock' => 1,
			'subproductstock' => 1,
			'totalclosing' => 1,
			'idealconsumption' => 1,
			'actualconsumption' => 1,
			'damage' => 1,
			'varience' => 1,
			'created' => '2018-01-08 11:01:12'
		),
	);

}
