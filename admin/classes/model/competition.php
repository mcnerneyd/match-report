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

	public static function parse($str) {
		$config = Config::get("config.pattern.competition");

		$patterns = array();
		$replacements = array();
		foreach ($config as $pattern) {
			if (trim($pattern) == '') break;
			$parts = explode($pattern[0], $pattern);
			if (count($parts) < 3) continue;
			$patterns[] = "/${parts[1]}/i";
			$replacements[] = $parts[2];
		}

		$str = trim(preg_replace($patterns, $replacements, trim($str)));

		if ($str == '!') return null;

		return $str;
	}
}
