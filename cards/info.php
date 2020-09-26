<?php 
ini_set('display_errors',1);
ini_set('display_startup_errors',1);
ini_set('max_execution_time', 300); 
error_reporting(E_ALL);

require_once('util.php');
require_once('fuel.php');
require_once('model/connection.php');

function debug($msg) { }

if (isset($_GET['c'])) {
	$clubs = $_GET['c'];

	foreach (explode(',', $clubs) as $club) {

	echo "<h2>$club</h2>\n";
	$db = Db::getInstance();

	echo "<pre>";

	$req = $db->query("select id from club where name = '$club'");
	$id = null;
	foreach ($req->fetchAll() as $row) {
		$id = $row['id'];
		echo "id=$id\n";
	}

	$req = $db->query("select id, team from team where club_id = $id");
	foreach ($req->fetchAll() as $row) {
		echo "team ${row['team']} id=${row['id']}\n";
	}

	echo "</pre>";
	}

	return;
}

echo "<pre>";
$configFile = DATAPATH.'/sites/'.site().'/config.json';
print_r(json_decode(file_get_contents($configFile), true));
echo "</pre>";

phpinfo();
