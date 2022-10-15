<?php
class Controller_Admin extends Controller_Hybrid
{
    public function action_index()
    {
        if (!Auth::has_access("super.edit")) {
            throw new HttpNoAccessException;
        }

        $allusers = Model_User::find('all');
        usort($allusers, function ($a, $b) {
            return strcmp($a->getName(), $b->getName());
        });

        $this->template->title = "SuperUser Administration";
        $this->template->content = View::forge('admin/index', array('users'=>$allusers));
    }

    /**
     * Get the tasks configured on the system
     */
    public function get_tasks()
    {
        if (!Auth::has_access("tasks.view")) {
            throw new HttpNoAccessException;
        }

        $tasks = Model_Task::find('all');

        $this->template->title = "Task Processing";
        $this->template->content = View::forge('admin/tasks', array('tasks' => $tasks));
    }

    public function get_testtask()
    {
        echo "Execute test task";

        $t = new \Fuel\Task\TestTask();

        return new Response("", 200);
    }

    public function action_touch()
    {
        if (!Auth::has_access("registration.touch")) {
            throw new HttpNoAccessException;
        }

        $f = Input::param('f');

        if (!$f) {
            Session::set_flash("message", array("message" => "No file specified"));
            Response::redirect("admin");
            return;
        }

        $d = Input::param('d', null);

        if ($d) {
            $d = Date::create_from_string($d, "%y%m%d%H%M%S");
        }

        foreach (Model_Section::find('all') as $section) {
            $root = DATAPATH."/sections/".$section['name'] . "/registration/$f";

            static::touchAll($root, $d);
        }

        Response::redirect("admin");
    }

    private function csvToJson($f) {
        $csv = array_map('str_getcsv', file($f));
        while (!$csv[0][0]) array_shift($csv); # remove blank rows
        print_r($csv[0]);
        for ($i=0;$i<count($csv[0]);$i++) $csv[0][$i] = strtolower($csv[0][$i]);
        array_walk($csv, function (&$a) use ($csv) {
            $a = array_combine($csv[0], $a);
        });
        array_shift($csv); # remove column header
        return json_encode($csv);
    }

    public function post_import()
    {
        $src = \Upload::get_files(0);

        if ($src) {
            switch ($src['extension']) {
              case 'xls':
              case 'xlsx':
                $filename = $src['file'];
                Log::info("Importing $filename");
                $spreadsheet = PhpOffice\PhpSpreadsheet\IOFactory::load($filename);

                $writer = new PhpOffice\PhpSpreadsheet\Writer\Csv($spreadsheet);
                $tmpfname = $filename.".csv"; //tempnam("/tmp", "csv");
                $writer->save($tmpfname);
                $src = self::csvToJson($tmpfname);
                break;
              case 'csv':
                $src = self::csvToJson($src['file']);
                break;
              default:
                $src = file_get_contents($src['file']);
                break;
            }
            $src = json_decode($src);
            self::import($src);
        }

        return new Response("", 200);
    }

    private static function validateEmail($email) {
        if ($email) {
            if (!preg_match("/[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}/i", $email)) {
                throw new Exception("Invalid email address: $email");
            }
        }
    }

    private static function generateRandomString($length = 10) {    # https://stackoverflow.com/questions/4356289/php-random-string-generator
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }

