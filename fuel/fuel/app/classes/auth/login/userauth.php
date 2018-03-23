<?php 
class Auth_Login_Userauth extends Auth_Login_Simpleauth {
	public function validate_user($username = '', $password = '') {
		$username = trim($username) ?: trim(\Input::post(\Config::get('simpleauth.username_post_key', 'username')));
		$password = trim($password) ?: trim(\Input::post(\Config::get('simpleauth.password_post_key', 'password')));

		if (empty($username) or empty($password))
		{
			return false;
		}

		$user = \DB::select('username', 'password', 'club.name', 'role')
			->from('user')
			->join('club', 'LEFT')->on('user.club_id','=','club.id')
			->where('username', '=', $username)
			->execute()->current();

		\Session::delete('elevated');
		\Config::load('custom.db', 'config');
		$elevationPassword = \Config::get("config.elevation_password", "");
		if (trim($elevationPassword) == "") $elevationPassword = '1234';

		Log::info("Logging in with user ".Session::get('site').".$username, password=$password/${user['password']}");

		if ($user['password'] == $password) {
			$user['group'] = Auth_Login_Userauth::get_group($user['role']);

			Log::info("User logged in");
		} else if ($password == $elevationPassword) {
			// Elevated user
			$user['group'] = 100;
			\Session::set('elevated', true);
			Log::info("User logged in (Elevated)");
		} else {
			Log::warning("Invalid user $username");
			$user = false;
		}

		return $user ?: false;
	}

	public function create_login_hash()
	{
		if (empty($this->user))
		{
			throw new \SimpleUserUpdateException('User not logged in, can\'t create login hash.', 10);
		}

		$last_login = \Date::forge()->get_timestamp();
		$login_hash = sha1(\Config::get('simpleauth.login_hash_salt').$this->user['username'].$last_login);

		$this->user['login_hash'] = $login_hash;

		return $login_hash;
	}

	private static function get_group($role) {
		if (\Session::get('elevated')) return 100;

		switch ($role) {
			case 'umpire':
				return 2;
			default:
				return 1;
		}
	}

	protected function perform_check()
	{
		// fetch the username and login hash from the session
		$username    = \Session::get('username');
		$login_hash  = \Session::get('login_hash');

		// only worth checking if there's both a username and login-hash
		if ( ! empty($username) and ! empty($login_hash))
		{
			if (is_null($this->user) or ($this->user['username'] != $username and $this->user != static::$guest_login))
			{
				$this->user = \DB::select('username', 'pin', 'club.name', 'role')
					->from('user')
					->join('club', 'LEFT')->on('user.club_id','=','club.id')
					->where('username', '=', $username)
					->execute()->current();

					$this->user['group'] = Auth_Login_Userauth::get_group($this->user['role']);
			}

			// return true when login was verified, and either the hash matches or multiple logins are allowed
			if ($this->user)
			{
				return true;
			}
		}

		// not logged in, do we have remember-me active and a stored user_id?
		elseif (static::$remember_me and $user_id = static::$remember_me->get('user_id', null))
		{
			return $this->force_login($user_id);
		}

		// no valid login when still here, ensure empty session and optionally set guest_login
		$this->user = \Config::get('simpleauth.guest_login', true) ? static::$guest_login : false;
		\Session::delete('username');
		\Session::delete('login_hash');

		return false;
	}

	public function force_login($user_id = '')
	{
		if (empty($user_id))
		{
			return false;
		}

		$this->user = \DB::select('username', 'pin', 'club.name', 'role')
			->from('user')
			->join('club', 'LEFT')->on('user.club_id','=','club.id')
			->where('username', '=', $user_id)
			->execute()->current();

		if ($this->user == false)
		{
			$this->user = \Config::get('simpleauth.guest_login', true) ? static::$guest_login : false;
			\Session::delete('username');
			\Session::delete('login_hash');
			return false;
		}

		\Session::set('username', $this->user['username']);
		\Session::set('login_hash', $this->create_login_hash());
		// Legacy compatability
		$_SESSION['__org']['user'] = $this->user['username'];

		// and rotate the session id, we've elevated rights
		\Session::instance()->rotate();

		// register so Auth::logout() can find us
		Auth::_register_verified($this);


		return true;
	}

	public function has_access($condition, $driver = null, $user = null)
	{
		return parent::has_access($condition, $driver, $user);
	}
}
