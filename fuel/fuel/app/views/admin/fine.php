<script>
$(document).ready(function() {
	$('#match').typeahead({
		source: ['Division 1','Division 2','Division 3','Division 4']
	});
});
</script>
<form class='form-horizontal' action='<?= Uri::create('admin/fine') ?>' method='POST'>

	<div class='form-group'>
		<label class='control-label col-sm-2'>Date</label>
		<div class='col-sm-8'>
			<input readonly class='form-control' value='2017-05-01'/>
		</div>
	</div>

	<div class='form-group'>
		<label class='control-label col-sm-2'>Club</label>
		<div class='col-sm-8'>
			<select class='form-control' name='club'>
			<?php foreach ($clubs as $club) {
				echo "<option>$club</option>";
			} ?>
			</select>
		</div>
	</div>

	<div class='form-group'>
		<label class='control-label col-sm-2'>Match</label>
		<div class='col-sm-8'>
			<input id='match' class='form-control' type='text' name='match' data-role='tagsinput' data-provide='typeahead'/>
		</div>
	</div>

	<div class='form-group'>
		<label class='control-label col-sm-2'>Player</label>
		<div class='col-sm-8'>
			<input class='form-control' type='text' name='player'/>
		</div>
	</div>

	<div class='form-group'>
		<label class='control-label col-sm-2'>Reason</label>
		<div class='col-sm-4'>
			<select class='form-control' name='reason'>
			</select>
		</div>

		<label class='control-label col-sm-1'>Amount</label>
		<div class='col-sm-3'>
			<input class='form-control' type='number' name='amount'/>
		</div>
	</div>

	<div class='form-group'>
		<div class='col-sm-8 col-sm-offset-2'>
			<button class='btn btn-success'>Submit</button>
			<a class='btn btn-warning'>Cancel</a> 
		</div>
	</div>
</form>
