<?php
class Controller_FixtureApi extends Controller_Rest
{
	public function before() {
		// FIXME if (!\Auth::has_access('registration.*')) throw new HttpNoAccessException;

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
				if ($fixture['home_club'] != $club && $fixture['away_club'] != $club) continue;
				$clubFixtures[] = $fixture;
			}
			$fixtures = $clubFixtures;
		}

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
