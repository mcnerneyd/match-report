<script>
	$(document).ready(function() {
		$('#competitions-table').dataTable({
			"columns": [
				{ "width": "20%" },
				{ "width": "10%" },
				{ "width": "10%", "orderable": false },
				{ "width": "10%", "orderable": false, "className":"dt-center" },
				{ "orderable": false },
			]
		});
		$('#competitions-table tbody').show();
	});
</script>
<style>
.command-group {
	margin-top: -40px;
	float:right;
}
</style>

<div class='form-group command-group hidden-sm hidden-xs'>
	<a class='btn btn-primary' href='#upload-config-modal' data-toggle='modal'><i class='glyphicon glyphicon-upload'></i> Upload</a>
	<a class='btn btn-primary' href='<?= Uri::create("Admin/Configfile") ?>'><i class='glyphicon glyphicon-download'></i> Download</a>
</div>

<table id='competitions-table' class='table table-condensed table-striped'>
	<thead>
	<tr>
		<th>Competition</th>
		<th>Code</th>
		<th>Team Size</th>
		<th>Starred</th>
		<th>Teams</th>
	</tr>
	</thead>

	<tbody style='display:none'>
	<?php foreach ($competitions as $competition) {
		echo "<tr>
			<td>${competition['name']}</td>
			<td>${competition['code']}</td>
			<td>${competition['teamsize']}</td>
			<td>${competition['teamstars']}</td>
			<td class='label-list'>";
		foreach ($competition['team'] as $team) {
			echo "<span class='label label-team'>".$team['club']['code'].$team['team']."</span>";
		}
		echo "</td>
		</tr>";
	} ?>
	</tbody>
</table>

<div id='upload-config-modal' class='modal' role='dialog'>
  <div class='modal-dialog'>
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal">&times;</button>
        <h4 class="modal-title">Upload League Configuration</h4>
      </div>
      <div class="modal-body">
        <form action='<?= Uri::create('/Admin/ConfigFile') ?>' method='POST' enctype="multipart/form-data">
					<div class='form-group'>
						<label for='configfile'>File</label>
						<input type='file' class='form-control' id='configfile' name='configfile'/>
					</div>
				</form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal" onclick="$('form').submit()">Upload</button>
        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div> <!-- #upload-config-modal -->
