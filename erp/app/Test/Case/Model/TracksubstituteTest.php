<?php
App::uses('Tracksubstitute', 'Model');

/**
 * Tracksubstitute Test Case
 */
class TracksubstituteTest extends CakeTestCase {

/**
 * Fixtures
 *
 * @var array
 */
	public $fixtures = array(
		'app.tracksubstitute',
		'app.substitute',
		'app.product',
		'app.category',
		'app.orderhistory'
	);

/**
 * setUp method
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();
		$this->Tracksubstitute = ClassRegistry::init('Tracksubstitute');
	}

/**
 * tearDown method
 *
 * @return void
 */
	public function tearDown() {
		unset($this->Tracksubstitute);

		parent::tearDown();
	}

}
