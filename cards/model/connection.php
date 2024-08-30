<?php
class Db {
	private static $instance = NULL;

	private function __construct() {}

	private function __clone() {}

	public static function getInstance() {
		if (!isset(self::$instance)) {
			try {
				$pdo_options[PDO::ATTR_ERRMODE] = PDO::ERRMODE_EXCEPTION;
				self::$instance = new PDO('mysql:host='.\Arr::get($_ENV, 'DB_HOST', Config::get("config.database.host", "localhost")).';dbname='.
					\Arr::get($_ENV, 'DB_NAME', Config::get("config.database.name", 'hockey')),
					\Arr::get($_ENV, 'DB_USER', Config::get("config.database.username")),
					\Arr::get($_ENV, 'DB_PASSWORD', Config::get("config.database.password")),
				  $pdo_options);
			} catch (Exception $e) {
				print_r($e);
				throw new Exception("Failed to connect to database");
			}
		}
		return self::$instance;
	}
}