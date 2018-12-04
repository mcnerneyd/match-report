<table width='100%' cellspacing='0' cellpadding='10' border='0'>
	<tr>
		<th style='border-bottom:1px solid black'>Club</th>
		<th style='border-bottom:1px solid black'>ID</th>
		<th style='border-bottom:1px solid black'>Date</th>
		<th style='border-bottom:1px solid black'>Reason</th>
		<th style='border-bottom:1px solid black'>Fine</th>
	</tr>

<?php $lastClub = "";
foreach ($fines as $fine) { 
	$matches = array();
	preg_match('/(?<amount>[0-9]*):(?<reason>.*)/', $fine['detail'], $matches);
	$club = $fine['club']['name'];
	?>
	<tr>
		<td><?= $lastClub != $club ? $club : "" ?></td>
		<td><?= $fine['id'] ?></td>
		<td><?= $fine['date'] ?></td>
		<td><?= $matches['reason'] ?></td>
		<td>&euro;<?= $matches['amount'] ?></td>
	</tr>
<?php 
	$lastClub = $club;
	} ?>
</table>
