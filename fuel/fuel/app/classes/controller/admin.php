<?php
class Controller_Admin extends Controller_Hybrid
{
	public function before() {
		if (!\Auth::has_access('admin.all')) throw new HttpNoAccessException;

		parent::before();
	}

	/**
	 * Get the tasks configured on the system
	 */
	public function get_tasks() {
		$tasks = Model_Task::find('all');


		$this->template->title = "Task Processing";
		$this->template->content = View::forge('admin/tasks', array('tasks'=>$tasks));
	}

	/**
	 * Archives all matchcards and incidents from before the current season
	 * start.
	 */
	public function action_archive() {
		$dateStart = currentSeasonStart();

		Model_Player::archive($dateStart, true);
		
		echo $dateStart; 
		return new Response("", 200);
	}

	/**
	 * Display the log from a specific date.
	 *
	 * @param d Date for which to get the log. Defaults to today.
	 */
	public function get_log() {
		$date = \Input::param('d', Date::forge()->format("%Y%m%d"));

		$filename = APPPATH."/logs/".substr($date, 0, 4)."/".substr($date,4,2)."/".substr($date,6,2).".php";
		$fp = fopen($filename, 'r');
		fseek($fp, -512000, SEEK_END);
		$src = fread($fp, 512000);

		echo "<head><title>Log</title></head>";

		foreach (array_reverse(explode("\n", $src)) as $line) {
			$match = array();
			if (!preg_match("/(.*) - (.*) --> (.*)/", $line, $match)) continue;
			switch ($match[1]) {
				case "ERROR":
					echo "<code style='color:red'>".$match[2]." <strong>".$match[1]."</strong> ".$match[3]."</code><br>";
					break;
				case "WARNING":
					echo "<code style='color:orange'>".$match[2]." <strong>".$match[1]."</strong> ".$match[3]."</code><br>";
					break;
				case "DEBUG":
					echo "<code>".$match[2]." <strong>".$match[1]."</strong> <i style='color:#666'>".$match[3]."</i></code><br>";
					break;
				default:
					echo "<code>".$match[2]." <strong>".$match[1]."</strong> ".$match[3]."</code><br>";
					break;
			}
		}
		fclose($fp);

		return new Response("", 200);
	}

	// --------------------------------------------------------------------------
	public function action_info() {
		phpinfo();

		exit(0);
	}

	// --------------------------------------------------------------------------
	public function get_clublist() {
		$clubs = array();

		foreach (Model_Club::find('all') as $club) {
			$clubs[] = $club['name'];
		}

		return $clubs;
	}

	// --------------------------------------------------------------------------
	public function get_registration() {
		$regs = array();
		$clubs = array();

		foreach (Model_Registration::find_all() as $reg) {
			if (!in_array($reg['club'], $clubs)) {
				$reg['head'] = true;
				$clubs[] = $reg['club'];
			} else {
				$reg['head'] = false;
			}
			$delta = array();
			if ($reg['additions'] > 0) $delta[] = $reg['additions']." changes";
			if ($reg['deletions'] > 0) $delta[] = $reg['deletions']." deletions";
			$reg['delta'] = join(", ", $delta);

			$regs[] = $reg;
		}

		$data['registrations'] = $regs;

		$this->template->title = "Registrations";
		$this->template->content = View::forge('admin/registration', $data);
	}

	public function delete_registration() {
		$batchId = Input::param('id');

		if (!isset($batchId)) return new Response("Missing batch", 400);

		if (DB::delete("registration")->where("batch", $batchId)->execute() == 0) {
			return new Reponse("No such batch", 404);
		}

		Log::info("Registration batch deleted: $batchId");
		return new Response("", 200);
	}

	public function post_registration() {
		Log::info("Adding new registration");
		$file = Input::file("registrationfile");

		$data = file($file['tmp_name']);

		Response::redirect("admin/registration");
	}

