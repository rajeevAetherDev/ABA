<?php
App::uses('Bogo', 'Model');

/**
 * Bogo Test Case
 */
class BogoTest extends CakeTestCase {

/**
 * Fixtures
 *
 * @var array
 */
	public $fixtures = array(
		'app.bogo',
		'app.product',
		'app.category',
		'app.linking',
		'app.subproduct',
		'app.subproductstock',
		'app.substitute',
		'app.tracksubstitute',
		'app.ingredient',
		'app.supplier',
		'app.stock',
		'app.orderhistory',
		'app.productstock'
	);

/**
 * setUp method
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();
		$this->Bogo = ClassRegistry::init('Bogo');
	}

/**
 * tearDown method
 *
 * @return void
 */
	public function tearDown() {
		unset($this->Bogo);

		parent::tearDown();
	}

}
