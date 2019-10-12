<style>
@import url(http://fonts.googleapis.com/css?family=Open+Sans:400,600,800);

* {
	font-family:'Open Sans',sans-serif;
}
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
</style>
<script>
$(document).ready(function() {
	<?php if (site()) { ?>
		$('#login select[name=site] option[value=<?= site() ?>]').prop('selected', true);
	<?php } ?>

	$('#login select[name=site]').change(function() {
		if ($('#login select[name=site]').val() != '') {
			var input = $("<input>").attr("name", "login").attr("type", "hidden");
			$('#login').append($(input));
			$('#login').submit();
		} else {
			$('#user-select').children().remove();		
		}
	});
});
</script>

<form id='login' action='<?= url(null, 'loginUP') ?>' class='form-signin' method="POST">

	<select id='site-select' class='form-control' name='site' placeholder='Select Site'>
		<option value=''>Select Section...</option>
		<option value='lhamen'>Leinster Hockey Men</option>
		<option value='lhaladies'>Leinster Hockey Ladies</option>
		<option value='test'>Test</option>
	</select>

	<select id='user-select' class='form-control' name='user'>
	<?php 
		foreach ($users as $role=>$userz) { ?>
		<optgroup label='<?= $role ?>'/>
		<?php foreach ($userz as $user) { ?>
			<option><?= $user ?></option>	
	<?php 
			}
		} ?>
	</select>

	<input type='number' name='pin' class='form-control pin' placeholder='PIN Number' required autocomplete='off'/>

	<div class="checkbox row">
		<div class='col-xs-6'>
			<label>
				<input type="checkbox" name="remember-me" <?= $_COOKIE['noremember'] ? '' : 'checked' ?>> Keep me logged in
			</label>
		</div>
		<div class='col-xs-6'>
			<a href='user/forgotten.php?cc=<?= $username ?>' class='pull-right'>Forgotten PIN</a>
		</div>
	</div>

	<button class="btn btn-lg btn-primary btn-block" type="submit">Sign in</button>

</form>

	<!--div class="alert alert-danger" role="alert"><span class='glyphicon glyphicon-exclamation-sign'></span> There was a problem earlier adding goals to players.  This has been fixed.  Apologies for the inconvenience.</div-->

