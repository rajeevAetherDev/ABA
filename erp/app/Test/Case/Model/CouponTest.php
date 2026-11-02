<?php
App::uses('Coupon', 'Model');

/**
 * Coupon Test Case
 */
class CouponTest extends CakeTestCase {

/**
 * Fixtures
 *
 * @var array
 */
	public $fixtures = array(
		'app.coupon'
	);

/**
 * setUp method
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();
		$this->Coupon = ClassRegistry::init('Coupon');
	}

/**
 * tearDown method
 *
 * @return void
 */
	public function tearDown() {
		unset($this->Coupon);

		parent::tearDown();
	}

}
