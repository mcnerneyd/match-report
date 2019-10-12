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
	$("#configform").submit(function(e){
		e.preventDefault();
    $.post("<?= Uri::current() ?>", $("#configform").serialize(), function(data) {
			location.reload();
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
});
</script>

<form id='configform' method='POST' autocomplete='off'>

	<div class='command-group'>
		<button class='btn btn-success'>Save</button>
		<a href='<?= Uri::current() ?>' class='btn btn-warning'>Cancel</a>
	</div>

	<!-- Tab Header -->
	<ul class="nav nav-tabs">
		<li class='nav-item'>
			<a class='nav-link active' href='#home' data-toggle='tab'>General</a>
		</li>
		<li class='nav-item'>
			<a class='nav-link' href='#fixtures' data-toggle='tab'>Fixtures</a>
		</li>
		<li class='nav-item'>
			<a class='nav-link' href='#security' data-toggle='tab'>Security</a>
		</li>
		<li class='nav-item'>
			<a class='nav-link' href='#tasks' data-toggle='tab'>Tasks</a>
		</li>
	</ul>

	<!-- Tab Panes -->
	<div class='tab-content'>
		<div class='tab-pane active' id='home'>
			<div class='form-row'>
				<div class='form-group col'>
					<label>Title</label>
					<input name='title' type='text' class='form-control' value='<?= $title ?>'/>
				</div>

				<div class='form-group col'>
					<label>Administrator Email</label>
					<input name='admin_email' type='text' autocomplete='off' class='form-control' value='<?= $admin_email ?>'></input>
				</div>
			</div>

			<div class='form-row'>
				<div class='form-group col'>
					<label>Strict</label>
					<input name='strict_comps' type='text' class='form-control' value='<?= $strict_comps ?>'></input>
				</div>

				<div class='form-group col'>
					<label>Standard Fine</label>
					<input name='fine' type='text' class='form-control' value='<?= $fine ?>'></input>
				</div>
			</div>

			<div class='form-row'>
				<div class='form-group col'>
					<label>Result Submission</label>
					<p>When a matchcard is complete, the result can be submitted to the LHA website.</p>
					<div class='radio'>
						<label>
							<input name='resultsubmit' type='radio' value='no'></input>
							Do not submit results
						</label>
					</div>
					<div class='radio'>
						<label>
						<input name='resultsubmit' type='radio' value='new'></input>
							Submit results, but do not overwrite an existing result
						</label>
					</div>
					<div class='radio'>
						<label>
							<input name='resultsubmit' type='radio' value='yes'></input>
							Submit results
						</label>
					</div>
				</div>

				<div class='form-group col'>
					<label>Season Start Date</label>
					<input name='seasonstart' type='text' class='form-control' value='<?= currentSeasonStart() ?>' readonly></input>
				</div>

			</div>

			<fieldset>
				<legend>Registration</legend>

				<div class='checkbox'>
					<label> <input type='checkbox' name='allow_registration' <?php 
						if ($automation_allowrequest) echo 'checked'; ?>></input> Allow Registration</label>
				</div>

				<div class='checkbox'>
					<label> <input type='checkbox' name='allow_assignment' <?php 
						if ($allowassignment) echo 'checked'; ?>></input> Allow Explicit Team Assignment</label>
				</div>

				<div class='checkbox'>
					<label> <input type='checkbox' name='block_errors' <?php 
						if (Config::get("hockey.block_errors", false)) echo 'checked'; ?>></input> Block Registration on Errors</label>
				</div>

				<div class='form-group col'>
					<label>Registration Restriction Date</label>
					<input name='regrestdate' type='text' class='form-control date-select' value='<?= $regrestdate ?>'></input>
					<small>After this date all rules regrading player registration will be applied</small>
				</div>
			</fieldset>

		</div> <!-- #home -->

		<div class='tab-pane' id='fixtures'>
			<div class='form-row'>
				<div class='form-group col-sm-12'>
					<label for='config-fixtures'>Fixtures</label>
					<textarea name='fixtures' id='config-fixtures' rows='8' cols='140' class='form-control' spellcheck='false'><?= $fixtures ?></textarea>
				</div>
			</div>

			<div class='form-row'>
				<div class='form-group col-sm-6'>
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

				<div class='form-group col-sm-6'>
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
			</div>
		</div> <!-- #fixtures -->

		<div class='tab-pane' id='security'>
				<div class='form-group col-sm-6' id='security-salt'>
					<label>Salt</label>
					<div class='input-group'>
						<span class='input-group-btn'>
							<button class='btn btn-success' type='button'>
								<i class='glyphicon glyphicon-refresh'></i>
							</button>
						</span>
						<input name='salt' type='text' class='form-control' value='<?= $salt ?>'></input>
						
					</div>
				</div>

				<div class='form-group col-sm-6'>
					<label>Elevation Password</label>
					<input name='elevation_password' type='text' class='form-control' value='<?= $elevation_password ?>'></input>
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
Result Submit: <?= Config::get("config.result_submit") ?>
-->

