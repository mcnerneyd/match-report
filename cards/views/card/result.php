<!--
<?php print_r($fixture); ?>
-->
<?php 
if (!isset($fixture['card'])) throw new Exception("Card does not exist");

$card = $fixture['card'];
$whoami = false;
global $strictProcessing;
$strictProcessing = false;
if (isset($fixture['competition-strict']) && $fixture['competition-strict'] == 'yes') {
	$strictProcessing = true;
}
if (isset($_REQUEST['strict'])) $strictProcessing = true;
if (user() and isset($fixture[user()])) {
	$whoami = $fixture[user()];
}

function renderHeadshot($cardid, $club, $player, $detail, $allowCtx = false) {
		//$strictProcessing = $GLOBALS['strictProcessing'];
		global $strictProcessing;

		echo "<!-- ".print_r($detail, true)." -->";

		if ($_SESSION['club'] != $club) $allowCtx = false;
		if (user('umpire')) $allowCtx = true;

		debug("Club:$club ".trim(preg_replace('/\s\s+/', ' ', print_r($detail, true))));
		$extraclass = "";
		if (isset($detail['ineligible'])) $extraclass .= " ineligible";
		if ($strictProcessing) {
			if (!isset($detail['number']) or !$detail['number']) {
				$extraclass .= " numberless";
			}
		}
		$profileUrl = "";
		if ($allowCtx) {
			$key = createsecurekey("profile$player$club");
			$profileUrl = url("card=".$cardid."&name=$player&club=$club&x=$key","profile","player"); 
			?>
		<a class='edit-player' data-toggle="modal" data-target="#player-detail-modal" data-player='<?= $player ?>' data-club='<?= $club ?>' data-cardid='<?= $cardid ?>' data-profile='<?= $profileUrl ?>'/>
		<?php } ?>
		<figure class='player<?= $extraclass ?>' title='<?= $player ?>'>
			<?php 
			if (isset($detail['number'])) echo "<span class='number'>".$detail['number']."</span>";
			if ($detail['score'] > 0) echo " <span class='score'>".$detail['score']."</span>";
			if (isset($detail['card'])) echo "<span data-card='".$detail['card']."' class='penalty-card glyphicon glyphicon-bookmark'></span>";
			$imagekey = createsecurekey("image$player$club");
			$cleanPlayer = explode(',', $player);
			?>
			<img src='image.php?site=<?= site() ?>&player=<?= $player ?>&w=200&club=<?= $club ?>&x=<?= $imagekey ?>'/>
			<?php if (count($cleanPlayer) > 1) { ?>
			<figcaption><?= $cleanPlayer[1] ?></figcaption>
			<?php } ?>
			<figcaption><?= $cleanPlayer[0] ?></figcaption>
		</figure>
<?php if ($allowCtx) { ?>
		</a>
<?php }
}

