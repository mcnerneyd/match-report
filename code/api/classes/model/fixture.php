<?php

class Model_Fixture extends \Model
{
    private static $globalId = 1;

    public static function get($fixtureId)
    {
        $fixtures = array_filter(static::getAll(), function ($f) use ($fixtureId) {
            return $f['fixtureID'] == $fixtureId && $f['status'] == 'active';
        });
        if (!$fixtures) {
            $fixtures = array();
        }
        return array_pop($fixtures);
    }

    public static function refresh()
    {
        return self::getAllFixtures(true);
    }

    public static function getAll()
    {
        return self::getAllFixtures(false);
    }

    public static function all(?Model_Section $section = null, ?int $modifiedSince = null)
    {
        $fixturesFilename = DATAPATH . '/fixtures.json';

        Log::debug("Get fixtures for $section and $modifiedSince");

        if ($modifiedSince) {
            Log::debug("Modified since = $modifiedSince");
            if ((filemtime($fixturesFilename) <= $modifiedSince) && !Model_Matchcard::expandFixtures(null, $modifiedSince)) {
                return null;
            }
        }

        $fixturesFile = file_get_contents($fixturesFilename);
        $allFixtures = json_decode($fixturesFile, true);
        $allFixtures = array_filter($allFixtures, function ($a) {
            return $a['status'] === 'active'; });
        if ($section != null) {
            $allFixtures = array_values(array_filter(
                $allFixtures,
                function ($f) use ($section) {
                    return $f['section'] == $section['name']; }
            ));
        }

        Log::debug("allFixtures contains " . count($allFixtures) . " fixture(s)");
        return Model_Matchcard::expandFixtures($allFixtures);
    }

    private static function getAllFixtures($flush = false)
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

        Log::debug("Fixtures cache contains " . count($fixtures) . " record(s)");

        if (count($fixtures) == 0) {
            $flush = true;
        }

        $semFile = DATAPATH . "/sem";
        if (file_exists($semFile)) {
            $mtime = filemtime($semFile);
            if (!$mtime || ((time() - $mtime) < 30)) {
                Log::info("Fixtures already processing");
                return $fixtures;
            }
        }

        touch($semFile);

        Log::debug("Fixtures processing");

        try {			// Flush all downloaded webpages
            Cache::delete('fixtures.source');
            Log::info("Webcache Flushed");
        } catch (\CacheNotFoundException $e) {
        }

        $ct = 1;
        $t = microtime(true);
        $pt = $t;

        $allfixtures = array();
        $failureCount = 0;

        foreach (Model_Section::find('all') as $section) {
            $result = self::loadSectionFixtures($section);
            $allfixtures = array_merge($allfixtures, $result['fixtures']);
            $failureCount += $result['failures'];
            gc_collect_cycles();
        }

        $et = microtime(true);

        $rawfixtures = $allfixtures;
        foreach ($rawfixtures as &$fixture) {
            unset($fixture['lastupdated_t']);
            unset($fixture['feed']);
            unset($fixture['x']);
            unset($fixture['y']);
            unset($fixture['zaway']);
            unset($fixture['zhome']);
            unset($fixture['zcompetition']);
            unset($fixture['hidden']);
            unset($fixture['cover']);
            unset($fixture['datetime']);
            unset($fixture['comment']);
            unset($fixture['id']);
            unset($fixture['home']);
            unset($fixture['away']);
        }
        $data = json_encode($rawfixtures, JSON_PRETTY_PRINT);

        if (!file_exists(DATAPATH . '/fixtures.json') || md5($data) != md5_file(DATAPATH . '/fixtures.json')) {
            file_put_contents(DATAPATH . '/fixtures.json', $data);
        }

        Cache::set('fixtures', $allfixtures, 600);

        unlink($semFile);
        Log::info("Fixtures processing complete: Loaded " . count($allfixtures) . " fixtures, $failureCount failures (" . (($pt - $t) * 1000) . "/" . (($et - $t) * 1000) . ")");

