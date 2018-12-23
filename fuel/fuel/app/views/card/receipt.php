<head>
	<title> <?= $card['competition'] ?> Matchcard #<?= $card['id']." ".$card['home']['club']." ".$card['home']['team']." (".$card['home']['goals']."-".$card['away']['goals'].") ".$card['away']['club']." ".$card['away']['team'] ?></title>
	<style>
		* { font-family: monospace }
		th { background: #def }
		td { padding-right: 15px }
		h1 { text-decoration: underline }
		h1 strong { color: green }
	</style>
</head>
<body>
<?php if ($card['home']['club'] == $club) $whoami='home';
	else $whoami = 'away'; 
	$detail = $card[$whoami]; ?>


<h2><?= $club ?> Players</h2>
<table>
	<tr><th>Time</th><th>Name</th></tr>
<?php foreach ($detail['players'] as $player=>$playerDetail) { 
	if (isset($playerDetail['deleted'])) continue;

	echo "<tr><td>${playerDetail['date']}</td><td>$player</td></tr>\n";
} ?>
</table>

<h2>Goals Scored</h2>
<table>
<?php foreach ($detail['scorers'] as $player=>$playerDetail) { 
	echo "<tr><td>$player</td><td>$playerDetail</th></tr>\n";
} ?>
</table>

<?php if (isset($detail['penalties'])) { ?>
<h2>Penalty Cards</h2>
<table>
<?php
	foreach ($detail['penalties'] as $penalty) {
		echo "<tr><td>${penalty['player']}</td><td>${penalty['penalty']}</td><td>${penalty['detail']}</td></tr>\n";
	}
	?>
</table>
<?php } ?>

</body>
