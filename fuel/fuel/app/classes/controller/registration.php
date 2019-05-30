<?php
class Controller_Registration extends Controller_Hybrid
{
	public function before() {
		if (!\Auth::has_access('registration.*')) throw new HttpNoAccessException;

		parent::before();
	}

	public function get_list() {
		$date = \Input::param('d', Date::forge()->format("%Y%m%d"));
		$club = \Input::param('c');

		return $this->response(array("d"=>$date,"c"=>$club));
	}

	public function get_index() {
		if (!Auth::has_access("registration.view")) {
			return new Response("Not permitted to view registrations", 403);
		}

		$club = Input::param("c");
		if (!isset($club)) {
			$username = Session::get("username");
			$user = Model_User::find_by_username($username);
			$club = $user['club']['name'];
		}

		$registrations = Model_Registration::find_all($club);
		
		$this->template->title = "Registrations";
		$this->template->content = View::forge('registration/index', array('club'=>$club,
			'clubs'=>Model_Club::find('all'),
			'registrations'=>$registrations));
	}

	public function get_errors() {
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

	public function get_info() {
		$userObj = Model_User::find_by_username(Session::get('username'));
		if ($userObj == null) {
			Log::error("No such user: ".Session::get('username'));
			return;
		}

		$club = $userObj->club['name'];

		$clubUser = Model_User::find('first', array(
				'where'=>array(
					array('username','=',$club),
					array('role','=','user')	
				)
			));

		$this->template->title = "Club Info";
		$this->template->content = View::forge('registration/info',
			array('user'=>$clubUser));
	}

	public function post_number() {
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
	}

	public function post_index() {
		// FIXME Check user admin or matches club
		$access = 'admin.all';
		Config::load('custom.db', 'config');
		if (Config::get('config.automation_allowrequest')) {
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

		if (\Auth::has_access('admin.all')) {
			$date = Input::param('d', null);
			if ($date) {
				$date = Date::create_from_string($date, "%Y-%m-%d");
				touch($filename, $date->get_timestamp());
				echo "Setting timestamp on $filename: $date";
			}
		}

		$this->validateRegistration($club, $filename);

		//return new Response("Registration Uploaded", 201);
		Response::redirect("registration");
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

		$registration = $this->stage($club, $thurs, $date->get_timestamp());

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

				if ($counts[$max] > 8) {
					$errors[] = "${player['name']} has played more than 8 times and more often for a team other than $club $playerTeam";
				}
			}
		}

		if (!$test && $errors) {
			Model_Registration::writeErrors($club, $errors);
		}

		return $errors;
	}

	public function get_registration() {

		if (!Auth::has_access("registration.view")) {
			return new Response("Not permitted to view registrations", 403);
		}

		$user = Model_User::find_by_username(Session::get("username"));
		$club = $user['club']['name'];

		if (\Auth::has_access("admin.all")) {
			$club = \Input::param('c', $club);
		}

		if (!$club) {
			return new Response("No club specified for registration", 404);
		}

		$file = Input::param('f', null);

		if ($file != null) {
			File::download(Model_Registration::getRoot($club, $file),
				 null, "text/csv");
		}

		$date = Input::param('d', null);
		if (!$date) {
			$date = Date::time();
		} else {
			$date = Date::create_from_string($date, "%Y-%m-%d");
		}

		$thurs = strtotime("first thursday of " . $date->format("%B %Y"));
		if ($thurs > $date->get_timestamp()) {
			$thurs = Date::forge(strtotime("-1 month", $date->get_timestamp()));
			$thurs = strtotime("first thursday of " . $thurs->format("%B %Y"));
		}
		$thurs = strtotime("+1 day", $thurs);

		$registration = $this->stage($club, $thurs, $date->get_timestamp());

		$this->template->title = "Registrations";
		$this->template->content = View::forge('registration/list', array(
			'registration'=>$registration,
			//'history'=>$history,
			'club'=>$club,
			'all'=>Model_Registration::find_before_date($club, Date::forge()->get_timestamp()),
			'ts'=>$date, 
			'base'=>Date::forge($thurs)));
	}

	// combine multiple files based on date range
	private function stage($club, $initialDate, $currentDate) {
		$result = array();
		$currentNames = array();
		$currentLookup = array();

		$current = Model_Registration::find_before_date($club, $currentDate);
		foreach ($current as $player) {
			$currentNames[] = $player['name'];
			$currentLookup[$player['name']] = $player;
		}

		$restrictionDate = strtotime(Config::get('config.date.restrict'));

		$order = 0;
		if ($currentDate > $restrictionDate) {
			$initial = Model_Registration::find_before_date($club, $initialDate);
			foreach ($initial as $player) {
				if (($key = array_search($player['name'], $currentNames)) !== false) {
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
		$teamSizes = Model_Club::find_by_name($club)->getTeamSizes();
		for ($i=0;$i<count($teamSizes);$i++) {
			for ($j=0;$j<$teamSizes[$i];$j++) {
				$teamsAllocation[] = $i+1;
			}
			$lastTeam = $i+1;
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
				//echo "<!-- Removed $key ct=".count($teamsAllocation)." -->";
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

		$history = Model_Player::getHistory($club);

		foreach ($result as &$player) {
			$player['score'] = 99;
			if (!isset($history[$player['name']])) {
				$player['history'] = array();	
				continue;
			}
			$player['history'] = $history[$player['name']];
			$teams = array_map(function($a) { return $a['team']; }, $player['history']);
			if ($teams) {
				$first = min($teams);
				$firstCount = array_count_values($teams);
				$firstCount = $firstCount[$first];
				$player['score'] = round($first + (1 - ($firstCount/count($teams))), 2);
			}
		}

		return $result;
	}
}
