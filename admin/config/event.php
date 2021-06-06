<?php

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

			if ($header = \Input::headers('Authorization')) {
				$matches = array();
				preg_match("/Basic (.*)/", $header, $matches);
				$data = base64_decode($matches[1]);
				preg_match("/(.*):(.*)/", $data, $matches);
				\Auth::login($matches[1], $matches[2]);
			}
		},
	),
);
