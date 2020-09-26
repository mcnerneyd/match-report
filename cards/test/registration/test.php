<?php

function debug($s) {
	echo $s."\n";
}

require_once("../../model/player.php");

function deepCompare($a,$b) {
  if(is_object($a) && is_object($b)) {
    if(get_class($a)!=get_class($b))
      return false;
    foreach($a as $key => $val) {
      if(!deepCompare($val,$b->$key))
        return false;
    }
    return true;
  }
  else if(is_array($a) && is_array($b)) {
    while(!is_null(key($a)) && !is_null(key($b))) {
      if (key($a)!==key($b) || !deepCompare(current($a),current($b))) {
        return false;
			}
      next($a); next($b);
    }
    return is_null(key($a)) && is_null(key($b));
  }
  else
    return $a===$b;
}

function assertion($a, $b) {
	if (!deepCompare($a, $b)) {
		die("Comparison failed");
	}
}

function readCsv($file, $db = false) {
	$result = array();

	$src = file($file);
	array_shift($src);
	array_shift($src);

	$ctr=0;
	foreach ($src as $row) {
		$row = trim($row);
		if (!$row) continue;

		$a = str_getcsv($row);
		if (count($a) < 2) {
			echo "Error:$row\n";
			continue;
		}
		$name = trim($a[1], " \t\n\r\0\x0B\xc2\xa0")." ".trim($a[0], " \t\n\r\0\x0B\xc2\xa0");
		$ctr++;
		if ($db) {
			$result[$name]=array('sequence'=>$a[2]);
		} else {
			$result[$name]=-1;
		}
	}

	return $result;
}

function dump($list) {
	$ctr=0;
	foreach ($list as $item) echo (++$ctr)." $item\n";
}

function dumpChanges($changes) {
	foreach ($changes as $change) {
		echo $change['sequence']."/".$change['team'].": ".$change['player']." ".$change['date']."\n";
	}
}

function test1() {
	echo "---- test1 ------------------------------------------------------------\n";
	$list = readCsv('bears2.csv');

	dump($list);
}

function test2() {
	echo "---- New registration start of year 2001-08-01 ------------------------\n";
	$list = readCsv('bears2.csv');

	$r = Player::registrationMigrate(array(), $list, '2001-08-01', null); 
	$g = array();
	$ctr=1;
	foreach ($list as $name=>$team) $g[] = array("player"=>$name,"sequence"=>$ctr++,"date"=>"2001-08-01","team"=>-1);
	assertion($r, $g);
}

function test3() {	// Standard registration
	echo "---- Standard Registration 2001-08-01/2001-09-01 ----------------------\n";
	$old = readCsv('bears2.csv', true);
	$new = readCsv('bears3.csv');
print_r($new);
	$r = Player::registrationMigrate($old, $new, '2001-08-01', '2001-09-01'); 
	$g = array(
		array("player"=>"Gerardo Bea","sequence"=>14,"date"=>"2001-08-01","team"=>null),
		array("player"=>"Manuel Gabaldon","sequence"=>15,"date"=>"2001-08-01","team"=>null),
	);
	$ctr = 1;
	unset($old['Hayden Lillie']);
	unset($old['Erich Kalb']);
	foreach ($old as $name=>$team) $g[] = array("player"=>$name,"sequence"=>$ctr++,"date"=>"2001-08-01","team"=>-1);
	dumpChanges($g);
	assertion($r, $g);
}

function test4() {	// Empty registration
	echo "---- Empty Registration -----------------------------------------------\n";
	$list = readCsv('bears2.csv');

	Player::registrationMigrate($list, $list, '2001-08-01', '2001-09-01'); 
}

function test5() {	// Delete registration
	echo "---- Delete Registration ----------------------------------------------\n";
	$list = readCsv('bears2.csv');

	Player::registrationMigrate($list, array(), '2001-08-01', '2001-09-01'); 
}

function test6() {	// Start of year registration with existing registration
	echo "---- Start of year with existing registration -------------------------\n";
	$old = readCsv('bears2.csv',true);
	$new = readCsv('bears3.csv');

	Player::registrationMigrate($old, $new, '2001-08-01', null); 
}

function test7() {	// Mid season new registration
	echo "---- Mid season new registration --------------------------------------\n";
	$list = readCsv('bears2.csv');

	Player::registrationMigrate(array(), $list, '2001-08-01', '2001-09-01'); 
}

function test9() {	// Standard registration
	echo "---- Same Position 2001-08-01/2001-09-01 ------------------------------\n";
	$old = readCsv('bears2.csv',true);
	$new = readCsv('bearsn.csv');

	$r = Player::registrationMigrate($old, $new, '2001-08-01', '2001-09-01'); 

	assertion($r, array(
		array("player"=>"Pat RXbello","sequence"=>14,"date"=>"2001-08-01","team"=>-1),
		array("player"=>"Pat RXbello","sequence"=>6,"date"=>"2001-09-01","team"=>-1),
		array("player"=>"Ronny Overbey","sequence"=>7,"date"=>"2001-09-01","team"=>-1),
		array("player"=>"Herman Vanhoose","sequence"=>8,"date"=>"2001-09-01","team"=>-1),
		array("player"=>"Pat Rebello","sequence"=>-1,"date"=>"2001-08-01","team"=>null),
		));
}

test3();
/*
test2();
test3();
test4();
test5();
test6();
test9();*/
