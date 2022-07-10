<style>
table.report { margin-bottom: 12px; }
table * { font-family: monospace; }
th { background: #def; }
</style>
<table class='report'>
	<tr><th colspan='2'>Teams</th></tr>
<?php foreach ($teams as $raw=>$team) {
		if ($team['valid']) {
			echo "<tr><td>$raw</td><td> &#8674; <span style='color:blue'>${team['club']}</span> <span style='color:green'>${team['team']}</span></tr>";
		} else {
			echo "<tr><td>$raw</td><td><span style='color:red'>No Match</span></td></tr>";
		}
} ?>
</table>

<table class='report'>
	<tr><th colspan='2'>Competitions</th></tr>
<?php foreach ($competitions as $raw=>$competition) {
	if ($competition['valid']) {
		echo "<tr><td>$raw</td><td> &#8674; <span style='color:purple'>".$competition['name']."</span></td></tr>";
	} else {
		echo "<tr><td style='color:red'>$raw</td><td></td></tr>";
	}
} ?>
</table>
