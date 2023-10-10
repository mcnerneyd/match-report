<?php

require_once('util.php');

class Card
{
    public $home;
    public $away;
    public $competition;

    public function __construct()
    {
        $home = array('players' => array());
        $away = array('players' => array());
    }

    public static function lock($id, $club)
    {
        $db = Db::getInstance();

        $res = $db->query("SELECT count(1) FROM incident i
				JOIN club c ON i.club_id = c.id
			WHERE matchcard_id = $id AND c.name = '$club' AND i.type = 'Locked'")->fetch();

        if ($res[0] > 0) {
            Log::debug("Card is already locked $id (club=$club)");
        }

        $lockCode = substr('0000' . rand(0, 9999), -4);

        $db->exec("INSERT INTO incident (club_id, matchcard_id, type, detail)
			SELECT c.id, $id, 'Locked', '$lockCode'
				FROM club c WHERE c.name = '$club'");

        return $lockCode;
    }

    public static function addNote($id, $user, $msg)
    {
        $db = Db::getInstance();
        $stmt = $db->prepare("insert into incident (matchcard_id, type, detail) 
				select $id, 'other', :detail");
        $stmt->execute(array(":detail" => $msg));
    }

    public static function getFixture($id)
    {
        $f = Card::fixtureFind($id);

        if (isset($f['card'])) {
            if ($f['competition-strict'] == 'yes') {
                $f['card']['official'][] = 'ALL';
            }
        }

        return $f;
    }

    private static function fixtureFind($fixtureId) // FIXME Messy
    {
        Log::info("Fetching fixture: $fixtureId");

        if (!$fixtureId) {
            throw new Exception("Invalid fixture request, no fixture ID specified");
        }

        $fixtures = json_decode(file_get_contents(DATAPATH . "/fixtures.json"));
        if (!$fixtures) {
            throw new Exception("No fixtures available");
        }

        $fixtures = array_filter($fixtures, function ($a) use ($fixtureId) {
            return ($a->status == 'active') && (($a->fixtureID == $fixtureId) || ($a->fixtureID == intval($fixtureId)));
        });
        $fixture = array_pop($fixtures);

        $matchcard = Card::getByFixture($fixtureId);
        $comps = Competition::allAll();

        if ($fixture) {
            $fixture = Card::normalizeFixture($fixture, $comps);

            if ($matchcard) {
                if (
                    ($matchcard['home']['team_id'] and $matchcard['home']['team'] != $fixture['home']['team'])
                    or ($matchcard['away']['team_id'] and $matchcard['away']['team'] != $fixture['away']['team'])
                ) {
                    $db = Db::getInstance();

                    Log::warning("Fixture $fixtureId does not match {$matchcard['id']} - disconnecting");

                    // Fixture has changed - disconnect matchcard and fixture
                    $db->exec("UPDATE matchcard SET fixture_id = NULL WHERE id = {$matchcard['id']}");
                } else {
                    $fixture['cardid'] = $matchcard['id'];
                    $fixture['card'] = $matchcard;
                }
            }

            return $fixture;
        } else {
            Log::warning("No fixture found in fixtures.json for $fixtureId");

            // If the fixture is not in fixtures list, convert the matchcard to a fixture
            if ($matchcard) {
                $fixture = array(
                    'home_club' => $matchcard['home']['club'],
                    'away_club' => $matchcard['away']['club'],
                    'home_team' => $matchcard['home']['teamx'],
                    'away_team' => $matchcard['away']['teamx'],
                    'fixtureID' => $fixtureId,
                    'datetime' => $matchcard['date'],
                    'played' => !$matchcard['open'],
                    'competition' => $matchcard['competition'],
                    'home_score' => $matchcard['home']['score'],
                    'away_score' => $matchcard['away']['score'],
                    'status' => 'active',
                    'src' => "Matchcard"
                );
                $fixture = json_decode(json_encode($fixture));
                $fixture = Card::normalizeFixture($fixture, $comps);
                $fixture['cardid'] = $matchcard['id'];
                $fixture['card'] = $matchcard;

                return $fixture;
            }
        }

        throw new Exception("No such fixture: $fixtureId");
    }

    public static function getDateRange()
    {
        $year = date('Y');
        $month = date('n');

        if ($month < 6) {
            $year = $year - 1;
        }

        $startDate = strtotime($year . ".06.01 00:00");

        $earliestDate = strtotime("$year-09-25");
        $latestDate = strtotime("+ year", $startDate);
        $validate = strtotime(($year + 1) . "-10-10");

        return array(
            "first" => date('Y-m-d', $startDate),
            "start" => date('Y-m-d', $earliestDate),
            "finish" => date('Y-m-d', $latestDate),
            "validate" => date('Y-m-d', $validate)
        );
    }

    private static function normalizeFixture($fixture, $comps)
    {
        $home = parse($fixture->home_club . " " . $fixture->home_team);
        $away = parse($fixture->away_club . " " . $fixture->away_team);

        $recomps = array();

        foreach ($comps as $comp) {
            $recomps[$comp['name']] = $comp;
        }

        $competition = parseCompetition($fixture->competition, array_keys($recomps));

        if ($competition == null) {
            throw new Exception("Unknown competition: {$fixture->competition}");
        }

        $result = array(
            'id' => $fixture->fixtureID,
            'org' => $fixture->competition,
            'home' => array(
                'org' => $fixture->home_club . " " . $fixture->home_team,
                'club' => $home['club'],
                'score' => $fixture->home_score,
                'teamnumber' => $home['team'],
                'team' => $home['name']
            ),
            'away' => array(
                'org' => $fixture->away_club . " " . $fixture->away_team,
                'club' => $away['club'],
                'score' => $fixture->away_score,
                'teamnumber' => $away['team'],
                'team' => $away['name']
            ),
            $home['club'] => 'home',
            $away['club'] => 'away',
            'status' => $fixture->status,
            'src' => 'file',
            'f' => $fixture
        );

        if (isset($fixture->section)) {
            $result['section'] = $fixture->section;
        }
        if (isset($fixture->datetimeZ)) {
            $result['date'] = strtotime($fixture->datetimeZ);
        } else {
            $result['date'] = strtotime($fixture->datetime);
            $result['datetimeZ'] = date('c', $result['date']);
        }

        $result['submitted'] = false;
        if ($fixture->played == 'yes') {
            $result['submitted'] = true;
        }

        $result['competition'] = $competition;

        if (isset($recomps[$competition])) {
            $result['competition-code'] = $recomps[$competition]['code'];
            $result['groups'] = array();

            if ($recomps[$competition]['groups']) {
                foreach (explode(',', $recomps[$competition]['groups']) as $group) {
                    $result['groups'][] = trim($group);
                }
            }
        } else {
            throw new Exception("Error with fixture {$fixture->fixtureID}: unknown competition '$competition'");
        }

        if (in_array(strtolower($result['competition-code']), explode(",", Config::get("config.strict_comps")))) {
            $result['competition-strict'] = 'yes';
        } else {
            $result['competition-strict'] = 'no';
        }

        return $result;
    }


    public static function create($fixture)
    {
        if (!$fixture) {
            throw new Exception("No fixture specified");
        }

        Log::info("Create matchcard for fixture:".print_r($fixture, true));

        $db = Db::getInstance();

        $req = $db->query("SELECT id FROM matchcard WHERE fixture_id = {$fixture['id']}");

        if ($req->fetch()) {
            throw new Exception("Matchcard already exists for fixture {$fixture['id']}");
        }

        $req = $db->prepare("SELECT t.id FROM team t JOIN club c ON t.club_id = c.id WHERE 
					t.name = :teamname
					AND c.name = :clubname");
        $req->bindParam(':teamname', $fixture['home']['teamnumber']);
        $req->bindParam(':clubname', $fixture['home']['club']);
        $req->execute();

        $homeId = "null";
        if ($row = $req->fetch()) {
            $homeId = $row[0];
        }

        $req = $db->prepare("SELECT t.id FROM team t JOIN club c ON t.club_id = c.id WHERE 
					t.name = :teamname
					AND c.name = :clubname");
        $req->bindParam(':teamname', $fixture['away']['teamnumber']);
        $req->bindParam(':clubname', $fixture['away']['club']);
        $req->execute();

        $awayId = "null";
        if ($row = $req->fetch()) {
            $awayId = $row[0];
        }

        if ($homeId == 'null' and $awayId == 'null') {
            throw new Exception("Teams are not from this section");
        }

        if ($homeId == $awayId) {
            throw new Exception("Team cannot play itself");
        }

        $sql = "INSERT INTO matchcard (fixture_id, competition_id, home_id, away_id, date, description)
			SELECT ${fixture['id']}, x.id, $homeId, $awayId, from_unixtime('{$fixture['date']}'), ''
			FROM competition x
                LEFT JOIN section s ON x.section_id = s.id
			WHERE x.name = '${fixture['competition']}'
                AND s.name = '${fixture['section']}'";

        debug($sql);

        if (!$db->exec($sql)) {
            throw new Exception("Cannot match competition to team configuration ({$fixture['competition']})");
        }

        return $db->lastInsertId();
    }

    public static function getFixtureByCardId($cardId)
    {
        $db = Db::getInstance();

        $sql = "select fixture_id 
			from matchcard m
			where m.id = :cardId";

        $req = $db->prepare($sql);
        $req->execute(array('cardId' => $cardId));
        $result = $req->fetch();

        return Card::getFixture($result['fixture_id']);
    }

    // FIXME fixtures may have more that one card - need merge tool
    private static function getByFixture($fixtureId)
    {
        $db = Db::getInstance();

        $sql = "select id 
			from matchcard m
			where m.fixture_id = :fixtureId";

        $req = $db->prepare($sql);
        $req->execute(array('fixtureId' => $fixtureId));
        $result = $req->fetch();

        debug("No fixture $fixtureId");

        if (!$result) {
            return null;
        }

        debug("Find fixture:" . $fixtureId . " " . $result[0]);

        return self::getCardByCardId($result[0]);
    }

    private static function getCardByCardId($cardId)
    {
        debug("Get ID=$cardId");

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
        $req->execute(array('id' => $cardId));
        $result = $req->fetch();

        if (!$result) {
            return null;
        }

        debug("Card found: {$result['id']}");

        $fixture = array(
            'id' => $result['id'],
            'fixture_id' => $result['fixture_id'],
            'competition' => $result['competition'],
            'competition-code' => $result['competitioncode'],
            'leaguematch' => ($result['teamsize'] == null ? false : true),
            'format' => $result['format'],
            'date' => date("F j, Y", strtotime($result['date'])),
            'datetime' => strtotime($result['date']),
            'open' => $result['open'],
            'home' => array(
                'club' => $result['homeclub'],
                'teamx' => $result['hometeam'],
                'code' => $result['homecode'] . $result['hometeam'],
                'club_id' => $result['homeclubid'],
                'team' => $result['homeclub'] . ' ' . $result['hometeam'],
                'team_id' => $result['hometeamid'],
                'score' => 0,
                'players' => array()
            ),
            'away' => array(
                'club' => $result['awayclub'],
                'teamx' => $result['awayteam'],
                'code' => $result['awaycode'] . $result['awayteam'],
                'club_id' => $result['awayclubid'],
                'team' => $result['awayclub'] . ' ' . $result['awayteam'],
                'team_id' => $result['awayteamid'],
                'score' => 0,
                'players' => array()
            )
        );

        // Blend all incidents from all matchcards referencing this fixture
        $sql = "select i.id, i.player, i.club_id, i.type, i.detail, i.date, u.username, i.resolved
        from incident i
					left join user u on i.user_id = u.id
					left join matchcard m on i.matchcard_id = m.id
        where i.type in ('Played', 'Scored', 'Ineligible', 'Yellow Card', 'Red Card', 'Locked', 'Signed', 'Missing', 'Late', 'Other') 
					and m.fixture_id = :id
        order by i.id";

        $req = $db->prepare($sql);
        $req->execute(array('id' => $result['fixture_id']));

        $locked = null;
        //$fixture['open'] = false;
        $fixture['official'] = array();
        $fixture['rycards'] = array();

        foreach ($req->fetchAll() as $row) {
            if ($row['type'] == 'Other') {
                if ($row['detail']) {
                    if ($row['detail'][0] == '"') { // Note
                        if (!isset($fixture['notes'])) {
                            $fixture['notes'] = array();
                        }
                        $fixture['notes'][] = array('note' => substr($row['detail'], 1, -1), 'user' => $row['username'], 'resolved' => $row['resolved']);
                        continue;
                    }
                }
            }

            if ($row['type'] != 'Played' and $row['resolved'] == 1) {
                continue;
            }

            //$fixture['open'] = true;

            if ($row['type'] == 'Late') {
                $fixture['late'] = true;
                continue;
            }

            if ($row['type'] == 'Missing') {
                $fixture['missing'] = true;
                continue;
            }

            if ($row['club_id'] == $result['homeclubid']) {
                $side = 'home';
            } else {
                $side = 'away';
            }

            $row['side'] = $side;
            $row['club'] = $fixture[$side]['club'];

            if ($row['type'] == 'Locked') {
                $fixture[$side]['locked'] = $row['detail'];

                continue;
            }

            if ($row['type'] == 'Signed') {
                $fixture[$side]['closed'] = strtotime(date('Y-m-d', strtotime($row['date'])) . ' 23:59');
                $matches = array();
                if (preg_match('/^([0-9]*)\/(.*)(?:;(.*))$/', $row['detail'], $matches) == 1) {
                    $fixture[$side]['umpire'] = $matches[2];
                    $fixture[$side]['oscore'] = $matches[1];
                }

                if (!isset($fixture[$side]['locked'])) {
                    $fixture[$side]['locked'] = 'Missing Code!';
                }
                continue;
            }

            if ($row['type'] == 'Other') {
                if ($row['detail']) {
                    if ($row['detail'] == 'Official Umpire') {
                        $fixture['official'][] = $row['username'];
                        continue;
                    }
                }
            }

            if (!$row['player']) {
                continue;
            }

            $late = false;
            if (strtotime($row['date']) > strtotime($result['date'])) {
                $late = true;
            }

            if (($row['resolved'] == 1) and !$late) {
                continue;
            }

            $playerName = $row['player'];
            if (!isset($fixture[$side]['players'][$playerName])) {
                $fixture[$side]['players'][$playerName] = array('score' => 0, 'datetime' => $row['date']);
            }

            $player = &$fixture[$side]['players'][$playerName];

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
                    $fixture[$side]['score'] += $row['detail'];
                    $player['score'] += $row['detail'];
                    break;

                case 'Ineligible':
                    $player['ineligible'] = true;
                    break;

                case 'Yellow Card':
                    // FIXME if (user('umpire') && $row['role'] != 'umpire') continue 2;

                    $fixture['rycards'][] = array(
                        'card' => 'yellow',
                        'type' => $row['type'],
                        'detail' => $row['detail'],
                        'player' => $playerName,
                        'side' => $side
                    );
                    break;

                case 'Red Card':
                    // FIXME if (user('umpire') && $row['role'] != 'umpire') continue 2;

                    $fixture['rycards'][] = array(
                        'card' => 'red',
                        'type' => $row['type'],
                        'detail' => $row['detail'],
                        'player' => $playerName,
                        'side' => $side
                    );
                    break;
            } // switch type
        } // foreach players

        if (isset($fixture['home']['closed']) and isset($fixture['away']['closed'])) {
            $fixture['home']['closed'] = true;
            $fixture['away']['closed'] = true;
        }

        $req = $db->prepare("SELECT player, detail, c.name club from incident i
						left join club c on c.id = i.club_id
						where type = 'Number' and detail is not null 
						and c.name in (:homeclub, :awayclub)");
        $req->bindParam(':homeclub', $fixture['home']['club']);
        $req->bindParam(':awayclub', $fixture['away']['club']);
        $req->execute();

        foreach ($req->fetchAll() as $row) {
            if ($row['club'] == $fixture['home']['club']) {
                $side = 'home';
            } else {
                $side = 'away';
            }

            //$cName = Player::cleanName($row['player']);
            $cName = cleanName($row['player'], "Fn LN");

            if (array_key_exists($cName, $fixture[$side]['players'])) {
                $fixture[$side]['players'][$cName]['number'] = $row['detail'];
            }
        }

        return $fixture;
    }
}