<?php if (isset($success)) { ?>
<p>Your password has been successfully changed.  Please make sure to
secure this password.</p>
<?php return; } ?>
<script>
$(document).ready(function() {
	$('#changepassword-form').validate({
		rules: { 
			confirm_password : { 
				equalTo: '[name=p]' ,
			}
		}
	});
});
</script>

<form id='changepassword-form'>
	<div class='form-group'>
		<label>Email Address</label>
		<input class='form-control' type='text' readonly name='e' value='<?= $email ?>'/>
	</div>

	<input type='hidden' name='h' value='<?= $hash ?>'/>
	<input type='hidden' name='ts' value='<?= $timestamp ?>'/>

	<div class='form-group'>
		<label>New Password</label>
		<input class='form-control' type='password' name='p' required/>
	</div>

	<div class='form-group'>
		<label>Confirm Password</label>
		<input class='form-control' type='password' name='confirm_password' required/>
	</div>

	<div>
		<button class='btn btn-success' type='submit'>Submit</button>
		<button class='btn btn-normal' type='reset'>Cancel</button>
	</div>

</form>
