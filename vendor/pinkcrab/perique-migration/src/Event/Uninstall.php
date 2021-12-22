<?php

declare(strict_types=1);

/**
 * Deactivation event to be launched on Uninstall.
 *
 * @package PinkCrab\Perique\Migration\Plugin_Lifecycle
 * @author Glynn Quelch glynn@pinkcrab.co.uk
 * @since 0.0.1
 */

namespace PinkCrab\Perique\Migration\Event;

use PinkCrab\Plugin_Lifecycle\State_Event\Uninstall as State_Events_Uninstall;

class Uninstall implements State_Events_Uninstall {

	/**
	 * Array of tables to be dropped.
	 *
	 * @var string[]
	 */
	protected $tables = array();

	/**
	 * The migration log key to clear after dropping tables.
	 *
	 * @var string
	 */
	protected $migration_log_key;

	/** @param string[] $tables */
	public function __construct( array $tables, string $migration_log_key ) {
		$this->tables            = $tables;
		$this->migration_log_key = $migration_log_key;
	}

	/**
	 * Invokes the run method.
	 *
	 * @return void
	 */
	public function __invoke(): void {
		$this->run();

	}

	/**
	 * Runs the dropping of all valid tables.
	 *
	 * @return void
	 */
	public function run(): void {
		$this->remove_migration_log();
		$this->drop_tables();

	}

	/**
	 * Drops all tables.
	 *
	 * @return void
	 */
	protected function drop_tables() {
		/** @var \wpdb $wpdb */
		global $wpdb;

		// Temp disable warnings.
		$original_state = (bool) $wpdb->suppress_errors;
		$wpdb->suppress_errors( true );

		foreach ( $this->tables as $table ) {
			$wpdb->get_results( "DROP TABLE IF EXISTS {$table}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		}

		// Reset warnings to initial state.
		$wpdb->suppress_errors( $original_state );
	}

	/**
	 * Delete the migration log.
	 *
	 * @return void
	 */
	protected function remove_migration_log(): void {
		\delete_option( $this->migration_log_key );
	}
}
