<?php

class Controller_FixtureApi extends Controller_RestApi
{
    // ------------- Working Below -----------------------------
    private static function ts($t0) {
        return (floor(microtime(true)*1000)) - $t0;
    }

    private static function log($frame) {
        echo "Log: ".self::ts()." $frame\n";
    }

    public function get_csv() {

        $date = \Input::headers("Last-Modified", null);

        if ($date) $date = strtotime($date);

        $f = fopen(DATAPATH.'/fixtures.csv', 'r');
        while (($line = fgets($f)) !== FALSE) {
            if ($date) {
                $data = str_getcsv($line);
                if ($data[2] <= $date) continue;
            }

            echo $line;
        }

        fclose($f);
    }
    
    // --------------------------------------------------------------------------
    public function action_index2()
    {
        $section = Input::param('section', null);
        $ts = Input::headers('If-Modified-Since', null);
        $expanded = Input::param('expand', null);

        $t = microtime(true);
        $timing = array("t0" => $t);

        $fixturesFilename = DATAPATH.'/fixtures.json';

        if ($ts) {
            $ts = strtotime($ts);
            if ((filemtime($fixturesFilename) <= $ts) && !Model_Matchcard::expandFixtures(null, $ts)) {
                return new Response("Fixtures unchanged", 304);
            }
        }

        $fixturesFile = file_get_contents($fixturesFilename);
        $allFixtures = json_decode($fixturesFile, true);
        $allFixtures = array_filter($allFixtures, function($a) { return $a['status'] === 'active'; });
        if ($section != null)
            $allFixtures = array_values(array_filter($allFixtures, 
                function ($f) use ($section) { return $f['section'] == $section;}));

        $timing['t1'] = microtime(true);

        $allFixtures = Model_Matchcard::expandFixtures($allFixtures);

        $timing['tf'] = microtime(true);

        $result = json_encode(array(
            'ts'=>$t,
            'timing'=>$timing,
            'fixtures'=> $allFixtures));

        return new Response($result, 200);
    }

