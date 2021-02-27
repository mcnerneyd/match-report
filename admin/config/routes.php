<?php
return array(
	'_root_'  => 'card/index',  	// The default route
	'_404_'   => 'welcome/404',    // The main 404 route
	'_403_'   => 'user/accessdenied',
	
	'Login' => 'user/login',
	'cards' => 'card',
	'cards/:id' => 'card',

	'fixtures' => 'fixture',
	'fines' => 'fine',
	'clubs' => 'admin/clubs',
	'competitions' => 'admin/competitions',
	'users' => 'user',

	'Report/Card/:id' => 'Report/Card',
	'fixtures/:id' => 'Fixture/Index',
);
