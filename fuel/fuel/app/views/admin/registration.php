<script>
	$(document).ready(function() {
		$('#competitions-table').dataTable({
			"order": [[0, 'desc']],
			"columns": [
				{ "width": "5%", "orderable": false },
				{ "width": "100px", "orderable": false },
				{ "orderable": false },
				{ "orderable": false },
			]	
		});
		$('#competitions-table tbody').show();
		$('a[rel="delete"]').click(function(e) {
			e.preventDefault();
			debugger;
			id = $(this).closest('tr').data('id');
			$.ajax('<?= Uri::create('Admin/Registration') ?>',
				{
					method:"DELETE",
					data:{ "id":id },
				},
				).done(function(data) { window.location.reload(); });
		});
	});
</script>

<div class='form-group command-group'>
	<a class='btn btn-primary' href='#upload-registration' data-toggle='modal'><i class='glyphicon glyphicon-upload'></i> Upload</a>
</div>

<table id='competitions-table' class='table table-condensed table-striped'>
	<thead>
	<tr>
		<th>ID</th>
		<th>Date</th>
		<th>Club</th>
		<th></th>
	</tr>
	</thead>

	<tbody style='display:none'>
	<?php foreach ($registrations as $registration) {
		echo "<tr data-id='${registration['batch']}' title='${registration['delta']}'>
			<td>${registration['batch']}</td>
			<td>${registration['date']}</td>
			<td>${registration['club']}</td>
			<td class='command-group'>
	<a class='btn btn-xs btn-primary' rel='download'><i class='glyphicon glyphicon-download-alt'></i></a>
	<a class='btn btn-xs btn-danger' rel='delete'";
		if (!$registration['head']) echo " disabled";
		echo "><i class='glyphicon glyphicon-trash'></i></a>
			</td>
		</tr>\n";
	} ?>
	</tbody>
</table>

<div id='upload-registration' class='modal' role='dialog'>
  <div class='modal-dialog'>
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal">&times;</button>
        <h4 class="modal-title">Upload Registration</h4>
      </div>
      <div class="modal-body">
        <form action='<?= Uri::create('/Admin/Registration') ?>' method='POST' enctype="multipart/form-data">
					<div class='form-group'>
						<label for='configfile'>File</label>
						<input type='file' class='form-control' id='registrationfile' name='registrationfile'/>
					</div>
				</form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal" onclick="$('form').submit()">Upload</button>
        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div> <!-- #upload-registration -->
