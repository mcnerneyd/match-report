<?php
class Tag {
	private $tag;
	private	$attrs;
	private $content; 
	public function __construct($tag, $attrs = array()) {
		$this->tag = $tag;
		$this->attrs = $attrs;
		$this->content = "";
	}

	public function render() {
		return html_tag($this->tag, $this->attrs, $this->content);
	}

	public function addAttr($attr, $value) {
		if (isset($this->attrs[$attr])) {
			$value = $this->attrs[$attr]." $value";	
		}

		$this->attrs[$attr] = $value;
	}

	public function setContent($content) {
		$this->content = $content;
	}
}
?>
<script>
$(document).ready(function() {
	var des_width = $('#gauge').width() * 11;
	var sw = screen.width/des_width;
	if (sw < 1.0) {
		$('.card')
			//.css('transform-origin','150px 0')
			.css('margin-top','-36px')
			.css('transform','scale('+sw+')');
	}
	$('.card').first().addClass('active').show();
	$('#next-button').click(function() {
		if ($('.card.active').next('.card').length) {
			$('.card.active').removeClass('active').hide().next('.card').addClass('active').show();
		}
	});
	$('#prev-button').click(function() {
		if ($('.card.active').prev('.card').length) {
			$('.card.active').removeClass('active').hide().prev('.card').addClass('active').show();
		} else {
			goToSearch();
		}
	});
	$('#search-button').click(goToSearch);
	function goToSearch() {
		window.location.href="<?= $searchUrl ?>";
	}
});
</script>

<style>
@import url('https://fonts.googleapis.com/css?family=Over+the+Rainbow|Droid+Sans');
#openProfiler { display:none; }
.container { margin-top: 0; }
.card .label {padding: 0 5px; color: black; font-size: 10pt; font-family: 'Droid Sans'; }
td.label {border: none; font-weight: normal; }
td.label:first-of-type { padding-left: 0; }
th.label { text-align:center; display:table-cell; line-height:19px; }
.card td { font-family: 'Over the Rainbow', cursive; font-size: 13pt; color: #000f88; 
	height: 6mm; 
	line-height: 0; padding-top: 5px; }
.card .identifier { position:absolute; bottom: 0px; right: 10px; font-size: 80%; }
h2 { font-size: 11pt; font-weight: 700; margin-bottom: 0; }
body { background-color: slategray; }
.card { width: 11cm; 
	min-height:20cm;
	background-color: white;
	position: absolute; 
	left: 50%; 
	padding: 20px 0 20px 20px; 
	margin: 60px 0 60px -5.5cm;
	display: none; }
.card * { font-family: "Droid Sans"; font-size: 10pt; letter-spacing: -1px; }
.round-button {
	display:block;
	width:30px;
	height:30px;
	line-height:34px;
	border-radius:50%;
	background-color:#dd8800;
	text-align:center;
	position:fixed;
	box-shadow: 0 0 3px gray;
	opacity: 80%;
	left: 50px;
	bottom: 10px;
	color: white;
	z-index: 1000;
	margin-left: -2px;
}
.round-button * { font-size:16px; }
.round-button:nth-of-type(2) { left: 10px; }
.round-button:nth-of-type(3) { right: 10px; left: auto; }
.round-button:nth-of-type(4) { right: 50px; left: auto; }
#card-header th { text-transform: none; font-weight: normal; }
#card-header td { width: 4.9cm; }
#card-header td:nth-of-type(2) { width: 2.9cm; }
#officials { border-spacing: 5px; border-collapse: separate; margin-left: -6px; }
#officials td { width:4.15cm; margin-top: 3px; margin-bottom: 2px; padding-top: 7px; }
#team-support { margin-top: -1px; }
#team-support td { width: 4.2cm; }
#team-support td:first-child { width: 1.62cm; font-family: "Droid Sans"; font-size: 10pt; color: black; padding-top: 0; }
table { border-spacing: 0; border-collapse: collapse; margin-top: 3px; }
td.number { padding-left: 0; font-size: 11pt; text-align: center; padding-top: 6px; letter-spacing: 1px; }
th { text-transform: uppercase; text-align:center; }
th.label { text-transform: none; font-weight: normal; }
#div-select th { width:16px; }
#competition td:nth-of-type(2) { width: 3.8cm; }
#competition td.number { width: 5mm !important; }
#scores td { width: 3.9cm; }
#scores td.number { width: 1cm; }
table td { border: 1px solid black; }
#player-lists table { display: inline; }
#player-lists table:first-child { display: inline; margin-right: -2px; }
#player-lists table th { width: 4.2cm; text-transform: uppercase; padding-bottom: 5px; }
#player-lists table .number { text-align: center; width: 8mm; text-transform: none; }
#player-lists table td { max-width: 4.2cm; height: 6mm; overflow: hidden; }
#player-lists table td.late { color: red; }
td { padding-left: 5px; white-space: nowrap; }
img { margin-left: 2cm; }
#marker { width: 6mm; margin: 0; position:absolute; top:152px; left:<?= 43 + 17 * ($card['div-number'] - 1) + ($card['div-number'] > 10 ? ($card['div-number'] - 10) * 6 : 0) ?>px; }
p { margin: 0; }

