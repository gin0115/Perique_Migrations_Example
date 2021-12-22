<?php

declare(strict_types=1);

/**
 * Unit tests for the main Migrations service.
 *
 * @since 0.1.0
 * @author GLynn Quelch <glynn.quelch@gmail.com>
 */

namespace PinkCrab\Perique\Migration\Tests\Integration;

use WP_UnitTestCase;
use PinkCrab\Perique\Migration\Migrations;
use PinkCrab\Perique\Application\App_Factory;
use PinkCrab\Plugin_Lifecycle\State_Change_Queue;
use PinkCrab\Plugin_Lifecycle\Plugin_State_Controller;
use PinkCrab\Perique\Migration\Tests\Helpers\App_Helper_Trait;
use PinkCrab\Perique\Migration\Tests\Fixtures\Has_Seeds_Migration;
use PinkCrab\Perique\Migration\Tests\Fixtures\Simple_Table_Migration;
use function PinkCrab\FunctionConstructors\GeneralFunctions\getProperty;
use PinkCrab\Perique\Migration\Tests\Fixtures\Drop_On_Uninstall_Migration;
use PinkCrab\Perique\Migration\Tests\Fixtures\Drop_On_Deactivation_Migration;
use PinkCrab\Perique\Migration\Tests\Fixtures\No_Drop_On_Uninstall_Migration;
use PinkCrab\Perique\Migration\Tests\Fixtures\Has_Seeds_Migration_But_Disabled;
use PinkCrab\Perique\Migration\Tests\Fixtures\Not_Drop_On_Deactivation_Migration;

class Test_Manually_Called_Lifecylce_Events extends WP_UnitTestCase {

	use App_Helper_Trait;

	public static $app_instance;
	public static $wpdb;

	/**
	 * Sets up instance of Perique App
	 * Only loaded with basic DI Rules.
	 */
	public function setUp() {
		parent::setUp();
		self::$app_instance    = ( new App_Factory() )->with_wp_dice()->boot();
		$GLOBALS['wp_filter']  = array();
		$GLOBALS['wp_actions'] = array();
		self::$wpdb            = $GLOBALS['wpdb'];
		self::$wpdb->suppress_errors( true );
	}

	/**
	 * Unsets the app instance, to be rebuilt next time.
	 *
	 * @return void
	 */
	public function tearDown() {
		parent::tearDown();
		$this->unset_app_instance();

		// Clear all hooks used.
		$GLOBALS['wp_actions'] = array();
		$GLOBALS['wp_filter']  = array();
		\delete_option( 'uninstall_plugins' );
		self::$wpdb->suppress_errors( false );
	}

	/** @testdox [INT] It should be possible to define migrations at which will be created and have seed data populated when the activation hook is called (MIMIC'S ACTIVATION PROCESS) */
	public function test_create_and_seed_on_activation() {
		// Create migrations and state controller.
		$plugin_state_controller = new Plugin_State_Controller( self::$app_instance, __FILE__ );
		$migrations              = new Migrations( $plugin_state_controller, 'test_create_and_seed_on_activation' );

		// Populate the migrations
		$migration_a = new Simple_Table_Migration();           // No seed data
		$migration_b = new Has_Seeds_Migration();              // Has seed data, should create
		$migration_c = new Has_Seeds_Migration_But_Disabled(); // Has seed data, but should not create.
		$migrations->add_migration( $migration_a );
		$migrations->add_migration( $migration_b );
		$migrations->add_migration( $migration_c );

		$migrations->done();
		$plugin_state_controller->finalise();

		// Run mock plugin activation
		\do_action( 'activate_' . ltrim( __FILE__, '/' ) );

		// Check tables and seed data created for each table.

		// CREATE WITH NO SEEDED DATA
		$simple_table_columns = self::$wpdb->get_results( "SHOW COLUMNS FROM {$migration_a->get_table_name()};" );
		$this->assertCount( 2, $simple_table_columns );
		$this->assertSame( array( 'id', 'user' ), array_map( getProperty( 'Field' ), $simple_table_columns ) );
		$simple_table_rows = self::$wpdb->get_results( "SELECT * FROM {$migration_a->get_table_name()};" );
		$this->assertCount( 0, $simple_table_rows );

		// CREATE WITH SEEDED DATA
		$has_seeds_columns = self::$wpdb->get_results( "SHOW COLUMNS FROM {$migration_b->get_table_name()};" );
		$this->assertCount( 2, $has_seeds_columns );
		$this->assertSame( array( 'id', 'user' ), array_map( getProperty( 'Field' ), $has_seeds_columns ) );
		$has_seeds_rows = self::$wpdb->get_results( "SELECT * FROM {$migration_b->get_table_name()};" );
		$this->assertCount( 2, $has_seeds_rows );
		$this->assertSame( array( 'Alpha', 'Bravo' ), array_map( getProperty( 'user' ), $has_seeds_rows ) );

		// CREATE WITH SEED DATA, BUT TOLD TO IGNORE
		$has_but_ignored_seeds_columns = self::$wpdb->get_results( "SHOW COLUMNS FROM {$migration_c->get_table_name()};" );
		$this->assertCount( 2, $has_but_ignored_seeds_columns );
		$this->assertSame( array( 'bar', 'foo' ), array_map( getProperty( 'Field' ), $has_but_ignored_seeds_columns ) );
		$has_but_ignored_seeds_rows = self::$wpdb->get_results( "SELECT * FROM {$migration_c->get_table_name()};" );
		$this->assertCount( 0, $has_but_ignored_seeds_rows );
	}

