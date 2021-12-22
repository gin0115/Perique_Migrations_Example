<?php

declare(strict_types=1);

/**
 * Unit tests for the main Migrations service.
 *
 * @since 0.1.0
 * @author GLynn Quelch <glynn.quelch@gmail.com>
 */

namespace PinkCrab\Perique\Migration\Tests\Unit\Migration;

use stdClass;
use WP_UnitTestCase;
use Gin0115\WPUnit_Helpers\Objects;
use PinkCrab\Perique\Migration\Migrations;
use PinkCrab\DB_Migration\Migration_Manager;
use PinkCrab\Perique\Application\App_Factory;
use PinkCrab\Perique\Migration\Event\Activation;
use PinkCrab\Perique\Migration\Event\Deactivation;
use PinkCrab\Perique\Migration\Migration_Exception;
use PinkCrab\Plugin_Lifecycle\Plugin_State_Controller;
use PinkCrab\Perique\Migration\Tests\Helpers\App_Helper_Trait;
use PinkCrab\Perique\Migration\Tests\Fixtures\Has_Seeds_Migration;
use PinkCrab\Perique\Migration\Tests\Fixtures\Simple_Table_Migration;
use PinkCrab\Perique\Migration\Tests\Fixtures\Throws_On_Construct_Migration;
use PinkCrab\Perique\Migration\Tests\Fixtures\Drop_On_Deactivation_Migration;
use PinkCrab\Perique\Migration\Tests\Fixtures\Data_Providers\Null_DI_Container;

class Test_Migrations extends WP_UnitTestCase {

	use App_Helper_Trait;

	/**
	 * Holds the mocked version of Perique
	 *
	 * @var App
	 */
	public static $app_instance;

	public static $plugin_state_controller;

	/**
	 * Sets up instance of Perique App
	 * Only loaded with basic DI Rules.
	 */
	public function setUp() {
		parent::setUp();
		self::$app_instance            = ( new App_Factory() )->with_wp_dice()->boot();
		self::$plugin_state_controller = new Plugin_State_Controller( self::$app_instance, __FILE__ );
		$GLOBALS['wp_filter']          = array();
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
	}

	/** @testdox When the service is costructed, the DI container should be set as property from Plugin State Controllers helper method. */
	public function test_di_container_set_at_construct(): void {
		$migrations = new Migrations( self::$plugin_state_controller );
		$this->assertSame( Objects::get_property( $migrations, 'di_container' ), self::$plugin_state_controller->get_app()->get_container() );
	}

	/** @testdox It should be possible to set the migration log key, both at construct and via setter method. */
	public function test_set_migration_log_key(): void {
		$migrations = new Migrations( self::$plugin_state_controller, 'at_const' );
		$this->assertEquals( 'at_const', Objects::get_property( $migrations, 'migration_log_key' ) );

		$migrations->set_migration_log_key( 'set_with_method' );
		$this->assertEquals( 'set_with_method', Objects::get_property( $migrations, 'migration_log_key' ) );
	}

	/** @testdox It should be possible to pass a constructed migration instance to be processed. */
	public function test_add_migration_as_instance(): void {
		$migrations         = new Migrations( self::$plugin_state_controller );
		$migration_instance = new Has_Seeds_Migration();
		$migrations->add_migration( $migration_instance );

		$this->assertCount( 1, $migrations->get_migrations() );
		$this->assertSame( $migrations->get_migrations()[0], $migration_instance );
	}

	/** @testdox It should be possible to pass an unconstructed migration class by its string (class name) to be constructed via DI and then processed. */
	public function test_add_migration_as_class_string(): void {
		$migrations = new Migrations( self::$plugin_state_controller );
		$migrations->add_migration( Simple_Table_Migration::class );

		$this->assertCount( 1, $migrations->get_migrations() );
		$this->assertInstanceOf( Simple_Table_Migration::class, $migrations->get_migrations()[0] );
	}

	/** @testdox If a custom migration manager is not defined, a fallback should be used when calling done() at the end of the setup. */
	public function test_migration_manager_added_at_done_if_not_set(): void {
		$migrations = new Migrations( self::$plugin_state_controller );
		$this->assertNull( Objects::get_property( $migrations, 'migration_manager' ) );
		$migrations->add_migration( Simple_Table_Migration::class );

		// Run done, should see the migration manager set.
		$migrations->done();
		$this->assertInstanceOf( Migration_Manager::class, Objects::get_property( $migrations, 'migration_manager' ) );
	}

	/** @testdox Attempting to pass a string which is not a valid Migration object, should throw an exception */
	public function test_throws_exception_if_none_migration_class_passed(): void {
		$migrations = new Migrations( self::$plugin_state_controller );

		$this->expectException( Migration_Exception::class );
		$this->expectDeprecationMessage( 'Migration::class instance or class name expected, got O:8:"stdClass":0:{}' );
		$this->expectExceptionCode( 102 );
		$migrations->add_migration( new stdClass() );
	}

