<?php
ini_set("auto_detect_line_endings", true);
require_once('controllers/club_controller.php'); 

class CardController {


	// ------------------------------------------------------------------------
	public function reset() {
			checkuser('admin');

			$fid = $_REQUEST['fid'];

			Card::clear($fid);

			require_once('views/admin/index.php');
	}

	// ------------------------------------------------------------------------
	public function index() {
		checkuser();
		$club = $_SESSION['club'];

		$cards = array();
		$deadline = strtotime(date('Y-m-d').' 00:00');

		Log::debug("Dateline Time:".$deadline." ".date('Y-m-d H:i:s', $deadline)." Club=$club");

		$teamMap = Club::getTeamMap();

		foreach (Card::fixtures($club) as $card) {

			if (isset($card['card'])) {
				if ($card['card']['open'] < 0) continue;		// Status less than zero is a dead card
			}

			$card['status'] = 'fixture';

			if ($card['date'] < $deadline) {
					$card['status'] = 'incomplete';

					$card['late'] = floor(($deadline - $card['date']) / (24*60*60));
					if (isset($card['card']['missing'])) $card['late'] = true;
					if (isset($card['card']['late'])) $card['warned'] = true;
			}

			if (isset($card['card'])) {
				if ($club) {
					$teamcard = $card['card'][$card[$club]];

					if (isset($teamcard['closed'])) {
						$card['status'] = 'result';
					}
				}

			}

			if (user('umpire') and $card['status'] == 'fixture') {
					if (!isset($card['card']['home']['locked']) and !isset($card['card']['away']['locked'])){
						continue;
					}
			}

			$homeValid = false;
			$awayValid = false;
			foreach ($teamMap as $team) {
				if (!$homeValid and $card['home']['club'] == $team['club'] and $card['home']['teamnumber'] == $team['team']) {
					$homeValid = true;
				}

				if (!$awayValid and $card['away']['club'] == $team['club'] and $card['away']['teamnumber'] == $team['team']) {
					$awayValid = true;
				}
			}

				if ((isset($card['card']['home']['closed']) or !$homeValid) and (isset($card['card']['away']['closed']) or !$awayValid)) {
					$card['status'] = 'result';
					unset($card['late']);
					unset($card['warned']);
				}

			if ($homeValid) $card['home']['valid'] = true;
			if ($awayValid) $card['away']['valid'] = true;

			//echo "<!-- ".print_r($card,true)." -->\n";
			$cards[] = $card;
		}

		$competitions = array_unique(array_map(function($card) {
			 return $card['competition']; 
			}, $cards));

		uasort($cards, 
			function ($a, $b) {
					$order = 1;
					if ($a['status'] == 'result') {
						if ($b['status'] != 'result') return 1;

						$order = -1;
					} else {
						if ($b['status'] == 'result') return -1;
					}

					if ($a['date'] == $b['date']) return 0;

					return $order * (($a['date'] < $b['date']) ? -1 : 1);
			});

		$counts = array_count_values(array_map(function($card) { return $card['status']; }, $cards));
		$clubs = Club::all();

		Log::debug("Cards: ".count($cards));

		require_once('views/card/index.php');
	}

	public function index2() {
		checkuser();

		require_once('views/card/index2.php');
	}

	public function view() {
		if (!user('admin')) return;
		// public static function getPlayers($rclub, $date, $teamNo) {
			$teamSizes = Club::getTeamSizes($_SESSION['club']);
			echo "<pre>";
			print_r($teamSizes);
			echo "</pre>";

			echo "Explicit=".EXPLICIT_TEAMS;

			if (isset($_GET['team'])) {
				if (isset($_REQUEST['t'])) $t = $_REQUEST['t'];
				else $t = time();

				$players = static::getPlayers($_SESSION['club'], date('Y-m-d', $t), $_GET['team']);
				$ct=0;
				echo "<table>";
				foreach ($players as $player=>$teams) {
					echo "<tr>
						<td>". ++$ct . "</td>
						<td>$player</td>
					</tr>";
				}
				echo "</table>";
			} else {
				if (isset($_REQUEST['t'])) $t = $_REQUEST['t'];
				else $t = time();

				$players = static::stage($_SESSION['club'], $t);

				$ct=0;
				echo "<table>";
				foreach ($players as $player) {
					echo "<tr>
						<td>". ++$ct . "</td>
						<td>${player['order']}</td>
						<td>${player['name']}</td>
						<td>${player['status']}</td>
						<td>${player['phone']}</td>
						<td>${player['team']}</td>
						<td>${player['club']}</td>
					</tr>";
				}
				echo "</table>";
			}
	}

