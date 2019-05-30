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
		$root = "../../../archive/".Session::get('site')."/registration";

		if ($club) $root .= "/".strtolower($club);

		if (!file_exists($root)) {
			mkdir($root,0777,TRUE);
		}

		if ($file) $root .= "/$file";

		return $root;
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

		if (is_dir($root)) {
			$files = glob("$root/*.csv");
			if ($files) {
				foreach ($files as $name) {
					if (!is_file($name)) continue;
					$ts=filemtime($name);
					if ($ts < $seasonStart) continue;
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

	public static function find_before_date($rclub, $date, $firstAsNecessary = true) {
		//echo "<!--\n";
		$match = null;
		$club = strtolower($rclub);
		foreach (self::find_all($club) as $registration) {
			if ($firstAsNecessary && $match == null) $match = $registration['name'];
			//echo "Comp:".$registration['timestamp']."=".$date."\n";
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
		Config::load('custom.db', 'config');

		$groups = array();
		if (Config::get("config.allowassignment")) {
			foreach (Model_Competition::find('all') as $comp) {
				if ($comp['groups']) {
					foreach (explode(',', $comp['groups']) as $group) {
						$groups[trim(strtolower($group))] = $group;
					}
				}
			}
		}

		$result = array();
		Log::debug("readRegistrationFile: club=$rclub file=$file");
		$pastHeaders = false;
		$team = null;
		$lastline = null;
		foreach (file($file) as $player) {
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

			$matches = array();
			if (preg_match('/^\s*"([^"]+)"\s*$/', $player, $matches)) {
				$player = $matches[1];
			}
			$arr = str_getcsv($player);
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
							if (preg_match('/^([0-9]+)(st|nd|rd|th)?$/', $arr[$i], $matches)) {
								$playerTeam = $matches[1];
								$pt = $arr[$i];
							}
						}
						// even if no team is matched scan is finished
						break;
					}
				}
			}

			if ($player) $result[] = array("name"=>$player,
				"status"=>"registered",
				"phone"=>phone($player), 
				"team"=>$playerTeam,
				"pt"=>$pt,
				"club"=>$rclub);
		}

		return $result;
	}
}
