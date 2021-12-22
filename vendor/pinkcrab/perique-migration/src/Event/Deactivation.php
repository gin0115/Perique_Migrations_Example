<?php

declare(strict_types=1);

/**
 * Deactivation event to be launched on deactivation.
 *
 * @package PinkCrab\Perique\Migration\Plugin_Lifecycle
 * @author Glynn Quelch glynn@pinkcrab.co.uk
 * @since 0.0.1
 */

namespace PinkCrab\Perique\Migration\Event;

use PinkCrab\Perique\Migration\Migration;
use PinkCrab\DB_Migration\Migration_Manager;
use PinkCrab\Plugin_Lifecycle\State_Event\Deactivation as State_Events_Deactivation;

class Deactivation implements State_Events_Deactivation {

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
		$this->drop_tables();
	}

	/**
	 * Drop all tables in migration manager
	 *
	 * @return void
	 */
	private function drop_tables(): void {
		$this->migration_manager->drop_tables(
			...$this->tables_to_exclude_from_drop_on_deactivation()
		);
	}

	/**
	 * Gets a list of all tables which should not be seeded.
	 *
	 * @return string[] Array of table names.
	 */
	private function tables_to_exclude_from_drop_on_deactivation(): array {
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
						return false === $migration->drop_on_deactivation();
					}
				)
			)
		);
	}
}
