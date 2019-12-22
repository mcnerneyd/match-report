<?php
ini_set("auto_detect_line_endings", true);

class Model_Registration 
{
	public static function addRegistration($file, $club) {
		$ts = Date::forge()->format("%y%m%d%H%M%S");
		$arcDir = self::getRoot($club);
		$newName = "$arcDir/$ts.csv";

		File::rename($file, $newName);

		return $newName;
	}

	public static function getRoot($club = null, $file = null) {
		$root = sitepath()."/registration";

		if ($club) $root .= "/".strtolower($club);

		if (!file_exists($root)) {
			mkdir($root,0777,TRUE);
		}

		if ($file) $root .= "/$file";

		return $root;
	}

	public static function flush($club) {
		$root = self::getRoot($club);
		Log::debug("Flushing root: $root");
		$files = glob("$root/*.json");
		
		if ($files) {
			foreach ($files as $file) {
				Log::debug("Flush $file");
				unlink($file);
			}	
		}
	}

	public static function writeErrors($club, $errors) {
		$root = self::getRoot($club);
		$files = glob("$root/*.csv");
		$file = end($files);
		file_put_contents($file.".err", implode("\n", $errors));
	}

	public static function delete($club, $filename) {
		$file = self::getRoot($club, $filename);

		Log::info("delete file: $file");

		unlink($file);
	}

	public static function find_all_players($time = null) {

		if ($time == null) $time = time();

		$result = array();

		Log::info("Full player list requested: ".date('Y-m-d H:i', $time));

		foreach (glob(self::getRoot()."/*") as $club) {
			if (!preg_match("/^[A-Za-z ]*$/", $club)) continue;
			$club = basename($club);
			$clubReg = self::find_before_date($club, $time);
			$result = array_merge($result, $clubReg);
		}

		return $result;
	}

	public static function clearErrors($club) {
		$root = self::getRoot($club);
		if (is_dir($root)) {
			$files = glob("$root/*.csv.err");
			if ($files) {
				foreach ($files as $name) {
					if (!is_file($name)) continue;
					unlink($name);
					Log::debug("Deleted errors file: $name");
				}
			}
		}
	}

	public static function find_all($club) {
		$result = array();
		$club = strtolower($club);
		$root = self::getRoot($club);
		$seasonStart = currentSeasonStart()->get_timestamp();
		Log::debug("loading $root (".strftime('%F', $seasonStart)."/$seasonStart)");

		if (is_dir($root)) {
			$files = glob("$root/*.csv");
			if ($files) {
				foreach ($files as $name) {
					if (!is_file($name)) continue;
					$ts=filemtime($name);
					$finfo=finfo_open(FILEINFO_MIME);
					$ftype = "";
					if ($finfo) {
						$ftype = finfo_file($finfo, $name);
						finfo_close($finfo);
					}
					$fileData = array("club"=>$club,
						"name"=>basename($name),
						"timestamp"=>$ts,
						"type"=>$ftype,
						"cksum"=>md5_file($name));
					if (file_exists($name.".err")) {
						$fileData['errors'] = file($name.".err");
					}

					$result[] = $fileData;
				}
			}
		}

		return $result;
	}

