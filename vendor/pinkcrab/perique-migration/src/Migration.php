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

use PinkCrab\DB_Migration\Database_Migration;
use PinkCrab\Table_Builder\Schema;

abstract class Migration extends Database_Migration {


	public function __construct() {
		$this->table_name = $this->table_name();
		$this->schema     = new Schema( $this->table_name, array( $this, 'schema' ) );
		$this->seed_data  = $this->seed( array() );
	}

	abstract protected function table_name(): string;

	/**
	 * Is this table dropped on deactivation
	 *
	 * Defaults to false.
	 *
	 * @return bool
	 */
	public function drop_on_deactivation(): bool {
		return false;
	}

	/**
	 * Drop table on uninstall.
	 *
	 * Defaults to false.
	 *
	 * @return bool
	 */
	public function drop_on_uninstall(): bool {
		return false;
	}

	/**
	 * Should this migration be seeded on activation.
	 *
	 * Defaults to true.
	 *
	 * @return bool
	 */
	public function seed_on_inital_activation(): bool {
		return true;
	}
}