#goals tr:first-child td, #cards tr:first-child td { font-family: "Droid Sans"; font-size: 10pt; 
	text-transform: uppercase;
	text-align: center;
	letter-spacing: -1px; color: black; }
#goals td { width: 4.9cm; }
#goals th, #cards th { font-weight:normal; border: 1px solid black; }
#cards td:first-child { width: 8mm; }
#cards td:nth-child(2) { width: 4.8cm; }
#cards td:nth-child(3) { width: 4.2cm; }
#cards tr:last-child td { max-width: 9.5cm; white-space: normal;
		font-family: "Droid Sans"; font-size: 10pt; font-weight: bold; color: black;
		line-height: 14px; text-align: center; }
.card ol { padding: 0 20px 0 15px !important; margin-left:20px; }
.card li { margin: 10px 20px 5px -20px; }
</style>

<div id='gauge' style='width:1cm;height:1cm;'></div>

<a class='round-button' id='comment-button'>
	<i class='glyphicon glyphicon-comment'></i>
</a>

<a class='round-button' id='prev-button'>
	<i class='glyphicon glyphicon-arrow-left'></i>
</a>

<a class='round-button' id='next-button'>
	<i class='glyphicon glyphicon-arrow-right'></i>
</a>

<a class='round-button' id='search-button'>
	<i class='glyphicon glyphicon-search'></i>
</a>

<div class='card'>
<div class='identifier'><?= $card['id']." / ".$card['fixture_id'] ?></div>

<?= Asset::img("matchcard_header.png", array("width"=>"200px")) ?>
<?php	if ($card['div-number'] > 0) echo Asset::img("loop.png", array("id"=>"marker")); ?>

<table id='card-header'>
	<tr>
		<td class='label'>Date:</td>
		<td class='number' title='<?= $card['date'] ?>'><?= $card['date']->format('%d/%m/%Y') ?></td>
		<td class='label'>Venue:</td>
		<td><?= $card['home']['club'] ?></td>
	</tr>
</table>

<table id='scores'>
	<tr>
		<th class='label'>Home</th>
		<th colspan='2'>Result:</th>
		<th class='label'>Away</th>
	</tr>
	<tr>
		<td><?= $card['home']['club']." ".$card['home']['team'] ?></td>
		<td class='number'><?= $card['home']['goals'] ?></td>
		<td class='number'><?= $card['away']['goals'] ?></td>
		<td><?= $card['away']['club']." ".$card['away']['team'] ?></td>
	</tr>
</table>

<table id='div-select'>
	<tr>
		<td class='label'>Div:</th>
		<?php for ($i=1;$i<=14;$i++) echo "<th class='label'>$i</th>"; ?>
	</tr>
</table>

<table id='competition'>
	<tr>
		<td class='label'>League/Cup:</td>
		<td><?= $card['competition'] ?></td>
		<td class='label'>CARDS:</td>
		<td class='label'>Yellow</td>
		<td class='number'></td>
		<td class='label'>Red</td>
		<td class='number'></td>
	</tr>
</table>

<table id='officials'>
	<tr>
		<td class='label'>Umpires:</td>
		<td><?= isset($card['home']['umpire']) ? $card['home']['umpire'] : "" ?></td>
		<td><?= isset($card['away']['umpire']) ? $card['away']['umpire'] : "" ?></td>
	</tr>
	<tr>
		<td class='label'>Captains:</td>
		<td><?= isset($card['home']['captain']) ? $card['home']['captain'] : "" ?></td>
		<td><?= isset($card['away']['captain']) ? $card['away']['captain'] : "" ?></td>
	</tr>
