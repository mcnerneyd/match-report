<?php

class Model_Section extends \Orm\Model
{
	protected static $_properties = array(
		'id',
    'name',
    'config',
	);

	protected static $_has_many = array('competition', 'user');

	protected static $_table_name = 'section';

}
