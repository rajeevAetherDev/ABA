<?php
/**
 * Offer Fixture
 */
class OfferFixture extends CakeTestFixture {

/**
 * Fields
 *
 * @var array
 */
	public $fields = array(
		'id' => array('type' => 'biginteger', 'null' => false, 'default' => null, 'length' => 10, 'unsigned' => false, 'key' => 'primary'),
		'product_id' => array('type' => 'integer', 'null' => false, 'default' => null, 'length' => 5, 'unsigned' => false),
		'type' => array('type' => 'integer', 'null' => false, 'default' => null, 'length' => 3, 'unsigned' => false),
		'value' => array('type' => 'float', 'null' => false, 'default' => null, 'unsigned' => false),
		'startdatetime' => array('type' => 'datetime', 'null' => false, 'default' => null),
		'enddatetime' => array('type' => 'datetime', 'null' => false, 'default' => null),
		'status' => array('type' => 'integer', 'null' => false, 'default' => null, 'length' => 3, 'unsigned' => false),
		'created' => array('type' => 'datetime', 'null' => false, 'default' => null),
		'modified' => array('type' => 'datetime', 'null' => false, 'default' => null),
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
			'product_id' => 1,
			'type' => 1,
			'value' => 1,
			'startdatetime' => '2018-01-23 13:10:02',
			'enddatetime' => '2018-01-23 13:10:02',
			'status' => 1,
			'created' => '2018-01-23 13:10:02',
			'modified' => '2018-01-23 13:10:02'
		),
	);

}
