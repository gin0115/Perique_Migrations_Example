<?php

declare(strict_types=1);

/**
 * Abstract class for all Migrations
 *
 * @package PinkCrab\Perique\Migration\Plugin_Lifecycle
 * @author Glynn Quelch glynn@pinkcrab.co.uk
 * @since 0.0.1
 */

namespace PinkCrab\Perique\Migration;

use PinkCrab\DB_Migration\Factory;
use PinkCrab\Perique\Interfaces\DI_Container;
use PinkCrab\DB_Migration\Migration_Manager;
use PinkCrab\Plugin_Lifecycle\Plugin_State_Controller;
use PinkCrab\Perique\Migration\Event\Activation;
use PinkCrab\Perique\Application\App;
use PinkCrab\Perique\Migration\Event\Deactivation;
use PinkCrab\Perique\Migration\Event\Uninstall;
use PinkCrab\Perique\Migration\Migration;


class Migrations {

	/**
	 * Holds the instance of the plugin state controller
	 *
	 * @var Plugin_State_Controller
	 */
	protected $plugin_state_controller;

	/**
	 * The migration manager instance.
	 *
	 * @var Migration_Manager
	 */
	protected $migration_manager;

	/**
	 * Access to Perique DI Container
	 *
	 * @var DI_Container
	 */
	protected $di_container;

	/**
	 * The migration Manager
	 *
	 * @var ?string
	 */
	protected $migration_log_key;

	/**
	 * Use prefix
	 *
	 * @var string|null
	 */
	protected $prefix;

	/**
	 * All migrations
	 *
	 * @var Migration[]
	 */
	protected $migrations = array();

	/**
	 * Creates an instance of the Migrations Service.
	 *
	 * @param Plugin_State_Controller $plugin_state_controller
	 */
	public function __construct( Plugin_State_Controller $plugin_state_controller, ?string $migration_log_key = null ) {
		$this->plugin_state_controller = $plugin_state_controller;
		$this->migration_log_key       = $migration_log_key;
		$this->di_container            = $plugin_state_controller->get_app()->get_container();
	}

	public function set_migration_log_key( string $log_key ): self {
		$this->migration_log_key = $log_key;
		return $this;
	}

	/**
	 * Set the migration manager instance.
	 *
	 * @param Migration_Manager $migration_manager  The migration manager instance.
	 * @return self
	 */
	public function set_migration_manager( Migration_Manager $migration_manager ):self {
		$this->migration_manager = $migration_manager;
		return $this;
	}

	/**
	 * Pushed an unpopulated migration to the stack
	 *
	 * @param class-string<Migration>|Migration $migration
	 * @return self
	 */
	public function add_migration( $migration ): self {
		if ( ! is_subclass_of( $migration, Migration::class ) ) {
			throw Migration_Exception::none_migration_type( \serialize( $migration ) ); //phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_serialize, used for exception messages
		}

		$migration = $this->maybe_construct_migration( $migration );
		if ( null === $migration ) {
			throw Migration_Exception::failed_to_construct_migration( 'Invalid after construction' );
		}

		$this->migrations[] = $migration;
		return $this;
	}

	/**
	 * Attempts to create a Migration if string passed
	 *
	 * @param class-string<Migration>|Migration $migration
	 * @return Migration|null
	 */
	protected function maybe_construct_migration( $migration ): ?Migration {
		if ( is_string( $migration ) ) {
			$migration_string = $migration;
			try {
				$migration = $this->di_container->create( $migration_string );
			} catch ( \Throwable $th ) {
				var_dump($th);
				throw Migration_Exception::failed_to_construct_migration( $migration_string );
			}
		}

		return is_object( $migration ) && is_a( $migration, Migration::class )
		? $migration
		: null;
	}

	/**
	 * Runs the process.
	 *
	 * @return self
	 */
	public function done(): self {
		// Bail if no migrations.
		if ( 0 === count( $this->get_migrations() ) ) {
			return $this;
		}

		// Set with a fallback Migration Manager if not set.
		if ( null === $this->migration_manager ) {
			$this->migration_manager = Factory::manager_with_db_delta( $this->migration_log_key );
		}

		// Register all migrations and hooks.
		$this->populate_migration_manager();
		$this->set_activation_calls();
		$this->set_deactivation_calls();
		$this->set_uninstall_calls();

		return $this;
	}

	/**
	 * Populates the migration with all migrations.
	 *
	 * @return void
	 */
	private function populate_migration_manager(): void {
		foreach ( $this->migrations as $migration ) {
			$this->migration_manager->add_migration( $migration );
		}
	}

	/**
	 * Get all migrations
	 *
	 * @return Migration[]
	 */
	public function get_migrations(): array {
		return $this->migrations;
	}

	/**
	 * Registers all actions to carry out on activation.
	 *
	 * @return void
	 */
	private function set_activation_calls(): void {
		$this->plugin_state_controller->event( new Activation( $this->migration_manager ) );
	}

	/**
	 * Registers the deactivation hook if any migrations are set to drop on
	 * deactivation.
	 *
	 * @return void
	 */
	private function set_deactivation_calls(): void {
		// Check we have valid deactivation calls.
		$drop_on_deactivation_migrations = array_filter(
			$this->migrations,
			function( Migration $migration ): bool {
				return $migration->drop_on_deactivation() === true;
			}
		);
		if ( count( $drop_on_deactivation_migrations ) >= 1 ) {
			$this->plugin_state_controller->event( new Deactivation( $this->migration_manager ) );
		}
	}

	/**
	 * Registers the uninstall hook if any migrations are set to drop on
	 * uninstall.
	 *
	 * @return void
	 */
	private function set_uninstall_calls(): void {
		// Check we have valid uninstall calls.
		$drop_on_uninstall_migrations = array_filter(
			$this->migrations,
			function( Migration $migration ): bool {
				return $migration->drop_on_uninstall() === true;
			}
		);
		if ( count( $drop_on_uninstall_migrations ) >= 1 ) {
			// Get all table names to be dropped.
			$table_names = \array_map(
				function( Migration $migration ): string {
					return $migration->get_table_name();
				},
				$drop_on_uninstall_migrations
			);

			// Register the uninstall event.
			$this->plugin_state_controller->event(
				new Uninstall(
					$table_names,
					$this->migration_manager->migation_log()->get_log_key()
				)
			);
		}
	}

}
