<script>
	var tutorial = [
		{ target: "#user-select", message: "Select a club or an umpire from the list",dir:"bottom" },
		{ target: "#login input[name=pin]", message: "Enter your PIN number here", dir:"top" },
		{ target: "#login button[type=submit]", message: "Then click the 'Sign in' button", dir:"top" },
		{ target: "#login .switch-login", message: "If you are trying to login as a club/registration secretary, click here to switch to 'Secretary Login'", dir:"bottom" },
		];
</script>

<?php /*
// --------------------------------------------------------
//  Site Select
// --------------------------------------------------------
if (!Session::get('site', false)) { ?>
<div id='site-select'>
<?php foreach ($sites as $site=>$v) { 
	echo "<a data-site='$site' class='btn btn-success col-md-12 col-xs-12' href='".Uri::create('/Login', array(), array('site'=>$site))."'>".$v."</a>\n";
} ?>
</div>
<?php return; } i*/ ?>

<?php 
// --------------------------------------------------------
//  User Login
?>
<form id='login' class='form-signin' method="POST">
	<?= Asset::img("user.svg") ?>
	<h2>
	<a href='<?= Uri::create('/Login', array(), array('site'=>'none')) ?>'><?= \Config::get('config.title') ?></a>
	</h2>

	<select id='user-select' class='custom-select' name='user'>
			<option value="" disabled selected>Select user&hellip;</option>
			<?php
			foreach ($users as $username=>$user) {
					echo "<option>$username</option>\n";
			}
			?>
	</select>

	<input type='password' name='pin' class='form-control pin' placeholder='Password' required autocomplete='off' disabled/>

	<div class='col-xs-6'>
		<a id='forgotten-password' href='<?= Uri::create('/User/ForgottenPassword') ?>' class='pull-right'>Forgotten Password</a>
	</div>

	<button class="btn btn-lg btn-primary btn-block" type="submit">Sign in</button>

</form>
<script>
$(document).ready(function() {
	//var site = <= "'" + Session::get('site') + "'" ?: 'null' >;

	$('#user-select').change(function() {
		$("input[name='pin']").prop('disabled', $(this).val() == "");
	});

	$('#user-select').keyup(function() {
		$("input[name='pin']").prop('disabled', $(this).val() == "");
	});

	if (site != null) $('#site-select').val(site);
	else $('#site-select').val('');

	<?php if ($selectedUser) { ?>
	$('input#user-select').val('<?= $selectedUser ?>');
	$("input[name='pin']").prop('disabled', false).focus();
	<?php } ?>
});
</script>
