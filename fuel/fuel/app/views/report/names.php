<style>
table * { font-family: monospace; margin-bottom: 12px; }
th { background: #def; }
</style>

<table><tr><th colspan='2'>Teams</th></tr>
<?php 

function site() {
	return Session::get('site');
}

foreach ($teams as $team) {
	$trans = parse($team);

	if (in_array("!".$trans['club']." ".$trans['team'], $dbTeams)) {
	echo "<tr>
		<td>$team</td>
		<td> &#8674; <span style='color:blue'>${trans['club']}</span> <span style='color:green'>${trans['team']}</span></td>
		</tr>";
	} else {
	echo "<tr><td style='color:red'>$team</td><td></td></tr>";
	}
}

echo "<tr><th colspan='2'>Competitions</th></tr>";

foreach ($competitions as $competition) {
	$trans = parsecompetition($competition, null);

	if ($trans != null && in_array("£".$trans, $dbTeams)) {
		echo "<tr><td>$competition</td><td> &#8674; <span style='color:purple'>".$trans."</span></td></tr>";
	} else {
		echo "<tr><td style='color:red'>$competition</td><td></td></tr>";
	}
}
?>
</table>
