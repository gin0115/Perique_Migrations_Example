<?php

declare(strict_types=1);

/**
 * Mock class used for activation which writes to options table
 *
 * @package PinkCrab\Perique\Migration
 * @author Glynn Quelch glynn@pinkcrab.co.uk
 * @since 0.0.1
 */

namespace PinkCrab\Perique\Migration\Tests\Fixtures;

use PinkCrab\Perique\Migration\Migration;
use PinkCrab\Table_Builder\Schema;

class Has_Seeds_Migration_But_Disabled extends Migration {

	public const TABLE_NAME = 'has_seeds_migration_disabled';
	public const SEED_DATA  = array(
		array( 'foo' => 'INVALID' ),
		array( 'foo' => 'INVALID' ),
	);

	protected function table_name(): string {
		return self::TABLE_NAME;
	}
	/**
	 * Defines the schema for the migration.
	 *
	 * @param Schema $schema_config
	 * @return void
	 */
	public function schema( Schema $schema_config ): void {
		$schema_config->column( 'bar' )->unsigned_int( 11 )->auto_increment();
		$schema_config->column( 'foo' )->text( 11 );
		$schema_config->index( 'bar' )->primary();
	}

	/**
	 * Defines the data to be seeded.
	 *
	 * @param array<string, mixed> $seeds
	 * @return array<string, mixed>
	 */
	public function seed( array $seeds ): array {
		return self::SEED_DATA;
	}

	/**
	 * DO NOT SEED
	 */
	public function seed_on_inital_activation(): bool {
		return false;
	}
}
