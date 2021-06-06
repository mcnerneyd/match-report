<?php

class Model_Team extends \Orm\Model
{
	public function getName() {
		return $this->club->name ." ". $this->name;
	}

	protected static $_properties = array(
		'id',
		'name',
		'club_id',
    'section_id'
	);

	protected static $_belongs_to = array('club','section');

	protected static $_many_many = array(
		'competition' => array(
				'table_through' => 'team__competition',
				'conditions' => array(
					'order_by' => array ('sequence'=>'ASC')),
				));

	protected static $_table_name = 'team';

	public static function find_by_name($name, $section) {
		$matches = array();
		if (preg_match("/(.*) ([0-9]+)/i", $name, $matches)) {
			$rows = DB::query("SELECT t.id FROM team t JOIN club c ON t.club_id = c.id
				WHERE c.name = '${matches[1]}' AND t.name = ${matches[2]} AND t.section_id = ${section['id']}")->execute();

			foreach ($rows as $row) {
				return Model_Team::find($row['id']);
			}
		}

		\Log::warning("Unable to locate team: $name");

		return null;
	}

	public static function parse($str) {
		$config = Config::get("section.pattern.team", []);

		$patterns = array();
		$replacements = array();
		foreach ($config as $pattern) {
			if (trim($pattern) == '') break;
			$parts = explode($pattern[0], $pattern);
			if (count($parts) < 3) continue;
			$patterns[] = "/".str_replace("/", "", $parts[1])."/i";
			$replacements[] = $parts[2];
		}

		Log::debug("Patterns:".print_r($patterns, true)." Replace:".print_r($replacements,true));

		$str = preg_replace($patterns, $replacements, trim($str));

		if ($str == '!') return null;

		$matches = array();
		if (!preg_match('/^([a-z ]*[a-z])(?:\s+([0-9]+))?$/i', trim($str), $matches)) {
			Log::warning("Cannot match '$str'");
			return null;
		}

		$result = array('club'=>$matches[1], 'raw'=>$str);

		if (count($matches) > 2) {
			$result['team'] = $matches[2];
		} else {
			$result['team'] = 1;
		}

		$result['name'] = $result['club'] .' '. $result['team'];

		return $result;
	}

	public static function findTeam($club, $team) {
		$result = Model_Team::query()
			->related('club')
			->where('name', '=', $team)
			->where('club.name', '=', $club)
			->get();

			return reset($result);
	}

	public static function lastGame($teamName) {
		$team = self::find_by_name($teamName);
		$teamId = $team->id;
		$rows = DB::query("SELECT x.id, x.date FROM (SELECT m.id, m.date FROM matchcard m WHERE home_id = $teamId
			UNION SELECT m.id, m.date FROM matchcard m WHERE away_id = $teamId) x
			INNER JOIN incident i ON i.type = 'Played' AND x.id = i.matchcard_id
			ORDER BY x.date DESC
			LIMIT 1")->execute();

		foreach ($rows as $row) {
			return Model_Matchcard::find($row['id']);
		}

		return null;
	}
}
