<?php
class Controller_FixtureApi extends Controller_RestApi
{
	// --------------------------------------------------------------------------
	public function get_index() {
		header('Access-Control-Allow-Origin: *');

		$page = \Input::param('p', 0);
		$pagesize = \Input::param('n', 10);
		$section = \Input::param('s', null);
		$club = \Input::param('c', null);

		if ($club === null) {
			$clubId = \Auth::get('club_id');
      if ($clubId) {
        $club = Model_Club::find_by_id($clubId);
        $club = $club['name'];
      }
		}

		Log::debug("Fixtures requested: $club/$page size=$pagesize");

		$compCodes = array();
		foreach (Model_Competition::find('all') as $comp) {
			$compCodes[$comp['name']] = $comp['code'];
		}

		$fixtures = Model_Fixture::getAll(false);
		$fixtures = array_filter($fixtures, function($a) { return !$a['hidden']; });
	
		if ($club) {
      Log::debug("Filtering by club");
			$clubFixtures = array();
			foreach ($fixtures as $fixture) {
				if (!isset($fixture['home_club'])) { Log::error("Bad fixture: ".print_r($fixture, true)); continue; }
				if (!isset($fixture['away_club'])) { Log::error("Bad fixture: ".print_r($fixture, true)); continue; }
				if ($fixture['home_club'] != $club && $fixture['away_club'] != $club) continue;
				$clubFixtures[] = $fixture;
			}
			$fixtures = $clubFixtures;
		}

		Log::debug("Fixtures loaded (".count($fixtures).")");

		usort($fixtures, function($a, $b) {
			return $a['datetime']->get_timestamp() - $b['datetime']->get_timestamp();
		});

		// Find the index where past/future fixtures meet
		$ts = Date::time()->get_timestamp();
		$ct=0;
		foreach ($fixtures as &$fixture) {
			if ($fixture['datetime']->get_timestamp() > $ts) break;
			$ct++;
		}

    $id = -$ct;
    foreach ($fixtures as &$fixture) $fixture['index']=$id++;
    $id = 0;
    foreach ($fixtures as &$fixture) $fixture['index0']=$id++;

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
      $start = $first;
      $size = $last - $first + 1;
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
        $start = 0;
        $size = 0;
      } else {
        $size = $pagesize;
        $start = ($page * $pagesize) + $ct;
        if ($start < 0) {
          $size += $start;
          $start = 0;
        }
      }
    }

    Log::debug("Slicing absolute from:$start size=$size");
    $fixtures = array_slice($fixtures, $start, $size);

    foreach ($fixtures as &$fixture) {
      $card = Model_Matchcard::find_by_fixture($fixture['fixtureID']);
      if (!$card) continue;
      if ($card['open'] < 0) {
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

		Log::debug("Fixtures ready: ".count($fixtures));

    $fixtures = array_map(fn($f) => array(
        "id"=>$f['fixtureID'],
        "datetimeZ"=>$f['datetime']->format('iso8601'),
        "section"=>$f['section'],
        "competition"=>$f['competition'],
        "played"=>$f['played'],
        "home"=>array(
          "name"=>$f['home'],
          "club"=>Arr::get($f,'home_club'),
          "team"=>Arr::get($f,'home_team'),
          "score"=>$f['home_score']
          ),
        "away"=>array(
          "name"=>$f['away'],
          "club"=>Arr::get($f,'away_club'),
          "team"=>Arr::get($f,'away_team'),
          "score"=>$f['away_score']
          )
        ), $fixtures);

    $result = array('page'=>$page, 'start'=>$start, 'size'=>$size, 'fixtures'=>$fixtures);

		return $this->response($result);
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
