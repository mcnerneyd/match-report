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

	public function getTeamSizes($stars = true) {
		$result = array();

		$carry = 0;
		foreach ($this->team as $team) {
			foreach ($team->competition as $competition) {
				$size = $competition['teamsize'];
				if ($size) {
					if ($stars) {
						$size += $carry;
						$carry = $competition['teamstars'];
						$size -= $carry;
					}
					$result[] = $size;
					break;
				}
			}
		}

		return $result;
	}

	public function getPlayerHistorySummary() {
		$clubId = $this['id'];
		$req = DB::query("select distinct player, COALESCE(th.team, ta.team) team from incident i 
					join matchcard m on i.matchcard_id = m.id
					left join team th on m.home_id = th.id and th.club_id = $clubId
					left join team ta on m.away_id = ta.id and ta.club_id = $clubId
					where i.club_id = $clubId");

		$result = array();
		foreach ($req->execute() as $row) {
			$playerName = cleanName($row['player']);
			$team = $row['team'];
			if (!isset($result[$playerName])) {
				$result[$playerName] = array('teams'=>array());
			}

			if (!in_array($team, $result[$playerName]['teams'])) {
				$result[$playerName]['teams'][] = $team;
			}
		}

		return $result;
	}

	public static function parse($str) {
		$config = Config::get("config.pattern.team");

		$patterns = array();
		$replacements = array();
		foreach ($config as $pattern) {
			if (trim($pattern) == '') break;
			$parts = explode($pattern[0], $pattern);
			if (count($parts) < 3) continue;
			$patterns[] = "/${parts[1]}/i";
			$replacements[] = $parts[2];
		}

		$str = preg_replace($patterns, $replacements, trim($str));

		if ($str == '!') return null;

		$matches = array();
		if (!preg_match('/^([a-z ]*[a-z])(?:\s+([0-9]+))?$/i', trim($str), $matches)) {
			//Log::warning("Cannot match '$str'");
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

}
