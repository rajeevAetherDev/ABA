<?php
App::uses('Subproduct', 'Model');

/**
 * Subproduct Test Case
 */
class SubproductTest extends CakeTestCase {

/**
 * Fixtures
 *
 * @var array
 */
	public $fixtures = array(
		'app.subproduct',
		'app.product',
		'app.category',
		'app.linking',
		'app.substitute',
		'app.tracksubstitute',
		'app.ingredient',
		'app.supplier',
		'app.stock',
		'app.orderhistory'
	);

/**
 * setUp method
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();
		$this->Subproduct = ClassRegistry::init('Subproduct');
	}

/**
 * tearDown method
 *
 * @return void
 */
	public function tearDown() {
		unset($this->Subproduct);

		parent::tearDown();
	}

}
