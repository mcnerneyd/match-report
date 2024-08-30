<?php

ini_set("auto_detect_line_endings", true);

class Model_Registration
{
    private static $cache;
    private static $codes;
    public static function init()
    {
        if (defined('DATAPATH')) {
            $path = DATAPATH . "/sections/" . Session::get('site') . "/tmp/cache";
            if (!file_exists($path)) {
                mkdir($path, 0777, true);
            }
            static::$cache = Cache::forge("membership", array('file' => array('path' => $path), 'driver' => 'file', 'expiration' => 3600 * 24));
            $codes = file_exists(APPPATH . "/classes/model/clublist.ini") ? parse_ini_file(APPPATH . "/classes/model/clublist.ini") : array();
        }
    }

    public static function addRegistration(Model_Section $section, Model_Club $club, string $file) : string
    {
        $ts = Date::forge()->format("%y%m%d%H%M%S");
        $arcDir = self::getRoot($section, $club);
        $newName = "$arcDir/$ts.csv";

        File::rename($file, $newName);

        return $newName;
    }

    public static function getRoot(Model_Section $section, ?Model_Club $club = null): string
    {
        if ($section == null) {
            throw new Exception("No section");
        }
        $root = DATAPATH . "/sections/".strtolower($section->name)."/registration";

        if ($club) {
            $root .= "/" . strtolower($club->name);
        }

        if (!file_exists($root)) {
            mkdir($root, 0777, true);
        }

        $root = realpath($root);

        if (!$root)
            throw new ErrorException("Unable to resolve path $root does not resolve to a real path");

        return $root;
    }

    public static function getLatestDate(Model_Section $section, Model_Club $club): int
    {
        $root = self::getRoot($section, $club);
        $files = glob("$root/*.csv");
        $maxtime = 0;
        if ($files) {
            foreach ($files as $name) {
                $t = filemtime($name);
                if ($t > $maxtime)
                    $maxtime = $t;
            }
        }

        return $maxtime;
    }

    public static function flush(Model_Section $section, Model_Club $club)
    {
        $root = self::getRoot($section, $club);
        Log::debug("Flushing root: $root");
        $files = glob("$root/*.json");

        if ($files) {
            foreach ($files as $file) {
                Log::debug("Flush $file");
                unlink($file);
            }
        }
    }

    public static function writeErrors(Model_Section $section, Model_Club $club, array $errors)
    {
        $root = self::getRoot($section, $club);
        $files = glob("$root/*.csv");
        $file = end($files);
        file_put_contents($file . ".err", implode("\n", $errors));
    }

    public static function delete(Model_Section $section, Model_Club $club, $filename)
    {
        $file = self::getRoot($section, $club) . "/$filename";

        if (file_exists($file)) {
            Log::info("delete file: $file");

            unlink($file);
        } else {
            Log::warning("Trying to delete non-existant file: $file");
        }
    }

    public static function find_all_players(Model_Section $section, ?int $time = null): array
    {
        if ($time == null) {
            $time = time();
        }

        $result = array();

        Log::info("Full player list requested: " . date('Y-m-d H:i', $time));

        foreach (glob(self::getRoot($section) . "/*") as $club) {
            if (!preg_match("/^[A-Za-z ]*$/", $club)) {
                continue;
            }
            $club = basename($club);
            $clubReg = self::find_before_date($section, $club, $time);
            $result = array_merge($result, $clubReg);
        }

        return $result;
    }

    public static function clearErrors(Model_Section $section, Model_Club $club)
    {
        $root = self::getRoot($section, $club);
        if (is_dir($root)) {
            $files = glob("$root/*.csv.err");
            if ($files) {
                foreach ($files as $name) {
                    if (!is_file($name)) {
                        continue;
                    }
                    unlink($name);
                    Log::debug("Deleted errors file: $name");
                }
            }
        }

        try {
            $ids = self::$cache->get();
        } catch (\CacheNotFoundException $e) {
            $ids = array();
        }

        foreach ($ids as $membershipId => $detail) {
            if (strpos($detail, "$club:") === 0) {
                unset($ids[$membershipId]);
            }
        }

        self::$cache->set($ids, 30 * 24 * 3600);	// 30 days
    }

