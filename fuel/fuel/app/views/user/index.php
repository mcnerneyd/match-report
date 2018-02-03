<style>
#users-select { padding-left: 20px; float:left; }
</style>
<script>
	$(document).ready(function() {
		$('#users-table').DataTable({
			responsive:true,
			columns: [ 
				{ responsivePriority: "1" },
				{ responsivePriority: "1" },
				{ responsivePriority: "1" },
				null,
				null,
				null,
			],
		});
		$('#users-select').detach().insertBefore($('#users-table_filter'));

		$('#users-select select').change(function() {
			var table = $('#users-table').DataTable();
			var key = $('#users-select option:selected').data('key');

			key = (typeof key==="undefined"?"":"^"+key+"$");

			table.columns(3).search(key, true).draw();
		});
		$('#users-table tbody').show();
		$('#users-table a[href="refresh"]').click(function(e) {
			e.preventDefault();
			var username = $(this).closest('tr').data('user');
			$.ajax({method: 'PUT',
				url: '<?= Uri::create("user/refreshpin") ?>',
				data: { 'username' : username }}).done(function(data) {
					window.location.reload();
				});
		});
		$('#users-table a[href="delete-user"]').click(function(e) {
			e.preventDefault();
			var username = $(this).closest('tr').data('user');
			$.ajax({method: 'DELETE',
				url: '<?= Uri::create("User") ?>',
				data: { 'username' : username }}).done(function(data) {
					window.location.reload();
			});
		});
	});
</script>

<div class='form-group command-group'>
	<a class='btn btn-danger' data-target='#add-user' data-toggle='modal'><i class='glyphicon glyphicon-plus-sign'></i> Add User</a>
</div>

<div id='users-select'>
	<label>Filter:
		<select class="form-control" data-toggle="buttons">
			<option value="">All</option>
			<option data-key="User">User</option>
			<option data-key="Umpire">Umpire</option>
			<option data-key="Secretary">Secretary</option>
		</select>
	</label>
</div>

<table id='users-table' class='table table-condensed table-striped'>
	<thead>
	<tr>
		<th>Username</th>
		<th>Club</th>
		<th>PIN</th>
		<th>Role</th>
		<th>Email</th>
		<th/>
	</tr>
	</thead>

	<tbody style='display:none'>
	<?php foreach ($users as $user) {
		echo "<tr data-user='".$user['username']."'>
			<td>${user['username']}</td>
			<td>".$user['club']['name']."</td>
			<td>";
		if ($user['role'] != 'secretary') {
			echo "${user['password']} <a href='refresh'><i class='glyphicon glyphicon-refresh'></i>";
		}
		echo "</td>
			<td>${user['role']}</td>
			<td>${user['email']}</td>
			<td class='command-group'>
				<a href='delete-user' class='btn btn-danger btn-xs'><i class='glyphicon glyphicon-trash'></i></a>
			</td>
		</tr>";
	} ?>
	</tbody>
</table>


<!-- Create User Modal -->
<script>
$(document).ready(function() {
	$("#add-user button[type='submit']").click(function() {
		$.post('<?= Uri::create('user') ?>', $('#add-user form').serialize(), function(data) {
			window.location.reload();
			$.notify({message: 'User Created'}, {
				placement: { from: 'top', align: 'right' },		
				delay: 1000,
				animate: {
					enter: 'animated bounceInDown',
					exit: 'animated bounceOutUp'
				},
				type: 'success'});
			});
	});
});
</script>

<div class='modal' id='add-user'>
	<div class='modal-dialog'>
		<div class='modal-content'>
			<div class='modal-header'>
				<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
				<h4 class='modal-title'>Create User</h4>
			</div>
			<div class='modal-body'>
			<form class='form-horizontal'>
					<input type='hidden' name='fixtureid'/>

					<div class='form-group'>
						<label class='control-label col-sm-3'>User Name</label>
						<div class='col-sm-6'>
							<input class='form-control' type='text' name='username'/>
						</div>
					</div>

					<div class='form-group'>
						<label class='control-label col-sm-3'>Email Address</label>
						<div class='col-sm-6'>
							<input class='form-control' type='email' name='email'/>
						</div>
					</div>

					<div class='form-group'>
						<label class='control-label col-sm-3'>Club</label>
						<div class='col-sm-8'>
							<select class='form-control' name='club'>
								<option value='none'>No club</option>
								<?php foreach ($clubs as $club) {
									echo "<option>${club['name']}</option>\n";
								} ?>
							</select>
						</div>
					</div>

					<div class='form-group'>
						<label class='control-label col-sm-3'>Role</label>
						<div class='col-sm-8'>
							<select class='form-control' name='role'>
								<option value='none'>--- Select Role ---</option>
								<option value='admin'>Administrator</option>
								<option value='manager'>Competition Manager</option>
								<option value='secretary'>Secretary</option>
								<option value='user'>Player</option>
								<option value='umpire'>Umpire</option>
							</select>
						</div>
					</div>
				</form>
			</div>

			<div class='modal-footer'>
				<button type='button' class='btn btn-default' data-dismiss='modal'>Close</button>
				<button type='submit' class='btn btn-danger'>Create User</button>
			</div>
		</div>
	</div>
</div>
