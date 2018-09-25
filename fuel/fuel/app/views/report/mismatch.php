<script>
	$(document).ready(function() {
		$('#mismatch-table').DataTable( {
			"order": [[0, 'desc']]
		});
		$('#mismatch-table').show();
	});
</script>

<style>
.important { background: #fba !important; }
</style>

<table id='mismatch-table' class='table table-condensed table-striped' style='display:none'>
	<thead>
	<tr><th>Date</th><th>Competition</th><th>Match</th><th>Score (Matchcard)</th><th>Score (Fixture)</th></tr>
	</thead>
	<tbody>
<?php 
foreach ($mismatches as $card) {
	//$important = ($card['away']['goals'] == 0 && $card['card']['away']['score'] != 0) 
	//		|| ($card['home']['score'] == 0 && $card['card']['home']['score'] != 0);
	$date = date('Y-m-d', $card['date']->get_timestamp());
	//echo "<tr ".($important?"class='important'":"")." title='".$card['id']."'><td>$date</td><td>${card['competition']}</td><td>".$card['home']['team']." v ".$card['away']['team']."</td>
	echo "<tr title='".$card['id']."'";
	if ($card['outcome_affected']) echo " class='important'";
	echo "><td>$date</td>
		<td>${card['competition']}</td>
		<td><a href='".Uri::create("Report/Card")."/".$card['fixture_id']."'>
		".$card['home_team']." v ".$card['away_team']."
		</a></td>
		<td>".$card['home']['goals']." v ".$card['away']['goals']."</td>
		<td>".$card['home_score']." v ".$card['away_score']."</td>
		</tr>";
} ?>
	<tbody>
</table>
