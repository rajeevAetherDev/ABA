<?php
/**
 * Takeaway Fixture
 */
class TakeawayFixture extends CakeTestFixture {

/**
 * Fields
 *
 * @var array
 */
	public $fields = array(
		'id' => array('type' => 'biginteger', 'null' => false, 'default' => null, 'length' => 10, 'unsigned' => false, 'key' => 'primary'),
		'number' => array('type' => 'string', 'null' => false, 'default' => null, 'length' => 50, 'collate' => 'latin1_swedish_ci', 'charset' => 'latin1'),
		'txnid' => array('type' => 'string', 'null' => false, 'default' => null, 'length' => 50, 'collate' => 'latin1_swedish_ci', 'charset' => 'latin1'),
		'guests' => array('type' => 'integer', 'null' => false, 'default' => '1', 'length' => 3, 'unsigned' => false),
		'capacity' => array('type' => 'integer', 'null' => false, 'default' => '1', 'length' => 3, 'unsigned' => false),
		'waiter_id' => array('type' => 'integer', 'null' => false, 'default' => '6', 'length' => 4, 'unsigned' => false),
		'delivery' => array('type' => 'integer', 'null' => false, 'default' => null, 'length' => 3, 'unsigned' => false),
		'status' => array('type' => 'integer', 'null' => false, 'default' => null, 'length' => 3, 'unsigned' => false),
		'created' => array('type' => 'datetime', 'null' => false, 'default' => null),
		'modified' => array('type' => 'datetime', 'null' => false, 'default' => null),
		'indexes' => array(
			'PRIMARY' => array('column' => 'id', 'unique' => 1)
		),
		'tableParameters' => array('charset' => 'latin1', 'collate' => 'latin1_swedish_ci', 'engine' => 'MyISAM')
	);

/**
 * Records
 *
 * @var array
 */
	public $records = array(
		array(
			'id' => 1,
			'number' => 'Lorem ipsum dolor sit amet',
			'txnid' => 'Lorem ipsum dolor sit amet',
			'guests' => 1,
			'capacity' => 1,
			'waiter_id' => 1,
			'delivery' => 1,
			'status' => 1,
			'created' => '2017-10-06 13:10:52',
			'modified' => '2017-10-06 13:10:52'
		),
	);

}
