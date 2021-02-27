<script>
	$(document).ready(function() {
		$('#fixtures-table').DataTable({
			columnDefs:[{targets:[2,4],orderable:false},
				],
		});
		$('#fixtures-table').show();
		$('#fixtures-table').on('change', 'input[type=checkbox]', function() {
			tr = $(this).closest('tr');
			fixtureId = tr.data('fixtureid');
			$.ajax('<?= Uri::create("fixtures/") ?>' + fixtureId + "?show=" + ($(this).checked?"true":"false"),
				{
					method:'PUT',
				});
		});
		$('#fixtures-table tbody').on('click', 'tr', function(e) {
			$("#issue-fine select[name='reason']").val('None');
			$('#issue-fine .radio:nth-of-type(1) .team-name').text($('td:nth-child(4)', this).text());
			$('#issue-fine .radio:nth-of-type(2) .team-name').text($('td:nth-child(6)', this).text());
			$('#issue-fine .form-group:nth-of-type(1) p').text('#' + $(this).data('fixtureid'));
			$('#issue-fine .form-group:nth-of-type(2) p').text($('td:nth-child(3)', this).text());
			$('#issue-fine .form-group:nth-of-type(3) p').text($('td:nth-child(2)', this).text());
			$('#issue-fine input[name="fixtureid"]').val($(this).data('fixtureid'));
			setFine();
			$('#issue-fine').modal('show');
		});
		$("#issue-fine select[name='reason']").change(setFine);
		$("#issue-fine button[type='submit']").click(function() {
			$.post('<?= Uri::create('fine') ?>', $('#issue-fine form').serialize(), function(data) {
				window.location.reload();
				$.notify({message: 'Fine Issued'}, {
					placement: { from: 'top', align: 'right' },		
					delay: 1000,
					animate: {
						enter: 'animated bounceInDown',
						exit: 'animated bounceOutUp'
					},
					type: 'success'});
				});
		});
		function setFine() {
			$("#issue-fine input[name='amount']").val($("option:selected", this).data('value'));
		}
	});
</script>
<style>
#fixtures-table tr {
	cursor: pointer;
}
</style>

<div class='form-group command-group hidden-sm hidden-xs'>
	<a class='btn btn-success' href='<?= Uri::create('fixtures?flush=true') ?>'><i class="fas fa-sync"></i> Refresh Fixtures</a>
	<a class='btn btn-warning' href='<?= Uri::create('fixture/repair') ?>'><i class="fas fa-briefcase-medical"></i> Repair Fixtures</a>
</div>

<!-- <?= print_r($cards, true) ?> -->

<table id='fixtures-table' class='table table-condensed table-striped' style='display:none'>
	<thead>
	<tr>
		<th style='width:1em'>Fixture</th>
		<th style='width:1em'>Date</th>
		<th style='width:1em'>Competition</th>
		<th>Home</th>
		<th style='width:4em'>Score</th>
		<th>Away</th>
		<th>Show</th>
	</tr>
	</thead>

	<tbody>
	<?php foreach ($cards as $card) {
		$fixture = json_decode(json_encode($card), True);
		$score = "";

		if (!isset($card['fixtureID'])) {
			Log::error("Invalid card - no fixture\n".print_r($card, true));
			continue;
		}

		if (!isset($fixture['played'])) Log::info("No play: ".print_r($fixture, true));
		else if ($fixture['played'] == 'yes') $score = "${fixture['home_score']} - ${fixture['away_score']}";
		echo "<tr data-fixtureid='".$card['fixtureID']."'>
			<td>
				<a href='".Uri::create("cards/${card['fixtureID']}")."'>#${fixture['fixtureID']}</a>
			</td>
			<td>".($card['datetime']?$card['datetime']->format():"")."</td>
			<td>${fixture['competition']}</td>
			<td>${fixture['home']}</td>
			<td>$score</td>
			<td>${fixture['away']}</td>
			<td><input type='checkbox' ".($fixture['show']?'checked':'')."/></td>
		</tr>";
	} ?>
	</tbody>
</table>

<div class='modal' id='issue-fine'>
	<div class='modal-dialog'>
		<div class='modal-content'>
			<div class='modal-header'>
				<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
				<h4 class='modal-title'>Issue Fine</h4>
			</div>
			<div class='modal-body'>
			<form class='form-horizontal'>
					<input type='hidden' name='fixtureid'/>

					<div class='form-group'>
						<label class='control-label col-sm-2'>Fixture</label>
						<div class='col-sm-4'>
							<p class='form-control-static'>#433234 2017-04-02 Division 3</p>
						</div>
					</div>

					<div class='form-group'>
						<label class='control-label col-sm-2'>Competition</label>
						<div class='col-sm-4'>
							<p class='form-control-static'>#433234 2017-04-02 Division 3</p>
						</div>
					</div>

					<div class='form-group'>
						<label class='control-label col-sm-2'>Date/Time</label>
						<div class='col-sm-4'>
							<p class='form-control-static'>2017-05-01</p>
						</div>
					</div>

					<div class='form-group'>
						<label class='control-label col-sm-2'>Team</label>
						<div class='col-sm-10'>
							<div class='radio'>
								<label>
									<input type='radio' name='optionsTeam' value='home' checked>
									<strong>Home</strong> - <span class='team-name'></span>
								</label>	
							</div>	
							<div class='radio'>
								<label>
									<input type='radio' name='optionsTeam' value='away'>
									<strong>Away</strong> - <span class='team-name'></span>
								</label>	
							</div>	
						</div>
					</div>

					<div class='form-group'>
						<label class='control-label col-sm-2'>Reason</label>
						<div class='col-sm-8'>
							<select class='form-control' name='reason'>
								<option value='None'>--- Select Reason ---</option>
								<option data-value='50'>Late Postponement Request</option>
								<option data-value='10'>Failed to update website</option>
								<option data-value='20'>Matchcard Incomplete</option>
								<option data-value='25'>Matchcard Late</option>
								<option data-value='50'>Unauthorized Postponement</option>
								<option data-value='125'>Ineligible Player</option>
							</select>
						</div>
					</div>

					<div class='form-group'>
						<label class='control-label col-sm-2'>Amount</label>
						<div class='col-sm-4'>
							<div class='input-group'>
								<span class='input-group-addon'><i class='glyphicon glyphicon-euro'?></i></span>
								<input class='form-control' type='number' name='amount'/>
							</div>
						</div>
					</div>

					<div class='form-group'>
						<label class='control-label col-sm-2'>Notes</label>
						<div class='col-sm-8'>
							<textarea class='form-control' name='additional' rows='3' cols='90' placeholder='Additional information such as specific player names or points to be deducted'></textarea>
						</div>
					</div>


				</form>
			</div>

			<div class='modal-footer'>
				<button type='button' class='btn btn-default' data-dismiss='modal'>Close</button>
				<button type='submit' class='btn btn-danger'>Issue Fine</button>
			</div>
		</div>
	</div>
</div>
