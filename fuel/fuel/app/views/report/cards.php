<script>
	$(document).ready(function() {
		$('#warning-table').dataTable({
			"order": [[0, 'desc']],
		});
		$('#warning-table tbody').show();
	});
</script>

<table class='table' id='warning-table'>
	<thead>
	<tr>
		<th>Date</th>
		<th>Competition</th>
		<th>Match</th>
		<th>Player</th>
		<th>Card</th>
		<th>Umpire</th>
	</tr>
	</thead>

	<tbody>
<?php foreach ($cards as $card) {
	$date = date('Y-m-d', strtotime($card['date']));
	echo "<tr>
		<td>$date</td>
		<td>".$card['card']['competition']['name']."</td>
		<td>".$card['card']['home']['club']['name']." v ".$card['card']['away']['club']['name']."</td>
		<td>${card['player']} (".$card['club']['name'].")</td>
		<td>".Asset::img($card['type'] == 'Red Card'?'red-card.png':'yellow-card.png',array('width'=>'18px'))." ${card['detail']}</td>
		<td>".$card['user']['username']."</td>
		</tr>";
}

?>
	</tbody>
</table>
