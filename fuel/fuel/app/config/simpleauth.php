<?php
/**
 * Fuel
 *
 * Fuel is a fast, lightweight, community driven PHP5 framework.
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

	'db_connection' => null,
	'db_write_connection' => null,
	'table_name' => 'user',
	'table_columns' => null,
	'guest_login' => true,
	'multiple_logins' => false,
	'remember_me' => array(
		'enabled' => true,
		'cookie_name' => 'rmcookie',
		'expiration' => 86400 * 31,
	),

	'groups' => array(
		 -1   => array('name' => 'Banned', 'roles' => array('banned')),
		 0    => array('name' => 'Guests', 'roles' => array()),
		 1    => array('name' => 'Users', 'roles' => array('user')),
		 2    => array('name' => 'Umpires', 'roles' => array('umpire')),
		 25   => array('name' => 'Secretaries', 'roles' => array('user', 'secretary')),
		 50   => array('name' => 'Moderators', 'roles' => array('user', 'moderator')),
		 99  => array('name' => 'Administrators', 'roles' => array('user', 'moderator', 'admin', 'secretary', 'registration')),
		 100  => array('name' => 'Superusers', 'roles' => array('admin')),
	),

	'roles' => array(
		'registration' => array(),
		 'admin'  => array('nav' => array('admin'), 'admin' => array('all')),
		 'secretary' => array('registration' => array('view', 'post'),'user'=> array('refreshpin')),
		 'umpire'  => array('nav' => array('umpire')),
		 'user'  => array('comments' => array('create', 'read')),
		 'moderator'  => array('comments' => array('update', 'delete')),
		 '#'  => array('website' => array('read')),
		 'banned' => false,
		 'super' => true,
	),
	'login_hash_salt' => 'put_some_salt_in_here',
	'username_post_key' => 'user',
	'password_post_key' => 'pin',
);
