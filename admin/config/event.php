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

			if ($header = \Input::headers('X-Auth-Token')) {
				$data = JWT::decode($header, new Key(JWT_KEY, 'HS256'));
				\Auth::force_login($data->id);
				Log::info("JWT Login:".($data->id));
			}
		},
	),
);
