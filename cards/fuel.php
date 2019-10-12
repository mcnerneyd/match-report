<?php
// FuelPHP faking classes/functions

define("ADMIN_ROOT", "http://cards.leinsterhockey.ie/public");
$root=realpath(dirname(__FILE__)."/..");
define("DATAPATH", "$root/data");

require_once "util.php";

class Log {
	static function debug($msg) {
		log_write("DEBUG", $msg);
	}

	static function info($msg) {
		log_write("INFO", $msg);
	}

	static function warn($msg) {
		log_write("WARNING", $msg);
	}
}


function site() {
	$site = null;

	if ($site == null && isset($_REQUEST['site'])) {
		$site = $_REQUEST['site'];
	}

	if ($site == null && isset($_COOKIE['site'])) {
		$site = $_COOKIE['site'];
	}

	if ($site == "") $site = null;
	
	if (!$site) {
		return false;
	}

	return $site;
}

class Config {
	public static function get($path) {
		$configFile = DATAPATH.'/sites/'.site().'/config.json';

		$json = file_get_contents($configFile);

		$config = json_decode($json, true);

		$keys = explode('.', $path);
		array_shift($keys);

		$value = $config;
		foreach ($keys as $key) {
			if (!isset($value[$key])) {
				Log::warn("No such key $key from $path");
				return null;
			}
			$value = $value[$key];
		}

		return $value;
	}
}

class Uri { static function create($str) { return ADMIN_ROOT."/$str"; } } 
class Asset {
	static function js($files) { foreach ($files as $file) echo "<script src='".Uri::create("assets/js/$file")."'></script>\n"; }
	static function css($files) { foreach ($files as $file) echo "<link rel='stylesheet' href='".Uri::create("assets/css/$file")."'/>\n"; }
}
class Session {
	static function get($key) { return $_SESSION[$key]; }
}
class Auth {
	static function check() { return user(); }
	static function has_access($perm) { return in_array($perm, $_SESSION['perms'] ?: array()); }
}
