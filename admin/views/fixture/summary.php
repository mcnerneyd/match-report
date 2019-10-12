<style>
	td, th { width: 40px; height: 40px; text-align: center; vertical-align: middle; line-height: 12px; }
	td { border: 1px dotted #888; }
	tr:first-child { border-bottom: 1px solid black; }
	tr td:first-child { font-size: 85%; text-align:right; padding-right: 5px; }
	tr td:first-child, tr th:first-child { border-left: none; border-right: 1px solid black; }
	.time {font-size: 70%;}
	.result { position: relative; 
	background: linear-gradient(to top right,
           rgba(0,0,0,0) 0%,
           rgba(0,0,0,0) calc(50% - 0.8px),
           rgba(0,0,0,1) 50%,
           rgba(0,0,0,0) calc(50% + 0.8px),
           rgba(0,0,0,0) 100%);
	}
	.result span:first-child { position: absolute; bottom: 5px; left: 5px; }
	.result span:last-child { position: absolute; top: 5px; right: 5px; }
	.fixture { color: #bbb; }
	.blank {
		background: repeating-linear-gradient(-45deg,
      white,
      white 4px,
      #888 5px);
	}
</style>
<?php foreach ($fixtures as $division=>$detail) {

	echo "<div id='summary-table'><h3>$division</h3>";

	echo "<table><tr><th></th>";

	foreach ($detail['teams'] as $team=>$name) {
		echo "<th>$team</th>";
	}
	echo "</tr>\n";

	foreach ($detail['teams'] as $team=>$name) {
		echo "<tr><td>$name</td>";
		foreach ($detail['teams'] as $oppo=>$name) {
			if (isset($detail['fixtures'][$team.$oppo])) {
				$fixture = $detail['fixtures'][$team.$oppo];

				if ($fixture['played'] == 'yes') {
					echo "<td class='result'><span>".$fixture['home_score']."</span><span>".$fixture['away_score']."</span></td>\n";
				} else {
					$m = substr($fixture['datetime']->format("%B"), 0, 1);
					$t = $fixture['datetime']->format("%H%M");
					echo "<td class='fixture'>".$m.$fixture['datetime']->format('%d')."<br><span class='time'>$t</span></td>";
				}

		//echo "<!-- "; print_r($fixture);echo " -->\n";
			} else {
				echo "<td class='blank'></td>";
			}
		}
		echo "</tr>\n";
	}

	echo "</table></div>";

}
