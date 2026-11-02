<?php
App::uses('Idealinventoryreport', 'Model');

/**
 * Idealinventoryreport Test Case
 */
class IdealinventoryreportTest extends CakeTestCase {

/**
 * Fixtures
 *
 * @var array
 */
	public $fixtures = array(
		'app.idealinventoryreport',
		'app.user',
		'app.usertrack',
		'app.ingredient',
		'app.supplier',
		'app.linking',
		'app.product',
		'app.category',
		'app.orderhistory',
		'app.substitute',
		'app.tracksubstitute',
		'app.productstock',
		'app.subproduct',
		'app.subproductstock',
		'app.stock'
	);

/**
 * setUp method
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();
		$this->Idealinventoryreport = ClassRegistry::init('Idealinventoryreport');
	}

/**
 * tearDown method
 *
 * @return void
 */
	public function tearDown() {
		unset($this->Idealinventoryreport);

		parent::tearDown();
	}

}