	// --------------------------------------------------------------------------
	public function get_configfile() {
		$body = ",,";
		$row2 = ",,Team Size";
		$row3 = "Club,Code,Email";
		$entries = array();

		foreach (Model_Competition::find('all', array(
			'order_by'=> array('sequence'=>'asc'),
			'where'=> array(array('sequence','>=','0')),
			)) as $competition) {
			$body .= ",\"${competition['name']}\"";
			$row2 .= ",";
			if ($competition['teamsize']) {
				$row2 .= "\"${competition['teamsize']}";
				if ($competition['teamstars']) $row2 .= "+${competition['teamstars']}";
				$row2 .= "\"";
			}
			$row3 .= ",\"${competition['code']}\"";
			$entries[$competition['name']] = "";
		}

		$body .= "\n$row2\n$row3\n";

		foreach (Model_Club::find('all') as $club) {
			$body .= "\"".$club['name']."\",".$club['code'].",";

			$secretary = null;
			foreach ($club->user as $user) {
				if ($user['username'] == $club['name']) $secretary = $user['email'];
			}

			$body .= $secretary;

			foreach ($entries as $comp=>$val) $entries[$comp] = "";

			foreach ($club['team'] as $team) {
				foreach ($team['competition'] as $comp) {
					if ($entries[$comp['name']]!="") $entries[$comp['name']].=",";
					$entries[$comp['name']].=$team['team'];
				}
			}
			foreach ($entries as $comp=>$team) {
				if ($team != "") $body .= ",\"".$team."\"";
				else $body .= ",";
			}
			$body .= "\n";
		}

		$response = Response::forge($body);

		$response->set_headers(array(
			'Content-Type' 					=> 'text/csv',
			'Content-Disposition'		=> 'attachment; filename="Config.csv"',
		));

		return $response;
	}

	public function post_configfile() {
		$file = Input::file("configfile");

		$data = loadFile($file);

		$compNames = str_getcsv(array_shift($data));

		if ($compNames[0] or $compNames[1]) throw new Exception("Not a valid configuration file");

		$compSizes = str_getcsv(array_shift($data));
		$compCodes = str_getcsv(array_shift($data));

		try {
				DB::start_transaction();
				DB::update('competition')->value('sequence', -1)->execute();

				for ($i=3; $i<count($compNames);$i++) {
						$matches = array();
						$teamSize = null;
						$teamStars = null;

						if (preg_match("/([0-9]+)(?:\+([0-9]+))?/", $compSizes[$i], $matches)) {
								if (count($matches) > 2) $teamStars = $matches[2];
								else $teamStars = 0;
								$teamSize = $matches[1] + $teamStars;
						}

						$comp = Model_Competition::find_by_name($compNames[$i]) ?: 
							Model_Competition::forge(array('name'=>$compNames[$i]));

						$comp->code = $compCodes[$i];
						$comp->teamsize = $teamSize;
						$comp->teamstars = $teamStars;
						$comp->sequence = $i-3;

						$comp->save();
				}

				DB::delete('entry')->execute();		// Delete all entrys

				foreach ($data as $line) {
						$club = str_getcsv($line);

						if (!$club[0]) continue;

						$clubX = Model_Club::find_by_name($club[0]) ?: 
							Model_Club::forge(array('name'=>$club[0]));

						// Not ideal - but best of worst
						$oddClub = Model_Club::find_by_code($club[1]);
						if ($oddClub && $oddClub->name != $club[0]) {
							$oddClub->delete();
						}

						$clubX->code = $club[1];

						$clubUser = Model_User::find_by_username($club[0]);

						if (!$clubUser) {
							Log::info("Creating new user:".$club[0]);
							$clubUser = new Model_User();
							$clubUser->username = $club[0];
							$clubUser->club_id = $clubX->id;
							$clubUser->role = "user";
							$clubUser->password = generatePassword(4);
						} else {
							Log::info("User exists:".$club[0]);
						}

						$clubUser->email = $club[2];
						$clubUser->save();

						$secretary = Model_User::find_by_username($club[2]);
						
						if (!$secretary) {
							Log::info("Creating secretary:".$club[0]);
							$secretary = new Model_User();
							$secretary->username = $club[2];
						}

						$secretary->club_id = $clubX->id;
						$secretary->role = "secretary";
						$secretary->email = $club[2]; 
						$secretary->password = '';
						$secretary->save();

						foreach ($clubX->team as $team) $team->competition = array();

						$entries = array();

						for ($j=3;$j<count($compNames);$j++) {
								if ($club[$j]) {
										$comp = Model_Competition::find_by_name($compNames[$j]);
										foreach (explode(",", $club[$j]) as $entry) {
											$matchTeam = null;
											foreach ($clubX->team as $v) if ($v->team == $entry) { $matchTeam = $v; break; }
											if (!$matchTeam) {
												$matchTeam = Model_Team::forge();
												$matchTeam->team = $entry;
												$matchTeam->competition = array();
												$matchTeam->club = $clubX;
												$clubX->team[] = $matchTeam;
											}
											$matchTeam->save();
											DB::insert('entry')
													->set(array(
														'team_id'=>$matchTeam->id, 
														'competition_id'=>$comp->id))
													->execute();
										}
								}
						}

						$clubX->save();
				}

				DB::commit_transaction();
			} catch (Exception $e) {
				DB::rollback_transaction();

				throw $e;
			}

		Response::redirect("admin/competitions");
	}

