<script>
$(document).ready(function() {
	$('table').dataTable({
	});
});
</script>
<table class='table table-condensed table-striped'>
	<thead>
	<tr>
		<th>Player</th>
		<th>Club</th>
		<th>Lowest Team</th>
		<th>Highest Team</th>
		<th>Total Games</th>
		<th>Float Games</th>
	</tr>
	</thead>
	<tbody>
	<?php foreach ($data as $row) {
		echo "<tr>
			<td>${row['name']}</td>
			<td>${row['club']}</td>
			<td>${row['lowestTeam']}</td>
			<td>${row['highestTeam']}</td>
			<td>${row['total']}</td>
			<td>".($row['total']-$row['lowestTeamCount'])."</td>
			</tr>\n";
	}?>
	</tbody>
</table>

