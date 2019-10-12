<script>
$(document).ready(function() {
	$('#postponement').hide();
 
	$('[name=postponement-request]').click(function() {
		if ($(this).is(':checked')) {
			$('#postponement').show();
		} else {
			$('#postponement').hide();
		}
	});
});
</script>
<form method='post'>
	
	<input type='hidden' name='id' value='<?= $id ?>'/>

	<div class='form-group'>
		<label>To</label>
		<div><?= implode(", ", $to) ?></div>
	</div>

	<div class='form-group'>
		<label>Cc</label>
		<div><?= implode(", ", $cc) ?></div>
	</div>

	<div class='form-group'>
		<label>Subject</label>
		<div><?= $description ?> #<?= $id ?></div>
	</div>

	<div class='form-group'>
		<div class='checkbox'>
			<label> <input type='checkbox' name='postponement-request'>
				</input> Postponement Request</label>
		</div>
	</div>

	<fieldset id='postponement'>
		<div class='form-group'>
			<p class='alert alert-warning'>IMPORTANT: If the postponement is granted, you must still
			mark the card as postponed in order to avoid being fined.</p>

			<h4>Reason for Postponement</h4>

			<div class='form-check' data-notice='10'>
				<input class='form-check-input' type='radio' name='postponement-reason' value='lenservpost'/>
				<label>Leinster Service Postponement</label>
				<p>The team applying for a postponement must have 4 players in their registered squad - not including
				starred players or players from lower teams that are playing Leinster or coaching a Leinster team.</p>
				<p>Players must be listed in the notes section at the end of this form.</p>
				<p>Refix must be within 22 days.</p>
			</div>

			<div class='form-check' data-notice='10'>
				<input class='form-check-input' type='radio' name='postponement-reason' value='schedconflpost'/>
				<label>Scheduling Conflict</label>
				<p>Where the team (not players) applying for the postponement or the team above them has been scheduled
				to play more than one match in one day in any of the following:
					<ul>
					<li>Leinster League</li>
					<li>Leinster Cup</li>
					<li>Any competition listed under the ‘National’ column on the Men’s Section Calendar</li>
					</ul>
				<p>
				<p>The match must be played the next day (or within 8 days by agreement).</p>
			</div>

			<div class='form-check'>
				<input class='form-check-input' type='radio' name='postponement-reason' value='lastteampost'/>
				<label>Last Team Postponement (Bye-Law 3.5.2)</label>
				<p>The team applying for a postponement is the last team in the club. Where a third or subsequent
				postponement is granted, the team will be deducted one point and subject to a fine.</p>
				<p>A fine will be issued if the request made is after 1pm on the day previous to the match</p>
				<p>Refix must be within 22 days.</p>
			</div>

			<div class='form-check' data-notice='30'>
				<input class='form-check-input' type='radio' name='postponement-reason' value='univerpost'/>
				<label>University Postponement</label>
				<p>At the start of the season University clubs may apply for a postponement before the start of term. This
					does not apply to 1st teams.</p>
				<p>Match must be played by next available Tuesday after term starts</p>
			</div>
 
			<div class='form-check'>
				<input class='form-check-input' type='radio' disabled/>
				<label>Compassionate Postponement</label>
				<p>Where a club feels that it must cancel some or all games in the relevant section due to the death of a
					significant person directly related to the club, the club secretary should contact the section committee
					directly.</p>
				<p>Best effort notice will be appreciated.</p>
			</div>

		</div>
	</fieldset>

	<div class='form-group'>
		<label>Message</label>
		<div><textarea class='form-control' name='message' rows='5' cols='30'></textarea></div>
	</div>

	<div class='form-group'>
		<button class='btn btn-success'>Send</button>
		<a class='btn btn-warn' href='<?= rootUrl()."/card/index.php?controller=card&action=index" ?>'>Cancel</a>
	</div>
</form>

