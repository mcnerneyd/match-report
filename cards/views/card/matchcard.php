<?php
if (!isset($fixture['card'])) throw new Exception("Card does not exist");

$club = $_SESSION['club'];

$whoami = "";
if ($club and isset($fixture[$club])) {
	$whoami = $fixture[$club];
}

$card = $fixture['card'];
$cardIsOpen=!isset($card[$whoami]['closed']);
list($date,$time) = explode(" ", $fixture['datetime']);
$time = substr($time, 0, 5);
$card['away']['suggested-score'] = emptyValue($card['home']['oscore'], 0);
$card['home']['suggested-score'] = emptyValue($card['away']['oscore'], 0);
$baseUrl=substr(url(), 0, -11)."&cid=${card['id']}&x=".createsecurekey("card${card['id']}");
?>

<script>
var baseUrl = '<?= $baseUrl ?>';
var restUrl = '<?= Uri::create('CardApi') ?>';
var unlockUrl = '<?= url("cid=".$card['id'], 'unlock', 'card') ?>';
var incidentUrl = '<?= url(null, 'player', 'card')."&x=".createsecurekey("card${card['id']}")."&cid=".$card['id'] ?>';
var cardIsOpen = <?= $cardIsOpen ? 'true' : 'false' ?>;
var messages = [
	{
		level: "info",
		title: "Remember",
		text: "When recording goals - include 1v1/Strokes",
	},
	{ 
		level: "success",
		text: "When you are finished, make sure click the 'Submit Card' button",
	},
	{ 
		level: "danger",
		text: "Players added or removed after the match start time are listed in red",
	},
];
function triggerMessage() {
	var msgBox = $('#messages');
	var index = msgBox.data('index') || 0;
	if (index >= messages.length) index = 0;
	var msg = messages[index];
	var msgText = msg['text'];
	if (msg['title']) msgText = "<strong>" + msg['title'] + "</strong> " + msgText;
	msgBox.html(msgText);
	msgBox.attr("class", "alert alert-" + msg['level']);
	msgBox.data('index', index+1);
	setTimeout(triggerMessage, 8000);
}
function flashSubmit() {
	var starttime = <?= $card['datetime'] ?>;
	var now = new Date();
	if (now.getTime() > starttime) {
		var submitButton = $('#submit-button');
		submitButton.toggleClass('flash');
	}
	setTimeout(flashSubmit, 1000);
}

$(document).ready(function() {
	triggerMessage();
	flashSubmit();
	//$('#player-name').combobox();
});
</script>

<style>
.flash {
	background-color: white !important;
	color: green;
}
</style>

<?php if ($card['official']) { ?>
<div class='alert alert-warning alert-small'>
	This matchcard has officially appointed umpires. Tap here for more details.
	<div class='alert-detail'>
		<ul>
			<li>Only umpires can assign penalty cards (Red/Yellow)</li>
			<li>Every player must have a shirt number</li>
			<li>Players must be assigned to card before match</li>
			<li>Matchcards will be closed once the umpire signs the card</li>
		</ul>
	</div>
</div>
<?php } ?>

<div id='messages' class='alert hidden'></div>

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
			<dd><a href='http://cards.leinsterhockey.ie/cards/fuel/public/Report/Card/<?= $fixture['id'] ?>'><?= $fixture['id'] ?></a></dd>
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
		<div id='matchcard-home' class='team <?= $whoami=='home'?'ours':'theirs' ?>' data-side='home'>
			<?php render_team($card['home']); ?>
		</div>

		<div id='matchcard-away' class='team <?= $whoami=='away'?'ours':'theirs' ?>' data-side='away'>
			<?php render_team($card['away']); ?>
		</div>

		<div style='clear:both'/>

	</div>

	<form id='submit-card'>
	<?php if ($cardIsOpen) { ?>
			<a id='submit-button' class='btn btn-success' data-toggle='modal' data-target='#submit-matchcard'>Submit Card</a>
			<a id='postpone' class='btn btn-warning' data-toggle='confirmation' 
				data-title='Mark match as postponed' 
				data-content='This only marks the match as postponed. All postponements must be prior approved by the relevant section committee. Penalties will be imposed for unapproved postponements.'
				data-btn-ok-label='Postponed' data-btn-cancel-label='Cancel'>Postponed</a>
	<?php } ?>
			<a class='btn btn-default' data-toggle='modal' data-target='#add-note'><i class='glyphicon glyphicon-comment'></i> Add Note</a>
	<?php if (!$cardIsOpen) { ?>
			<a class='btn btn-success sign-card' data-toggle='modal' data-target='#submit-matchcard'>
				<i class='glyphicon glyphicon-pencil'></i> Add Signature</a>
	<?php } ?>
		</div>
	</form>
