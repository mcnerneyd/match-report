<!--
<?php
		if ($fixture['home']['club'] == $_SESSION['club']) {
			$whoami = 'home';
		} else {
			$whoami = 'away';
		}

		$team = $fixture[$whoami];

?>-->
<style>
table { width: 100%; }
table tr.regular, table tr.other { border: 1px dotted #bbb; }
table td { padding: 10px; }
table tr.selected td { border: 1px solid #555; background: #bfa; }
tr.regular { font-weight: bold; }
tr.other { font-style: italic; }
tr.summary td { padding: 0; text-align: center; background: #584; color: white; border: 1px solid #584; }
tr.warning td { padding: 0; text-align: center; background: #822; color: white; border: 1px solid #822; }
#count { position: fixed; top:80px; right:0; left:0; text-align:center; 
	color: #120; font-size: 200pt; font-weight: bold; }
.buttons { position:absolute; top:0; right:0; }
#fixture { position:relative; }
.alert { margin-top: 10px; margin-bottom: 10px; }
</style>
<script>
function addSorted($selector, $item, $sort) {
	var $list = $($selector).children();

	if ($sort && $list.length>0) {
		var t = $item.text().trim();
		var aft = $list.filter(function() {
			return (t > $(this).text().trim());
		});
		
		if (aft.length>0) {
			aft.last().after($item);
		} else {
			$($selector).prepend($item);
		}
		return;
	}

	$($selector).append($item);
}

$(document).ready(function () {

	$('tr.regular').each(function(index) {
		var c = $(this).hasClass('last') ? 'L' : 'P';
		$(this).children('td').first().append("<span class='badge pull-right'>"+c+"</span>");
	});

	function counts(flash) {
			var ct = $("tr.selected").length;
			if (flash && ct > 0) $("#count").text(ct).show().fadeOut();

			if (ct == 0) {
				$(".summary td").hide();
			} else if (ct == 1) {
				$(".summary td").show().text('1 player has been selected for this team');
			} else {
				$(".summary td").show().text(ct + ' players have been selected for this team');
			}

			<?php if ($fixture['date'] > time()) { ?>
			if (ct >= 7) {
				$(".warning td").hide();
			} else {
				$(".warning td").show().text((7-ct) + ' more players required before <?= $fixture['datetime'] ?>');
			}
			<?php } else {
				$ct = 0;
				if (isset($fixture['card'])) {
					foreach ($fixture['card'][$whoami]['players'] as $player=>$detail) {
						if (strtotime($detail['datetime']) < $fixture['date']) $ct++;
					} 
				}
				echo "var mct=$ct;" ?>
			if (mct >= 7) {
				$(".warning td").hide();
			} else if (mct == 0) {
				$(".warning td").show().text('No players on card at start time');
			} else if (mct == 1) {
				$(".warning td").show().text('Only 1 player on card at start time');
			} else {
				$(".warning td").show().text('Only ' + mct + ' players on card at start time');
			}
			<?php } ?>
	}

	counts(false);

	function selectPlayer(playerRow, select) {
		if (playerRow.hasClass('selected') == select) return;

		var playerName = playerRow.data('name');

		playerRow.remove();

		if (select) {
			$.post("<?= url(null, "player") ?>&cid=<?= $fixture['cardid'] ?>&player="+playerName);
			
			addSorted("tbody.selected", playerRow, false);
			playerRow.addClass('selected');
		} else {
			$.post("<?= url(null, "player") ?>&cid=<?= $fixture['cardid'] ?>&player="+playerName+"&remove");

			if (playerRow.hasClass('regular')) {
				addSorted("tbody.regular", playerRow, true);
			} else {
				addSorted("tbody.other", playerRow, true);
			}
			playerRow.removeClass('selected');
		}

		playerRow.fadeIn(400, counts(true));
	}

	$(document).on('click', 'tr', function () {
		$(this).fadeOut(400, function() {

			var playerName = $(this).data('name');

			if (playerName === undefined) {
				throw "No player associated with this row";
			}

			selectPlayer($(this), !$(this).hasClass('selected'));
		});
	});

	$('#button-copy').on('click', function() {
		$('tr.last').each(function() {
			selectPlayer($(this), true);
		});
	});

	$('#button-clear').on('click', function() {
		$('tr.selected').each(function() {
			selectPlayer($(this), false);
		});
		$.ajax('<?= Uri::create("CardApi/Team") ?>', {
			method: "DELETE",
			data: { cardid: <?= $fixture['cardid'] ?> },
		});
			
	});

	$('tr.summary td').hide();

	$('.alert').fadeOut(3000);

	$('tr').css( 'cursor', 'pointer' );

	$('#button-copy').notify('New! Add all players from last match',
		{position:"bottom"});
});	// .ready
</script>

<div id='fixture' data-id='<?= $fixture['cardid'] ?>'>
	<div class='row'>
		<p class='subtitle col-md-6 col-xs-12'><?= $fixture['home']['team'] ?> v <?= $fixture['away']['team'] ?></p>
		<p class='subtitle col-md-6 col-xs-12'><?= date('j F, Y', $fixture['date']) ?></p>

	</div>

	<a href='<?= url("cid=${fixture['cardid']}&x=".createsecurekey('card'.$fixture['cardid']), "lock", "card") ?>' class='btn btn-success'>Submit Team</a>
	<a id='button-clear' class='btn btn-danger'>Clear</a>
	<a id='button-copy' class='btn btn-primary' title='Copy players from last match'>Last Match</a>

	<!--div class='alert alert-info'><strong>Important</strong> You do not need to submit your team before your match. You just need to select the players below to avoid the fine.</div-->

	<h1><?php
		echo $team['team'];
	?></h1>

	<span id='count'></span>

	<?php 
		$selected = array();
		$regular = array();
		$others = array();

		debug("$whoami Players:".print_r($players, true));
	
		foreach ($players as $player=>$detail) {
			$players[$player]['regular'] = in_array($team['teamnumber'], $detail['teams']);
		}

		if (isset($fixture['card'][$whoami]['players'])) {
			$selected=array();
			
			foreach (array_keys($fixture['card'][$whoami]['players']) as $player) {
				if (!$player) continue;
				$selected[] = cleanName($player);
			}
		}

		foreach ($players as $player=>$detail) {
			if (in_array($player, $selected)) {
				continue;
			} else if ($detail['regular']) {
				$regular[] = $player;
			} else {
				$others[] = $player;
			}
		} 
		asort($regular);
		asort($others);	
		?>
	<table>
			<tr class='warning'><td></td></tr>
		<tbody class='selected'>
		<?php foreach ($selected as $name) {
			$clx = 'selected';
			if (isset($players[$name]) && $players[$name]['regular']) $clx .= ' regular';
			if (isset($lastPlayers)) { 
				if (in_array($name, $lastPlayers)) $clx .= " last";
			} ?>
		<tr class='<?= $clx ?>' data-name='<?= $name ?>'>
			<td><?= $name ?></td>
		</tr>
		<?php } ?>
		</tbody>
			<tr class='summary'><td></td></tr>
			<tr><td>The following players have played for this team already this year</td></tr>
		<tbody class='regular'>
		<?php foreach ($regular as $name) { 
			$clx = "regular";
			if (isset($lastPlayers)) {
				if (in_array($name, $lastPlayers)) $clx .= " last";
			}
			?>
		<tr class='<?= $clx ?>' data-name='<?= $name ?>'>
			<td><?= $name ?></td>
		</tr>
		<?php } ?>
		</tbody> <!-- .regular -->
			<tr><td>The following players are registered, but have not played for this team this year</td></tr>
		<tbody class='other'>
		<?php foreach ($others as $name) { ?>
			<tr class='other' data-name='<?= $name ?>'>
				<td><?= $name ?></td>
			</tr>
		<?php } ?>
		</tbody>
	</table>
</div>

<!--
<?php 
 echo "Fixture:\n";
 print_r($fixture);
 echo "\nPlayers:\n";
 print_r($players); ?>

-->

