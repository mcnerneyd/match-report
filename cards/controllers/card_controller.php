<?php
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

		debug("Dateline Time:".$deadline." ".date('Y-m-d H:i:s', $deadline)." Club=$club");

		$teamMap = Club::getTeamMap();

		foreach (Card::fixtures($club) as $card) {
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

		require_once('views/card/index.php');
	}

	// ------------------------------------------------------------------------
	// Get a matchcard based on its id
	public function get() {
		checkuser();

		pushUrl();

		$id = $_REQUEST['fid'];

		if (!$id) throw new Exception("No fixture specified");

		securekey("card$id");

		$fixture = Card::getFixture($id);

		if (!$fixture) throw new Exception("No such fixture (fid=$id)");

		if (user('umpire')) {
			require_once('views/card/matchcard.php');
			return;
		}

		$club = $_SESSION['club'];

		if (!isset($fixture['card'])) {
			info("Creating new card for ${fixture['id']}");
			$fixture['cardid'] = Card::create($fixture);
			//$players = ClubController::getPlayers(date('Y-m-d'), $club, $fixture[$fixture[$club]]['teamnumber']);
			$players = CardController::getPlayers($club, date('Y-m-d'), $fixture[$fixture[$club]]['teamnumber']);
			//$players = CardController::getPlayers($club, date('Y-m-d'));
			require_once('views/card/fixture.php');
			return;
		}

		$teamcard = $fixture['card'][$fixture[$club]];

		foreach ($fixture['card']['rycards'] as $rycard) {
			$player = &$fixture['card'][$rycard['side']]['players'][$rycard['player']];
			if (!isset($player['cards'])) $player['cards'] = array();
			$player['cards'][] = array('type'=>$rycard['type'],'detail'=>$rycard['detail']);
		}

		if (isset($teamcard['locked']) or isset($teamcard['closed'])) {
			$fixture['card']['away']['suggested-score'] = emptyValue($fixture['card']['home']['oscore'], 0);
			$fixture['card']['home']['suggested-score'] = emptyValue($fixture['card']['away']['oscore'], 0);
			
			require_once('views/card/matchcard.php');
			return;
		}

		debug("Last player for: $club ".$teamcard['teamx']);
		//echo "Last player for: $club".$teamcard['teamx'];
		$lastPlayers = Card::getLastPlayers($club, $teamcard['teamx']) or array();
		echo "<!-- Last Players:\n ".print_r($lastPlayers, true). "-->";
		//$players = ClubController::getPlayers(date('Y-m-d'), $club, $fixture[$fixture[$club]]['teamnumber']);
		$players = CardController::getPlayers($club, date('Y-m-d'), $fixture[$fixture[$club]]['teamnumber']);
		//$players = CardController::getPlayers($club, date('Y-m-d'));

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
		if (isset($_REQUEST['remove'])) {
			Card::removePlayer($_REQUEST['cid'], $_REQUEST['player']);
			return;
		}

		$cid = $_REQUEST['cid'];
		$fixture = Card::getFixtureByCardId($cid);

		if (isset($_REQUEST['club'])) {
			$club = $_REQUEST['club'];

			if (!user('umpire') && $_SESSION['club'] != $club) {
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

		info("Player: $name=$key");

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

	// ------------------------------------------------------------------------
	/* Moved to fuel Card/Signature
	public function commit() {
		checkuser();
		$cardid = $_REQUEST['cid'];

		securekey("card$cardid");

		$fixture = Card::getFixtureByCardId($cardid);
		$whoami = $fixture[user()];
		$whoareyou = $whoami == 'home' ? 'away' : 'home';

		$umpire = $_REQUEST['umpire'];
		$score = $_REQUEST['score'];

		Card::commit($cardid, user(), $umpire, $score);

		$myTeam = $fixture['card'][$whoami];

		$msg = "The following players were listed for your team: <table style='border:1px solid black;border-collapse:collapse;'> <tr><th colspan='2' style='border:1px solid black;'>${myTeam['team']}</th></tr>";

		foreach ($myTeam['players'] as $player=>$detail) {
			$msg.= "<tr><td style='border:1px solid black;'>";
			if (isset($detail['number'])) $msg .= $detail['number'];
			$msg.= "</td><td style='border:1px solid black;'>".$player."</td></tr>";
		}

		$msg .= "</table>";

		sendClubMessage(user(), "Matchcard for ${myTeam['team']} submitted", $msg);

		if (isset($fixture['card'][$whoareyou]['closed'])) {
			$this->sendResultDo($fixture);
		}

		info("Card committed: $cardid");

		$this->redirectGet($fixture);
	}
	*/

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
		// FIXME Log this: echo "http://admin.sportsmanager.ie/fixtureFeed/push.php?fixtureId=$fixtureId&homeScore=$homeGoals&awayScore=$awayGoals";
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

		$skip = 0;
		foreach (Club::getTeamSizes($rclub) as $index=>$teamSize) {
			if ($index < $teamNo - 1) $skip += $teamSize;
		}
		$history = Club::getPlayerHistorySummary($rclub);
		echo "<!-- Skip:$skip $teamNo \n";
		print_r($history);
		echo "-->";

		foreach (CardController::find_before_date($rclub, strtotime($date)) as $player) {
			if ($skip-->0) continue;

			if (isset($history[$player['name']])) $teams = $history[$player['name']]['teams'];
			else $teams = array();

			$result[$player['name']] = array('teams'=>$teams);
		}

		return $result;
	}

	public static function find_before_date($rclub, $date) {
		echo "<!--\n";
		$match = null;
		$club = strtolower($rclub);
		foreach (CardController::find_all($club) as $registration) {
			echo "Comp:".$registration['timestamp']."=".$date."\n";
			if ($registration['timestamp'] < $date) {
				$match = $registration['name'];
				echo "Match:$match";
			}
		}
		echo "-->";

		$result = array();
		if ($match != null) {
			$file = CardController::getRoot($club, $match);
			$result = CardController::readRegistrationFile($file, $club);
		}

		return $result;
	}

	public static function find_all($club) {
		$result = array();

		$club = strtolower($club);

		$root = CardController::getRoot($club);

		if (is_dir($root)) {
			$files = glob("$root/*.csv");
			if ($files) {
				foreach ($files as $name) {
					$result[] = array("club"=>$club,
						"name"=>basename($name),
						"timestamp"=>filemtime($name),
						"cksum"=>md5_file($name));
				}
			}
		}

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

	/**
	 * Open a file and read a list of players from it. Strips headers
	 * and assigns teams as required.
	 */
	public static function readRegistrationFile($file, $rclub = null) {
		$result = array();
		echo "<!-- readRegistrationFile: club=$rclub file=$file -->\n";
		$team = 1;
		$pastHeaders = false;
		foreach (file($file) as $player) {
			$arr = str_getcsv($player);
			if (!$pastHeaders) {
				if (!$arr[0]) continue;

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
			while (is_numeric($arr[0])) array_shift($arr);

			if (stripos($arr[0], 'team:')) {
				$team = trim(substr($arr[0], 5));
				continue;
			}

			$player = $arr[0];

			if (count($arr) > 1) $player .= ",".$arr[1];
			$player = cleanName($player);

			$playerTeam = $team;

			for ($i=count($arr)-1;$i>0;$i--) {
				if ($arr[$i]) {
					if (is_numeric($arr[$i])) $playerTeam = $arr[$i];
					break;
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
