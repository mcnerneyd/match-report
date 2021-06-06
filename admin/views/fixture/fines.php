<!-- <?php 
print_r($fines);
/*
https://www.gyrocode.com/articles/jquery-datatables-how-to-add-a-checkbox-column/
*/ ?> -->
<script>
$(document).ready(function() {
	$('#fines-table').dataTable({
		'order':[1],
		'columns':[
			{
				'orderable':false,
				'searchable':false,
				'className':'dt-body-center',
				'render':function(d,t,f,m) { return '<input type="checkbox" name="id[]">'; }
			},
			null,
			null,
			null,
			{'orderable':false},
			{'orderable':false},
			{'orderable':false},
		]
	});
	$('#fines-table').show();

	$('#fines-table thead input[type="checkbox"]').click(function() {
		var rows = $('#fines-table').DataTable().rows({'search':'applied'}).nodes();
		$('input[type="checkbox"]', rows).prop('checked',this.checked);
	});

	function getChecked() {
		return $('#fines-table').DataTable().$('input[type="checkbox"]:checked').map(function() {
			return $(this).parents('tr').data('id');
		}).get();
	}

	$('.form-group .btn-danger').click(function() {
		$.post({
			'url':'<?= Uri::create('Fine/IssueFines') ?>',
			'data':{'ids':getChecked()},
			'success':function(d,s,x) {
				location.reload(true);
			},
		});
	});

	$('.form-group .btn-warning').click(function() {
		$.ajax({
			'url':'<?= Uri::create('fines') ?>',
			'method':'delete',
			'data':{'ids':getChecked()},
			'success':function(d,s,x) {
				location.reload(true);
			}
		});
	});
});
</script>
<style>
#fines-table tr {
	cursor: pointer;
}
.command-group {
	margin-top: -40px;
	float:right;
}
.note {
	color: red;
}
</style>

<div class='form-group command-group'>
	<a class='btn btn-danger' href='#' title='Send the selected fines to the administrator for processing'><i class="fas fa-envelope"></i> Issue Fines</a>
	<a class='btn btn-warning' href='#' title='Cancel the selected fines'><i class="fas fa-trash-alt"></i> Cancel Fines</a>
</div>

<table id='fines-table' class='table table-condensed' style='display:none'>
	<thead>
		<tr>
			<th width='1em'><input type='checkbox'/></th>
			<th width='4em'>Date</th>
			<th>Competition</th>
			<th>Club</th>
			<th>Reason</th>
			<th>Fine</th>
			<th/></tr>
	</thead>
	<tbody>
<?php
foreach ($fines as $fine) {
	echo "<tr data-id='${fine['id']}' data-cardid='${fine['matchcard_id']}'
			title='".$fine['competition']." - ".$fine['home_team']." v ".$fine['away_team']."'>
		<td></td>
		<td>".substr($fine['date'], 0,10)."</td>
		<td>".$fine['competition']."</td>
		<td>".$fine['club']['name']."</td>
		<td>${fine['reason']}";

	if ($fine['has_notes']) echo ' <i class="note fas fa-sticky-note"></i>';	

	echo "</td>
		<td>&euro;${fine['amount']}</td>
		<td><a href='".Uri::create("Report/Card/n${fine['matchcard_id']}")."'>View</a></td></tr>\n\t";
}
?>
	</tbody>
</table>

