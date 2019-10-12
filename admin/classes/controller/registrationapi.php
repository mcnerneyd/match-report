<?php
class Controller_RegistrationApi extends Controller_Rest
{
	// --------------------------------------------------------------------------
	public function get_list() {
		$dateS = \Input::param('d', Date::forge()->format("%Y%m%d"));
		$clubId = \Auth::get('club_id');
		$club = Model_Club::find_by_id($clubId);
		$club = $club['name'];
		$team = \Input::param('t', 1);
		$groups = \Input::param('g', null);

		$players = self::getPlayers($club, $dateS, $team, $groups);

		$players = array_values($players);
		usort($players, function($a, $b) { return strcmp($a['name'], $b['name']); });

		Log::debug(count($players)." player(s) valid for team $team/$groups ($club/$clubId) date=$dateS");

		return $this->response($players);
	}

	public static function getPlayers($club, $dateS, $team, $groups) { 
		if ($groups) {
			$groups = explode(",", strtolower($groups));
		}

		$date = Date::create_from_string($dateS, '%Y%m%d');
		$date = $date->get_timestamp();
		$initialDate = strtotime("first thursday of " . date("M YY", $date));
		if ($initialDate > $date) {
				$initialDate = strtotime("-1 month", $date);
				$initialDate = strtotime("first thursday of " . date("M YY", $initialDate));
		}
		$startDate = strtotime("+1 day", $initialDate);

		$players = Model_Registration::find_between_dates($club, $startDate, $date);

		$players = array_filter($players, function($v) use ($team, $groups) {
			if ($v['team'] < $team) return false;
			if ($groups) {
				if (!in_array(strtolower($v['team']), $groups)) {
					return false;
				}
			}

			return true;
		});
		/*foreach ($players as &$player) {
			if ($player['team'] < $teamNo) continue;
			if ($groups) {
				if (!in_array($player['team'], $groups)) {
					continue;
				}
			}

			$history = Club::getPlayerHistorySummary($club);
			if (isset($history[$player['name']])) $teams = $history[$player['name']]['teams'];
			else $teams = array();

			$result[] = $player; //array('teams'=>$teams);
		}*/

		return $players;
	}

	// --------------------------------------------------------------------------
	public function put_number() {
		$clubName = Input::param("c");
		$player = Input::param("p");
		$number = Input::param("n");

		$club = Model_Club::find_by_name($clubName);

		$incident = new Model_Incident();
		$incident->player = $player;
		//$incident->matchcard_id = Input::post('card_id');
		$incident->detail = $number;
		$incident->type = 'Number';
		$incident->club = $club;
		$incident->resolved = 0;
		$incident->save();

		Log::debug("Set shirt number for $player to $number");

		return new Response("Player number set to $number", 200);
	}

	// --------------------------------------------------------------------------
	public function delete_index() {
		if (!\Auth::has_access("admin.all")) {
			return new Response("Not permitted to register: $access", 403);
		}

		$club = Input::param("c");
		$file = Input::param("f");

		Model_Registration::delete($club, $file);

		return new Response("Registration file: $file deleted from club $club", 202);
	}

