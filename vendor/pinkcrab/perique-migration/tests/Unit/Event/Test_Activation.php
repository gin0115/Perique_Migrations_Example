<?php

declare(strict_types=1);

/**
 * Unit tests for the Migrations "Activation" plugin event
 *
 * @since 0.1.0
 * @author GLynn Quelch <glynn.quelch@gmail.com>
 */

namespace PinkCrab\Perique\Migration\Tests\Unit\Migration\Event;

use WP_UnitTestCase;
use PinkCrab\Perique\Migration\Event\Activation;
use PinkCrab\Table_Builder\Engines\Engine;
use PinkCrab\DB_Migration\Migration_Manager;
use PinkCrab\Perique\Migration\Tests\Helpers\Logable_WPDB;
use PinkCrab\Perique\Migration\Tests\Fixtures\Has_Seeds_Migration;
use PinkCrab\Perique\Migration\Tests\Fixtures\Simple_Table_Migration;
use PinkCrab\Perique\Migration\Tests\Fixtures\Has_Seeds_Migration_But_Disabled;
use PinkCrab\Perique\Migration\Tests\Fixtures\Data_Providers\Migration_Manager_Provider;

class Test_Activation extends WP_UnitTestCase {

	/**
	 * @var Migration_Manager_Provider
	 */
	protected $migration_manager_provider;

	public function setUp(): void {
		parent::setUp();
		$this->migration_manager_provider = new Migration_Manager_Provider();
	}

	/** @testdox It should be possible to define a selection of Migrations which can be created using a table builder. */
	public function test_can_create_tables(): void {
		$migration_manager_tuple = $this->migration_manager_provider->with_logging_table_builder( 'test_can_create_tables', $this->createMock( 'wpdb' ) );
		/** @var Migration_Manager */
		$migration_manager = $migration_manager_tuple['migration_manger'];
		/** @var Engine */
		$engine = $migration_manager_tuple['engine'];

		$migration_a = new Simple_Table_Migration();
		$migration_b = new Has_Seeds_Migration();
		$migration_manager->add_migration( $migration_a );
		$migration_manager->add_migration( $migration_b );

		$activation_event = new Activation( $migration_manager );
		$activation_event->run();

		$this->assertCount( 2, $engine->events['create'] );
		$this->assertContains( $migration_a::TABLE_NAME, $engine->events['create'][0]->get_table_name() );
		$this->assertContains( $migration_b::TABLE_NAME, $engine->events['create'][1]->get_table_name() );
	}

	/** @testdox It should be possible to set data to be seeded on activation. There should also be a way to disable seeding, even if data is present too. */
	public function test_can_seed_tables(): void {
		$wpdb = new Logable_WPDB();

		$migration_manager_tuple = $this->migration_manager_provider->with_logging_table_builder( 'test_can_seed_tables', $wpdb );
		/** @var Migration_Manager */
		$migration_manager = $migration_manager_tuple['migration_manger'];
		/** @var Engine */
		$engine = $migration_manager_tuple['engine'];

		$migration_a = new Simple_Table_Migration();           // No seed data
		$migration_b = new Has_Seeds_Migration();              // Has seed data, should create
		$migration_c = new Has_Seeds_Migration_But_Disabled(); // Has seed data, but should not create.
		$migration_manager->add_migration( $migration_a );
		$migration_manager->add_migration( $migration_b );
		$migration_manager->add_migration( $migration_c );

		$activation_event = new Activation( $migration_manager );
		$activation_event->run();

		// Should have inserted only seeds from has_seeds_migration
		$this->assertCount( 1, $wpdb->usage_log['insert'] );
		$this->assertArrayHasKey( 'has_seeds_migration', $wpdb->usage_log['insert'] );

		// Both users from Has_Seeds_Migration seed should have been inserted.
		$this->assertCount( 2, $wpdb->usage_log['insert']['has_seeds_migration'] );
		$this->assertEquals( 'Alpha', $wpdb->usage_log['insert']['has_seeds_migration'][0]['data']['user'] );
		$this->assertEquals( '%s', $wpdb->usage_log['insert']['has_seeds_migration'][0]['format'][0] );
		$this->assertEquals( 'Bravo', $wpdb->usage_log['insert']['has_seeds_migration'][1]['data']['user'] );
		$this->assertEquals( '%s', $wpdb->usage_log['insert']['has_seeds_migration'][1]['format'][0] );
	}


}
