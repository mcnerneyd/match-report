<?php

class Controller_Admin extends Controller_Hybrid {

    public function action_index() {

			$allusers = array_column(Model_User::find('all'), 'username');
			sort($allusers);
			
			$this->template->title = "SuperUser Administration";
			$this->template->content = View::forge('admin/index', array('users'=>$allusers));
    }

    /**
     * Get the tasks configured on the system
     */
    public function get_tasks() {
			if (!Auth::has_access("tasks.view"))
					throw new HttpNoAccessException;

			$tasks = Model_Task::find('all');

			$this->template->title = "Task Processing";
			$this->template->content = View::forge('admin/tasks', array('tasks' => $tasks));
    }

    public function get_testtask() {
        echo "Execute test task";

        $t = new \Fuel\Task\TestTask();

        return new Response("", 200);
    }

    public function action_touch() {
        if (!Auth::has_access("registration.touch"))
            throw new HttpNoAccessException;

        $f = Input::param('f');

        if (!$f) {
            Session::set_flash("message", array("message" => "No file specified"));
            Response::redirect("admin");
            return;
        }

        $d = Input::param('d', null);

        if ($d)
            $d = Date::create_from_string($d, "%y%m%d%H%M%S");

        $root = sitepath() . "/registration/$f";

        static::touchAll($root, $d);

        Response::redirect("admin");
    }

    private static function touchAll($path, $date) {

        if (!$path)
            return;

        if (is_dir($path)) {
            $files = glob($path . "/*");

            if ($files) {
                foreach ($files as $sub) {
                    static::touchAll($sub, $date);
                }
            }
            return;
        }

        if (!$date) {
            if (!preg_match("/[0-9]{12}.*/", $path)) {
                return;
            }
            $date = Date::create_from_string(substr(basename($path), 0, 12), "%y%m%d%H%M%S");
        }

        touch($path, $date->get_timestamp());
    }

    private static function cleanAll($path, $date) {
        $ct = 0;

        if (!$path)
            return $ct;

        if (is_dir($path)) {
            $deleteDate = $date->get_timestamp();
            $files = glob($path . "/*.csv");
            if ($files) {
                rsort($files);
                $lastDate = filemtime($files[0]);
                if ($deleteDate > $lastDate)
                    $deleteDate = $lastDate;
            }

            $files = glob($path . "/*");
            if ($files)
                foreach ($files as $sub) {
                    if (is_dir($sub)) {
                        $ct += static::cleanAll($sub, $date);
                    } else {
                        if (filemtime($sub) < $deleteDate) {
                            unlink($sub);
                            $ct++;
                        }
                    }
                }
            return $ct;
        }
    }

    public function action_clean() {
        if (!Auth::has_access("data.clean")) {
            throw new HttpNoAccessException;
        }

        $d = Input::param('d');
        $date = currentSeasonStart();

        if ($d != $date->get_timestamp()) {
            return new Response("", 410);
        }

        $rct = static::cleanAll(sitepath() . "/registration/", $date);

        $ct = 0;
        $sql = "DELETE FROM incident WHERE date < '" . $date->format("%Y-%m-%d") . "'";
        $ct += \DB::query($sql)->execute();

        $sql = "DELETE FROM matchcard WHERE date < '" . $date->format("%Y-%m-%d") . "'";
        $ct += \DB::query($sql)->execute();

        Session::set_flash("message", array("message" => "Data cleansed: $ct record(s) removed, $rct file(s) removed"));

        Response::redirect("admin");
    }

    /**
     * Archives all matchcards and incidents from before the current season
     * start.
     */
    public function action_archive() {
        if (!Auth::has_access("data.archive")) {
            throw new HttpNoAccessException;
        }

        $file = Model_Player::archive();

        File::download($file, 'archive.zip', $delete = true);

        Response::redirect("admin");
    }

