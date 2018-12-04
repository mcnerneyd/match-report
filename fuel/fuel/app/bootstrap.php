<?php
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

require APPPATH."../../../../lib/PHPExcel/PHPExcel/IOFactory.php";
require APPPATH.'classes/lib/util.php';

$site = Session::get('site',null);
if ($site) {
	$path = APPPATH."../../../sites/$site";
	if (file_exists($path)) {
		Config::load("$path/config.json", 'hockey');
	}
}
