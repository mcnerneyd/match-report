<?php
class Controller_RegistrationApi extends Controller_Rest
{
	public function before() {
		if (!\Auth::has_access('admin.all')) throw new HttpNoAccessException;

		parent::before();
	}

	// --------------------------------------------------------------------------
	public function get_list() {
		$date = \Input::param('d', Date::forge()->format("%Y%m%d"));
		$club = \Input::param('c');

		$players=Model_Registration::find_before_date($club, Date::forge()->get_timestamp());
		
		return $this->response($players);
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

		return new Response("Player number set to $number", 200);
	}

	// --------------------------------------------------------------------------
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

		return new Response("Registration Uploaded", 201);
	}

	// --------------------------------------------------------------------------
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
}
