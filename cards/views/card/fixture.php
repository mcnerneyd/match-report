<!--
<?= print_r($fixture, true) ?>
-->
<?php
if ($fixture['home_club'] == $_SESSION['club']) {
	$whoami = 'home';
} else if ($fixture['away_club'] == $_SESSION['club']) {
	$whoami = 'away';
} else {
	echo $_SESSION['club'] . " is not one of the teams in this match";
	return;
}

$team = \Arr::get($fixture, "card.$whoami.team");
$fixtureDate = DateTime::createFromFormat(DATE_ISO8601, $fixture['datetimeZ']);


?>
<style>
	table {
		width: 100%;
	}

	table#players .score {
		margin-left: 1rem;
	}

	table#players tr {
		border-bottom: 5px solid white;
	}

	table#players tbody tr td {
		font-weight: bold;
		border-radius: 0.5em;
		background: #ffe;
		padding: 10px;
	}

	table#players tbody.selected td {
		background: #1c5;
		color: white;
	}

	table#players tbody.buttons td {
		background: none;
		border-radius: 0;
	}

	tr.other {
		font-style: italic;
	}

	tr.summary td {
		padding: 0;
		text-align: center;
		background: #584;
		color: white;
		border: 1px solid #584;
	}

	tr.warning td {
		padding: 0;
		text-align: center;
		background: #822;
		color: white;
		border: 1px solid #822;
	}

	#count {
		position: fixed;
		top: 80px;
		right: 0;
		left: 0;
		text-align: center;
		color: #120;
		font-size: 200pt;
		font-weight: bold;
	}

	.xbuttons {
		position: absolute;
		top: 0;
		right: 0;
	}

	#fixture {
		position: relative;
		overflow-y: scroll;
	}

	.alert {
		margin-top: 10px;
		margin-bottom: 10px;
	}

	img.membership {
		width: 20px;
		height: 20px;
		margin: -2px 0 0 5px;
	}

	h1 {
		padding: 10px 0;
	}

	h2 {
		margin-top: 0.75rem;
		font-size: 0.75rem;
		font-style: italic;
	}
