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

	public function get_testtask() {
		echo "Execute test task";

		$t = new \Fuel\Task\TestTask();
		
		return new Response("", 200);
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
	public function action_log() {
		$date = \Input::param('d', Date::forge()->format("%Y%m%d"));

		$filename = APPPATH."/logs/".substr($date, 0, 4)."/".substr($date,4,2)."/".substr($date,6,2).".php";
		$fp = fopen($filename, 'r');
		fseek($fp, -512000, SEEK_END);
		$src = fread($fp, 512000);

		echo "<head><title>Log</title></head>";
		echo "<a href='".Uri::create('Admin/Log?d='.Date::forge(strtotime("-1 day", strtotime($date)))->format("%Y%m%d"))."'>Previous</a><br>";

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
					$match2 = array();
					if (preg_match("/Fuel.Core.Request::__construct - Creating a new main Request with URI = \"(.*)\"/", $match[3], $match2)) {
						$squeak = substr("----- ".$match2[1]." ".str_repeat("-", 120),0,120);
						echo "<code style='color:green'>".$match[2]." $squeak </code><br>";
						break;
					}

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

		foreach (Model_Registration::find_all_db() as $reg) {
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

	public function get_refresh() {

		$flush = false;

		if (Input::param('flush')) {
			$flush = Input::param('flush');
		}

		$fixtures = Model_Fixture::getAll($flush);
		$competitionTeams = array();
		$clubTeams = array();
		$teamLookup = array();

		foreach ($fixtures as $fixture) {
			
			self::treeset($competitionTeams, $fixture['competition'], $fixture['home']);					 
			self::treeset($competitionTeams, $fixture['competition'], $fixture['away']);					 
			
			self::treeset($clubTeams, $fixture['home_club'], $fixture['home']);					 
			self::treeset($clubTeams, $fixture['away_club'], $fixture['away']);					 

			$teamLookup[$fixture['home']] = array('club'=>$fixture['home_club'],'team'=>$fixture['home_team']);
			$teamLookup[$fixture['away']] = array('club'=>$fixture['away_club'],'team'=>$fixture['away_team']);
		}

		Log::debug("Fixtures loaded and parsed: xt=".count($competitionTeams)." ct=".count($clubTeams)." tl=".count($teamLookup));

		if ($flush=='2') {

			$this->template->title = 'Refresh';
			$this->template->content = "";
			"<pre>".print_r($competitionTeams,true)."\n".print_r($clubTeams,true)."\n".print_r($teamLookup,true)."</pre>";
			return;
		}

		try {
				DB::start_transaction();
				DB::delete('entry')->execute();		// Delete all entrys

				foreach ($teamLookup as $team=>$detail) {
					$t = Model_Team::find_by_name($team);
					if ($t) continue;

					$clubName = $detail['club'];
					$club = Model_Club::find_by_name($clubName);
					if (!$club) {
						continue;
					}
					$t = new Model_Team();
					$t->club = $club;
					$t->team = $detail['team'];
					$t->save();
				}

				Log::debug("Teams updated");

				foreach ($competitionTeams as $competition=>$teams) {
					$comp = Model_Competition::find_by_name($competition);
					if (!$comp) {
						continue;
					}

					foreach ($teams as $team) {
						$findTeam = Model_Team::find_by_name($team);

						if ($findTeam) {
							$comp->team[] = $findTeam;
						}
					}
					$comp->save();
				}

				Log::debug("Competitions updated");

				foreach (Model_Card::query()
					->where('home_id','=',null)
					->or_where('away_id','=',null)->get() as $card) {
					
					foreach ($fixtures as $fixture) {
						if ($fixture['fixtureID'] != $card['fixture_id']) continue;

						if (!$card['home_id']) {
							$t = Model_Team::find_by_name($fixture['home']);
							$card['home_id'] = $t['id'];
						}
						if (!$card['away_id']) {
							$t = Model_Team::find_by_name($fixture['away']);
							$card['away_id'] = $t['id'];
						}
						$card->save();
					}
				}

				DB::commit_transaction();
			} catch (Exception $e) {
				DB::rollback_transaction();

				throw $e;
			}

			Log::debug("Refresh complete");

			$this->template->title = 'Refresh';
			$this->template->content = "";

		//Response::redirect("admin/competitions");
	}

	private static function treeset(&$t, $k, $v) {
		if (!isset($t[$k])) {
			$t[$k] = array();
		}

		if (!in_array($v, $t[$k])) {
			$t[$k][] = $v;
		}
	}

	// ----- Clubs --------------------------------------------------------------
	public function get_clubs() {
		$data['clubs'] = array();
		
		foreach (Model_Club::find('all', 
			array('order_by'=>array('name'=>'asc'))) as $club) {
			$data['clubs'][] = $club;
		}

		$this->template->title = "Clubs";
		$this->template->content = View::forge('admin/clubs', $data);
	}

	public function post_club() {
		$club = new Model_Club();
		$club->name = Input::post('clubname');
		$club->code = Input::post('clubcode');
		$club->save();

		return new Response("Club created", 201);
	}

	public function delete_club() {
		$club = Model_Club::find_by_code(Input::delete('code'));
		$club->delete();

		return new Response("Club deleted", 201);
	}

	// ----- Competitions -------------------------------------------------------
	public function get_competitions() {
		$data['competitions'] = Model_Competition::find('all');

		$this->template->title = "Competitions";
		$this->template->content = View::forge('admin/competitions', $data);
	}

	public function post_competition() {
		$competition = new Model_Competition();
		$competition->name = Input::post('competitionname');
		$competition->code = Input::post('competitioncode');
		$competition->sequence = 0;
		$competition->save();

		return new Response("Competition created", 201);
	}

	public function delete_competition() {
		$competition = Model_Competition::find_by_code(Input::delete('code'));
		$competition->delete();

		return new Response("Competition deleted", 201);
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
		Config::set("config.fine", Input::post("fine"));
		Config::set("config.elevation_password", Input::post("elevation_password"));
		Config::set("config.admin_email", Input::post("admin_email"));
		Config::set("config.strict_comps", Input::post("strict_comps"));
		Config::set("config.fixtures", Input::post("fixtures"));
		Config::set("config.automation_email", Input::post("automation_email"));	
		$pw = Input::post("automation_password", null);
		if ($pw) {
			Config::set("config.automation_password", $pw);
		}
		Config::set("config.automation_allowrequest", Input::post('allow_registration'));
		Config::set("config.allowassignment", Input::post('allow_assignment') == 'on');
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
			"fine"=>Config::get("config.fine", 25),
			"elevation_password"=>Config::get("config.elevation_password"),
			"admin_email"=>Config::get("config.admin_email"),
			"strict_comps"=>Config::get("config.strict_comps"),
			"automation_email"=>Config::get("config.automation_email"),
			"automation_password"=>Config::get("config.automation_password"),
			"automation_allowrequest"=>Config::get("config.automation_allowrequest"),
			"allowassignment"=>Config::get("config.allowassignment"),
			"fixescompetition"=>Config::get("config.pattern_competition"),
			"fixesteam"=>Config::get("config.pattern_team"),
			"fixtures"=>Config::get("config.fixtures"),
			"tasks"=>$tasks,
		));
	}

	private function convertConfig() {
		$site = Session::get('site');
		$path = APPPATH."../../../sites/$site";
		if (!file_exists($path)) mkdir($path);
		$configFile = "$path/config.ini";

		Log::info("Updating cards config file: $configFile");

		$file = fopen($configFile,'w');
		fwrite($file, "[main]\ntitle=".safe(Config::get("config.title")).
			"\nhashtemplate=".safe(Config::get("config.salt"))."\n");

			fwrite($file, "allowassignment=".(Config::get("config.allowassignment")?"yes":"no")."\n");

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
