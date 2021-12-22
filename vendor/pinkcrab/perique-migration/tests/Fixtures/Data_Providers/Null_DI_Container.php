<?php

declare(strict_types=1);

/**
 * A DI Container which lets you set the return of calling create()
 *
 * @package PinkCrab\Perique\Migration
 * @author Glynn Quelch glynn@pinkcrab.co.uk
 * @since 0.0.1
 */

namespace PinkCrab\Perique\Migration\Tests\Fixtures\Data_Providers;

use PinkCrab\Perique\Interfaces\DI_Container;

class Null_DI_Container implements DI_Container {
	public $returns = null;

	public function __construct( $returns = null ) {
		$this->returns = $returns;
	}

	public function addRule( string $name, array $rule ): DI_Container {
		return $this;
	}
	public function addRules( array $rules ): DI_Container {
		return $this;
	}
	public function create( string $name, array $args = array() ) {
		return null;
	}
	public function get( $id ) {}
	public function has( $id ) {}
}
