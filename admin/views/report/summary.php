<div>
<h1>Weekly Report</h1>

<table>
	<tr><th>Date</th><td><?= $date['from'] ?> to <?= $date['to'] ?></td></tr>
	<tr><th>Club</th><td><?= $club['name'] ?></td></tr>
</table>

<h2>Match Cards</h2>
<table style='width:100%'>
<thead>
	<tr style='border-bottom:1px solid black'>
		<th>Date</th>
		<th>Competition</th>
		<th>Home</th>
		<th>Score</th>
		<th>Away</th>
	</tr>
</thead>
<tbody>
<?php
foreach ($cards as $score) {
	echo "<tr><td>".date('Y-m-d', $score['date']->get_timestamp())."</td>
		<td>".$score['competition']."</td>
		<td>".$score['home']['club']." ".$score['home']['team']."</td>
		<td>".$score['home']['goals']."-".$score['away']['goals']."</td>
		<td>".$score['away']['club']." ".$score['away']['team']."</td>
		</tr>";
}
?>
</tbody>
</table>

<h2>Scores</h2>
<table style='width:100%'>
<thead>
	<tr style='border-bottom:1px solid black'>
		<th>Date</th>
		<th>Team</th>
		<th>Player</th>
		<th>Score</th>
	</tr>
</thead>
<tbody>
<?php
foreach ($scores as $score) {
	$homeTeam = ($score['card']['home']['club_id'] == $club['id']);

	if ($homeTeam) $team = $score['card']['home'];
	else $team = $score['card']['away'];

	echo "<tr><td>".date('Y-m-d', strtotime($score['date']))."</td>
		<td>".$club['name']." ".$team['team']."</td>
		<td>".$score['player']."</td>
		<td>".$score['detail']."</td></tr>";
}
?>
</tbody>
</table>

<h2>Cards</h2>

<h2>Fines</h2>
<table style='width:100%'>
<thead>
	<tr style='border-bottom:1px solid black'>
		<th>Date</th>
		<th>Team</th>
		<th>Fine</th>
	</tr>
</thead>
<tbody>
<?php
foreach ($fines as $fine) {
	$homeTeam = ($fine['card']['home']['club_id'] == $club['id']);

	if ($homeTeam) $team = $fine['card']['home'];
	else $team = $fine['card']['away'];

	$detail = preg_split("/:/", $fine['detail'], 2);

	echo "<tr><td>".date('Y-m-d', strtotime($fine['date']))."</td>
		<td>".$club['name']." ".$team['team']."</td>
		<td>".$detail[1]." (&euro;".$detail[0].")</td></tr>";
}
?>
</tbody>
</table>
</div>
