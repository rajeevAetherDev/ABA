<?php
App::uses('Purchaseorder', 'Model');

/**
 * Purchaseorder Test Case
 */
class PurchaseorderTest extends CakeTestCase {

/**
 * Fixtures
 *
 * @var array
 */
	public $fixtures = array(
		'app.purchaseorder',
		'app.ingredient',
		'app.supplier',
		'app.linking',
		'app.product',
		'app.category',
		'app.orderhistory',
		'app.subproduct',
		'app.subproductstock',
		'app.substitute',
		'app.tracksubstitute',
		'app.productstock',
		'app.stock'
	);

/**
 * setUp method
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();
		$this->Purchaseorder = ClassRegistry::init('Purchaseorder');
	}

/**
 * tearDown method
 *
 * @return void
 */
	public function tearDown() {
		unset($this->Purchaseorder);

		parent::tearDown();
	}

}
