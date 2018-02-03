<?php

require_once('util.php');

$_REQUEST['site'] = 'lhamen';

function warning($msg) {
	log_write('WARNING', $msg);
	echo "$msg\n";
}

function info($msg) {
	log_write('INFO', $msg);
	echo "$msg\n";
}

function debug($msg) {
	if (isset($_REQUEST['debug'])) {
		log_write('DEBUG', $msg);
		echo "$msg\n";
	}
}

require_once('util.php');
require_once('config.php');
require_once('model/connection.php');
require_once('secure.php');
require_once('mail.php');

require_once('model/club.php');
require_once('model/card.php');
require_once('model/player.php');
require_once('controllers/club_controller.php');

function url($query, $action, $controller) {	
	global $site;

	$target = "";

	$target.="&site=".site();
	if ($controller) $target.="&controller=$controller";
	if ($action) $target.="&action=$action";
	if ($query) $target .= "&".$query;

	$url = 'http://' . $_SERVER['HTTP_HOST'];            // Get the server

	return $url . "/cards/index.php?".substr($target,1); //str_replace("&", "&amp;", substr($target,1));
}

function register($data, $from, $filename, $date, $test = false) {

	if (isset($_REQUEST['test'])) $test = true;

	$club = Club::fromSecretaryEmail($from);

	debug("Club=$club");

	if (!$club) {
		echo "Email $from is not listed as a secretary for a registered club.";
		return;
	}

	try {
		$tmpfile = tempnam("tmp", "reg_");
		$f = fopen($tmpfile, "w+");
		fwrite($f, $data);
		fclose($f);

		$lines = loadFile(array('name'=>$filename,'tmp_name'=>$tmpfile));
		echo "file loaded: $filename\n";
		$result = ClubController::orderedlistupload($lines, $club, $date, false);
		$errors = array();
		
		if (isset($result['errors'])) {
			$errors = $result['errors'];
			unset($result['errors']);

			foreach ($result as $player=>$detail) {
				if (isset($detail['error'])) {
					$errors[] = $detail['error'];
				}
			}
		}

		warning("ERRORS:\n".print_r($errors, true));

		unlink($tmpfile);

		$date = date('Y-m-d');
		$players = ClubController::getPlayers(date('Y-m-d'), $club);

		$registration = "$club,,$date\nLast Name,First Name,Team,Rating\n";

		foreach ($players as $player=>$detail) {
			$registration .= "$player,${detail['team']},${detail['score']}\n";
		}

		if (count($errors) > 0) {
			$msg = "Registration contains errors\n\nExisting registration for $date attached.";
			$msg .= "<span style='color:red'><hr>Errors:\n<ul>";
			foreach ($errors as $error) {
				$msg .= "<li>".$error."</li>";
			}
			$msg .= "</ul>";
			$msg .= "<p>The LHA Mens Registration Secretary can upload this file for you if you can provide suitable justification.</p>";
		} else {
			$msg = "Registration submitted without error.\n\nRegistration attached for $date - please verify this.";
		}

		if (!$test) {
			sendClubMessage($club, "$club Registration Successful", $msg, array("$club.csv"=>$registration));

			$path = "archive/lhamen/registration/".strtolower($club)."/";
			if (!file_exists($path)) {
				mkdir($path, 0777, true);
			}

			$fname = $path.date("ymdHis").".csv";
			file_put_contents($fname, $registration);
		} else {
			echo $msg;
		}
	} catch (Exception $e) {
		if (!$test) {
			sendClubMessage($club, "$club Registration Failed", 
				"The registration failed.\nReason: ".$e->getMessage());
		} else {
			print_r($e);
		}
	}
}

function query_register($from, $test = false) {

	if ($test) {
		$club = $from;
	} else {
		$club = Club::fromSecretaryEmail($from);
	}

	debug("Email=$from Club=$club");

	if ($club == null) return;

	$date = date('Y-m-d');

	$players = ClubController::getPlayers($date, $club);

	$msg = "$club,,$date\nLast Name,First Name,Team,Rating\n";

	foreach ($players as $player=>$detail) {
		$msg .= "$player,${detail['team']},${detail['score']}\n";
	}

	if ($test) {
		echo $msg;
		return;
	}

	sendClubMessage($club, "$club Registration", "Registration attached for $date.", array("$club.csv"=>$msg));
}

if (basename($_SERVER['PHP_SELF']) == 'stub.php') {

if (isset($_POST['from'])) {
	echo "<pre>";
	echo print_r($_REQUEST, true);
	if (isset($_REQUEST['btnUpload'])) {
		$f0 = $_FILES['file']['tmp_name'];
		$f = fopen($f0, 'r');
		$data = fread($f, filesize($f0));
		fclose($f);

		register($data, $_POST['from'], $_FILES['file']['name'], $_POST['date'], true);
	}

	if (isset($_REQUEST['btnRegistration'])) {
		query_register($_POST['from'], true);
	}

	echo "</pre>";
}
?>

<form method='POST' action='stub.php' enctype='multipart/form-data'>
	From: <input name='from' type='text'/><br>
	Date: <input name='date' type='text' value='<?= date('Y-m-d') ?>'/><br>
	File: <input name='file' type='file'/><br>
	<button type='submit' name='btnUpload'>Upload</button>
	<button type='submit' name='btnRegistration'>Registration</button>
</form>

<?php } ?>


