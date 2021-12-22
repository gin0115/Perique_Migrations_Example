<?php

/**
 * Plugin Name: PinkCrab Perique Migrations Example Plugin
 * Plugin URI: https://github.com/gin0115/Perique_Migrations_Example
 * Description: This is an example project using Perique and Perique Migrations, for more details please visit https://github.com/Pink-Crab/Perique_Migrations
 * Version: 1.0.0
 * Author: Glynn Quelch
 * Author URI: https://github.com/gin0115/Perique_Migrations_Example
 * Text Domain: gin0115-pinkcrab-examples
 * Domain Path: /languages
 * Tested up to: 5.8
 * License: MIT
 **/

use PinkCrab\Perique\Migration\Migrations;
use PinkCrab\Perique\Application\App_Factory;
use PinkCrab\Plugin_Lifecycle\Plugin_State_Controller;
use Gin0115\Perique_Migrations_Example\Migration\Gin0115_Migration;

require_once __DIR__ . '/vendor/autoload.php';

// Boot a barebones version of perique
$app = ( new App_Factory() )
	->with_wp_dice()
	->app_config(
		// Usually you do with as its own file with more settings!
		array(
			'db_tables' => array(
				'gin0115' => 'gin0115_table',
			),
		)
	)
	->boot();

// Setup Plugin Life Cycle and Migration services.
$plugin_state_controller = new Plugin_State_Controller( $app );
$migrations              = new Migrations( $plugin_state_controller, 'perique_migrations_example_a' );

// Add our migration
$migrations->add_migration( Gin0115_Migration::class );

// Finalise.
$migrations->done();
$plugin_state_controller->finalise( __FILE__ );
