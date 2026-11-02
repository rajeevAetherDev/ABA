<?php
App::uses('Substitute', 'Model');

/**
 * Substitute Test Case
 */
class SubstituteTest extends CakeTestCase {

/**
 * Fixtures
 *
 * @var array
 */
	public $fixtures = array(
		'app.substitute',
		'app.product',
		'app.category',
		'app.linking',
		'app.subproduct',
		'app.ingredient',
		'app.supplier',
		'app.stock',
		'app.orderhistory',
		'app.tracksubstitute'
	);

/**
 * setUp method
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();
		$this->Substitute = ClassRegistry::init('Substitute');
	}

/**
 * tearDown method
 *
 * @return void
 */
	public function tearDown() {
		unset($this->Substitute);

		parent::tearDown();
	}

}
