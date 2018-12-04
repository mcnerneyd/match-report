<?php
class Controller_User extends Controller_Template
{
	// --------------------------------------------------------------------------
	public function action_index() {
		if (!\Auth::has_access('admin.all')) throw new HttpNoAccessException;
		$data = array();
		$data['users'] = $this->userlist();
		$data['clubs'] = Model_Club::find('all');

		$this->template->title = "Users";
		$this->template->content = View::forge('user/index', $data);
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

		Config::load('custom.db', 'config');
		$salt = Config::get("config.salt");
		$autoEmail = Config::get("config.automation_email");
		$title = Config::get("config.title");
		$site = \Session::get('site');
		$hash = Input::param('h');

		if (!isset($hash)) {

			$ts = Date::forge()->get_timestamp();
			$hash = md5("$site $username $ts $salt");

			$email = Email::forge();
			$email->from($autoEmail, "$title (No Reply)");
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
			$user['password'] = $newPassword;
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

	// --------------------------------------------------------------------------
	public function action_accessdenied() {
		if (!Session::get('username')) {
			Log::info("User is not logged in - redirecting to login page");
			Response::redirect(Uri::create('Login'));
		}

		Log::info("User is accessing restricted area: ".Session::get('username'));
		Response::redirect(Uri::base(false));
	}

	// --------------------------------------------------------------------------
	public function action_login() {
		Log::info("Login ".Request::main()->get_method().":".print_r(Input::all(),true));

		if (\Auth::check()) \Auth::logout();

		if (Input::param('site', null) === 'none') {
			Cookie::delete('site');
			Session::delete('site');
			Profiler::console("Site unselected");
		}

		$data = array();

		if (Input::post()) {
			if (Auth::login()) {
				Log::info("Logged in user: ".Session::get('username'));
				Response::redirect(Uri::create('../../sso.php?'.base64_encode($this->encode("/cards/index.php"))));
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

		$this->template->content = View::forge('login', $data);
	}

	// --------------------------------------------------------------------------
	private function encode($redirect = null) {
		$data = array('timestamp'=>time(), 'session'=>array());
		$data['site'] = Session::get('site');
		$username = Session::get('username');
		$user = Model_User::find_by_username($username);
		$data['session']['user'] = $username;
		if ($user['role'] != 'umpire') {
			$data['session']['club'] = $user['club']['name'];
		}

		$roles = array($user['role']);

		if (\Auth::has_access('nav.[admin]')) {
			$roles[] = 'admin';
		}

		$data['session']['roles'] = $roles;
		if ($redirect != null) $data['redirect'] = $redirect;
		$data['signature'] = md5(json_encode($data).\Config::get('config.salt'));

		return json_encode($data);
	}

	public function action_switch() {
		if (!\Auth::has_access('admin.all')) throw new HttpNoAccessException;
		\Auth::force_login(Input::param('u'));
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