	/** @testdox Attempting to pass a string which is not a valid Migration class name, should throw an exception */
	public function test_throws_exception_if_none_migration_class_string_passed(): void {
		$migrations = new Migrations( self::$plugin_state_controller );

		$this->expectException( Migration_Exception::class );
		$this->expectExceptionCode( 102 );
		$this->expectDeprecationMessage( 'Migration::class instance or class name expected, got s:11:"SOME STRING"' );
		$migrations->add_migration( 'SOME STRING' );
	}

	/** @testdox Attempting to pass none object or class string, should throw an exception */
	public function test_throws_exception_if_invalid_type_passed(): void {
		$migrations = new Migrations( self::$plugin_state_controller );

		$this->expectException( Migration_Exception::class );
		$this->expectDeprecationMessage( 'Migration::class instance or class name expected, got a:1:{i:0;a:1:{i:0;i:1;}}' );
		$this->expectExceptionCode( 102 );
		$migrations->add_migration( array( array( 1 ) ) );
	}

	/** @testdox Any exception or errors generated while constructing a migration instance via the DI Container. This should be caught and re thrown as a Migration Exception. */
	public function test_rethrow_migration_exception_if_exception_caught_creating_instance(): void {
		$migrations = new Migrations( self::$plugin_state_controller );

		$this->expectException( Migration_Exception::class );
		$this->expectDeprecationMessage( 'Failed to construct ' . Throws_On_Construct_Migration::class . ' using the DI Container' );
		$this->expectExceptionCode( 101 );

		$migrations->add_migration( Throws_On_Construct_Migration::class );
	}

	public function test_throw_exception_if_null_is_returned_from_constructing_migration(): void {
		$migrations = new Migrations( self::$plugin_state_controller );
		Objects::set_property( $migrations, 'di_container', new Null_DI_Container() );

		$this->expectException( Migration_Exception::class );
		$this->expectDeprecationMessage( 'Failed to construct Invalid after construction using the DI Container' );
		$this->expectExceptionCode( 101 );

		$migrations->add_migration( Simple_Table_Migration::class );
	}


	public function test_throw_exception_if_wrong_type_is_returned_from_constructing_migration(): void {
		$migrations         = new Migrations( self::$plugin_state_controller );
		$container          = new Null_DI_Container();
		$container->returns = new stdClass();
		Objects::set_property( $migrations, 'di_container', $container );

		$this->expectException( Migration_Exception::class );
		$this->expectDeprecationMessage( 'Failed to construct Invalid after construction using the DI Container' );
		$this->expectExceptionCode( 101 );

		$migrations->add_migration( Simple_Table_Migration::class );
	}

	/** @testdox When a migration manager is defined, the fallback should not be set when calling done() */
	public function test_custom_migration_manager_instance(): void {
		$migrations = new Migrations( self::$plugin_state_controller );

		$mock_manager = $this->createMock( Migration_Manager::class );
		$migrations->set_migration_manager( $mock_manager );
		$this->assertSame( $mock_manager, Objects::get_property( $migrations, 'migration_manager' ) );

		// Should not be set when running done as already set.
		$migrations->done();
		$this->assertSame( $mock_manager, Objects::get_property( $migrations, 'migration_manager' ) );
	}

	/** @testdox When migrations are added, an activation event should be registered with the PLugin State Controller to handle the activation hook handling. */
	public function test_plugin_activation_event_changes_created(): void {
		$migrations = new Migrations( self::$plugin_state_controller );
		$table      = new Simple_Table_Migration();
		$migrations->add_migration( $table );
		$migrations->done();

		$events            = Objects::get_property( self::$plugin_state_controller, 'state_events' );
		$migration_manager = Objects::get_property( $events[0], 'migration_manager' );

		$this->assertInstanceOf( Activation::class, $events[0] );
		$this->assertArrayHasKey( $table::TABLE_NAME, $migration_manager->get_migrations() );
		$this->assertSame( $table, $migration_manager->get_migrations()[ $table::TABLE_NAME ] );
	}

	/** @testdox When migrations are added, an deactivation event should be registered with the PLugin State Controller to handle the activation hook handling. */
	public function test_plugin_deactivation_event_changes_created(): void {
		$migrations = new Migrations( self::$plugin_state_controller );
		$table      = new Drop_On_Deactivation_Migration();
		$migrations->add_migration( $table );
		$migrations->done();

		$events = Objects::get_property( self::$plugin_state_controller, 'state_events' );
		$this->assertCount( 2, $events );
		$this->assertSame(
			Objects::get_property( $events[0], 'migration_manager' ),
			Objects::get_property( $events[1], 'migration_manager' )
		);

		$this->assertInstanceOf( Activation::class, $events[0] );
		$this->assertInstanceOf( Deactivation::class, $events[1] );
	}





}
