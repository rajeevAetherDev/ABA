<?php
App::uses('Managertrack', 'Model');

/**
 * Managertrack Test Case
 */
class ManagertrackTest extends CakeTestCase {

/**
 * Fixtures
 *
 * @var array
 */
	public $fixtures = array(
		'app.managertrack',
		'app.user',
		'app.usertrack'
	);

/**
 * setUp method
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();
		$this->Managertrack = ClassRegistry::init('Managertrack');
	}

/**
 * tearDown method
 *
 * @return void
 */
	public function tearDown() {
		unset($this->Managertrack);

		parent::tearDown();
	}

}
