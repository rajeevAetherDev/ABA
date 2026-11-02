<?php
App::uses('Takeaway', 'Model');

/**
 * Takeaway Test Case
 */
class TakeawayTest extends CakeTestCase {

/**
 * Fixtures
 *
 * @var array
 */
	public $fixtures = array(
		'app.takeaway'
	);

/**
 * setUp method
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();
		$this->Takeaway = ClassRegistry::init('Takeaway');
	}

/**
 * tearDown method
 *
 * @return void
 */
	public function tearDown() {
		unset($this->Takeaway);

		parent::tearDown();
	}

}
