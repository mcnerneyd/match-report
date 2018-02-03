<?php

class Model_Club extends \Orm\Model
{
	protected static $_properties = array(
		'id',
		'name',
		'code',
	);

	protected static $_table_name = 'club';

	protected static $_has_many = array('team', 'user');

	protected static $_conditions = array(
		'order_by' => array('name'=>'asc'),
	);
}
