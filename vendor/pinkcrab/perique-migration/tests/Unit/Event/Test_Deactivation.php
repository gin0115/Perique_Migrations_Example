<?php

declare(strict_types=1);

/**
 * Unit tests for the Migrations "Deactivation" plugin event
 *
 * @since 0.1.0
 * @author GLynn Quelch <glynn.quelch@gmail.com>
 */

namespace PinkCrab\Perique\Migration\Tests\Unit\Migration\Event;

use WP_UnitTestCase;
use PinkCrab\Table_Builder\Engines\Engine;
use PinkCrab\DB_Migration\Migration_Manager;
use PinkCrab\Perique\Migration\Event\Deactivation;
use PinkCrab\Perique\Migration\Tests\Fixtures\Simple_Table_Migration;
use PinkCrab\Perique\Migration\Tests\Fixtures\Drop_On_Deactivation_Migration;
use PinkCrab\Perique\Migration\Tests\Fixtures\Not_Drop_On_Deactivation_Migration;
use PinkCrab\Perique\Migration\Tests\Fixtures\Data_Providers\Migration_Manager_Provider;

class Test_Deactivation extends WP_UnitTestCase {

	/**
	 * @var Migration_Manager_Provider
	 */
	protected $migration_manager_provider;

	public function setUp(): void {
		parent::setUp();
		$this->migration_manager_provider = new Migration_Manager_Provider();
	}

	/** @testdox It should be possible to define a selection of Migrations which can be droped using a table builder. */
	public function test_can_drop_tables(): void {
		$migration_manager_tuple = $this->migration_manager_provider->with_logging_table_builder( 'test_can_create_tables', $this->createMock( 'wpdb' ) );
		/** @var Migration_Manager */
		$migration_manager = $migration_manager_tuple['migration_manger'];
		/** @var Engine */
		$engine = $migration_manager_tuple['engine'];

		$migration_a = new Simple_Table_Migration();              // Do not drop (default)
		$migration_b = new Not_Drop_On_Deactivation_Migration();  // Do not drop (explicit)
		$migration_c = new Drop_On_Deactivation_Migration(); 	  // Do drop (explicit)
		$migration_manager->add_migration( $migration_a );
		$migration_manager->add_migration( $migration_b );
		$migration_manager->add_migration( $migration_c );

		$activation_event = new Deactivation( $migration_manager );
		$activation_event->run();

		$this->assertCount( 1, $engine->events['drop'] );
		$this->assertContains( $migration_c::TABLE_NAME, $engine->events['drop'][0]->get_table_name() );
	}
}
