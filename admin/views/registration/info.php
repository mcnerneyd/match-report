<script>
$(document).ready(function() {
	$('button[type=reset]').click(function(e) {
		e.preventDefault();
		var username = '<?= $user['username'] ?>';
		$.ajax({method: 'PUT',
			url: '<?= Uri::create("user/refreshpin") ?>',
			data: { 'username' : username }}).done(function(data) {
				window.location.reload();
			});
	});
});
</script>
<style>
#pin {
	font-size: 200%;
	font-weight: bold;
}
</style>
<p>Your Club PIN Number is: <span id='pin'><?= $user['pin'] ?></span> <button class='btn btn-warning' type='reset'>Reset PIN <i class='glyphicon glyphicon-refresh'></i></button></p>

<p>Important: if you reset the PIN, you will have to inform all your team captains of the new PIN - or they will not be able to access the matchcard system.</p>

