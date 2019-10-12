<div class='card'>
	<div class='card-body'>
		<h5 class='card-title'>Season Close</h5>
		<p class='card-text'>This operation closes the season and removes all data.
		This data is bundled into a downloadable archive.</p>

		<label>
			<input type='checkbox' data-toggle='toggle'> Activate Season Close
		</label>

		<style>
		table p {
			white-space: normal;
		}
		</style>

		<table class='table'>
			<tr>
				<td><a href='<?= Uri::create('User') ?>' class='btn btn-primary'>Users</a> </td>
				<td><p>View, Add, Edit and Remove Users</p>
					<form action='<?= Uri::create('User/switch') ?>'>
						<div class='form-row'>
							<div class='col-auto'>
								<label>Impersonate</label>
								<div class='input-group'>
									<select class='form-control' name='u'>
									<?php foreach ($users as $user) {
										echo "<option>$user</option>"; 
									} ?>
									</select>
								</div>
							</div>
							<div class='col-auto'>
								<button class='btn btn-warning'>Go</button>
							</div>
						</div>
					</form>
				</td>
			</tr>
			<tr>
				<td>
					<a href='<?= Uri::create('Admin/Touch?f=/') ?>' class='btn btn-primary'>Touch</a>
				</td>
				<td>
					<p>Makes sure all registration files have the correct date</p>
				</td>
			</tr>
			<tr>
				<td>
					<a href='<?= Uri::create('Admin/Archive') ?>' class='btn btn-primary'>Archive</a>
				</td>
				<td>
					<p>Archives all incidents, matchcards and registrations and returns 
					contents as a zip file</p>
				</td>
			</tr>
			<tr>
				<td><a href='<?= Uri::create('Admin/Clean?d='.currentSeasonStart()->get_timestamp()) ?>' class='btn btn-danger'>Clean</a> </td>
				<td>
					<p>Delete all incidents and matchcards from before <?= currentSeasonStart() ?>. 
						Also deletes all registration files that are older that this date (leaving 
						at least one for each club).
						<i>Please note this is not easily reversed. You are strongly recommended 
						to run Archive first.</i>
					</p>
				</td>
			</tr>
		</table>
	</div>
</div>
