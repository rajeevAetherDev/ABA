<?php
App::uses('Offer', 'Model');

/**
 * Offer Test Case
 */
class OfferTest extends CakeTestCase {

/**
 * Fixtures
 *
 * @var array
 */
	public $fixtures = array(
		'app.offer',
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
		$this->Offer = ClassRegistry::init('Offer');
	}

/**
 * tearDown method
 *
 * @return void
 */
	public function tearDown() {
		unset($this->Offer);

		parent::tearDown();
	}

}
