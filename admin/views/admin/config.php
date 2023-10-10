<script>
$(document).ready(function(event) {
   $('#section-select').change(function() {
    window.location="<?= Uri::create('/admin/config') ?>?section=" + $('#section-select').val();
   });
});
</script>

<div class='command-group'>
	<select class='form-control' id='section-select'>
		<option>Select Section...</option>
		<?php foreach ($sections as $s) echo "<option value='${s['name']}'>".$s['name']."</option>"; ?>
	</select>
	<?php if ($section) { ?>
	<button class='btn btn-success'>Save</button>
	<a href='<?= Uri::current() ?>' class='btn btn-warning'>Cancel</a>
	<?php } ?>
</div>

<?php if (!$section) return; ?>

<style>
form { position: relative; }
.valid { color: green; display: none; }
.invalid { color: red; }
.valid::after, .invalid::after { content: " "; }
.valid span { font-weight: bold; }
</style>

<script>
$(document).ready(function(event) {
	$('#task-table').DataTable();
	$('.command-group .btn-success').click(function() {
		$("#configform").submit();
	});
	$("#configform").submit(function(e){
		e.preventDefault();
    $.post("<?= Uri::create('/api/1.0/admin/config') ?>", $("#configform").serialize(), function(data) {
			location.reload();
			Toastify({
            text: "Configuration Saved",
            duration: 2000,
            close: true,
            gravity: "top", // `top` or `bottom`
            position: "right", // `left`, `center` or `right`
            offset: { y: 50 },
            stopOnFocus: true, // Prevents dismissing of toast on hover
          }).showToast();
		});
	});
	$('#security-salt .btn').click(function(e) {
		debugger;
		var chars = "0123456789"
			+ "abcdefghijklmnopqrstuvwxyz"
			+ "ABCDEFGHIJKLMNOPQRSTUVWXYZ"
			+ "#$%!@";
		var salt = "";
		while (salt.length < 32) {
			var charIndex = Math.floor(Math.random() * chars.length);
			salt += chars.charAt(charIndex);
		}
		$('#security-salt input').val(salt);
		$('#configform').submit();
	});
	$('.date-select').datepicker({
		dateFormat: "yy-mm-dd",
		showOtherMonths: true,
		selectOtherMonths: true,
		});

	$('.radio [name=resultsubmit][value=<?= $resultsubmit ?>]').prop('checked', true);

	<?php if ($section) { ?>
	$('#section-select option[value="<?= $section ?>"]').prop('selected', true);
	<?php } ?>
});
</script>

