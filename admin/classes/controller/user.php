<?php
class Controller_User extends Controller_Template
{
	// --------------------------------------------------------------------------
	public function action_index() {
		if (!\Auth::has_access('users.view')) throw new HttpNoAccessException;

		$data = array();
		$data['users'] = $this->userlist();
		$data['clubs'] = Model_Club::find('all');

		foreach ($data['users'] as &$user) {
			$user['role'] = Auth::group('Simplegroup')->get_name($user['group']);
		}

		$this->template->title = "Users";
		$this->template->content = View::forge('user/index', $data);
	}

	public function action_sqlupdate() {
		if (!\Auth::has_access('users.view')) throw new HttpNoAccessException;
		foreach( \DB::query('select * from `user`')->execute() as $user) {
			if ($user['password']) {
				echo "UPDATE `user` SET `password`='".\Auth::hash_password($user['old_password'])."' WHERE id = ${user['id']};\n";
			}
		}

		return new Response("", 200);
	}

	// --------------------------------------------------------------------------
	public function action_forgottenpassword() {
		$username = Input::param('e');

		if (!isset($username)) {
			$this->template->title = "Forgotten Password";
			$this->template->content = View::forge('user/forgottenpassword');
			return;
		}

		$user = Model_User::find_by_email($username);
		if (!$user) {
			Log::warning("Unknown user:$username");
			return new Response("User not found", 404);
		}
		if ($user['role'] == 'user' || $user['role'] == 'umpire') {
			return new Response("Cannot reset matchcard user password (only secretaries/admins)", 403);
		}

		$salt = Config::get("config.salt");
		$autoEmail = Config::get("config.automation.email");
		$title = Config::get("config.title");
		$site = \Session::get('site');
		$hash = Input::param('h');

		if (!isset($hash)) {

			$ts = Date::forge()->get_timestamp();
			$hash = md5("$site $username $ts $salt");

			$email = Email::forge();
			$email->to($username);
			$email->subject("Leinster Hockey Cards - Password Reset");
			$email->html_body(View::forge("user/resetemail", array(
				"email"=>$username,
				"site"=>$site,
				"timestamp"=>$ts,
				"hash"=>$hash)));
			$email->send();

			Log::info("Password reset email sent to:$username");

			$this->template->title = "Email Sent";
			$this->template->content = View::forge('user/forgottenpassword',
				array("email"=>$username));
			return;
		}

		$ts = Input::param('ts');
		$delta = Date::forge()->get_timestamp() - $ts;
		if ($delta > (5 * 60)) {
			return new Response("Expired hash", 401);
		}

		if ($hash != md5("$site $username $ts $salt")) {
			return new Response("Invalid hash", 401);
		}

		$newPassword = Input::param('p');
		if ($newPassword) {
			$user['password'] = \Auth::hash_password($newPassword);
			$user->save();				
			$this->template->title = "Password Reset";
			$this->template->content = View::forge('user/changepassword', array(
				"success"=>true));
		} else {
			$this->template->title = "Reset Password";
			$this->template->content = View::forge('user/changepassword', array(
				"timestamp"=>$ts,
				"email"=>$username,
				"hash"=>$hash));
		}
	}

  public function action_resetlink() {
		if (!\Auth::has_access('user.impersonate')) throw new HttpNoAccessException;

		$username = Input::param('email');
		$user = Model_User::find_by_email($username);
		if (!$user) {
			Log::warning("Unknown user:$username");
			return new Response("User not found", 404);
		}
		if ($user['role'] == 'user' || $user['role'] == 'umpire') {
			return new Response("Cannot reset matchcard user password (only secretaries/admins)", 403);
		}

		$salt = Config::get("config.salt");
		$site = \Session::get('site');
		$ts = Date::forge()->get_timestamp() + (24 * 60 * 60);
		$hash = md5("$site $username $ts $salt");

    $url = Uri::create("/User/ForgottenPassword?e=$username&ts=$ts&h=$hash&site=$site");
    return new Response($url, 200);
  }

	// --------------------------------------------------------------------------
	public function action_accessdenied() {
		if (!Session::get('username')) {
			$loginPage = Uri::create('Login');
			Log::info("User is not logged in - redirecting to login page ($loginPage)");
			Response::redirect($loginPage);
		}

		Log::info("User is accessing restricted area: ".Session::get('username')." - redirecting to ".Uri::base(false));
		//Response::redirect(Uri::base(false));
		$this->template->content = View::forge('user/403.php', array());
	}

