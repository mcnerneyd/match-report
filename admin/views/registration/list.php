<!--
<?php print_r(Model_Club::find_by_name($club)->getTeamSizes(false)); ?>
-->
<script>
$(document).ready(function() {
	$('#registration-table').DataTable({
		paging: false,
		ordering: false,
	});
	$('#registration-table tbody').show();
	$('#change-date').click(function() {
		if ($('#date-select').is(':visible')) {
			$('#date-select').hide();
		} else {
			$('#date-select').show();
		}
	});
	$('#date-select').datepicker({
		dateFormat: "yy-mm-dd",
		showOtherMonths: true,
		selectOtherMonths: true,
		onSelect: function(d, i) {
				window.location = "./Registration/Registration?c=<?=$club?>&d=" + d;
			}
		});
	$('#view-registration a').click(function(e) {
		e.preventDefault();
		window.location = "./Registration/Registration?c=" + $("#registration-club select").val();
	});
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
#date-select {
	position: absolute;
	z-index: 1000;
	display: none;
}
img.membership {
	width: 20px;
	height: 20px;
	margin: -2px 0 0 5px;
}
</style>
<table id='registration-table' class='table'>
	<thead>
		<th/>
		<th>Player</th>
		<th>Matches</th>
		<th>Team</th>
		<th></th>
	</thead>
	<tbody display='none'>
<!-- <?php print_r($all); ?> -->
<p>Players eligible on or after <?= $ts->format("%A %e, %B %G") ?> for <?= $club ?></p>
<button id='change-date' class='btn btn-primary'>Select Date <i class="far fa-calendar-alt"></i></button>
<div id='date-select'></div>
<?php
echo "<!-- ".print_r($registration, true)." -->";
$ct=1;
foreach ($registration as $player) 
{
	$class = "player";
	if (isset($player['status'])) $class .= " ${player['status']}";
	echo "<tr>
		<td>$ct</td>
		<td class='$class'>${player['name']}";
	if (isset($player['membershipid']) && $player['membershipid']) {
		echo "&nbsp;<img class='membership' src='http://cards.leinsterhockey.ie/public/assets/img/hockeyireland-icon.png'/>";
	}
	echo "</td><td>";

	foreach ($player['history'] as $match) {
		$date = date('d.n', strtotime($match['date']));
		if ($match['code'][0] == 'D') $cls = 'match-pill-league';
		else $cls = 'match-pill-cup';
		echo "<a class='match-pill $cls' href='#'><span>${match['code']}</span><span>$date</span></a>";
	}

	$score = $player['score'];
	echo "</td>
		<td>${player['team']}";
	echo "</td><td>";
	if (Session::get('site') === 'lhamen' && $score < 99) echo $score;
	echo "</td>";
	echo "</tr>";

	$ct++;
}
?>
	</tbody>
</table>