	// ------------------------------------------------------------------------
	// Get a matchcard based on its id
	public function get() {
		checkuser();

		pushUrl();

		$id = $_REQUEST['fid'];

		if (!$id) throw new Exception("No fixture specified");

		Log::info("Get card for fixture:$id");

		securekey("card$id");

		$fixture = Card::getFixture($id);

		if (!$fixture) throw new Exception("No such fixture (fid=$id)");

		if (user('umpire')) {
			if (!isset($fixture['card'])) {
				Log::info("Creating new card for ${fixture['id']}");
				Card::create($fixture);
				$fixture = Card::getFixture($id);
			}

			if (isset($_REQUEST['official']) && $_REQUEST['official'] == 'yes') {
				Card::addNote($fixture['card']['id'], user(), 'Official Umpire');
			}

			$players = array();

			Log::debug("Umpire card");
			if (in_array(user(), $fixture['card']['official']) || isset($_REQUEST['official'])) {
				require_once('views/card/matchcard.php');
			} else {
				require_once('views/card/umpire_check.php');
			}

			return;
		}

		$club = $_SESSION['club'];

		if ($club and !isset($fixture['card'])) {
			Log::debug("Creating new card for ${fixture['id']}");
			$fixture['cardid'] = Card::create($fixture);
			$fixture = Card::getFixture($id);
			$teamcard = $fixture['card'][$fixture[$club]];
			$lastPlayers = Card::getLastPlayers($club, $teamcard['teamx']) or array();
			$players = static::getPlayers($club, date('Y-m-d'), $fixture[$fixture[$club]]['teamnumber']);
			require_once('views/card/fixture.php');
			return;
		}

		if (user('admin') && !isset($fixture[$club])) $club = null;

		if ($club) {
			$teamcard = $fixture['card'][$fixture[$club]];
		}

		foreach ($fixture['card']['rycards'] as $rycard) {
			$player = &$fixture['card'][$rycard['side']]['players'][$rycard['player']];
			if (!isset($player['cards'])) $player['cards'] = array();
			$player['cards'][] = array('type'=>$rycard['type'],'detail'=>$rycard['detail']);
		}

		if ((!$club and user('admin')) or isset($teamcard['locked']) or isset($teamcard['closed'])) {
			Log::debug("Edit/View matchcard ($club): cardid=".$fixture['card']['id']);
			$fixture['card']['away']['suggested-score'] = emptyValue($fixture['card']['home']['oscore'], 0);
			$fixture['card']['home']['suggested-score'] = emptyValue($fixture['card']['away']['oscore'], 0);
			if ($club) {
				$players = static::getPlayers($club, date('Y-m-d'), $fixture[$fixture[$club]]['teamnumber']);
				$players = array_keys($players);
				sort($players);
			} else {
				$players = array();
			}
			
			require_once('views/card/matchcard.php');
			return;
		}

		if (isset($fixture[$club])) {
			Log::debug("Last player for: $club ".$teamcard['teamx']);
			$lastPlayers = Card::getLastPlayers($club, $teamcard['teamx']) or array();
			$players = static::getPlayers($club, date('Y-m-d'), $fixture[$fixture[$club]]['teamnumber']);
		} else {
			$lastPlayers = array();
			$players = array();
			Log::warn("Attempting to locate club: $club for fixture=$id");
		}

		require_once('views/card/fixture.php');
	}


	// ------------------------------------------------------------------------
	public function fine() {
		$id = $_REQUEST['fixtureid'];

		$fixture = Card::getFixture($id);

		if (!isset($fixture['card'])) {
			$cardid = Card::create($fixture);
		} else {
			$cardid = $fixture['card']['id'];
		}

		Card::addCardIncident($cardid, 'Missing', checkuser('admin'));	

		redirect('card', 'index');
	}

	// ------------------------------------------------------------------------
	public function note() {
		$id = $_POST['cardid'];
		$msg = $_POST['note'];

		Card::addNote($id, checkuser(), $msg);
	}

