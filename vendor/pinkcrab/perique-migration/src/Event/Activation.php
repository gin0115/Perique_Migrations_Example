<?php

declare(strict_types=1);

/**
 * Abstract class for all Migrations
 *
 * @package PinkCrab\Perique\Migration\Plugin_Lifecycle
 * @author Glynn Quelch glynn@pinkcrab.co.uk
 * @since 0.0.1
 */

namespace PinkCrab\Perique\Migration\Event;

use PinkCrab\DB_Migration\Migration_Manager;
use PinkCrab\Perique\Migration\Migration;
use PinkCrab\Plugin_Lifecycle\State_Event\Activation as State_Events_Activation;

class Activation implements State_Events_Activation {

	/**
	 * Holds the current Migration Manager and list of all migrations.
	 *
	 * @var Migration_Manager
	 */
	protected $migration_manager;

	public function __construct( Migration_Manager $migration_manager ) {
		$this->migration_manager = $migration_manager;
	}

	/**
	 * Create all table and seed those that allow.
	 *
	 * @return void
	 */
	public function run(): void {
		$this->upsert_tables();
		$this->seed_tables();
	}

	/**
	 * Upsert all tables in migration manager
	 *
	 * @return void
	 */
	private function upsert_tables(): void {
		$this->migration_manager->create_tables();
	}

	/**
	 * Seed all valid tables in the migration manager
	 *
	 * @return void
	 */
	private function seed_tables(): void {
		$this->migration_manager->seed_tables(
			...$this->tables_to_exclude_from_seeding()
		);
	}

	/**
	 * Gets a list of all tables which should not be seeded.
	 *
	 * @return string[] Array of table names.
	 */
	private function tables_to_exclude_from_seeding(): array {
		/** @var Migration[] */
		$migrations = $this->migration_manager->get_migrations();

		return array_values(
			array_map(
				function( Migration $migration ): string {
					return $migration->get_table_name();
				},
				array_filter(
					$migrations,
					function( Migration $migration ):bool {
						return count( $migration->get_seeds() ) === 0
						|| false === $migration->seed_on_inital_activation();
					}
				)
			)
		);
	}
}
