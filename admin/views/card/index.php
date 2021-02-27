<script>
$(document).ready(function() {
	$('#results-table').DataTable({
		"searching":false,
		"lengthChange":false,
		"order":[[0,'desc']],
		"columns":[
			null,
			null,
			{ "orderable":false },
			null,
		]
		});
	$('#results-table').show();
	$('#results-table tbody').on('click', 'tr', function(e) {
		e.preventDefault();
		window.location.href = '<?= Uri::create('cards') ?>/'+$(this).data('id');
	});
});
</script>
<style>
#results-table tr {
	cursor: pointer;
}
</style>

<table id='results-table' class='table table-condensed' style='display:none'>
	<thead>
		<tr><th>Date</th><th>Home</th><th></th><th>Away</th></tr>
	</thead>
	<tbody>
<?php
foreach ($results as $card) {
	$id = $card['fixture_id'] ?: "n".$card['id'];
	echo "<tr data-id='$id'>
		<td>".substr($card['date'], 0,10)."</td>
		<td>${card['home_name']} ${card['home_team']}</td>
		<td>&nbsp;v&nbsp;</td>
		<td>${card['away_name']} ${card['away_team']}</td></tr>";
}
?>
	</tbody>
</table>