    private function import($src)
    {
        echo "<pre>";
        if ($src) {
            foreach ($src as $srci) {
                $item = (array)$srci;
                if (!isset($item['type'])) {
                    $item['type'] = 'section';
                    if (isset($item['username']) or isset($item['email'])) {
                        $item['type'] = 'user';
                    } elseif (isset($item['format'])) {
                        $item['type'] = 'competition';
                    } elseif (isset($item['code'])) {
                        $item['type'] = 'club';
                    } elseif (!isset($item['name'])) {
                        echo "No type\n";
                        continue;
                    }
                }

                foreach ($item as $key=>&$value) {
                    if ($value === 'NULL') {
                        $value = null;
                    }
                }

                try {
                    switch ($item['type']) {
              case 'section':
                if (!Model_Section::find_by_name($item['name'])) {
                    $s = new Model_Section();
                    $s['name'] = $item['name'];
                    $s->save();
                    echo "Imported Section: ".$item['name']."\n";
                }
                break;
              case 'club':
                if (!Model_Club::find_by_name($item['name'])) {
                    $c = new Model_Club();
                    $c['name'] = $item['name'];
                    $c['code'] = $item['code'];
                    $c->save();
                    echo "Imported Club: ".$item['name']."\n";
                }
                break;
              case 'competition':
                  $s = $this->getSection($item['section']);
                  $x = DB::select()->from("competition")->where('name', $item['name'])->where('section_id', $s['id'])->execute()->current();
                   if (!$x) {
                       $x = new Model_Competition();
                       $x['name'] = $item['name'];
                       $x['code'] = $item['code'];
                       $x['section'] = $s;
                       $x['teamsize'] = $item['teamsize'];
                       $x['teamstars'] = $item['teamstars'];
                       $x['groups'] = $item['groups'];
                       $x['format'] = $item['format'];
                       $x['sequence'] = $item['sequence'] == null ? null : intval($item['sequence']);
                       $x->save();
                       echo "Imported Competition: ".$item['name']."\n";
                   }
                break;
              case 'user':
                {
                    if (!isset($item['section'])) {
                        $sectionParam = \Input::param("section", null);

                        if ($sectionParam !== null) {
                            $item['section'] = $sectionParam;
                        } else {
                            echo "Users must have a section column";
                            return;
                        }
                    }

                    if (!isset($item['group'])) {
                        if ($item['email']) {
                          $item['group'] = 25;
                        }
                    }

                    if (!isset($item['password'])) {
                        $item['password'] = self::generateRandomString();
                    }

                    self::validateEmail($item['email']);

                    switch ($item['group']) {
                        case 25: // secretary
                        case 99: // admin
                            $username = $item['email'];
                            break;
                        case 2: // umpire
                            $username = ucwords($item['username']);
                            break;
                        case 1: // player
                            if ($item['club']) {
                                $username = $item['club']." (".$item['section'].")";
                            }
                            break;
                        case 100: // admin
                            $username = null;
                            break;
                        default:
                            print_r($item);
                            echo "Unknown user type: ".$item['group']."\n";
                            $username = null;
                    }
                    if (!$username) {
                        continue 2;
                    }
                    $u0 = Model_User::find_by_username($username);
                    if ($u0) {
                        if (isset($u0->section) && $u0->section !== null) {
                            if (!isset($item['section'])) {   // new user has no section, overrides existing user
                                $u0->section = null;
                                $u0->save();
                            } elseif ($u0->section['name'] !== $item['section']) {  // new user is not same as existing user
                                if ($u0['group'] == 25 || $u0['group'] == 99) {   // user is a secretary/admin - widen their scope
                                    $u0->section = null;
                                    $u0->save();
                                }
                            }
                        }

                        continue 2;
                    }
                    echo "------------------ Importing ".$item['type']."\n".print_r($item, true)."\n";

                    echo "New User: $username\n";
                    $s = isset($item['section']) ? $this->getSection($item['section']) : null;
                    if ($item['group'] == 2) {
                        $s = null;
                    }  // umpires

                    $c = isset($item['club']) ? Model_Club::find_by_name($item['club']) : null;
                    if ($item['group'] == 1 || $item['group'] == 25) {
                        if (!$c) {
                            throw new Exception("User $username no such club: ".$item['club']);
                        }
                    }

                    $u = new Model_User();
                    $u['username'] = $username;
                    $u['password'] = $item['password'];
                    $u['club'] = $c;
                    $u['section'] = $s;
                    $u['group'] = $item['group'];
                    $u['email'] = $item['email'];
                    $u->save();
                    echo "Successfully imported\n";
                }
                break;

              default:
                echo $item['type']."\n";
            }
                } catch (Exception $e) {
                    Log::error("Failed to import item: ".$e->getMessage());
                    echo "Error importing: ".print_r($item, true)."\n".print_r($e, true)."\n";
                }
            }
        }
    }

    private function getSection($section)
    {
        $s = Model_Section::find_by_name($section);
        if (!$s) {
            $s = new Model_Section();
            $s['name'] = $section;
            $s->save();
        }
        return $s;
    }

