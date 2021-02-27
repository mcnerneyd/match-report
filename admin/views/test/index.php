<?php
$flash = Session::get_flash('msg');
if ($flash) {
	if (is_array($flash)) {
		$msg = $flash['msg'];
		$style = $flash['style'];
	} else {
		$msg = $flash;
		$style = "success";
	}
	echo "<div class='alert alert-$style'>$msg</div>";
}
?>

<form>
	<h2>Find Cards</h2>
	<div class='form-group'>
	<label>Fixture Id <input class='form-control' type='number' name='fixture_id'/></label>
	</div>
	
	<button class='btn btn-success' type='submit'>Search</button>
</form>

<?php if (isset($cards)) { ?>
<table>
<?php foreach ($cards as $card) {
	echo "<tr><td>${card['id']}</td></tr>\n";
} ?>
</table>
<?php } ?>

<form action='<?= Uri::create('test/incident') ?>'>
	<h2>Create Incident</h2>
	<div class='form-group'>
	<label>Type <select class='form-control' name='type'>
		<option>Played</option>
		<option>Ineligible</option>
		<option>Red Card</option>
		<option>Yellow Card</option>
		<option>Scored</option>
		</select></label>
	</div>
	<div class='form-group'>
	<label>Player <input type='text' class='form-control' name='player'/></label>
	</div>
	<div class='form-group'>
	<label>Club <select class='form-control' name='club_id'>
	<?php foreach ($clubs as $club) {
		echo "<option value='${club['id']}'>${club['name']}</option>\n";
	} ?></select></label>
	</div>
	<div class='form-group'>
	<label>Card Id <input class='form-control' type='number' name='card_id'/></label>
	</div>
	<div class='form-group'>
	<label>Detail <input class='form-control' type='text' name='detail'/></label>
	</div>
	
	<button class='btn btn-success' type='submit'>Create Incident</button>
</form>

<form action='<?= Uri::create('test/reset') ?>'>
	<h2>Reset Card</h2>
	<label>Card ID <input type='number' name='card_id'/></label>

	<button type='submit'>Reset Card</button>
</form>
