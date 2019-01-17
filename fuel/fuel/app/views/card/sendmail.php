<form method='post'>
	
	<input type='hidden' name='id' value='<?= $id ?>'/>
<div class='form-group'>
	<label>To</label>
	<div><?= implode(", ", $to) ?></div>
</div>

<div class='form-group'>
	<label>Cc</label>
	<div><?= implode(", ", $cc) ?></div>
</div>

<div class='form-group'>
	<label>Subject</label>
	<div><?= $description ?> #<?= $id ?></div>
</div>

<div class='form-group'>
	<label>Message</label>
	<div><textarea class='form-control' name='message' rows='10' cols='40'></textarea></div>
</div>

<div class='form-group'>
	<button class='btn btn-success'>Send</button>
</div>
</form>

