<style>
.label-competition { background-color: #7a33b7; }
</style>
<script>
	$(document).ready(function() {
		$('#teams-table').DataTable( {
			"columnDefs":[ {"width":"6em", "targets":1} ],
		});
	});
</script>

<table id='teams-table' class='table table-condensed table-striped'>
	<thead>
	<tr>
		<th>Club</th>
		<th>Team</th>
		<th>Competitions</th>
	</tr>
	</thead>

	<tbody>
	<?php foreach ($teams as $team) {
		echo "<tr>
			<td>".$team['club']['name']."</td>
			<td>${team['team']}</td><td>";
		foreach ($team['competition'] as $competition) {
			echo "<span class='label label-competition'>${competition['code']}";
			echo "</span>&nbsp;";
		}
		echo "</td></tr>";
	} ?>
	</tbody>
</table>

