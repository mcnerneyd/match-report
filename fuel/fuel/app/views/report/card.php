<head>
	<title>Card #<?= "${card['id']}/${card['fixture_id']}" ?></title>
</head>
<style>
* { font-family: monospace; }
#header tr td:first-child { font-weight: bold; padding-right: 30px; }
#teams th { background: #def; }
#incidents td { white-space: nowrap; padding-right: 12px; }
</style>

<?php if ($card) { ?>
<table id='header'>
	<tr><td>id</td><td><?= $card['id'] ?></td></tr>
	<tr><td>fixture id</td><td><?= $card['fixture_id'] ?></td></tr>
	<tr><td>fixture date</td><td><?= $fixture['datetime'] ?></td></tr>
	<tr><td>date</td><td><?= $card['date'] ?></td></tr>
	<tr><td>competition</td><td><?= $card['competition'] ?></td></tr>
</table>

<table id='teams'>
<tr><th>Home</th><td><?= $card['home']['club']." ".$card['home']['team'] ?></tr>
<tr><td><strong>Score</strong></td><td><?= $card['home']['goals'] ?></td></tr>
<?php
	foreach ($card['home']['players'] as $player=>$incident) {
		echo "<tr><td style='color:blue'>$player</td>
			<td>${incident['number']}</td>
			<td>${incident['date']}</td></tr>";
	}
?>
<tr><th>Away</th><td><?= $card['away']['club']." ".$card['away']['team'] ?></tr>
<tr><td><strong>Score</strong></td><td><?= $card['away']['goals'] ?></td></tr>
<?php
	foreach ($card['away']['players'] as $player=>$incident) {
		echo "<tr><td style='color:blue'>$player</td>
			<td>${incident['number']}</td>
			<td>${incident['date']}</td></tr>";
	}
?>
</table>

<hr>
<h3>Incidents</h3>
<table id='incidents'>
<?php foreach ($incidents as $incident) {
	echo "<tr data-description='${incident['type']} ${incident['player']} ${incident['name']} ${incident['detail']}'>";
	echo "<td>".$incident['date']."</td>";
	echo "<td>".$incident['type']."</td>";
	echo "<td>".$incident['player']."</td>";
	echo "<td>".$incident['name']."</td>";
	echo "<td>".$incident['detail']."</td>";
	echo "<td>".$incident['resolved']."</td>";
	echo "</tr>";
} ?>
</table>

<hr>
<h3>Raw Fixture Data</h3>
<pre>
<?php print_r($fixture); ?>
</pre>

<hr>

<h3>Raw Card Data</h3>
<pre>
<?php print_r($card) ?>
</pre>
<?php } /* if $card */ 
else echo "No card on system";

