<?php
class Controller_FixtureApi extends Controller_Rest
{
	public function before() {
//		if (!\Auth::has_access('fixtureapi.*')) throw new HttpNoAccessException;

		parent::before();
	}

	// --------------------------------------------------------------------------
	public function get_index() {
		$page = \Input::param('p', 0);
		$pagesize = \Input::param('s', 10);
		$club = \Session::get('club', null);

		$compCodes = array();
		foreach (Model_Competition::find('all') as $comp) {
			$compCodes[$comp['name']] = $comp['code'];
		}

		$fixtures = Model_Fixture::getAll(false);

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
				$fixture['datetimeZ'] = $fixture['datetime']->format('%Y-%m-%dT%H:%M:%S');
				if ($fixture['datetime']->get_timestamp() < $ts) $fixture['state'] = 'late';
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
}
