<?php
require_once 'fuel.php';
require_once('vendor/autoload.php');

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

ini_set('display_errors',1);
ini_set('display_startup_errors',1);
ini_set('max_execution_time', 300); 
error_reporting(E_ALL);

header('Content-Type: text/plain');

$jwt = $_COOKIE['jwt-token'];
$key = Config::get("config.jwt_key");
print_r($key);
echo "JWT:".$jwt;
$token = JWT::decode($jwt, new Key($key, 'HS256'));
$token = json_decode(json_encode($token), true);

try {

	// POST uses data, GET uses query string, other methods fail
	switch ($_SERVER['REQUEST_METHOD']) {
		case 'POST':
			$src = file_get_contents('php://input');
			break;
		case 'GET':
			$src = base64_decode($_SERVER['QUERY_STRING']);
			break;
		default:
			header($_SERVER['SERVER_PROTOCOL']." 405 Method not allowed");
			exit();
	}

	session_start();
	$_SESSION['base-url'] = $token['base'];
	$_SESSION['site'] = $token['site'];
	$_SESSION['section'] = $token['site'];
	$_SESSION['user'] = $token['user'];
	$_SESSION['user-title'] = $token['user-title'];
	if (isset($token['club'])) {
		$_SESSION['club'] = $token['club'];
	} else {
		unset($_SESSION['club']);
	}
	$_SESSION['roles'] = $token['roles'];
	$_SESSION['perms'] = $token['perms'];

	print_r($_SESSION);

	$redirect = "/cards/ui/";
	if (isset($data['redirect'])) {
		$redirect = $data['redirect'];
	}

	if ($redirect !== '-') {
		header($_SERVER['SERVER_PROTOCOL']." 303 Redirecting");
		header("Location: ".$redirect);
		echo "303 Redirecting: $redirect";
		Log::info("303 Redirecting: $redirect");
		exit();
	} 

	header($_SERVER['SERVER_PROTOCOL']." 202 Accepted");
	echo "202 Accepted\n";
} catch (Exception $e) {
	echo $e->getMessage();
}