	// ------------------------------------------------------------------------
	public function player() {

		try {
			if (isset($_REQUEST['remove'])) {
				Card::removePlayer($_REQUEST['cid'], $_REQUEST['player']);
				info("Removed: ${_REQUEST['player']} from card");
				return;
			}

			$cid = $_REQUEST['cid'];
			$fixture = Card::getFixtureByCardId($cid);

			if (isset($_REQUEST['club'])) {
				$club = $_REQUEST['club'];

				if (!user('admin') && !user('umpire') && $_SESSION['club'] != $club) {
					throw new LoginException("User is not in this club");
				}
			} else {
				$club = $_SESSION['club'];
			}

			$key = "played";

			if (isset($_REQUEST['player'])) {
				$name = $_REQUEST['player'];
			} else if (isset($_REQUEST['ineligible'])) {
				$name = $_REQUEST['ineligible'];
			}

			$name = cleanName($name);
			if (!$name) {
				Log::warn("Incident request with no name: ".$key);
				return;
			}

			//$fixture = Card::getFixture($id);
			$date = date('Y-m-d');
			$teamNo = $fixture[$fixture[$club]]['teamnumber'];
			$players = static::getPlayers($club, $date, $teamNo);
			if (!isset($players[$name])) {
					Log::warn("Player is ineligible for club $club, team $teamNo: $name");
					Card::addIncident($cid, $name, $club, 'Ineligible', user());
					$key = 'ineligible';
				}

			//securekey("player$name-$id-$club-$set");

			Card::addIncident($cid, $name, $club, 'Played', user());

			if (isset($_REQUEST['goal'])) {
				Card::addIncident($cid, $name, $club, 'Scored', user(), $_REQUEST['goal']);
				$key = 'goal';
			}

			if (isset($_REQUEST['red'])) {
				Card::addIncident($cid, $name, $club, 'Red Card', user(), $_REQUEST['red']);
				$key = 'red';
			}

			if (isset($_REQUEST['yellow'])) {
				Card::addIncident($cid, $name, $club, 'Yellow Card', user(), $_REQUEST['yellow']);
				$key = 'yellow';
			}

			if (isset($_REQUEST['clearcards'])) {
				Card::searchAndRemoveIncident($cid, $name, $club, 'Yellow Card');
				Card::searchAndRemoveIncident($cid, $name, $club, 'Red Card');
			}

			Log::info("Player: $name=$key ($cid)");
		} catch (Throwable $e) {
			Log::warning("Error adding incident: ".$e->getMessage());
		}

		if ($_SERVER['REQUEST_METHOD'] === 'GET') {
			$this->redirectGet($fixture);
		}
	}

	// ------------------------------------------------------------------------
	public function lock() {
		checkuser();
		$cardid = $_REQUEST['cid'];

		securekey("card$cardid");

		$lockCode = Card::lock($cardid, $_SESSION['club']);

		$this->redirectGet(Card::getFixtureByCardId($cardid));
	}

	public function unlock() {
		checkuser('admin');
		$cardid = $_REQUEST['cid'];
		echo "<pre>Card ID:$cardid\n";
		$fixture = Card::getFixtureByCardId($cardid);

		$home = "home";
		if (isset($_REQUEST['away'])) $home = "away";
		else if (!isset($_REQUEST['home'])) return;

		echo "Team:$home";

		$club = $fixture[$home]['club'];

		Card::unlock($cardid, $club);

		echo "</pre>";

		$this->redirectGet(Card::getFixtureByCardId($cardid));
	}

	public function close() {
		checkuser('admin');

		$fixtureid = $_REQUEST['fid'];
		$fixture = Card::getFixture($fixtureid);

		if (!isset($fixture['card'])) {
			debug("Creating new card for $fixtureid");
			$cardid = Card::create($fixture);
		} else {
			$cardid = $fixture['cardid'];
		}

		Card::commit($cardid, $fixture['home']['club'], '', 0);
		Card::commit($cardid, $fixture['away']['club'], '', 0);
	}

	public function sendResult() {
		$cardid = $_REQUEST['cid'];
		$fixture = Card::getFixtureByCardId($cardid);

		if ($this->sendResultDo($fixture)) {
			$this->redirectGet($fixture);
		}
	}

	private function sendResultDo($fixture) {
		$card = $fixture['card'];

		$fixtureId = $fixture['id'];

		$marker = 'A';
	
		if (isset($card['home']['oscore'])) {
			$homeGoals = $card['home']['score'];
			$awayGoals = $card['home']['oscore'];
			$marker .= "B$homeGoals.$awayGoals";
		}

		if (isset($card['away']['oscore'])) {
			if (!isset($homeGoals)) {
				$homeGoals = $card['away']['oscore'];
				$marker .= "C$homeGoals";
			}
			$awayGoals = $card['away']['score'];
			$marker .= "D$awayGoals";
		}

		$url = "http://admin.sportsmanager.ie/fixtureFeed/push.php?fixtureId=$fixtureId&homeScore=$homeGoals&awayScore=$awayGoals";

		info("Submit:$url =$marker");

		if (isset($_REQUEST['test']) || site() == 'test') {
			return false;
		} else {
			file_get_contents($url);
			return true;
		}
	}

