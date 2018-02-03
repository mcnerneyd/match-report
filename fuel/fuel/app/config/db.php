<?php
/**
 * Part of the Fuel framework.
 *
 * @package    Fuel
 * @version    1.8
 * @author     Fuel Development Team
 * @license    MIT License
 * @copyright  2010 - 2016 Fuel Development Team
 * @link       http://fuelphp.com
 */

/**
 * NOTICE:
 *
 * If you need to make modifications to the default configuration, copy
 * this file to your app/config folder, and make them in there.
 *
 * This will allow you to upgrade fuel without losing your custom config.
 */

return array(

	//'active' => 'test',

	'lhamen' => array(
		'type'        => 'mysqli',
		'connection'  => array(
			'hostname'   => 'localhost',
			'port'       => '3306',
			'username'   => 'registration',
			'password'   => 'password',
			'database'   => 'registration',
			'persistent' => false,
			'compress' => false,
		),
		'profiling' => true,
		'table_prefix' => '',
	),

	'lhaladies' => array(
		'type'        => 'mysqli',
		'connection'  => array(
			'hostname'   => 'localhost',
			'port'       => '3306',
			'username'   => 'lregdb',
			'password'   => 'password',
			'database'   => 'registration_ladies',
			'persistent' => false,
			'compress' => false
		),
		'table_prefix' => '',
	),

	'test' => array(
		'type'        => 'mysqli',
		'connection'  => array(
			'hostname'   => 'localhost',
			'port'       => '3306',
			'username'   => 'test',
			'password'   => 'password',
			'database'   => 'test',
			'persistent' => false,
			'compress' => false
		),
		'profiling' => true,
		'table_prefix' => '',
	),
);