    /**
     * Display the log from a specific date.
     *
     * @param d Date for which to get the log. Defaults to today.
     */
    public function action_log() {

        $date = \Input::param('d', Date::forge()->format("%Y%m%d"));
				$page = \Input::param('p', 1);
				$site = \Input::get('site', Session::get('site' , 'none'));
				
				if ($site != 'none') {
					$logPath = DATAPATH."sites/$site/logs/";
				} else {
					$logPath = DATAPATH."logs/";
				}

        $filename = $logPath . substr($date, 0, 4) . "/" . substr($date, 4, 2) . "/" . substr($date, 6, 2) . ".php";
        $fp = fopen($filename, 'r');
        fseek($fp, -512000 * $page, SEEK_END);
        $src = fread($fp, 512000);

        echo "<head><title>Log</title><style>code { white-space: nowrap; }</style></head>";
				$previous = 'Admin/Log?d=' . Date::forge(strtotime("-1 day", strtotime($date)))->format("%Y%m%d");
				if ($site) $previous .= "&site=$site";
        echo "<a href='" . Uri::create($previous) . "'>Previous</a><br>";
				echo "<code style='display:block;clear:both;margin:10px 0;'>$filename</code>";

        foreach (array_reverse(explode("\n", $src)) as $line) {
						if (\Input::param('raw', null) !== null) {
							echo "<pre>$line</pre>";
							continue;
						}

            $match = array();
            if (!preg_match("/(.*) - (.*) --> (.*)/", $line, $match))
                continue;

            switch ($match[1]) {
                case "ERROR":
                    echo "<code style='color:red'>" . $match[2] . " <strong>" . $match[1] . "</strong> " . $match[3] . "</code><br>";
                    break;
                case "WARNING":
                    echo "<code style='color:orange'>" . $match[2] . " <strong>" . $match[1] . "</strong> " . $match[3] . "</code><br>";
                    break;
                case "DEBUG":
                    echo "<code>" . $match[2] . " <strong>" . $match[1] . "</strong> <i style='color:#666'>" . $match[3] . "</i></code><br>";
                    break;
                default:
                    $match2 = array();
                    if (preg_match("/--- Execute: (.*)/", $match[3], $match2)) {
                        $squeak = substr("----- " . $match2[1] . " " . str_repeat("-", 120), 0, 120);
                        echo "<code style='color:#88bb99'>" . $match[2] . " $squeak </code><br><br>";
                        break;
                    }
                    if (preg_match("/Fuel.Core.Request::__construct - Creating a new main Request with URI = \"(.*)\"/", $match[3], $match2)) {
                        echo "<code style='color:green'>${match[2]} ${match2[1]}</code><br><br>";
                        break;
                    }

                    echo "<code>" . $match[2] . " <strong>" . $match[1] . "</strong> " . $match[3] . "</code><br>";
                    if (preg_match("/Request:(.*)/", $match[3], $match2)) {
                        $squeak = str_repeat("&nbsp;", 20).substr("----- Begin Request " . str_repeat("-", 120), 0, 120);
                        echo "<code style='color:green;size:80%'>$squeak</code><br><br>";
										}
                    break;
            }
        }
        fclose($fp);

        return new Response("", 200);
    }

    // --------------------------------------------------------------------------
    public function action_info() {
        phpinfo();

        exit(0);
    }

    // --------------------------------------------------------------------------
    public function get_clublist() {
        $clubs = array();

        foreach (Model_Club::find('all') as $club) {
            $clubs[] = $club['name'];
        }

        return $clubs;
    }

    // --------------------------------------------------------------------------
    public function get_configfile() {
        $body = ",,";
        $row2 = ",,Team Size";
        $row3 = "Club,Code,Email";
        $entries = array();

        foreach (Model_Competition::find('all', array(
            'order_by' => array('sequence' => 'asc'),
            'where' => array(array('sequence', '>=', '0')),
        )) as $competition) {
            $body .= ",\"${competition['name']}\"";
            $row2 .= ",";
            if ($competition['teamsize']) {
                $row2 .= "\"${competition['teamsize']}";
                if ($competition['teamstars'])
                    $row2 .= "+${competition['teamstars']}";
                $row2 .= "\"";
            }
            $row3 .= ",\"${competition['code']}\"";
            $entries[$competition['name']] = "";
        }

        $body .= "\n$row2\n$row3\n";

        foreach (Model_Club::find('all') as $club) {
            $body .= "\"" . $club['name'] . "\"," . $club['code'] . ",";

            $secretary = null;
            foreach ($club->user as $user) {
                if ($user['username'] == $club['name'])
                    $secretary = $user['email'];
            }

            $body .= $secretary;

            foreach ($entries as $comp => $val)
                $entries[$comp] = "";

            foreach ($club['team'] as $team) {
                foreach ($team['competition'] as $comp) {
                    if ($entries[$comp['name']] != "")
                        $entries[$comp['name']] .= ",";
                    $entries[$comp['name']] .= $team['team'];
                }
            }
            foreach ($entries as $comp => $team) {
                if ($team != "")
                    $body .= ",\"" . $team . "\"";
                else
                    $body .= ",";
            }
            $body .= "\n";
        }

        $response = Response::forge($body);

        $response->set_headers(array(
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="Config.csv"',
        ));

        return $response;
    }