    public static function find_all(Model_Section $section, Model_Club $club): array
    {
        $result = array();
        $root = self::getRoot($section, $club);
        $seasonStart = currentSeasonStart()->get_timestamp();
        Log::debug("loading $root (" . strftime('%F', $seasonStart) . "/$seasonStart)");

        if (is_dir($root)) {
            $files = glob("$root/*.csv");
            if ($files) {
                foreach ($files as $name) {
                    if (!is_file($name)) {
                        continue;
                    }
                    Log::debug("Checking file $name");
                    $ts = Date::create_from_string(basename($name), '%y%m%d%H%M%S.csv');
                    $finfo = finfo_open(FILEINFO_MIME);
                    $ftype = "";
                    if ($finfo) {
                        $ftype = finfo_file($finfo, $name);
                        finfo_close($finfo);
                    }
                    $fileData = array(
                        "club" => strtolower($club->name),
                        "name" => basename($name),
                        "timestamp" => $ts->get_timestamp(),
                        "type" => $ftype,
                        "cksum" => md5_file($name)
                    );
                    if (file_exists($name . ".err")) {
                        $fileData['errors'] = file($name . ".err");
                    }

                    $result[] = $fileData;
                }
            }
        }

        Log::debug("Files " . count($result));

        return $result;
    }

    public static function buildRegistration(array $current, array $initial = null, array $teamSizes = null, array $history = null, bool $allowPlaceholders = false): array
    {
        $currentNames = array();
        $currentLookup = array();

        Log::debug("Current players: " . count($current) . " teamSizes=" . join($teamSizes));

        foreach ($current as $player) {
            $currentNames[] = $player['name'];
            $currentLookup[$player['name']] = $player;
        }

        $order = 0;
        $result = array();
        if ($initial) {
            foreach ($initial as $player) {
                if (($key = array_search($player['name'], $currentNames)) !== false) {
                    if (isset($currentLookup[$player['name']]['membershipid'])) {
                        $player['membershipid'] = $currentLookup[$player['name']]['membershipid'];
                    }
                    unset($currentNames[$key]);
                } else {
                    $player['status'] = "deleted";
                }

                $player['order'] = $order++;
                $result[] = $player;
            }
        }

        foreach ($currentNames as $name) {
            $player = $currentLookup[$name];
            $player['status'] = "added";
            $player['order'] = $order++;
            $result[] = $player;
        }

        // generate player history
        foreach ($result as &$player) {
            $player['score'] = 99;
            if (!isset($history[$player['name']])) {
                $player['history'] = array();
                $placeholders[] = $player;
                continue;
            }
            $player['history'] = $history[$player['name']];
            $teams = array_map(function ($a) {
                return $a['team'];
            }, $player['history']);
            if ($teams) {
                $first = min($teams);
                $firstCount = array_count_values($teams);
                $firstCount = $firstCount[$first];
                $player['score'] = round($first + (1 - ($firstCount / count($teams))), 2);
            }
        }

        if (!$allowPlaceholders) {
            // move placeholders to end of list
            $placeholders = array_filter($result, function ($a) {
                return count($a['history']) == 0;
            });
            $result = array_filter($result, function ($a) {
                return count($a['history']) > 0;
            });
            $result = array_merge($result, $placeholders);
        }

        Log::debug("Players for assignment: " . count($result));

        // assign players to teams
        $lastTeam = 1;
        $teamsAllocation = array();
        if ($teamSizes) {
            for ($i = 0; $i < count($teamSizes); $i++) {
                $lastTeam = $i + 1;
                for ($j = 0; $j < $teamSizes[$i]; $j++) {
                    $teamsAllocation[] = $lastTeam;
                }
            }
        }

        foreach ($result as &$player) {
            if ($player['team'] > $lastTeam) {
                $lastTeam = $player['team'];
            }
        }

        if (!is_numeric($lastTeam)) {
            $lastTeam = null;
        }

        foreach ($result as &$player) {
            if ($player['team']) {
                $key = array_search($player['team'], $teamsAllocation);
                if ($key !== false) {
                    unset($teamsAllocation[$key]);
                }
            } else {
                $player['team'] = $teamsAllocation ? array_shift($teamsAllocation) : $lastTeam;
            }
        }

        // sort players by team
        usort($result, function ($a, $b) {
            if ($a['team'] == $b['team']) {
                return $a['order'] - $b['order'];
            }
            if ($a['team'] === null) {
                return $b['team'] === null ? 0 : 1;
            }
            if ($b['team'] === null) {
                return $a['team'] === null ? 0 : -1;
            }

            if (is_numeric($a['team']) and is_numeric($b['team'])) {
                return $a['team'] - $b['team'];
            } else {
                return strcasecmp($a['team'], $b['team']);
            }
        });

        Log::debug("Registration has " . count($result) . " valid player(s)");

        return $result;
    }

