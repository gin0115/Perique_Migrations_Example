<?php

declare(strict_types=1);

/**
 * This is Some Service, it serves some purpose.
 */

namespace Gin0115\Perique_Migrations_Example\Service;

class Some_Service {

	/**
	 * Returns some data used to seed the Gin0115 table.
	 *
	 * @return array{foo:string,bar:string}[]
	 */
	public function generate_migration_seeds(): array {
		return array(
			array(
				'foo' => 'apple',
				'bar' => 'green',
			),
			array(
				'foo' => 'orange',
				'bar' => 'plumb',
			),
		);
	}
}
