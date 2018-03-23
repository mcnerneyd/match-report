<?php
class Controller_Report extends Controller_Hybrid
{

	// --------------------------------------------------------------------------
	public function action_cards() {
		$cards = Model_Incident::find('all', array(
			'where'=> array(
				array('type','Red Card'),
				'or'=>array('type','Yellow Card'),
			),
			'order_by'=>array('date'=>'desc'),
		));

		$this->template->title = "Red/Yellow Cards";
		$this->template->content = View::forge('report/cards', array(
			'cards'=>$cards
		));
	}

	// --------------------------------------------------------------------------
	public function action_summary() {
		$club = \Input::get('c');
		$club = Model_Club::find_by_name($club);

		if (\Input::get('d')) {
			$dateTo = strtotime(\Input::get('d'));
		} else {
			$dateTo = strtotime(date("Y-m-d")." 00:00");
		}

		$dateFrom = strtotime("-7 days", $dateTo);

		$incidents = Model_Incident::find('all', array(
			'where'=> array(
				array('date','<',date('Y-m-d', $dateTo)),
				array('date','>',date('Y-m-d', $dateFrom)),
				array('club_id','=', $club['id'])
				)
			)
		);

		$cards = array();
		foreach (Model_Card::find('all', array(
			'where'=> array(
				array('date','<',date('Y-m-d', $dateTo)),
				array('date','>',date('Y-m-d', $dateFrom)),
				),
			'order_by' => array('date'=>'asc'),
			)
		) as $card) {
			if ($card['home']['club_id'] == $club['id'] || $card['away']['club_id'] == $club['id']) {
				$cards[] = Model_Card::card($card['id']);
			}
		}

		$fines = array_filter($incidents, function($a) { return $a['type'] == 'Missing'; });
		$scores = array_filter($incidents, function($a) { return $a['type'] == 'Scored'; });

		echo \View::forge("report/summary", array('club'=>$club,
			'date'=>array('from'=>date('Y-m-d', $dateFrom),'to'=>date('Y-m-d', $dateTo)),
			'cards'=>$cards,
			'fines'=>$fines,
			'scores'=>$scores));

		return new Response("Report sent", 200);
	}

	public function action_email() {

		$html = "<style>td { border-bottom: 1px solid black; }</style>
		<table>
			<tr><td>A</td><td>D</td>
		</table>";

		$cssToInlineStyles = new voku\CssToInlineStyles\CssToInlineStyles($html);
		$cssToInlineStyles->setUseInlineStylesBlock(true);
		$html = $cssToInlineStyles->convert();

		return new Response($html);
	}

	public function action_index() {
		$this->template->title = "Reports";
		$this->template->content = View::forge('report/index');
	}

	public function action_games() {
		$dates = Db::query('select distinct date from incident order by date');
	}

	public function get_card() {
		$cardId = $this->param('id');

		if (substr($cardId,0,1) == "n") {
			$card = Model_Card::card(substr($cardId, 1));
			$fixture = Model_Fixture::get($card['fixture_id']);
		} else {
			$card = Model_Card::find_by_fixture($cardId);
			$fixture = Model_Fixture::get($cardId);
		}

		$incidents = Model_Card::incidents($card['id']);

		$html = View::forge('report/card', array('card'=>$card, 'fixture'=>$fixture, 
				'incidents'=>$incidents))->render();
		return new Response($html);
	}

	public function get_scorers() {

		$data['scorers'] = Model_Report::scorers();

		$this->template->title = "Scorers";
		$this->template->content = View::forge('report/scorers', $data);
	}

	public function get_diagnostics() {

		$this->template->title = "Diagnostics";
		$this->template->content = "<pre>Fuel Base: ".Uri::base(false)."\n"
			.\Model_Task::command(array('command'=>"abc"))."\n"
			."SERVER:".print_r($_SERVER,true)."\n\n"
			."REQUEST:".print_r($_REQUEST,true)."</pre>";
	}

