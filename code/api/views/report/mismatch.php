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
	$date = date('Y-m-d', $card['date']->get_timestamp());
	$cardUrl = 'http://cards.leinsterhockey.ie/card/n'.$card['id'];

	$opscore = (isset($card['away-opposition-score']) ? $card['away-opposition-score'] : "?");
	$opscore .= " v ";
	$opscore .= (isset($card['home-opposition-score']) ? $card['home-opposition-score'] : "?");
			
	$url = "https://admin.sportsmanager.ie/fixtureFeed/push.php?fixtureId=${card['fixture_id']}&homeScore=".$card['home']['goals']."&awayScore=".$card['away']['goals'];

	echo "<tr title='".$card['id']."'";
	$class = '';
	if ($card['outcome_affected']) $class .= 'important ';
	if ($class) echo " class='".trim($class)."'";
	echo "><td>$date</td>
		<td>${card['competition']}</td>
		<td><a href='".$cardUrl."'>".$card['home_team']." v ".$card['away_team']."</a>";
	if ($card['home']['notes'] || $card['away']['notes']) echo ' <i class="far fa-sticky-note"></i>';

    echo "</td>
		<td>".$card['home']['goals']." v ".$card['away']['goals']." <small>(".$opscore.")</small></td>
		<td><a href='$url'>".$card['home_score']." v ".$card['away_score']."</a></td>
		</tr>";
} ?>
	<tbody>
</table>