    // --------------------------------------------------------------------------
    public function get_index()
    {
        header('Access-Control-Allow-Origin: *');

        $id = $this->param('id');

        $t0 = self::ts(0);

        if ($id) {
            $card = Model_Matchcard::find_by_fixture($id);
            if (!$card) {
                return new Response("No such card: fixture_id=$id", 404);
            }

                $clubId = \Auth::get('club_id');
                $club = Model_Club::find_by_id($clubId);
                if ($club !== null) {
                $club = $club['name'];
                if ($card['home']['club'] == $club or $card['away']['club'] == $club) {
                    $team = $card['home']['club'] == $club ? $card['home']['team'] : $card['away']['team'];
            
                    $card['players'] = array(
                        "club"=>$club,
                        "section"=>$card['section'],
                        "team"=>$team,
                        "values"=>Controller_RegistrationApi::getPlayersWithHistory($card['section'],
                        $club, $team, null));
                }
            }

            return array('data' => $this->simplify($card));
        }

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

        if ($section === null) {
            $sectionId = \Auth::get('section_id');
            if ($sectionId) {
                $section = Model_Section::find_by_id($sectionId);
                $section = $section['name'];
            }
        }

        $t1 = self::ts($t0);
        Log::debug("Fixtures requested: $section/$club/$page size=$pagesize");

        $compCodes = array();
        foreach (Model_Competition::find('all') as $comp) {
            $compCodes[$comp['name']] = $comp['code'];
        }

        $fixtures = Model_Fixture::getAll();
        $fixtures = array_filter(
            $fixtures,
            function ($a) use ($section) {
                return !$a['hidden'] and $a['status'] === 'active' and ($section === null or $a['section'] === $section);
            }
        );

        if ($club) {
            Log::debug("Filtering by club");
            $clubFixtures = array();
            foreach ($fixtures as $fixture) {
                if (Arr::get($fixture, 'home_club', "") == $club || Arr::get($fixture, 'away_club', "") == $club) {
                    $clubFixtures[] = $fixture;
                }
            }
            $fixtures = $clubFixtures;
        }

        if ($section) {
            $fixtures = array_filter($fixtures, function ($a) use ($section) {
                return $a['section'] === $section;
            });
        }

        Log::debug("Fixtures loaded (".count($fixtures).")");
        $t2 = self::ts($t0);

        usort($fixtures, function ($a, $b) {
            return $a['datetime']->get_timestamp() - $b['datetime']->get_timestamp();
        });

        // Find the index where past/future fixtures meet
        $ts = Date::time()->get_timestamp();
        $ct=0;
        foreach ($fixtures as &$fixture) {
            if ($fixture['datetime']->get_timestamp() > $ts) {
                break;
            }
            $ct++;
        }

        $id = -$ct;
        foreach ($fixtures as &$fixture) {
            $fixture['index']=$id++;
        }
        $id = 0;
        foreach ($fixtures as &$fixture) {
            $fixture['index0']=$id++;
        }

        $first = \Input::param('i0', null);
        $last = \Input::param('i1', null);

        if ($first != null) {
            $first += $ct;
            $last += $ct;
            if ($first > $last) {
                $t = $first;
                $first = $last;
                $last = $t;
            }
            if ($first < 0) {
                $first = 0;
            }
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
        $fixtureIds = array();

        foreach ($fixtures as &$fixture) {
            $fixture['competition-code'] = Arr::get($compCodes, $fixture['competition'], "??");
            $fixture['datetimeZ'] = $fixture['datetime']->format('%Y-%m-%dT%H:%M:%S');
            if ($fixture['played'] === 'yes') {
                $fixture['state'] = 'result';
            }

            $fixtureIds[] = $fixture['fixtureID'];
        }

        $t3 = self::ts($t0);

        $scores = Model_Matchcard::getAll($fixtureIds);

        $t4 = self::ts($t0);

        $fixtures = array_map(fn ($f) => array(
            "id"=>$f['fixtureID'],
            "datetimeZ"=>$f['datetime']->format('iso8601'),
            "section"=>$f['section'],
            "competition"=>$f['competition'],
            "competition-code"=>$f['competition-code'],
            "played"=>$f['played'],
            "home"=>array(
              "name"=>$f['home'],
              "club"=>Arr::get($f, 'home_club'),
              "team"=>Arr::get($f, 'home_team'),
              "score"=>$f['home_score'],
              "match_score"=>Arr::get($scores, 'Scored'.$f['fixtureID'].Arr::get($f, 'home_club'), 0),
              "players"=>Arr::get($scores, 'Played'.$f['fixtureID'].Arr::get($f, 'home_club'), 0)
              ),
            "away"=>array(
              "name"=>$f['away'],
              "club"=>Arr::get($f, 'away_club'),
              "team"=>Arr::get($f, 'away_team'),
              "score"=>$f['away_score'],
              "match_score"=>Arr::get($scores, 'Scored'.$f['fixtureID'].Arr::get($f, 'away_club'), 0),
              "players"=>Arr::get($scores, 'Played'.$f['fixtureID'].Arr::get($f, 'away_club'), 0)
              )
            ), $fixtures);

        $result = array('page'=>$page, 'start'=>$start, 'size'=>$size, 'fixtures'=>$fixtures);

        $t5 = self::ts($t0);

        Log::debug("Fixtures ready: ".count($fixtures)." $t1/$t2/$t3/$t4/$t5");

        return $this->response($result);
    }

    public function get_test() {
        self::log("start");
            header('Access-Control-Allow-Origin: *');
    
            $id = $this->param('id');
    
            if ($id) {
                $card = Model_Matchcard::find_by_fixture($id);
                if (!$card) {
                    return new Response("No such card", 404);
                }
    
                return array('data' => $this->simplify($card));
            }
    
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
    
            if ($section === null) {
                $sectionId = \Auth::get('section_id');
                if ($sectionId) {
                    $section = Model_Section::find_by_id($sectionId);
                    $section = $section['name'];
                }
            }
    
            Log::debug("Fixtures requested: $section/$club/$page size=$pagesize");
    
            $compCodes = array();
            foreach (Model_Competition::find('all') as $comp) {
                $compCodes[$comp['name']] = $comp['code'];
            }
    
            $fixtures = Model_Fixture::getAll();
            $fixtures = array_filter(
                $fixtures,
                function ($a) use ($section) {
                    return !$a['hidden'] and $a['status'] === 'active' and ($section === null or $a['section'] === $section);
                }
            );
    
            if ($club) {
                Log::debug("Filtering by club");
                $clubFixtures = array();
                foreach ($fixtures as $fixture) {
                    if (!isset($fixture['home_club'])) {
                        Log::error("Bad fixture: ".print_r($fixture, true));
                        continue;
                    }
                    if (!isset($fixture['away_club'])) {
                        Log::error("Bad fixture: ".print_r($fixture, true));
                        continue;
                    }
                    if ($fixture['home_club'] != $club && $fixture['away_club'] != $club) {
                        continue;
                    }
                    $clubFixtures[] = $fixture;
                }
                $fixtures = $clubFixtures;
            }
    
            if ($section) {
                $fixtures = array_filter($fixtures, function ($a) use ($section) {
                    return $a['section'] === $section;
                });
            }
    
            Log::debug("Fixtures loaded (".count($fixtures).")");
    
            usort($fixtures, function ($a, $b) {
                return $a['datetime']->get_timestamp() - $b['datetime']->get_timestamp();
            });
    
            // Find the index where past/future fixtures meet
            $ts = Date::time()->get_timestamp();
            $ct=0;
            foreach ($fixtures as &$fixture) {
                if ($fixture['datetime']->get_timestamp() > $ts) {
                    break;
                }
                $ct++;
            }
    
            $id = -$ct;
            foreach ($fixtures as &$fixture) {
                $fixture['index']=$id++;
            }
            $id = 0;
            foreach ($fixtures as &$fixture) {
                $fixture['index0']=$id++;
            }
    
            $first = \Input::param('i0', null);
            $last = \Input::param('i1', null);
    
            if ($first != null) {
                $first += $ct;
                $last += $ct;
                if ($first > $last) {
                    $t = $first;
                    $first = $last;
                    $last = $t;
                }
                if ($first < 0) {
                    $first = 0;
                }
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

            self::log("slicing");

            Log::debug("Slicing absolute from:$start size=$size");
            $fixtures = array_slice($fixtures, $start, $size);
            $fixtureIds = array();
    
            foreach ($fixtures as &$fixture) {
                if (isset($compCodes[$fixture['competition']])) {
                    $fixture['competition-code'] = $compCodes[$fixture['competition']];
                } else {
                    $fixture['competition-code'] = '??';
                }
                $fixture['datetimeZ'] = $fixture['datetime']->format('%Y-%m-%dT%H:%M:%S');
                if ($fixture['played'] === 'yes') {
                    $fixture['state'] = 'result';
                }
    
                $fixtureIds[] = $fixture['fixtureID'];
            }
    
            $scores = Model_Matchcard::getAll($fixtureIds);
    
            Log::debug("Fixtures ready: ".count($fixtures));
    
            $fixtures = array_map(fn ($f) => array(
                "id"=>$f['fixtureID'],
                "datetimeZ"=>$f['datetime']->format('iso8601'),
                "section"=>$f['section'],
                "competition"=>$f['competition'],
                "competition-code"=>$f['competition-code'],
                "played"=>$f['played'],
                "home"=>array(
                  "name"=>$f['home'],
                  "club"=>Arr::get($f, 'home_club'),
                  "team"=>Arr::get($f, 'home_team'),
                  "score"=>$f['home_score'],
                  "match_score"=>Arr::get($scores, 'Scored'.$f['fixtureID'].Arr::get($f, 'home_club'), 0),
                  "players"=>Arr::get($scores, 'Played'.$f['fixtureID'].Arr::get($f, 'home_club'), 0)
                  ),
                "away"=>array(
                  "name"=>$f['away'],
                  "club"=>Arr::get($f, 'away_club'),
                  "team"=>Arr::get($f, 'away_team'),
                  "score"=>$f['away_score'],
                  "match_score"=>Arr::get($scores, 'Scored'.$f['fixtureID'].Arr::get($f, 'away_club'), 0),
                  "players"=>Arr::get($scores, 'Played'.$f['fixtureID'].Arr::get($f, 'away_club'), 0)
                  )
                ), $fixtures);
    
            $result = array('page'=>$page, 'start'=>$start, 'size'=>$size, 'fixtures'=>$fixtures);
    
            self::log("end");
            return $this->response($result);
        }
    
    private function parseUsers($users, $section)
    {
        $result = array();
        foreach ($users as $user) {
            if ($user['email'] != null) {
                if ($user['section'] == null || $user['section']['name'] == $section) {
                    $result[] = $user['email'];
                }
            }
        }

        return $result;
    }

  public function get_contact()
  {
      $fixtureId  = \Input::param('id');
      $fixture = Model_Fixture::get($fixtureId);

      if ($fixture === null) {
          return new Response("No such fixture $fixtureId", 404);
      }

      $users = $this->parseUsers(
          array_merge(
              Model_User::query()->related('club')
                ->where('club.name', $fixture['home_club'])->get(),
              Model_User::query()->related('club')
                ->where('club.name', $fixture['away_club'])->get()
          ),
          $fixture['section']
      );

      $cc = array('goodetom@icloud.com');
      if ($fixture['section'] == 'lha-men') {
          $cc[] = 'men@leinsterhockey.ie';
      }
      $result = array('to' => $users,
          'cc'=> $cc,
          'fixture_id' => $fixture['id'],
          'subject' => "{$fixture['zcompetition']}: {$fixture['zhome']} v {$fixture['zaway']} #{$fixture['id']}");

      return new Response(json_encode($result, JSON_PRETTY_PRINT), 200);
  }

    // ----- Internals --------------------------------------------------------

    private function getCardInfo($card, $side)
    {
        $sideX = $card[$side];
        $result = array('signed'=>false, 'locked'=>false);

        if (isset($sideX['signed'])) {
            if ($sideX['signed'] === true) {
                $result['signed'] = true;
            }
        }

        if (isset($sideX['incidents'])) {
            foreach ($sideX['incidents'] as $incident) {
                if ($incident['resolved'] === 1) {
                    continue;
                }
                if ($incident['type'] === 'Locked') {
                    $result['locked'] = true;
                }
            }
        }

        return $result;
    }
}
