<script>
	$(document).ready(function() {
		$('#fixtures-table').DataTable({
			columnDefs:[{targets:[2,4],orderable:false},
				],
		});
		$('#fixtures-table').show();
		$('#fixtures-table').on('change', 'input[type=checkbox]', function() {
			tr = $(this).closest('tr');
			fixtureId = tr.data('fixtureid');
			$.ajax('<?= Uri::create("fixtures/") ?>' + fixtureId + "?show=" + ($(this).checked?"true":"false"),
				{
					method:'PUT',
				});
		});
		$('#fixtures-table tbody').on('click', 'tr', function(e) {
			$("#issue-fine select[name='reason']").val('None');
			$('#issue-fine .radio:nth-of-type(1) .team-name').text($('td:nth-child(4)', this).text());
			$('#issue-fine .radio:nth-of-type(2) .team-name').text($('td:nth-child(6)', this).text());
			$('#issue-fine .form-group:nth-of-type(1) p').text('#' + $(this).data('fixtureid'));
			$('#issue-fine .form-group:nth-of-type(2) p').text($('td:nth-child(3)', this).text());
			$('#issue-fine .form-group:nth-of-type(3) p').text($('td:nth-child(2)', this).text());
			$('#issue-fine input[name="fixtureid"]').val($(this).data('fixtureid'));
			setFine();
			$('#issue-fine').modal('show');
		});
		$("#issue-fine select[name='reason']").change(setFine);
		$("#issue-fine button[type='submit']").click(function() {
			$.post('<?= Uri::create('fine') ?>', $('#issue-fine form').serialize(), function(data) {
				window.location.reload();
				$.notify({message: 'Fine Issued'}, {
					placement: { from: 'top', align: 'right' },		
					delay: 1000,
					animate: {
						enter: 'animated bounceInDown',
						exit: 'animated bounceOutUp'
					},
					type: 'success'});
				});
		});
		function setFine() {
			$("#issue-fine input[name='amount']").val($("option:selected", this).data('value'));
		}
	});
</script>
<style>
#fixtures-table tr {
	cursor: pointer;
}
</style>

