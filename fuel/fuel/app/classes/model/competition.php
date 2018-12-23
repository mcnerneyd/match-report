<?php

class Model_Competition extends \Orm\Model
{
	protected static $_properties = array(
		'id',
		'name',
		'code',
		'teamsize',
		'teamstars',
		'format',
		'groups',
		'sequence',
	);

	protected static $_conditions = array(
		'order_by' => array('sequence' => 'asc'),
	);

	protected static $_many_many = array(
		'team' => array(
				'table_through' => 'entry',
				));


	protected static $_table_name = 'competition';
}
