<?php
/* Raven/Sentry */
require_once APPPATH.'../lib/Raven/Autoloader.php';
Raven_Autoloader::register();
$sentry_client = new Raven_Client('https://0e648f1a6af5407985c427afb086e5bb:37b68176201d451a849bbbb4c81ec6f6@sentry.io/1242091');
$error_handler = new Raven_ErrorHandler($sentry_client);
$error_handler->registerExceptionHandler();
$error_handler->registerErrorHandler();
$error_handler->registerShutdownFunction();

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
\Fuel::$env = \Arr::get($_SERVER, 'FUEL_ENV', \Arr::get($_ENV, 'FUEL_ENV', \Fuel::DEVELOPMENT));

// Initialize the framework with the config file.
\Fuel::init('config.php');

require APPPATH."../lib/PHPExcel/PHPExcel/IOFactory.php";

require APPPATH.'classes/lib/upgrade.php';
require APPPATH.'classes/lib/util.php';

if (defined('DATAPATH')) {
	$globalLogPath = DATAPATH."logs/";
	if (!file_exists($globalLogPath)) {
		mkdir($globalLogPath, 0777, TRUE);
	}

	\Config::set('log_path', $globalLogPath);

	if (!defined('SITE')) {
		$site = Input::param('site', Session::get('site',null));

		if (!$site) {
			echo "Redirecting to login";
			Response::redirect("Login?site=none");
		}

		function sitepath() {
			$site = Input::param('site', Session::get('site',null));
			return DATAPATH."/sites/$site/";
		}

		$path = sitepath();

		if (!file_exists($path)) {
			echo "Bad site - redirecting to login";
			Response::redirect("Login?site=none");
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
	}
}