	/** @testdox [INT] It should be possible to define migrations at which will drop existing tables that are not skipped via configuration when the cdactivation hook is called (MIMIC'S DEACTIVATION PROCESS) */
	public function test_drop_tables_on_deactivate(): void {

		// Create migrations and state controller.
		$plugin_state_controller = new Plugin_State_Controller( self::$app_instance, __FILE__ );
		$migrations              = new Migrations( $plugin_state_controller, 'test_create_and_seed_on_activation' );

		// Populate the migrations
		$migration_a = new Drop_On_Deactivation_Migration();     // Does Drop
		$migration_b = new Not_Drop_On_Deactivation_Migration(); // Has seed data, should create
		$migrations->add_migration( $migration_a );
		$migrations->add_migration( $migration_b );

		$migrations->done();
		$plugin_state_controller->finalise();

		// Run mock plugin activation to create
		\do_action( 'activate_' . ltrim( __FILE__, '/' ) );

		// Check we have both tables.
		$a_post_activation = self::$wpdb->get_results( "SHOW COLUMNS FROM {$migration_a->get_table_name()};" );
		$b_post_activation = self::$wpdb->get_results( "SHOW COLUMNS FROM {$migration_b->get_table_name()};" );
		if ( empty( $a_post_activation ) || empty( $b_post_activation ) ) {
			$this->fail( 'Failed to create mock table to drop for test' );
		}

		\do_action( 'deactivate_' . ltrim( __FILE__, '/' ) );

		$a_post_deactivation = self::$wpdb->get_results( "SHOW COLUMNS FROM {$migration_a->get_table_name()};" );
		$b_post_deactivation = self::$wpdb->get_results( "SHOW COLUMNS FROM {$migration_b->get_table_name()};" );
		$this->assertEmpty( $a_post_deactivation ); // dropped
		$this->assertNotEmpty( $b_post_deactivation ); // not dropped
	}

    /** @testdox [INT] It should be possible to set up migrations that will either be dropped or left when unistalling the plugin. As under the hood this uses the WPDB migrations module, the migration log should also be removed.*/
	public function test_drop_tables_on_uninstall(): void {
		// Create migrations and state controller.
		$plugin_state_controller = new Plugin_State_Controller( self::$app_instance, __FILE__ );
		$migrations              = new Migrations( $plugin_state_controller, 'test_drop_tables_on_uninstall' );

		// Populate the migrations
		$migration_a = new Drop_On_Uninstall_Migration();     // Does Drop
		$migration_b = new No_Drop_On_Uninstall_Migration();  // Does not drop
		$migrations->add_migration( $migration_a );
		$migrations->add_migration( $migration_b );

		$migrations->done();
		$plugin_state_controller->finalise();

		// Run mock plugin activation to create
		\do_action( 'activate_' . ltrim( __FILE__, '/' ) );

		// Check we have both tables.
		$a_post_activation = self::$wpdb->get_results( "SHOW COLUMNS FROM {$migration_a->get_table_name()};" );
		$b_post_activation = self::$wpdb->get_results( "SHOW COLUMNS FROM {$migration_b->get_table_name()};" );
		if ( empty( $a_post_activation ) || empty( $b_post_activation ) ) {
			$this->fail( 'Failed to create mock table to drop for test' );
		}

		// Check the migration log was created.
		if ( false === get_option( 'test_drop_tables_on_uninstall' ) ) {
			$this->fail( 'Failed to create migration log for tests.' );
		}

		// Check the plugins uninstall class has been added to the option for uninstalling.
		$plugins_with_uninstall = \get_option( 'uninstall_plugins' );
		$this->assertArrayHasKey( ltrim( __FILE__, '/' ), $plugins_with_uninstall );
		$this->assertInstanceOf( State_Change_Queue::class, $plugins_with_uninstall[ ltrim( __FILE__, '/' ) ] );

		// Mock calling the uninstall process.
		$event = $plugins_with_uninstall[ ltrim( __FILE__, '/' ) ];
		$event->__invoke();

		// Check correct tables dropped.
		$a_post_deactivation = self::$wpdb->get_results( "SHOW COLUMNS FROM {$migration_a->get_table_name()};" );
		$b_post_deactivation = self::$wpdb->get_results( "SHOW COLUMNS FROM {$migration_b->get_table_name()};" );
		$this->assertEmpty( $a_post_deactivation ); // dropped
		$this->assertNotEmpty( $b_post_deactivation ); // not dropped

		// Check the migration log was cleared.
		$this->assertFalse( get_option( 'test_drop_tables_on_uninstall' ) );
	}


}
