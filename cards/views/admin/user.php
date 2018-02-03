<style>
.table>tbody>tr>th { border-top: none; }
.btn .glyphicon { vertical-align: -1px; }
</style>
<?php 
$mailTo = "";

foreach ($users as $user) {
	if ($user['role'] != 'secretary') continue;
	if (strlen($mailTo) > 0) $mailTo .= ", ";
	$mailTo .= $user['username']; 
} ?>

<h1>Adminstration</h1>
<h2>Users <a href='mailTo:<?= $mailTo ?>' title='E-mail all registration secretaries'><i class='fa fa-envelope-o'></i></a></h2>


<div class='tab' id='users'>

	<form class='form-inline' id='newuser' action='<?= url(null, 'adduser', 'admin') ?>' method='POST'>
		<div class='form-group'>
			<label for='newuser-username'>Username</label>
			<input type='text' class='form-control' id='newuser-username' name='username'/>
		</div>
		<div class='form-group'>
			<label for='newuser-email'>Email</label>
			<input type='text' class='form-control' id='newuser-email' name='email'/>
		</div>
		<div class='form-group'>
			<label for='newuser-role'>Role</label>
			<select name='role' id='newuser-role' class='form-control'>
				<option>Umpire</option>
				<option disabled>Secretary</option>
			</select>
		</div>
		<button type="button" class="btn btn-primary" onclick="$('#newuser').submit()">Add User</button>
	</form>

	<table class='table'>
		<tr>
			<th>User</th>
			<th>Club</th>
			<th>Role</th>
			<th>PIN</th>
		</tr>
	<?php foreach ($users as $user) {
		$a = false;
		$username = $user['username'];
		if ($user['role'] == 'user' and !$user['password']) continue;

		if ($user['role'] == 'secretary') {
			$x = createsecurekey('secretarylogin'.$username);
			$a = url("x=$x&u=$username", 'loginUC', 'admin');
			$username = "<a href='$a'>$username</a>";
		}
		?>
		<tr>
			<td><?= $username ?></td>
			<td><?= $user['club'] ?></td>
			<td><?= $user['role'] ?></td>
			<td><?= $user['password'] ?><?php if ($user['role'] == 'umpire' || $user['role'] == 'user') { ?>
				<a href='<?= url("u=".urlencode($username), "resetpin", "admin") ?>'><i class="fa fa-refresh" aria-hidden="true"></i></a>
				<?php } ?>
			</td>
		</tr>
		<?php
	} ?>
	</table>
</div>	<!-- #users -->
