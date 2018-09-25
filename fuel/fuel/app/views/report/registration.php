<style>
.warning {
	background: #f7e8e9;
	color:#7c0000;
}
</style>
<h3><strong>Report Date:</strong> <?= date('l j F, Y') ?></h3>

<h4>Registrations</h4>
<table>
	<thead>
		<tr>
			<th>Club</th>
			<th># Registered</th>
			<th>Team Sizes</th>
		</tr>
	</thead>

	<tbody>
<?php	
		foreach (Model_Club::find('all') as $club) {
			$ts = $club->getTeamSizes();

			if (!$ts) continue;

			$reg = Model_Registration::find_before_date($club->name, $reportDate);

			echo "<tr";
			if (!$reg) echo " class='warning'";
			echo "><td>".$club->name."</td><td>".count($reg)."</td><td>";
			foreach ($ts as $teamSize) {
				echo $teamSize." ";
			}
			echo "</td></tr>";
		}
		?>
	</tbody>
</table>

<h4>Matchcards (from the last 6 days)</h4>
<table>
<?php
	$finish = date();
	$start = strtotime("-6d", $finish);
	foreach (Model_Card::query()->where('date','>',$start)
		->where('date','<',$finish)->get() as $card) {
		echo "<tr><td>".$card->id."</td></tr>\n";
	}
?>
</table>