	// --------------------------------------------------------------------------
	public function action_clubs() {
		$data['clubs'] = array();
		
		foreach (Model_Club::find('all', 
			array('order_by'=>array('name'=>'asc'))) as $club) {
			foreach ($club->team as $team) {
				if (count($team->competition) > 0) {
					$data['clubs'][] = $club;
					break;
				}
			}
		}

		$this->template->title = "Clubs";
		$this->template->content = View::forge('admin/clubs', $data);
	}

	public function action_competitions() {
		$data['competitions'] = Model_Competition::find('all',
			array(
			'where' => array(
				array('sequence', '>', 0),
				),
			));

		$this->template->title = "Competitions";
		$this->template->content = View::forge('admin/competitions', $data);
	}

	public function action_teams() {
		$this->template->title = "Teams";

		$q = Model_Team::query();

		$club = Input::param('club', null);

		if ($club) {
			$q = $q->related('club')->where('club.code', $club);
		}

		$data['teams'] = $q->get();

		$this->template->content = View::forge('admin/teams', $data);
	}

	public function post_config() {
		Config::load('custom.db', 'config');

		Config::set("config.title", Input::post("title"));
		Config::set("config.salt", Input::post("salt"));
		Config::set("config.elevation_password", Input::post("elevation_password"));
		Config::set("config.admin_email", Input::post("admin_email"));
		Config::set("config.strict_comps", Input::post("strict_comps"));
		Config::set("config.fixtures", Input::post("fixtures"));
		Config::set("config.automation_email", Input::post("automation_email"));	
		$pw = Input::post("automation_password", null);
		if ($pw) {
			Config::set("config.automation_password", $pw);
		}
		Config::set("config.pattern_competition", Input::post("fixescompetition"));
		Config::set("config.pattern_team", Input::post("fixesteam"));
		Config::save('custom.db', 'config');
		Cache::delete_all();
		$this->convertConfig();

		return new Response("", 200);
	}

	public function get_config() {
		Config::load('custom.db', 'config');

		$tasks = Model_Task::find('all');

		$this->template->title = "Configuration";
		$this->template->content = View::forge('admin/config', array(
			"title"=>Config::get("config.title"),
			"salt"=>Config::get("config.salt"),
			"elevation_password"=>Config::get("config.elevation_password"),
			"admin_email"=>Config::get("config.admin_email"),
			"strict_comps"=>Config::get("config.strict_comps"),
			"automation_email"=>Config::get("config.automation_email"),
			"automation_password"=>Config::get("config.automation_password"),
			"automation_allowrequest"=>Config::get("config.automation_allowrequest"),
			"fixescompetition"=>Config::get("config.pattern_competition"),
			"fixesteam"=>Config::get("config.pattern_team"),
			"fixtures"=>Config::get("config.fixtures"),
			"tasks"=>$tasks,
		));
	}

	private function convertConfig() {
		$site = Session::get('site');
		$path = APPPATH."../../../sites/$site";
		$configFile = "$path/config.ini";

		$file = fopen($configFile,'w');
		fwrite($file, "[main]\ntitle=".safe(Config::get("config.title")).
			"\nhashtemplate=".safe(Config::get("config.salt"))."\n");

		foreach (explode(" ", Config::get("config.strict_comps")) as $strictcomp) {
			fwrite($file, "strict[]=$strictcomp\n");
		}
		
		foreach (explode("\n", Config::get('config.fixtures')) as $fixture) 
			if (trim($fixture))
				fwrite($file, "fixturefeed[]=".safe($fixture)."\n");

		fwrite($file, "\n[users]\nadmin.code=".safe(Config::get("config.elevation_password")).
			"\nadmin.email=".safe(Config::get("config.admin_email"))."\n");

		fwrite($file, "\n[automation]\nemail=".safe(Config::get("config.automation_email"))."\npassword=".safe(Config::get("config.automation_password"))."\n");

		fwrite($file, "\n[database]\ndatabase=".safe(Config::get("db.$site.connection.database")).
			"\nusername=".safe(Config::get("db.$site.connection.username")).
			"\npassword=".safe(Config::get("db.$site.connection.password")));

		fclose($file);

		$file = fopen("$path/patterns.ini", "w");
		fwrite($file, "# Competitions\n");
		foreach (explode("\n", trim(Config::get("config.pattern_competition"))) as $pattern)
			if (trim($pattern)) fwrite($file, trim($pattern)."\n");

		fwrite($file, "\n# Clubs\n");
		foreach (explode("\n", trim(Config::get("config.pattern_team"))) as $pattern)
			if (trim($pattern)) fwrite($file, trim($pattern)."\n");

		fclose($file);
	}
}

function safe($a) {
	$a = trim($a);

	return "\"$a\"";
}

function array_column($a, $c) {
	return array_map(function($x) use ($c) { return $x[$c]; }, $a);
}
