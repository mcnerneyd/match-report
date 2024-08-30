<?php
// FuelPHP faking classes/functions

define('DOCROOT', __DIR__.DIRECTORY_SEPARATOR.'/../');
#define('FUELPATH', '/var/www/fuelphp-1.8.2');
define('FUELPATH', getenv('FUELPATH') ?: DOCROOT.'/fuel');
define('PKGPATH', FUELPATH.'/packages/');
define('APPPATH', FUELPATH.'/app/');

define("ADMIN_ROOT", "/");
define("DATAPATH", getenv('DATAPATH') ?: DOCROOT."/data");


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
		$filename = DATAPATH."/logs/matchcard.log";
		$dir = dirname($filename);

		if (!file_exists($dir)) {
			mkdir($dir, 0777, true);
		}

		$username = Session::get('user', null);
		if ($username == null) $username = "";
		else $username = " @".$username->username;
		$output = date('Y-m-d\\TH:i:s')." [".substr($level, 0, 1)."]$username ".$msg;
		$output = str_replace(PHP_EOL, PHP_EOL."   | ", $output);
		$msg = $output.PHP_EOL;


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
		//log_write("DEBUG", $msg);
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

	static function warning($msg) {
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
		$site = $_SESSION['site']['name'];
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
			return isset($_SESSION['base-url']) ? $_SESSION['base-url'] : null;
		}

		if (strpos($path, "section.") === 0 || $path === "section") {
			if (!isset($_SESSION['site'])) {
				return $def;
			}
			$site = $_SESSION['site']['name'];

			$configFile = DATAPATH.'/sections/'.$site.'/config.json';
		} else {
			$configFile = DATAPATH.'/config.json';
		}

		$configFile = realpath($configFile);
		if (!file_exists($configFile)) return $def;

		Log::debug("Config File:".$configFile);
		$json = file_get_contents($configFile);

		$config = json_decode($json, true);

		$keys = explode('.', $path);
		array_shift($keys);

		$value = $config;
		foreach ($keys as $key) {
			if (!isset($value[$key])) {
				Log::warn("No such key $key from $path in $configFile");
				return $def;
			}
			$value = $value[$key];
		}

		return $value;
	}
}

class Arr {
	public static function get($arr, $key, $def = false) {

		$b = strpos($key, ".");
		$balance = null;

		if ($b) {
			$balance = substr($key, $b + 1);
			$key = substr($key, 0, $b);
		}

		if (isset($arr[$key])) {
			if ($balance) {
				return self::get($arr[$key], $balance);
			} else {
				return $arr[$key];
			}
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
	static function get($key = null, $d = null) {
	if ($key == null) return $_SESSION; 
    if (!isset($_SESSION[$key])) return $d; 
    return $_SESSION[$key]; 
  }
}
class Auth {
	static function check() { return user(); }
	static function has_access($perm) { return in_array($perm, $_SESSION['perms'] ?? array()); }

	static function get_user_id() { 
		$username = Session::get("username");

		return $username == null ? false : array("cards_auth", $username);
	}
}
class Date {
	static function create_from_string($str) {
		return strtotime($str);
	}
}