        return $allfixtures;
    }

    private static function loadSectionFixtures(Model_Section $section)
    {
        loadSectionConfig($section['name']);

        try {
            $srcs = Cache::get('fixtures.source');
            array_shift($srcs);
        } catch (\CacheNotFoundException $e) {
            $srcs = array();
        }

        $allfixtures = array();
        $failures = 0;

        foreach (Config::get("section.fixtures", array()) as $feed) {
            $feed = trim($feed);

            if (!$feed) {
                continue;
            }

            $fixtures = array();

            Log::debug("Pulling fixtures $feed");

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
                    $pt = microtime(true);
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
                    $pt = microtime(true);
                    $fixtures = self::load_scrape($src);
                } elseif (preg_match('/^=.*/', $feed)) {
                    $values = str_getcsv(substr($feed, 1));
                    $fixture = array(
                        'datetime' => $values[0],
                        'competition' => $values[1],
                        'home' => $values[2],
                        'away' => $values[3],
                        'home_score' => null,
                        'away_score' => null,
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
                    $pt = microtime(true);

                    $fixtures = json_decode($src, true);
                    if ($fixtures == null) {
                        $failures += 1;
                        Log::debug("Cannot process $feed - response is not Json");
                        $fixtures = array();
                    }
                }

                foreach ($fixtures as $fixture) {
                    $aFixture = (array) $fixture;
                    $aFixture['feed'] = $feed;
                    $allfixtures[$section['name'] . ":" . $aFixture['fixtureID']] = $aFixture;
                }
            } catch (Exception $e) {
                Log::error("Failed to scan feed: $feed, " . ($e->getMessage()) . " @" . ($e->getTraceAsString()));
            }
        } // load feeds

        $clubs = array_map(
            function ($a) {
                return trim($a['name']); },
            Model_Club::find('all')
        );
        $competitions = array_unique(array_map(
            function ($a) {
                return trim($a['name']); },
            Model_Competition::query()->where('section_id', '=', $section['id'])->get()
        ));

        $validFixtures = array();
        foreach ($allfixtures as $fixture) {
            if (!self::cleanFixture($section, $fixture, $clubs, $competitions)) {
                $failures += 1;
            } else {
                $validFixtures[] = $fixture;
            }
        }

        Log::debug("Competitions for {$section['name']}: " . implode(",", $competitions) . " - processed " . count($allfixtures) . " fixture(s) with $failures unmatched");

        return array('fixtures' => $validFixtures, 'failures' => $failures);
    }

    public static function cleanFixture(Model_Section $section, array &$fixture, array $clubs, array $competitions): bool
    {
        $fixture['id'] = $fixture['fixtureID'];
        $fixture['section'] = $section->name;
        $fixture['cover'] = '';
        $fixture['zhome'] = $fixture['home'];
        $fixture['zaway'] = $fixture['away'];
        $fixture['zcompetition'] = $fixture['competition'];
        $error = array();

        $k = Model_Competition::parse($fixture['competition']);
        if (!$k) {
            return false;
        }  // excluded competition

        if (!in_array($k, $competitions)) {
            // unknown competition
            $error['competition'] = $k;
        }

        $fixture['cover'] = 'C';
        $fixture['competition'] = $k;

        if (strpos($fixture['datetime'], '0000') === 0) {
            // bad date
            Log::debug("Fixture contains bad date: " . $fixture['datetime']);
            return false;
        }

        $fixture['datetime'] = Date::forge(strtotime($fixture['datetime']), "Europe/Dublin");
        $fixture['datetimeZ'] = $fixture['datetime']->format('iso8601', 'UTC');

        $k = Model_Team::parse($fixture['home']);
        if (!$k) {
            return false;
        } // excluded club

        if (!in_array($k['club'], $clubs)) {
            // unknown club
            $error['teams'] = array($k['raw']);
        } else {
            $fixture['home'] = $k['name'];
            $fixture['home_club'] = $k['club'];
            $fixture['home_team'] = $k['team'];
            $fixture['x'] = $k;
            $fixture['cover'] .= 'H';
        }

        $k = Model_Team::parse($fixture['away']);
        if (!$k) {
            return false;
        } // excluded club

        if (!in_array($k['club'], $clubs)) {
            // unknown club
            if (!isset($error['teams'])) {
                $error['teams'] = array();
            }
            $error['teams'] = array($k['raw']);
        } else {
            $fixture['away'] = $k['name'];
            $fixture['away_club'] = $k['club'];
            $fixture['away_team'] = $k['team'];
            $fixture['y'] = $k;
            $fixture['cover'] .= 'A';
        }

        if (!isset($fixture['played'])) {
            $fixture['played'] = 'no';
        }

        $fixture['lastupdated_t'] = time();
        if (count($error) > 0) {
            $fixture['hidden'] = true;
            $fixture['status'] = 'inactive';
            $fixture['error'] = $error;
        } else {
            $fixture['hidden'] = false;
            $fixture['status'] = self::getStatus($fixture);
        }

        return true;
    }

    private static function getStatus($fixture)
    {
        if (isset($fixture['card'])) {
            $card = $fixture['card'];
            if ($card['open'] === 0) {
                return "closed";
            }
            return "open";
        }

        return "active";
    }

    private static function load_csv($src)
    {
        $fixtures = array();
        $headers = null;

        // Suggested headers: datetime,competition,home,away

        foreach (explode("\n", $src) as $line) {
            if ($headers == null) {
                $headers = str_getcsv($line);
                Log::debug("CSV header:" . print_r($headers, true));
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

            foreach (array_combine($headers, $items) as $key => $value) {
                $object->$key = $value;
            }

            $fixtures[] = $object;
        }

        return $fixtures;
    }

    private static function load_scrape($src)
    {
        $fixtures = array();

        foreach (scrape($src) as $line) {
            $object = new stdClass();

            $object->played = 'No';
            $object->home_score = 0;
            $object->away_score = 0;

            foreach ($line as $key => $value) {
                $object->$key = $value;
            }

            $fixtures[] = $object;
        }

        return $fixtures;
    }

    //-----------------------------------------------------------------------------
    public static function scrape($src, $explain = false)
    {
        libxml_use_internal_errors(true);
        $xml = new DOMDocument();

        $xml->loadHTML($src);
        $xpath = new DOMXPath($xml);

        $competition = null;
        $date = null;
        $fixtures = array();

        foreach ($xml->getElementsByTagName('table') as $table) {

            if (!($table->getAttribute('class') == 'frData league' || $table->getAttribute('class') == 'frData diagram')) {
                continue;
            }

            foreach ($table->childNodes as $child) {

                if ($child->getAttribute('class') == 'competition') {
                    $competition = $child->childNodes->item(0)->nodeValue;
                    continue;
                }
                if ($child->getAttribute('class') == 'date') {
                    $date = str_replace("/", "-", $child->childNodes->item(0)->nodeValue);
                    continue;
                }

                if ($date == null or $competition == null) {
                    continue;
                }

                $result = array("competition" => $competition);

                if (stripos($child->getAttribute('class'), 'item') !== false) {
                    foreach ($child->childNodes as $item) {
                        $key = $item->getAttribute('class');

                        if ($explain) {
                            echo "$key = " . $item->nodeValue . "\n";
                        }

                        if ($key == 'time') {
                            $result['datetime'] = "$date " . $item->nodeValue;
                        }
                        if ($key == 'homeClub') {
                            $result['home'] = $item->nodeValue;
                        }
                        if ($key == 'awayClub') {
                            $result['away'] = $item->nodeValue;
                        }
                        if ($key == 'homeScore') {
                            $result['home_score'] = $item->nodeValue;
                        }
                        if ($key == 'awayScore') {
                            $result['away_score'] = $item->nodeValue;
                        }
                        if ($item->hasChildNodes()) {
                            $fidspan = $item->childNodes->item(0);
                            if ($fidspan->nodeName == 'span' and $fidspan->hasAttribute('fid')) {
                                $result['fixtureID'] = $fidspan->getAttribute('fid');
                            }
                        }
                    }
                }

                if (isset($result['fixtureID'])) {
                    if (isset($result['home_score']) && isset($result['away_score'])) {
                        $result['played'] = 'yes';
                        if ($explain) {
                            echo "Played\n";
                        }
                    }
                    $fixtures[] = $result;
                }
            }
        }

        $fixtureId = 0;
        foreach ($xml->getElementsByTagName('link') as $link) {
            if (!$link->hasAttribute("rel") || $link->getAttribute("rel") != "canonical") {
                continue;
            }
            $matches = array();
            if (preg_match('/https?:\/\/[^\/]*\/[^\/]*\/([0-9]*)\/.*/', $link->getAttribute("href"), $matches)) {
                $fixtureId = $matches[1] * 1000;
                break;
            }
        }

        foreach ($xml->getElementsByTagName('ul') as $elm) {
            $classes = $elm->getAttribute("class");
            $classes = explode(" ", $classes);
            if (!in_array("fixtures", $classes) && !in_array("results", $classes)) {
                continue;
            }

            $result = array();
            $result['competition'] = $elm->getAttribute("data-compname");
            $result['datetime'] = $elm->getAttribute("data-date") . " " . $elm->getAttribute("data-time");
            $result['home'] = $elm->getAttribute("data-hometeam");
            $result['away'] = $elm->getAttribute("data-awayteam");
            $result['home_score'] = $elm->getAttribute("data-homescore");
            $result['away_score'] = $elm->getAttribute("data-awayscore");
            $result['comment'] = $elm->getAttribute("data-comment");

            $result['played'] = ($result['home_score'] != '' && $result['away_score'] != '' ? "yes" : "no");

            $fixtures[] = $result;
        }

        usort($fixtures, function ($a, $b) {
            $rdiff = strcasecmp($a['home'], $b['home']);
            if ($rdiff) {
                return $rdiff;
            }
            return strcasecmp($a['away'], $b['away']);
        });

        foreach ($fixtures as &$fixture) {
            if (!isset($fixture['fixtureID'])) {
                $fixture['fixtureID'] = ++$fixtureId;
            }
        }

        if ($explain) {
            echo "<pre>" . print_r($fixtures, true) . "</pre>\n";
        }

        return $fixtures;
    }
}
