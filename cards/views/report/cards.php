<h1>Player Card Report</h1>

<table class='table'>
	<tr><th>Date</th><th>Competition</th><th>Match</th><th>Player</th><th>Card</th><th>Umpire</th></tr>
<?php 
foreach ($cards as $card) {
	$date = date('Y-m-d', strtotime($card['date']));
	echo "<tr><td>$date</td><td>${card['Competition']}</td><td>${card['Home']} v ${card['Away']}</td><td>${card['player']} (${card['club']})</td>
		<td><img width='18px' src='".($card['type'] == 'Red Card'?'img/red-card.png':'img/yellow-card.png')."'/> ${card['detail']}</td>
		<td>${card['user']}</td>
		</tr>";
}

?>
</table>
