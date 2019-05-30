<table width='100%' cellspacing='0' cellpadding='10' border='0'>
	<tr>
		<th style='border-bottom:1px solid black;text-align:left'>Club</th>
		<th style='border-bottom:1px solid black;text-align:left'>Reason</th>
		<th style='border-bottom:1px solid black;text-align:left'>Fine</th>
		<th style='border-bottom:1px solid black;text-align:left'>Match</th>
	</tr>

<?php $lastClub = "";
foreach ($fines as $fine) { 
	$matches = array();
	preg_match('/(?<amount>[0-9]*):(?<reason>.*)/', $fine['detail'], $matches);
	$club = $fine['club']['name'];
	?>
	<tr>
		<td><?= $lastClub != $club ? $club : "" ?></td>
		<td><?= $matches['reason'] ?></td>
		<td>&euro;<?= $matches['amount'] ?><br><span style='font-size:80%'><?= $fine['id'] ?>/<?= $fine['matchcard_id'] ?></span></td>
		<td><span style='font-size:90%'><?= $fine['date'] ?><br><?= $fine['competition']."<br>".$fine['home_team']." v ".$fine['away_team'] ?></span></td>
	</tr>
<?php 
	$lastClub = $club;
	} ?>
</table>
