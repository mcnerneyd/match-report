<?php
class Controller_Check extends Controller_Rest
{
	public function before() {
		if (!\Auth::has_access('check.*')) throw new HttpNoAccessException;

		parent::before();
	}

	// --------------------------------------------------------------------------
	public function action_createcards() {
		//if (!\Auth::has_access('admin.all')) throw new HttpNoAccessException;

		$fines = array();

		// ---- Unstarted Cards -------------------------
		$fixtureIds = array();
		$nowDate = Date::forge();
		foreach (Model_Fixture::getAll() as $fixture) {
			if ($fixture['datetime'] > $nowDate) continue;
			$fixtureIds[] = $fixture['fixtureID'];
		}
		Log::info("Verify ".count($fixtureIds)." fixtures");
		$missing = Model_Matchcard::fixturesWithoutMatchcards($fixtureIds);
		$newCards = false;
		foreach ($missing as $missingCard) {
			if (Model_Matchcard::createMatchcard($missingCard)) {
				echo "[+] Created new card ${missing['fixture_id']}\n";
			}
		}

		echo "[*] Card check complete\n";
	}

	public function action_incompleteCards() {

			// ---- Incomplete Cards ------------------------
			$cards = \Model_Matchcard::incompleteCards(0, 7);

			foreach ($cards as $cardId) {
				$card = \Model_Matchcard::card($cardId['id']);

				$fixture = Model_Fixture::get($card['fixture_id']);

				if (!$fixture) continue;

				if (isset($fixture['comment'])) {
						if (preg_match("/\bPP\b|\bpostpone|not played/i", $fixture['comment'])) continue;
				}

				if (!isset($fixture['datetime']) || !is_object($fixture['datetime'])) {
					echo "[x] Non-object error, cardid=".$cardId['id']."\n";
					continue;
				}

				// Match time is in the future
				if ($fixture['datetime'] > Date::forge()) continue;

				// If the time hasn't been updated they've been fined already...
				$time = (int)$fixture['datetime']->format('%H');
				if ($time < 9) continue;
				// FIXME but if the home team has players and the opposition has none at midnight - fine them
				// FIXME if by midnights, no players are on the card, it's probably postponed

				if ($card['home']['players'] && !isset($card['home']['umpire'])) {
					$this->fine($card, true, "Minimum number of players not on card", 10);
				}

				if (!$card['home']['players'] && !$card['away']['players']) continue;

				if ($card['away']['players'] && !isset($card['away']['umpire'])) {
					$this->fine($card, false, "Minimum number of players not on card", 10);
				}
			}
	}

	public function action_unclosedCards() {

			// ---- Unclosed Cards --------------------------
			foreach (\Model_Matchcard::unclosedMatchcards() as $cardId) {
				$card = \Model_Matchcard::card($cardId['id']);

				$fixture = Model_Fixture::get($card['fixture_id']);

				if (!$fixture) continue;

				if (isset($fixture['comment'])) {
						if (preg_match("/\bPP\b|\bpostpone|not played/i", $fixture['comment'])) continue;
				}

				if (!isset($fixture['datetime']) || !is_object($fixture['datetime'])) {
					echo "[x] Non-object error, cardid=".$cardId['id']."\n";
					continue;
				}

				// Match time is in the future
				if ($fixture['datetime'] > Date::forge()) continue;

				// If the time hasn't been updated they've been fined already...
				$time = (int)$fixture['datetime']->format('%H');
				if ($time < 9) continue;

				if ($card['home']['players'] && !isset($card['home']['umpire'])) {
					$this->fine($card, true, "Card must be submitted by midnight", 10);
				}

				if ($card['away']['players'] && !isset($card['away']['umpire'])) {
					$this->fine($card, false, "Card must be submitted by midnight", 10);
				}
			}
	}

	private function fine($card, $homeTeam, $message, $value) {
		echo "[$] Fined ${card['id']} ".($homeTeam?"Home":"Away").": $message = $value\n";
	}

	private function xfine($card, $clubcard, $cardTime) {
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

			$newfine = new Model_Fine();
			$newfine->competition = $card['competition'];
			$newfine->cardtime = $cardTime;
			$newfine->team = "${clubcard['club']} ${clubcard['team']}";
			$newfine->fixture_id = $card['fixture_id'];
			$newfine->matchcard_id = $card['id'];
			$newfine->club_id = $clubcard['club_id'];
			$newfine->detail = '10:Card Incomplete at Match Time';
			$newfine->type = 'Missing';
			$newfine->message = "$onTimePlayerCount players on card";
			$newfine->resolved = 0;

			return $newfine;
	}
}
