<?php

class Model_Fixture extends \Model
{
    private static $globalId = 1;

    public static function find($fixtureId)
    {
        return self::get($fixtureId);
    }

    public static function get($fixtureId)
    {
        foreach (Model_Fixture::getAll() as $fixture) {
            if ($fixture['fixtureID'] == $fixtureId and $fixture['status'] == 'active') {
                return $fixture;
            }
        }

        return null;
    }

    public static function match($competition, $homeTeam, $awayTeam)
    {
        foreach (static::getAll() as $fixture) {
            if ($fixture['competition'] != $competition) {
                continue;
            }
            if ($fixture['home'] != $homeTeam) {
                continue;
            }
            if ($fixture['away'] != $awayTeam) {
                continue;
            }

            return $fixture;
        }

        return null;
    }

    public static function getAll($flush = false)
    {
        // TODO Skip processing with cksums - only process if source has changed
        try {
            $fixtures = Cache::get('fixtures');
        } catch (\CacheNotFoundException $e) {
            Log::warning("Fixtures cache does not exist");
            $fixtures = array();
        }

        if ($fixtures && $flush === false) {
            return $fixtures;
        }

        Log::debug("Fixtures cache contains ".count($fixtures)." record(s)");

        if (count($fixtures) == 0) {
            $flush = true;
        }

        try {
            $processing = Cache::get('fixtures.processing');
        } catch (\CacheNotFoundException $e) {
            $processing = false;
        }

        Log::info("processing=$processing");
        if ($processing === true) {
            Log::info("Fixtures already processing");
            return $fixtures;
        }

        Cache::set('fixtures.processing', true, 600);

        $flushFeed = false;
        if ($flush === true) {
            try {			// Flush all downloaded webpages
                Cache::delete('fixtures.source');
                Log::info("Webcache Flushed");
            } catch (\CacheNotFoundException $e) {
            }
        } else {		// If a specific fixture is specified for flush then find it
            if (isset($fixtures[$flush])) {
                $fixture = $fixtures[$flush];
                $flushFeed = $fixture['feed'];
                Log::debug("Flushing feed for fixture $flush: $flushFeed");
            } else {
                Log::warning("Fixture does not exist: $flush");
            }
        }

        Log::info('Fixtures full load');

        $ct=1;
        $t = microtime(true);
        $pt = $t;


        $allfixtures = array();

        foreach (Model_Section::find('all') as $section) {
            $allfixtures = array_merge($allfixtures, self::getFixtures($section, $flushFeed));
            gc_collect_cycles();
        }

        $et = microtime(true);
        Log::info("Loaded ".count($fixtures)." fixtures: ".(($pt-$t)*1000)."/".(($et-$t)*1000));

        file_put_contents(DATAPATH.'/fixtures.json', json_encode($allfixtures));

        Cache::set('fixtures', $allfixtures, 600);
        try {
            Cache::delete('fixtures.processing');
        } catch (\CacheNotFoundException $e) {
        }

    return $allfixtures;
    }

