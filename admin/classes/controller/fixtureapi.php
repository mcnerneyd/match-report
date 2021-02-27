<?php
class Controller_FixtureApi extends Controller_RestApi
{
	// --------------------------------------------------------------------------
	public function get_index() {
		header('Access-Control-Allow-Origin: *');

		$page = \Input::param('p', 0);
		$pagesize = \Input::param('s', 10);
		$club = \Input::param('c', null);

		if (! $club) {
			$clubId = \Auth::get('club_id');
			$club = Model_Club::find_by_id($clubId);
			$club = $club['name'];
		}

		Log::debug("Fixtures requested: $club/$page size=$pagesize");

		$compCodes = array();
		foreach (Model_Competition::find('all') as $comp) {
			$compCodes[$comp['name']] = $comp['code'];
		}

		$fixtures = Model_Fixture::getAll(false);
		$fixtures = array_filter($fixtures, function($a) { return !$a['hidden']; });
	
		Log::debug("Fixtures loaded");

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

		usort($fixtures, function($a, $b) {
			return $a['datetime']->get_timestamp() - $b['datetime']->get_timestamp();
		});

		// Find the index where past/future fixtures meet
		$ts = Date::time()->get_timestamp();
		$ct=0;
		foreach ($fixtures as $fixture) {
			if ($fixture['datetime']->get_timestamp() > $ts) break;
      $fixture['index'] = $ct;
			$ct++;
		}

    foreach ($fixtures as &$fixture) $fixture['index'] -= $ct;

		$first = \Input::param('i0', null);
		$last = \Input::param('i1', null);

    if ($first != null) {
      $first += $ct;
      $last += $ct;
      if ($first > $last) { $t = $first; $first = $last; $last = $t; }
      if ($first < 0) $first = 0;
      /*if ($last < 0 ) $last = 0;
      if ($first > count($fixtures)) $first = count($fixtures);
      if ($last > count($fixtures)) $last = count($fixtures);*/
      $fixtures = array_slice($fixtures, $first, $last - $first);
      Log::debug("Slicing absolute: $first -- $last (ct=$ct)");
    } else {
      if ($pagesize > 0) {
        $minPage = floor(-$ct / $pagesize);
      } else {
        $minPage = $page;
        $pagesize = count($fixtures);
        $ct = 0;
      }
      Log::debug("Minimum page: $minPage/$pagesize ($ct)");

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
        Log::debug("Slice: $startToReturn-$ctToReturn = ".count($fixtures));
      }
    }

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

		Log::debug("Fixtures ready");

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
