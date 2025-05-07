<head>
	<title>Weekly Summary for <?= $club ?></title>
	<style>
	* { font-family: monospace; }
	td, th { padding: 0 10px 0 0; }
	</style>
</head>
<body>
	<h2>Weekly Summary for <?= $club ?></h2>
	<p><strong>Date:</strong> <?= $date ?></p>

	<h3>Matchcards Submitted</h3>
	<table>
		<thead>
			<tr>
				<th>Date/Time</th>
				<th>Competition</th>
				<th>Home</th>
				<th>Result</th>
				<th>Away</th>
			</tr>
		</thead>
		<tbody>
<?php
$start = date('Y-m-d');
$finish = date('Y-m-d', strtotime("$start -1 week"));
$startTS = strtotime($start);
$finishTS = strtotime($finish);
$fixtures = array();
foreach (Model_Fixture::getAll() as $fixture) {
	if ($fixture['datetime']->get_timestamp() < $startTS) continue;
	if ($fixture['datetime']->get_timestamp() > $finishTS) continue;
	if ($fixture['home_club'] != $club and $fixture['away_club'] != $club) continue;
	$fixtures[$fixture['fixtureID']] = $fixture;
}

$missing = $fixtures;

$sql = "select m.id from matchcard m
		join team th on m.home_id = th.id
		join club ch on th.club_id = ch.id
		join team ta on m.away_id = ta.id
		join club ca on ta.club_id = ca.id
		join competition x on m.competition_id = x.id
	where (ca.name = '$club' or ch.name = '$club')
		and (m.date between '$finish' and '$start')
	order by m.date";
foreach (DB::query($sql)->execute() as $row) {
	$card = null;
	try {
		$card = Model_Card::card($row['id']);
	} catch (Exception $e) {
		continue;
	}
	unset($missing[$card['fixture_id']]);
	echo "<tr>
		<td>${card['date']}</td>
		<td>${card['competition']}</td>
		<td>${card['home_name']} ${card['home_team']}</td>
		<td>".$card['home']['goals']." v ".$card['away']['goals']."</td>
		<td>${card['away_name']} ${card['away_team']}</td>
		</tr>\n";
}
?>
		</tbody>
	</table>

<?php if ($missing) { ?>
	<h3>Incomplete/Missing Matchcards</h3>
	<table>
		<thead>
			<tr>
				<th>Competition</th>
				<th>Home</th>
				<th>Away</th>
			</tr>
		</thead>
		<tbody>
<?php foreach ($missing as $missingCard) {
	echo "<tr>
		<td>${missingCard['competition']}</td>
		<td>${missingCard['home']}</td>
		<td>${missingCard['away']}</td>
		</tr>\n";
}?>
		</tbody>
	</table>
<?php } ?>

	<h3>Red Cards/Yellow Cards/Ineligible Players</h3>
<?php

?>

	<h3>Fines</h3>
<?php

?>
	<i>Please note this "Weekly Summary" is work in progress and will fill out more completely over the coming weeks</i>
</body>
