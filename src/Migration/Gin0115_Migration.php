<?php

declare(strict_types=1);

/**
 * Gin0115 Table model
 */

namespace Gin0115\Perique_Migrations_Example\Migration;

use PinkCrab\Table_Builder\Schema;
use PinkCrab\Perique\Migration\Migration;
use PinkCrab\Perique\Application\App_Config;
use Gin0115\Perique_Migrations_Example\Service\Some_Service;

class Gin0115_Migration extends Migration {

	/** @var App_Config */
	protected $app_config;

	/** @var Some_Service */
	protected $some_service;

	public function __construct( App_Config $app_config, Some_Service $some_service ) {
		$this->app_config   = $app_config;
		$this->some_service = $some_service;

		// The parents constructor should always be called after setting any dependencies.
		parent::__construct();
	}

	/**
	 * Sets the table name, from App Config
	 *
	 * @return string
	 */
	protected function table_name(): string {
		return $this->app_config->db_tables( 'gin0115' );
	}

	/**
	 * Defines the schema for the migration.
	 *
	 * @param Schema $schema_config
	 * @return void
	 */
	public function schema( Schema $schema_config ): void {
		$schema_config->column( 'id' )
			->unsigned_int( 11 )
			->auto_increment();

		$schema_config->column( 'foo' )
			->text( 24 );

		$schema_config->column( 'bar' )
			->text( 24 );

		$schema_config->index( 'id' )
			->primary();
	}

	/**
	 * Seed table using data from service.
	 *
	 * @param array<string,mixed>[] $seeds
	 * @return array<string,mixed>[]
	 */
	public function seed( array $seeds ): array {
		return $this->some_service->generate_migration_seeds();
	}

	/**
	 * This table should NOT be dropped when its deactivated
	 *
	 * @return bool
	 */
	public function drop_on_deactivation(): bool {
		return false;
	}

	/**
	 * This table should be dropped when its uninstalled
	 *
	 * @return bool
	 */
	public function drop_on_uninstall(): bool {
		return true;
	}
}