    private function get_refresh() {

        $fixtures = Model_Fixture::getAll();
        $competitionTeams = array();
        $clubTeams = array();
        $teamLookup = array();
        $errors = 0;

				echo "Processing ".count($fixtures)." fixture(s)\n";

				$unknowns = array();

        foreach ($fixtures as $fixture) {
						if (!isset($fixture['home_club'])) {
							$unknowns[$fixture['home']] = true;
							continue;
						}
						if (!isset($fixture['away_club'])) {
							$unknowns[$fixture['away']] = true;
							continue;
						}

            try {
                self::treeset($competitionTeams, $fixture['competition'], $fixture['home']);
                self::treeset($competitionTeams, $fixture['competition'], $fixture['away']);

                self::treeset($clubTeams, $fixture['home_club'], $fixture['home']);
                self::treeset($clubTeams, $fixture['away_club'], $fixture['away']);

                $teamLookup[$fixture['home']] = array('club' => $fixture['home_club'], 'team' => $fixture['home_team']);
                $teamLookup[$fixture['away']] = array('club' => $fixture['away_club'], 'team' => $fixture['away_team']);
            } catch (Exception $e) {
                Log::error("Cannot process fixture: " . $e->getMessage() . "\n" . print_r($fixture, true));
                $errors++;
            }
        }

				Log::warning("Unknown teams: ".join(',', array_keys($unknowns)));

        Log::debug("Fixtures loaded and parsed: xt=" . count($competitionTeams) . " ct=" . count($clubTeams) . " tl=" . count($teamLookup));

        try {
            DB::start_transaction();
            DB::delete('entry')->execute();  // Delete all entrys

            foreach ($teamLookup as $team => $detail) {
                $t = Model_Team::find_by_name($team);
                if ($t)
                    continue;

                $clubName = $detail['club'];
                $club = Model_Club::find_by_name($clubName);
                if (!$club) {
                    continue;
                }
                $t = new Model_Team();
                $t->club = $club;
                $t->team = $detail['team'];
                $t->save();
            }

            Log::debug("Teams updated");

            foreach ($competitionTeams as $competition => $teams) {
                $comp = Model_Competition::find_by_name($competition);
                if (!$comp) {
                    continue;
                }

                foreach ($teams as $team) {
                    $findTeam = Model_Team::find_by_name($team);

                    if ($findTeam) {
                        $comp->team[] = $findTeam;
                    }
                }
                $comp->save();
            }

            Log::debug("Competitions updated");

            foreach (Model_Card::query()
                    ->where('home_id', '=', null)
                    ->or_where('away_id', '=', null)->get() as $card) {

                foreach ($fixtures as $fixture) {
                    if ($fixture['fixtureID'] != $card['fixture_id'])
                        continue;

                    if (!$card['home_id']) {
                        $t = Model_Team::find_by_name($fixture['home']);
                        $card['home_id'] = $t['id'];
                    }
                    if (!$card['away_id']) {
                        $t = Model_Team::find_by_name($fixture['away']);
                        $card['away_id'] = $t['id'];
                    }
                    $card->save();
                }
            }

            DB::commit_transaction();
        } catch (Exception $e) {
            DB::rollback_transaction();

            throw $e;
        }

        Log::debug("Refresh complete");

        $this->template->title = 'Refresh';
        $this->template->content = "";

        //Response::redirect("admin/competitions");
    }

    private static function treeset(&$t, $k, $v) {
        if (!isset($t[$k])) {
            $t[$k] = array();
        }

        if (!in_array($v, $t[$k])) {
            $t[$k][] = $v;
        }
    }

    // ----- Clubs --------------------------------------------------------------
    public function get_clubs() {
        $data['clubs'] = array();

        foreach (Model_Club::find('all',
                array('order_by' => array('name' => 'asc'))) as $club) {
            $data['clubs'][] = $club;
        }

        $this->template->title = "Clubs";
        $this->template->content = View::forge('admin/clubs', $data);
    }

    public function post_club() {
        $id = Input::post('id', -1);

        if (!$id || $id == -1) {
            $club = new Model_Club();
        } else {
            $club = Model_Club::find($id);
        }

				Log::debug("$id Clubname:".\Input::post('clubname'));

        $club->name = Input::post('clubname');
        $club->code = Input::post('clubcode');
        $club->save();

        Response::redirect('admin/clubs');
    }

