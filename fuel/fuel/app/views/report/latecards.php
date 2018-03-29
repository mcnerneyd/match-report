<table id='latecards' class='table table-condensed table-striped'>
	<thead>
	<tr>
		<th>Date</th>
		<th>Competition</th>
		<th>Team</th>
		<th>Reason</th>
		<th>Card</th>
	</tr>
	</thead>

	<tbody>
	<?php foreach ($faults as $fine) {
			echo "<tr>";
				echo "<td>".date("Y.m.d G:i", $fine['cardtime'])."</td>";
				echo "<td>${fine['competition']}</td>";
				echo "<td>${fine['team']}</td>";
				echo "<td>${fine['message']}</td>";
				echo "<td>
							<a href='".Uri::create("Report/Card/${fine['fixture_id']}")."'>#${fine['fixture_id']}</a>
						</td>";
			echo "</tr>";
	} ?>
	</tbody>

</table>
