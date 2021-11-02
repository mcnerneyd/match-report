<?php
/* Raven/Sentry */
#require_once PKGPATH.'Raven/Autoloader.php';
#Raven_Autoloader::register();
#$sentry_client = new Raven_Client('https://0e648f1a6af5407985c427afb086e5bb:37b68176201d451a849bbbb4c81ec6f6@sentry.io/1242091');
#$error_handler = new Raven_ErrorHandler($sentry_client);
#$error_handler->registerExceptionHandler();
#$error_handler->registerErrorHandler();
#$error_handler->registerShutdownFunction();

// Bootstrap the framework DO NOT edit this
require COREPATH.'bootstrap.php';

\Autoloader::add_classes(array(
    // Add classes you want to override here
    // Example: 'View' => APPPATH.'classes/view.php',
));

// Register the autoloader
\Autoloader::register();

require APPPATH.'vendor/autoload.php';

\Sentry\init(['dsn' => 'https://773a5c2c3fc64be3961a669bf217015c@o48105.ingest.sentry.io/103038' ,
    'before_send' => function (\Sentry\Event $event, ?\Sentry\EventHint $hint): ?\Sentry\Event {
        // Ignore the event if the original exception is an instance of MyException
        if ($hint !== null && $hint->exception instanceof HttpNotFoundException) {
        return null;
        }
        
        return $event;
    }]);
\Sentry\captureLastError();

function ensurePath($path, $file = "")
{
    if (!file_exists($path)) {
        mkdir($path, 0777, true);
    }
    return realpath("$path")."/$file";
}

define('DATAPATH', DOCROOT.'/data/');

ensurePath(DATAPATH);
ensurePath(DATAPATH."logs/");
ensurePath(DATAPATH."sections/");

/**
 * Your environment.  Can be set to any of the following:
 *
 * Fuel::DEVELOPMENT
 * Fuel::TEST
 * Fuel::STAGING
 * Fuel::PRODUCTION
 */
\Fuel::$env = \Arr::get($_SERVER, 'FUEL_ENV', \Arr::get($_ENV, 'FUEL_ENV', \Fuel::PRODUCTION));
//\Fuel::$env = \Fuel::STAGING;

// Initialize the framework with the config file.
\Fuel::init('config.php');

$route = \Router::process(\Request::forge(), true);
$route = $route ? " (".$route->controller."/".$route->action.")" : "";

Log::info("*****************\nRequest: ".$_SERVER['REQUEST_METHOD']." ".$_SERVER['REQUEST_URI']." ->".$_SERVER['PHP_SELF'].
  "$route ua=".\Arr::get($_SERVER, 'HTTP_USER_AGENT', ''));

Model_User::initialize();

require APPPATH.'classes/lib/upgrade.php';
require APPPATH.'classes/lib/util.php';

Config::load(DATAPATH."config.json", 'config');
Config::set('cache_dir', ensurePath(DATAPATH."/cache"));

$firstPass = null;

function milliseconds() {
    $mt = explode(' ', microtime());
    return ((int)$mt[1]) * 1000 + ((int)round($mt[0] * 1000));
}

function loadSectionConfig($section, $global = true)
{
    global $firstPass;

    $sectionConfig = ensurePath(DATAPATH."sections/$section", "config.json");
    if (!file_exists($sectionConfig)) {
        Log::info("Initializing config file $sectionConfig");
        file_put_contents($sectionConfig, "{}");
    }

    $root = $global ? "section" : $section;

    Config::delete($root);
    Config::load($sectionConfig, $root, $reload=true);
    Config::set("$root.name", $section);
    if (!$firstPass) Log::info("Section Config from $sectionConfig: $section/$root=".print_r(Config::get($root, array()), true));
    $firstPass = $section;
}

$user = Session::get('user', null);
if ($user) {
    Log::info("User set: ".$user['username']." ".($user->section ? $user->section->name:"(No section)"));
    if ($user->section) {
        loadSectionConfig($user->section['name']);
    }
} else {
    Log::info("User set: none");
}

// Configuration for the cards website
$cardsConfig = DATAPATH."/config.json";
if (!file_exists($cardsConfig)) {
    $config = array("database"=>array("name"=>\Config::get("db.default.connection.database"),
    "username"=>\Config::get("db.default.connection.username"),
    "password"=>\Config::get("db.default.connection.password"),
    "host"=>\Config::get("db.default.connection.hostname")));
    file_put_contents($cardsConfig, json_encode($config));
}

Log::debug("Bootstrap complete. session=".print_r(\Session::get(), true));
