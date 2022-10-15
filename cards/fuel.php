<?php
// FuelPHP faking classes/functions

define('DOCROOT', __DIR__.DIRECTORY_SEPARATOR.'/../');
define('PKGPATH', realpath(DOCROOT.'/fuel/packages/').DIRECTORY_SEPARATOR);
define('APPPATH', DOCROOT.'/fuel/app/');

define("ADMIN_ROOT", "/");
define("DATAPATH", DOCROOT."/data");

class Fuel {
  static $env = "development";
}

// https://stackoverflow.com/questions/6768793/get-the-full-url-in-php
function url_origin( $s, $use_forwarded_host = false )
{
    $ssl      = ( ! empty( $s['HTTPS'] ) && $s['HTTPS'] == 'on' );
    $sp       = strtolower( $s['SERVER_PROTOCOL'] );
    $protocol = substr( $sp, 0, strpos( $sp, '/' ) ) . ( ( $ssl ) ? 's' : '' );
    $port     = $s['SERVER_PORT'];
    $port     = ( ( ! $ssl && $port=='80' ) || ( $ssl && $port=='443' ) ) ? '' : ':'.$port;
    $host     = ( $use_forwarded_host && isset( $s['HTTP_X_FORWARDED_HOST'] ) ) ? $s['HTTP_X_FORWARDED_HOST'] : ( isset( $s['HTTP_HOST'] ) ? $s['HTTP_HOST'] : null );
    $host     = isset( $host ) ? $host : $s['SERVER_NAME'] . $port;
    return $protocol . '://' . $host;
}

define('BASE', url_origin($_SERVER));

//-----------------------------------------------------------------------------
// Write a log entry to the fuelphp logs
function log_write($level, $msg) {
	try {
		$filename = DATAPATH."/logs/".date("Y/m/d").".php";
		$dir = dirname($filename);

		if (!file_exists($dir)) {
			mkdir($dir, 0777, true);
		}

		$msg = "$level - ".date("Y-m-d H:i:s")." --> # $msg\n";

		if (!file_exists($filename)) {
			$msg = "<?php defined('COREPATH') or exit('No direct script access allowed'); ?".">\n\n$msg"; 
		}

		file_put_contents($filename, $msg, FILE_APPEND);
	} catch (Exception $e) {
		echo "Log Failure: ".$e->getMessage();
	}

	return true;
}

class Log {
	static function debug($msg) {
		log_write("DEBUG", $msg);
	}

	static function info($msg) {
		log_write("INFO", $msg);
	}

	static function error($msg) {
		log_write("ERROR", $msg);
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

  if ($site == null && isset($_SESSION['site'])) {
		$site = $_SESSION['site'];
  }

	if ($site == "") $site = null;
	
	if (!$site) {
		return false;
	}

	return $site;
}

class Config {
	public static function get($path, $def = null) {
		if ($path === 'base_url') {
			return $_SESSION['base-url'];
		}

		$configFile = realpath(DATAPATH.'/config.json');

		$json = file_get_contents($configFile);

		$config = json_decode($json, true);

		$keys = explode('.', $path);
		array_shift($keys);

		$value = $config;
		foreach ($keys as $key) {
			if (!isset($value[$key])) {
				Log::warn("No such key $key from $path");
				return $def;
			}
			$value = $value[$key];
		}

		return $value;
	}
}

class Arr {
	public static function get($arr, $key, $def) {
		if (isset($arr[$key])) {
			return $arr[$key];
		}

		return $def;
	}
}

class Uri { static function create($str) { return BASE."/$str"; } } 
class Asset {
	static function js($files) { foreach ($files as $file) echo "<script src='".Uri::create("assets/js/$file")."'></script>\n"; }
	static function css($files) { foreach ($files as $file) echo "<link rel='stylesheet' href='".Uri::create("assets/css/$file")."'/>\n"; }
}
class Session {
	static function get($key, $d = null) { 
    if (!isset($_SESSION[$key])) return $d; 
    return $_SESSION[$key]; 
  }
}
class Auth {
	static function check() { return user(); }
	static function has_access($perm) { return in_array($perm, $_SESSION['perms'] ?? array()); }
}
class Date {
	static function create_from_string($str) {
		return strtotime($str);
	}
}

