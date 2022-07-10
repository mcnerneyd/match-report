<script>
	$(document).ready(function() {
		$('#users-table').DataTable({
			responsive:true,
			columns: [
				{ responsivePriority: "1" },
				{ responsivePriority: "1" },
				{ responsivePriority: "3" },
				{ responsivePriority: "4" },
				{ responsivePriority: "5" },
				{ responsivePriority: "6" },
				{ responsivePriority: "2" },
			],
		});
		$('#users-select').detach().insertBefore($('#users-table_filter'));

		$('#users-select select').change(function() {
			var table = $('#users-table').DataTable();
			var key = $('#users-select option:selected').data('key');

			key = (typeof key==="undefined"?"":"^"+key+"$");

			table.columns(3).search(key, true).draw();
		});
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
			$('#add-user-modal [name=section]').closest('.form-group').show();
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
			$('#add-user-modal [name=section]').closest('.form-group').show();
			$('#add-user-modal [name=email]').closest('.form-group').show();
			$('#add-user-modal [name=club]').closest('.form-group').show();
			$('#add-user-modal [name=role]').val('secretary');
			$('#add-user-modal').modal('show');
		});
		$('#add-admin').click(function() {
			$('#add-user-modal .form-group').hide();
			$('#add-user-modal [name=section]').closest('.form-group').show();
			$('#add-user-modal [name=email]').closest('.form-group').show();
			$('#add-user-modal [name=role]').val('admin');
			$('#add-user-modal').modal('show');
		});
		$('#import-users').click(function() {
			$('#import-user-modal').modal('show');
		});

		$("#add-user-modal button[type='submit']").click(function() {
			$.post('<?= Uri::create('index.php/api/users') ?>', $('#add-user-modal form').serialize(), function(data) {
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

		$("#import-user-modal button[type='submit']").click(function(event) {
            event.preventDefault();
             var form = $('#import-user-modal form')[0]
             var data = new FormData(form)
             $.ajax({
                type: 'POST',
                url: form.action,
                data: data,
                processData: false,
                contentType: false
             }).done((data) => {
				window.location.reload();
				$.notify({message: 'Users Imported'}, {
					placement: { from: 'top', align: 'right' },
					delay: 1000,
					animate: {
						enter: 'animated bounceInDown',
						exit: 'animated bounceOutUp'
					},
					type: 'success'});
				});
		});

		$("#add-all-club-users").click(function() {
			$.post('<?= Uri::create('userapi/missingusers') ?>', function(data) { window.location.reload(); });
		});

	});
</script>

<div class='command-group'>
	<div class='dropdown'>
		<button id='add-user-button' type='button' class='btn btn-success dropdown-toggle' data-toggle='dropdown'>
			<i class="fas fa-user-plus"></i> Add User</a>
		</button>
		<div class='dropdown-menu'>
			<a class='dropdown-item' id='add-user'>Club User&hellip;</a>
			<a class='dropdown-item' id='add-secretary'>Club Secretary&hellip;</a>
			<a class='dropdown-item' id='add-umpire'>Umpire&hellip;</a>
			<a class='dropdown-item' id='add-admin'>Admin User&hellip;</a>
			<div class='dropdown-divider'></div>
			<a class='dropdown-item' id='add-all-club-users'>Add missing club users</a>
			<a class='dropdown-item' id='import-users'>Import Users&hellip;<a>
		</div>
	</div>	<!-- .btn-group -->
</div>

<div class='objecttable'>
<table id='users-table' class='table table-condensed table-striped nowrap'>
	<thead>
		<tr>
			<th>Username</th>
			<th>Section</th>
			<th>Club</th>
			<th>PIN</th>
			<th>Role</th>
			<th>Email</th>
			<th/>
		</tr>
	</thead>

	<tbody>
	<?php foreach ($users as $user) {
    // The SuperUser is not available here
    if ($user['username'] === 'admin') {
        continue;
    }

    echo "<tr data-user='".$user['username']."'>
			<td><a href='User/switch?u=".$user['username']."' class='btn btn-secondary btn-sm'><i class='fas fa-user-secret'></i></a> ${user['username']}</td>
			<td>".($user->section ? $user->section['name'] : "-")."</td>
			<td>".($user['club'] ? $user['club']['name'] : "-")."</td>
			<td>";
    if ($user['role'] == 'Users' || $user['role'] == 'Umpires') {
    	echo "${user['pin']} <a href='refresh'> <i class='fas fa-sync-alt'></i> </a>";
    }
    echo "</td>
			<td>${user['role']}</td>
			<td>${user['email']}</td>
			<td class='command-group'>
				<a href='delete-user' class='btn btn-danger btn-sm'><i class='fas fa-trash-alt'></i></a>
			</td>
		</tr>";
} ?>
	</tbody>
</table>
</div>


<!-- Create User Modal -->
<div class='modal' id='add-user-modal'>
	<div class='modal-dialog'>
		<div class='modal-content'>
			<div class='modal-header'>
				<h5 class='modal-title'>Create User</h5>
				<button type="button" class="close" data-dismiss="modal" aria-label="Close">
					<span aria-hidden="true">&times;</span>
				</button>
			</div>
			<div class='modal-body'>
			<form>
					<input type='hidden' name='fixtureid'/>

					<div class='form-group'>
						<label>User Name</label>
						<input class='form-control' type='text' name='username'  pattern='[A-Za-z0-9.@,/ ]+'/>
					</div>

					<div class='form-group'>
						<label>Email Address</label>
						<input class='form-control' type='email' name='email' pattern='[A-Za-z0-9.@ ]+'/>
					</div>

					<div class='form-group'>
						<label>Section</label>
						<select class='form-control' name='section'>
              <option value='all'>All sections</option>
							<?php foreach ($sections as $section) { echo "<option>${section['name']}</option>\n"; } ?>
						</select>
					</div>

					<div class='form-group'>
						<label>Club</label>
						<select class='form-control' name='club'>
							<option value='none'>No club</option>
							<?php foreach ($clubs as $club) {
    echo "<option>${club['name']}</option>\n";
} ?>
						</select>
					</div>

					<div class='form-group'>
						<label>Role</label>
						<select class='form-control' name='role'>
							<option value='none'>--- Select Role ---</option>
							<option value='admin'>Administrator</option>
							<option value='manager'>Competition Manager</option>
							<option value='secretary'>Secretary</option>
							<option value='user'>Player</option>
							<option value='umpire'>Umpire</option>
						</select>
					</div>
				</form>
			</div>

			<div class='modal-footer'>
				<button type='button' class='btn btn-outline-default' data-dismiss='modal'>Close</button>
				<button id='create-user' type='submit' class='btn btn-success'>Create User</button>
			</div>
		</div>
	</div>
</div>

<div class='modal' id='import-user-modal'>
	<div class='modal-dialog'>
		<div class='modal-content'>
			<div class='modal-header'>
				<h5 class='modal-title'>Import Users</h5>
				<button type="button" class="close" data-dismiss="modal" aria-label="Close">
					<span aria-hidden="true">&times;</span>
				</button>
			</div>
			<div class='modal-body'>
                <form enctype="multipart/form-data" action="<?= Uri::create('Admin/Import') ?>" method="post">
                <div class='form-group'>
                    <label>Section</label>
                    <select class='form-control' name='section'>
                      <option value='all'>All sections</option>
                            <?php foreach ($sections as $section) {echo "<option>${section['name']}</option>\n";} ?>
                    </select>
                </div>
                  <div class='row'>
                    <div class='input-group col'>
                      <input type='file' name='source' id='source' required='true'/>
                    </div>
                  </div>
                </form>
			</div>

			<div class='modal-footer'>
				<button type='button' class='btn btn-outline-default' data-dismiss='modal'>Cancel</button>
				<button type='submit' class='btn btn-success'>Import</button>
			</div>
		</div>
	</div>
</div>
