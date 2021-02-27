<?php

class Model_Team extends \Orm\Model
{
	public function getName() {
		return $this->club->name ." ". $this->team;
	}

	protected static $_properties = array(
		'id',
		'team',
		'club_id',
	);

	protected static $_belongs_to = array('club');

	protected static $_many_many = array(
		'competition' => array(
				'table_through' => 'entry',
				'conditions' => array(
					'order_by' => array ('sequence'=>'ASC')),
				));

	protected static $_table_name = 'team';

	public static function find_by_name($name) {
		$matches = array();
		if (preg_match("/(.*) ([0-9]+)/i", $name, $matches)) {
			$rows = DB::query("SELECT t.id FROM team t JOIN club c ON t.club_id = c.id
				WHERE c.name = '${matches[1]}' AND t.team = ${matches[2]}")->execute();

			foreach ($rows as $row) {
				return Model_Team::find($row['id']);
			}
		}

		\Log::warning("Unable to locate team: $name");

		return null;
	}

	public static function findTeam($club, $team) {
		$result = Model_Team::query()
			->related('club')
			->where('team', '=', $team)
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
			return Model_Card::find($row['id']);
		}

		return null;
	}
}
