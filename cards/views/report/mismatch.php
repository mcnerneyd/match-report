<style>
.important { background: #fba; }
</style>
<h1>Mismatch Result Report</h1>

<table class='table'>
	<tr><th>Date</th><th>Competition</th><th>Match</th><th>Score (Matchcard)</th><th>Score (LHA)</th></tr>
<?php 
foreach ($mismatches as $card) {
	$important = ($card['away']['score'] == 0 && $card['card']['away']['score'] != 0) 
			|| ($card['home']['score'] == 0 && $card['card']['home']['score'] != 0);
	$date = date('Y-m-d', $card['date']);
	echo "<tr ".($important?"class='important'":"")." title='".$card['id']."'><td>$date</td><td>${card['competition']}</td><td>".$card['home']['team']." v ".$card['away']['team']."</td>
		<td>".$card['card']['home']['score']." v ".$card['card']['away']['score']."</td>
		<td>".$card['home']['score']." v ".$card['away']['score']."</td>
		</tr>";
}

?>
</table>