</table>

<p>Teams: <em>(Print clearly and include <strong>full first and surnames</strong>)</em></p>
<div id='player-lists'>

	<table>
		<tr>
			<th class='number'>No.</th>
			<th>Home</th>
			<th class='number'>No.</th>
			<th>Away</th>
		</tr>
		<?php 
			$homekeys = array_keys($card['home']['players']);
			$awaykeys = array_keys($card['away']['players']);

			for ($i=0;$i<16;$i++) {
				echo "<tr>";
				$homenumber = new Tag('td', array('class'=>'number'));
				$homeplayer = new Tag('td');

				if (isset($homekeys[$i])) {
					$homenumber->setContent($card['home']['players'][$homekeys[$i]]['number']);
					$homeplayer->setContent($homekeys[$i]);
					if (\Auth::has_access('admin.[all]')) {
						$homeplayer->addAttr('title', $card['home']['players'][$homekeys[$i]]['date']);
						if ($card['date'] < $card['home']['players'][$homekeys[$i]]['date']) {
							$homeplayer->addAttr('class', 'late');
						}
					}
				}
				echo $homenumber->render().$homeplayer->render();

				$awaynumber = new Tag('td', array('class'=>'number'));
				$awayplayer = new Tag('td');
				if (isset($awaykeys[$i])) {
					$awaynumber->setContent($card['away']['players'][$awaykeys[$i]]['number']);
					$awayplayer->setContent($awaykeys[$i]);
					$awayplayer->addAttr('title', $card['away']['players'][$awaykeys[$i]]['date']);
					if ($card['date'] < $card['away']['players'][$awaykeys[$i]]['date']) {
						$awayplayer->addAttr('class', 'late');
					}
				}
				echo $awaynumber->render().$awayplayer->render();

				echo "<tr>";
			} ?>
	</table>

</div>

	<table id='team-support'>
		<tr>
			<td>Coach:</td>
			<td></td>
			<td></td>
		</tr>
		<tr>
			<td>Manager:</td>
			<td></td>
			<td></td>
		</tr>
		<tr>
			<td>Physio:</td>
			<td></td>
			<td></td>
		</tr>
	</table>
</div>

<div class='card'>
<h2>Goal Scorers' Names (mandatory for all games)</h2>

<table id='goals'>
	<tr><th>Home</th><th>Away</th></tr>
	<?php 
		$hkeys = array_keys($card['home']['scorers']);
		$akeys = array_keys($card['away']['scorers']);
		for ($i=0;$i<7;$i++) {
			$hscore = "";
			$ascore = "";
			if (isset($hkeys[$i])) {
				$k = $card['home']['scorers'][$hkeys[$i]];
				$hscore = $hkeys[$i];		
				if ($k > 1) $hscore .= " &times; $k";
			}
			if (isset($akeys[$i])) {
				$k = $card['away']['scorers'][$akeys[$i]];
				$ascore = $akeys[$i];		
				if ($k > 1) $ascore .= " &times; $k";
			}

			echo "<tr><td>$hscore</td><td>$ascore</td></tr>";
		} ?>
</table>

<h2>Yellow/Red Card Report:</h2>

<table id='cards'>
	<tr><th>No.</th><th>Name</th><th>Club</th></tr>
	<?php for ($i=0;$i<7;$i++) {
			echo "<tr><td></td><td></td><td></td></tr>";
	}?>
	<tr><td colspan='3'>Send separate yellow/red card report form for each player
		within 48 hours to admin@leinsterhockey.ie</td></tr>
</table>

<ol>
<li>Match cards must be completed in full. Please ensure full names
are detailed. Incomplete or late receipt of cards will incur a fine/</li>
<li>Home team is responsible for texting result and sending the
Match Card to the relevant Section Officer* within 72 hours
<br>(*see www.leinsterhockey.ie)</li>
<li>Details of rules/regulations available on <a href='www.leinsterhockey.ie'>www.leinsterhockey.ie</a></li>
<li>Games must start no later than 45 mins after scheduled start time.</li>
<li>Copies of Yellow / Red Card report available on <a href='www.leinsterhockey.ie'>www.leinsterhockey.ie</a></li>
</ol>

</div>