    private static function fmt(int $date): string
    {
        return strftime('%F', $date) . "/" . $date;
    }

    public static function find_between_dates(Model_Section $section, Model_Club $club, int $initialDate, int $currentDate, &$info = array()): array
    {
        Log::debug("Request for registration for $section/$club: between " . self::fmt($initialDate) . " and " . self::fmt($currentDate));
        $current = Model_Registration::find_before_date($section, $club, $currentDate);
        $restrictionDate = Config::get('section.date.restrict', null);
        $initial = null;

        if ($restrictionDate) {
            $restrictionDate = strtotime($restrictionDate);
            if ($currentDate > $restrictionDate) {
                Log::debug("Restrictions in place");
                $initial = Model_Registration::find_before_date($section, $club, $initialDate);
            }
        }

        $teamSizes = $club ? $club->getTeamSizes($section->name) : array();
        $history = Model_Player::getHistory($club, $currentDate);

        $info['initial'] = $initialDate;
        $info['current'] = $currentDate;
        $info['teamSizes'] = $teamSizes;
        $info['club'] = $club->name;
        $info['section'] = $section->name;

        return self::buildRegistration($current, $initial, $teamSizes, $history, \Config::get("section.registration.placeholders", true));
    }


    /**
     * Returns a list of players that are elgible to play before a specific date.
     *
     * @param $club The club being searched
     * @param $date The date before which players must be elgible
     * @param $firstAsNecessary If there is no registration available then allow
     *            the first available registration instead
     * @return A list of players
     */
    public static function find_before_date(Model_Section $section, Model_Club $club, int $date): array
    {
        $match = null;
        foreach (self::find_all($section, $club) as $registration) {
            // If there's a newer one before the date use that (or get the first one)
            if ($match == null || $registration['timestamp'] < $date) {
                $match = $registration['name'];
            }
        }

        $result = array();
        if ($match != null) {
            $file = self::getRoot($section, $club) . "/$match";

            Log::info("[$club] Registration on " . date('Y-m-d H:i:s', $date) . " = " . basename($file));

            if ($section) {
                loadSectionConfig($section->name);
            }
            $result = self::readRegistrationFile($file, $club);
        }

        return $result;
    }

    /**
     * Open a file and read a list of players from it. Strips headers
     * and assigns teams as required.
     */
    private static function readRegistrationFile($file, ?Model_Club $club = null)
    {
        $jsonFile = "$file.json";
        if (file_exists($jsonFile)) {
            Log::debug("[$club] Using cached registration: $jsonFile");
            $json_data = file_get_contents($jsonFile);
            return json_decode($json_data, true);
        }

        $groups = null;
        if (Config::get("section.allowassignment")) {
            $groups = array();
            foreach (Model_Competition::find('all') as $comp) {
                if ($comp['groups']) {
                    foreach (explode(',', $comp['groups']) as $group) {
                        $groups[trim(strtolower($group))] = trim($group);
                    }
                }
            }
        }

        Log::info("[$club] Caching file=$file");
        if ($groups)
            Log::debug("groups=" . implode(",", array_values($groups)));

        $result = self::parse(file($file), $club->name, $groups, Config::get("section.allowassignment"));

        //$json_data = xjson_encode($result, JSON_PRETTY_PRINT);
        file_put_contents("$file.json", json_encode($result, JSON_PRETTY_PRINT));
        static::touch("$file.json");

        return $result;
    }

