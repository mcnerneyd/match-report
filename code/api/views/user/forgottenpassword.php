<?php if (isset($email)) { ?>
<div class='legacy'>
    <p>An email has been sent to <?= $email ?> with instructions for resetting your password.</p>

    <small>The existence of a user with the email address has not been verified.</small>
</div>
<?php return; } ?>
<form class='legacy'>

	<p>A email will be sent to this address with instructions for
	resetting your password.</p>

	<div class='form-group'>
		<label>Email Address</label>
		<input class='form-control' type='text' name='e'/>
	</div>

	<div>
		<button class='btn btn-success' type='submit'>Submit</button>
		<a class='btn btn-default' href='<?= Uri::create("/Login") ?>'>Cancel</a>
	</div>

</form>
