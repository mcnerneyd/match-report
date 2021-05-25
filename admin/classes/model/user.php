<?php

class Model_User extends \Orm\Model
{
	protected static $_properties = array(
		'id',
		'username',
		'password',
    'club_id',
    'section_id',
		'email',
		'group',
	);

	protected static $_belongs_to = array('club','section');

	protected static $_table_name = 'user';

}
