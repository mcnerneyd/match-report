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
		if ($file) $root .= "/$file";

		if (!file_exists($root)) {
			mkdir($root,0777,TRUE);
		}

		return $root;
	}

	public static function delete($club, $filename) {
		$file = self::getRoot($club, $filename);

		Log::info("delete file: $file");

		unlink($file);
	}

	public static function find_all_players($date) {
		$result = array();

		foreach (glob(self::getRoot("*")) as $club) {
			$club = basename($club);
			$clubReg = self::find_before_date($club, $date);
			$result = array_merge($result, $clubReg);
		}

		return $result;
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
					$ts=filemtime($name);
					if ($ts < $seasonStart) continue;
					$result[] = array("club"=>$club,
						"name"=>basename($name),
						"timestamp"=>$ts,
						"cksum"=>md5_file($name));
				}
			}
		}

		return $result;
	}

	public static function find_before_date($rclub, $date) {
		$match = null;
		$club = strtolower($rclub);
		foreach (self::find_all($club) as $registration) {
			if ($registration['timestamp'] < $date) {
				$match = $registration['name'];
			}
		}

		$result = array();
		if ($match != null) {
			$file = self::getRoot($club, $match);
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
		$result = array();
		Log::debug("readRegistrationFile: club=$rclub file=$file");
		$pastHeaders = false;
		$team = null;
		foreach (file($file) as $player) {
			if ($player[0] == '#') continue;
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

			if (count($arr) > 1) $player .= ",".$arr[1];
			$player = cleanName($player);

			$playerTeam = $team;

			if (Config::get("config.allowassignment")) {
				for ($i=count($arr)-1;$i>0;$i--) {
					if ($arr[$i]) {
							$matches = array();
							if (preg_match('/^([0-9]+)(st|nd|rd|th)?$/', $arr[$i], $matches)) {
								$playerTeam = $matches[1];
							}
						break;
					}
				}
			}

			if ($player) $result[] = array("name"=>$player,
				"status"=>"registered",
				"phone"=>phone($player), 
				"team"=>$playerTeam,
				"club"=>$rclub);
		}

		return $result;
	}
}
