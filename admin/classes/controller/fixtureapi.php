<?php
class Controller_FixtureApi extends Controller_Rest
{
	// --------------------------------------------------------------------------
	public function get_index() {
		$page = \Input::param('p', 0);
		$pagesize = \Input::param('s', 10);
		$clubId = \Auth::get('club_id');
		$club = Model_Club::find_by_id($clubId);
		$club = $club['name'];

		$compCodes = array();
		foreach (Model_Competition::find('all') as $comp) {
			$compCodes[$comp['name']] = $comp['code'];
		}

		$fixtures = Model_Fixture::getAll(false);
		$fixtures = array_filter($fixtures, function($a) { return !$a['hidden']; });

		if ($club != null) {
			$clubFixtures = array();
			foreach ($fixtures as $fixture) {
				if (!isset($fixture['home_club'])) { Log::error("Bad fixture: ".print_r($fixture, true)); continue; }
				if (!isset($fixture['away_club'])) { Log::error("Bad fixture: ".print_r($fixture, true)); continue; }
				if ($fixture['home_club'] != $club && $fixture['away_club'] != $club) continue;
				$clubFixtures[] = $fixture;
			}
			$fixtures = $clubFixtures;
		}

		/*
		foreach ($fixtures as &$fixture) {
			if (!is_object($fixture['datetime'])) {
				$fixture['datetime'] = Date::time();
			}
		}
		*/

		usort($fixtures, function($a, $b) {
			return $a['datetime']->get_timestamp() - $b['datetime']->get_timestamp();
		});

		$ts = Date::time()->get_timestamp();
		$ct=0;
		foreach ($fixtures as $fixture) {
			if ($fixture['datetime']->get_timestamp() > $ts) break;
			$ct++;
		}

		$minPage = floor(-$ct / $pagesize);
		Log::debug("Page requested: $club)/$page min=$minPage");

		if ($page < $minPage) {
			$fixtures = array();
		} else {
			$ctToReturn = $pagesize;
			$startToReturn = ($page * $pagesize) + $ct;
			if ($startToReturn < 0) {
				$ctToReturn += $startToReturn;
				$startToReturn = 0;
			}
			$fixtures = array_slice($fixtures, $startToReturn, $ctToReturn);

			foreach ($fixtures as &$fixture) {
				$card = Model_Card::find_by_fixture($fixture['fixtureID']);
				if ($card && $card['open'] < 0) {
					$fixture['state'] = 'invalid';
					continue;
				}
				$fixture['home_info'] = $this->getCardInfo($card, 'home');
				$fixture['away_info'] = $this->getCardInfo($card, 'away');
				$us_info = ($card['home']['club'] == $club ? $fixture['home_info'] : $fixture['away_info']);
				if ($us_info['signed'] === true) {
					$fixture['state'] = 'signed';
				} else {
					if ($us_info['locked'] === true) $fixture['state'] = 'locked';
					if ($fixture['datetime']->get_timestamp() < $ts) $fixture['state'] = 'late';
				}
				$fixture['us_info'] = $us_info;
				//$fixture['card'] = $card;
				$fixture['datetimeZ'] = $fixture['datetime']->format('%Y-%m-%dT%H:%M:%S');
				if ($fixture['played'] === 'yes') $fixture['state'] = 'result';
				if (isset($compCodes[$fixture['competition']])) {
					$fixture['competition-code'] = $compCodes[$fixture['competition']];
				} else {
					$fixture['competition-code'] = '??';
				}
			}
		}

		return $this->response($fixtures);
	}

	private function getCardInfo($card, $side) {
		$sideX = $card[$side];
		$result = array('signed'=>false, 'locked'=>false);

		if (isset($sideX['signed'])) {
			if ($sideX['signed'] === true) $result['signed'] = true;
		}

		if (isset($sideX['incidents']))
		foreach ($sideX['incidents'] as $incident) {
			if ($incident['resolved'] === 1) continue;
			if ($incident['type'] === 'Locked') {
				$result['locked'] = true;
			}
		}

		return $result;
	}
}
