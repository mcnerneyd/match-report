<style>
.table>tbody>tr>th { border-top: none; }
.btn .glyphicon { vertical-align: -1px; }
th, td { text-align: right; }
th:first-child, td:first-child { text-align: left; }
</style>
<h1>Adminstration</h1>
<h2>Competitions</h2>

<div class='tab' id='competitions'>
	<table class='table'>
		<tr><th>Competition</th>
			<th>Team Size</th>
			<th>Stars</th></tr>
	<?php foreach ($competitions as $competition) {
		echo "<tr><td>${competition['name']}</td>
			<td>".($competition['teamsize']>=0?$competition['teamsize']:"")."</td>
			<td>".($competition['teamstars']>=0?$competition['teamstars']:"")."</td></tr>\n";
	} ?>
	</table>
</div>	<!-- #competitions -->

