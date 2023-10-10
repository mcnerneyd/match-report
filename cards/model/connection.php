<?php
class Db {
	private static $instance = NULL;

	private function __construct() {}

	private function __clone() {}

	public static function getInstance() {
		if (!isset(self::$instance)) {
			try {
				$pdo_options[PDO::ATTR_ERRMODE] = PDO::ERRMODE_EXCEPTION;
				self::$instance = new PDO('mysql:host='.Config::get("config.database.host", "localhost").';dbname='.
					Config::get("config.database.name"),
					Config::get("config.database.username"),
					Config::get("config.database.password"),
				  $pdo_options);
				//debug("Connected to:".DB_DATABASE);
			} catch (Exception $e) {
				print_r($e);
				throw new Exception("Failed to connected to database");
			}
		}
		return self::$instance;
	}
}

?>