</div>

<?php
// -------------------------------------------------------------------
//		 Dialog Box: Submit Matchcard
// -------------------------------------------------------------------
?>
<div id="submit-matchcard" class="modal fade" role="dialog">
  <div class="modal-dialog">

    <!-- Modal content-->
    <div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal">&times;</button>
				<h4 class="modal-title">Submit Card</h4>
			</div>

      <div class="modal-body" id='submit-form-detail'>

				<?php if ($cardIsOpen) { ?>
				<div class='form-group'>
					<label for='player-name'>Opposition Score</label>
					<input class='form-control' type='number' name='opposition-score' value='99'/>
					<small>In cases where the opposition do not submit their matchcard this value will be submitted
					as the score for the opposition.</small>
				</div>

				<div class='form-group'>
					<label for='player-name'>Umpire</label>
					<input class='form-control' type='text' name='umpire'/>
				</div>

				<div class='form-group'>
					<label for='player-name'>Email for receipt (Optional)</label>
					<input class='form-control' type='email' name='receipt-email'/>
					<small>If you wish to receive an acknowledgement of submission of this card provide an email address here.</small>
				</div>
				<?php } ?>
      </div>

      <div class="modal-body" id='submit-form-signature'>
				<div class='form-group'>
					<label>Signature</label>
					<canvas class='form-control'></canvas>
				</div>

			</div>

      <div class="modal-footer">
        <button type="submit" class="btn btn-success" data-dismiss="modal">
					Submit Matchcard
				</button>
        <a class="btn btn-success">
					<i class='glyphicon glyphicon-pencil'></i> Sign
					<i class='glyphicon glyphicon-chevron-right'></i>
				</a>
        <button type="button" class="btn btn-danger" data-dismiss="modal">Cancel</button>
      </div>
    </div>

  </div>
</div>

<script src='js/matchcard.js' type='text/javascript'></script>
<script src='js/signature_pad.min.js' type='text/javascript'></script>

<?php if (isset($card['notes'])) { ?>
<div id='Notes'>
<h4>Notes</h4>
	<table id='notes'>
		<?php foreach ($card['notes'] as $note) { ?>
		<tr>
			<td><i class='glyphicon glyphicon-comment'></i>&nbsp;<?= $note['user'] ?></td>
			<td><?= $note['note'] ?></td>
		</tr>
		<?php } ?>
	</table>
</div>
<?php } ?>

<div id='signatures'>
<h4>Signatures</h4>
<span class='progress'>Loading Signatures...</span>
</div>

<?php
// ------------------------------------------------------------------------
//		 Signature Pad
// ------------------------------------------------------------------------
?>
<div id='signature'>
	<canvas></canvas>
	<h5>Please sign here</h5>
	<div class='button-box'>
		<button class='btn btn-success' type='submit'>Sign</button>
		<button class='btn btn-warning' type='reset'>Clear</button>
		<a id='cancel-signature' href='#' class='btn btn-danger'>Cancel</a>
	</div>
</div>

<?php
// ------------------------------------------------------------------------
//		 Context Menu
// ------------------------------------------------------------------------
if ($cardIsOpen) {
?>
<div id='context-menu' class='dropdown clearfix'>
	<ul class='dropdown-menu'>
		<li class='dropdown-title'>Player Name<span id='context-close'>&times;</span></li>
		<?php if (!user('umpire')) { ?>
		<li>
			<!--button id='photograph' class='btn btn-primary' disabled>Take Photo</button-->
			<div class='input-group'>
				<input type='number' name='shirt-number' class='form-control'/>
				<span class='input-group-btn'>
					<button id='set-number' class='btn btn-default'>Set Number</button>
				</span>
			</div>
		</li>
		<li class='divider'/>
		<li>
			<div class='btn-group'>
				<button id='add-goal' class='btn btn-success'>Add Goal</button>
				<button id='clear-goal' class='btn btn-success'>Clear Goals</button>
			</div>
		</li>
		<!--li class='divider'/>
		<li disabled>Captain</li>
		<li>Goalkeeper</li>
		<li>Manager</li>
		<li>Physiotherapist</li-->
		<li class='divider'/>
		<?php } ?>

		<?php if (!$card['official'] || user('umpire')) { ?>
		<li class='card-yellow'>Technical - Breakdown</li>
		<li class='card-yellow'>Technical - Delay/Time Wasting</li>
		<li class='card-yellow'>Technical - Dissent</li>
		<li class='card-yellow'>Technical - Foul/Abusive Language</li>
		<li class='card-yellow'>Technical - Bench/Coach/Team Foul</li>
		<li class='card-yellow'>Physical - Tackle</li>
		<li class='card-yellow'>Physical - Dangerous/Reckless Play</li>
		<li class='card-red'>Red Card</li>
		<li class='card-clear'>No Cards</li>
		<?php } ?>

		<li>
				<button id='remove-player' class='btn btn-warning'>Remove Player</button>
		</li>

	</ul>
</div>
<?php } ?>

