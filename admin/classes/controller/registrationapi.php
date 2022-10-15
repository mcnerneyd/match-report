<?php
class Controller_RegistrationApi extends Controller_Rest
{
    // --------------------------------------------------------------------------
    public function options_list() {
        return new Response("OK", 200);
    }
    
    public function get_list()
    {
        $clubId = \Auth::get('club_id');
        $club = Model_Club::find_by_id($clubId);
        if ($club === null) {
            Log::error("Unable to find club for id: $clubId");
            return new Response("User not authorized: no club", 401);
        }
        $club = $club['name'];
        $dateS = \Input::param('d', Date::forge()->format("%Y%m%d"));
        $team = \Input::param('t', 1);
        $section = \Input::param('s', 1);
        $groups = \Input::param('g', null);

        $s = Model_Section::find_by_name($section);

        $players = self::getPlayers($section, $club, $dateS, $team, $groups);
        $lastGameDate = Model_Team::lastGame("$club $team", $s);
        if ($lastGameDate !== null) {
            $lastGameDate = substr($lastGameDate['date'], 0, 10)." 00:00:00";
        }
        Log::debug("LastGameDate:" . $lastGameDate . " ct=".count($players));

        $players = array_values($players);
        usort($players, function ($a, $b) {
            return strcmp($a['name'], $b['name']);
        });

        foreach ($players as &$player) {
            foreach ($player['history'] as &$history) {
                if ($lastGameDate && $history['date'] >= $lastGameDate) {
                    $history['last'] = 'yes';
                }
                break;
            }
        }

        Log::debug(count($players)." player(s) valid for team $team/$groups ($club/$clubId) date=$dateS");

        return $this->response($players);
    }

    public static function getPlayers($section, $club, $dateS, $team, $groups)
    {
        if ($groups) {
            $groups = explode(",", strtolower($groups));
            Log::debug("Groups is active: ".join(",", $groups));
        }

        $date = Date::create_from_string($dateS, '%Y%m%d');
        $date = $date->get_timestamp();
        $initialDate = strtotime("first thursday of " . date("M YY", $date));
        if ($initialDate > $date) {
            $initialDate = strtotime("-1 month", $date);
            $initialDate = strtotime("first thursday of " . date("M YY", $initialDate));
        }
        $startDate = strtotime("+1 day", $initialDate);

        $players = Model_Registration::find_between_dates($section, $club, $startDate, $date);

        Log::debug("Players between dates: ".count($players). " team=$team groups=[".implode(",", $groups ?: array())."]");

        $players = array_filter($players, function ($v) use ($team, $groups) {
            if ($groups) {
                Log::debug(strtolower($v['team']));
                if (!in_array(strtolower($v['team']), $groups)) {
                    return false;
                }
            } else {
                if ($v['team'] < $team) {
                    return false;
                }
            }

            return true;
        });

        return $players;
    }

    // --------------------------------------------------------------------------
    public function put_number()
    {
        $clubName = Input::param("c");
        $player = Input::param("p");
        $number = Input::param("n");

        $club = Model_Club::find_by_name($clubName);

        $incident = new Model_Incident();
        $incident->date = Date::time();
        $incident->player = $player;
        $incident->detail = $number;
        $incident->type = 'Number';
        $incident->club = $club;
        $incident->resolved = 0;
        $incident->save();

        Log::debug("Set shirt number for $player to $number");

        return new Response("Player number set to $number", 200);
    }

    // --------------------------------------------------------------------------
    public function delete_index()
    {
        if (!\Auth::has_access('registration.delete')) {
            return new Response("Not permitted to register: $access", 403);
        }

        $section = Input::param("s");
        $club = Input::param("c");
        $file = Input::param("f");

        Model_Registration::delete($section, $club, $file);

        return new Response("Registration file: $file deleted from club $club", 202);
    }

