<?php
class Controller_RegistrationApi extends Controller_Rest
{
	public function before() {
		// FIXME if (!\Auth::has_access('registration.*')) throw new HttpNoAccessException;

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

	// --------------------------------------------------------------------------
	// NOT USED
	public function post_index() {
		// FIXME Check user admin or matches club
		$access = 'admin.all';
		if (Config::get('config.automation_allowrequest')) {
			$access = 'registration.post';
		}
		
		if (!\Auth::has_access($access)) {
			return new Response("Not permitted to register: $access", 403);
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
	// NOT USED
	//

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
			$errorStatus = Config::get("hockey.block_errors", false) ? "error":"warn";
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
}
