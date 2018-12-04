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

					Log::info("method: ".Request::main()->get_method(). " content-type: ".\Input::headers('Content-Type')." site=$site ua=$ua"); 

					if ($site and $site != 'none') {
						DB::instance($site);
						Config::set('db.active', $site);
						Cookie::set('site', $site, 60*60*24*30);
						Session::set('site', $site);

						if ($header = \Input::headers('Authorization')) {
							$matches = array();
							preg_match("/Basic (.*)/", $header, $matches);
							$data = base64_decode($matches[1]);
							preg_match("/(.*):(.*)/", $data, $matches);
							\Auth::login($matches[1], $matches[2]);
						}
					} else {
						//Session::delete('site');
						Log::warning("No site specified: ".Request::active()->uri);

						if (Request::active()->uri->get() != 'Login')  {
							Response::redirect("/Login");
						}
					}
        },
    ),
);