    // --------------------------------------------------------------------------
    public function post_rename()
    {
        $clubname = Input::param("c");
        $old = Input::param("o");
        $new = Input::param("n");

        Log::info("Rename player $old to $new (club=$clubname)");

        $club = Model_Club::find_by_name($clubname);

        if ($club == null) {
            return new Response("Unknown club: $clubname", 404);
        }

        $new = cleanName($new, "LN, Fn");
        $old = cleanName($old, "LN, Fn");

        $newPlayer = "$new/$old";
        
        DB::query("UPDATE incident SET player='$new'
			WHERE club_id = ".$club->id."
				AND player like '%/$new'")->execute();

        DB::query("UPDATE incident SET player='$newPlayer'
			WHERE club_id = ".$club->id."
				AND (player='$old' OR player like '$old/%')")->execute();


        return new Response("Name changed: $old->$new", 200);
    }

    public function post_index()
    {
        // FIXME Check user admin or matches club
        $access = 'registration.impersonate';	// minimum required access

    $section = Input::param("section");
        if ($section) {
            loadSectionConfig($section);
        }

        if (Config::get('section.automation.allowrequest')) {
            $access = 'registration.post';
        }
        
        if (!\Auth::has_access($access)) {
            return new Response("Not permitted to register: $access", 403);
        }

        $club = Input::param("club");
        $file = Input::file("file");
        $type = mime_content_type($file['tmp_name']);

        Log::info("Posting ${file['name']} for club: $club (type=$type)");

        if (preg_match("/.*\.xlsx?/", $file['name']) || !(preg_match("/text\/.*/", $type) || $type == 'application/csv')) {
            $file['tmp_name'] = self::convertXls($file['name'], $file['tmp_name']);
        }

        $filename = Model_Registration::addRegistration($section, $file['tmp_name'], $club);

        $this->validateRegistration($section, $club);

        //return new Response("Registration Uploaded", 201);
        Response::redirect("registration?s=$section");
    }

    // ----- errors -------------------------------------------------------------
    public function get_errors2()
    {
        $club = Input::param('c', null);
        $file = Input::param('f', null);

        if ($club == null) {
            return;
        }

        if ($file == null) {
            $all = Model_Registration::find_all($club);
            $file = array_shift($all);
            $file = $file['name'];
            echo "File: $file\n";
        }

        return $this->validateRegistration($club);
    }


    public function delete_errors()
    {
        $club = Input::param("club", null);
        if ($club == null) {
            return;
        }
        $club = strtolower($club);
        
        Log::info("Flushing $club");

        Model_Registration::clearErrors($club);
        Model_Registration::flush($club);
        $this->validateRegistration($club);
    }

    public function get_errors()
    {
        $club = Input::param("c", null);
        $sectionName = Input::param("s", null);
        if ($club == null or $sectionName == null) {
            return array();
        }
        $club = strtolower($club);

        $errors = array();

        foreach ($this->get_duplicates($club) as $name=>$players) {
            foreach ($players as $player) {
                $errors[] = array('class'=>'warn','msg'=>
                    "Player $name is similar to ${player['name']} playing for ${player['club']}");
            }
        }

        $registrations = Model_Registration::find_all($sectionName, $club);
        $lastReg = end($registrations);

        if (isset($lastReg['errors'])) {
            Log::info("Registration has error");
            loadSectionConfig($sectionName);

            $errorStatus = Config::get("section.registration.blockerrors", false) ? "error":"warn";
            foreach ($lastReg['errors'] as $error) {
                $errors[] = array('class'=>$errorStatus, 'msg'=>$error);
            }
        }

        return $errors;
    }

    public function get_duplicates($club)
    {
        Log::info("Request duplicates");
        $errors = array();

        $now = Date::forge()->get_timestamp();

        $playersByName = Model_Registration::find_all_players($now);
        usort($playersByName, function ($a, $b) {
            return strcmp($a['phone'], $b['phone']);
        });

        Log::info("Players:".count($playersByName));

        $lastPlayer = null;
        foreach ($playersByName as $player) {
            if ($lastPlayer == null) {
                $lastPlayer = $player;
                continue;
            }

            if ($player['phone'] ==  $lastPlayer['phone']) {
                $name = $player['phone'];
                if (!isset($errors[$name])) {
                    $errors[$name] = array($lastPlayer);
                }
                $errors[$name][] = $player;
                continue;
            }

            $lastPlayer = $player;
        }

        $clubErrors = array();
        foreach ($errors as $name=>$players) {
            foreach ($players as $player) {
                if ($player['club'] === $club) {
                    // Remove this club from list
                    $key = array_search($player, $players);
                    unset($players[$key]);
                    $clubErrors[$player['name']] = $players;
                    break;
                }
            }
        }

        return $clubErrors;
    }

    private function validateRegistration($sectionName, $club, $test=false)
    {
        $errors = array();

        Log::info("Revalidating registration for $club");

        $date = Date::time();

        foreach (Model_Registration::find_all($sectionName, $club) as $regFile) {
            $date = Date::forge($regFile["timestamp"]);
        }

        Log::info("Revalidating registration for $club to $date");

        $thurs = strtotime("first thursday of " . $date->format("%B %Y"));
        if ($thurs > $date->get_timestamp()) {
            $thurs = Date::forge(strtotime("-1 month", $date->get_timestamp()));
            $thurs = strtotime("first thursday of " . $thurs->format("%B %Y"));
        }
        $thurs = strtotime("+1 day", $thurs);

        $registration = Model_Registration::find_between_dates($sectionName, $club, $thurs, $date->get_timestamp());
        
        $scores = array_map(function ($a) {
            return $a['score'];
        }, $registration);
        sort($scores);

        $start = 0;
        $teamSizes = Model_Club::find_by_name($club)->getTeamSizes($sectionName, false);
        array_pop($teamSizes);
        foreach ($teamSizes as $team=>$size) {
            if ($size == 0) {
                continue;
            }

            $finish = $start + $size;
            $maxScore = $scores[$finish];

            for ($i=$start;$i<$finish;$i++) {
                $player = $registration[$i];
                if ($player['score'] > $maxScore) {
                    if ($player['score'] == 99) {
                        $errors[] = "${player['name']} has no rating. Players for $club ".($team+1)." must have played at least one match";
                    } else {
                        $errors[] = "${player['name']} has a rating of ${player['score']}. The maximum allowed rating for $club ".($team+1)." is $maxScore";
                    }
                }
            }

            $start = $finish;
        }

        if (Config::get("section.allowassignment")) {
            foreach ($registration as $player) {
                $counts = array();
                foreach ($player['history'] as $history) {
                    $team = $history['team'];
                    if (!isset($counts[$team])) {
                        $counts[$team] = 0;
                    }
                    $counts[$team] = $counts[$team] + 1;
                }
                if (!$counts) {
                    continue;
                }
                arsort($counts);
                $max = array_keys($counts);
                $max = $max[0];
                $playerTeam = $player['team'];
                if (isset($counts[$playerTeam])) {
                    if ($counts[$max] == $counts[$playerTeam]) {
                        continue;
                    }
                }

                if ($counts[$max] >= 6) {
                    $errors[] = "${player['name']} has played 6 times or more for a team other than $club $playerTeam";
                }
            }
        }

        if (!$test && $errors) {
            Model_Registration::writeErrors($sectionName, $club, $errors);
        }

        return $errors;
    }

    private function convertXls($name, $tmpfile)
    {
        /*
            $cacheMethod = PHPExcel_CachedObjectStorageFactory::cache_to_phpTemp;
            $cacheSettings = array( 'memoryCacheSize' => '2GB');
            PHPExcel_Settings::setCacheStorageMethod($cacheMethod, $cacheSettings);

            $inputFileType = PHPExcel_IOFactory::identify($tmpfile);
            $reader = PHPExcel_IOFactory::createReader($inputFileType);
            $reader->setReadDataOnly(true);

            $excel = $reader->load($tmpfile);

            $writer = PHPExcel_IOFactory::createWriter($excel, 'CSV');
            $tmpfname = tempnam("../tmp", "xlsx");
            $writer->save($tmpfname);
            */
        $spreadsheet = PhpOffice\PhpSpreadsheet\IOFactory::load($tmpfile);

        $writer = new PhpOffice\PhpSpreadsheet\Writer\Csv($spreadsheet);
        $tmpfname = "/tmp/".$name.".csv"; //tempnam("/tmp", "csv");
        $writer->save($tmpfname);

        return $tmpfname;
    }
}