  public static function getFixtures($section, $flushFeed = null)
  {
      $allfixtures = array();

      loadSectionConfig($section['name']);

      // $configFile = DATAPATH."/sections/${section['name']}/config.json";

      // if (!file_exists($configFile)) return array();

      // Config::load($configFile, "section"); //$section['name']);

      // Log::info("Conf: $configFile ".print_r(Config::get("section.pattern.competition", array()), true));

      try {
          $srcs = Cache::get('fixtures.source');
          if ($flushFeed) {
              unset($srcs[$flushFeed]);
          } else {
              array_shift($srcs);
          }
      } catch (\CacheNotFoundException $e) {
          $srcs = array();
      }

      foreach (Config::get("section.fixtures", array()) as $feed) {
          $feed = trim($feed);

          if (!$feed) {
              continue;
          }

          $fixtures = array();

          Log::info("Pulling fixtures $feed");

          try {
              $matches = array();

              // Remove ^ fixtures
              if (preg_match('/^\^([0-9]+)(?:-([0-9]+))?/', $feed, $matches)) {
                  $start = intval($matches[1]);
                  $end = count($matches) > 2 ? intval($matches[2]) : $start;

                  for ($i = $start; $i <= $end; $i++) {
                      Log::info("Remove fixture $i");
                      unset($allfixtures[$i]);
                  }

                  continue;
              }

              if (preg_match('/.*\.csv/', $feed)) {
                  $src = file_get_contents($feed);
                  $pt=microtime(true);
                  $fixtures = self::load_csv($src);
              } elseif (preg_match('/^!.*/', $feed)) {
                  if (isset($srcs[$feed])) {
                      $src = $srcs[$feed];
                  } else {
                      Log::info("Fetching feed: $feed");
                      $src = file_get_contents(substr($feed, 1));
                      $srcs[$feed] = $src;
                      Cache::set('fixtures.source', $srcs, 3600);
                  }
                  $pt=microtime(true);
                  $fixtures = self::load_scrape($src);
              } elseif (preg_match('/^=.*/', $feed)) {
                  $values = str_getcsv(substr($feed, 1));
                  $fixture = array('datetime'=>$values[0],
                      'competition'=>$values[1],
                      'home'=>$values[2],
                      'away'=>$values[3],
                      'home_score'=>null,
                      'away_score'=>null,
                      );
                  $fixture['fixtureID'] = self::$globalId++;

                  if (count($values) > 4 && is_numeric($values[4])) {
                      $fixture['home_score'] = $values[4];
                  }
                  if (count($values) > 5 && is_numeric($values[5])) {
                      $fixture['away_score'] = $values[5];
                  }

                  $fixtures = array($fixture);
              } else {
                  $src = file_get_contents($feed);
                  $pt=microtime(true);

                  $fixtures = json_decode($src, true);
                  if ($fixtures == null) {
                      Log::warning("Cannot process $feed - response is not Json");
                  }
              }

              foreach ($fixtures as $fixture) {
                  $aFixture = (array)$fixture;
                  $aFixture['feed'] = $feed;
                  //$allfixtures[$fixture['fixtureID']] = (array)$fixture;
                  $allfixtures[$section['name'].":".$aFixture['fixtureID']] = $aFixture;
              }
          } catch (Exception $e) {
              Log::error("Failed to scan feed: $feed, ".($e->getMessage())." @".($e->getTraceAsString()));
          }
      }

      $badFixtures = array();
      $goodCompetition = array();
      $clubs = array_map(
          function ($a) {
          return trim($a['name']);
      },
          Model_Club::find('all')
      );
      $competitions = array_map(
          function ($a) {
          return trim($a['name']);
      },
          Model_Competition::query()->where('section_id', '=', $section['id'])->get()
      );
      Log::debug("Competitions for ${section['name']}: ".implode(",", $competitions));
      foreach ($allfixtures as $key => $fixture) {
          $k = Model_Competition::parse($section['name'], $fixture['competition']);
          if (!$k) {
              $badFixtures[] = $fixture['competition'];
              unset($allfixtures[$key]);
              continue;
          }

          $goodCompetition[] = $fixture['competition'];

          $allfixtures[$key]['id'] = $fixture['fixtureID'];
          $allfixtures[$key]['hidden'] = false;
          if (!in_array($k, $competitions)) {
              $allfixtures[$key]['hidden'] = true;
              $allfixtures[$key]['cover'] = '';
          //echo "Checking ${section['name']} $key .. $k - true\n";
          } else {
              $allfixtures[$key]['cover'] = 'C';
          }

          if (strpos($fixture['datetime'], '0000') === 0) {
              Log::debug("Bad date for $k");
              continue;
          }

          $allfixtures[$key]['datetime'] = Date::forge(strtotime($fixture['datetime']));
          $allfixtures[$key]['zcompetition'] = $fixture['competition'];
          $allfixtures[$key]['competition'] = $k;
          $k = Model_Team::parse($section['name'], $fixture['home']);
          if (!$k) {
              $badFixtures[] = $fixture['home'];
              unset($allfixtures[$key]);
              continue;
          }

          $allfixtures[$key]['zhome'] = $fixture['home'];
          $allfixtures[$key]['home'] = $k['name'];
          $allfixtures[$key]['home_club'] = $k['club'];
          $allfixtures[$key]['home_team'] = $k['team'];
          $allfixtures[$key]['x'] = $k;
          if (in_array($k['club'], $clubs)) {
              $allfixtures[$key]['cover'] .= 'H';
          }

          $k = Model_Team::parse($section['name'], $fixture['away']);
          if (!$k) {
              $badFixtures[] = $fixture['away'];
              unset($allfixtures[$key]);
              continue;
          }

          $allfixtures[$key]['zaway'] = $fixture['away'];
          $allfixtures[$key]['away'] = $k['name'];
          $allfixtures[$key]['away_club'] = $k['club'];
          $allfixtures[$key]['away_team'] = $k['team'];
          $allfixtures[$key]['y'] = $k;
          if (in_array($k['club'], $clubs)) {
              $allfixtures[$key]['cover'] .= 'A';
          }
          if (!isset($allfixtures[$key]['played'])) {
              $allfixtures[$key]['played'] = 'no';
          }
          $allfixtures[$key]['lastupdated_t'] = time();
      }

      if ($badFixtures) {
          Log::warning("Unresolvable fixtures (${section['name']}: ".implode(",", $badFixtures));
          Log::debug("Good (${section['name']}: ".implode(",", $goodCompetition));
      }

      foreach ($allfixtures as &$fixture) {
          $fixture['section'] = $section['name'];
          if (!is_object($fixture['datetime'])) {
              $fixture['datetime'] = Date::time();
          }
          $fixture['datetimeZ'] = $fixture['datetime']->format('iso8601');
          $fixture['status'] = self::getStatus($fixture);
      }

      return array_values($allfixtures);
  }

  private static function getStatus($fixture)
  {
      if ($fixture['hidden']) {
          return "inactive";
      }


      if (isset($fixture['card'])) {
          $card = $fixture['card'];
          if ($card['open'] === 0) {
              return "closed";
          }
          return "open";
      }

      return "active";
  }

    public static function load_csv($src)
    {
        $fixtures = array();
        $headers = null;

        // Suggested headers: datetime,competition,home,away

        foreach (explode("\n", $src) as $line) {
            if ($headers == null) {
                $headers = str_getcsv($line);
                Log::debug("CSV header:".print_r($headers, true));
                continue;
            }

            $object = new stdClass();

            $items = str_getcsv($line);

            if (count($items) != count($headers)) {
                continue;
            }

            $object->played = 'No';
            $object->home_score = 0;
            $object->away_score = 0;

            foreach (array_combine($headers, $items) as $key=>$value) {
                $object->$key = $value;
            }

            $fixtures[] = $object;
        }

        return $fixtures;
    }

    public static function load_scrape($src)
    {
        $fixtures = array();

        foreach (scrape($src) as $line) {
            $object = new stdClass();

            $object->played = 'No';
            $object->home_score = 0;
            $object->away_score = 0;

            foreach ($line as $key=>$value) {
                $object->$key = $value;
            }

            $fixtures[] = $object;
        }

        return $fixtures;
    }
}
