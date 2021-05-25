<?php
/* Raven/Sentry */
require_once PKGPATH.'Raven/Autoloader.php';
Raven_Autoloader::register();
$sentry_client = new Raven_Client('https://0e648f1a6af5407985c427afb086e5bb:37b68176201d451a849bbbb4c81ec6f6@sentry.io/1242091');
$error_handler = new Raven_ErrorHandler($sentry_client);
$error_handler->registerExceptionHandler();
$error_handler->registerErrorHandler();
$error_handler->registerShutdownFunction();

function ensurePath($path) {
  if (!file_exists($path)) {
    mkdir($path, 0777, TRUE);
  }
  return realpath($path);
}

define('DATAPATH', DOCROOT.'/data/');

ensurePath(DATAPATH);
ensurePath(DATAPATH."logs/");
$sectionBase = ensurePath(DATAPATH."sections/");

// Bootstrap the framework DO NOT edit this
require COREPATH.'bootstrap.php';

\Autoloader::add_classes(array(
	// Add classes you want to override here
	// Example: 'View' => APPPATH.'classes/view.php',
));

// Register the autoloader
\Autoloader::register();

/**
 * Your environment.  Can be set to any of the following:
 *
 * Fuel::DEVELOPMENT
 * Fuel::TEST
 * Fuel::STAGING
 * Fuel::PRODUCTION
 */
\Fuel::$env = \Arr::get($_SERVER, 'FUEL_ENV', \Arr::get($_ENV, 'FUEL_ENV', \Fuel::PRODUCTION));

// Initialize the framework with the config file.
\Fuel::init('config.php');

Log::info("Request: ".$_SERVER['REQUEST_METHOD']." ".$_SERVER['REQUEST_URI']." ->".$_SERVER['PHP_SELF']);
$rq = \Request::forge();
Log::info("Route: ".print_r(\Router::process($rq, true), true));

require PKGPATH."PHPExcel/PHPExcel/IOFactory.php";

require APPPATH.'classes/lib/upgrade.php';
require APPPATH.'classes/lib/util.php';

Config::load(DATAPATH."config.json", 'config');

$user = Session::get('user', null);
Log::info("Checking for user");
if ($user) {
  Log::info("User set: ".$user['username']);
  if ($user->section) {
    Log::info("Section: ".$user->section['name']);
    $sectionPath = ensurePath($sectionBase."/".($user->section['name']));
    $sectionConfig = $sectionPath."/config.json";
    if (!file_exists($sectionConfig)) {
      Log::info("Initializing config file $sectionConfig");
      file_put_contents($sectionConfig, "{}");
    }
    Config::load($sectionConfig, 'section');
    Config::set('section.name', $user->section['name']);
    Log::info(print_r(Config::get('section', array()), true));
  }
}

/*	if (!defined('section')) {
		$section = Input::param('section', Session::get('section',null));

		if (!$section) {
			echo "Redirecting to login";
			Response::redirect("Login?section=none");
		}

		function sectionpath() {
			$section = Input::param('section', Session::get('section',null));
			return DATAPATH."/sections/$section/";
		}

		$path = sectionpath();

		if (!file_exists($path)) {
			echo "Bad section - redirecting to login";
			Response::redirect("Login?section=none");
		}

		if ($path) {
			define('CONFIG_FILE', "$path/config.json");
			if (file_exists($path)) {
				Config::load("$path/config.json", 'config');
			}
		}

		$logPath = $path."logs/";

		if (!file_exists($logPath)) {
			mkdir($logPath, 0777, TRUE);
		}

		\Config::set('log_path', $logPath);
	}*/
