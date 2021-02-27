<?php

class Model_User extends \Orm\Model
{
	protected static $_properties = array(
		'id',
		'username',
		'password',
		'old_password',
		'pin',
		'role',
		'email',
		'club_id',
		'group',
	);

	protected static $_belongs_to = array('club');

	protected static $_table_name = 'user';

}