	// --------------------------------------------------------------------------
	public function post_rename() {
		$clubname = Input::param("c");
		$old = Input::param("o");
		$new = Input::param("n");

		Log::info("Rename player $old to $new (club=$clubname)");

		$club = Model_Club::find_by_name($clubname);

		if ($club == null) return new Response("Unknown club: $clubname", 404);

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

	public function post_index() {
		// FIXME Check user admin or matches club
		$access = 'admin.all';
		if (Config::get('config.automation.allowrequest')) {
			$access = 'registration.post';
		}
		
		if (!\Auth::has_access($access)) { 
			return new Response("Not permitted to register: $access", 403);
		}

		$club = Input::param("club");
		$file = Input::file("file");
		$type = mime_content_type($file['tmp_name']);

		Log::info("Posting ${file['name']} for club: $club (type=$type)");

		if (preg_match("/.*\.xlsx?/", $file['name']) || !preg_match("/text\/.*/", $type)) {
			$file['tmp_name'] = convertXls($file['name'], $file['tmp_name']);
		}

		$filename = Model_Registration::addRegistration($file['tmp_name'], $club);

		$this->validateRegistration($club, $filename);

		//return new Response("Registration Uploaded", 201);
		Response::redirect("registration");
	}

	// ----- errors -------------------------------------------------------------
	public function get_errors2() {
		$club = Input::param('c', null);
		$file = Input::param('f', null);

		if ($club == null) return;

		if ($file == null) { 
			$all = Model_Registration::find_all($club);
			$file = array_shift($all);
			$file = $file['name'];
			echo "File: $file\n";
		}

		return $this->validateRegistration($club, $file);
	}


	public function delete_errors() {
		$club = Input::param("club", null);
		if ($club == null) return;
		$club = strtolower($club);

		Model_Registration::clearErrors($club);
	}

	public function get_errors() {
		$club = Input::param("c", null);
		if ($club == null) return;
		$club = strtolower($club);

		$errors = array();

		foreach ($this->get_duplicates($club) as $name=>$players) {
			foreach ($players as $player) {
				$errors[] = array('class'=>'warn','msg'=>
					"Player $name is similar to ${player['name']} playing for ${player['club']}");
			}	
		}

		$registrations = Model_Registration::find_all($club);
		$lastReg = end($registrations);

		if (isset($lastReg['errors'])) {
			Log::info("Registration has error");
			$errorStatus = Config::get("config.registration.blockerrors", false) ? "error":"warn";
			foreach ($lastReg['errors'] as $error) {
				$errors[] = array('class'=>$errorStatus, 'msg'=>$error);
			}
		}

		return $errors;
	}

	public function get_duplicates($club) {

		Log::info("Request duplicates");
		$errors = array();

		$now = Date::forge()->get_timestamp();

		$playersByName = Model_Registration::find_all_players($now);
		usort($playersByName, function ($a, $b) { return strcmp($a['phone'], $b['phone']); });

		Log::info("Players:".count($playersByName));

		$lastPlayer = null;
		foreach ($playersByName as $player) {
			if ($lastPlayer == null) {
				$lastPlayer = $player;
				continue;
			}

			if ($player['phone'] ==  $lastPlayer['phone']) {
				$name = $player['phone'];
				if (!isset($errors[$name])) $errors[$name] = array($lastPlayer);
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

	private function validateRegistration($club, $filename, $test=false) {
		$errors = array();

		$date = Date::time();

		$thurs = strtotime("first thursday of " . $date->format("%B %Y"));
		if ($thurs > $date->get_timestamp()) {
			$thurs = Date::forge(strtotime("-1 month", $date->get_timestamp()));
			$thurs = strtotime("first thursday of " . $thurs->format("%B %Y"));
		}
		$thurs = strtotime("+1 day", $thurs);

		$registration = Model_Registration::find_between_dates($club, $thurs, $date->get_timestamp());

		$scores = array_map(function($a) { return $a['score'];}, $registration);
		sort($scores);

		$start = 0;
		$teamSizes = Model_Club::find_by_name($club)->getTeamSizes(false);
		array_pop($teamSizes);
		foreach ($teamSizes as $team=>$size) {
			if ($size == 0) continue;

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

		if (Config::get("config.allowassignment")) {
			foreach ($registration as $player) {
				$counts = array();
				foreach ($player['history'] as $history) {
					$team = $history['team'];
					if (!isset($counts[$team])) $counts[$team] = 0;
					$counts[$team] = $counts[$team] + 1;
				}
				if (!$counts) continue;
				arsort($counts);
				$max = array_keys($counts);
				$max = $max[0];
				$playerTeam = $player['team'];
				if (isset($counts[$playerTeam])) {
					if ($counts[$max] == $counts[$playerTeam]) continue;
				}

				if ($counts[$max] >= 6) {
					$errors[] = "${player['name']} has played 6 times or more for a team other than $club $playerTeam";
				}
			}
		}

		if (!$test && $errors) {
			Model_Registration::writeErrors($club, $errors);
		}

		return $errors;
	}

}
