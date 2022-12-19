<?php

class Controller_Report extends Controller_Template
{
    // --------------------------------------------------------------------------
    public function action_playerreport() 
    {
        if (!\Auth::has_access("report.players")) {
            return new Response("Not permitted: administrator only", 403);
        }

        $section = \Input::get('s');
        $limit = \Input::get('l', 4);

        $sql = "select i.player, c.name club, i.date, x.name competition, 
                    ch.name home, th.name home_team, ca.name away, ta.name away_team 
                from incident i
                    join club c on c.id = i.club_id
                    join matchcard m on m.id = i.matchcard_id
                    join team th on th.id = m.home_id
                    join club ch on ch.id = th.club_id
                    join team ta on ta.id = m.away_id
                    join club ca on ca.id = ta.club_id
                    join competition x on x.id = m.competition_id
                    join section s on s.id = x.section_id
                where i.type = 'Played' and i.date > '2022-08-01' and s.name = :section
                order by i.player, c.name, i.date";

        $rows = DB::query($sql)->bind("section", $section)->execute();
        $current = null;
        $result = array();
        foreach ($rows as $row) {
            $player = $row['player']." -- ".$row['club'];

            if ($player !== $current) {
                $result[] = array('player' => $row['player'],
                'club'=>$row['club'],
                'matches'=>array());
            }
            $result[count($result)-1]['matches'][] = array(
                'date' => $row['date'],
                'competition' => $row['competition'],
                'home' => $row['home']." ".$row['home_team'],
                'away' => $row['away']." ".$row['away_team']
            );
            
            $current = $player;
        }

        usort($result, function($a, $b) { return count($b['matches']) - count($a['matches']); });

        echo "<html><body><style>@import url('/assets/css/reports.css');</style>";

        echo "<h1>Player Report</h1>";
        echo "<h2>Section: $section</h2>";

        foreach ($result as $player) {
            if (count($player['matches']) < $limit) continue;
            echo "<div class='player'>
                <p>{$player['player']}, {$player['club']}</p>
                <span>".count($player['matches'])."</span>";

            echo "<table>";
            foreach ($player['matches'] as $match) {
                echo "<tr><td>{$match['date']}</td><td>{$match['competition']}</td><td>{$match['home']}</td><td>{$match['away']}</td></tr>";
            }
            echo "</table>";

            echo "</div>";

        }

        echo "</body></html>";

        return new Response("", 200);
    }   

    // --------------------------------------------------------------------------
    public function action_stats()
    {
        echo "<style>table{margin-top:10px;}table *{font-family:monospace;text-align:left;padding-right:20px;}</style>";
        self::dump(
            "Average number of players by competition",
            "select x.competition, round(avg(least(num,16)),1) num from ( 
			select count(*) num, matchcard_id, competition from incidents 
			where type = 'Played' and resolved = 0 group by club_name, team, matchcard_id
			) x
			group by competition with rollup"
        );

