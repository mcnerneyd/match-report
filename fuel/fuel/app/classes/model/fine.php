<?php

class Model_Fine extends \Orm\Model
{
	protected static $_properties = array(
		'id',
		'date',
		'type',
		'club_id',
		'matchcard_id',
		'detail',
		'resolved',
	);

	protected static $_belongs_to = array('club');

	protected static $_has_one = array(
		'card'=>array(
			'key_to'=>'id',
			'key_from'=>'matchcard_id',
			'model_to'=>'Model_Card',
		)
	);

	protected static $_table_name = 'incident';

	protected static $_conditions = array(
		'order_by' => array('date' => 'desc'),
		'where' => array(
			array('type', '=', 'Missing'),		// Missing is actually the type for fine (Late=Warning)
		),
	);
}
