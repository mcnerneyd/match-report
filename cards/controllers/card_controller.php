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

			//debug("Fixture ID:".$card['id']." status=".$card['status']." home=".$card['home']['team']." away=".$card['away']['team']." date=".date('Y-m-d', $card['date']));

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
			//else echo "Invalid:".print_r($card['home'], true);
			if ($awayValid) $card['away']['valid'] = true;
			//else echo "Invalid:".print_r($card['away'], true);

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

		debug("Counts:".print_r($counts, true));

		require_once('views/card/index.php');
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
			require_once('views/card/result.php');
			return;
		}

		$club = $_SESSION['club'];

		if (!isset($fixture['card'])) {
			info("Creating new card for ${fixture['id']}");
			$fixture['cardid'] = Card::create($fixture);
			$players = ClubController::getPlayers(date('Y-m-d'), $club, $fixture[$fixture[$club]]['teamnumber']);
			require_once('views/card/fixture.php');
			return;
		}

		$teamcard = $fixture['card'][$fixture[$club]];
		debug("WhoAmI:".print_r($teamcard,true));

		if (isset($teamcard['locked']) or isset($teamcard['closed'])) {
			require_once('views/card/result.php');
			return;
		}

		$lastPlayers = Card::getLastPlayers($club, $teamcard['teamx']) or array();
		$players = ClubController::getPlayers(date('Y-m-d'), $club, $fixture[$fixture[$club]]['teamnumber']);

		info("Card opened");
		
		require_once('views/card/fixture.php');
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

			if (!user('umpire') && user() != $club) {
				throw new LoginException("User is not in this club");
			}
		} else {
			$club = user();
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

		$lockCode = Card::lock($cardid, user());

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
}
?>
