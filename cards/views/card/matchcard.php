<?php
if (!isset($fixture['card'])) throw new Exception("Card does not exist");

$card = $fixture['card'];
list($date,$time) = explode(" ", $fixture['datetime']);
$time = substr($time, 0, 5);
?>

<div id='match-card' data-fixtureid='<?= $fixture['id'] ?>' data-cardid='<?= $card['id'] ?>'>

	<h1 id='competition' data-code='<?= $card['competition-code'] ?>'><?= $card['competition'] ?></h1>

	<detail data-timestamp='<?= $fixture['date'] ?>'>
		<?php if (isset($card['away']['locked'])) { ?>
		<dl id='lock-code'>
			<dt>Lock Code</dt>				
			<dd><?= count($card['away']['players'])."/".$card['away']['locked'] ?></dd>
		</dl>
		<?php } ?>

		<dl id='fixtureid'>
			<dt>Fixture ID</dt>				
			<dd><?= $fixture['id'] ?></dd>
		</dl>

		<dl id='cardid'>
			<dt>Card ID</dt>				
			<dd><?= $card['id'] ?></dd>
		</dl>

		<dl id='date'>
			<dt>Date</dt>				
			<dd><?= $card['date'] ?></dd>
		</dl>

		<dl id='time'>
			<dt>Time</dt>				
			<dd><?= $time ?></dd>
		</dl>
	</detail>

	<div id='teams'>
		<div id='matchcard-home' class='team'>
			<?php render_team($card['home']); ?>
		</div>

		<div id='matchcard-away' class='team'>
			<?php render_team($card['away']); ?>
		</div>

</div>

<script src='js/matchcard.js' type='text/javascript'></script>

<!--
<?php print_r($fixture); ?>
-->
<?php	//-------------------------------------------------------------------------------------------

function render_team($team) {
	echo "<table>
	<caption data-club='${team['club']}' data-team='${team['teamx']}'>".$team['team']." <span class='score'>${team['score']}</span></caption>

	<tbody>\n";

	$ct = 0;
	foreach ($team['players'] as $player=>$detail) {
		list($lastName, $firstName) = explode(",", $player, 2);
		$firstName = trim($firstName);

		$class = "player";
		if (isset($detail['ineligible'])) $class.=" ineligible";

		$imagekey = createsecurekey("image$player${team['club']}");
		$url="image.php?site=".site()."&player=$player&w=200&club=${team['club']}&x=$imagekey";
		echo "		<tr class='$class' data-timestamp='${detail['datetime']}' data-imageurl='$url'>
			<th>".(isset($detail['number'])?$detail['number']:"")."</th>
			<td data-firstname='$firstName'>$firstName</td>
			<td data-surname='$lastName'>$lastName ";

		if ($detail['score'] != 0) echo "<span class='score'>${detail['score']}</span>";
		if (isset($detail['card'])) echo "<span class='card card-${detail['card']}'>${detail['card']}</span>";

		echo "</td>
		</tr>\n";
		$ct++;
	}

	for (;$ct<16;$ct++) { echo "		<tr class='filler'><td colspan='4'>&nbsp;</td></tr>\n"; }

	echo "	</tbody>

		</table>\n";
}