    private static function touch($path, $date = null)
    {
        if (!$path) {
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


    /**
     * Parse a registration providered as an array of lines.
     */
    public static function parse(array $lines, string $rclub, ?array $groups, bool $allowAssignment = false): array
    {
        $result = array();
        $pastHeaders = false;
        $team = null;
        $lastline = null;
        foreach ($lines as $player) {
            $player = trim($player);

            if (!$player) {
                continue;
            }		// ignore empty lines

            // Join broken lines
            if ($player[0] == '"' and substr($player, -1) != '"') {
                $lastline = $player;
                continue;
            }
            if ($lastline and substr($player, -1) != '"') {
                $lastline .= $player;
                continue;
            }
            if ($lastline) {
                $player = $lastline . $player;
                $lastline = null;
            }

            // Remove comments
            if ($player[0] == '#') {
                continue;
            }

            $membershipId = null;
            $matches = array();
            if (preg_match('/\b([a-z]{2}[0-9]{4,})\b/i', $player, $matches)) {
                $membershipId = $matches[1];
                $player = str_replace($membershipId, "", $player);
            }

            $matches = array();
            if (preg_match('/^\s*"([^"]+)"\s*$/', $player, $matches)) {
                $player = $matches[1];
            }
            $arr = str_getcsv($player);

            // Strip empty values from left
            while ($arr && !$arr[0]) {
                array_shift($arr);
            }
            if (!$arr) {
                continue;
            }

            if (stripos($arr[0], '------') === 0) { // if the first column starts with ------ then reset the registration
                $result = array();
                continue;
            }

            if (!$pastHeaders) {
                if (stripos($arr[0], '------') === 0) {
                    $pastHeaders = true;
                    continue;
                }

                if (preg_match('/(club:|school:|registration|surname|\bname\b|do not delete)/i', $arr[0])) {
                    continue;
                }
                if ($rclub && stripos($arr[0], $rclub) === 0) {
                    continue;
                }
            }
            $pastHeaders = true;
            while ($arr && is_numeric($arr[0])) {
                array_shift($arr);
            }

            if (!$arr) {
                continue;
            }

            if (stripos($arr[0], 'team:')) {
                $team = trim(substr($arr[0], 5));
                continue;
            }

            $player = $arr[0];

            if (count($arr) > 1) {
                if (preg_match('/[0-9:]/', $arr[1]) === false) {
                    $player .= "," . $arr[1];
                }
            }

            $player = Model_Player::cleanName($player);
            $pt = null;

            $playerTeam = is_numeric($team) ? $team : null;
            if ($allowAssignment) {
                for ($i = count($arr) - 1; $i > 0; $i--) {
                    if ($arr[$i]) {
                        $group = trim(strtolower($arr[$i]));
                        if (isset($groups[$group])) {
                            $playerTeam = $groups[$group];
                            $pt = $groups[$group];
                            //Log::debug("PT = $playerTeam $i");
                        } else {
                            $matches = array();
                            if (preg_match('/^([1-9][0-9]*)(?:(st|nd|rd|th)(s)?)?$/', trim($arr[$i]), $matches)) {
                                $playerTeam = $matches[1];
                                $pt = $arr[$i];
                            }
                        }
                        // even if no team is matched scan is finished
                        break;
                    }
                }
            }

            $playerArr = Model_Player::cleanName($player, "[Fn][LN]");

            if ($player) {
                if (!self::validateMembership($rclub, $playerArr['Fn'], $playerArr['LN'], $membershipId)) {
                    if (Config::get("section.registration.mandatoryhi", "noselect") === 'noregister') {
                        continue;
                    }
                    $membershipId = null;
                }

                $result[] = array(
                    "name" => $player,
                    "lastname" => $playerArr['LN'],
                    "firstname" => $playerArr['Fn'],
                    "membershipid" => $membershipId,
                    "status" => "registered",
                    "phone" => Model_Player::phone($player),
                    "team" => $playerTeam,
                    "pt" => $pt,
                    "club" => $rclub
                );
            }
        }

        return $result;
    }

    private static function validateMembership($club, $firstName, $lastName, $membershipId)
    {
        if (!self::$codes) {
            return true;
        }

        $clubId = self::$codes[$club];

        $url = "http://portal.azolve.com/azolveapi/AzolveService/Neptune2?clientReference=HockeyIreland" .
            "&objectName=Cus_ValidateClubMembers&objectType=sp" .
            "&parameters=MID%7C$membershipId;Firstname%7CXX;Surname%7CXX;ClubID%7C$clubId;DOB%7C1970-01-01" .
            "&password=0O34zW934rVC&userId=AzolveAPI";

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        // Set so curl_exec returns the result instead of outputting it.
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        // Get the response and close the channel.
        $response = curl_exec($ch);
        curl_close($ch);

        $response = json_decode($response);
        if (!is_array($response) || $response[1][0] === 0) {
            Log::warn("Invalid membership ID: $membershipId = $club:$firstName:$lastName " . print_r($response, true));
            return false;
        }

        Log::debug("Valid membership ID: $membershipId = $club:$firstName:$lastName");

        return true;
    }
}

Model_Registration::init();
