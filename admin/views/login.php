<style>
.form-signin {
  max-width: 330px;
  padding: 15px;
  margin: 0 auto;
}
.form-signin .checkbox {
  font-weight: normal;
	font-size: 90%;
}
.form-signin .form-control {
  position: relative;
  height: auto;
  -webkit-box-sizing: border-box;
     -moz-box-sizing: border-box;
          box-sizing: border-box;
  padding: 10px;
  font-size: 14px;
}
.form-signin #user-select {
	height: 45px;
	margin-top: 22px;
  border-bottom-right-radius: 0;
  border-bottom-left-radius: 0;
}
select#user-select {
	margin-top: 14px !important;
}
.form-signin .pin {
	height: 45px;
  margin-bottom: 10px;
  border-top-left-radius: 0;
  border-top-right-radius: 0;
	border-top: 0;
}
.form-signin button {
	margin-top: 20px;
}
.form-signin img {
	width: 10em;
	margin-left: -5em;
	left:50%;
	position: relative;
}
.form-signin h2 {
	width: 100%;
	text-align: center;
}
.form-signin h2 a {
	color: black;
}

input.pin {
	    -webkit-text-security: disc;
}
input.pin::-webkit-inner-spin-button, 
input.pin::-webkit-outer-spin-button { 
	  -webkit-appearance: none; 
		  margin: 0; 
}
div#site-select {
  max-width: 330px;
  margin: 0 auto;
}
div#site-select a {
	margin: 5px;
}
.switch-login {
	text-align: center;
	margin-top: 8px;
	font-size: 90%;
	display: block;
}
</style>

<script>
	var tutorial = [
		{ target: "#user-select", message: "Select a club or an umpire from the list",dir:"bottom" },
		{ target: "#login input[name=pin]", message: "Enter your PIN number here", dir:"top" },
		{ target: "#login button[type=submit]", message: "Then click the 'Sign in' button", dir:"top" },
		{ target: "#login .switch-login", message: "If you are trying to login as a club/registration secretary, click here to switch to 'Secretary Login'", dir:"bottom" },
		];
</script>

<?php 
// --------------------------------------------------------
//  Site Select
// --------------------------------------------------------
if (!Session::get('site', false)) { ?>
<div id='site-select'>
<?php foreach ($sites as $site=>$v) { 
	echo "<a data-site='$site' class='btn btn-success col-md-12 col-xs-12' href='".Uri::create('/Login', array(), array('site'=>$site))."'>".$v."</a>\n";
} ?>
</div>
<?php return; } ?>

<?php 
// --------------------------------------------------------
//  User Login
// --------------------------------------------------------
?>
<form id='login' class='form-signin' method="POST">
	<?= Asset::img("user.svg") ?>
	<h2>
	<a href='<?= Uri::create('/Login', array(), array('site'=>'none')) ?>'><?= \Config::get('config.title') ?></a>
	</h2>

	<?php if (!isset($_REQUEST['role'])) { ?>
	<select id='user-select' class='custom-select' name='user'>
			<option value="" disabled selected>Select user&hellip;</option>
			<?php
			foreach ($users as $group=>$list) {
				echo "<optgroup label='$group'>\n";
				foreach ($list as $user) {
					echo "<option>${user['username']}</option>\n";
				}
			}
			?>
	</select>

	<input type='number' name='pin' class='form-control pin' placeholder='PIN Number' required autocomplete='off' disabled/>
	<?php } else { ?>
	<input id='user-select' class='form-control' placeholder='Club Secretary Email Address' name='user' type='text' autocomplete='off'/>

	<input type='password' name='pin' class='form-control pin' placeholder='Password' required autocomplete='off' disabled/>
	<?php } ?>

	<div class="form-check">
		<input class='form-check-input' type="checkbox" name="remember-me"/>
		<label class='form-check-label'>Keep me logged in</label>
	</div>

	<?php if (!isset($_REQUEST['role'])) { ?>
	<style>#forgotten-password { visibility: hidden; }</style>
	<?php } ?>
	<div class='col-xs-6'>
		<a id='forgotten-password' href='<?= Uri::create('/User/ForgottenPassword') ?>' class='pull-right'>Forgotten Password</a>
	</div>

	<button class="btn btn-lg btn-primary btn-block" type="submit">Sign in</button>

	<?php if (!isset($_REQUEST['role'])) { ?>
	<a class='switch-login' href='<?= Uri::create('/Login', array(), array('role'=>'secretary')) ?>'>Secretary Login</a>
	<?php } else { ?>
	<a class='switch-login' href='<?= Uri::create('/Login') ?>'>Standard Login</a>
	<?php } ?>
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
