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

	public function getTeamSizes() {
		$result = array();

		$carry = 0;
		foreach ($this->team as $team) {
			foreach ($team->competition as $competition) {
				$size = $competition['teamsize'];
				if ($size) {
					$size += $carry;
					$carry = $competition['teamstars'];
					$size -= $carry;
					$result[] = $size;
					break;
				}
			}
		}

		return $result;
	}
}
