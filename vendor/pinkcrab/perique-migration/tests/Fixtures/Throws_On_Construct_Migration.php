<?php

declare(strict_types=1);

/**
 * Mock migration that throws an exception on construct
 *
 * @package PinkCrab\Perique\Migration
 * @author Glynn Quelch glynn@pinkcrab.co.uk
 * @since 0.0.1
 */

namespace PinkCrab\Perique\Migration\Tests\Fixtures;

use PinkCrab\Perique\Migration\Migration;
use PinkCrab\Table_Builder\Schema;

class Throws_On_Construct_Migration extends Migration {

	public const TABLE_NAME = 'throw_on_con';

    public function __construct() {
        throw new \Exception("Throws_On_Construct_Migration");
        
    }

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
		$schema_config->column( 'id' )->unsigned_int( 11 )->auto_increment();
		$schema_config->column( 'user' )->varchar( 11 );
		$schema_config->index('id')->primary();
	}
}