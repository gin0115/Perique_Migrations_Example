<?php

declare(strict_types=1);

/**
 * Mock class used for deactivation which is NOT set to drop table.
 *
 * @package PinkCrab\Perique\Migration
 * @author Glynn Quelch glynn@pinkcrab.co.uk
 * @since 0.0.1
 */

namespace PinkCrab\Perique\Migration\Tests\Fixtures;

use PinkCrab\Perique\Migration\Migration;
use PinkCrab\Table_Builder\Schema;

class Not_Drop_On_Deactivation_Migration extends Migration {

	public const TABLE_NAME = 'not_drop_on_deactivation';

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

	public function seed_on_inital_activation(): bool {
		return false;
	}

	/**
	 * DO DROP
	 */
	public function drop_on_deactivation(): bool {
		return false;
	}
}