    private static function touchAll($path, $date)
    {
        if (!$path) {
            return;
        }

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

    private static function cleanAll($path, $date)
    {
        $ct = 0;

        if (!$path) {
            return $ct;
        }

        if (is_dir($path)) {
            $deleteDate = $date->get_timestamp();
            $files = glob($path . "/*.csv");
            if ($files) {
                rsort($files);
                $lastDate = filemtime($files[0]);
                if ($deleteDate > $lastDate) {
                    $deleteDate = $lastDate;
                }
            }

            $files = glob($path . "/*");
            if ($files) {
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
            }
            return $ct;
        }
    }

    public function action_clean()
    {
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
    public function action_archive()
    {
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
    public function action_log()
    {
        if (!Auth::has_access("data.log")) {
            throw new HttpNoAccessException;
        }

        $date = \Input::param('d', Date::forge()->format("%Y%m%d"));
        $page = \Input::param('p', 1);
        $site = \Input::get('site', Session::get('site', 'none'));

        if (isset($_GET['mark'])) {
            Log::info("==== Log Marker ====");
        }

        $logPath = DATAPATH."logs/";

        $filename = $logPath . substr($date, 0, 4) . "/" . substr($date, 4, 2) . "/" . substr($date, 6, 2) . ".php";

        if (!file_exists($filename)) {
            return new Response("Log file does not exist: $filename", 404);
        }

        $fp = fopen($filename, 'r');

        fseek($fp, -512000 * $page, SEEK_END);
        $src = fread($fp, 512000);
        //$src = fread($fp);

        echo "<head><title>Log</title><style>code { white-space: nowrap; }</style></head>";
        $previous = 'Admin/Log?d=' . Date::forge(strtotime("-1 day", strtotime($date)))->format("%Y%m%d");
        if ($site) {
            $previous .= "&site=$site";
        }
        echo "<a href='" . Uri::create($previous) . "'>Previous</a><br>";
        echo "<code style='display:block;clear:both;margin:10px 0;'>$filename</code>";

        foreach (array_reverse(explode("\n", $src)) as $line) {
            if (\Input::param('raw', null) !== null) {
                echo "<pre>$line</pre>";
                continue;
            }

            $match = array();
            if (!preg_match("/(.*) - (.*) --> (.*)/", $line, $match)) {
                continue;
            }

            switch ($match[1]) {
                case "ERROR":
                    echo "<code style='color:red'>" . $match[2] . " <strong>" . $match[1] . "</strong> " . $match[3] . "</code><br>".print_r($line, true);
                    break;
                case "WARNING":
                    echo "<code style='color:orange'>" . $match[2] . " <strong>" . $match[1] . "</strong> " . $match[3] . "</code><br>";
                    break;
                case "DEBUG":
                    echo "<code>" . $match[2] . " <strong>" . $match[1] . "</strong> <i style='color:#666'>" . $match[3] . "</i></code><br>";
                    break;
                default:
                    $match2 = array();
                    if (preg_match("/==== Log Marker ====/", $match[3], $match2)) {
                        echo "<hr>";
                        break;
                    }
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
    public function action_info()
    {
        phpinfo();

        exit(0);
    }

    // --------------------------------------------------------------------------
    public function get_clublist()
    {
        $clubs = array();

        foreach (Model_Club::find('all') as $club) {
            $clubs[] = $club['name'];
        }

        return $clubs;
    }

    // --------------------------------------------------------------------------
    public function get_configfile()
    {
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
                if ($competition['teamstars']) {
                    $row2 .= "+${competition['teamstars']}";
                }
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
                if ($user['username'] == $club['name']) {
                    $secretary = $user['email'];
                }
            }

            $body .= $secretary;

            foreach ($entries as $comp => $val) {
                $entries[$comp] = "";
            }

            foreach ($club['team'] as $team) {
                foreach ($team['competition'] as $comp) {
                    if ($entries[$comp['name']] != "") {
                        $entries[$comp['name']] .= ",";
                    }
                    $entries[$comp['name']] .= $team['team'];
                }
            }
            foreach ($entries as $comp => $team) {
                if ($team != "") {
                    $body .= ",\"" . $team . "\"";
                } else {
                    $body .= ",";
                }
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

    private function get_refresh()
    {
        foreach (Model_Section::find('all') as $section) {
            self::refreshSection($section);
        }

        Log::debug("Refresh complete");

        $this->template->title = 'Refresh';
        $this->template->content = "";
    }


    private function refreshSection($section)
    {
        //Response::redirect("admin/competitions");

        $fixtures = Model_Fixture::getAll();
        $competitionTeams = array();
        $clubTeams = array();
        $teamLookup = array();
        $errors = 0;

        Log::info("Rebuilding competitions for ${section['name']}: ".count($fixtures)." fixture(s)");
        $unknowns = array();

        foreach ($fixtures as $fixture) {
            if ($fixture['section'] !== $section['name']) {
                continue;
            }

            if ($fixture['hidden']) {
                continue;
            }

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

        if ($unknowns) Log::warning("Unknown teams: ".join(',', array_keys($unknowns)));

        Log::debug("Fixtures loaded and parsed: xt=" . count($competitionTeams) . " ct=" . count($clubTeams) . " tl=" . count($teamLookup));

        try {
            DB::start_transaction();
            DB::query("DELETE tc FROM team__competition tc
              JOIN competition c ON c.id = tc.competition_id
              JOIN section s on s.id = c.section_id
            WHERE s.name = '${section['name']}'")->execute();  // Delete all entrys

            foreach ($teamLookup as $team => $detail) {
                $t = Model_Team::find_by_name($team, $section);
                if (!$t) {
                    Log::info("Creating team: $team ".print_r($detail, true));
                    // if team does not exist, create it
                    $clubName = $detail['club'];
                    $club = Model_Club::find_by_name($clubName);
                    if (!$club) {
                        Log::warning("No club $clubName");
                        continue;
                    }
                    $t = new Model_Team();
                    $t->club = $club;
                    $t->section = $section;
                    $t->name = $detail['team'];
                    $t->save();
                }
            }

            Log::debug("Teams updated");

            foreach ($competitionTeams as $competition => $teams) {
                $comp = Model_Competition::query()
                          ->where('name', '=', $competition)
                          ->where('section_id', '=', $section['id'])
                          ->get_one();
                if (!$comp) {
                    continue;
                }

                foreach ($teams as $team) {
                    $findTeam = Model_Team::find_by_name($team, $section);

                    if ($findTeam) {
                        $comp->team[] = $findTeam;
                    }
                }
                $comp->save();
            }

            Log::debug("Competitions updated");

            foreach (Model_Matchcard::query()
                    ->where('home_id', '=', null)
                    ->or_where('away_id', '=', null)->get() as $card) {
                foreach ($fixtures as $fixture) {
                    if ($fixture['fixtureID'] != $card['fixture_id']) {
                        continue;
                    }

                    if (!$card['home_id']) {
                        $t = Model_Team::find_by_name($fixture['home'], $section);
                        if ($t) $card['home_id'] = $t['id'];
                        else Log::warning("Unknown home team: ".$fixture['home']."/$section");
                    }
                    if (!$card['away_id']) {
                        $t = Model_Team::find_by_name($fixture['away'], $section);
                        if ($t) $card['away_id'] = $t['id'];
                        else Log::warning("Unknown away team: ".$fixture['away']."/$section");
                    }
                    $card->save();
                }
            }

            DB::commit_transaction();
        } catch (Exception $e) {
            DB::rollback_transaction();

            throw $e;
        }
    }

    private static function treeset(&$t, $k, $v)
    {
        if (!isset($t[$k])) {
            $t[$k] = array();
        }

        if (!in_array($v, $t[$k])) {
            $t[$k][] = $v;
        }
    }

    // ----- Clubs --------------------------------------------------------------
    public function get_clubs()
    {
        $data['clubs'] = array();

        foreach (Model_Club::find(
            'all',
            array('order_by' => array('name' => 'asc'))
        ) as $club) {
            $data['clubs'][] = $club;
        }

        $this->template->title = "Clubs";
        $this->template->content = View::forge('admin/clubs', $data);
    }

    public function post_club()
    {
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

    public function delete_club()
    {
        $club = Model_Club::find_by_code(Input::delete('code'));
        $club->delete();

        return new Response("Club deleted", 201);
    }

    // ----- Competitions -------------------------------------------------------
    public function get_competitions()
    {
        if (!Auth::has_access("competition.view")) {
            throw new HttpNoAccessException;
        }

        $data['sections'] = Model_Section::find('all');
        $data['competitions'] = Model_Competition::find('all');

        if (Input::param("rebuild", false) === 'true') {
            $this->get_refresh();
        }

        $this->template->title = "Competitions";
        $this->template->content = View::forge('admin/competitions', $data);
    }

    public function post_competition()
    {
        $id = Input::post('id', null);

        if (!$id) {
            Log::debug("New competition: " . Input::post('competitionname'));
            $competition = new Model_Competition();
            $competition->name = trim(Input::post('competitionname'));
            $competition->section = Model_Section::find_by_id(Input::post('section'));
        } else {
            Log::debug("Updating competition: $id");
            $competition = Model_Competition::find($id);
        }

        $competition->code = trim(Input::post('competitioncode'));
        $competition->format = Input::post('option_type');
        $competition->teamsize = Input::post('competition-teamsize', '');
        if ($competition->teamsize == '') {
            $competition->teamsize = null;
        }
        $competition->teamstars = Input::post('competition-teamstars', '');
        if ($competition->teamstars == '') {
            $competition->teamstars = null;
        }
        $competition->groups = Input::post('age-group');
        $competition->sequence = 0;
        $competition->save();

        Response::redirect('admin/competitions');
    }

    public function delete_competition()
    {
        $competition = Model_Competition::find_by_code(Input::delete('code'));
        $competition->delete();

        return new Response("Competition deleted", 201);
    }

    public function action_teams()
    {
        $this->template->title = "Teams";

        $q = Model_Team::query();

        $club = Input::param('club', null);

        if ($club) {
            $q = $q->related('club')->where('club.code', $club);
        }

        $data['teams'] = $q->get();

        $this->template->content = View::forge('admin/teams', $data);
    }

    public function get_config()
    {
        if (!Auth::has_access("configuration.view")) {
            throw new HttpNoAccessException;
        }

        $sections = Model_Section::find('all');
        $section = \Input::param('section', null);

        $data = array('sections' => $sections, 'section'=>$section);

        if ($section) {
            Config::load(DATAPATH."/sections/$section/config.json", $section);

            list($comps, $teams) = self::parsing($section);
            $tasks = array();

            $data = array_merge($data, array(
                "config" => Config::get("hockey"),
                "title" => Config::get("$section.title"),
                "salt" => Config::get("$section.salt"),
                "fine" => Config::get("$section.fine", 25),
                "admin_email" => Config::get("$section.admin.email"),
                "cc_email" => Config::get("$section.cc.email"),
                "strict_comps" => Config::get("$section.strict_comps"),
                "automation_email" => Config::get("$section.automation.email"),
                "automation_password" => Config::get("$section.automation.password"),
                "automation_allowrequest" => Config::get("$section.automation.allowrequest"),
                "allowassignment" => Config::get("$section.allowassignment"),
                "allowplaceholders" => Config::get("$section.registration.placeholders", true),
                "mandatory_hi" => Config::get("$section.registration.mandatoryhi", "noselect"),
                "fixescompetition" => join("\r\n", Config::get("$section.pattern.competition", array())),
                "fixesteam" => join("\r\n", Config::get("$section.pattern.team", array())),
                "fixtures" => join("\r\n", Config::get("$section.fixtures", array())),
                "resultsubmit" => Config::get("$section.result.submit", 'no'),
                //"seasonstart" => Config::get("$section.date.start"),
                "regrestdate" => Config::get("$section.date.restrict"),
                "block_errors" => Config::get("$section.registration.blockerrors"),
                "tasks" => $tasks,
                "competitions" => $comps,
                "teams" => $teams));
        }

        $this->template->title = "Configuration";
        $this->template->content = View::forge('admin/config', $data);
    }

    private function parsing($section)
    {
        $teams = array();
        $competitions = array();

        foreach (Model_Fixture::getAll() as $fixture) {
            if ($fixture['section'] !== $section) {
                continue;
            }

            $competitions[$fixture['competition']] = "xx";

            if (isset($fixture['x'])) {
                $teams[$fixture['x']['raw']] = "xx";
            }
            if (isset($fixture['y'])) {
                $teams[$fixture['y']['raw']] = "xx";
            }
        }

        // Process Competitions
        $dbComps = array_filter(Model_Competition::find('all'), function($a) use ($section) { return $a->section['name'] == $section; });
        $dbComps = array_column($dbComps, 'name');

        foreach ($competitions as $competition => $x) {
            $comp = Model_Competition::parse($section, $competition);
            if ($comp === null) {
                Log::warning("Cannot find competition: $competition");
            }
            $competitions[$competition] = array('valid' => in_array($comp, $dbComps), 'name' => $comp);
        }

        ksort($competitions);

        // Process Clubs
        $dbClubs = array_column(Model_Club::find('all'), 'name');

        foreach ($teams as $team => $x) {
            $tm = Model_Team::parse($section, $team);
            if ($tm === null) {
                Log::debug("Cannot find team $section .. $team");
                continue;
            }
            $valid = in_array($tm['club'], $dbClubs);
            $tm['valid'] = $valid;
            if (!$valid) {
                Log::info("Club [".$tm['club']."] is not valid");
            }
            $teams[$team] = $tm;
        }

        $teams = array_filter($teams, function ($a) {
            return $a !== 'xx';
        });

        ksort($teams);

        return array($competitions, $teams);
    }
}
