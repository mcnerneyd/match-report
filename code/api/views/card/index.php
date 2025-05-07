<script>
$(document).ready(function() {
	$('#results-table').DataTable({
		"searching":false,
		"lengthChange":false,
		"order":[[0,'desc']],
		"columns":[
			null,
			null,
			null,
			{ "orderable":false },
			{ "orderable":false },
			null,
		]
		});
	$('#results-table').show();
	$('#results-table tbody').on('click', 'tr', function(e) {
		e.preventDefault();
		window.location.href = '<?= Uri::create('card') ?>/'+$(this).data('id');
	});
});
</script>
<style>
#results-table tr {
	cursor: pointer;
}
#results-table small {
	font-style: italic;
	font-size: 65%;
	color: green;
}
#results-table tr td:nth-child(4) {
	text-align:right;
	padding-right:0;
}
#results-table tr td:nth-child(5) {
	padding-left:0;
}
</style>

<table id='results-table' class='table table-condensed' style='display:none'>
	<thead>
		<tr><th>Date</th><th>Competition</th><th>Home</th><th></th><th></th><th>Away</th></tr>
	</thead>
	<tbody>
<?php
$first = true;
foreach ($results as $card) {
	$id = $card['fixture_id'] ?: "n".$card['id'];
	echo "<tr data-id='$id'>
		<td>".substr($card['date'], 0,10)."</td>
		<td>${card['competition']}</td>
		<td>${card['home_name']} ${card['home_team']} <small>(${card['home_count']})</small></td>
		<td>${card['home_score']}</td>
		<td>&nbsp;v&nbsp;${card['away_score']}</td>
		<td>${card['away_name']} ${card['away_team']} <small>(${card['away_count']})</small></td></tr>";
}
?>
	</tbody>
</table>

