<script>
	$(document).ready(function() {
		$('#dofines').click(function() {
			$.post({
				'url':'<?= Uri::create('Fixture/Fine.json') ?>',
				'success':function(d,s,x) {
					$('#output').text(d);	
				}
			});
		});
	});
</script>
<a id='dofines'>Check Fines</a>
<br>
<textarea id='output'></textarea>
