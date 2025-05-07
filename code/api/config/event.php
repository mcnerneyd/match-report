<?php
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

return array(
	'fuelphp' => array(
		'controller_started' => function()
		{
			$site = Input::param('site');
			if (!$site) $site = Cookie::get('site');
			if (!$site) $site = Session::get('site');

			$ua = null;
			if (isset($_SERVER['HTTP_USER_AGENT'])) $ua = $_SERVER['HTTP_USER_AGENT'];

			DB::instance('mysqli');
			Cookie::set('site', $site, 60*60*24*30);
			Session::set('site', $site);

			// if there is a user logged in, but has not JWT token - force them out
			if (Auth::check() && !Cookie::get('jwt-token', null)) {
				Log::warning("Log out user: user must have a jwt-token");
				\Auth::logout();
				return;
			}

			if ($header = \Input::headers('X-Auth-Token')) {
				$data = JWT::decode($header, new Key(JWT_KEY, 'HS256'));
				\Auth::force_login($data->id);
				Log::info("Logged in user:".($data->id)." [JWT]");
			}

			if ($header = \Input::headers('authorization')) {
				$data = explode(" ", \Input::headers('authorization'));
				$data = explode(":", base64_decode($data[1]));
				\Auth::login($data[0], $data[1]);
				Log::info("Logged in user:".($data[0])." [Basic/Direct]");
			}
		},
	),
);
