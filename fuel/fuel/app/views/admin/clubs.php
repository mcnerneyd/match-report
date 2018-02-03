<style>
#clubs-table th:nth-of-type(2) { width: 50px; }
</style>
<script>
	$(document).ready(function() {
		$('#clubs-table').DataTable({
			columns:[
				{width:"2em"},
				{width:"30%"},
				null,
			]
			});
		$('#clubs-table tbody').show();
	});
</script>

<table id='clubs-table' class='table table-condensed table-striped'>
	<thead>
	<tr>
		<th>Code</th>
		<th>Club</th>
		<th class='desktop'>Teams</th>
	</tr>
	</thead>

	<tbody style='display:none'>
	<?php foreach ($clubs as $club) {
		echo "<tr>
			<td>${club['code']}</td>
			<td>${club['name']}</td>
			<td>";
		$comps = array();
		foreach ($club['team'] as $team) {
			$comps = array_merge($comps, $team['competition']);
		}
		usort($comps, function($a,$b) {
			$sa = $a['sequence'];
			$sb = $b['sequence'];
			if ($sa == $sb) $ret = 0; 
			else $ret = ($sa < $sb) ? -1 : 1;
			return $ret; 
		});
		foreach ($comps as $teamComp) {
			echo "<span class='hidden-sm hidden-xs label label-".($teamComp['teamsize']?'league':'cup')."'>${teamComp['name']}</span>";
			echo "<span class='hidden-md hidden-lg label label-".($teamComp['teamsize']?'league':'cup')."'>${teamComp['code']}</span>";
		}
		echo "</td></tr>";
	} ?>
	</tbody>
</table>
