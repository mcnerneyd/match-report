<?php
require_once('../lib/phpfastcache/phpfastcache.php');

$cachePath = SITEPATH."/tmp/cache";
if (!file_exists($cachePath)) mkdir($cachePath, 0755, true);

phpFastCache::setup("storage","auto");
phpFastCache::setup("path", SITEPATH."/tmp/cache");
phpFastCache::setup("securityKey", "cache.folder");

class Db {
	private static $instance = NULL;

	private function __construct() {}

	private function __clone() {}

	public static function getInstance() {
		if (!isset(self::$instance)) {
			try {
				$pdo_options[PDO::ATTR_ERRMODE] = PDO::ERRMODE_EXCEPTION;
				self::$instance = new PDO('mysql:host=localhost;dbname='.
					Config::get("config.database.name"),
					Config::get("config.database.username"),
					Config::get("config.database.password"),
				  $pdo_options);
				//debug("Connected to:".DB_DATABASE);
			} catch (Exception $e) {
				echo $e->message;
				throw new Exception("Failed to connected to database");
			}
		}
		return self::$instance;
	}
}

class Cache {
	private static $instance = NULL;
	private $cache;

	private function __construct() {
	 	$this->cache = phpFastCache('content');	
	}

	private function __clone() {}

	public static function getInstance() {
		if (!isset(self::$instance)) {
			self::$instance = new Cache();
		}
		
		return self::$instance;
	}

	public function get($filename, $timeout = 600) {
		//debug(print_r($this->cache->getInfo($filename), true));

		$info = $this->cache->getInfo($filename);

		if ($info)
			debug($filename."=".count($info['value'])." t=".$info['write_time']." ".$info['expired_in']." ".$info['expired_time']);
			else debug($filename."=no info");

		$flush = false;
		if (isset($_REQUEST['flush'])) {
			debug("Force flush");
			$flush = true;
		}

		if ($flush or is_null($info) or (time() > $info['write_time'] + rand($timeout, 1.5*$timeout))) {
			if ($flush or !isset($_REQUEST['one-cache-update-per-request'])) {
				$_REQUEST['one-cache-update-per-request'] = true;

				debug("Non-cache request: $filename");
				try {
					$src = file_get_contents($filename);

					if (!is_null($src)) {
						debug("Updating source for $filename");
						$this->cache->set($filename, $src, 40000000);		// Not using phpCache timeout, using $timeout parameter
					}
				} catch (Exception $e) {
					debug("Cache update failed: ".$e->getMessage());
				}
			}
		}

		$src = $this->cache->get($filename);

		debug("src:".substr($src, 0, 20));

		return $src;
	}
}
?>
