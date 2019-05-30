<?php 
class Auth_Login_Userauth extends Auth_Login_Simpleauth {

	public function validate_user($username = '', $password = '') {

		$username = trim($username) ?: trim(\Input::post(\Config::get('simpleauth.username_post_key', 'username')));
		$password = trim($password) ?: trim(\Input::post(\Config::get('simpleauth.password_post_key', 'password')));

		Log::info("Login: $username $password ".$this->hash_password($password));

		\Session::delete('elevated');

		if (strtolower($username) === 'admin') {
			\Config::load('custom.db', 'config');

			if ($password !== \Config::get("config.elevation_password", "1234")) {
				return false;
			}

			\Session::set('elevated', true);

			return array('username'=>'admin', 'group'=>100);
		}

		return parent::validate_user($username, $password);
	}
}
