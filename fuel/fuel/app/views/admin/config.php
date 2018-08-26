<style>
form { position: relative; }
</style>

<script>
$(document).ready(function(event) {
	$('#task-table').DataTable();
	$("#configform").submit(function(e){
		e.preventDefault();
    $.post("<?= Uri::current() ?>", $("#configform").serialize(), function(data) {
			$.notify({message: 'Configuration Saved'}, {
				placement: { from: 'top', align: 'right' },		
				delay: 1000,
				animate: {
					enter: 'animated bounceInDown',
					exit: 'animated bounceOutUp'
				},
				type: 'success'});
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
});
</script>

<form id='configform' method='POST'>

	<div class='command-group'>
		<button class='btn btn-success'>Save</button>
		<a href='<?= Uri::current() ?>' class='btn btn-warning'>Cancel</a>
	</div>

	<!-- Tab Header -->
	<ul class="nav nav-tabs">
		<li class='active'><a href='#home' data-toggle='tab'>General</a></li>
		<li><a href='#fixtures' data-toggle='tab'>Fixtures</a></li>
		<li><a href='#security' data-toggle='tab'>Security</a></li>
		<li><a href='#tasks' data-toggle='tab'>Tasks</a></li>
	</ul>

	<!-- Tab Panes -->
	<div class='tab-content'>
		<div class='tab-pane active' id='home'>
			<div class='row'>
				<div class='form-group col-sm-6'>
					<label>Title</label>
					<input name='title' type='text' class='form-control' value='<?= $title ?>'/>
				</div>

				<div class='form-group col-sm-6'>
					<label for='config-uploadformat'>Default Upload Format</label>
					<select id='config-uploadformat' class='form-control'>
						<option>Ordered List</option>
						<option>Numbered List</option>
					</select>
				</div>

				<div class='form-group col-sm-6'>
					<label>Administrator Email</label>
					<input name='admin_email' type='text' class='form-control' value='<?= $admin_email ?>'></input>
				</div>

				<div class='form-group col-sm-6'>
					<label>Strict</label>
					<input name='strict_comps' type='text' class='form-control' value='<?= $strict_comps ?>'></input>
				</div>

				<div class='form-group col-sm-6'>
					<label>Standard Fine</label>
					<input name='fine' type='text' class='form-control' value='<?= $fine ?>'></input>
				</div>
			</div>

			<fieldset>
				<legend>Automation Email</legend>
				<div class='row'>
					<div class='form-group col-sm-6'>
						<label for='config-automation'>Email</label>
						<input type='text' name='automation_email' class='form-control col-sm-6' value='<?= $automation_email ?>'/>
					</div>

					<div class='form-group col-sm-6'>
						<label for='config-automation'>Password</label>
						<input type='password' name='automation_password' class='form-control col-sm-6'/>
					</div>
				</div>

				<div class='checkbox'>
					<label> <input type='checkbox' name='allow_registration' <?php 
						if ($automation_allowrequest) echo 'checked'; ?>></input> Allow Registration</label>
				</div>
			</fieldset>

		</div> <!-- #home -->

		<div class='tab-pane' id='fixtures'>
			<div class='row'>
				<div class='form-group col-sm-12'>
					<label for='config-fixtures'>Fixtures</label>
					<textarea name='fixtures' id='config-fixtures' rows='8' cols='140' class='form-control' spellcheck='false'><?= $fixtures ?></textarea>
				</div>
			</div>

			<div class='row'>
				<div class='form-group col-sm-6'>
					<label for='config-fixes-competitions help'>Competition Fixes</label>
					<textarea name='fixescompetition' id='config-fixes-competitions' rows='8' class='form-control' spellcheck='false'><?= $fixescompetition ?></textarea>
				</div>

				<div class='form-group col-sm-6'>
					<label for='config-fixes-team help'>Team Fixes</label>
					<textarea name='fixesteam' id='config-fixes-team' rows='8' class='form-control' spellcheck='false'><?= $fixesteam ?></textarea>
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
