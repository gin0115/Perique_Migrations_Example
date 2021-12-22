<?php

declare(strict_types=1);

/**
 * Unit tests for the Migrations "Uninstall" plugin event
 *
 * @since 0.1.0
 * @author GLynn Quelch <glynn.quelch@gmail.com>
 */

namespace PinkCrab\Perique\Migration\Tests\Unit\Migration\Event;

use WP_UnitTestCase;
use PinkCrab\Perique\Migration\Event\Uninstall;
use PinkCrab\Perique\Migration\Tests\Helpers\Logable_WPDB;
use PinkCrab\Perique\Migration\Tests\Fixtures\Simple_Table_Migration;
use PinkCrab\Perique\Migration\Tests\Fixtures\Drop_On_Uninstall_Migration;
use PinkCrab\Perique\Migration\Tests\Fixtures\No_Drop_On_Uninstall_Migration;
use PinkCrab\Perique\Migration\Tests\Fixtures\Data_Providers\Migration_Manager_Provider;

class Test_Uninstall extends WP_UnitTestCase {

	/**
	 * @var Migration_Manager_Provider
	 */
	protected $migration_manager_provider;

	protected $inital_wpdb;

	public function setUp(): void {
		$this->migration_manager_provider = new Migration_Manager_Provider();
		$this->inital_wpdb                = $GLOBALS['wpdb'];
		parent::setUp();
	}

	public function tearDown():void {
		$GLOBALS['wpdb'] = $this->inital_wpdb;
		parent::tearDown();
	}

	/** @testdox When running the uninstall event the state of wpdb's suppress errors should be reset to its initial state. */
	public function test_suppress_error_state_restored(): void {
		$logable_wpdb                  = new Logable_WPDB();
		$logable_wpdb->suppress_errors = false;
		$GLOBALS['wpdb']               = $logable_wpdb;
		$this->caught_doing_it_wrong   = array();

		( new Uninstall( array( 'table1', 'table2' ), 'foo' ) )();

		$this->assertEquals( false, $logable_wpdb->suppress_errors );

		// Suppress WPTestCase from throwing doing it wrong error due to mocking WPDB as a global.
		$this->caught_doing_it_wrong = array();
	}
}