	private function redirectGet($fixture) {
		redirect('card', 'get', "fid=".$fixture['id']."&x=".createsecurekey("card".$fixture['id']));
	}

	public function search() {
		$competitions = Competition::all();
		$clubs = Club::all();
		$teams = Club::getTeamMap();

		require_once('views/card/search.php');
	}

	public function searchAJAX() {
		if (!(isset($_REQUEST['club']) or isset($_REQUEST['competition']))) return "";

		$result = Card::fixtures(isset($_REQUEST['club'])?$_REQUEST['club']:null);

		if (isset($_REQUEST['competition'])) {
			$result = array_filter($result, function($item) { 
				return $item['competition'] == $_REQUEST['competition'];
			});
		}

		echo json_encode($result);
	}

	public function create() {
		$id = $_REQUEST['id'];

		$card = Card::get($id);

		debug('card:'.print_r($card,true));

		$competition = $card['competition'];

		if (user() == $card['home']['club']) {
			$card = $card['home'];
		} else {
			$card = $card['away'];
		}

		require_once('views/card/create.php');
	}

// ------------------------- COPIED FROM FUEL

	public static function getPlayers($rclub, $date, $teamNo) {
		$result = array();

		$history = Club::getPlayerHistorySummary($rclub);
		$players = static::stage($rclub, strtotime($date));

		//echo "<!-- ".print_r($players,true)."-->";

		foreach ($players as $player) {
			if ($player['team'] < $teamNo) continue;

			if (isset($history[$player['name']])) $teams = $history[$player['name']]['teams'];
			else $teams = array();

			$result[$player['name']] = array('teams'=>$teams);
		}

		return $result;
	}

	// combine multiple files based on date range
	private static function stage($club, $currentDate) {
		$initialDate = strtotime("first thursday of " . date("M YY", $currentDate));
		if ($initialDate > $currentDate) {
			$initialDate = strtotime("-1 month", $currentDate);
			$initialDate = strtotime("first thursday of " . date("M YY", $initialDate));
		}
		$initialDate = strtotime("+1 day", $initialDate);

		$result = array();
		$currentNames = array();
		$currentLookup = array();

		$current = static::find_before_date($club, $currentDate);
		foreach ($current as $player) {
			$currentNames[] = $player['name'];
			$currentLookup[$player['name']] = $player;
		}

		$restrictionDate = strtotime(Config::get('config.date.restrict'));

		$order = 0;
		if ($currentDate > $restrictionDate) {
			$initial = static::find_before_date($club, $initialDate, false);
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
		$teamSizes = Club::getTeamSizes($club);
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

		return $result;
	}

	public static function getRoot($club = null, $file = null) {
		$root = "../archive/".site()."/registration";

		if ($club) $root .= "/".strtolower($club);
		if ($file) $root .= "/$file";

		if (!file_exists($root)) {
			mkdir($root,0777,TRUE);
		}

		return $root;
	}

	public static function find_all($club) {
		$result = array();
		$club = strtolower($club);
		$root = self::getRoot($club);
		$seasonStart = currentSeasonStart();

		if (is_dir($root)) {
			$files = glob("$root/*.csv");
			if ($files) {
				foreach ($files as $name) {
					if (!is_file($name)) continue;
					$ts=filemtime($name);
					if ($ts < $seasonStart) continue;
					$fileData = array("club"=>$club,
						"name"=>basename($name),
						"timestamp"=>$ts,
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
				//echo "Match:$match";
			}
		}
		//echo "-->";

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
		$result = array();
		echo "<!-- Using registration: club=$rclub file=$file -->\n";
		$pastHeaders = false;
		$team = null;
		foreach (file($file) as $player) {
			if ($player[0] == '#') continue;
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

			$playerTeam = $team;
			$pt = null;

			if (EXPLICIT_TEAMS) {
				for ($i=count($arr)-1;$i>0;$i--) {
					if ($arr[$i]) {
						$matches = array();
						if (preg_match('/^([0-9]+)(st|nd|rd|th)?$/', $arr[$i], $matches)) {
							$playerTeam = $matches[1];
							$pt = $arr[$i];
						}
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
