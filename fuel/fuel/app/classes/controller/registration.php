<?php
class Controller_Registration extends Controller_Hybrid
{
/*	public function before() {
		if (!\Auth::has_access('admin.all')) throw new HttpNoAccessException;

		parent::before();
	}*/

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

	public function get_info() {
		$userObj = Model_User::find_by_username(Session::get('username'));
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
		if (Config::get('config.automation_allowrequest')) {
			$access = 'registration.post';
		}
		
		if (!\Auth::has_access($access)) {
			return new Response("Not permitted to register", 403);
		}

		$club = Input::param("club");
		$file = Input::file("file");

		if (preg_match("/.*\.xlsx?/", $file['name'])) {
			$file['tmp_name'] = convertXls($file['name'], $file['tmp_name']);
		}

		if ($this->validateRegistration($file['tmp_name'])) {
			return new Response("Registration Failed", 400);
		}

		Model_Registration::addRegistration($file['tmp_name'], $club);

		//return new Response("Registration Uploaded", 201);
		Response::redirect("registration");
	}

	private function validateRegistration($filename) {
		$errors = array();

		$now = Date::forge()->get_timestamp();
		$club = Session::get("username");
		$reg = Model_Registration::readRegistrationFile($filename, $club);
		usort($reg, function ($a, $b) { return strcmp($a['phone'], $b['phone']); });

		$playersByName = Model_Registration::find_all_players($now);
		usort($playersByName, function ($a, $b) { return strcmp($a['phone'], $b['phone']); });

		$i=0;
		foreach ($playersByName as $player) {
			for (;$i < count($reg); $i++) {
				$regPlayer = $reg[$i];
				$cmp = strcmp($player['phone'], $regPlayer['phone']);
				if ($cmp < 0) break;
				if ($cmp == 0) {
					if (strcasecmp($regPlayer['club'], $player['club']) != 0) {
						$errors[] = "Player ${regPlayer['name']} ${regPlayer['club']} name is already registered to ${player['club']} as ${player['name']}";
					}
					break;
				}
			}
		}

		print_r($errors);

		return array();
	}

	public function get_registration() {

		if (!Auth::has_access("registration.view")) {
			return new Response("Not permitted to view registrations", 403);
		}

		$user = Model_User::find_by_username(Session::get("username"));
		$club = $user['club']['name'];

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

		$registration = $this->stage($club, $thurs, $date->get_timestamp());
		
		$this->template->title = "Registrations";
		$this->template->content = View::forge('registration/list', array(
			'registration'=>$registration,
			'club'=>$club,
			'all'=>Model_Registration::find_before_date($club, Date::forge()->get_timestamp()),
			'ts'=>$date, 'base'=>Date::forge($thurs)));
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
		$initial = Model_Registration::find_before_date($club, $initialDate);
		echo "<!-- Initial\n".print_r($initial,true)."-->";
		$order = 0;
		foreach ($initial as $player) {
			if (($key = array_search($player['name'], $currentNames)) !== false) {
				unset($currentNames[$key]);
			} else {
				$player['status'] = "deleted";
			}

			$player['order'] = $order++;
			$result[] = $player;
		}
		foreach ($currentNames as $name) {
			$player = $currentLookup[$name];
			$player['status'] = "added";
			$player['order'] = $order++;
			$result[] = $player;
		}

		$teamsAllocation = array();
		$teamSizes = Model_Club::find_by_name($club)->getTeamSizes();
		for ($i=0;$i<count($teamSizes);$i++) {
			for ($j=0;$j<$teamSizes[$i];$j++) {
				$teamsAllocation[] = $i+1;
			}
		}

		$lastTeam = null;
		foreach ($result as &$player) {
			if (!$player['team']) {
				if ($teamsAllocation) {
				$lastTeam = array_shift($teamsAllocation);
				}
				$player['team'] = $lastTeam;
			}
		}

		usort($result, function($a, $b) {
			if ($a['team'] == $b['team']) {
				return $a['order'] - $b['order'];
			}
			return $a['team'] - $b['team'];
		});

		return $result;
	}
}
