<?php
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
$root = dirname(__FILE__);
$configFile = $root.'/sites/'.$site.'/config.ini';
#print_r(file_get_contents($configFile));
$config = parse_ini_file($configFile, true);
$salt = $config['main']['hashtemplate'];
echo "C:$configFile";print_r($config);

$key = $data['signature'];
unset($data['signature']);

$raw = json_encode($data).$salt;
echo $salt." ".md5($raw);
if (md5($raw) != $key) {
	header($_SERVER['SERVER_PROTOCOL']." 403 Forbidden");
	return;
}

if (isset($data['session'])) {
	$session = $data['session'];
	echo "(Session:".print_r($session,true).")\n";
	session_start();
	$_SESSION['site'] = $site;
	$_SESSION['user'] = $session['user'];
	$_SESSION['club'] = $session['club'];
	$_SESSION['roles'] = $session['roles'];
} else {
	session_unset();
}

if (isset($data['redirect'])) {
	header($_SERVER['SERVER_PROTOCOL']." 303 Redirecting");
	header("Location: ".$data['redirect']);
	exit();
} 

header($_SERVER['SERVER_PROTOCOL']." 202 Accepted");
