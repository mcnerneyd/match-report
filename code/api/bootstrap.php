<?php

// Bootstrap the framework DO NOT edit this
require COREPATH . 'bootstrap.php';

\Autoloader::add_classes(array(
    // Add classes you want to override here
    // Example: 'View' => APPPATH.'classes/view.php',
    'Log' => APPPATH . 'classes/log.php',
));

// Register the autoloader
\Autoloader::register();

require APPPATH . 'vendor/autoload.php';
\Sentry\init([
    'dsn' => 'https://773a5c2c3fc64be3961a669bf217015c@o48105.ingest.sentry.io/103038',
    'before_send' => function (\Sentry\Event $event, ?\Sentry\EventHint $hint): ?\Sentry\Event {
        // Ignore the event if the original exception is an instance of MyException
        if ($hint !== null && $hint->exception instanceof HttpNotFoundException) {
            return null;
	}
	if ($event->getMessage() != null) {
        if (strpos($event->getMessage(), "Module 'curl' already loaded") !== false) {
            return null;
        }
        if (strpos($event->getMessage(), "file_get_contents(): Unable to find the wrapper") !== false) {
            return null;
	}
	}

    // If you get this error (which happens before the FuelPhp error handler is loaded):
    // "There is no security.output_filter defined in your application config file"
    // Then turn on this:
	//print_r($event);

        Log::error("Sentry severity " . print_r($event->getLevel(), true));

        if ($event->getLevel()->isEqualTo(\Sentry\Severity::warning())) {
            Log::error("Sentry warning (ignoring): " . $event->getMessage());
            return null;
        }

        return $event;
    }
]);
\Sentry\captureLastError();

require APPPATH . 'classes/lib/util.php';
require APPPATH . 'classes/lib/exception.php';

define('DATAPATH', getenv('DATAPATH') ?: DOCROOT . '/data/');

ensurePath(DATAPATH);
ensurePath(DATAPATH . "logs/");
ensurePath(DATAPATH . "sections/");

/**
 * Your environment.  Can be set to any of the following:
 *
 * Fuel::DEVELOPMENT
 * Fuel::TEST
 * Fuel::STAGING
 * Fuel::PRODUCTION
 */
\Fuel::$env = \Arr::get($_SERVER, 'FUEL_ENV', \Arr::get($_ENV, 'FUEL_ENV', \Fuel::PRODUCTION));
\Fuel::$env = \Fuel::STAGING;

// Initialize the framework with the config file.
\Fuel::init('config.php');

$route = \Router::process(\Request::forge(), true);
$route = $route ? " (" . $route->controller . "/" . $route->action . ")" : "";

if (isset($_SERVER['REQUEST_METHOD'])) {
Log::debug("---- REQUEST: " . $_SERVER['REQUEST_METHOD'] . " " . $_SERVER['REQUEST_URI'] . " ↝" . $_SERVER['PHP_SELF'] .
    "$route ua=" . \Arr::get($_SERVER, 'HTTP_USER_AGENT', ''));
}

if (Session::get("SESSION_KEY", null) == null)
    Session::set("SESSION_KEY", randomString(4));
Session::set("REQUEST_KEY", randomString(4) . "/" . Session::get("SESSION_KEY", "XXXX"));

Model_User::initialize();

Config::load(DATAPATH . "config.json", 'config');
Config::set('cache_dir', ensurePath(DATAPATH . "/cache"));

$jwtKey = Config::get('config.jwt_key');
if (!$jwtKey) {
    $jwtKey = bin2hex(random_bytes(32));
    Config::set('config.jwt_key', $jwtKey);
    Config::save(DATAPATH . "config.json", 'config');
    Log::info("JWT Key initialized: " . $jwtKey);
}

define('JWT_KEY', $jwtKey);

function loadSectionConfig($section, $global = true)
{
    $sectionConfig = ensurePath(DATAPATH . "sections/$section", "config.json");
    if (!file_exists($sectionConfig)) {
        Log::info("Initializing config file $sectionConfig");
        file_put_contents($sectionConfig, "{}");
    }

    $root = $global ? "section" : $section;

    Config::delete($root);
    Config::load($sectionConfig, $root, $reload = true);
    Config::set("$root.name", $section);
}

$user = Session::get('user', null);
if ($user) {
    $username = " ⚝ " . ($user->section ? $user->section->name . ":" : "") . $user['username'];
    if ($user->section) {
        loadSectionConfig($user->section['name']);
    }
} else {
    $username = "";
    \Config::set("section", \Config::get("config.section"));
}

if (isset($_SERVER['REQUEST_METHOD'])) {
Log::info("⇒ {$_SERVER['REQUEST_METHOD']}@{$_SERVER['REQUEST_URI']} $username ={$_SERVER['REMOTE_ADDR']}");
Log::debug("Bootstrap complete: session=" . json_encode(\Session::get()) . " config=" . json_encode(\Config::get("section")));
}
