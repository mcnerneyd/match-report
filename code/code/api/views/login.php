<script>
	var tutorial = [
		{ target: "#user-select", message: "Select a club or an umpire from the list",dir:"bottom" },
		{ target: "#login input[name=pin]", message: "Enter your PIN number here", dir:"top" },
		{ target: "#login button[type=submit]", message: "Then click the 'Sign in' button", dir:"top" },
		{ target: "#login .switch-login", message: "If you are trying to login as a club/registration secretary, click here to switch to 'Secretary Login'", dir:"bottom" },
		];

	// Clear Session
	document.cookie = "PHPSESSID=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;";
	document.cookie = "jwt-token=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;";
</script>

<?php 
// --------------------------------------------------------
//  User Login
?>
<form id='login' class='form-signin' method="POST">
	<?= Asset::img("user.svg") ?>
	<h2>
	<a href='<?= Uri::create('/Login', array(), array('site'=>'none')) ?>'><?= \Config::get('config.title') ?></a>
	</h2>

  <input id='user-select' class='form-control' type='text' name='user' list='users' placeholder='Username'/>
	<datalist id='users' class='custom-select' style='display:none'>
			<option value="" disabled selected>Select user&hellip;</option>
			<?php
			foreach ($users as $username) {
					echo "<option>$username</option>\n";
			}
			?>
	</datalist>

	<input type='password' name='pin' class='form-control pin' placeholder='Password' required autocomplete='off' disabled/>

	<div class='col-xs-6'>
		<a id='forgotten-password' href='<?= Uri::create('/User/ForgottenPassword') ?>' class='pull-right'>Forgotten Password</a>
	</div>

	<button class="btn btn-lg btn-primary btn-block" type="submit">Sign in</button>

</form>

<script>
$(document).ready(function() {
	$('#user-select').change(function() {
		$("input[name='pin']").prop('disabled', $(this).val() == "");
	});

	$('#user-select').keyup(function() {
		$("input[name='pin']").prop('disabled', $(this).val() == "");
	});

	<?php if ($selectedUser) { ?>
	$('input#user-select').val('<?= $selectedUser ?>');
	$("input[name='pin']").prop('disabled', false).focus();
	<?php } ?>
});
</script>
