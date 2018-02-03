<style>
* { font-family: monospace; }
th { background: #def; }
</style>
<?php
function debug($x) { }

require_once('config.php');
require_once('util.php');
require_once('model/connection.php');
require_once('model/card.php');

$teams = array();
$competitions = array();

foreach (Card::getFixtures() as $fixture) {
	$teams[$fixture->home] = "";
	$teams[$fixture->away] = "";
	$competitions[$fixture->competition] = "";
}

$teams = array_keys($teams);
sort($teams);
$competitions = array_keys($competitions);
sort($competitions);

$db = Db::getInstance();
$dbTeams = array();
foreach ($db->query("select c.name, t.team from team t join club c on t.club_id = c.id")->fetchAll() as $row) {
	$dbTeams[] = "!".$row['name']." ".$row['team'];
}
foreach ($db->query("select name from competition") as $row) {
	$dbTeams[] = "£".$row['name'];
}


echo "<table><tr><th colspan='2'>Teams</th></tr>";
foreach ($teams as $team) {
	try {
		$trans = parse($team);
	} catch (Exception $e) {
		echo "<tr><td>$team</td><td><span style='color:red'>".$e->getMessage()."</span></td></tr>";
		continue;
	}

	if (in_array("!".$trans['club']." ".$trans['team'], $dbTeams)) {
	echo "<tr><td>$team</td><td> &#8674; <span style='color:blue'>${trans['club']}</span> <span style='color:green'>${trans['team']}</span></tr>";
	} else {
	echo "<tr><td style='color:red'>$team</td><td></td></tr>";
	}
}

echo "<tr><th colspan='2'>Competitions</th></tr>";

foreach ($competitions as $competition) {
	if (in_array("£".$row['name'], $dbTeams)) {
		echo "<tr><td>$competition</td><td> &#8674; <span style='color:purple'>".parsecompetition($competition, null)."</span></td></tr>";
	} else {
		echo "<tr><td style='color:red'>$competition</td><td></td></tr>";
	}
}
echo "</table>";