	// --------------------------------------------------------------------------
	public function action_login() {
		Log::info("Login ".Request::main()->get_method().":".print_r(Input::all(),true));

		if (\Input::param('consent')) {
			Cookie::set('CONSENT', 'YES');
		}

		if (\Auth::check()) \Auth::logout();

		if (Input::param('site', null) === 'none') {
			Cookie::delete('site');
			Session::delete('site');
			Profiler::console("Site unselected");
		}

		$data = array();

		$data['selectedUser'] = Input::param('user', null);

		if (Input::post()) {
			Log::debug("Crypted password: ".Auth::hash_password(\Input::param('pin')));
			if (Auth::login()) {
				Input::param("remember-me", false) ? \Auth::remember_me() : \Auth::dont_remember_me();
				Log::info("Logged in user: ".Session::get('username'));

				if (Session::get('username') === 'admin') {
					Response::redirect(Uri::create('Admin'));
				} else {
					Response::redirect('../card/sso.php?'.base64_encode($this->encode("/card/index.php")));
				}
			} else {
				$data['username'] = Input::post('user');
				$data['login_error'] = 'Invalid credentials. Try again';
				Log::warning("Invalid credentials");
			}
		}

		if (Session::get('site')) {
			$users = array_filter($this->userlist(), function($k) { return $k['password']; });
			$users = self::classify($users, 'role');
			$data['users'] = array('Clubs'=>array(), 'Umpires'=>array());
			if ($users) {
				if (isset($users['user'])) $data['users']['Clubs'] = $users['user'];
				if (isset($users['umpire'])) $data['users']['Umpires'] = $users['umpire'];
			}
		}

		$sites = array();
		foreach (scandir(DATAPATH."/sites/") as $site) {
			if ($site[0] === '.') continue;
			$configPath = DATAPATH."/sites/$site/config.json";
			if (!file_exists($configPath)) continue;
			$config = json_decode(file_get_contents($configPath));
			$sites[$site] = $config->title;
		}
		$data['sites'] = $sites;

		Session::delete('club');

		$this->template->content = View::forge('login', $data);
	}

	// --------------------------------------------------------------------------
	private function encode($redirect = null) {
		$data = array('timestamp'=>time(), 'session'=>array());
		$site = Session::get('site', null);
		if (!$site) {
			throw new Exception("No site set");
		}

		$username = Session::get('username', null);
		if (!$username) {
			throw new Exception("No username set");
		}

		$data['site'] = $site;
		$data['u'] = $username;
		$user = Model_User::find_by_username($username);
		$data['session']['user'] = $username;
		if ($user['role'] != 'umpire') {
			$data['session']['club'] = $user['club']['name'];
		}

		$roles = array($user['role']);

		$perms = array();
		foreach (\Config::get('simpleauth.roles', array()) as $role=>$rolev) {
			if (is_array($rolev))
			foreach ($rolev as $object=>$values) {
				if (is_array($values))
				foreach ($values as $perm) {
					$perms[] = $object.".".$perm;
				}
			}
		}

		$data['session']['perms'] = array_filter($perms, function($x) { return \Auth::has_access($x); });

		$data['session']['roles'] = $roles;
		if ($redirect != null) $data['redirect'] = $redirect;
		$data['signature'] = md5(json_encode($data).Session::get('login_hash'));

		return json_encode($data);
	}

	public function action_switch() {
		Log::debug("User switching: ".\Session::get('username'));
		if (!\Auth::has_access('user.impersonate')) throw new HttpNoAccessException;
		$user = Input::param('u');
		$user = Model_User::find_by_username($user);
		$success = \Auth::force_login($user['id']);
		$a = \Session::get('login_hash');
		$user = \Session::get('username');
		Log::debug("User switched: $success $user $a ".Session::get('site'));

		Response::redirect('../card/sso.php?'.base64_encode($this->encode("/card/index.php")));
	}

	public function action_root() {
		if (!Session::get('username')) {
			Response::redirect(Uri::create('Login'));
		} else {
			Response::redirect('/cards/index');
		}
	}

	private function userlist() {
		$allusers = array();
		$clubs = array();
		//foreach (Db::query("select distinct name from club c")->execute() as $row) $clubs[] = $row['name'];
		
		foreach (Model_User::find('all') as $user) {
			if (!$user['username']) continue;
			//if ($user['role'] == 'user' and !in_array($user['username'], $clubs)) continue;
			$allusers[$user['username']] = $user;
		}

		return $allusers;
	}

	private static function classify($arr, $key) {
		$result = array();

		foreach ($arr as $item) {
			$kvalue = $item[$key];
			if (!isset($result[$kvalue])) $result[$kvalue] = array();
			$result[$kvalue][] = $item;
		}

		foreach ($result as $k=>$x) {
			usort($x, function($a, $b) {
				return strcasecmp($a['username'],$b['username']);
			});
			$result[$k] = $x;
		}


		return $result;
	}
}
