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
</style>

<?php if (!Session::get('site', false)) { ?>
<div id='site-select'>
	<a data-site='lhamen' class='btn btn-success col-md-12 col-xs-12' href='<?= Uri::create('/Login', array(), array('site'=>'lhamen')) ?>'>Leinster Mens Hockey</a>
	<a data-site='lhaladies' class='btn btn-success col-md-12 col-xs-12' href='<?= Uri::create('/Login', array(), array('site'=>'lhaladies')) ?>'>Leinster Ladies Hockey</a>
	<a data-site='test' class='btn btn-success col-md-12 col-xs-12' href='<?= Uri::create('/Login', array(), array('site'=>'test')) ?>'>Test Site</a>
</div>
<?php } else { ?>
<form id='login' class='form-signin' method="POST">
	<h2><?= \Config::get('config.title') ?></h2>
	<a href='<?= Uri::create('/Login', array(), array('site'=>'none')) ?>'>Change Section</a>

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

	<script>
	$(document).ready(function() {
		var site = <?= "'" + Session::get('site') + "'" ?: 'null' ?>;

		$('#user-select').change(function() {
			$("input[name='pin']").prop('disabled', false);
		});

		if (site != null) $('#site-select').val(site);
		else $('#site-select').val('');

		<?php if ($preferredUser) { ?>
		$('#user-select').val('<?= $preferredUser ?>');
		$("input[name='pin']").prop('disabled', false);
		<?php } ?>
	});
	</script>

	<input type='number' name='pin' class='form-control pin' placeholder='PIN Number' required autocomplete='off' disabled/>

	<div class="checkbox row">
		<div class='col-xs-6'>
			<label>
				<input type="checkbox" name="remember-me" <?= /*$_COOKIE['noremember']*/false ? '' : 'checked' ?>> Keep me logged in
			</label>
		</div>
		<div class='col-xs-6'>
			<a href='user/forgotten.php?cc=' class='pull-right'>Forgotten PIN</a>
		</div>
	</div>

	<button class="btn btn-lg btn-primary btn-block" type="submit">Sign in</button>

</form>
<?php } 
