<script>
$(document).ready(function() {
	$('button[type=reset]').click(function(e) {
		e.preventDefault();
		var username = "";
		$.ajax({method: 'PUT',
			url: '<?= Uri::create("user/refreshpin") ?>',
			data: { 'username' : username }}).done(function(data) {
				window.location.reload();
			});
	});
});
</script>
<style>
.pin {
	font-size: 200%;
	font-weight: bold;
	letter-spacing: 0.4em;
}
td { padding: 1rem 5rem 1rem 0; }
tr { border-bottom: 1px solid gray; }
</style>
<?php
$pinZ = DB::query("select max(pin) pin from pins")->execute();
$pinZ = $pinZ[0]['pin'];
if (!$pinZ) $pin = 0;
else $pin = intval($pinZ) + 1;
Log::info("No pin found - building from $pin / $pinZ");

for (;$pin<10000;$pin++) {
	$pinZ = strrev(substr(strrev("000000$pin"), 0, 4));
	$hash = \Auth::hash_password($pinZ);
	DB::query("insert into pins (pin, hash) values ('$pinZ', '$hash')")->execute();
}
?>
<div id='registration-info'>
<table>
	<?php
foreach ($users as $user) {
	$pinZ = DB::query("select pin from pins where hash='".$user['password']."'")->execute();
	$pinZ = $pinZ[0]['pin'];
		echo "<tr>
		<td>".$user['username']."</td>
		<td>".($user->section?$user->section['name']:"All Sections")."</td>
		<td class='pin'>".$pinZ."</td>
		<td><button disabled class='btn btn-warning' type='reset'>Reset PIN <i class='fas fa-sync-alt'></i></button></td>
	</tr>\n";
}
?>
</table>


<!-- <p>Your Club PIN Number is: <span id='pin'><?= $pinZ ?></span> <button class='btn btn-warning' type='reset'>Reset PIN <i class="fas fa-sync-alt"></i></button></p> -->

<p>Important: if you reset the PIN, you will have to inform all your team captains of the new PIN - or they will not be able to access the matchcard system.</p>
</div>