    public function delete_club() {
        $club = Model_Club::find_by_code(Input::delete('code'));
        $club->delete();

        return new Response("Club deleted", 201);
    }

    // ----- Competitions -------------------------------------------------------
    public function get_competitions() {
        if (!Auth::has_access("competition.view")) {
            throw new HttpNoAccessException;
        }

        $data['competitions'] = Model_Competition::find('all');

        if (Input::param("rebuild", false) === 'true') {
            $this->get_refresh();
        }

        $this->template->title = "Competitions";
        $this->template->content = View::forge('admin/competitions', $data);
    }

    public function post_competition() {
        $id = Input::post('id', null);

        if (!$id) {
            Log::debug("New competition: " . Input::post('competitionname'));
            $competition = new Model_Competition();
            $competition->name = Input::post('competitionname');
        } else {
            Log::debug("Updating competition: $id");
            $competition = Model_Competition::find($id);
        }

        $competition->code = Input::post('competitioncode');
        $competition->format = Input::post('option_type');
        $competition->teamsize = Input::post('competition-teamsize', '');
        if ($competition->teamsize == '')
            $competition->teamsize = null;
        $competition->teamstars = Input::post('competition-teamstars', '');
        if ($competition->teamstars == '')
            $competition->teamstars = null;
        $competition->groups = Input::post('age-group');
        $competition->sequence = 0;
        $competition->save();

        Response::redirect('admin/competitions');
    }

    public function delete_competition() {
        $competition = Model_Competition::find_by_code(Input::delete('code'));
        $competition->delete();

        return new Response("Competition deleted", 201);
    }

    public function action_teams() {
        $this->template->title = "Teams";

        $q = Model_Team::query();

        $club = Input::param('club', null);

        if ($club) {
            $q = $q->related('club')->where('club.code', $club);
        }

        $data['teams'] = $q->get();

        $this->template->content = View::forge('admin/teams', $data);
    }

    public function get_config() {
        $tasks = Model_Task::find('all');

        list($comps, $teams) = self::parsing();

        $this->template->title = "Configuration";
        $this->template->content = View::forge('admin/config', array(
            "config" => Config::get("hockey"),
            "title" => Config::get("section.title"),
            "salt" => Config::get("section.salt"),
            "fine" => Config::get("section.fine", 25),
            "elevation_password" => Config::get("section.elevation.password"),
            "admin_email" => Config::get("section.admin.email"),
            "cc_email" => Config::get("section.cc.email"),
            "strict_comps" => Config::get("section.strict_comps"),
            "automation_email" => Config::get("section.automation.email"),
            "automation_password" => Config::get("section.automation.password"),
            "automation_allowrequest" => Config::get("section.automation.allowrequest"),
            "allowassignment" => Config::get("section.allowassignment"),
            "allowplaceholders" => Config::get("section.registration.placeholders", true),
            "mandatory_hi" => Config::get("section.registration.mandatoryhi", "noselect"),
            "fixescompetition" => join("\r\n", Config::get("section.pattern.competition", array())),
            "fixesteam" => join("\r\n", Config::get("section.pattern.team", array())),
            "fixtures" => join("\r\n", Config::get("section.fixtures", array())),
            "resultsubmit" => Config::get("section.result.submit", 'no'),
            //"seasonstart" => Config::get("section.date.start"),
            "regrestdate" => Config::get("section.date.restrict"),
            "block_errors" => Config::get("section.registration.blockerrors"),
            "tasks" => $tasks,
            "competitions" => $comps,
            "teams" => $teams,
        ));
    }

    private function parsing() {
        $teams = array();
        $competitions = array();

        foreach (Model_Fixture::getAll() as $fixture) {
					$competitions[$fixture['competition']] = "xx";

					if (isset($fixture['x'])) $teams[$fixture['x']['raw']] = "xx";
					if (isset($fixture['y'])) $teams[$fixture['y']['raw']] = "xx";
				}

				// Process Competitions
        $dbComps = array_column(Model_Competition::find('all'), 'name');

        foreach ($competitions as $competition => $x) {
            $comp = Model_Competition::parse($competition);
            $competitions[$competition] = array('valid' => in_array($comp, $dbComps), 'name' => $comp);
        }

        ksort($competitions);

				// Process Clubs
				$dbClubs = array_column(Model_Club::find('all'), 'name');

        foreach ($teams as $team => $x) {
            $tm = Model_Club::parse($team);
            $tm['valid'] = in_array($tm['club'], $dbClubs);
            $teams[$team] = $tm;
        }

        ksort($teams);

        return array($competitions, $teams);
    }
}
