<?php
class Model_Fixture extends \Model
{

	// fixtures cache expires every hour
	private static $cache;
	private static $webcache;

	static function init() {
		$path = DATAPATH."/sites/".Session::get('site')."/tmp/cache";
		if (!file_exists($path)) mkdir($path, 0777, true);
		static::$cache = Cache::forge("fixtures", array('file'=>array('path'=>$path),'driver'=>'file','expiration'=>600)); 
		static::$webcache = Cache::forge("webcache", array('file'=>array('path'=>$path),'driver'=>'file','expiration'=>3600)); 
	}

	public static function get($fixtureId) {
			foreach (Model_Fixture::getAll() as $fixture) {
				if ($fixture['fixtureID'] == $fixtureId) {
					return $fixture;
				}
			}

			return null;
	}

	public static function match($competition, $homeTeam, $awayTeam) {
		foreach (static::getAll() as $fixture) {
			if ($fixture['competition'] != $competition) continue;
			if ($fixture['home'] != $homeTeam) continue;
			if ($fixture['away'] != $awayTeam) continue;

			return $fixture;
		}

		return null;
	}

	public static function getAll($flush = false) {

		// TODO Skip processing with cksums - only process if source has changed
		try {
			$fixtures = self::$cache->get();
		} catch (\CacheNotFoundException $e) {
			Log::warning("Fixtures cache does not exist");
			$fixtures = array();
		}

		if ($fixtures && $flush === false) return $fixtures;

		Log::debug("Fixtures cache contains ".count($fixtures)." record(s)");

		$flushFeed = false;
		if ($flush === true) {
			try {			// Flush all downloaded webpages
				Model_Fixture::$webcache->delete();
				Log::info("Webcache Flushed");
			} catch (\CacheNotFoundException $e) { }
		} else {		// If a specific fixture is specified for flush then find it
			if (isset($fixtures[$flush])) {
				$fixture = $fixtures[$flush];
				$flushFeed = $fixture['feed'];
				Log::debug("Flushing feed for fixture $flush: $flushFeed");
			} else {
				Log::warning("Fixture does not exist: $flush");
			}
		}

		try {
				$srcs = Model_Fixture::$webcache->get();
				if ($flushFeed) {
					unset($srcs[$flushFeed]);
				} else {
					array_shift($srcs);
				}
		} catch (\CacheNotFoundException $e) {
				$srcs = array();
	  }

		Log::info('Fixtures full load');

		$allfixtures = array();

		$ct=1;
		$t = microtime(true);
		$pt = $t;
		foreach (Config::get("config.fixtures") as $feed) {

			$feed = trim($feed);

			if (!$feed) continue;

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
				} else if (preg_match('/^!.*/', $feed)) {
					if (isset($srcs[$feed])) {
						$src = $srcs[$feed];
					} else {
						Log::info("Fetching feed: $feed");
						$src = file_get_contents(substr($feed, 1));
						$srcs[$feed] = $src;
						static::$webcache->set($srcs);
					}
					$pt=microtime(true);
					$fixtures = self::load_scrape($src);
				} else if (preg_match('/^=.*/', $feed)) {
					$values = str_getcsv(substr($feed, 1));
					$fixture = array('datetime'=>$values[0],
						'competition'=>$values[1],
						'home'=>$values[2],
						'away'=>$values[3],
						'home_score'=>null,
						'away_score'=>null,
						);
					$fixture['fixtureID'] = $ct++;	

					if (count($values) > 4 && is_numeric($values[4])) $fixture['home_score'] = $values[4];
					if (count($values) > 5 && is_numeric($values[5])) $fixture['away_score'] = $values[5];

					$fixtures = array($fixture);
				} else {
					$src = file_get_contents($feed);
					$pt=microtime(true);

					$fixtures = json_decode($src, true);
				}

				foreach ($fixtures as $fixture) {
					$aFixture = (array)$fixture;
					$aFixture['feed'] = $feed;
					//$allfixtures[$fixture['fixtureID']] = (array)$fixture;
					$allfixtures[$aFixture['fixtureID']] = $aFixture;
				}

				$et = microtime(true);
				Log::info("Loaded ".count($fixtures)." fixtures: ".(($pt-$t)*1000)."/".(($et-$t)*1000));
			} catch (Exception $e) {
				Log::error("Failed to scan feed: $feed, ".($e->getTraceAsString()));
			}
		}

		$badFixtures = array();
		$clubs = array_map(function($a) { return trim($a['name']); },
			Model_Club::find('all'));
		$competitions = array_map(function($a) { return trim($a['name']); },
			Model_Competition::find('all'));
		Log::debug("Competitions: ".implode(",", $competitions));
		foreach ($allfixtures as $key => $fixture) {
			$k = Model_Competition::parse($fixture['competition']);
			if (!$k) {
				$badFixtures[] = $fixture['competition'];
				unset($allfixtures[$key]); 
				continue;
			}

			$allfixtures[$key]['hidden'] = false;
			if (!in_array($k, $competitions)) {
				$allfixtures[$key]['hidden'] = true;
				$allfixtures[$key]['cover'] = '';
			} else {
				$allfixtures[$key]['cover'] = 'C';
			}

			if (strpos($fixture['datetime'], '0000') === 0) {
				Log::debug("Bad date for $k");
				continue;
			}


			$allfixtures[$key]['datetime'] = Date::forge(strtotime($fixture['datetime']));
			$allfixtures[$key]['competition'] = $k;
			$k = Model_Club::parse($fixture['home']);
			if ($k != null) {
				$allfixtures[$key]['home'] = $k['name'];
				$allfixtures[$key]['home_club'] = $k['club'];
				$allfixtures[$key]['home_team'] = $k['team'];
				$allfixtures[$key]['x'] = $k;
				if (in_array($k['club'], $clubs)) {
					$allfixtures[$key]['cover'] .= 'H';
				}
			}
			$k = Model_Club::parse($fixture['away']);
			if ($k != null) {
				$allfixtures[$key]['away'] = $k['name'];
				$allfixtures[$key]['away_club'] = $k['club'];
				$allfixtures[$key]['away_team'] = $k['team'];
				$allfixtures[$key]['y'] = $k;
				if (in_array($k['club'], $clubs)) {
					$allfixtures[$key]['cover'] .= 'A';
				}
			}
			if (!isset($allfixtures[$key]['played'])) $allfixtures[$key]['played'] = 'no';
			$allfixtures[$key]['lastupdated_t'] = time();
		}

		if ($badFixtures) {
			Log::warning("Unresolvable competitions: ".implode(",", $badFixtures));
		}

		foreach ($allfixtures as &$fixture) {
			if (!is_object($fixture['datetime'])) {
				$fixture['datetime'] = Date::time();
			}
			if (!isset($fixture['cover'])) $fixture['cover'] = '';
		}

		self::$cache->set($allfixtures, 60);		// Refresh fixtures every 60 seconds
		Log::debug('Loading fixtures complete');

		/*foreach ($allfixtures as $key => $fixture) {
			if (!is_object($fixture['datetime'])) {
				LOG::error("Bad fixture: $key");
				unset($allfixtures[$key]);
			}
		}*/

		return $allfixtures;
	}

	static function load_csv($src) {
		$fixtures = array();
		$headers = null;

		// Suggested headers: datetime,competition,home,away

		foreach (explode("\n", $src) as $line) {
			if ($headers == null) {
				$headers = str_getcsv($line);
				debug("CSV header:".print_r($headers, true));
				continue;
			}

			$object = new stdClass();

			$items = str_getcsv($line);

			if (count($items) != count($headers)) continue;

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

	static function load_scrape($src) {

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

Model_Fixture::init();
