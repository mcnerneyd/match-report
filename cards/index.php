<?php

session_start();

require_once('util.php');
require_once('fuel.php');
require_once('vendor/autoload.php');

/* Raven/Sentry */
require_once PKGPATH.'/Raven/Autoloader.php';
Raven_Autoloader::register();
$sentry_client = new Raven_Client('https://0e648f1a6af5407985c427afb086e5bb:37b68176201d451a849bbbb4c81ec6f6@sentry.io/1242091');
$error_handler = new Raven_ErrorHandler($sentry_client);
$error_handler->registerExceptionHandler();
$error_handler->registerErrorHandler();
$error_handler->registerShutdownFunction();

ini_set('display_errors',1);
ini_set('display_startup_errors',1);
ini_set('max_execution_time', 300); 
error_reporting(E_ALL);

$consolelog = "";
$warnings = array();

class RedirectException extends Exception { 

	public $location;	

	function __construct($msg = null, $location = null) {
		parent::__construct($msg);

		$this->location = $location;
	}
}

class LoginException extends RedirectException { }

function dd($arr, $key, $def = null) {
	if (!isset($arr[$key])) return $def;
	return $arr[$key];
}

function debug($msg) {
	if (!isset($_SESSION['debug']) and !isset($_REQUEST['debug'])) return false;
	Log::debug($msg);
}

function info($msg) {
	Log::info($msg);
}

function warn($msg) {
	Log::warn($msg);

	global $warnings;

	$warnings[] = $msg;
}

function url($query = null, $action = null, $controller = null) {	
	global $site;

	if ($controller == null and isset($_REQUEST['controller'])) $controller = $_REQUEST['controller'];
	if ($action == null and isset($_REQUEST['action'])) $action = $_REQUEST['action'];

	$target = "";

	$target.="&site=".site();
	if ($controller) $target.="&controller=$controller";
	if ($action) $target.="&action=$action";
	if ($query) $target .= "&".$query;

	$url = 'http://' . $_SERVER['HTTP_HOST'];            // Get the server

	return $url . $_SERVER['SCRIPT_NAME']."?".substr($target,1); //str_replace("&", "&amp;", substr($target,1));
}

function urlx() {
	$s = $_SERVER;

	$ssl      = ( ! empty( $s['HTTPS'] ) && $s['HTTPS'] == 'on' );
	$sp       = strtolower( $s['SERVER_PROTOCOL'] );
	$protocol = substr( $sp, 0, strpos( $sp, '/' ) ) . ( ( $ssl ) ? 's' : '' );
	$port     = $s['SERVER_PORT'];
	$port     = ( ( ! $ssl && $port=='80' ) || ( $ssl && $port=='443' ) ) ? '' : ':'.$port;
	$host     = ( false && isset( $s['HTTP_X_FORWARDED_HOST'] ) ) ? $s['HTTP_X_FORWARDED_HOST'] : ( isset( $s['HTTP_HOST'] ) ? $s['HTTP_HOST'] : null );
	$host     = isset( $host ) ? $host : $s['SERVER_NAME'] . $port;

	$url = 'http://' . $_SERVER['HTTP_HOST'];            // Get the server

	return $url . $s['REQUEST_URI']; 
}

function pushUrl() {
	if (isset($_SESSION['url-stack'])) {
		$stack = $_SESSION['url-stack'];
	} else {
		$stack = array();
	}

	$result = array();

	$url = urlx();

	foreach ($stack as $value) 
		if ($url != $value) $result[] = $value;
		else break;

	$result[] = $url;

	$_SESSION['url-stack'] = $result;
}

function getBackUrl() {
	if (isset($_SESSION['url-stack'])) {
		return end($_SESSION['url-stack']);
	}

	return null;
}

function redirect($controller, $action, $query = null) {
	throw new RedirectException('', url($query, $action, $controller));
}

ob_start();

try {
	
	if (isset($_REQUEST['debug'])) {
			if ($_REQUEST['debug'] == 'off') {
				unset($_SESSION['debug']);
			}

			if ($_REQUEST['debug'] == 'session') {
				$_SESSION['debug'] = true;
			}
	}

	$site = null;
	$controller = Arr::get($_REQUEST, "controller", null);
	$action = Arr::get($_REQUEST, "action", null);

	require_once('model/connection.php');
	require_once('secure.php');

	if (isset($_COOKIE['site']) && !isset($_REQUEST['site']) && !isset($_SESSION['site'])) {
		$site = $_COOKIE['site'];
		throw new RedirectException("User logged in (1)", url("site=$site", 'index', 'card'));
	}

	$username =  isset($_SESSION['username']) ? $_SESSION['user'] : "<unknown user>";

	if (isset($_COOKIE['key']) && !isset($_SESSION['user'])) {
		$username = $_COOKIE['username'];
		$site = $_REQUEST['site'];
		if ($_COOKIE['key'] == createsecurekey($site + "cookie" + $username)) {
			$x = createsecurekey('secretarylogin'.$username);
			throw new RedirectException("User logged in (2)", url("site=$site&x=$x&u=$username", 'loginUC', 'club'));
		}
	}

	if (isset($_SESSION['site'])) $site = $_SESSION['site'];

	if (!$site) Log::error("User ($username) does not have a site associated");
  
	$layout = 'layout';
	if (isset($_REQUEST['layout'])) $layout = $_REQUEST['layout'];

	$sentry_client->user_context(array(
		'site'=>isset($_SESSION['site']) ? $_SESSION['site'] : "Unknown",
		'user'=>isset($_SESSION['user']) ? $_SESSION['user'] : "Unknown",
	));

	require_once("views/$layout.php");

} catch (LoginException $e) {
  Log::info("Login exception: ".$e->getMessage());
	header("Location: ".BASE."/Login");
  header("X-Detail: ".$e->getMessage());
	exit();
} catch (RedirectException $e) {
  Log::info("Redirect exception: ".$e->getMessage());
	header("Location: ".$e->location);
  header("X-Detail: redirect2");
	exit();
} catch (Exception $e) {
	echo "<pre>".print_r($e, true)."</pre>";
}

ob_end_flush();

if (count($warnings)) { ?>

<div class="alert alert-warning" role="alert" id='warning-box'>
  <i class="fas fa-exclamation-circle"></i> Warnings
	<ul>
  <?php
		foreach (array_unique($warnings) as $warning) {
			echo "<li>$warning</li>";
		}
	?>
	</ul>
</div>

<script>
$(document).ready(function() {
	$('#warning-box').prependTo(".container:first");
});
</script>
	<?php }

	echo "<!-- Site=$site Controller=[$controller] Action=[$action] User=[".user()."] Club=[".\Arr::get($_SESSION, 'club', 'No club')."] Roles=".(isset($_SESSION['roles']) ? join($_SESSION['roles']):"no roles")."-->";
	echo "<!-- ".print_r($_SESSION, true)." -->";
?>
