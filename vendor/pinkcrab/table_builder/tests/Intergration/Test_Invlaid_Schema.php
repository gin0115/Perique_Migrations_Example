<?php

declare(strict_types=1);

/**
 * Test that an exception is thrown if attempting to create or drop from an invalid schema.
 *
 * @since 0.1.0
 * @author Glynn Quelch <glynn.quelch@gmail.com>
 * @license http://www.opensource.org/licenses/mit-license.html  MIT License
 * @package PinkCrab\WPDB_Table_Builder
 */

namespace PinkCrab\Table_Builder\Tests;

use WP_UnitTestCase;
use PinkCrab\Table_Builder\Schema;
use PinkCrab\Table_Builder\Builder;
use PinkCrab\Table_Builder\Engines\WPDB_DB_Delta\DB_Delta_Engine;

class Test_Invlaid_Schema extends WP_UnitTestCase {

    protected $builder;

    protected $scehma;

    public function setup()
    {
        $this->schema = new Schema('test_drop_table', function(Schema $schema): void{
            $schema->column('id')->unsigned_int(11)->auto_increment();
            $schema->column('name')->int(11);
            $schema->index('name')->primary();
            $schema->index('id')->primary();
        });

        global $wpdb;
        $this->builder = new Builder( new DB_Delta_Engine( $wpdb ) );
    }
    
    
    /** @testdox It should not be possible to create a table from an invalid schema. Attempting to do so should generate an error and prevent the table from being created. */
    public function test_throws_exception_attempting_to_create_from_invalid_schema(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionCode(1);      
        $this->expectExceptionMessageRegExp('/Failed to create table/'); 
		$this->builder->create_table( $this->schema );
    }

    /** @testdox It should not be possible to drop a table from an invalid schema. Attempting to do so should generate an error and prevent the table from being created. */
    public function test_throws_exception_attempting_to_drop_from_invalid_schema(): void
    {
        $this->expectException(\Exception::class);       
        $this->expectExceptionCode(2);       
        $this->expectExceptionMessageRegExp('/Failed to drop table/'); 
		$this->builder->drop_table( $this->schema );
    }
}