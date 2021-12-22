<?php

declare(strict_types=1);

/**
 * Tests a table with multiple unique indexes.
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
use PinkCrab\Table_Builder\Table_Index;
use PinkCrab\PHPUnit_Helpers\Reflection;
use PinkCrab\Table_Builder\Table_Schema;
use PinkCrab\Table_Builder\Builders\DB_Delta;
use PinkCrab\Table_Builder\Engines\WPDB_DB_Delta\DB_Delta_Engine;

class Test_Table_With_Indexes extends WP_UnitTestCase {



	/**
	 * WPDB
	 *
	 * @var wpdb
	 */
	protected $wpdb;

	/**
	 * Undocumented variable
	 *
	 * @var \PinkCrab\Table_Builder\Interfaces\SQL_Schema
	 */
	protected $schema;


	public function setUp(): void {
		parent::setup();

		global $wpdb;
		$this->wpdb = $wpdb;

		$this->schema = new Schema( 'table_with_indexes' );
			$this->schema->column( 'id' )
				->type( 'INT' )
				->auto_increment()
				->nullable( false )
				->unsigned();
			
			$this->schema->column( 'user_id' )
				->type( 'varchar' )
				->length( 16 );
			
			$this->schema->column( 'user_email' )
				->type( 'varchar' )
				->length( 256 );
			
			$this->schema->column( 'created_on' )
				->type( 'datetime' )
				->default( 'CURRENT_TIMESTAMP' );
			
			$this->schema->column( 'last_updated' )
				->type( 'datetime' )
				->default( 'CURRENT_TIMESTAMP' );
			
			$this->schema->index( 'user_id', 'user_id' )->unique();
			$this->schema->index( 'user_email', 'user_email' )->unique();
			$this->schema->index( 'id' )->primary();
	}

	/**
	 * Test that the table is created.
	 *
	 * @return void
	 */
	public function test_can_create_table_with_wpdb_delta(): void {

		// Create table
		$builder = new Builder( new DB_Delta_Engine( $this->wpdb ) );
		$builder->create_table( $this->schema );

		// Grab the table column info. If not created, will fail.
		$table_details = $this->wpdb->get_results( 'SHOW COLUMNS FROM table_with_indexes;' );
		$this->assertCount( 5, $table_details );

		// Expected results.
		$expected = array(
			'id'           => array(
				'Type'    => 'int(10) unsigned',
				'Null'    => 'NO',
				'Key'     => 'PRI',
				'Default' => null,
				'Extra'   => 'auto_increment',
			),
			'user_id'      => array(
				'Type'    => 'varchar(16)',
				'Null'    => 'NO',
				'Key'     => 'UNI',
				'Default' => null,
				'Extra'   => '',
			),
			'user_email'   => array(
				'Type'    => 'varchar(256)',
				'Null'    => 'NO',
				'Key'     => 'UNI',
				'Default' => null,
				'Extra'   => '',
			),
			'created_on'   => array(
				'Type'  => 'datetime',
				'Null'  => 'NO',
				'Key'   => '',
				'Extra' => '',
			),
			'last_updated' => array(
				'Type'  => 'datetime',
				'Null'  => 'NO',
				'Key'   => '',
				'Extra' => '',
			),
		);

		foreach ( $table_details as $column ) {
			$this->assertArrayHasKey( $column->Field, $expected );
			$this->assertEquals( $expected[ $column->Field ]['Type'], $column->Type );
			$this->assertEquals( $expected[ $column->Field ]['Null'], $column->Null );
			$this->assertEquals( $expected[ $column->Field ]['Key'], $column->Key );
			if ( isset( $expected[ $column->Field ]['Default'] ) ) { // Due to differences in mysql ver cant test datetimes current_timestamp() vs CURRENT_TIMESTAMP
				$this->assertEquals( $expected[ $column->Field ]['Default'], $column->Default );
			}
			$this->assertEquals( $expected[ $column->Field ]['Extra'], $column->Extra );
		}
	}

}
