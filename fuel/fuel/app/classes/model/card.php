<?php

class Model_Card extends \Orm\Model
{
	protected static $_properties = array(
		'id',
		'fixture_id',
		'date',
		'competition_id',
		'home_id',
		'away_id',		
		'contact_id',
		'open',
	);

	protected static $_table_name = 'matchcard';

	protected static $_has_one = array(
		'competition' => array(
			'model_to'=>'Model_Competition',
			'key_from'=>'competition_id',
			'key_to'=>'id',
		),
		'home' => array(
			'model_to'=>'Model_Team',
			'key_from'=>'home_id',
			'key_to'=>'id',
		),
		'away' => array(
			'model_to'=>'Model_Team',
			'key_from'=>'away_id',
			'key_to'=>'id',
		),
		'user' => array(
			'model_to'=>'Model_User',
			'key_from'=>'contact_id',
			'key_to'=>'id',
		),
	);

	public static function createCard($fixtureId) {
		try {
			$fixture = Model_Fixture::get($fixtureId);

			$comp = Model_Competition::find_by_name($fixture['competition']);
			$comp = $comp->id;
			$home = Model_Team::find_by_name($fixture['home']);
			$home = $home->id;
			$away = Model_Team::find_by_name($fixture['away']);
			$away = $away->id;

			//echo "$fixtureId=${fixture['competition']}->$comp, ${fixture['home']}->$home, ${fixture['away']}->$away\n";

			DB::insert('matchcard', array('fixture_id','home_id','away_id','competition_id'))
				->values(array($fixtureId, $home, $away, $comp))->execute();

			//DB::query("INSERT INTO matchcard (fixture_id, home_id, away_id, competition_id)
			//	values ($fixtureId, $home, $away, $comp})")->execute();

			Log::info("Created new card for $fixtureId");

			return true;
		} catch (Exception $e) {
			Log::warning("Failed to create card (fixtureId=$fixtureId): ".$e->getMessage());

			return false;
		}
	}

