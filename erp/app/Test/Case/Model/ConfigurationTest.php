<?php
App::uses('Configuration', 'Model');

/**
 * Configuration Test Case
 */
class ConfigurationTest extends CakeTestCase {

/**
 * Fixtures
 *
 * @var array
 */
	public $fixtures = array(
		'app.configuration'
	);

/**
 * setUp method
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();
		$this->Configuration = ClassRegistry::init('Configuration');
	}

/**
 * tearDown method
 *
 * @return void
 */
	public function tearDown() {
		unset($this->Configuration);

		parent::tearDown();
	}

}
