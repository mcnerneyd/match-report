<style>
.table>tbody>tr>th { border-top: none; }
.btn .glyphicon { vertical-align: -1px; }
th, td { text-align: right; }
th:first-child, td:first-child { text-align: left; }
</style>
<h1>Adminstration</h1>
<h2>Clubs</h2>

<div class='tab' id='clubs'>
	<table class='table'>
		<tr>
			<th>Club</th>
			<th>Code</th>
		</tr>
	<?php foreach ($clubs as $club) {
		echo "<tr>
			<td>${club['name']}</td>
			<td>${club['code']}</td>
			</tr>\n";
	} ?>
	</table>
</div>	<!-- #clubs -->