	public static function search($query) {
		if ($query == '') return array();

		$sql = "select m.id, m.fixture_id, m.date, x.name competition, 
				ch.id home_id, ch.name home_name, th.team home_team, 
				ca.id away_id, ca.name away_name, ta.team away_team
			from matchcard m
				left join competition x on m.competition_id = x.id
				left join team th on m.home_id = th.id
				join club ch on th.club_id = ch.id
				left join team ta on m.away_id = ta.id
				join club ca on ta.club_id = ca.id where 1=1 ";

		$output_array = array();
		if (preg_match_all("/([^\s\"']+)|\"([^\"]*)\"|'([^']*)'/", $query, $output_array)) {
			$output_array[0] = array($query);
			foreach ($output_array as $qs) {
				$allMatch = false;
				foreach ($qs as $q) {
					if (trim($q) == "") continue;

					$match = false;

					$sql .= "\n/* Q:$q */ ";		// FIXME SQL Injection
					$matches = \DB::query("select id 
						from club
						where name like '%$q%' or code='$q'")->execute();

					if ($matches->count()) {
						$match = true;
						$clubIds = join(',', array_keys($matches->as_array('id')));
						$sql .= "and (ch.id in ($clubIds) or ca.id in ($clubIds)) /* Club $q */ ";
					}

					$matches = \DB::query("select id 
						from competition
						where name like '%$q%' or code='$q'")->execute();

					if ($matches->count()) {
						$match = true;
						$compIds = join(',', array_keys($matches->as_array('id')));
						$sql .= "and (x.id in ($compIds)) /* Competition $q */ ";
					}

					if (preg_match("/^[0-9]{4}-[0-9]{1,2}-[0-9]{1,2}$/", $q)) {
						$match = true;
						$sql .= "and m.date = '$q'";
					}

					if (preg_match("/^[0-9]+$/", $q)) {
						$match = true;
						$sql .= "and (m.fixture_id = $q or m.id = $q)";
					}

					if ($match && $q = $query) $allMatch = true;
				}

				if ($allMatch) break;
			}
		}

		$sql .=" order by date desc";

		Log::info("Search query: $sql");

		return \DB::query($sql)->execute();
	}

	public static function find_by_fixture($fixtureid, $createAsNeeded = false) {
		$ids = \DB::query("SELECT id FROM matchcard WHERE fixture_id=".$fixtureid)->execute();

		foreach ($ids as $id) {
			return Model_Card::card($id['id']);
		}

		if (!$createAsNeeded) return null;
			$xFixture = null;
			foreach (Model_Fixture::getAll() as $fixture) {
				if ($fixture['fixtureID'] == $fixtureid) {
					$xFixture = $fixture;
					break;
				}
			}

			if ($xFixture == null) return null;		// No such fixture

			Log::debug("Creating matchcard for $fixtureid");

			if (\Session::get('username') === 'admin') $user_id = 0;
			else $user_id = static::first("SELECT id FROM user WHERE username = '".\Session::get('username')."'");
			$home_id = static::first("SELECT t.id FROM team t JOIN club c ON t.club_id = c.id 
				WHERE c.name = '${xFixture['home_club']}' AND t.team = ${xFixture['home_team']}");
			if (!$home_id) $home_id = 'null';
			$away_id = static::first("SELECT t.id FROM team t JOIN club c ON t.club_id = c.id 
				WHERE c.name = '${xFixture['away_club']}' AND t.team = ${xFixture['away_team']}");
			if (!$away_id) $away_id = 'null';
			$competition_id = static::first("SELECT id FROM competition WHERE name = '${xFixture['competition']}'");
			
			$sql="INSERT INTO matchcard (fixture_id, competition_id, home_id, away_id, contact_id)
					VALUES ($fixtureid, $competition_id, $home_id, $away_id, $user_id)";

			$res=\DB::query($sql)->execute();

			Log::info(print_r($res, true));

			$card = Model_Card::find_by_fixture($fixtureid, false);

			Log::info("Created card for fixture: fixtureid=".$fixtureid." id=".$card['id']);

			return $card;
	}

	private static function first($sql) {

		foreach (DB::query($sql)->execute() as $row) {
			return array_shift($row);
		}

		return null;
	}

	public static function incidents($cardId) {
		return \DB::query("select * from incident i left join club c on i.club_id = c.id where matchcard_id = $cardId")->execute();
	}

	private static function &arr_get(&$arr, $subindex) {
		if (!isset($arr[$subindex])) $arr[$subindex] = array();

		return $arr[$subindex];
	}

	private static function arr_add(&$arr, $subindex, $val) {
		if (!isset($arr[$subindex])) $arr[$subindex] = array();

		$arr[$subindex][] = $val;
	}

	public static function card($id) {

		if (!$id) return null;

		$cards = \DB::query("select m.id, m.fixture_id, 
				date_format(m.date, '%Y-%m-%d %H:%i:%S') date, 
				x.name competition, 
				ch.id home_id, ch.name home_name, th.team home_team, 
				ca.id away_id, ca.name away_name, ta.team away_team,
				m.open
			from matchcard m
				left join competition x on m.competition_id = x.id
				left join team th on m.home_id = th.id
				left join club ch on th.club_id = ch.id
				left join team ta on m.away_id = ta.id
				left join club ca on ta.club_id = ca.id
			where m.id = $id
				")->execute();

		if (count($cards) < 1) return null;
		$card = $cards[0];

		// Verify that the fixture is still valid
		$fixture = Model_Fixture::get($card['fixture_id']);
		if ($fixture == null) {
			throw new Exception("Card $id is associated with non-existant fixture");
		}

		if ($card['date']) {
			$card['date'] = \Date::create_from_string($card['date'], '%Y-%m-%d %H:%M:%S');
		}

		$card['home'] = array('club'=>null, 'team'=>null, 'players'=>array(), 'signed'=>false,
			'goals'=>0, 'scorers'=>array(), 'fines'=>array());
		$card['away'] = array('club'=>null, 'team'=>null, 'players'=>array(), 'signed'=>false, 
			'goals'=>0, 'scorers'=>array(), 'fines'=>array());

		if ($card['home_id'] != null) {
			$card['home']['club'] = $card['home_name'];
			$card['home']['team'] = $card['home_team'];
			$card['home']['club_id'] = $card['home_id'];
			$numbers['home'] = Model_Card::numberTable($card['home_id']);
		}
		if ($card['away_id'] != null) {
			$card['away']['club'] = $card['away_name'];
			$card['away']['team'] = $card['away_team'];
			$card['away']['club_id'] = $card['away_id'];
			$numbers['away'] = Model_Card::numberTable($card['away_id']);
		}
		$card['goals'] = array();
		$card['home']['incidents'] = array();
		$card['away']['incidents'] = array();

		$incidents = \DB::query("select i.id, player, club_id, type, detail, date, resolved
			from incident i where matchcard_id = $id")->execute();

		foreach ($incidents as $incident) {
			if ($incident['club_id'] == $card['home_id']) $key = 'home';
			if ($incident['club_id'] == $card['away_id']) $key = 'away';

			if (!isset($key)) {
				 continue;
			}

			if ($incident['player']) {
				//$playerName = Model_Card::cleanName($incident['player']);
				$playerName = cleanName($incident['player'], "LN, Fn");

				if (isset($card[$key]['players'][$playerName])) {
					$player = $card[$key]['players'][$playerName];
				} else {
					$player = array('number'=>'', 'date'=>Date::create_from_string($incident['date'], '%F %T'));
					if (isset($numbers[$key][$incident['player']])) $player['number'] = $numbers[$key][$incident['player']];
				}

				$card[$key]['players'][$playerName] = $player;
			}

			switch ($incident['type']) {
				case 'Played':
					if ($incident['resolved'] == 1) continue;
					if ($incident['detail']) {
						$card[$key]['players'][$playerName]['detail'] = $incident['detail'];
					}
					break;
				case 'Scored':
					if ($incident['resolved'] == 1) continue;
					$card[$key]['goals'] = $card[$key]['goals'] + $incident['detail'];
					if (isset($card[$key]['scorers'][$playerName])) $score = $card[$key]['scorers'][$playerName];
					else $score = 0;
					$card[$key]['scorers'][$playerName] = $score + $incident['detail'];
					break;
				case 'Missing':
					$card[$key]['fines'][] = array('Missing'=>$incident['detail'], "resolved"=>$incident['resolved']);
					break;
				case 'Signed':
					if ($incident['resolved'] == 1) continue;
					if (preg_match("/^([0-9]+)?(?:\/([^;]*))?(?:;(.*))?$/i", $incident['detail'], $output_array)) {
						$card[$key]['signed'] = true;
						$oppositionScore = $output_array[1];
						if ($oppositionScore === "") $oppositionScore = 0;
						$card[$key.'-opposition-score'] = $oppositionScore;
						if (count($output_array) > 2) $card[$key]['umpire'] = $output_array[2];
					}
					break;
				case 'Yellow Card':
				case 'Red Card':
					if ($incident['resolved'] == 1) continue;
					self::arr_add($card[$key],'penalties', array(
						'player'=>$incident['player'], 
						'penalty'=>$incident['type'],
						'detail'=>$incident['detail']));
					break;
				default:
					$card[$key]['incidents'][] = $incident;
					continue;
			}
		}

		if (!$card['home']['signed']) {
			if (isset($card['away-opposition-score'])) $card['home']['goals'] = $card['away-opposition-score'];
		}

		if (!$card['away']['signed']) {
			if (isset($card['home-opposition-score'])) $card['away']['goals'] = $card['home-opposition-score'];
		}

		if ($card['away']['signed'] && $card['home']['signed']) {
			$card['signed'] = true;
		}

		if (!isset($card['home']['captain'])) {
			$players = array_keys($card['home']['players']); 
			if ($players) {
				$card['home']['captain'] = $players[0];
			}
		}
		if (!isset($card['away']['captain'])) {
			$players = array_keys($card['away']['players']); 
			if ($players) {
				$card['away']['captain'] = $players[0];
			}
		}

//		unset($card['home_id']);
//		unset($card['home_name']);
//		unset($card['home_team']);
//		unset($card['away_id']);
//		unset($card['away_name']);
//		unset($card['away_team']);
//		unset($card['home-opposition-score']);
//		unset($card['away-opposition-score']);

	//	print_r($card);

		$card['description'] = $card['competition'].":".
			$card['home']['club']." ".$card['home']['team']." v ".
			$card['away']['club']." ".$card['away']['team'];


		return $card;
	}

	private static function cleanName($name) {
		$a = strpos($name, ',');	

		if (!$a) return $name;

		return trim(substr($name, $a+1))." ".ucwords(strtolower(substr($name, 0, $a)));
	}

	private static function numberTable($clubId) {
		$list = \DB::query("select player, detail
				from incident i
			where type = 'Number'
				and detail is not null
				and trim(detail) <> ''
					and club_id = $clubId
			order by i.id desc")->execute();

		$table = array();
		foreach ($list as $item) {
			if (!isset($item['player'])) continue;

			$table[$item['player']] = $item['detail'];
		}

		return $table;
	}

	public static function incompleteCards($delay, $playerCount) {
		$list = \DB::query("select m.id, m.fixture_id, i.club_id, count(i.id) 
				from matchcard m
				left join incident i on i.matchcard_id = m.id 
					and type = 'Played' 
					and i.date between m.date and date_add(m.date, interval $delay minute)
				where m.fixture_id is not null
					and m.open < 60
					and m.date < now()
				group by m.id, m.fixture_id, i.club_id
				having count(i.id) < $playerCount
				order by m.date")->execute();

		return $list;
	}

	public static function unclosedCards() {
		$list = \DB::query("select distinct t0.id from
				(select m.fixture_id, m.id, club_id, m.date
					from matchcard m join team t on m.home_id = t.id
					where m.open < 60 and m.date < now()
				union
				select m.fixture_id, m.id, club_id, m.date 
					from matchcard m join team t on m.away_id = t.id
					where m.open < 60 and m.date < now()) t0
					left join incident i 
						on t0.id = i.matchcard_id 
							and t0.club_id = i.club_id 
							and i.type = 'Signed'
				where i.id is null
				order by t0.date")->execute();

		return $list;
	}

	public static function fixturesWithoutMatchcards($fixtureIds) {
		$missing = array();
		sort($fixtureIds);
		$dbIds = \DB::query("select fixture_id from matchcard where fixture_id is not null order by fixture_id")->execute();	

		$currentId = array_shift($fixtureIds);
		foreach ($dbIds as $id) {
			if (!$currentId) break;

			$id = $id['fixture_id'];

			if ($currentId > $id) continue;

			while ($currentId <= $id) {
				if ($currentId < $id) $missing[] = $currentId;

				$currentId = array_shift($fixtureIds);				
				if ($currentId == null) break;
			}
		}

		$missing = array_merge($missing, $fixtureIds);

		return $missing;
	}
}