	public function get_parsing() {
		$dbComps = array();
		foreach (Model_Competition::find('all') as $comp) $dbComps[] = $comp['name'];
		$dbClubs = array();
		foreach (Model_Club::find('all') as $comp) $dbClubs[] = $comp['name'];

		$teams = array();
		$competitions = array();

		foreach (Model_Fixture::getAll(true) as $fixture) {
			$competitions[$fixture['competition']] = "xx";
			$teams[$fixture['home']] = "xx";
			$teams[$fixture['away']] = "xx";
		}

		foreach ($competitions as $competition=>$x) {
			$comp = Model_Fixture::parseCompetition($competition);
			$competitions[$competition] = array('valid'=>in_array($comp, $dbComps), 'name'=>$comp);
		}

		foreach ($teams as $team=>$x) {
			$tm = Model_Fixture::parseClub($team);
			$tm['valid'] = in_array($tm['club'], $dbClubs);
			$teams[$team] = $tm;
		}

		ksort($competitions);
		ksort($teams);
		$data = array('competitions'=>$competitions,'teams'=>$teams);

		$this->template->title = "Parsing";
		$this->template->content = View::forge('report/parsing', $data);
	}

	public function get_mismatch() {
		$mismatches = array();

		foreach (Model_Fixture::getAll() as $fixture) {
			$card = Model_Card::find_by_fixture($fixture['fixtureID']);
			if (!$card) continue;

			if (($card['home']['goals'] == $fixture['home_score'])
					and ($card['away']['goals'] == $fixture['away_score'])) continue;

			$card['home_score'] = $fixture['home_score'];
			$card['home_team'] = $card['home']['club'].' '.$card['home']['team'];
			$card['away_score'] = $fixture['away_score'];
			$card['away_team'] = $card['away']['club'].' '.$card['away']['team'];

			$mismatches[] = $card;
		}

		$this->template->title = "Mismatch Results";
		$this->template->content = View::forge('report/mismatch', array('mismatches'=>$mismatches));
	}

	// --------------------------------------------------------------------------
	public function action_latecards() {
		//if (!\Auth::has_access('admin.all')) throw new HttpNoAccessException;

		$fines = array();

		try {
			// ---- Unstarted Cards -------------------------
			$fixtureIds = array();
			$nowDate = Date::forge();
			foreach (Model_Fixture::getAll() as $fixture) {
				if ($fixture['datetime'] > $nowDate) continue;
				$fixtureIds[] = $fixture['fixtureID'];
			}
			Log::info("Verify ".count($fixtureIds)." fixtures");
			$missing = Model_Card::fixturesWithoutMatchcards($fixtureIds);
			$newCards = false;
			foreach ($missing as $missingCard) {
				if (Model_Card::createCard($missingCard)) $newCards = true;
			}
			if ($newCards) {
				$this->template->title = "Late/Missing Cards Report";
				$this->template->content = "Processing...";
				return;
			}

			// ---- Incomplete Cards ------------------------
			$cards = \Model_Card::incompleteCards(0, 7);

			foreach ($cards as $cardId) {
				$card = \Model_Card::card($cardId['id']);

				$fixture = Model_Fixture::get($card['fixture_id']);

				if (!$fixture) continue;

				if (isset($fixture['comment'])) {
						if (preg_match("/\bPP\b|\bpostpone|not played/i", $fixture['comment'])) continue;
				}

				if (!isset($fixture['datetime']) || !is_object($fixture['datetime'])) {
					echo "Non-object error, cardid=".$cardId['id']."\n";
					continue;
				}

				// Match time is in the future
				if ($fixture['datetime'] > Date::forge()) continue;

				// If the time hasn't been updated they've been fined already...
				$time = (int)$fixture['datetime']->format('%H');
				if ($time < 9) continue;
				// FIXME but if the home team has players and the opposition has none at midnight - fine them
				// FIXME if by midnights, no players are on the card, it's probably postponed

				$fine = $this->fine($card, $card['home'], $fixture['datetime']->get_timestamp());
				if ($fine) $fines[] = $fine;

				if (!$card['home']['players'] && !$card['away']['players']) continue;

				$fine = $this->fine($card, $card['away'], $fixture['datetime']->get_timestamp());
				if ($fine) $fines[] = $fine;
			}

			// ---- Unclosed Cards --------------------------
			foreach (\Model_Card::unclosedCards() as $cardId) {
				$card = \Model_Card::card($cardId['id']);

				$fixture = Model_Fixture::get($card['fixture_id']);

				if (!$fixture) continue;

				if (isset($fixture['comment'])) {
						if (preg_match("/\bPP\b|\bpostpone|not played/i", $fixture['comment'])) continue;
				}

				if (!isset($fixture['datetime']) || !is_object($fixture['datetime'])) {
					echo "Non-object error, cardid=".$cardId['id']."\n";
					print_r($fixture);
					continue;
				}

				// Match time is in the future
				if ($fixture['datetime'] > Date::forge()) continue;

				// If the time hasn't been updated they've been fined already...
				$time = (int)$fixture['datetime']->format('%H');
				if ($time < 9) continue;

				$fine = $this->fineIncomplete($card, $card['home'], $fixture['datetime']->get_timestamp());
				if ($fine) $fines[] = $fine;

				$fine = $this->fineIncomplete($card, $card['away'], $fixture['datetime']->get_timestamp());
				if ($fine) $fines[] = $fine;
			}

			$cards = array();		// FIXME where card doesn't exist but fixture is expired
				// Ignore fixtures that appear in incompleteCards

			// Remove where club is fined twice
			$finedAlready = array();

			foreach ($fines as $fine) {
				$key = $fine['matchcard_id']."/".$fine['team'];
				if (isset($finedAlready[$key])) {
					continue;
				}
				$finedAlready[$key] = $fine;
			}
			$fines = array_values($finedAlready);

			if (\Input::param("execute")) {
				foreach ($fines as $fine) {
					try {
						echo "Executing fine: ".print_r($fine, true)."<br>";
						$fine->save();
					} catch (Exception $e1) {
						Log::error("Failed to issue fine: ${fine['matchcard_id']}/${fine['team']} ".$e1->getMessage());
					}
				}
			}
		} catch (Exception $e) {
			echo "<pre>".$e->getMessage()."\n".$e->getTraceAsString()."</pre>";
		}

		$this->template->title = "Late/Missing Cards Report";
		$this->template->content = View::forge('report/latecards', array('faults'=>$fines));
	}

