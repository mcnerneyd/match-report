<?php if (isset($email)) { ?>
<p>An email has been sent to <?= $email ?> with instructions for
resetting your password.</p>
<?php return; } ?>
<form>

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