<form id='configform' method='POST' autocomplete='off'>

    <input type='hidden' name='section' value='<?= $section ?>'/>

	<!-- Tab Header -->
	<ul class="nav nav-tabs">
		<li class='nav-item'>
			<a class='nav-link active' href='#home' data-bs-toggle='tab'>General</a>
		</li>
		<li class='nav-item'>
			<a class='nav-link' href='#registration' data-bs-toggle='tab'>Registration</a>
		</li>
		<li class='nav-item'>
			<a class='nav-link' href='#fixtures' data-bs-toggle='tab'>Fixtures</a>
		</li>
		<li class='nav-item'>
			<a class='nav-link' href='#security' data-bs-toggle='tab'>Security</a>
		</li>
		<li class='nav-item'>
			<a class='nav-link' href='#tasks' data-bs-toggle='tab'>Tasks</a>
		</li>
	</ul>

	<!-- Tab Panes -->
	<div class='tab-content'>
		<div class='tab-pane active' id='home'>
			<label>Title
				<input name='title' type='text' class='form-control' value='<?= $title ?>'/>
			</label>

			<label>Administrator Email
				<input name='admin_email' type='text' autocomplete='off' class='form-control' value='<?= $admin_email ?>'></input>
			</label>

			<label>Strict
				<input name='strict_comps' type='text' class='form-control' value='<?= $strict_comps ?>'></input>
			</label>

			<label>Standard Fine
				<input name='fine' type='text' class='form-control' value='<?= $fine ?>'></input>
			</label>

			<fieldset>
				<legend><u>Result Submission</u></legend>
				<p>When a matchcard is complete, the result can be submitted to the LHA website.</p>
				<label>
					<input class='form-check-input' name='resultsubmit' type='radio' value='no' 
						<?php if (!$resultsubmit || $resultsubmit == 'no') echo 'checked'; ?>></input>
					Do not submit results
				</label>
				<label>
					<input class='form-check-input' name='resultsubmit' type='radio' value='new'
							<?php if ($resultsubmit == 'new') echo 'checked'; ?>></input>
						Submit results, but do not overwrite an existing result
				</label>
				<label>
					<input class='form-check-input' name='resultsubmit' type='radio' value='yes'
						<?php if ($resultsubmit == 'yes') echo 'checked'; ?>></input>
					Submit results
				</label>

				<label>
					<input class='form-check-input' name='resultbutton' type='checkbox'
						<?php if ($resultbutton == 'on') echo 'checked'; ?>></input>
					Hide 'Upload Results' button from non-Admin users
				</label>

			</fieldset>

			<label>Season Start Date
				<input name='seasonstart' type='text' class='form-control' value='<?= currentSeasonStart() ?>' readonly></input>
			</label>

			<label>CC Emails
				<input name='cc_email' type='text' autocomplete='off' class='form-control' value='<?= $cc_email ?>'></input>
				<small>When a user creates a fixture email, these email address will be automatically included in the cc list.
				(Separate with commas)</small>
			</label>
		</div>

		<div class='tab-pane' id='registration'>
				<div class='form-group col'>
					<label>Registration Restriction Date</label>
					<input name='regrestdate' type='text' class='form-control date-select' value='<?= $regrestdate ?>'></input>
					<small>After this date all rules regrading player registration will be applied</small>
				</div>

				<div class='form-group col'>
				<h3>Options</h3>

				<div class='form-check'>
					<label> <input class='form-check-input' type='checkbox' name='allow_registration' <?php 
						if ($automation_allowrequest) echo 'checked'; ?>></input> Allow club secretaries to submit their own registration</label>
				</div>

				<div class='form-check'>
					<label> <input class='form-check-input' type='checkbox' name='allow_assignment' <?php 
						if ($allowassignment) echo 'checked'; ?>></input> Allow players to be assigned to specific teams in registration files</label>
				</div>

				<div class='form-check'>
					<label> <input class='form-check-input' type='checkbox' name='allow_placeholders' <?php 
						if ($allowplaceholders) echo 'checked'; ?>></input> Allow players who have not played any matches to be registered on teams other than the last team</label>
				</div>

				<div class='form-check'>
					<label> <input class='form-check-input' type='checkbox' name='block_errors' <?php 
						if (Config::get("hockey.block_errors", false)) echo 'checked'; ?>></input> Do not activate a registration file if it contains errors</label>
				</div>

				<div class='form-group'>
					<p>If a player does not have a valid Hockey Ireland membership:</p>
					<!-- Mandatory HI: <?= $mandatory_hi ?> -->
					<div class='form-check'>
						<label>
							<input class='form-check-input' name='mandatory_hi' type='radio' value='noregister'
									<?php if (!$mandatory_hi || $mandatory_hi == 'noregister') echo 'checked'; ?>></input>
							That player cannot be registered	
						</label>
					</div>
					<div class='form-check'>
						<label>
							<input class='form-check-input' name='mandatory_hi' type='radio' value='noselect'
									<?php if ($mandatory_hi == 'noselect') echo 'checked'; ?>></input>
							That player cannot be selected for any team	
						</label>
					</div>
					<div class='form-check'>
						<label>
							<input class='form-check-input' name='mandatory_hi' type='radio' value='lastteamonly'
									<?php if ($mandatory_hi == 'lastteamonly') echo 'checked'; ?>></input>
							That player can only be selected for the last team in the club
						</label>
					</div>
					<div class='form-check'>
						<label>
							<input class='form-check-input' name='mandatory_hi' type='radio' value='unrestricted'
									<?php if ($mandatory_hi == 'unrestricted') echo 'checked'; ?>></input>
							That player will not be restricted
						</label>
					</div>
				</div>
				</div>

		</div> <!-- #home -->

		<div class='tab-pane' id='fixtures'>
			<div>
				<label for='config-fixtures'>Fixtures</label>
				<textarea name='fixtures' id='config-fixtures' rows='8' cols='140' class='form-control' spellcheck='false'><?= $fixtures ?></textarea>

				<small>[feed] | [file.csv] | ![web page] | =[yyyy-mm-dd,comp,home,away]</small>
			</div>

			<div>
				<label for='config-fixes-competitions help'>Competition Fixes</label>
				<textarea name='fixescompetition' id='config-fixes-competitions' rows='8' class='form-control' spellcheck='false'><?= $fixescompetition ?></textarea>
				<?php foreach ($competitions as $raw=>$competition) {
					if ($competition['valid']) {
						echo "<span class='valid'>[${competition['name']}]</span>";
					} else {
						echo "<span class='invalid'>[$raw]</span>";
					}
						echo "&nbsp;";
				} ?>
			</div>

			<div>
				<label for='config-fixes-team help'>Team Fixes</label>
				<textarea name='fixesteam' id='config-fixes-team' rows='8' class='form-control' spellcheck='false'><?= $fixesteam ?></textarea>
				<?php foreach ($teams as $raw=>$team) { 
						if ($team['valid']) {
						echo "<span class='valid'>[${team['club']}&nbsp;<span>${team['team']}</span>]</span>"; 
					} else {
						echo "<span class='invalid'>[$raw]</span>";
					}
				}	?>
			</div>
		</div> <!-- #fixtures -->

		<div class='tab-pane' id='security'>
				<div class='form-group col-sm-6' id='security-salt'>
					<label>Salt</label>
					<div class='input-group'>
						<span class='input-group-btn'>
							<button class='btn btn-success' type='button'>
								<i class="fas fa-sync-alt"></i>
							</button>
						</span>
						<input name='salt' type='text' class='form-control' value='<?= $salt ?>'></input>
						
					</div>
				</div>
		</div> <!-- #security -->

		<div class='tab-pane' id='tasks'>

			<table id='task-table' class='table table-condensed table-striped'>
				<thead>
				<tr><th>Command</th><th>Next Execution</th><th>Recurrence</th></tr>
				</thead>
				<tbody>
			<?php foreach ($tasks as $task) {
				echo "<tr><td>${task['command']}</td><td>${task['datetime']}</td><td>${task['recur']}</td></tr>\n";
			} ?>
				</tbody>
			</table>

		</div>

	</div> <!-- .tab-content -->

</form>

<!-- Config Summary:
Result Submit: <?= Config::get("section.result_submit") ?>
-->

