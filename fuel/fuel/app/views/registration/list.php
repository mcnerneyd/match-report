<!--
<?php print_r($registration); ?>
-->
<script>
$(document).ready(function() {
	$('#registration-table').DataTable({
		paging: false,
		ordering: false,
	});
	$('#registration-table tbody').show();
});
</script>
<style>
.deleted {
	text-decoration: line-through;
	color: red;
}
.added {
	font-style: italic;
	color: green;
}
</style>
<table id='registration-table' class='table'>
	<thead>
		<th/>
		<th>Player</th>
		<th>Matches</th>
		<th>Team</th>
	</thead>
	<tbody display='none'>
<!-- <?php print_r($all); ?> -->
<?php
echo "From: ".$base->format("%Y%m%d")." to ".$ts->format("%Y%m%d");
$ct=1;
foreach ($registration as $player) 
{
	$class = "player";
	if (isset($player['status'])) $class .= " ${player['status']}";
	echo "<tr>
		<td>$ct</td>
		<td class='$class'>${player['name']}</td>
		<td></td>
		<td>${player['team']}</td>
		</tr>";
	$ct++;
}
?>
	</tbody>
</table>
