<?php
class Model_Fixture extends \Model
{

	// fixtures cache expires every hour
	private static $cache;

	static function init() {
		Model_Fixture::$cache = Cache::forge("fixtures", array('driver'=>'file','expiration'=>60)); 
	}

	public static function get($fixtureId) {
			foreach (Model_Fixture::getAll() as $fixture) {
				if ($fixture['fixtureID'] == $fixtureId) {
					return $fixture;
				}
			}

			return null;
	}

	public static function getAll($flush = false) {

		Log::debug('Loading fixtures');

		// TODO Skip processing with cksums - only process if source has changed
		if (!$flush) {
			try {
				return Model_Fixture::$cache->get();
			} catch (\CacheNotFoundException $e) {
			}
		}

		Log::info('Fixtures full load');

		Config::load('custom.db', 'config');

		$allfixtures = array();

		$fixture_feed = Config::get("config.fixtures");

		$ct=1;
		foreach (explode("\n", $fixture_feed) as $feed) {

			$feed = trim($feed);

			if (!$feed) continue;

			$fixtures = array();

			try {
			if (preg_match('/.*\.csv/', $feed)) {
				$src = file_get_contents($feed);
				$fixtures = Model_Fixture::load_csv($src);
			} else if (preg_match('/^!.*/', $feed)) {
				$src = file_get_contents(substr($feed, 1));
				//$fixtures = Model_Fixture::load_scrape($src);
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

				$fixtures = json_decode($src, true);
			}

			$allfixtures = array_merge($allfixtures, $fixtures);
			} catch (Exception $e) {
				echo "Failed to scan feed: $feed\n";
			}
		}

		foreach ($allfixtures as $key => $fixture) {
			$k = Model_Fixture::parseCompetition($fixture['competition']);
			if (!$k) {
				unset($allfixtures[$key]); 
				continue;
			}

			if (strpos($fixture['datetime'], '0000') === 0) {
				continue;
			}
			$allfixtures[$key]['datetime'] = Date::create_from_string($fixture['datetime'], 'mysql');
			$allfixtures[$key]['competition'] = $k;
			$k = Model_Fixture::parseClub($fixture['home']);
			if ($k != null) {
				$allfixtures[$key]['home'] = $k['name'];
				$allfixtures[$key]['home_club'] = $k['club'];
				$allfixtures[$key]['home_team'] = $k['team'];
			}
			$k = Model_Fixture::parseClub($fixture['away']);
			if ($k != null) {
				$allfixtures[$key]['away'] = $k['name'];
				$allfixtures[$key]['away_club'] = $k['club'];
				$allfixtures[$key]['away_team'] = $k['team'];
			}
			if (!isset($allfixtures[$key]['played'])) $allfixtures[$key]['played'] = 'no';
		}

		Model_Fixture::$cache->set($allfixtures, 100);
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

	static function load_scrape($feed) {
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
