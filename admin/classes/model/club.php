<?php 
class Model_Club extends \Orm\Model
{
	protected static $_properties = array(
		'id',
		'name',
		'code',
	);

	protected static $_table_name = 'club';

	protected static $_has_many = array('team'=>array(
        ), 'user' => array(
        ));

	protected static $_conditions = array(
		'order_by' => array('name'=>'asc'),
	);

	public function getTeamSizes($sectionName, $stars = true) {
		$result = array();

		$teams = $this->team;
		usort($teams, function($a, $b) {
			return ($a->name - $b->name);
		});
		
		$carry = 0;
		foreach ($teams as $team) {
        	Log::debug("Team; ".$team->name);
			foreach ($team->competition as $competition) {
				if ($competition->section['name'] != $sectionName) continue;

				$size = $competition['teamsize'];
				$starSize = $competition['teamstars'];
				if (!$starSize) $starSize = 0;
        		Log::debug("Competition: ".$competition->name." size=$size stars=$starSize");
				if ($size) {
					if ($stars) {
						$size += $carry;
						$carry = $starSize;
						$size -= $carry;
					}
					$result[] = $size;
					break;
				}
			}
		}

		return $result;
	}

  public function __toString() {
    return "Club(".$this['name']."/".$this['code'].")";
  }

	public static function getAnalysis() {
		// FIXME
		$results = array();
		foreach (DB::query("SELECT DISTINCT c.id, c.name, COALESCE(s.name, s2.name) as sectionName FROM club c 
				LEFT JOIN team t on c.id = t.club_id 
				LEFT JOIN section s on t.section_id = s.id, section s2")->execute() as $row) {
			$club = Model_Club::find_by_id($row['id']);
			$sectionName = $row['sectionName'];
			$teams = $club->getTeamSizes($sectionName);
			$reg = Model_Registration::find_before_date($sectionName, $row['name'], time());
			$summary = $club->getPlayerHistorySummary();
			
			$results[$row['id']] = array('name'=>$row['name'], 'section'=>$sectionName, 'players'=>count($reg), 'teams'=>count($teams), 'reg'=>$reg);
		}

		//print_r($results);

		return $results;
	}

	public function getPlayerHistorySummary() {
		$clubId = $this['id'];
		$req = DB::query("select distinct player, COALESCE(th.name, ta.name) team from incident i 
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

}
