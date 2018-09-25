<?php
//-----------------------------------------------------------------------------
function cleanName($player, $format = "Fn LN") {

		if (!$player) return $player;

		$zPlayer = $player;
		$a = strpos($player, ",");
		if ($a) {
			$lastname = substr($player, 0, $a);
			$b = strpos($player, "," , $a+1);
			if (!$b) $b = strlen($player);
			$firstname = substr($player, $a+1, $b);
		} else {
			$c = strrpos(unicode_trim($player), " ");
			$lastname = substr($player, $c+1);
			$firstname = substr($player, 0, $c);
		}

		$firstname = trim(preg_replace('/[^A-Za-z ]/', '', $firstname));
		$lastname = trim(preg_replace('/[^A-Za-z ]/', '', $lastname));
		if (!$firstname) return cleanName($lastname);

		switch ($format) {
			case "LN, Fn":
				$player = strtoupper($lastname).", ".ucwords(strtolower($firstname));
				break;
					
			case "Fn LN":
			default:
				$player = ucwords(strtolower($firstname))." ".strtoupper($lastname);
				break;
		}

		$player = trim($player);

		//echo "Clean:$zPlayer->$player ($lastname,$firstname/$a$c)\n";

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

	if ($month < 6) $year = $year - 1;

	return Date::create_from_string($year.".06.01 00:00");
}

//-----------------------------------------------------------------------------
function generatePassword($length) {
	return substr(str_pad(rand(0,pow(10,$length)-1),$length),'0',$length);
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
	$config = file('../../../cards/sites/'.site().'/patterns.ini');

	$patterns = array();
	$replacements = array();
	while (trim(array_shift($config)) != '');
	array_shift($config);
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
		throw new Exception("Cannot match '$str'");
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
	$config = file('../../../cards/sites/'.site().'/patterns.ini');

	$patterns = array();
	$replacements = array();
	array_shift($config);
	foreach ($config as $pattern) {
		if (trim($pattern) == '') break;
		$parts = explode($pattern[0], $pattern);
		if (count($parts) < 3) continue;
		$patterns[] = "/${parts[1]}/i";
		$replacements[] = $parts[2];
	}

	$str = trim(preg_replace($patterns, $replacements, trim($str)));

	if ($str == '!') return null;

	if ($competitions != null && !in_array($str, $competitions)) {
		throw new Exception("Cannot resolve competitionx '$str'");
	}

	return $str;
}

//-----------------------------------------------------------------------------
function loadFile($file) {
	$ext = pathinfo($file['name'], PATHINFO_EXTENSION);

	switch ($ext) {
		case 'xls':
		case 'xlsx':
			$cacheMethod = PHPExcel_CachedObjectStorageFactory::cache_to_phpTemp;
			$cacheSettings = array( 'memoryCacheSize' => '2GB');
			PHPExcel_Settings::setCacheStorageMethod($cacheMethod, $cacheSettings);

			$inputFileType = PHPExcel_IOFactory::identify($file['tmp_name']);
			$reader = PHPExcel_IOFactory::createReader($inputFileType);
			$reader->setReadDataOnly(true);

			$excel = $reader->load($file['tmp_name']);

			$writer = PHPExcel_IOFactory::createWriter($excel, 'CSV');
			$tmpfname = tempnam("../tmp", "xlsx");
			$writer->save($tmpfname);

			$filename = $tmpfname;
			break;

		case 'csv':
		default:
			$filename = $file['tmp_name'];
			break;
	}

	$result = array();

	if (copy($filename, $filename.".xxx")) {
		//echo "Copy success\n";
	} else {
		echo "Copy failed\n";
	}

	//echo "Filename:$filename~$ext\n";
	$data = file_get_contents($filename);
	//echo bin2hex($data);

	$data = str_replace("\r", "\n", $data);
	$data = str_replace(";", ",", $data);

	foreach (explode("\n", $data) as $line) {
		if (trim($line) == "") continue;
		$result[] = preg_replace('/[^A-Za-z0-9,+_@. \/-]/', '', trim($line));
	}

	return $result;
}

//-----------------------------------------------------------------------------
function convertXls($name, $tmpfile) {
		$cacheMethod = PHPExcel_CachedObjectStorageFactory::cache_to_phpTemp;
		$cacheSettings = array( 'memoryCacheSize' => '2GB');
		PHPExcel_Settings::setCacheStorageMethod($cacheMethod, $cacheSettings);

		$inputFileType = PHPExcel_IOFactory::identify($tmpfile);
		$reader = PHPExcel_IOFactory::createReader($inputFileType);
		$reader->setReadDataOnly(true);

		$excel = $reader->load($tmpfile);

		$writer = PHPExcel_IOFactory::createWriter($excel, 'CSV');
		$tmpfname = tempnam("../tmp", "xlsx");
		$writer->save($tmpfname);

		return $tmpfname;
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
function scrape($src) {
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
						if (isset($result['home_score']) && isset($result['away_score'])) $result['played'] = 'yes';
						$fixtures[] = $result;
					}
				}

		}

		return $fixtures;
}

//-----------------------------------------------------------------------------
function enqueue($task) {
	$cacheName = "queue-".Session::get('site');
	$queue = null;
	try {
		$queue = Cache::get($cacheName);
	} catch (\CacheNotFoundException $e) {
		$queue = array();
	}

	$queue[] = $task;

	Cache::set($cacheName, $queue);
}

