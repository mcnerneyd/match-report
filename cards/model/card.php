<?php
require_once('util.php');

class Card {
  public $home;
  public $away;
  public $competition;

  public function __construct() {
    $home = array('players'=>array());
    $away = array('players'=>array());
  }

	public static function lock($id, $club) {
		$db = Db::getInstance();

		$res = $db->query("SELECT count(1) FROM incident i
				JOIN club c ON i.club_id = c.id
			WHERE matchcard_id = $id AND c.name = '$club' AND i.type = 'Locked'")->fetch();

		if ($res[0] > 0) throw new Exception("Card is already locked");

		$lockCode = substr('0000'.rand(0, 9999), -4);

		$db->exec("INSERT INTO incident (club_id, matchcard_id, type, detail)
			SELECT c.id, $id, 'Locked', '$lockCode'
				FROM club c WHERE c.name = '$club'");

		return $lockCode;
	}

	public static function addNote($id, $user, $msg) {
		$db = Db::getInstance();
		$stmt = $db->prepare("insert into incident (matchcard_id, type, detail) 
				select $id, 'other', :detail");
		$stmt->execute(array(":detail"=>$msg));
	}

  public static function fixtures($club) {
		return Card::fixtureFind($club, null);
	}

	public static function getFixture($id) {
		$f = Card::fixtureFind(null, $id); 

		if (isset($f['card'])) {
			if ($f['competition-strict'] == 'yes') {
				$f['card']['official'][] = 'ALL';
			}
		}

		return $f;
	}

	public static function getDateRange() {
		$year = date('Y');
		$month = date('n');

		if ($month < 6) $year = $year - 1;

		$startDate = strtotime($year.".06.01 00:00");

		$earliestDate = strtotime("$year-09-25");
		$latestDate = strtotime("+ year", $startDate);
		$validate = strtotime(($year+1)."-10-10");

		return array(
			"first"=>date('Y-m-d', $startDate), 
			"start"=>date('Y-m-d', $earliestDate), 
			"finish"=>date('Y-m-d', $latestDate),
			"validate"=>date('Y-m-d', $validate));
	}

	private static function getFixtures() {

		Log::debug("Getting fixtures");

		$allfixtures = array();

        $allfixtures = json_decode(file_get_contents(DATAPATH."/fixtures.json"));

		return $allfixtures;
	}

	private static function fixtureFind($club, $fixtureId) { // FIXME Messy
		$fixtures = Card::getFixtures();

		$comps = Competition::allAll();
		//echo "<!-- Competitions\n".print_r($comps, true)." -->";

    $result = array();

    if ($fixtures) {
      foreach ($fixtures as $fixture) {
				if ($fixtureId != null and $fixture->fixtureID != $fixtureId) continue;

				try {
					$card = Card::convertFixture($fixture, $club, $comps);

					if ($card) {
						$matchcard = Card::getByFixture($fixture->fixtureID);
						if ($matchcard) {
							if (($matchcard['home']['team_id'] and $matchcard['home']['team'] != $card['home']['team'])
							or ($matchcard['away']['team_id'] and $matchcard['away']['team'] != $card['away']['team'])) {
								$db = Db::getInstance();

								// Fixture has changed - disconnect matchcard and fixture
								$db->exec("UPDATE matchcard SET fixture_id = NULL WHERE id = ${matchcard['id']}");
								
							} else {
								$card['cardid'] = $matchcard['id'];
								$card['card'] = $matchcard;
							}
						}

						if ($fixtureId != null) {
							debug("Selected fixture card:".print_r($card,true));
							return $card;
						}

						$result[] = $card;
					}
				} catch (Exception $e) {
					debug("Exception:".print_r($e, true));
					warn($e->getMessage());
				}

      }
    }

		if ($fixtureId != null) {
				$matchcard = Card::getByFixture($fixtureId);
				$fixture = array('home'=>$matchcard['home']['team'],
					'away'=>$matchcard['away']['team'],
					'fixtureID'=>$fixtureId,
					'datetime'=>$matchcard['date'],
					'played'=>!$matchcard['open'],
					'competition'=>$matchcard['competition'],
					'home_score'=>$matchcard['home']['score'],
					'away_score'=>$matchcard['away']['score']);
				$fixture = json_decode(json_encode($fixture));
				$card = Card::convertFixture($fixture, $club, $comps);
				$card['cardid'] = $matchcard['id'];
				$card['card'] = $matchcard;

				return $card;
		}

    debug("Fixtures for $club/$fixtureId:\n".print_r($result,true));

    return $result;
  }

	private static function convertFixture($fixture, $club, $comps) {
		$home = parse($fixture->home);
		$away = parse($fixture->away);

		if ($club != null) {
			if (!($home['club'] == $club or $away['club'] == $club)) return false;
		}

		$recomps = array();

		foreach ($comps as $comp) $recomps[$comp['name']] = $comp;

		$competition = parseCompetition($fixture->competition, array_keys($recomps));

		if ($competition == null) return false;

		$result = array(
			'id'=>$fixture->fixtureID,
      'section'=>$fixture->section,
			'date'=>strtotime($fixture->datetimeZ),
			'datetime'=>$fixture->datetime,
			'org'=>$fixture->competition,
			'home'=>array(
				'org'=>$fixture->home,
				'club'=>$home['club'],
				'score'=>$fixture->home_score,
				'teamnumber'=>$home['team'],
				'team'=>$home['name']),
			'away'=>array(
				'org'=>$fixture->away,
				'club'=>$away['club'],
				'score'=>$fixture->away_score,
				'teamnumber'=>$away['team'],
				'team'=>$away['name']),
			$home['club']=>'home',
			$away['club']=>'away'
			);

		$result['submitted'] = false;
		if ($fixture->played == 'yes') {	
			$result['submitted'] = true;
		}

		$result['competition'] = $competition;

		if (isset($recomps[$competition])) {
			$result['competition-code'] = $recomps[$competition]['code'];
			$result['groups'] = array();
			
			if ($recomps[$competition]['groups']) {
				foreach (explode(',', $recomps[$competition]['groups']) as $group) $result['groups'][] = trim($group);
			}
		} else {
			throw new Exception("Unknown competition: $competition");
		}


		if (in_array(strtolower($result['competition-code']),explode(",", Config::get("config.strict_comps")))) {
			$result['competition-strict'] = 'yes';
		} else {
			$result['competition-strict'] = 'no';
		}

		return $result;
	}


	public static function create($fixture) {

		if (!$fixture) {
			throw new Exception("No fixture specified");
		}

    $db = Db::getInstance();

		$req = $db->query("SELECT id FROM matchcard WHERE fixture_id = ${fixture['id']}");
		
		if ($req->fetch()) {
			throw new Exception("Matchcard already exists for fixture ${fixture['id']}");
		}

		$req = $db->query("SELECT t.id FROM team t JOIN club c ON t.club_id = c.id WHERE 
					t.name =".$fixture['home']['teamnumber']."
					AND c.name ='".$fixture['home']['club']."'");

		$homeId = "null";
		if ($row = $req->fetch()) $homeId = $row[0];

		$req = $db->query("SELECT t.id FROM team t JOIN club c ON t.club_id = c.id WHERE 
					t.name =".$fixture['away']['teamnumber']."
					AND c.name ='".$fixture['away']['club']."'");

		$awayId = "null";
		if ($row = $req->fetch()) $awayId = $row[0];

		if ($homeId == 'null' and $awayId == 'null') {
			throw new Exception("Teams are not from this section");
		}

		if ($homeId == $awayId) {
			throw new Exception("Team cannot play itself");
		}

		$sql = "INSERT INTO matchcard (fixture_id, competition_id, home_id, away_id, date, description)
			SELECT ${fixture['id']}, x.id, $homeId, $awayId, from_unixtime('${fixture['date']}'), ''
				FROM competition x
        LEFT JOIN section s ON x.section_id = s.id
				WHERE x.name = '${fixture['competition']}'
          AND s.name = '${fixture['section']}'";

		debug($sql);

		if (!$db->exec($sql)) {
			throw new Exception("Cannot match competition to team configuration (${fixture['competition']})");
		}

		return $db->lastInsertId();

	}

	public static function getFixtureByCardId($cardId) {
    $db = Db::getInstance();

		$sql = "select fixture_id 
			from matchcard m
			where m.id = :cardId";

    $req = $db->prepare($sql);
    $req->execute(array('cardId'=>$cardId));
    $result = $req->fetch();

		return Card::getFixture($result['fixture_id']);
	}

	// FIXME fixtures may have more that one card - need merge tool
	public static function getByFixture($fixtureId) {
    $db = Db::getInstance();

		$sql = "select id 
			from matchcard m
			where m.fixture_id = :fixtureId";

    $req = $db->prepare($sql);
    $req->execute(array('fixtureId'=>$fixtureId));
    $result = $req->fetch();

		debug("No card matchcard $fixtureId");

		if (!$result) return null;

		debug("Find fixture:".$fixtureId." ".$result[0]);

		return Card::get($result[0]);
	}

	public static function getLastPlayers($club, $team) {
    $db = Db::getInstance();

		$sql = "select player from incident i join 
			(SELECT m.id matchcard_id,c.id club_id FROM matchcard m join team t ON m.home_id = t.id or m.away_id = t.id
				join club c on t.club_id = c.id
			where t.name = :team and c.name = :club and m.date < subdate(current_date, 1)
				and m.date > '".currentSeasonStart()."'
			order by m.date desc
			limit 1) t0 on t0.matchcard_id = i.matchcard_id and t0.club_id = i.club_id";

    $req = $db->prepare($sql);
    $req->execute(array('team'=>$team,'club'=>$club));
		$result = array();
    foreach ($req->fetchAll() as $row) {
			$player = $row['player'];
			if ($player) $result[] = cleanName($player);
		}

		return $result;
	}

  public static function get($id) {
		debug("Get ID=$id");

    $db = Db::getInstance();

    $sql = "select x.name competition, ch.id homeclubid, ch.name homeclub, 
				ca.name awayclub, th.name hometeam, ta.name awayteam, m.date, m.id, m.fixture_id,
				th.id hometeamid, ta.id awayteamid, ca.id awayclubid, x.teamsize,
				ch.code homecode, ca.code awaycode, x.code competitioncode, m.open, x.format
      from matchcard m
        left join team th on th.id = m.home_id
        left join club ch on ch.id = th.club_id
        left join team ta on ta.id = m.away_id
        left join club ca on ca.id = ta.club_id
        left join competition x on x.id = m.competition_id
      where m.id = :id";

    $req = $db->prepare($sql);
    $req->execute(array('id'=>$id));
    $result = $req->fetch();

		if (!$result) return null;

		debug("Card found: ${result['id']}");

    $card = array(
      'id'=>$result['id'],
			'fixture_id'=>$result['fixture_id'],
      'competition'=>$result['competition'],
      'competition-code'=>$result['competitioncode'],
			'leaguematch'=>($result['teamsize'] == null ? false : true),
			'format'=>$result['format'],
      'date'=>date("F j, Y", strtotime($result['date'])),
      'datetime'=>	strtotime($result['date']),
			'open'=>$result['open'],
      'home'=>array(
        'club'=>$result['homeclub'],
        'teamx'=>$result['hometeam'],
				'code'=>$result['homecode'].$result['hometeam'],
				'club_id'=>$result['homeclubid'],
        'team'=>$result['homeclub'].' '.$result['hometeam'],
				'team_id'=>$result['hometeamid'],
        'score'=>0,
        'players'=>array()
				),
      'away'=>array(
        'club'=>$result['awayclub'],
        'teamx'=>$result['awayteam'],
				'code'=>$result['awaycode'].$result['awayteam'],
				'club_id'=>$result['awayclubid'],
        'team'=>$result['awayclub'].' '.$result['awayteam'],
				'team_id'=>$result['awayteamid'],
        'score'=>0,
        'players'=>array()
				));

		// Blend all incidents from all matchcards referencing this fixture
    $sql = "select i.id, i.player, i.club_id, i.type, i.detail, i.date, u.username, i.resolved
        from incident i
					left join user u on i.user_id = u.id
					left join matchcard m on i.matchcard_id = m.id
        where i.type in ('Played', 'Scored', 'Ineligible', 'Yellow Card', 'Red Card', 'Locked', 'Signed', 'Missing', 'Late', 'Other') 
					and m.fixture_id = :id
        order by i.id";

    $req = $db->prepare($sql);
    $req->execute(array('id'=>$result['fixture_id']));

		$locked = null;
		//$card['open'] = false;
		$card['official'] = array();
		$card['rycards'] = array();

    foreach ($req->fetchAll() as $row) {

			if ($row['type'] != 'Played' and $row['resolved'] == 1) continue;

			//$card['open'] = true;

			if ($row['type'] == 'Late') {
				$card['late'] = true;
				continue;
			}

			if ($row['type'] == 'Missing') {
				$card['missing'] = true;
				continue;
			}

      if ($row['club_id'] == $result['homeclubid']) {
        $side = 'home';
      } else {
        $side = 'away';
      }

			$row['side'] = $side;
			$row['club'] = $card[$side]['club'];

			if ($row['type'] == 'Locked') {
				$card[$side]['locked'] = $row['detail'];

				continue;
			}

			if ($row['type'] == 'Signed') {
				$card[$side]['closed'] = strtotime(date('Y-m-d', strtotime($row['date'])).' 23:59');
				$matches = array();
				if (preg_match('/^([0-9]*)\/(.*)(?:;(.*))$/', $row['detail'], $matches) == 1) {
					$card[$side]['umpire'] = $matches[2];
					$card[$side]['oscore'] = $matches[1];
				}

				if (!isset($card[$side]['locked'])) $card[$side]['locked'] = 'Missing Code!';
				continue;
			}

			if ($row['type'] == 'Other') {
				if ($row['detail']) {
					if ($row['detail'][0] == '"') {	// Note
						if (!isset($card['notes'])) $card['notes'] = array();
						$card['notes'][] = array('note'=>substr($row['detail'],1,-1), 'user'=>$row['username']);
						continue;
					}

					if ($row['detail'] == 'Official Umpire') {
						$card['official'][] = $row['username'];
						continue;
					}
				}
			}

			if (!$row['player']) continue;

			$late = false;
			if (strtotime($row['date']) > strtotime($result['date'])) $late = true;

			if (($row['resolved'] == 1) and !$late) continue; 

			$playerName = $row['player'];
			if (!isset($card[$side]['players'][$playerName])) {
				$card[$side]['players'][$playerName] = array('score'=>0,'datetime'=>$row['date']);
			}

			$player = &$card[$side]['players'][$playerName];

			switch ($row['type']) {
				case 'Played':
					if ($row['detail']) {
						$player['detail'] = json_decode($row['detail']);
					}

					if ($late) {
						if ($row['resolved'] == 1) {
							$player['deleted'] = 1;
						}
						$player['late'] = 1;
					}
					break;

				case 'Scored':
					$card[$side]['score'] += $row['detail'];
					$player['score'] += $row['detail'];
					break;

				case 'Ineligible':
					$player['ineligible'] = true;
					break;

				case 'Yellow Card':
					// FIXME if (user('umpire') && $row['role'] != 'umpire') continue 2;

					$card['rycards'][] = array('card'=>'yellow',
						'type'=>$row['type'],
						'detail'=>$row['detail'],
						'player'=>$playerName,
						'side'=>$side);
				 break;

				case 'Red Card':
					// FIXME if (user('umpire') && $row['role'] != 'umpire') continue 2;

					$card['rycards'][] = array('card'=>'red',
						'type'=>$row['type'],
						'detail'=>$row['detail'],
						'player'=>$playerName,
						'side'=>$side);
					break;

			}	// switch type
    }	// foreach players

		if (isset($card['home']['closed']) and isset($card['away']['closed'])) {
			$card['home']['closed'] = true;
			$card['away']['closed'] = true;
		}

    $sql = "select player, detail, c.name club from incident i
      left join club c on c.id = i.club_id
              where type = 'Number' and detail is not null 
              and c.name in ('".$card['home']['club']."','".$card['away']['club']."')";

    $req = $db->query($sql);

    foreach ($req->fetchAll() as $row) {
      if ($row['club'] == $card['home']['club']) {
          $side = 'home';
      } else {
          $side = 'away';
      }

			//$cName = Player::cleanName($row['player']);
			$cName = cleanName($row['player'], "Fn LN");

      if (array_key_exists($cName, $card[$side]['players'])) {
        $card[$side]['players'][$cName]['number'] = $row['detail'];
      }
    }

    return $card;
  }
}
