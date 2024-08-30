<?php

function rootUrl()
{
    return (!empty($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . '/';
}

function ensurePath($path, $file = "")
{
    if (!file_exists($path)) {
        mkdir($path, 0777, true);
    }
    return realpath("$path")."/$file";
}

function milliseconds()
{
    $mt = explode(' ', microtime());
    return ((int)$mt[1]) * 1000 + ((int)round($mt[0] * 1000));
}

//-----------------------------------------------------------------------------
function arr_add(&$arr, $subindex, $val)
{
    if (!isset($arr[$subindex])) {
        $arr[$subindex] = array();
    }

    $arr[$subindex][] = $val;
}


//-----------------------------------------------------------------------------
// Returns a default value for empty value
function emptyValue(&$var, $def)
{
    if (empty($var)) {
        return $def;
    }

    return $var;
}

//-----------------------------------------------------------------------------
function cleanName(String $player, String $format = "Fn LN")
{
    if (!$player) {
        return $player;
    }

    if ($format === '') {
        return unicode_trim($player);
    }

    $player = trim(preg_replace("/[^A-Za-z, ]/", "", $player));
    $a = strpos($player, ",");
    if ($a) {
        $lastname = substr($player, 0, $a);
        $b = strpos($player, ",", $a + 1);
        if (!$b) {
            $b = strlen($player);
        }
        $firstname = substr($player, $a + 1, $b);
    } else {
        $c = strrpos($player, " ");
        $lastname = substr($player, $c + 1);
        $firstname = substr($player, 0, $c);
    }

    $firstname = trim(preg_replace('/[^A-Za-z ]/', '', $firstname));
    $lastname = trim(preg_replace('/[^A-Za-z]/', '', $lastname));
    if (!$firstname) {
        return cleanName($lastname);
    }

    switch ($format) {
        case "LN, Fn":
            $player = strtoupper($lastname).", ".ucwords(strtolower($firstname));
            break;

        case "[Fn][LN]":
            return array("Fn" => ucwords(strtolower($firstname)),
                    "LN" => strtoupper($lastname));

        case "Fn LN":
        default:
            $player = ucwords(strtolower($firstname))." ".strtoupper($lastname);
            break;
    }

    $player = trim($player);
    if ($player == ',') {
        $player = "";
    }


    return $player;
}

//-----------------------------------------------------------------------------
function unicode_trim($str)
{
    return preg_replace('/^[\pZ\pC]+|[\pZ\pC]+$/u', '', $str);
}

//-----------------------------------------------------------------------------
function seasonStart($ts)
{
    $year = date('Y', $ts);
    $month = date('n', $ts);

    if ($month < 8) {
        $year = $year - 1;
    }

    return Date::create_from_string($year.".08.01 00:00");
}

function currentSeasonStart()
{
    $css = \Input::param('css', null);
    if ($css == null) {
        $ts = time();
    } else {
        $ts = strtotime($css);
    }

    return seasonStart($ts);
}

//-----------------------------------------------------------------------------
function firstThursday($now = null)
{
    if ($now == null) {
        $now = date('Y-m-d');
    }

    $thisMonth = date('M Y', strtotime($now));
    $startDate = strtotime("first thursday of $thisMonth + 1 day");
    //debug('Date1:'.date('Y-m-d', $startDate)." ".strtotime($now)." B".$startDate);
    if (strtotime($now) < $startDate) {
        $thisMonth = date('M Y', strtotime(date('Y-m-d', $startDate)." - 20 days"));
        $startDate = strtotime("first thursday of $thisMonth + 1 day");
    }

    return $startDate;
}

function rangeEnd($now = null)
{
    $nextThursdayForRange = firstThursday(date('Y-m-d', strtotime($now.' + 3 days')));
    $firstThursdayForRange = firstThursday(date('Y-m-d', $nextThursdayForRange - (24 * 60 * 60)));
    return array($firstThursdayForRange, $nextThursdayForRange);
}

//-----------------------------------------------------------------------------
function strToHex($string)
{
    $hex = '';
    for ($i = 0; $i < strlen($string); $i++) {
        $hex .= dechex(ord($string[$i]));
    }
    return $hex;
}

//-----------------------------------------------------------------------------
function enqueue($command, $timestamp = null)
{
    $fp = fopen("../../../queue", "a");

    $resultTask = null;

    if (flock($fp, LOCK_EX)) {
        $task = array('command-endpoint' => $command);

        if ($timestamp) {
            $task['date'] = $timestamp;
        }

        fputs($fp, json_encode($task)."\n");
    }

    fclose($fp);
}

//-----------------------------------------------------------------------------
function randomString($n, $space = "0123456789abcdef")
{
    $randomString = '';

    for ($i = 0; $i < $n; $i++) {
        $index = rand(0, strlen($space) - 1);
        $randomString .= $space[$index];
    }

    return $randomString;
}
