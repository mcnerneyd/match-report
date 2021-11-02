<?php
$dateTo = date('Y-m-d');
$dateFrom = date('Y-m-d', strtotime($dateTo." -7 days"));

$anomalies = array();

$sql = "SELECT matchcard_id, c.name, count(*) ct FROM incident i left join club c on i.club_id = c.id
	WHERE type = 'Played'
    	and date between '$dateFrom' and '$dateTo'
    group by matchcard_id, c.name
    having count(*) < 11 or count(*) > 16";

foreach (\DB::query($sql)->execute() as $row) {
	$detail = array("card"=>Model_Matchcard::card($row['matchcard_id']),
		"club"=>$row['name']);
	$ct = $row['ct'];
	if ($ct < 11) $detail['description'] = "Card has less than normal players ($ct)";
	if ($ct > 16) $detail['description'] = "Card has more than normal players ($ct)";

	$anomalies[] = $detail;
}

$sql = "SELECT matchcard_id, c.name, detail FROM incident i left join club c on i.club_id = c.id 
	WHERE type = 'Other' and detail like '\"%'
    	and date between '$dateFrom' and '$dateTo'";
foreach (\DB::query($sql)->execute() as $row) {
	$detail = array("card"=>Model_Matchcard::card($row['matchcard_id']),
		"description"=>"Note:".$row['detail'],
		"club"=>$row['name']);

	$anomalies[] = $detail;
}

$sql = "SELECT matchcard_id, c.name, count(*) ct 
		FROM incident i left join club c on i.club_id = c.id 
		WHERE type = 'Ineligible' 
			and resolved = 0
    	and date between '$dateFrom' and '$dateTo' 
			group by matchcard_id, c.name";
foreach (\DB::query($sql)->execute() as $row) {
	$detail = array("card"=>Model_Matchcard::card($row['matchcard_id']),
		"description"=>"Card contains ".$row['ct']." ineligible player(s)",
		"club"=>$row['name']);

	$anomalies[] = $detail;
}

usort($anomalies, function($a, $b) {
		if ($a['card']['date'] == $b['card']['date']) { return 0; }
    return ($a['card']['date'] < $b['card']['date']) ? -1 : 1;
});
?>
<style>
.warning {
	background: #f7e8e9;
	color:#7c0000;
}
td {
	padding-right: 1em;
}
</style>
<h3><strong>Report Date:</strong> <?= date('l j F, Y') ?> (From <?= $dateFrom ?> to <?= $dateTo ?>)</h3>

<h4>Anomalies</h4>

<table>
	<thead>
		<th>Date</th>
		<th>Match</th>
		<th>Detail</th>
	</thead>
	<tbody>
<?php foreach ($anomalies as $anomaly) {
	$card = $anomaly['card'];
	$home = $card['home']['club']." ".$card['home']['team'];
	if ($card['home']['club'] == $anomaly['club']) {
		$home = "<strong>$home</strong>";
	}
	$away = $card['away']['club']." ".$card['away']['team'];
	if ($card['away']['club'] == $anomaly['club']) {
		$away = "<strong>$away</strong>";
	}
	$description = "${card['competition']}: $home v $away";
	
	$url = "http://cards.leinsterhockey.ie/cards/index.php?site=".Session::get('site')."&controller=card&action=get&fid=".$card['fixture_id'];
	echo "<tr>
			<td>".$card['date']."</td>
			<td><a href='$url'>$description</a></td>
			<td>".$anomaly['description']."</td>
		</tr>";
} ?>
	</tbody>
</table>


<h4>Registrations</h4>
<table>
	<thead>
		<tr>
			<th>Club</th>
			<th># Registered</th>
			<th>Team Sizes</th>
		</tr>
	</thead>

	<tbody>
<?php	
		foreach (Model_Club::find('all') as $club) {
			$ts = $club->getTeamSizes(null);	// FIXME

			if (!$ts) continue;

			$reg = Model_Registration::find_before_date($club->name, $reportDate);

			echo "<tr";
			if (!$reg) echo " class='warning'";
			echo "><td>".$club->name."</td><td>".count($reg)."</td><td>";
			foreach ($ts as $teamSize) {
				echo $teamSize." ";
			}
			echo "</td></tr>";
		}
		?>
	</tbody>
</table>

