<?php
require_once 'fuel.php';

ini_set('display_errors',1);
ini_set('display_startup_errors',1);
ini_set('max_execution_time', 300); 
error_reporting(E_ALL);

header('Content-Type: text/plain');

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

	$data = json_decode($src, true);
	$site = $data['site'];
	$baseUrl = $data['base'];
	$username = $data['u'];
	echo "Request received ($username@".($site || '-').")\n";
	$root = dirname(__FILE__);

	require_once 'model/connection.php';
	Log::debug("Transferring: $username");
	echo "Transferring: $username";

	$sth = Db::getInstance()->prepare("SELECT * FROM user WHERE username = ?");
	$sth->execute([$username]);
	$user = $sth->fetch();
	if (!$user) {
		echo "\n\n403 Unknown user";
		header($_SERVER['SERVER_PROTOCOL']." 403 Unknown user");
		return;
	}
	echo "User valid\n";

	$key = $data['signature'];
	unset($data['signature']);

	$raw = json_encode($data).$user['login_hash'];
	if (md5($raw) != $key) {
		Log::error("403 Forbidden: $username");
		header($_SERVER['SERVER_PROTOCOL']." 403 Forbidden");
		return;
	}

	if (isset($data['session'])) {
		$session = $data['session'];
		//echo "(Session:".print_r($session,true).")\n";
		session_start();
		$_SESSION['base-url'] = $baseUrl;
		$_SESSION['site'] = $site;
		$_SESSION['section'] = $site;
		$_SESSION['user'] = $session['user'];
		$_SESSION['user-title'] = $session['user-title'];
		if (isset($session['club'])) {
			$_SESSION['club'] = $session['club'];
		} else {
			unset($_SESSION['club']);
		}
		$_SESSION['roles'] = $session['roles'];
		$_SESSION['perms'] = $session['perms'];
    Log::debug("Session transferred:".print_r($_SESSION, true));
		echo "Session data valid\n";
	} else {
		session_unset();
		echo "Session data not valid\n";	
    Log::warning("Session data is not valid");
	}

	$redirect = "/cards/index.php";
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