</style>
<script>
	function counts(flash) {
		var ct = $(".selected tr").length;
		if (flash && ct > 0) $("#count").text(ct).show().fadeOut();

		if (ct == 0) {
			$(".summary td").hide();
		} else if (ct == 1) {
			$(".summary td").show().text('1 player has been selected for this team');
		} else {
			$(".summary td").show().text(ct + ' players have been selected for this team');
		}

		$('#players .score').html(ct + " player" + (ct != 1 ? "s" : ""));

		<?php if ($fixtureDate->getTimestamp() > time()) { ?>
			if (ct >= -1) {
				$(".warning td").hide();
			} else {
				$(".warning td").show().text((7 - ct) + ' more players required before <?= strftime("%H:%M on %A, %B %e, %Y", $fixture['date']) ?>');
			}
		<?php } else {
			$ct = 0;
			if (isset($fixture['card'])) {
				foreach ($fixture['card'][$whoami]['players'] as $player => $detail) {
					if (strtotime($detail['datetime']) < $fixtureDate->getTimestamp())
						$ct++;
				}
			}
			echo "var mct=$ct;" ?>
			if (mct >= -1) {
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

	var data = <?= json_encode($data) ?>;

	<?php
	$date = $data['date'];
	$date = DateTime::createFromFormat(DATE_ISO8601, $date);
	$date = $date->getTimestamp();
	$initialDate = strtotime("first thursday of " . date("M YY", $date));
	if ($initialDate > $date) {
		$initialDate = strtotime("-1 month", $date);
		$initialDate = strtotime("first thursday of " . date("M YY", $initialDate));
	}
	$startDate = strtotime("+1 day", $initialDate);
	echo "// $startDate $date\n";
	?>

	function addSorted($selector, $item, $sort) {
		var $list = $($selector).children();

		if ($sort && $list.length > 0) {
			var t = $item.text().trim();
			var aft = $list.filter(function () {
				return (t > $(this).text().trim());
			});

			if (aft.length > 0) {
				aft.last().after($item);
			} else {
				$($selector).prepend($item);
			}
			return;
		}

		$($selector).append($item);
	}

	$(document).ready(function () {

		$.getJSON('/api/1.0/registration/list.json?s=<?= $data['section'] ?>&t=<?= $data['team'] ?>&d=<?= $fixtureDate->format("Ymd") ?>&x=<?= $fixture['competition'] ?>',
			function (jsonx) {
				if (typeof jsonx === 'undefined') return;
				const json = jsonx['data']
				var selected = <?= json_encode(array_keys($fixture['card'][$whoami]['players'])) ?>;
				const m1 = moment.unix(jsonx['latest'])
				const m2 = moment(data['date'], 'YYYYMMDD')
				if (m2.diff(m1) < 0) {
					console.warn("Latest registration for " + data['club'] + " was submitted on " + m1.format('YYYY-MM-DD') +
						" which is after the date for this matchcard. The player list will not be the latest.")
					$('#fixture>div').after(`<div class="alert alert-warning" role="alert">
					<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="bi bi-exclamation-triangle-fill flex-shrink-0 me-2" viewBox="0 0 16 16" role="img" aria-label="Warning:">
						<path d="M8.982 1.566a1.13 1.13 0 0 0-1.96 0L.165 13.233c-.457.778.091 1.767.98 1.767h13.713c.889 0 1.438-.99.98-1.767L8.982 1.566zM8 5c.535 0 .954.462.9.995l-.35 3.507a.552.552 0 0 1-1.1 0L7.1 5.995A.905.905 0 0 1 8 5zm.002 6a1 1 0 1 1 0 2 1 1 0 0 1 0-2z"/>
					</svg>
					Latest registration for ${data['club']} was submitted on ${m1.format('YYYY-MM-DD')}
					which is after the date for this matchcard.<br>This player list will not be the latest.</div>`)
				}

				console.log(json.length + " player(s) eligible", m1.format(moment.defaultFormat), m2.format(moment.defaultFormat), m2.diff(m1));
				for (var i = 0; i < json.length; i++) {
					var p = json[i];
					var group = selected.includes(p['name']) ? 'selected' : 'regular';

					var cls = "";
					if (p['membershipid']) cls = "<span class='member'></span>";

					var html = "<tr class='player' data-name='" + p['name'] + "'";

					if (p['history'].length > 0) {
						html += " data-played='yes'";

						if (p['history'][0].hasOwnProperty('last')) {
							html += " data-last='yes'";
						}
					}

					html += "><td>" + p['name'];

					if (p['membershipid']) html += "<img class='membership' src='http://cards.leinsterhockey.ie/assets/img/hockeyireland-icon.png'/>";

					html += "</td></tr>";

					$('#players .' + group).append(html);
				}
				counts(false);
			})
			.fail(function () {
				$('#players').append('<div class="alert alert-danger" role="alert">Failed to get player list</div>');
			});

		$('tr.regular').each(function (index) {
			var c = $(this).hasClass('last') ? 'L' : 'P';
			$(this).children('td').first().append("<span class='badge pull-right'>" + c + "</span>");
		});

		function addNote(msg) {
			var cardId = <?= $fixture['cardid'] ?>;
			$.post('/api/1.0/card/note',
				{ 'card_id': cardId, 'msg': msg })
				.done(function () {
					window.location = '/';
				});
		}

		$('#postpone').click(function () {
			addNote('Match Postponed');
		});

		$('#select-all').click(function () {
			$('.player').show();
			$('.buttons a').removeClass('active');
			$('#select-all').addClass('active');
		});

		$('#select-played').click(function () {
			$('.player').hide();
			$('.player[data-played=yes]').show();
			$('.buttons a').removeClass('active');
			$('#select-played').addClass('active');
		});

		$('#select-last').click(function () {
			$('.player').hide();
			$('.player[data-last=yes]').show();
			$('.buttons a').removeClass('active');
			$('#select-last').addClass('active');
		});

		$('#select-unplayed').click(function () {
			$('.player').show();
			$('.player[data-played=yes]').hide();
			$('.buttons a').removeClass('active');
			$('#select-unplayed').addClass('active');
		});


		counts(false);

		function selectPlayer(playerRow, select) {
			var cardId = <?= $fixture['cardid'] ?>;

			var playerName = playerRow.data('name');
			var group = playerRow.closest('tbody');


			if (select) {
				if (group.hasClass('selected')) return;

				$.post('/api/1.0/cards/' + cardId, { 'player': playerName });

				playerRow.remove();
				addSorted("tbody.selected", playerRow, false);
			} else {
				if (!group.hasClass('selected')) return;

				$.ajax('/api/1.0/cards/' + cardId, {
					data: { 'player': playerName },
					type: 'DELETE'
				});

				playerRow.remove();
				addSorted("tbody.regular", playerRow, true);
			}

			playerRow.fadeIn(400, counts(true));
		}

		$(document).on('click', 'tr[data-name]', function () {
			var playerName = $(this).data('name');

			if (playerName === undefined) {
				// FIXME temporary - should just return where no player associated
				var html = $(this).html();
				if (html.includes("The following players")) return;
				if (html.includes("been selected for this team")) return;

				throw "No player associated with this row: " + html;
			}

			$(this).fadeOut(400, function () {

				var group = $(this).closest('tbody');
				selectPlayer($(this), !group.hasClass('selected'));
			});
		});

		$('#button-copy').on('click', function () {
			$('tr.last').each(function () {
				selectPlayer($(this), true);
			});
		});

		$('#button-clear').on('click', function () {
			$('tr.selected').each(function () {
				selectPlayer($(this), false);
			});
			$.ajax('<?= Uri::create("CardApi/Team") ?>', {
				method: "DELETE",
				data: { cardid: <?= $fixture['cardid'] ?> },
			});

		});

		$('tr.summary td').hide();

		$('.alert').fadeOut(3000);

		$('tr').css('cursor', 'pointer');

	});	// .ready
</script>

<div id='fixture' data-id='<?= $fixture['cardid'] ?>'>
	<div class='row'>
		<p class='subtitle col-8'><?= $fixture['card']['home']['team'] ?> v <?= $fixture['card']['away']['team'] ?></p>
		<p class='subtitle col-4 text-right'>
			<span class='text-right d-none d-md-inline'><?= $fixtureDate->format('j F, Y') ?></span>
			<span class='text-right d-md-none'><?= $fixtureDate->format('j.n.y') ?></span>
		</p>
	</div>

	<a href='<?= url("cid={$fixture['cardid']}&x=" . createsecurekey('card' . $fixture['cardid']), "lock", "card") ?>'
		class='btn btn-success'>Submit Team</a>
	<!--a id='button-copy' class='btn btn-primary' title='Copy players from last match'>Last Match</a-->
	<button id='postpone' class='btn btn-warning float-right' data-bs-toggle='confirmation' data-placement='bottom'
		data-title='Mark match as postponed'
		data-content='Postponements must be prior approved by the relevant section committee to avoid a penalty'
		data-btn-ok-label='Postponed' data-btn-cancel-label='Cancel'>Postponed</button>

	<span id='count'></span>

	<table id='players'>
		<thead>
			<tr>
				<th><?= $fixture['card'][$whoami]['team'] ?>
					<span class='score'></span>
				</th>
			</tr>
		</thead>
		<tbody class='selected'>
		</tbody>
		<tbody class='buttons'>
			<tr>
				<td>
					<div class='btn-group'>
						<a id='select-all' class='btn btn-info active'>All</a>
						<a id='select-played' class='btn btn-info'>Played</a>
						<a id='select-last' class='btn btn-info'>Last</a>
						<a id='select-unplayed' class='btn btn-info'>Unplayed</a>
					</div>
					<a class='btn btn-danger float-right'>Clear</a>
				</td>
			</tr>
		</tbody>
		<tbody class='regular'>
		</tbody>
	</table>
</div>