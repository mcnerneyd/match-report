<?php Config::load('custom.db', 'config'); ?>
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
	margin-top: 20px;
  margin-bottom: -1px;
  border-bottom-right-radius: 0;
  border-bottom-left-radius: 0;
}
.form-signin input {
  margin-bottom: 10px;
  border-top-left-radius: 0;
  border-top-right-radius: 0;
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

<?php 
// --------------------------------------------------------
//  Site Select
// --------------------------------------------------------
if (!Session::get('site', false)) { ?>
<div id='site-select'>
	<a data-site='lhamen' class='btn btn-success col-md-12 col-xs-12' href='<?= Uri::create('/Login', array(), array('site'=>'lhamen')) ?>'>Leinster Mens Hockey</a>
	<a data-site='lhaladies' class='btn btn-success col-md-12 col-xs-12' href='<?= Uri::create('/Login', array(), array('site'=>'lhaladies')) ?>'>Leinster Ladies Hockey</a>
	<a data-site='lhajunior' class='btn btn-success col-md-12 col-xs-12' href='<?= Uri::create('/Login', array(), array('site'=>'lhajunior')) ?>'>Leinster Junior</a>
	<a data-site='test' class='btn btn-success col-md-12 col-xs-12' href='<?= Uri::create('/Login', array(), array('site'=>'test')) ?>'>Test Site</a>
</div>
<?php return; } ?>

<?php 
// --------------------------------------------------------
//  User Login
// --------------------------------------------------------
?>
<form id='login' class='form-signin' method="POST">
	<h2><?= \Config::get('config.title') ?></h2>
	<a href='<?= Uri::create('/Login', array(), array('site'=>'none')) ?>'>Change Section</a>

	<?php if (!isset($_REQUEST['role'])) { ?>
	<select id='user-select' class='form-control' name='user'>
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

	<div class="checkbox row">
		<div class='col-xs-6'>
			<label>
				<input type="checkbox" name="remember-me" <?= /*$_COOKIE['noremember']*/false ? '' : 'checked' ?>> Keep me logged in
			</label>
		</div>
		<?php if (isset($_REQUEST['role'])) { ?>
		<div class='col-xs-6'>
			<a href='<?= Uri::create('/User/ForgottenPassword') ?>' class='pull-right'>Forgotten Password</a>
		</div>
		<?php } ?>
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
	var site = <?= "'" + Session::get('site') + "'" ?: 'null' ?>;

	$('#user-select').change(function() {
		$("input[name='pin']").prop('disabled', $(this).val() == "");
	});

	$('#user-select').keyup(function() {
		$("input[name='pin']").prop('disabled', $(this).val() == "");
	});

	if (site != null) $('#site-select').val(site);
	else $('#site-select').val('');
});
</script>