        self::dump(
            "Average number of players on teams",
            "select x.club_name, x.team, round(avg(least(num,16)),1) num, count(*) matches from ( 
			select count(*) num, club_name, team, matchcard_id from incidents 
			where type = 'Played' and resolved = 0 group by club_name, team, matchcard_id
			) x
			group by club_name, team with rollup"
        );

        self::dump(
            "Average number of players on last team",
            "select x.club_name, round(avg(least(num,16)),1) num from ( 
			select count(*) num, club_name, team, matchcard_id from incidents 
			where type = 'Played' and resolved = 0 group by club_name, team, matchcard_id
			) x join (
			select club_name, max(team) team from incidents group by club_name) y 
				on x.club_name = y.club_name and x.team = y.team 
			group by club_name with rollup"
        );

        self::dump(
            "Players playing for more than one team",
            "select club_name, 
					count(*) as total, 
					count(if (times = 1, player, null)) as c1, 
					count(if (times = 2, player, null)) as c2, 
					count(if (times = 3, player, null)) as c3, 
					count(if (times = 4, player, null)) as c4
				from (
				SELECT player, club_name, count(distinct team) times FROM `incidents` 
					where type = 'Played' and resolved = 0 and competition like 'Division %'  
					group by player, club_name 
					order by times desc) k0 group by club_name with rollup"
        );

        self::dump(
            "Number of goals scored depending on size of team",
            "select players, round(avg(score),1) 'average score' from (
					SELECT count(distinct if (type = 'Played', player, null)) players, 
						sum(if (type = 'Scored', detail, 0)) score 
						FROM `incidents` where resolved = 0 group by matchcard_id, club_name 
				order by players) k0 group by players order by players"
        );

        self::dump("Home advantage", "select round(avg(score),1) avg_adv, round(std(score),1) std_adv from (
					select m.id, COALESCE(kh.score,0) -  COALESCE( ka.score,0) score from
					matchcard m
					left join
					(SELECT matchcard_id, sum(detail) score FROM `incidents` 
						where type = 'Scored' and resolved = 0 and at_home group by matchcard_id, club_name) kh 
						on kh.matchcard_id = m.id
					left join
					(SELECT matchcard_id, sum(detail) score FROM `incidents` 
						where type = 'Scored' and resolved = 0 and not at_home group by matchcard_id, club_name) ka 
						on ka.matchcard_id = m.id
					where ka.matchcard_id is not null or kh.matchcard_id is not null
							) k0");



        return new Response("Report sent", 200);
    }

    private static function dump($title, $sql)
    {
        echo "<h1>$title</h1>\n<code style='color:#abe'>$sql</code>\n";
        $result = DB::query($sql)->execute();

        echo "<table>";
        $first = true;
        foreach ($result->as_array() as $row) {
            if ($first) {
                echo "<tr>";
                foreach (array_keys($row) as $head) {
                    echo "<th>$head</th>";
                }
                echo "</tr>";
                $first = false;
            }

            echo "<tr>";
            foreach (array_values($row) as $value) {
                echo "<td>$value</td>";
            }
            echo "</tr>";
        }
        echo "</table><hr>";
    }

    // --------------------------------------------------------------------------
    public function action_notes()
    {
        if (!\Auth::has_access("registration.status")) {
            return new Response("Not permitted: administrator only", 403);
        }

        $rows = DB::query("select t0.id, t0.date, t0.fixture_id, x.name comp_name, 
			c.name club_name, t.team, home, i.type, i.detail from (
					select date, id, fixture_id, competition_id, home_id team_id, 'home' home from matchcard
					union
					select date, id, fixture_id, competition_id, away_id, 'away' home from matchcard
					) t0
					left join team t on t0.team_id = t.id
					left join incident i on t0.id = i.matchcard_id and t.club_id = i.club_id
						and (i.type = 'Scored' or (i.type = 'Other' and i.detail like '\"%\"'))
					left join club c on t.club_id = c.id
					left join competition x on t0.competition_id = x.id
				ORDER BY t0.date, t0.fixture_id, `t0`.`home`  ASC")->execute();

        $result = array();
        foreach ($rows as $row) {
            $fixtureId = $row['fixture_id'];
            if (!isset($result[$fixtureId])) {
                $result[$fixtureId] = array('id'=>$row['id'],
                    'date'=>$row['date'],
                    'competition'=>$row['comp_name'],
                    'home'=>array('score'=>0, 'notes'=>array()),
                    'away'=>array('score'=>0, 'notes'=>array()));
            }
            $record = &$result[$fixtureId];

            $recordTeam = &$record[$row['home']];
            $recordTeam['name'] = $row['club_name']." ".$row['team'];

            switch ($row['type']) {
                case 'Scored':
                    $recordTeam['score'] += $row['detail'];
                    break;
                case 'Other':
                    $recordTeam['notes'][] = $row['detail'];
                    break;
            }
        }

        $this->template->title = "Simple Match/Notes report";
        $this->template->content = View::forge('report/notes', array(
            'data'=>$result
        ));
    }

    // --------------------------------------------------------------------------
    public function action_analysis()
    {
        $rows = Db::query("select x.name comp, c.name club, m.date, matchcard_id, i.club_id, cdp, sd, (i.club_id = t.club_id) home from (select i.matchcard_id, i.club_id, cdp, COALESCE(sd, 0) sd FROM (SELECT matchcard_id, club_id, count(distinct player) cdp FROM `incident` where type = 'Played' and resolved = 0 group by matchcard_id, club_id) AS `i` left join (select matchcard_id, club_id, sum(detail) sd from incident where type='Scored' group by matchcard_id, club_id) i2 on i.club_id = i2.club_id and i.matchcard_id = i2.matchcard_id) i left join matchcard m on m.id = matchcard_id left join competition x on x.id = m.competition_id left join team t on t.id = m.home_id left join club c on c.id = i.club_id")->execute();

        foreach ($rows as $row) {
            echo "${row['comp']},${row['club']},${row['date']}, ${row['cdp']}, ${row['sd']}, ${row['home']}\n";
        }

        return new Response("Report sent", 200);
    }

    // --------------------------------------------------------------------------
    public function action_clubs()
    {
        echo "<h2>Club Analysis</h2>";
        foreach (Model_Club::getAnalysis() as $c) {
            echo "<table>";
            echo "<tr><th colspan=4>${c['name']}/${c['section']}  -- ${c['teams']} teams, ${c['players']} players</th><tr>";
            foreach ($c['reg'] as $player) {
                echo "<tr><td>${player['lastname']}</td><td>${player['firstname']}</td><td>${player['team']}</td><td>${player['membershipid']}</td></tr>";
            }
            echo "</table>";
        }


        return new Response("Report sent", 200);
    }

    // --------------------------------------------------------------------------
    public function action_cards()
    {
        $cards = Model_Incident::find('all', array(
            'where'=> array(
                array('type','Red Card'),
                'or'=>array('type','Yellow Card'),
            ),
            'order_by'=>array('date'=>'desc'),
        ));

        $club = \Session::get('club', null);
        if ($club) {
            $cards = array_filter($cards, function ($a) use ($club) {
                return ($a['club']['name'] == $club);
            });
        }

        $this->template->title = "Red/Yellow Cards";
        $this->template->content = View::forge('report/cards', array(
            'cards'=>$cards
        ));
    }


    // --------------------------------------------------------------------------
    public function action_regsec()
    {
        if (!\Auth::has_access("registration.status")) {
            return new Response("Not permitted: administrator only", 403);
        }

        $reportDate = time();

        $this->template->title = "Registration Secretary Report";
        $this->template->content = View::forge('report/registration', array(
            'reportDate'=>$reportDate
        ));
    }

    // -------------------------------------------------------
    public function action_grid()
    {
        $competition = Input::get("x");

        self::header("Grid Report - $competition");

        $fixtures = array_filter(Model_Fixture::getAll(), function ($a) use ($competition) {
            return $a['competition'] === $competition;
        });
        $map = array();
        $clubs = array_unique(array_values(array_map(function ($a) {
            return $a['home_club'];
        }, $fixtures)));
        $playct = array_fill_keys($clubs, 0);
        $clubs = array_fill_keys($clubs, 0);
        foreach ($fixtures as $fixture) {
            if ($fixture['home_club'] === 'Blank') {
                continue;
            }
            if ($fixture['away_club'] === 'Blank') {
                continue;
            }

            $map[$fixture['home_club'].":".$fixture['away_club']] = $fixture;

            if ($fixture['played'] === 'no') {
                continue;
            }

            $hpts = 0;
            $apts = 0;
            if ($fixture['home_score'] > $fixture['away_score']) {
                $hpts = 3;
            } elseif ($fixture['home_score'] < $fixture['away_score']) {
                $apts = 3;
            } elseif ($fixture['home_score'] === $fixture['away_score']) {
                $hpts = 1;
                $apts = 1;
            }

            $playct[$fixture['home_club']] = (@$playct[$fixture['home_club']] ?: 0) + 1;
            $playct[$fixture['away_club']] = (@$playct[$fixture['away_club']] ?: 0) + 1;
            $clubs[$fixture['home_club']] = (@$clubs[$fixture['home_club']] ?: 0) + $hpts;
            $clubs[$fixture['away_club']] = (@$clubs[$fixture['away_club']] ?: 0) + $apts;
        }
        foreach ($clubs as $club=>$score) {
            $clubs[$club] = array('c'=>$club, 's'=>$score);
        }
        usort($clubs, function ($a, $b) {
            return $b['s'] - $a['s'];
        });

        echo "<style>
			table { margin-top: 2cm; }
			td { border:1px solid black; text-align:center; max-width:1cm; height:1cm; } 
			#away th div {width:1cm;transform:rotate(-90deg);}
			.home th { text-align: right; }
			.blank { background-color: #bbb; }
			.unplayed { background-color: #bbf; }
			.late { background-color: #fbb; }
			.draw { background-color: #bfb; }
			.away-win {
				background: rgba(248, 80, 50, 1);
				background:linear-gradient(to bottom left,#bfb 50%,#fff 50%);
			}
			.home-win {
				background: rgba(248, 80, 50, 1);
				background:linear-gradient(to bottom left,#fff 50%,#bfb 50%);
			}
			</style>";

        $nowts = Date::time()->get_timestamp();

        echo "<table><tr id='away'><th/><th>Pld</th><th>Pts</th>";
        foreach ($clubs as $club) {
            echo "<th><div>${club['c']}</div></th>";
        }
        echo "<th>HI<br>%</th></tr>";
        foreach ($clubs as $home_club) {
            $ct = $playct[$home_club['c']];
            echo "<tr class='home'><th>${home_club['c']}</th><td style='border:0'>$ct</td><td style='border:0'>${home_club['s']}</td>";
            foreach ($clubs as $away_club) {
                $key = "${home_club['c']}:${away_club['c']}";
                if (!isset($map[$key])) {
                    echo "<td class='blank'/>";
                    continue;
                }

                $fixture = $map[$key];
                if ($fixture['played'] === 'no') {
                    $class = "unplayed";
                    if ($fixture['datetime']->get_timestamp() < $nowts) {
                        $class = "late";
                    }

                    echo "<td class='fixture $class'>".$fixture['datetime']->format('%d<br>%b')."</td>";
                } else {
                    $class = "draw";
                    if ($fixture['home_score'] > $fixture['away_score']) {
                        $class = "home-win";
                    }
                    if ($fixture['home_score'] < $fixture['away_score']) {
                        $class = "away-win";
                    }

                    echo "<td class='fixture $class'>
						<div style='text-align:right'>${fixture['away_score']}</div>
						<div style='text-align:left'>${fixture['home_score']}</div>
						</td>";
                }
            }
            $hipct = 0;
            if ($ct > 0) {
                $hipct = round(18 * $home_club['s'] / $ct);
            }
            echo "<td style='border: 0'>".$hipct."</td>";
            echo "</tr>";
        }
        echo "</table>";

        $comps = array_map(function ($a) {
            return $a['name'];
        }, Model_Competition::find('all'));
        sort($comps);

        echo "<br>";

        foreach ($comps as $name) {
            //if (!preg_match('/Division .*/', $name)) continue;
            echo "<a class='no-print' href='http://cards.leinsterhockey.ie/public/report/Grid?site=".Session::get('site')."&x=$name'>$name</a> ";
        }

        return new Response("", 200);
    }

    // -------------------------------------------------------
    public function action_summary()
    {
        $club = \Input::get('c');
        $club = Model_Club::find_by_name($club);

        if (\Input::get('d')) {
            $dateTo = strtotime(\Input::get('d'));
        } else {
            $dateTo = strtotime(date("Y-m-d")." 00:00");
        }

        $dateFrom = strtotime("-7 days", $dateTo);

        $incidents = Model_Incident::find(
            'all',
            array(
            'where'=> array(
                array('date','<',date('Y-m-d', $dateTo)),
                array('date','>',date('Y-m-d', $dateFrom)),
                array('club_id','=', $club['id'])
                )
            )
        );

        $cards = array();
        foreach (Model_Matchcard::find(
            'all',
            array(
            'where'=> array(
                array('date','<',date('Y-m-d', $dateTo)),
                array('date','>',date('Y-m-d', $dateFrom)),
                ),
            'order_by' => array('date'=>'asc'),
            )
        ) as $card) {
            if ($card['home']['club_id'] == $club['id'] || $card['away']['club_id'] == $club['id']) {
                $cards[] = Model_Matchcard::card($card['id']);
            }
        }

        $fines = array_filter($incidents, function ($a) {
            return $a['type'] == 'Missing';
        });
        $scores = array_filter($incidents, function ($a) {
            return $a['type'] == 'Scored';
        });

        echo \View::forge("report/summary", array('club'=>$club,
            'date'=>array('from'=>date('Y-m-d', $dateFrom),'to'=>date('Y-m-d', $dateTo)),
            'cards'=>$cards,
            'fines'=>$fines,
            'scores'=>$scores));

        return new Response("Report sent", 200);
    }

    public function action_email()
    {
        $club = Input::param("c");

        if (!$club) {
            Log::warning("No club specified");
            foreach (Model_Club::find('all') as $club) {
                enqueue("fuel/public/report/email?site=".Session::get('site')."&c=".urlencode($club['name']));
            }
            return;
        }

        $date = date('d/m/Y');

        $emailAddresses = array();

        foreach (DB::query("select email from user u join club c on u.club_id = c.id 
				where role='secretary' and c.name='$club'")->execute() as $row) {
            $emailAddresses[] = $row['email'];
        }

        //$card = Model_Matchcard::card($cardId);
        $autoEmail = Config::get("section.automation_email");
        $title = Config::get("section.title");
        $email = Email::forge();
        $email->from($autoEmail, "$title (No Reply)");
        $email->to($emailAddresses);
        $body = View::forge("report/weeklyemail", array(
            "club"=>$club,
            "date"=>$date));
        $matches = array();
        if (preg_match('/title>(.*)<\/title/', $body, $matches)) {
            $email->subject($matches[1]);
        }
        $email->html_body($body);
        $email->send();
        Log::info("Receipt email sent to ".implode(',', $emailAddresses)." =".print_r($email, true));
        //print_r($email);

        return new Response($body);
    }

    public function action_index()
    {
        $this->template->title = "Reports";
        $this->template->content = View::forge('report/index');
    }

    public function action_games()
    {
        $dates = Db::query('select distinct date from incident order by date');
    }

    public function action_played()
    {
        $result = Db::query("select i.player, c.name club, count(1) count, coalesce(ta.team, th.team) team from incident i
							join club c on i.club_id = c.id 
							join matchcard m on m.id = i.matchcard_id
							left join team ta on m.away_id = ta.id and ta.club_id = c.id
							left join team th on m.home_id = th.id and th.club_id = c.id
					where i.type = 'Played' and i.date > '2018-06-01'
						and i.resolved = 0
					group by i.player, c.name, coalesce(ta.team, th.team)
					order by i.player, coalesce(ta.team, th.team) desc");

        $summary = array();
        foreach ($result->execute() as $row) {
            echo "<!-- ".print_r($row, true)." -->\n";
            $playerName = $row['player']."/".$row['club'];
            if (!isset($summary[$playerName])) {
                $summary[$playerName] = array('name'=>$row['player'],
                    'club'=>$row['club'],
                    'total'=>0,
                    'lowestTeam'=>$row['team'],
                    'highestTeam'=>$row['team'],
                    'lowestTeamCount'=>$row['count']);
            }
            $summary[$playerName]['total'] = $summary[$playerName]['total'] + $row['count'];
            $summary[$playerName]['highestTeam'] = $row['team'];
        }

        $this->template->title = "Played Games";
        $this->template->content = View::forge('report/played', array('data'=>$summary));
    }

    public function action_card()
    {
        $key = \Input::param('key', null);

        if ($key != null) {
            $cardId = Model_Matchcard::find_by_key($key);
            $cardId = $cardId[0]['id']; // always take the first one
            $card = Model_Matchcard::card($cardId);
            $fixture = Model_Fixture::get($card['fixture_id']);
        } else {
            $cardId = $this->param('id');

            if (substr($cardId, 0, 1) == "n") {
                $card = Model_Matchcard::card(substr($cardId, 1));
                $fixture = Model_Fixture::get($card['fixture_id']);
            } else {
                $card = Model_Matchcard::find_by_fixture($cardId);
                $fixture = Model_Fixture::get($cardId);
            }
        }

        if ($fixture) {
            $card['section'] = $fixture['section'];
        }

        $incidents = array();
        $card2 = array();

        if ($card && $card['id']) {
            $incidents = Model_Matchcard::incidents($card['id']);
            $card2 = Model_Matchcard::card2($card['id']);
        }

        $html = View::forge('report/card', array('card'=>$card, 'fixture'=>$fixture,
                'incidents'=>$incidents, 'card2'=>$card2))->render();
        return new Response($html);
    }

    public function action_scorers()
    {
        $data['scorers'] = Model_Report::scorers();

        $this->template->title = "Scorers";
        $this->template->content = View::forge('report/scorers', $data);
    }

    public function action_diagnostics()
    {
        $this->template->title = "Diagnostics";
        $this->template->content = "<pre>Fuel Base: ".Uri::base(false)."\n"
            .\Model_Task::command(array('command'=>"abc"))."\n"
            ."php version:".phpversion()."\n"
            ."SERVER:".print_r($_SERVER, true)."\n\n"
            ."REQUEST:".print_r($_REQUEST, true)."</pre>";
    }

    private static function strToHex($string)
    {
        $hex = '';
        for ($i=0; $i<strlen($string); $i++) {
            $ord = ord($string[$i]);
            $hexCode = dechex($ord);
            $hex .= substr('0'.$hexCode, -2);
        }
        return strToUpper($hex);
    }

    public function action_parsing()
    {
        $section = \Input::param("s", null);
        if ($section == null) {
            return;
        }
        loadSectionConfig($section);

        //echo "<pre>";

        print_r(Config::get($section));

        $dbComps = array();
        foreach (Model_Competition::find('all') as $comp) {
            if ($comp->section['name'] !== $section) {
                continue;
            }
            $dbComps[] = $comp['name'];
        }
        $dbClubs = array();
        foreach (Model_Club::find('all') as $comp) {
            //echo "${comp['name']} ".self::strToHex($comp['name'])."\n";
            $dbClubs[] = $comp['name'];
        }

        $teams = array();
        $competitions = array();

        foreach (Model_Fixture::getAll() as $fixture) {
            $competitions[$fixture['competition']] = "xx";
            $teams[$fixture['home']] = "xx";
            $teams[$fixture['away']] = "xx";
        }

        foreach ($competitions as $competition=>$x) {
            $comp = Model_Competition::parse($section, $competition);
            //echo "$competition -> $comp\n";
            $competitions[$competition] = array('valid'=>in_array($comp, $dbComps), 'name'=>$comp);
        }

        foreach ($teams as $team=>$x) {
            $tm = Model_Team::parse($section, $team);
            if ($tm == null) {
                $tm['valid'] = false;
            } else {
                $tm['valid'] = in_array($tm['club'], $dbClubs);
            }
            //echo "$team -> ${tm['club']} =${tm['valid']}".self::strToHex($tm['club'])."\n";
            $teams[$team] = $tm;
        }

        ksort($competitions);
        ksort($teams);
        $data = array('competitions'=>$competitions,'teams'=>$teams);

        $this->template->title = "Parsing";
        $this->template->content = View::forge('report/parsing', $data);
    }

    public function action_mismatch()
    {
        $sectionName = \Input::param("s", null);

        Log::info("Mismatch report: $sectionName");

        $t0 = milliseconds();

        $fixtures = array();

        foreach (Model_Fixture::getAll() as $fixture) {
            if ($fixture['status'] !== 'active') {
                continue;
            }

            if ($sectionName) {
                if ($fixture['section'] != $sectionName) {
                    continue;
                }
            }

            $fixtures[] = $fixture;
        }

        $t2 = milliseconds();

        $fixtures = Model_Matchcard::cardsFromFixtures($fixtures);

        $t3 = milliseconds();
        foreach ($fixtures as $fixture) {
            $card = $fixture['card'];

            if (!$card) {
                continue;
            }		// If the fixture has no card, there's no mismatch
            if (!$card['away_id'] || !$card['home_id']) {
                continue;
            }		// Don't card about EHYL etc

            if (($card['home']['goals'] == $fixture['home_score'])
                    && ($card['away']['goals'] == $fixture['away_score'])) {
                continue;
            }

            $card['home_score'] = $fixture['home_score'] || 0;
            $card['home_team'] = $card['home']['club'].' '.$card['home']['team'];
            $card['away_score'] = $fixture['away_score'] || 0;
            $card['away_team'] = $card['away']['club'].' '.$card['away']['team'];

            $outcomeAffected = false;

            if ($card['home_score'] == $card['away_score']) {
                if ($card['home']['goals'] != $card['away']['goals']) {
                    $outcomeAffected = true;
                }
            }

            if ($card['home']['goals'] == $card['away']['goals']) {
                if ($card['home_score'] != $card['away_score']) {
                    $outcomeAffected = true;
                }
            }

            if ((($card['home']['goals'] - $card['away']['goals']) * ($card['home_score'] - $card['away_score'])) < 0) {
                $outcomeAffected = true;
            }

            $card['outcome_affected'] = $outcomeAffected;

            $mismatches[] = $card;
        }

        $t4 = milliseconds();

        Log::debug("Mismatch report timing: ".($t2-$t0)." ".($t3-$t0)." ".($t4-$t0));

        $this->template->title = "Mismatch Results";
        $this->template->content = View::forge('report/mismatch', array('mismatches'=>$mismatches));
    }

    // ----------------------------------------------------
    public function action_latecards()
    {
        //if (!\Auth::has_access('admin.all')) throw new HttpNoAccessException;

        $fines = array();

        try {
            // ---- Unstarted Cards -------------------------
            $fixtureIds = array();
            $nowDate = Date::forge();
            foreach (Model_Fixture::getAll() as $fixture) {
                if ($fixture['datetime'] > $nowDate) {
                    continue;
                }
                $fixtureIds[] = $fixture['fixtureID'];
            }
            Log::info("Verify ".count($fixtureIds)." fixtures");
            $missing = Model_Matchcard::fixturesWithoutMatchcards($fixtureIds);
            $newCards = false;
            foreach ($missing as $missingCard) {
                if (Model_Matchcard::createCard($missingCard)) {
                    $newCards = true;
                }
            }
            if ($newCards) {
                $this->template->title = "Late/Missing Cards Report";
                $this->template->content = "Processing...";
                return;
            }

            // ---- Incomplete Cards ------------------------
            $cards = \Model_Matchcard::incompleteCards(0, 7);

            foreach ($cards as $cardId) {
                try {
                    $card = \Model_Matchcard::card($cardId['id']);
                } catch (Exception $e) {
                    Log::error("Failed to process incomplete card: ${cardId['id']}:".$e->getMessage());
                    continue;
                }

                $fixture = Model_Fixture::get($card['fixture_id']);

                if (!$fixture) {
                    continue;
                }

                if (isset($fixture['comment'])) {
                    if (preg_match("/\bPP\b|\bpostpone|not played/i", $fixture['comment'])) {
                        continue;
                    }
                }

                if (!isset($fixture['datetime']) || !is_object($fixture['datetime'])) {
                    Log::error("Non-object error, cardid=${cardId['id']}");
                    continue;
                }

                // Match time is in the future
                if ($fixture['datetime'] > Date::forge()) {
                    continue;
                }

                // If the time hasn't been updated they've been fined already...
                $time = (int)$fixture['datetime']->format('%H');
                if ($time < 9) {
                    continue;
                }
                // FIXME but if the home team has players and the opposition has none at midnight - fine them
                // FIXME if by midnights, no players are on the card, it's probably postponed

                $fine = $this->fine($card, $card['home'], $fixture['datetime']->get_timestamp());
                if ($fine) {
                    $fines[] = $fine;
                }

                if (!$card['home']['players'] && !$card['away']['players']) {
                    continue;
                }

                $fine = $this->fine($card, $card['away'], $fixture['datetime']->get_timestamp());
                if ($fine) {
                    $fines[] = $fine;
                    $card = Model_Matchcard::find($card['id']);
                    $card->open = 60;
                    $card->save();
                }
            }

            // ---- Unclosed Cards --------------------------
            foreach (\Model_Matchcard::unclosedCards() as $cardId) {
                try {
                    $card = \Model_Matchcard::card($cardId['id']);
                } catch (Exception $e) {
                    Log::error("Failed to process unclosed card: ${cardId['id']}:".$e->getMessage());
                    continue;
                }

                $fixture = Model_Fixture::get($card['fixture_id']);

                if (!$fixture) {
                    continue;
                }

                if (isset($fixture['comment'])) {
                    if (preg_match("/\bPP\b|\bpostpone|not played/i", $fixture['comment'])) {
                        continue;
                    }
                }

                if (!isset($fixture['datetime']) || !is_object($fixture['datetime'])) {
                    echo "Non-object error, cardid=".$cardId['id']."\n";
                    print_r($fixture);
                    continue;
                }

                // Match time is in the future
                if ($fixture['datetime'] > Date::forge()) {
                    continue;
                }

                // If the time hasn't been updated they've been fined already...
                $time = (int)$fixture['datetime']->format('%H');
                if ($time < 9) {
                    continue;
                }

                $fine = $this->fineIncomplete($card, $card['home'], $fixture['datetime']->get_timestamp());
                if ($fine) {
                    $fines[] = $fine;
                }

                $fine = $this->fineIncomplete($card, $card['away'], $fixture['datetime']->get_timestamp());
                if ($fine) {
                    $fines[] = $fine;
                    $card = Model_Matchcard::find($card['id']);
                    $card->open = 60;
                    $card->save();
                }
            }

            $cards = array();		// FIXME where card doesn't exist but fixture is expired
            // Ignore fixtures that appear in incompleteCards

            // Remove where club is fined twice
            $finedAlready = array();

            foreach ($fines as $fine) {
                $key = $fine['matchcard_id']."/".$fine['team'];
                if (isset($finedAlready[$key])) {
                    continue;
                }
                $finedAlready[$key] = $fine;
            }
            $fines = array_values($finedAlready);

            if (\Input::param("execute")) {
                foreach ($fines as $fine) {
                    try {
                        echo "Executing fine: ".print_r($fine, true)."<br>";
                        $fine->save();
                    } catch (Exception $e1) {
                        Log::error("Failed to issue fine: ${fine['matchcard_id']}/${fine['team']} ".$e1->getMessage());
                    }
                }
            }
        } catch (Exception $e) {
            echo "<pre>".$e->getMessage()."\n".$e->getTraceAsString()."</pre>";
        }

        $this->template->title = "Late/Missing Cards Report";
        $this->template->content = View::forge('report/latecards', array('faults'=>$fines));
    }

    private function fineIncomplete($card, $clubcard, $cardTime)
    {
        if (!$clubcard['club']) {
            return false;
        }
        if (count($clubcard['fines']) > 0) {
            return false;
        }
        if (isset($clubcard['umpire'])) {
            return false;
        }
        if (!$clubcard['players']) {
            return false;
        }

        loadSectionConfig($card['section']);

        $value = \Config::get('section.fine', 10);

        $newfine = new Model_Fine();
        $newfine->competition = $card['competition'];
        $newfine->cardtime = $cardTime;
        $newfine->team = "${clubcard['club']} ${clubcard['team']}";
        $newfine->fixture_id = $card['fixture_id'];
        $newfine->matchcard_id = $card['id'];
        $newfine->detail = $value.':Card not submitted';
        $newfine->club_id = $clubcard['club_id'];
        $newfine->type = 'Missing';
        $newfine->message = "Card must be submitted by midnight";
        $newfine->resolved = 0;

        return $newfine;
    }

    private function fine($card, $clubcard, $cardTime)
    {
        if (!$clubcard['club']) {
            return false;
        }
        if (count($clubcard['fines']) > 0) {
            return false;
        }

        // If club has already been fined for this - then skip it
        foreach ($clubcard['fines'] as $fine) {
            if (isset($fine['Missing'])) {
                if (stripos($fine['Missing'], 'Card Incomplete at Match Time')) {
                    return false;
                }
            }
        }

        $onTimePlayerCount = 0;
        foreach ($clubcard['players'] as $player) {
            if ($player['date']->get_timestamp() < $cardTime) {
                $onTimePlayerCount++;
            }
        }

        if ($onTimePlayerCount >= 7) {
            return false;
        }
        $fCardTime = date("Y.m.d G:i", $cardTime);

        loadSectionConfig($card['section']);
        $value = \Config::get('section.fine', 10);

        $newfine = new Model_Fine();
        $newfine->competition = $card['competition'];
        $newfine->cardtime = $cardTime;
        $newfine->team = "${clubcard['club']} ${clubcard['team']}";
        $newfine->fixture_id = $card['fixture_id'];
        $newfine->matchcard_id = $card['id'];
        $newfine->club_id = $clubcard['club_id'];
        $newfine->detail = $value.':Card Incomplete at Match Time';
        $newfine->type = 'Missing';
        $newfine->message = "$onTimePlayerCount players on card";
        $newfine->resolved = 0;

        return $newfine;
    }

    public static function header($title)
    {
        echo "<html><head><title>$title</title></head>\n";
        echo "<body><style>
			* { font: 12pt 'Courier New' }
			@media print { .no-print, .no-print * { display: none !important; } }
			</style>";
    }
}
