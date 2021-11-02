<?php
function rootUrl() {
	return (!empty($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . '/';
}

//-----------------------------------------------------------------------------
function &arr_get(&$arr, $subindex) {
	if (!isset($arr[$subindex])) $arr[$subindex] = array();

	return $arr[$subindex];
}

function arr_add(&$arr, $subindex, $val) {
	if (!isset($arr[$subindex])) $arr[$subindex] = array();

	$arr[$subindex][] = $val;
}

# Provide 5.5 functionality
function x_array_column($input, $column_key) {
	return array_map(function($v) use ($column_key) { return $v[$column_key]; }, $input);
}

//-----------------------------------------------------------------------------
// Returns a default value for empty value
function emptyValue(&$var, $def) {
	if (empty($var)) return $def;

	return $var;
}

//-----------------------------------------------------------------------------
function cleanName($player, $format = "Fn LN") {

		if (!$player) return $player;

		$player = trim(preg_replace("/[^A-Za-z, ]/", "", $player));
		$a = strpos($player, ",");
		if ($a) {
			$lastname = substr($player, 0, $a);
			$b = strpos($player, "," , $a+1);
			if (!$b) $b = strlen($player);
			$firstname = substr($player, $a+1, $b);
		} else {
			$c = strrpos($player, " ");
			$lastname = substr($player, $c+1);
			$firstname = substr($player, 0, $c);
		}

		$firstname = trim(preg_replace('/[^A-Za-z ]/', '', $firstname));
		$lastname = trim(preg_replace('/[^A-Za-z]/', '', $lastname));
		if (!$firstname) return cleanName($lastname);

		switch ($format) {
			case "LN, Fn":
				$player = strtoupper($lastname).", ".ucwords(strtolower($firstname));
				break;
					
			case "[Fn][LN]":
				return array("Fn"=>ucwords(strtolower($firstname)),
						"LN"=>strtoupper($lastname));

			case "Fn LN":
			default:
				$player = ucwords(strtolower($firstname))." ".strtoupper($lastname);
				break;
		}

		$player = trim($player);
		if ($player == ',') $player = "";


		return $player;
}

//-----------------------------------------------------------------------------
function unicode_trim ($str) {
    return preg_replace('/^[\pZ\pC]+|[\pZ\pC]+$/u','',$str);
}

//-----------------------------------------------------------------------------
function phone($player) {
	$result = "";

	foreach (explode(" ", $player) as $name) {
		if (!$name) continue;
		if ($result) $result .= " ";
		$result .= metaphone($name);
	}

	return $result;
}

//-----------------------------------------------------------------------------
function currentSeasonStart() {
	$year = date('Y');
	$month = date('n');

	if ($month < 8) $year = $year - 1;

	return Date::create_from_string($year.".08.01 00:00");
}

//-----------------------------------------------------------------------------
function generatePassword($length) {
	return substr(str_pad(rand(0,pow(10,$length)-1),$length,'0'), 0, $length);
}

//-----------------------------------------------------------------------------
function firstThursday($now = null) {
	if ($now == null) $now = date('Y-m-d');

	$thisMonth = date('M Y',strtotime($now));
	$startDate = strtotime("first thursday of $thisMonth + 1 day");
	//debug('Date1:'.date('Y-m-d', $startDate)." ".strtotime($now)." B".$startDate);
	if (strtotime($now) < $startDate) {
		$thisMonth = date('M Y', strtotime(date('Y-m-d', $startDate)." - 20 days"));
		$startDate = strtotime("first thursday of $thisMonth + 1 day");
	}

	return $startDate;
}

function nextFirstThursday($now = null) {
	if ($now == null) $now = date('Y-m-d');

	$thisMonth = date('M Y',strtotime($now));
	$startDate = strtotime("first thursday of $thisMonth + 1 day");
	//debug('Date1:'.$now." = ".date('Y-m-d', $startDate)." ".strtotime($now)." B".$startDate);
	if (strtotime($now) >= $startDate) {
		$thisMonth = date('M Y', strtotime(date('Y-m-20', $startDate)." + 1 month"));
		$startDate = strtotime("first thursday of $thisMonth + 1 day");
	}

	return $startDate;
}

function rangeEnd($now = null) {
	$nextThursdayForRange = firstThursday(date('Y-m-d', strtotime($now.' + 3 days')));
	$firstThursdayForRange = firstThursday(date('Y-m-d', $nextThursdayForRange - (24*60*60)));
	return array($firstThursdayForRange, $nextThursdayForRange);
}

//-----------------------------------------------------------------------------
function parse($str) {
/*	$config = Config::get("config.pattern.team", array());
	$patterns = array();
	$replacements = array();
	foreach ($config as $pattern) {
		$parts = explode($pattern[0], $pattern);
		if (count($parts) < 3) continue;
		$patterns[] = "/${parts[1]}/i";
		$replacements[] = $parts[2];
	}

	$str = preg_replace($patterns, $replacements, trim($str));

	if ($str == '!') return null;*/

	$matches = array();
	if (!preg_match('/^([a-z\\/\' ]*[a-z])(?:\s+([0-9]+))?$/i', trim($str), $matches)) {
		throw new Exception("Cannot match team '$str'");
	}

	$result = array('club'=>$matches[1]);

	if (count($matches) > 2) {
		$result['team'] = $matches[2];
	} else {
		$result['team'] = 1;
	}

	$result['name'] = $result['club'] .' '. $result['team'];

	return $result;
}

//-----------------------------------------------------------------------------
function parseCompetition($str, $competitions) {
/*	$config = Config::get("config.pattern.competition", array());
	if (!$config) {
		Log::warn("No competition patterns specified");
	}

	$patterns = array();
	$replacements = array();
	foreach ($config as $pattern) {
		if (trim($pattern) == '') break;
		$parts = explode($pattern[0], $pattern);
		if (count($parts) < 3) continue;
		$patterns[] = "/${parts[1]}/i";
		$replacements[] = $parts[2];
	}

	$newstr = trim(preg_replace($patterns, $replacements, trim($str)));

	if ($newstr == '!') return null;

	if ($competitions != null && !in_array($newstr, $competitions)) {
		echo "<!-- ".print_r($competitions, true)." -->";
		throw new Exception("Cannot resolve competition '$newstr' ('$str')");
	}

	return $newstr;*/

    return $str;
}

//-----------------------------------------------------------------------------
function strToHex($string){
	$hex='';
	for ($i=0; $i < strlen($string); $i++){
		$hex .= dechex(ord($string[$i]));
	}
	return $hex;
}

//-----------------------------------------------------------------------------
function arrayToCSV($arr) {
	// Create a stream opening it with read / write mode
	$stream = fopen('data://text/plain,' . "", 'w+');

	// Iterate over the data, writting each line to the text stream
	foreach ($data as $val) {
		fputcsv($stream, $val);
	}

	// Rewind the stream
	rewind($stream);

	// You can now echo it's content
	$result = stream_get_contents($stream);

	// Close the stream 
	fclose($stream);

	return $result;
}

//-----------------------------------------------------------------------------
function scrape($src, $explain = false) {
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

					if ($date == null or $competition == null) continue;

					$result = array("competition"=>$competition);

					if (stripos($child->getAttribute('class'), 'item') !== false) {
						foreach ($child->childNodes as $item) {
							$key = $item->getAttribute('class');

							if ($explain) echo "$key = ".$item->nodeValue."\n";

							if ($key == 'time') $result['datetime'] = "$date ".$item->nodeValue;
							if ($key == 'homeClub') $result['home'] = $item->nodeValue;
							if ($key == 'awayClub') $result['away'] = $item->nodeValue;
							if ($key == 'homeScore') $result['home_score'] = $item->nodeValue;
							if ($key == 'awayScore') $result['away_score'] = $item->nodeValue;
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
							if ($explain) echo "Played\n";
						}
						$fixtures[] = $result;
					}
				}

		}

		$fixtureId = 0;
		foreach ($xml->getElementsByTagName('link') as $link) {
			if (!$link->hasAttribute("rel") || $link->getAttribute("rel") != "canonical") continue;
			$matches = array();
			if (preg_match('/https?:\/\/[^\/]*\/[^\/]*\/([0-9]*)\/.*/', $link->getAttribute("href"), $matches)) {
				$fixtureId = $matches[1] * 1000;
				break;
			}
		}

		foreach ($xml->getElementsByTagName('ul') as $elm) {
			$classes = $elm->getAttribute("class");
			$classes = explode(" ", $classes);
			if (!in_array("fixtures", $classes) && !in_array("results", $classes)) continue;

			$result = array();
			$result['competition'] = $elm->getAttribute("data-compname");
			$result['datetime'] = $elm->getAttribute("data-date")." ".$elm->getAttribute("data-time");
			$result['home'] = $elm->getAttribute("data-hometeam");
			$result['away'] = $elm->getAttribute("data-awayteam");
			$result['home_score'] = $elm->getAttribute("data-homescore");
			$result['away_score'] = $elm->getAttribute("data-awayscore");
			$result['comment'] = $elm->getAttribute("data-comment");

			$result['played'] = ($result['home_score'] != '' && $result['away_score'] != '' ? "yes" : "no");

			$fixtures[] = $result;
		}

		usort($fixtures, function($a, $b) { 
			$rdiff = strcasecmp($a['home'], $b['home']);
			if ($rdiff) return $rdiff; 
			return strcasecmp($a['away'], $b['away']); 
		});

		foreach ($fixtures as &$fixture) {
			if (!isset($fixture['fixtureID'])) {
				$fixture['fixtureID'] = ++$fixtureId;
			}
		}

		if ($explain) echo "<pre>".print_r($fixtures,true)."</pre>\n";

		return $fixtures;
}

//-----------------------------------------------------------------------------
function enqueue($command, $timestamp=null) {
	$fp = fopen("../../../queue", "a");

	$resultTask = null;

	if (flock($fp, LOCK_EX)) {
		$task = array('command-endpoint'=>$command);
		
		if ($timestamp) $task['date'] = $timestamp;

		fputs($fp, json_encode($task)."\n");			
	}

	fclose($fp);
}

