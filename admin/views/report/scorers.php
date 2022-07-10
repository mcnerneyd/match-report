<script>
	$(document).ready(function() {
		$('#scorers-table').DataTable( {
			"order": [[3, 'desc']]
		});
		$('#scorers-table').show();
	});
</script>

<table id='scorers-table' class='table table-condensed table-striped' style='display:none'>
	<thead>
	<tr>
		<th>Player</th>
		<th>Club</th>
		<th>Competition</th>
		<th>Score</th>
	</tr>
	</thead>

	<tbody>
	<?php foreach ($scorers as $scorer) {
		echo "<tr>
			<td>${scorer['player']}</td>
			<td>${scorer['club']}</td>
			<td>${scorer['competition']}</td>
			<td>${scorer['score']}</td>
		</tr>";
	} ?>
	</tbody>
</table>
