<?php
require_once 'fuel.php';

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
#print_r($data);
	$site = $data['site'];
	$username = $data['u'];
	echo "Request received ($username@$site)\n";
	$root = dirname(__FILE__);
	define('SITEPATH', "$root/sites/$site");
	$configFile = $root.'/sites/'.$site.'/config.ini';
#print_r(file_get_contents($configFile));
	//$config = parse_ini_file($configFile, true);

	require_once 'model/connection.php';
	echo "Transferring: $username\n";

	$user = Db::getInstance()->query("SELECT * FROM user WHERE username = '$username'")->fetch();
	if (!$user) {
		echo "403 Unknown user";
		header($_SERVER['SERVER_PROTOCOL']." 403 Unknown user");
		return;
	}
	//echo "C:$configFile";print_r($config);
	echo "User valid\n";

	$key = $data['signature'];
	unset($data['signature']);

	$raw = json_encode($data).$user['login_hash'];
	if (md5($raw) != $key) {
		echo "403 Forbidden";
		header($_SERVER['SERVER_PROTOCOL']." 403 Forbidden");
		return;
	}

	if (isset($data['session'])) {
		$session = $data['session'];
		//echo "(Session:".print_r($session,true).")\n";
		session_start();
		$_SESSION['site'] = $site;
		$_SESSION['user'] = $session['user'];
		$_SESSION['club'] = $session['club'];
		$_SESSION['roles'] = $session['roles'];
		$_SESSION['perms'] = $session['perms'];
		echo "Session data valid\n";
	} else {
		session_unset();
		echo "Session data not valid\n";	
	}

	if (isset($data['redirect'])) {
		header($_SERVER['SERVER_PROTOCOL']." 303 Redirecting");
		header("Location: ".$data['redirect']);
		echo "303 Redirecting\n";
		exit();
	} 

	header($_SERVER['SERVER_PROTOCOL']." 202 Accepted");
	echo "202 Accepted\n";

} catch (Exception $e) {
	echo $e->message;
}
