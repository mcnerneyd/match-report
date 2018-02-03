<?php

// ----------------------------------------------------------------------------
if (isset($action) && $action == 'logout') {
	unset($_SESSION['user']);
	setcookie("key", null, -1, '/');
	setcookie("site", null, -1, '/');

	throw new RedirectException("User logged out", url(null, 'login', 'club'));
}

// Single Sign-on Login
if (isset($_REQUEST['ccode'])) {
	$ccode = $_REQUEST['ccode'];

	$key = base64_decode($ccode);
	$keys = explode(';', $key);
	$enckey = $keys[0]."$".$keys[1].$keys[2].HASH_TEMPLATE.$keys[3];
	$site = $keys[0];
	$username = $keys[1];
	$adminUser = false;

	if ($keys[3] == 'A') {
		$adminUser = true;
	}

	if (md5($enckey) != $keys[4]) {
		throw new LoginException("Unknown user or invalid password");
	}

	$db = Db::getInstance();

	$req = $db->prepare("select u.*,c.name club from user u left join club c on u.club_id = c.id where username = :username");

	$req->execute(array('username'=>$username));
	$res = $req->fetch();

	if (!$res) {
		throw new LoginException("Unknown user or invalid password.");
	}

	$_SESSION['site'] = $site;
	$_SESSION['user'] = $username;
	$_SESSION['club'] = $res['club'];
	$_SESSION['roles'] = $adminUser ? array('admin') : array($res['role']);
	$_SESSION['breadcrumbs'] = array($username=>url('', null, null));
}

// ----------------------------------------------------------------------------
if (isset($action) && $action == 'loginUC') {
	echo "loginUC";
	$username = $_REQUEST['u'];
	securekey('secretarylogin'.$username);

	$db = Db::getInstance();

	$req = $db->prepare("select u.*,c.name club from user u left join club c on u.club_id = c.id where username = :username");

	$req->execute(array('username'=>$username));
	$res = $req->fetch();

	if (!$res) {
		throw new LoginException("Unknown user or invalid password.");
	}

	$_SESSION['user'] = $username;
	$_SESSION['club'] = $res['club'];
	$_SESSION['roles'] = array('secretary');
	$_SESSION['breadcrumbs'] = array($username=>url('', null, null));

	$controller = null;
	$action = null;

	if (user('secretary')) {
		$q = null;
		if ($_REQUEST['validate']) $q='validate=1';
		throw new RedirectException("User logged in", url($q, 'register', 'club'));
	}

	throw new RedirectException("User logged in", url(null, 'index', 'card'));
}

// ----------------------------------------------------------------------------
if (isset($action) && $action == 'loginUP') {

	if (isset($_SESSION['user'])) {
		throw new RedirectException("User logged in", url(null, 'index', 'card'));
	}

	if (!isset($_SESSION['site']) or $_SESSION['site'] != site()) {
		$_SESSION['site'] = site();
		//throw new LoginException("Site set_".site()."_".$_SESSION['site']);
	}

	if (!isset($_POST['pin'])) {
		throw new LoginException("PIN must be set");
	}

	$username = null;
	if (isset($_POST['user'])) {
		$username = $_POST['user'];
	}
	$password = $_POST['pin'];

	$roles = array();
	$adminUser = false;

	if (ADMIN_CODE == $password) {
		if ($username == null) $username = 'admin';
		$roles[] = 'admin';
		$adminUser = true;
	} 
			
	if ($username == null) {
		throw new LoginException("Username must be set");
	}
	debug("Logging in $username/$password");

	$db = Db::getInstance();

	$req = $db->prepare("select * from user where username = :username");

	$req->execute(array('username'=>$username));
	$res = $req->fetch();


	if (!$res) {
		throw new LoginException("Unknown user or invalid password.");
	} else {
		$club = null;
		$roles[] = $res['role'];

		if ($res['role'] == 'user') $club = $username;
	}

 	if (!$adminUser and $res['password'] != $password) {
		throw new LoginException("Unknown user or invalid password");
	}

	if (!isset($_REQUEST['remember-me'])) setcookie('noremember', 'yes', 2147483647, '/');

	$_SESSION['user'] = $username;
	$_SESSION['roles'] = $roles;
	$_SESSION['club'] = $club;
	$_SESSION['breadcrumbs'] = array($username=>url('', null, null));

	$controller = null;
	$action = null;

	throw new RedirectException("User logged in", url(null, 'index', 'card'));
}

function user($role = null) {

	// for a secure call the site must be set
	if (!site()) {
		return false;
	}

	if ($role != null) {
		if (!isset($_SESSION['roles'])) return false;
		if (!in_array($role, $_SESSION['roles'])) {
			return false;
		}
	}

	if (!isset($_SESSION['user'])) return false;
		
	return $_SESSION['user'];
}

function checkuser() {	
	if (func_num_args() == 0) {
		$user = user();
	} else {
		$user = user('admin');		// admin user can do anything
		if (!$user) {
			foreach (func_get_args() as $role) {
				$user = user($role);
				if ($user) break;
			}
		}
	}

	if ($user) return $user;

	if (!isset($_SESSION['user'])) throw new LoginException("You are not logged in");

	throw new Exception("You do not have access to this area");
}

function securekey($key) {
	if (user('admin')) return;

	$salt = 'ALPHAcodePIG';

	if (!isset($_REQUEST['x'])) {
			throw new Exception('Security key match failed (no key provided)');
	}

	$hash = $_REQUEST['x'];

	$t = substr($hash, 32);
	if ($hash != createsecurekey($key, $t)) {
		throw new Exception('Security key match failed');
	}
}

function createsecurekey($key, $t = null) {
	$salt = 'ALPHAcodePIG';

	if ($t != null) $t = dechex(floor(time()/60));

	return md5($salt.strtolower($key).$t).$t;
}

if (!user() && isset($controller)) {
	if ($controller == null) {
		$controller = 'club';
		$_REQUEST['controller'] = $controller;
	}
}
?>
