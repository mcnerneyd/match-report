<?php
class Model_Fixture extends \Model
{

	// fixtures cache expires every hour
	private static $cache;
	private static $webcache;

	static function init() {
		static::$cache = Cache::forge("fixtures", array('driver'=>'file','expiration'=>600)); 
		static::$webcache = Cache::forge("webcache", array('driver'=>'file','expiration'=>3600)); 
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

		Config::load('custom.db', 'config');

		$allfixtures = array();

		$fixture_feed = Config::get("config.fixtures");

		$ct=1;
		$t = microtime(true);
		$pt = $t;
		foreach (explode("\n", $fixture_feed) as $feed) {

			$feed = trim($feed);

			if (!$feed) continue;

			$fixtures = array();

			Log::info("Pulling fixtures $feed");

			try {
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

		foreach ($allfixtures as $key => $fixture) {
			$k = self::parseCompetition($fixture['competition']);
			if (!$k) {
				unset($allfixtures[$key]); 
				continue;
			}

			if (strpos($fixture['datetime'], '0000') === 0) {
				continue;
			}
			$allfixtures[$key]['datetime'] = Date::forge(strtotime($fixture['datetime']));
			$allfixtures[$key]['competition'] = $k;
			$k = self::parseClub($fixture['home']);
			if ($k != null) {
				$allfixtures[$key]['home'] = $k['name'];
				$allfixtures[$key]['home_club'] = $k['club'];
				$allfixtures[$key]['home_team'] = $k['team'];
				$allfixtures[$key]['x'] = $k;
			}
			$k = self::parseClub($fixture['away']);
			if ($k != null) {
				$allfixtures[$key]['away'] = $k['name'];
				$allfixtures[$key]['away_club'] = $k['club'];
				$allfixtures[$key]['away_team'] = $k['team'];
				$allfixtures[$key]['y'] = $k;
			}
			if (!isset($allfixtures[$key]['played'])) $allfixtures[$key]['played'] = 'no';
			$allfixtures[$key]['lastupdated_t'] = time();
		}

		self::$cache->set($allfixtures, 60);		// Refresh fixtures every 60 seconds
		Log::debug('Loading fixtures complete');

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

	//-----------------------------------------------------------------------------
	static function parseClub($str) {
		$config = explode("\n", Config::get("config.pattern_team"));

		$patterns = array();
		$replacements = array();
		foreach ($config as $pattern) {
			if (trim($pattern) == '') break;
			$parts = explode($pattern[0], $pattern);
			if (count($parts) < 3) continue;
			$patterns[] = "/${parts[1]}/i";
			$replacements[] = $parts[2];
		}

		$str = preg_replace($patterns, $replacements, trim($str));

		if ($str == '!') return null;

		$matches = array();
		if (!preg_match('/^([a-z ]*[a-z])(?:\s+([0-9]+))?$/i', trim($str), $matches)) {
			Log::warning("Cannot match '$str'");
			return null;
		}

		$result = array('club'=>$matches[1], 'raw'=>$str);

		if (count($matches) > 2) {
			$result['team'] = $matches[2];
		} else {
			$result['team'] = 1;
		}

		$result['name'] = $result['club'] .' '. $result['team'];

		return $result;
	}

	//-----------------------------------------------------------------------------
	static function parseCompetition($str) {
		$config = explode("\n", Config::get("config.pattern_competition"));

		$patterns = array();
		$replacements = array();
		foreach ($config as $pattern) {
			if (trim($pattern) == '') break;
			$parts = explode($pattern[0], $pattern);
			if (count($parts) < 3) continue;
			$patterns[] = "/${parts[1]}/i";
			$replacements[] = $parts[2];
		}

		$str = trim(preg_replace($patterns, $replacements, trim($str)));

		if ($str == '!') return null;

		return $str;
	}
}

Model_Fixture::init();
