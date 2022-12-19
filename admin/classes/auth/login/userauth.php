<?php 
class Auth_Login_Userauth extends Auth_Login_Simpleauth {

	public function validate_user($username = '', $password = '') {

		$username = trim($username) ?: trim(\Input::post(\Config::get('simpleauth.username_post_key', 'username')));
		$password = trim($password) ?: trim(\Input::post(\Config::get('simpleauth.password_post_key', 'password')));

		Log::info("Login: $username $password ".$this->hash_password($password));

		return parent::validate_user($username, $password);
	}
}
