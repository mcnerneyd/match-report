<style>
table * { font-family: monospace; }
th { background: #def; }
</style>
<table class='report'>
	<tr><th colspan='2'>Teams</th></tr>
<?php foreach ($teams as $team) {
		if ($team['valid']) {
			echo "<tr><td>${team['name']}</td><td> &#8674; <span style='color:blue'>${team['club']}</span> <span style='color:green'>${team['team']}</span></tr>";
		} else {
			echo "<tr><td>${team['name']}</td><td><span style='color:red'>No Match</span></td></tr>";
		}
} ?>
</table>