	public static function find_between_dates($clubName, $initialDate, $currentDate) {
		$club = Model_Club::find_by_name($clubName);
		$result = array();
		$currentNames = array();
		$currentLookup = array();

		$current = Model_Registration::find_before_date($clubName, $currentDate);
		foreach ($current as $player) {
			$currentNames[] = $player['name'];
			$currentLookup[$player['name']] = $player;
		}

		Log::debug("Boor in place: $clubName");
		$restrictionDate = strtotime(Config::get('config.date.restrict'));

		$order = 0;
		if ($currentDate > $restrictionDate) {
			Log::debug("Restrictions in place");
			$initial = Model_Registration::find_before_date($clubName, $initialDate);
			foreach ($initial as $player) {
				if (($key = array_search($player['name'], $currentNames)) !== false) {
					if (isset($currentLookup[$player['name']]['membershipid'])) {
						$player['membershipid'] = $currentLookup[$player['name']]['membershipid'];
						//Log::info("Glif: $key = ${player['membershipid']} --".$currentNames[$key]['membershipid']);
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

		$lastTeam = 1;
		$teamsAllocation = array();
		if ($club) {
			$teamSizes = $club->getTeamSizes();
			for ($i=0;$i<count($teamSizes);$i++) {
				for ($j=0;$j<$teamSizes[$i];$j++) {
					$teamsAllocation[] = $i+1;
				}
				$lastTeam = $i+1;
			}
		}

		foreach ($result as $player) {
			if ($player['team'] > $lastTeam) $lastTeam = $player['team'];
		}

		foreach ($result as &$player) {
			if ($player['team']) {
				$key = array_search($player['team'], $teamsAllocation);
				if ($key !== FALSE) {
					unset($teamsAllocation[$key]);
				}
			} else {
				$player['team'] = $teamsAllocation ? array_shift($teamsAllocation) : $lastTeam;
			}
		}

		usort($result, function($a, $b) {
			if ($a['team'] == $b['team']) {
				return $a['order'] - $b['order'];
			}
			return $a['team'] - $b['team'];
		});

		$history = Model_Player::getHistory($clubName);
		
		foreach ($result as &$player) {
			$player['score'] = 99;
			if (!isset($history[$player['name']])) {
				$player['history'] = array();	
				continue;
			}
			$player['history'] = array_filter($history[$player['name']], function($a) use ($currentDate) {
				return Date::create_from_string($a['date'], 'mysql')->get_timestamp() < $currentDate;
			});
			$teams = array_map(function($a) { return $a['team']; }, $player['history']);
			if ($teams) {
				$first = min($teams);
				$firstCount = array_count_values($teams);
				$firstCount = $firstCount[$first];
				$player['score'] = round($first + (1 - ($firstCount/count($teams))), 2);
			}
		}

		Log::debug("Registration has ".count($result). " valid player(s)");

		return $result;
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
	public static function find_before_date($club, $date, $firstAsNecessary = true) {
		$match = null;
		$club = strtolower($club);
		foreach (self::find_all($club) as $registration) {
			if ($firstAsNecessary && $match == null) $match = $registration['name'];
			if ($registration['timestamp'] < $date) {
				$match = $registration['name'];
			}
		}

		$result = array();
		if ($match != null) {
			$file = self::getRoot($club, $match);

			Log::debug("Find: $club ".date('Y-m-d H:i:s', $date)." = $file");

			$result = self::readRegistrationFile($file, $club);
		}

		return $result;
	}

	/**
	 * Open a file and read a list of players from it. Strips headers
	 * and assigns teams as required.
	 */
	public static function readRegistrationFile($file, $rclub = null) {
		$jsonFile = "$file.json";
		if (file_exists($jsonFile)) {
			$json_data = file_get_contents($jsonFile);
			return json_decode($json_data, TRUE);
		}

		$groups = array();
		if (Config::get("config.allowassignment")) {
			foreach (Model_Competition::find('all') as $comp) {
				if ($comp['groups']) {
					foreach (explode(',', $comp['groups']) as $group) {
						$groups[trim(strtolower($group))] = trim($group);
					}
				}
			}
		}

		Log::debug("readRegistrationFile: club=$rclub file=$file");

		$result = self::parse(file($file), $rclub, $groups);

		$json_data = xjson_encode($result, JSON_PRETTY_PRINT);
		file_put_contents("$file.json", $json_data);

		return $result;
	}

	public static function parse($lines, $rclub, $groups) {

		$result = array();
		$pastHeaders = false;
		$team = null;
		$lastline = null;
		foreach ($lines as $player) {
			$player = trim($player);

			if (!$player) continue;		// ignore empty lines

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
				$player = $lastline.$player;
				$lastline = null;
			}

			// Remove comments
			if ($player[0] == '#') continue;

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
			while ($arr && !$arr[0]) array_shift($arr);
			if (!$arr) continue;

			if (!$pastHeaders) {

				if (stripos($arr[0], '------') === 0) {
					$pastHeaders = true;
					continue;
				}

				if (preg_match('/club:|last name|first name|name|do not delete/i', $arr[0])) {
					continue;
				}
				if ($rclub && stripos($arr[0], $rclub) === 0) continue;

			}
			$pastHeaders = true;
			while ($arr && is_numeric($arr[0])) array_shift($arr);

			if (!$arr) continue;

			if (stripos($arr[0], 'team:')) {
				$team = trim(substr($arr[0], 5));
				continue;
			}

			$player = $arr[0];

			if (count($arr) > 1) {
				if (preg_replace('/[^A-Za-z]/',"", $arr[1])) {
					$player .= ",".$arr[1];
				}
			}

			$player = cleanName($player);
			$pt = null;

			$playerTeam = $team;

			if (Config::get("config.allowassignment")) {
				for ($i=count($arr)-1;$i>0;$i--) {
					if ($arr[$i]) {
						$group = trim(strtolower($arr[$i]));
						if (isset($groups[$group])) {
							$playerTeam = $groups[$group];
							$pt = $groups[$group];
						} else {
							$matches = array();
							if (preg_match('/^([0-9]+)(?:(st|nd|rd|th)(s)?)?$/', $arr[$i], $matches)) {
								$playerTeam = $matches[1];
								$pt = $arr[$i];
							}
						}
						// even if no team is matched scan is finished
						break;
					}
				}
			}

			$playerArr = cleanName($player, "[Fn][LN]");

			if ($player) $result[] = array("name"=>$player,
				"lastname"=>$playerArr['LN'],
				"firstname"=>$playerArr['Fn'],
				"membershipid"=>$membershipId,
				"status"=>"registered",
				"phone"=>phone($player), 
				"team"=>$playerTeam,
				"pt"=>$pt,
				"club"=>$rclub);
		}

		return $result;
	}
}
