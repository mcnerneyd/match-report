<?php

class Model_Matchcard extends \Orm\Model
{
    protected static $_properties = array(
        'id',
        'fixture_id',
        'date',
        'competition_id',
        'home_id',
        'away_id',
        'open',
    );

    public function __toString()
    {
        return "Card(${this['fixture_id']}/${this['id']}=".$this['competition']['name'].":".$this['home']['club']['name']."^".$this['away']['club']['name'].")";
    }

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
    );

    protected static $_has_many = array(
        'incidents' => array(
            'key_to' => 'matchcard_id',
        ),
    );

    public static function getAll($fixtureIds)
    {
        if (!$fixtureIds) {
            return array();
        }

        $sql = "select type, fixture_id, c.name, sum(case when type = 'Scored' then detail else 1 end) value
                    from incident i
                    join matchcard m on m.id = i.matchcard_id
                    join club c on c.id = i.club_id
                where type in ('Played', 'Scored') and fixture_id in (".join(",", $fixtureIds).")
                group by type, fixture_id, c.name";

        $rows = DB::query($sql)->execute();

        $result = array();
        foreach ($rows as $row) {
            $result[$row['type'].$row['fixture_id'].$row['name']] = $row['value'];
        }

        return $result;
    }


    public static function getx($fixtureId)
    {
        if (!self::createMatchcard($fixtureId)) {
            return null;
        }

        return Model_Matchcard::find_by_fixture_id($fixtureId);
    }

    public static function createMatchcard($fixtureId)
    {
        try {
            $fixture = Model_Fixture::get($fixtureId);
            $section = Model_Section::find_by_name($fixture['section']);
            $comp = Model_Competition::find_by_name($fixture['competition'])->id;
            $home = Model_Team::find_by_name($fixture['home'], $section)->id;
            $away = Model_Team::find_by_name($fixture['away'], $section)->id;

            DB::insert('matchcard', array('fixture_id','home_id','away_id','competition_id','description'))
                ->values(array($fixtureId, $home, $away, $comp, ""))->execute();

            Log::info("Created new card for $fixtureId");
            Model_Incident::log("create_matchcard", $fixture['id'], "");

            return true;
        } catch (Exception $e) {
            Log::warning("Failed to create card (fixtureId=$fixtureId): ".$e);

            return false;
        }
    }

    public static function find_by_key($key)
    {
        return DB::query("SELECT m.id FROM matchcard m 
          LEFT JOIN competition x ON m.competition_id = x.id
          LEFT JOIN section s ON x.section_id = s.id
          LEFT JOIN team th ON m.home_id = th.id 
          LEFT JOIN club ch ON th.club_id = ch.id
          LEFT JOIN team ta ON m.away_id = ta.id 
          LEFT JOIN club ca ON ta.club_id = ca.id
          WHERE lower(replace(concat( s.name,'.',x.name,'.',ch.name,th.name,'.',ca.name,ta.name),' ','')) = '$key'")->execute();
    }

    public static function search($query)
    {
        if ($query == '') {
            return array();
        }

        $sql = "select m.id, m.fixture_id, m.date, x.name competition, 
				ch.id home_id, ch.name home_name, th.name home_team, 
				ca.id away_id, ca.name away_name, ta.name away_team,
				(select count(*) from incident i where matchcard_id = m.id and ch.id = club_id and i.type = 'Played' and i.resolved = 0) home_count,
				(select count(*) from incident i where matchcard_id = m.id and ca.id = club_id and i.type = 'Played' and i.resolved = 0) away_count,
				(select sum(detail) from incident i where matchcard_id = m.id and ch.id = club_id and i.type = 'Scored' and i.resolved = 0) home_score,
				(select sum(detail) from incident i where matchcard_id = m.id and ca.id = club_id and i.type = 'Scored' and i.resolved = 0) away_score
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
                    if (trim($q) == "") {
                        continue;
                    }

                    $q = str_replace("'", "", $q);

                    $match = false;

                    $sql .= "\n/* Q:$q */ ";
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

                    if ($match && $q = $query) {
                        $allMatch = true;
                    }
                }

                if ($allMatch) {
                    break;
                }
            }
        }

        $sql .=" order by date desc";

        Log::info("Search query: $sql");

        return \DB::query($sql)->execute();
    }

    public static function search2($q, $limit = -1, $offset = 0)
    {
        $sql = " (
		SELECT m.id, CONCAT_WS('/',x.name, ch.name, th.name, ca.name, ta.name) d FROM matchcard m
			JOIN competition x ON m.competition_id = x.id
			JOIN team th ON m.home_id = th.id
			JOIN club ch ON th.club_id = ch.id
			JOIN team ta ON m.away_id = ta.id
			JOIN club ca ON ta.club_id = ca.id) x";

        if ($q) {
            $sql .= " WHERE ".
            implode(
                ' AND ',
                array_map(function ($x) {
                    return "x.d like '%".str_replace(array('/',"'"), '', $x)."%'";
                }, $q)
            );
        }

        Log::info("Search query2: $sql");

        if ($limit < 0) {
            $sql = "SELECT COUNT(*) as count FROM $sql";
            foreach (\DB::query($sql)->execute() as $row) {
                return $row['count'];
            }
            return 0;
        } else {
            $sql = "SELECT id FROM $sql LIMIT $limit OFFSET $offset";
            foreach (\DB::query($sql)->execute() as $row) {
                $result[] = Model_Matchcard::card($row['id']);
            }
            return $result;
        }
    }

    public static function find_by_fixture($fixtureid, $createAsNeeded = false)
    {
        $t0 = milliseconds();

        $ids = \DB::query("SELECT id FROM matchcard WHERE fixture_id=".$fixtureid)->execute();

        foreach ($ids as $id) {
            $c = Model_Matchcard::card($id['id']);
            Log::debug("fbfTime:".(milliseconds()-$t0));
            return $c;
        }

        if (!$createAsNeeded) {
            return null;
        }
        $xFixture = null;
        foreach (Model_Fixture::getAll() as $fixture) {
            if ($fixture['fixtureID'] == $fixtureid) {
                $xFixture = $fixture;
                break;
            }
        }

        if ($xFixture == null) {
            return null;
        }		// No such fixture

        Log::debug("Creating matchcard for $fixtureid");

        if (\Session::get('username') === 'admin') {
            $user_id = 0;
        } else {
            $user_id = static::first("SELECT id FROM user WHERE username = '".\Session::get('username')."'");
        }
        $home_id = static::first("SELECT t.id FROM team t JOIN club c ON t.club_id = c.id 
				WHERE c.name = '${xFixture['home_club']}' AND t.name = ${xFixture['home_team']}");
        if (!$home_id) {
            $home_id = 'null';
        }
        $away_id = static::first("SELECT t.id FROM team t JOIN club c ON t.club_id = c.id 
				WHERE c.name = '${xFixture['away_club']}' AND t.name = ${xFixture['away_team']}");
        if (!$away_id) {
            $away_id = 'null';
        }
        $competition_id = static::first("SELECT id FROM competition WHERE name = '${xFixture['competition']}'");

        $sql="INSERT INTO matchcard (fixture_id, competition_id, home_id, away_id)
					VALUES ($fixtureid, $competition_id, $home_id, $away_id)";

        $res=\DB::query($sql)->execute();

        Log::info(print_r($res, true));

        $card = Model_Matchcard::find_by_fixture($fixtureid, false);

        Log::info("Created card for fixture: fixtureid=".$fixtureid." id=".$card['id']);

        return $card;
    }

    private static function first($sql)
    {
        foreach (DB::query($sql)->execute() as $row) {
            return array_shift($row);
        }

        return null;
    }

    public static function incidents($cardId)
    {		// FIXME: SQL Injection
        return \DB::query("select i.*, c.name, u.username from incident i 
            left join club c on i.club_id = c.id 
            left join user u on i.user_id = u.id 
            where matchcard_id = $cardId")->execute();
    }

    private static function arr_add(&$arr, $subindex, $val)
    {
        if (!isset($arr[$subindex])) {
            $arr[$subindex] = array();
        }

        $arr[$subindex][] = $val;
    }

    public static function card2($id)
    {
        $card = Model_Matchcard::find_by_id($id);

        $result = $card->to_array();

        $result['home'] = $card->home->to_array();
        $result['away'] = $card->away->to_array();
        $result['competition'] = $card->competition->to_array();

        $result['incidents'] = array();
        foreach ($card->incidents as $incident) {
            $incident = $incident->to_array();
            $result['incidents'][] = $incident;
            continue;

            $side = null;
            if ($incident->club->id === $card->home->club_id) {
                $side = &$result['home'];
            } elseif ($incident->club->id === $card->away->club_id) {
                $side = &$result['away'];
            }

            if (!$side) {
                $incident = $incident->to_array();
                $result['incidents'][] = $incident;
                continue;
            } else {
                $incident = $incident->to_array();
                $player = cleanName($incident['player'], "LN, Fn");
                if ($player) {
                    $side['players'][$player][$incident['type']][] = $incident;
                }
                unset($side);
            }
        }

        return $result;
    }

    public static function card($id)
    {
        if (!$id) {
            throw new InvalidArgumentException("Card ID must be provided");
        }

        if (!is_numeric($id)) {
            throw new InvalidArgumentException("Card ID is invalid: $id");
        }

        Log::debug("Requesting card $id");

        $cards = \DB::query("select m.id, m.fixture_id, 
				date_format(m.date, '%Y-%m-%d %H:%i:%S') date, 
				x.name competition, 
				ch.id home_id, ch.name home_name, th.name home_team, 
				ca.id away_id, ca.name away_name, ta.name away_team,
				m.open,
                s.name as section
			from matchcard m
				left join competition x on m.competition_id = x.id
                left join section s on x.section_id = s.id
				left join team th on m.home_id = th.id
				left join club ch on th.club_id = ch.id
				left join team ta on m.away_id = ta.id
				left join club ca on ta.club_id = ca.id
			where m.id = $id
				")->execute();

        if (count($cards) < 1) {
            return null;
        }

        $card = $cards[0];

        // Verify that the fixture is still valid
        $fixture = Model_Fixture::get($card['fixture_id']);

        return self::build_card($card, $fixture);
    }

    public static function expandFixtures($fixtures, $after = null)
    {
        if ($after != null) {
            foreach (DB::query("SELECT * FROM incident 
                WHERE type in ('Scored','Played','Other') AND date > FROM_UNIXTIME($after)
                LIMIT 1")->execute() as $r) return true;
            return false;
        }

        $result = array_combine(array_column($fixtures, 'fixtureID'), $fixtures);

        foreach (DB::query("SELECT m.fixture_id, h.club_id = i.club_id AS home, SUM(detail) AS score 
                FROM incident i 
                    JOIN matchcard m ON i.matchcard_id = m.id
                    JOIN team h ON m.home_id = h.id
                WHERE type = 'Scored' AND resolved = 0 
                GROUP BY m.fixture_id, h.club_id = i.club_id")->execute() as $r) {
            if (isset($result[$r['fixture_id']])) {
                $result[$r['fixture_id']][($r['home'] ? "home" : "away")."_reported_score"] = $r['score'];
            }
        }

        foreach (DB::query("SELECT m.fixture_id, h.club_id = i.club_id AS home, COUNT(*) AS players 
                FROM incident i 
                    JOIN matchcard m ON i.matchcard_id = m.id
                    JOIN team h ON m.home_id = h.id
                WHERE type = 'Played' AND resolved = 0 
                GROUP BY m.fixture_id, h.club_id = i.club_id")->execute() as $r) {
            if (isset($result[$r['fixture_id']])) {
                $result[$r['fixture_id']][($r['home'] ? "home" : "away")."_players"] = $r['players'];
            }
        }

        foreach (DB::query("SELECT m.fixture_id, i.detail, u.username, i.resolved
                FROM incident i JOIN matchcard m ON i.matchcard_id = m.id
                    LEFT JOIN user u ON i.user_id = u.id
                WHERE i.type = 'Other'")->execute() as $r) {
            if (isset($result[$r['fixture_id']])) {
                $notes = $result[$r['fixture_id']]['notes'] ?? array();
                $notes[] = array('v' => trim($r['detail'], '"'), 'u' => $r['username'], "r" => ($r['resolved'] == 1));
                $result[$r['fixture_id']]['notes'] = $notes;
            }
        }

        return array_values($result);
    }

    public static function cardsFromFixtures($fixtures)
    {
        $fixtureIds = array();

        foreach ($fixtures as $fixture) {
            $fixtureIds[$fixture['fixtureID']] = $fixture;
        }

        $cards = \DB::query("select m.id, m.fixture_id, 
				date_format(m.date, '%Y-%m-%d %H:%i:%S') date, 
				x.name competition, 
				ch.id home_id, ch.name home_name, th.name home_team, 
				ca.id away_id, ca.name away_name, ta.name away_team,
				m.open,
                s.name as section,
                sum(case when i.club_id = th.club_id then i.detail else 0 end) home_score,
                sum(case when i.club_id = ta.club_id then i.detail else 0 end) away_score
			from matchcard m
				left join competition x on m.competition_id = x.id
                left join section s on x.section_id = s.id
				left join team th on m.home_id = th.id
				left join club ch on th.club_id = ch.id
				left join team ta on m.away_id = ta.id
				left join club ca on ta.club_id = ca.id
                left join incident i on i.matchcard_id = m.id and i.type = 'Scored' and i.resolved = false
			where m.fixture_id in (".implode(",", array_keys($fixtureIds)).")
            group by m.id")->execute();

        $fixtures = array();

        foreach ($cards as $card) {
            $fixture = $fixtureIds[$card['fixture_id']];
            if (($card['home_score'] == $fixture['home_score']) &&
                ($card['away_score'] == $fixture['away_score'])) {
                continue;
            }
            Log::debug("Score: ".$card['home_score']." ".$card['away_score'].
                " == ".$fixture["home_score"]." ".$fixture["away_score"]);
            $fixture['card'] = self::build_card($card, $fixture);
            $fixtures[] = $fixture;
        }

        return $fixtures;
    }

    private static function build_card($card, $fixture)
    {
        $t0 = milliseconds();

        if ($fixture != null) {
            $card['comment'] = isset($fixture['comment']) ? $fixture['comment'] : "";
            //throw new Exception("Card $id is associated with non-existant fixture (id=".$card['fixture_id'].")");
        } else {
            $card['comment'] = 'No fixture';
        }

        $t2 = milliseconds();

        if ($card['date']) {
            // Bad date goes to end of season
            if ($card['date'] == '0000-00-00 00:00:00') {
                $card['date'] = '2050-07-31 07:00:00';
            }

            $card['date'] = \Date::create_from_string($card['date'], '%Y-%m-%d %H:%M:%S');
        }

        $card['home'] = array('club'=>null, 'team'=>null, 'players'=>array(), 'signed'=>false,
            'goals'=>0, 'scorers'=>array(), 'fines'=>array(), 'notes'=>array());
        $card['away'] = array('club'=>null, 'team'=>null, 'players'=>array(), 'signed'=>false,
            'goals'=>0, 'scorers'=>array(), 'fines'=>array(), 'notes'=>array());

        if ($card['home_id'] != null) {
            $card['home']['club'] = $card['home_name'];
            $card['home']['team'] = $card['home_team'];
            $card['home']['club_id'] = $card['home_id'];
            $numbers['home'] = Model_Matchcard::numberTable($card['home_id']);
        }
        if ($card['away_id'] != null) {
            $card['away']['club'] = $card['away_name'];
            $card['away']['team'] = $card['away_team'];
            $card['away']['club_id'] = $card['away_id'];
            $numbers['away'] = Model_Matchcard::numberTable($card['away_id']);
        }
        $card['goals'] = array();
        $card['home']['incidents'] = array();
        $card['away']['incidents'] = array();

        $incidents = \DB::query("select id, player, club_id, type, detail, date, resolved
			from incident where matchcard_id = ${card['id']}")->execute();

        $t3 = milliseconds();

        foreach ($incidents as $incident) {
            if ($incident['club_id'] == $card['home_id']) {
                $key = 'home';
            }
            if ($incident['club_id'] == $card['away_id']) {
                $key = 'away';
            }

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
                    if (isset($numbers[$key][$incident['player']])) {
                        $player['number'] = $numbers[$key][$incident['player']];
                    }
                }

                $card[$key]['players'][$playerName] = $player;
            }

            switch ($incident['type']) {
                case 'Played':
                    if ($incident['resolved'] == 1) {
                        $card[$key]['players'][$playerName]['deleted'] = true;
                    }
                    if ($incident['detail']) {
                        $card[$key]['players'][$playerName]['detail'] = $incident['detail'];
                    }
                    break;
                case 'Scored':
                    if ($incident['resolved'] == 1) {
                        break;
                    }
                    $card[$key]['goals'] = $card[$key]['goals'] + $incident['detail'];
                    if (isset($card[$key]['scorers'][$playerName])) {
                        $score = $card[$key]['scorers'][$playerName];
                    } else {
                        $score = 0;
                    }
                    $card[$key]['scorers'][$playerName] = $score + $incident['detail'];
                    break;
                case 'Missing':
                    $card[$key]['fines'][] = array('Missing'=>$incident['detail'], "resolved"=>$incident['resolved']);
                    break;
                case 'Signed':
                    if ($incident['resolved'] == 1) {
                        break;
                    }
                    if (preg_match("/^([0-9]+)?(?:\/([^;]*))?(?:;(.*))?$/i", $incident['detail'], $output_array)) {
                        $card[$key]['signed'] = true;
                        $oppositionScore = $output_array[1];
                        if ($oppositionScore === "") {
                            $oppositionScore = 0;
                        }
                        $card[$key.'-opposition-score'] = $oppositionScore;
                        if (count($output_array) > 2) {
                            $card[$key]['umpire'] = $output_array[2];
                        }
                    }
                    break;
                case 'Yellow Card':
                case 'Red Card':
                    if ($incident['resolved'] == 1) {
                        break;
                    }
                    self::arr_add($card[$key], 'penalties', array(
                        'player'=>$incident['player'],
                        'penalty'=>$incident['type'],
                        'detail'=>$incident['detail']));
                    break;
                case 'Other':
                    $detail = $incident['detail'];
                    $matches = array();
                    if (preg_match('/^"(.*)"$/', $detail, $matches)) {
                        $card[$key]['notes'][] = $matches[1];
                    }
                    break;
                default:
                    $card[$key]['incidents'][] = $incident;
            }
        }

        if (!$card['home']['signed']) {
            if (isset($card['away-opposition-score'])) {
                $card['home']['goals'] = $card['away-opposition-score'];
            }
        }

        if (!$card['away']['signed']) {
            if (isset($card['home-opposition-score'])) {
                $card['away']['goals'] = $card['home-opposition-score'];
            }
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

        $card['description'] = $card['competition'].":".
            $card['home']['club']." ".$card['home']['team']." v ".
            $card['away']['club']." ".$card['away']['team'];

        $t4 = milliseconds();

        Log::debug("Times: ".($t2-$t0)." ".($t3-$t0)." ".($t4-$t0));

        return $card;
    }

    //	private static function cleanName($name) {
    //		$a = strpos($name, ',');
    //
    //		if (!$a) return $name;
    //
    //		return trim(substr($name, $a+1))." ".ucwords(strtolower(substr($name, 0, $a)));
    //	}

    private static function numberTable($clubId)
    {
        $numbers = \DB::query("SELECT player, detail FROM incident i JOIN
			(SELECT max(id) id
			FROM incident
			WHERE type = 'Number' AND club_id = :club_id
			GROUP BY player) n ON n.id = i.id")
            ->bind('club_id', $clubId)
            ->execute();

        $result = array();
        foreach ($numbers as $number) {
            $result[$number['player']] = $number['detail'];
        }

        return $result;
    }

    public static function incompleteMatchcards($delay, $playerCount)
    {
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

    public static function unclosedMatchcards()
    {
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

    public static function fixturesWithoutMatchcards($fixtureIds)
    {
        $missing = array();
        sort($fixtureIds);
        $dbIds = \DB::query("select fixture_id from matchcard where fixture_id is not null order by fixture_id")->execute();

        $currentId = array_shift($fixtureIds);
        foreach ($dbIds as $id) {
            if (!$currentId) {
                break;
            }

            $id = $id['fixture_id'];

            if ($currentId > $id) {
                continue;
            }

            while ($currentId <= $id) {
                if ($currentId < $id) {
                    $missing[] = $currentId;
                }

                $currentId = array_shift($fixtureIds);
                if ($currentId == null) {
                    break;
                }
            }
        }

        $missing = array_merge($missing, $fixtureIds);

        return $missing;
    }

    public static function lateCards($section = null) {
        $sql = "with unsigned_cards as (
            select m.matchcard_id, m.club_id, m.home, i.type is not null signed from (
                select m.id as matchcard_id, true as home, c.id as club_id, date
                    from matchcard m 
                    join team t on t.id = m.home_id
                    join club c on c.id = t.club_id
                    
                union
            
                select m.id as matchcard_id, false as home, c.id as club_id, date
                    from matchcard m 
                    join team t on t.id = m.away_id
                    join club c on c.id = t.club_id
            ) m
            left join incident i on i.matchcard_id = m.matchcard_id and i.type = 'Signed' 
                and i.club_id = m.club_id and i.resolved = 0
            where m.date between '2023-08-01' and CURRENT_TIMESTAMP()
            order by m.matchcard_id
        ),
        late_cards as (
            select matchcard_id, club_id, date, detail, row_number() over (partition by matchcard_id, club_id order by k, date) rownum from (
                select matchcard_id, club_id, -detail k, detail, date from incident 
                    where resolved = 0 and detail < 0 and type = 'Late'
                UNION
                select matchcard_id, club_id, 0, detail, date from incident 
                    where resolved = 0 and detail >= 0 and type = 'Late'
            ) t
        )
        select m.fixture_id, z.matchcard_id, z.club_id, z.home, z.signed, l.date late_date, l.detail late_status from unsigned_cards z 
            join matchcard m on m.id = z.matchcard_id
            left join late_cards l on z.matchcard_id = l.matchcard_id and (l.club_id is null or l.club_id = z.club_id) and l.rownum = 1
        order by m.fixture_id";

        $results = [];
        foreach (DB::query($sql)->execute() as $r) $results[] = $r;

        return $results;
    }
}
