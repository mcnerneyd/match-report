<!--
<?php print_r($history); ?>
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
.match-pill {
	margin: 2px;
	display: block;
	float: left;
}
.match-pill span {
	font-size: 80%;
	color: black;
	padding: 3px;
	background: #fffff8;
	border: 2px solid #b08080;
	border-left: 0;
	border-radius: 0 5px 5px 0;
}
.match-pill span:first-child {
	border-radius: 5px 0 0 5px;
	background: #b08080;
	color: white;
	border-color: #b08080;
	border-right: 0;
}
.match-pill-cup span {
	border-color: #8080b0 !important;
}
.match-pill-cup span:first-child {
	background: #8080b0;
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
		<td>";
	if (isset($history[$player['name']])) {
		$matches = $history[$player['name']];
		foreach ($matches as $match) {
			$date = date('d.n', strtotime($match['date']));
			if ($match['code'][0] == 'D') $cls = 'match-pill-league';
			else $cls = 'match-pill-cup';
			echo "<a class='match-pill $cls' href='#'><span>${match['code']}</span><span>$date</span></a>";
		}
	}
	echo "</td>
		<td>${player['team']}</td>
		</tr>";
	$ct++;
}
?>
	</tbody>
</table>
