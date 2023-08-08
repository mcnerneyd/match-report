<head>
	<title>Fixture #<?= $fixture['fixtureID'] ?></title>
	<?= Asset::js(array('jquery-3.3.1.js')) ?>
</head>
<style>
* { font-family: monospace; }
#header tr td:first-child { font-weight: bold; width: 8rem; }
#teams  tr th { font-weight: bold; width: 8rem; }
#teams th { background: #def; }
#incidents td { white-space: nowrap; padding-right: 12px; }
#incidents tr.delete * { text-decoration: line-through; color: #888; }
.delete { color: red; }
</style>
<script>
$(document).ready(function() {
	$('.delete').click(function() {
		var id=$(this).closest('[data-id]').data('id');
		$.ajax({url:"<?= Uri::create('CardApi/Incident') ?>",
			data:{"incident_id":id},
			method:"delete"})
			.done(function() { window.location.reload(); });
	});
});
</script>

<table id='header'>
	<tr><td>id</td><td><?= $fixture['fixtureID'] ?></td></tr>
	<tr><td>card id</td><td><?= $card ? $card['id'] : '-' ?></td></tr>
	<tr><td>section</td><td><?= $fixture['section'] ?></td></tr>
	<tr><td>fixture date</td><td><?= $fixture['datetimeZ'] ?></td></tr>
  <?php if ($card) { ?>
	<tr><td>card date</td><td><?= $card['date'] ?></td></tr>
  <?php } ?>
	<tr><td>competition</td><td><?= $fixture['competition'] ?></td></tr>
</table>

<table id='teams'>
<tr><th>Home</th><td><?= $fixture['home'] ?></tr>
<?php if ($card) { ?>
<tr><td><strong>Score</strong></td><td><?= $card['home']['goals'] ?></td></tr>
<?php
	foreach ($card['home']['players'] as $player=>$incident) {
		echo "<tr data-description='home $player ${incident['number']}'><td style='color:blue'>$player</td>
			<td>${incident['number']}</td>
			<td>${incident['date']}</td></tr>";
	}
}
?>
<tr><th>Away</th><td><?= $fixture['away'] ?></tr>
<?php if ($card) { ?>
<tr><td><strong>Score</strong></td><td><?= $card['away']['goals'] ?></td></tr>
<?php
	foreach ($card['away']['players'] as $player=>$incident) {
		echo "<tr data-description='away $player ${incident['number']}'><td style='color:blue'>$player</td>
			<td>${incident['number']}</td>
			<td>${incident['date']}</td></tr>";
	}
} ?>
</table>

<?php if ($card) { ?>
<hr>
<h3>Incidents</h3>
<table id='incidents'>
<?php foreach ($incidents as $incident) {
	$description = trim("${incident['type']}");
	$description = trim("$description ${incident['player']}");
	$description = trim("$description ${incident['name']}");
	$description = trim("$description ${incident['detail']}");
	$class = $incident['resolved'] == 1 ? "deleted" : "";
	echo "<tr class='$class' data-description='$description' data-id='${incident['id']}'>";
	echo "<td>".$incident['id'];
	if (\Auth::has_access('incident.delete')) {
		echo " <a class='delete'>[delete]</a>";
	}
	echo "</td>";
	echo "<td>".$incident['date']."</td>";
	echo "<td>".$incident['type']."</td>";
	echo "<td>".$incident['player']."</td>";
	echo "<td>".$incident['name']."</td>";
	echo "<td>".$incident['username']."</td>";
	echo "<td>".$incident['resolved']."</td>";
	echo "<td>".$incident['detail']."</td>";
	echo "</tr>";
} ?>
</table>

<hr>
<?php } ?>

<h3>Raw Fixture Data</h3>
<pre>
<?php print_r($fixture); ?>
</pre>

<?php if ($card) { ?>
<hr>

<h3>Raw Card Data</h3>
<pre>
<?php print_r($card) ?>

</pre>
<?php } /* if $card */ 
else echo "No card on system";

