	$(document).ready(function() {
		var site = <?= "'" + Session::get('site') + "'" ?: 'null' ?>;

		$('#user-select').change(function() {
			$("input[name='pin']").prop('disabled', false);
			localStorage.setItem("preferred-username", $('#user-select').val());
		});

		if (site != null) $('#site-select').val(site);
		else $('#site-select').val('');

		var preferredUser = localStorage.getItem("preferred-username");
		if (preferredUser) {
			$('#user-select').val(preferredUser);
			$("input[name='pin']").prop('disabled', false);
		}
	});