function clean($name) {
	if (!strpos($name, ',')) return $name;

	$nameParts = explode(',', $name);
	return str_replace(' ','&nbsp;',trim(ucwords(strtolower($nameParts[1] . ' ' . $nameParts[0]))));
} ?>
<style>
	span[data-card='red'] { color:red; vertical-align:middle; }
	span[data-card='yellow'] { color:yellow; vertical-align:middle; }
	#matchcard-summary th { border:1px solid black; padding: 5px 20px; border-collapse:collapse; }
	#matchcard-summary { margin-bottom:20px; width:100%; }
	#matchcard-players td { padding:2px 0px; }
	#matchcard-away, #matchcard-home { /*overflow: auto;*/ padding-left: 0; padding-right: 0; }
	#matchcard-detail { position:relative; width:100%; }
	#matchcard-detail dt { display:none; }
	#matchcard-detail dd:nth-of-type(2) { top:0;right:0; position:absolute; }
	.add-player { min-height: 135px; float: left; width: 100px; height: 100%; margin: 0 10px 10px 0; padding: 10px 10px 20px 10px; position: relative; }
	.add-player img { width: 100%; padding-top: 25px; }
	figure.player { min-height: 135px; float: left; width: 100px; height: 100%; margin: 0 10px 10px 0; padding: 10px 10px 20px 10px; box-shadow: 2px 2px 2px #bbb; border: 1px solid #bbb; position: relative; }
	figcaption { font-size: 80%; position: absolute; width: 80px; bottom: 3px; text-align:center; text-overflow:ellipsis; overflow:hidden; white-space: nowrap; }
	figcaption:nth-of-type(1) { font-size:70%; bottom: 15px; display:block; background:white; width:60px; margin-left:10px; border-radius:3px; }
	figure .penalty-card { position: absolute; top:0; right: 10px; }
	.score { margin-right: 20px; color: green; background: white; border: 3px solid green; font-weight: bold;
		text-align:center; vertical-align:middle; padding:0 8px; border-radius:20px;}
	#contextMenu .score, figure .score { border:none; background:green; color:white;
		border-radius:10px; padding:0 6px; }
	figure .score { border:none; background:green; color:white; position: absolute; 
		top:13px; left: 13px; border-radius:10px; padding:0 6px; }
	figure .number { border:none; background:white; color: black; position: absolute; left:8px; bottom:18px;
		border-top-right-radius: 5px; padding-right: 5px; font-size: 75%; }
	figure img { max-width: 100%; min-width:100%; max-height: 100px; }
	
	figure.ineligible, figure.ineligible figcaption:nth-of-type(1) {
		background: repeating-linear-gradient( 45deg, #fdd, #fdd 10px, orange 10px, orange 20px);
	}
	figure.numberless { border: 5px solid red; }
	h1 { border-bottom: 1px solid black; }
	h2 { clear: both; margin-bottom: 25px; }
  #rycards, #notes { clear: both; }
	#contextMenu { position:absolute; display:none; }
	.dropdown-title { font-weight:bold; text-align:center; padding:5px 10px; }
	.dropdown-menu { position: fixed !important; top:75px; left:30px; }
	.lockcode { font-weight:bold; font-size: 1.5em; color: #45a; clear: left; }
	.alert { margin: 20px 0; }
	.with-errors { font-size: 0.8em; margin-top: 0; }
	.xalert-danger { position: absolute; z-index: 1000; margin: 0; }
	.hint { margin: 15px 0; }
	#submit-form { position: relative; }
</style>
<script>
		$(document).ready(function () {
			//$('.alert-danger').delay(4000).fadeOut(1000);
		});
</script>
<a href='<?= url(null, 'index') ?>#results' class='btn btn-primary'><span class='glyphicon glyphicon-chevron-left'></span>&nbsp;Back</a>

<div id='matchcard' data-id='<?= $card['id'] ?>'>
	<dl id='matchcard-detail'>
		<dd>Card #<?= $card['id'] ?>/<?= $fixture['id'] ?></dd>
		<?php if ($whoami) { ?>
		<dd>Lock Code<br><span class='lockcode'><?= count($card[$whoami]['players'])."/".$card[$whoami]['locked'] ?></span></dd>
		<?php } ?>

		<dd><?= $card['date'] ?></dd>
	</dl>

<?php
if ($whoami) {
	if (!isset($card[$whoami]['closed'])) { 
		$whoareyou = ($whoami == 'home' ? 'away' : 'home');
			$oppositionClub = $card[$whoareyou]['club'];
			?>

		<form class='form-inline' id='submit-form'>
			<!--div class='alert alert-danger'><strong>Important</strong> Do not forget to Submit the card</div-->

			<div class='form-group col-md-4'>
				<label class='sr-only' for='umpire-box'>Umpire</label>
				<input name='umpire' type='text' class='form-control' placeholder='Umpire' id='umpire-box' required
					data-error='You must identify your umpire'/>
				<div class="help-block with-errors"></div>
			</div>
			<div class='form-group col-md-4'>
				<label class='sr-only' for='score-box'>Score</label>
				<input name='oppositionscore' type='number' class='form-control' placeholder='Opposition Score' id='score-box' required
					data-error='You must provide the score for <?= $oppositionClub ?>'/>
				<div class="help-block with-errors"></div>
			</div>
			<button id='submit-card-button' type='submit' class='btn btn-success'>Submit Card</button>
			<button class='btn btn-info' disabled><i class='glyphicon glyphicon-comment'></i> Add Comment</button>
		</form>

		<script>
		$(document).ready(function() {
			$('#submit-form .btn-success').notify("Do not forget to Submit the card", {className: 'warn'});
		});
		</script>

	<?php } else if ($card[$whoami]['closed'] !== true) { ?>
		<h4 id='countdown' style='display:none'>Card will automatically submit in ...</h4>
		<script>
		function timestr() {
			var endTime = <?= $card[$whoami]['closed'] ?>;
			var totalSec = endTime - Math.floor(new Date().getTime()/1000);

			if (totalSec < 0) return null;

			var hours = "0" + parseInt( totalSec / 3600 ) % 24;
			var minutes = "0" + parseInt( totalSec / 60 ) % 60;
			var seconds = "0" + totalSec % 60;

			hours = hours.substring(hours.length - 2);
			minutes = minutes.substring(minutes.length - 2);
			seconds = seconds.substring(seconds.length - 2);

			return hours + ":" + minutes + ":" + seconds;
		}
		
		$(document).ready(function () {
			window.setInterval(function () {
					var tstr = timestr();
					if (tstr != null) {
						$('#countdown').text("Card will automatically submit in " + tstr);
						$('#countdown').show();
					} else {
						$('#countdown').hide();
						window.clearInterval();
					}
				}, 1000);

		});
		</script>
	<?php }
}	// if ($whoami)...
?>

<?php if ($strictProcessing) { ?>
	<div class="alert alert-info" data-help='strict-processing'>Important: Strict processing applies to this card</div>
	<script>
	$(document).ready(function() {
		if ($('.numberless').length > 0) {
			$('#submit-card-button').attr('disabled','disabled');
			$('[data-help="strict-processing"]').before("<div class='alert alert-danger' data-help='adding-shirt-numbers'>Submit Card button is disabled</strong> because there are players without assigned shirt numbers</div>");
		}
	});
	</script>
<?php } else { ?>
	<div class="hint text-info"><strong>Hint</strong> Tap the player to add goals, cards or update their picture</div>
<?php } ?>
	<h1><?= $card['competition'] ?></h1>

	<div id='matchcard-home' class='col-md-6 col-xs-12'>
			<h2>
			<?php if (user('admin') && isset($card['home']['locked'])) {
				echo "<a class='btn btn-danger btn-sm' data-toggle='confirmation' data-title='Unlock team?' href='".url('home&cid='.$card['id'], 'unlock', 'card')."'><i class='fa fa-unlock' aria-hidden='true'></i></a>";
			}?>
			<?= $card['home']['team'] ?>
			<span class='score pull-right'><?= $card['home']['score'] ?></span></h2>

<?php
	foreach ($card['home']['players'] as $player=>$detail) 
		renderHeadshot($card['id'], $card['home']['club'], $player, $detail, !isset($card['home']['closed']));

	if (user('umpire') or ($whoami == 'home' and !isset($card['home']['closed']))) {
?>
		<a class='add-player' data-toggle='modal' data-target='#add-player-modal' data-side='home'><img src='img/add-user.png'/></a>
<?php } ?>
	</div>

	<div id='matchcard-away' class='col-md-6 col-xs-12'>
			<h2>
			<?php if (user('admin') && isset($card['away']['locked'])) {
				echo "<a class='btn btn-danger btn-sm' data-toggle='confirmation' data-title='Unlock team?' href='".url('away&cid='.$card['id'], 'unlock', 'card')."'><i class='fa fa-unlock' aria-hidden='true'></i></a>";
			}?>
			<?= $card['away']['team'] ?>
			<span class='score pull-right'><?= $card['away']['score'] ?></span></h2>

<?php
	foreach ($card['away']['players'] as $player=>$detail) 
		renderHeadshot($card['id'], $card['away']['club'], $player, $detail, !isset($card['away']['closed']));

	if (user('umpire') or ($whoami == 'away' and !isset($card['away']['closed']))) {
?>
		<a class='add-player' data-toggle='modal' data-target='#add-player-modal' data-side='away'><img src='img/add-user.png'/></a>
<?php } ?>
	</div>

	<?php if ($card['rycards']) { ?>
	<div id='rycards'>
	<h3>Cards</h3>

	<table class='table-condensed'>
		<tr>
			<th/>
			<th>Player</th>
			<th>Club</th>
			<th>Detail</th>
		</tr>
		<?php foreach ($card['rycards'] as $rycard) { ?>
		<tr>
			<td><img width='18px' src='<?= $rycard['type'] == 'Red Card'?'img/red-card.png':'img/yellow-card.png' ?>'></td>
			<td><?= $rycard['player'] ?></td>
			<td><?= $rycard['club'] ?></td>
			<td><?= $rycard['detail'] ?></td>
		</tr>
		<?php }?>
	</table>
	</div> <!-- #rycards -->
	<?php } ?>

	<?php if (isset($card['notes'])) { ?>
	<div id='notes'>
	<h3>Notes</h3>
	<table class='table-condensed'>
		<tr>
			<th>User</th>
			<th>Note</th>
		</tr>
		<?php foreach ($card['notes'] as $note) { ?>
		<tr>
			<td><i class='glyphicon glyphicon-comment'></i> <?= $note['user'] ?></td>
			<td><?= $note['note'] ?></td>
		</tr>
		<?php } ?>
	</table>
	</div> <!-- #notes -->
	<?php } ?>

</div>	<!-- #matchcard -->

<div id="contextMenu" class="dropdown clearfix">
	<ul class="dropdown-menu" role="menu" aria-labelledby="dropdownMenu" style="display:block;position:static;margin-bottom:5px;">
		<li class='dropdown-title'>Player Name</li>

		<?php if (!user('umpire')) { ?>
		<li class='score-up'><a><span class='score'>1</span>Scored</a></li>
		<li class='score-clear'><a><span class='score'>0</span>No Score</a></li>
		<?php } ?>

		<?php if (!$strictProcessing || user('umpire')) { ?>
		<li class='divider'></li>
		<li class='card-yellow'><a><img width='18px' src='img/yellow-card.png'/> Technical - Breakdown</a></li>
		<li class='card-yellow'><a><img width='18px' src='img/yellow-card.png'/> Technical - Delay/Time Wasting</a></li>
		<li class='card-yellow'><a><img width='18px' src='img/yellow-card.png'/> Technical - Dissent</a></li>
		<li class='card-yellow'><a><img width='18px' src='img/yellow-card.png'/> Technical - Foul/Abusive Language</a></li>
		<li class='card-yellow'><a><img width='18px' src='img/yellow-card.png'/> Technical - Bench/Coach/Team Foul</a></li>
		<li class='card-yellow'><a><img width='18px' src='img/yellow-card.png'/> Physical - Tackle</a></li>
		<li class='card-yellow'><a><img width='18px' src='img/yellow-card.png'/> Physical - Dangerous/Reckless Play</a></li>
		<li class='card-red'><a><img width='18px' src='img/red-card.png'/> Red Card</a></li>
		<li class='card-clear'><a><img width='18px' src='img/no-card.png'/> No Cards</a></li>
		<?php } ?>

		<?php if (!user('umpire')) { ?>
		<li class='divider'></li>
		<li><a class='view-profile'><span class='glyphicon glyphicon-user'></span> Profile</a></li>
		<?php } ?>
	</ul>
</div>

<div id="add-player-modal" class="modal fade" role="dialog">
  <div class="modal-dialog">

    <!-- Modal content-->
    <div class="modal-content">
      <div class="modal-body">
        <label for='player-name'>Player Name</label>
				<input class='form-control' type='text' id='player-name'/>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-success" data-dismiss="modal">Add Player</button>
        <button type="button" class="btn btn-danger" data-dismiss="modal">Cancel</button>
      </div>
    </div>

  </div>
</div>

<script>
$(document).ready(function () {
	$.fn.validator.Constructor.FOCUS_OFFSET = 60;

	$('#submit-form').validator().on('submit', function(e) {
		if (!e.isDefaultPrevented()) {
			e.preventDefault();
			var score = $('#score-box').val() || 0;
			var umpire = $('#umpire-box').val();
			var myscore = $('#matchcard-<?= $whoami ?> .score').text();
			$.post("<?= url(null, "commit") ?>&cid=<?= $card['id'] ?>&umpire="+umpire+"&score="+score+"&myscore="+myscore+"&x=<?= createsecurekey('card'.$card['id']) ?>",
					null, 
					function() { location.reload(); });
		}
	});

	$('a.add-player').on('click', function(e) {
		$('#add-player-modal').data('side', $(this).data('side'));
	});

	$('#add-player-modal .btn-success').on('click', function(e) {
		var club = '<?= $fixture['home']['club'] ?>';
		if ($('#add-player-modal').data('side') == 'away') club = '<?= $fixture['away']['club'] ?>';
		$.post("<?= url(null, "player") ?>&ineligible="+$('#player-name').val() +"&cid=<?= $card['id'] ?>&club=" + club,
				null, 
				function() { location.reload(); });
	});

	$(document).on("click", ".edit-player", function(e) {
		console.log("Edit player clicked");
		var cm = $('#contextMenu');
		cm.css("top", Math.max(0, (($(window).height() - cm.outerHeight()) / 2) + $(window).scrollTop()) + "px");
		cm.css("left", Math.max(0, (($(window).width() - cm.outerWidth()) / 2) + $(window).scrollLeft()) + "px");
		cm.show();
		cm.find('.dropdown-title').html($(this).data('player'));
		cm.find('.view-profile').attr('href',$(this).data('profile'));
		var scoreTag = $(this).find('.score');
		var score = (scoreTag.length > 0 ? parseInt(scoreTag.text()) : 0) + 1;
		cm.find('.score-up .score').text(score);

		cm.data('score', score);
		cm.data('club', $(this).data('club'));
		cm.data('cardid', <?= $card['id'] ?>);

		e.preventDefault();
  });

	$("#contextMenu a").on("click", function(e) {
		debugger;
		var url = '<?= url(null, 'player', 'card') ?>';
		var playerName = $('#contextMenu .dropdown-title').text();
		var club = $('#contextMenu').data('club');
		url += '&player=' + playerName;
		url += '&club=' + club;
		url += '&cid=<?= $fixture['cardid'] ?>';
		url += '&x=<?= createsecurekey('card'.$fixture['cardid']) ?>';

		<?= "// ${fixture['cardid']}" ?>

		var	key = $(this).parent().attr('class');

		switch (key) {
			case 'score-up':
				url += '&goal=' + ($('#contextMenu').data('score'));
				break;
			case 'score-clear':
				url += '&goal=0';
				break;
			case 'card-yellow':
				url += '&yellow=' + $(this).text();
				break;
			case 'card-red':
				url += '&red=' + $(this).text();
				break;
			case 'card-clear':
				url += '&clearcards';
				break;
		}

		document.location = url;
	});
  
  $('body').on("click", function() {
     $('#contextMenu').hide();
  });
});
</script>

