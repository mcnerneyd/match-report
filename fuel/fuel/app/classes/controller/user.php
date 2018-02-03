<?php
class Controller_User extends Controller_Hybrid
{
	// --------------------------------------------------------------------------
	public function delete_index() {
		if (!\Auth::has_access('admin.all')) throw new HttpNoAccessException;
		$username = Input::param('username');
		$user = Model_user::find_by_username($username);

		if (!$user) {
			return new Response("User deleted", 409);
		}

		$user->delete();

		return new Response("User deleted", 204);
	}

	public function get_index() {
		if (!\Auth::has_access('admin.all')) throw new HttpNoAccessException;
		$data = array();
		$data['users'] = $this->userlist();
		$data['clubs'] = Model_Club::find('all');

		$this->template->title = "Users";
		$this->template->content = View::forge('user/index', $data);
	}

	public function post_index() {
		if (!\Auth::has_access('admin.all')) throw new HttpNoAccessException;
		$newUser = new Model_User();

		$clubName = Input::post('club');
		$club = null;

		if ($clubName != null) {
			$club = Model_Club::find_by_name($clubName);
		}

		$username = Input::post('username');

		if (Model_user::find_by_username($username)) {
			return new Reponse("User already exists", 409);
		}

		$newUser->username = $username;
		$newUser->email = Input::post('email');
		$newUser->password = generatePassword(4);
		$newUser->club = $club;
		$newUser->role = Input::post('role');
		$newUser->save();

		return new Response("Created user", 201);
	}

	// --------------------------------------------------------------------------
	public function put_refreshpin() {
		if (!\Auth::has_access('admin.all')) throw new HttpNoAccessException;
		$username = Input::put('username');

		$user = Model_User::find_by_username($username);
		if (!$user) {
			return new Response("User not found", 404);
		}

		$user->password = generatePassword(4);
		$user->save();

		Session::set_flash("notify", array("msg"=>"PIN updated for user $username",
			"className"=>"warn"));
	}

	public function action_accessdenied() {
		if (!Session::get('username')) {
			Log::info("User is not logged in - redirecting to login page");
			Response::redirect(Uri::create('Login'));
		}

		Log::info("User is accessing restricted area");
		Response::redirect(Uri::base(false));
	}

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
				Cookie::set('preferreduser', Session::get('username'));
				Response::redirect(Uri::create('../../sso.php?'.base64_encode($this->encode("/cards/index.php"))));
			} else {
				$data['username'] = Input::post('user');
				$data['login_error'] = 'Invalid credentials. Try again';
				Log::warning("Invalid credentials");
			}
		}

		if (Session::get('site')) {
			$users = Controller_User::classify($this->userlist(), 'role');
			$data['users'] = array('Clubs'=>$users['user'], 'Umpires'=>$users['umpire']);
		}

		$data['preferredUser'] = Cookie::get('preferreduser');

		Log::info("Preferred User: ${data['preferredUser']}, Site: ".Session::get('site'));

		$this->template->content = View::forge('login', $data);
	}

	private function encode($redirect = null) {
		$data = array('timestamp'=>time(), 'session'=>array());
		$data['site'] = Session::get('site');
		$username = Session::get('username');
		$user = Model_User::find_by_username($username);
		$data['session']['user'] = $username;
		if ($user['role'] != 'umpire') {
			$data['session']['club'] = $username;
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
		foreach (Db::query("select distinct name from club c
				join team t on c.id = t.club_id
					join entry e on t.id = e.team_id")->execute() as $row) $clubs[] = $row['name'];
		
		foreach (Model_User::find('all') as $user) {
			if (!$user['username']) continue;
			if ($user['role'] == 'user' and !in_array($user['username'], $clubs)) continue;
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
