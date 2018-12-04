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
		$('#users-table').on('click', 'a[href="refresh"]', function(e) {
			e.preventDefault();
			var username = $(this).closest('tr').data('user');
			$.ajax({method: 'PUT',
				url: '<?= Uri::create("userapi/refreshpin") ?>',
				data: { 'username' : username }}).done(function(data) {
					window.location.reload();
				});
		});
		$('#users-table').on('click','a[href="delete-user"]',function(e) {
			e.preventDefault();
			var userrow = $(this).closest('tr');
			$.ajax({method: 'DELETE',
				url: '<?= Uri::create("UserApi") ?>',
				data: { 'username' : userrow.data('user') }}).done(function(data) {
					userrow.remove();
			});
		});

		$('#add-user').click(function() {
			$('#add-user-modal .form-group').hide();
			$('#add-user-modal [name=club]').closest('.form-group').show();
			$('#add-user-modal [name=role]').val('user');
			$('#add-user-modal').modal('show');
		});
		$('#add-umpire').click(function() {
			$('#add-user-modal .form-group').hide();
			$('#add-user-modal [name=username]').closest('.form-group').show();
			$('#add-user-modal [name=email]').closest('.form-group').show();
			$('#add-user-modal [name=role]').val('umpire');
			$('#add-user-modal').modal('show');
		});
		$('#add-secretary').click(function() {
			$('#add-user-modal .form-group').hide();
			$('#add-user-modal [name=email]').closest('.form-group').show();
			$('#add-user-modal [name=club]').closest('.form-group').show();
			$('#add-user-modal [name=role]').val('secretary');
			$('#add-user-modal').modal('show');
		});

		$("#add-user-modal button[type='submit']").click(function() {
			$.post('<?= Uri::create('userapi') ?>', $('#add-user-modal form').serialize(), function(data) {
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

<div class='form-group command-group'>
	<div class='btn-group'>
		<button type='button' class='btn btn-success dropdown-toggle' data-toggle='dropdown'>
			<i class='glyphicon glyphicon-plus-sign'></i> Add User</a>
			<span class='caret'></span>
		</button>
		<ul class='dropdown-menu'>
			<li><a id='add-user'>Club User&hellip;</a></li>
			<li><a id='add-secretary'>Club Secretary&hellip;</a></li>
			<li><a id='add-umpire'>Umpire&hellip;</a></li>
			<li class='divider'></li>
			<li><a disabled id='add-all-club-users'>Add missing club users</a></li>
		</ul>
	</div>	<!-- .btn-group -->
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
		} else {
			if ($user['password']) echo "*****";
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
<div class='modal' id='add-user-modal'>
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
				<button id='create-user' type='submit' class='btn btn-danger'>Create User</button>
			</div>
		</div>
	</div>
</div>
