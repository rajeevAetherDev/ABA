<?php
App::uses('Linking', 'Model');

/**
 * Linking Test Case
 */
class LinkingTest extends CakeTestCase {

/**
 * Fixtures
 *
 * @var array
 */
	public $fixtures = array(
		'app.linking'
	);

/**
 * setUp method
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();
		$this->Linking = ClassRegistry::init('Linking');
	}

/**
 * tearDown method
 *
 * @return void
 */
	public function tearDown() {
		unset($this->Linking);

		parent::tearDown();
	}

}