<?php
// ------------------------------------------------------------------------
//		 Dialog Box: Add New Player
// ------------------------------------------------------------------------
?>
<div id="add-player-modal" class="modal fade" role="dialog">
  <div class="modal-dialog">

    <!-- Modal content-->
    <div class="modal-content">
      <div class="modal-body">
				
        <label for='player-name'>Player Name</label>
				<input class='form-control' type='text' id='player-name'/>

				<!--label>Or select registered player</label>
				<select id='player-name' class='form-control'>
				<?php foreach ($players as $player) echo "<option>$player</option>\n"; ?>
				</select-->
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-success" data-dismiss="modal">Add Player</button>
        <button type="button" class="btn btn-danger" data-dismiss="modal">Cancel</button>
      </div>
    </div>

  </div>
</div>

<?php
// ------------------------------------------------------------------------
//		 Dialog Box: Add Note
// ------------------------------------------------------------------------
?>
<div id="add-note" class="modal fade" role="dialog">
  <div class="modal-dialog">

    <!-- Modal content-->
    <div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal">&times;</button>
				<h4 class="modal-title">Add Note</h4>
			</div>
      <div class="modal-body">
        <label>Note</label>
				<textarea class='form-control' rows='4' cols='50'></textarea>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-success" data-dismiss="modal">Save</button>
        <button type="button" class="btn btn-danger" data-dismiss="modal">Cancel</button>
      </div>
    </div>

  </div>
</div>

<!--
<?php print_r($card); echo "\n\n"; print_r($players); ?>
-->
<?php	//--------------------------------------------------------------
function render_team($team) {
	echo "<table data-club='${team['club']}' data-team='${team['teamx']}'>
	<caption>".$team['team']." 
	<span class='score'>${team['score']}";
	if ($team['suggested-score'] != $team['score']) echo "<span class='score'>".$team['suggested-score']."</span>";
	echo "</span>";
	if (user('admin')) echo "<a class='unlock'>Unlock</a>";
	echo "</caption>

	<tbody>\n";

	$ct = 0;
	foreach ($team['players'] as $player=>$detail) {
		list($lastName, $firstName) = cleanSplit($player);
		$firstName = trim($firstName);

		$class = "player";
		if (isset($detail['ineligible'])) $class.=" ineligible";
		if (isset($detail['late'])) $class.=" late";
		if (isset($detail['deleted'])) $class.=" deleted";

		$imagekey = createsecurekey("image$player${team['club']}");
		$url="image.php?site=".site()."&player=$player&w=200&club=${team['club']}&x=$imagekey";
		echo "		<tr class='$class' data-timestamp='${detail['datetime']}' data-imageurl='$url' data-name='$player'>
			<th>".(isset($detail['number'])?$detail['number']:"")."</th>
			<td data-firstname='$firstName'>$firstName</td>
			<td data-surname='$lastName'>$lastName ";

		echo "<div class='player-annotations'>";
		if ($detail['score'] != 0) echo "<span class='score'>${detail['score']}</span>";
		if (isset($detail['cards'])) {
			foreach ($detail['cards'] as $rycard) {
				$type = "yellow";
				if ($rycard['type'] == 'Red Card') $type = "red";
				echo "<span class='card card-$type'>${rycard['detail']}</span>";
			}
		}
		if (isset($detail['detail'])) {
			$d = $detail['detail'];
				$roles = $d->roles;
				if ($roles) {
					if (in_array('G', $roles)) echo "<span class='role role-goalkeeper'>GK</span>";
					if (in_array('C', $roles)) echo "<span class='role role-captain'>C</span>";
					if (in_array('M', $roles)) echo "<span class='role role-manager'>M</span>";
					if (in_array('P', $roles)) echo "<span class='role role-physio'>P</span>";
				}

		}
		echo "</div>";

		echo "</td>
		</tr>\n";
		$ct++;
	}

	for (;$ct<16;$ct++) { echo "		<tr class='filler hidden-xs'><td colspan='4'>&nbsp;</td></tr>\n"; }

	echo "	</tbody>

		</table>\n";

	if (isset($team['umpire'])) {
		echo "<dl><dt>Umpire</dt><dd>".$team['umpire']."</dd></dl>";
	}
}

function cleanSplit($name) {
	$names = explode(",", $name, 2);

	if (count($names) == 1) return array($names[0], "");

	return $names;
}