	private function fineIncomplete($card, $clubcard, $cardTime) {
			if (!$clubcard['club']) return false;
			if (count($clubcard['fines']) > 0) return false;
			if (isset($clubcard['umpire'])) return false;
			if (!$clubcard['players']) return false;

			Config::load('custom.db', 'config');
			$value = \Config::get('config.fine', 10);

			$newfine = new Model_Fine();
			$newfine->competition = $card['competition'];
			$newfine->cardtime = $cardTime;
			$newfine->team = "${clubcard['club']} ${clubcard['team']}";
			$newfine->fixture_id = $card['fixture_id'];
			$newfine->matchcard_id = $card['id'];
			$newfine->detail = $value.':Card not submitted';
			$newfine->club_id = $clubcard['club_id'];
			$newfine->type = 'Missing';
			$newfine->message = "Card must be submitted by midnight";
			$newfine->resolved = 0;

			return $newfine;
	}

	private function fine($card, $clubcard, $cardTime) {
			if (!$clubcard['club']) return false;
			if (count($clubcard['fines']) > 0) return false;

			// If club has already been fined for this - then skip it
			foreach ($clubcard['fines'] as $fine) {
				if (isset($fine['Missing'])) {
					if (stripos($fine['Missing'], 'Card Incomplete at Match Time')) {
						return false;
					}
				}
			}

			$onTimePlayerCount = 0;
			foreach ($clubcard['players'] as $player) {
				if ($player['date']->get_timestamp() < $cardTime) {
					$onTimePlayerCount++;
				}
			}

			if ($onTimePlayerCount >= 7) return false;
			$fCardTime = date("Y.m.d G:i", $cardTime);

			Config::load('custom.db', 'config');
			$value = \Config::get('config.fine', 10);

			$newfine = new Model_Fine();
			$newfine->competition = $card['competition'];
			$newfine->cardtime = $cardTime;
			$newfine->team = "${clubcard['club']} ${clubcard['team']}";
			$newfine->fixture_id = $card['fixture_id'];
			$newfine->matchcard_id = $card['id'];
			$newfine->club_id = $clubcard['club_id'];
			$newfine->detail = $value.':Card Incomplete at Match Time';
			$newfine->type = 'Missing';
			$newfine->message = "$onTimePlayerCount players on card";
			$newfine->resolved = 0;

			return $newfine;
	}
}
