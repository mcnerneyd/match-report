<script>
	$(document).ready(function() {
		$('#competitions-table').dataTable({
			"columns": [
				{ "width": "20%" },
				{ "width": "10%" },
				{ "width": "10%", "orderable": false },
				{ "width": "10%", "orderable": false, "className":"dt-center" },
				{ "orderable": false },
				{ "orderable": false },
			]
		});
		$('#competitions-table tbody').show();
		$('a[rel=delete]').click(function(e) {
			e.preventDefault();
			var code = $(this).closest('tr').data('code');
			$.ajax('<?= Uri::create('Admin/Competition') ?>',
				{
					method:"DELETE",
					data:{ "code":code },
				},
				).done(function(data) { window.location.reload(); });
		});
	});
</script>

<div class='form-group command-group hidden-sm hidden-xs'>
	<a class='btn btn-primary' href='#add-competition' data-toggle='modal'><i class='glyphicon glyphicon-plus-sign'></i> Add Competition</a>
	<a class='btn btn-success' href='<?= Uri::create('Admin/Refresh') ?>'><i class='glyphicon glyphicon-refresh'></i> Refresh</a>
</div>

<table id='competitions-table' class='table table-condensed table-striped'>
	<thead>
	<tr>
		<th>Competition</th>
		<th>Code</th>
		<th>Team Size</th>
		<th>Starred</th>
		<th>Teams</th>
		<th/>
	</tr>
	</thead>

	<tbody style='display:none'>
	<?php foreach ($competitions as $competition) {
		echo "<tr data-code='${competition['code']}'>
			<td>${competition['name']}</td>
			<td>${competition['code']}</td>
			<td>${competition['teamsize']}</td>
			<td>${competition['teamstars']}</td>
			<td class='label-list'>";
		foreach ($competition['team'] as $team) {
			echo "<span class='label label-team'>".$team['club']['code'].$team['team']."</span>";
		}
		echo "</td>
				<td class='command-group'>
					<a class='btn btn-xs btn-danger' rel='delete'><i class='glyphicon glyphicon-trash'></i></a>
				</td>
		</tr>";
	} ?>
	</tbody>
</table>

<div id='add-competition' class='modal' role='dialog'>
  <div class='modal-dialog'>
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal">&times;</button>
        <h4 class="modal-title">Add New Competition</h4>
      </div>
      <div class="modal-body">
        <form action='<?= Uri::create('/Admin/Competition') ?>' method='POST'>
					<div class='row'>
						<div class='form-group col-xs-12'>
							<label for='competitionname'>Competition Name</label>
							<input type='text' class='form-control' id='competitionname' name='competitionname'/>
						</div>
					</div>
					<div class='row'>
						<div class='col-xs-12'>
							<label>
								<input type='radio' name='option_type' id='option-type-league' value='league'/> League Competition
							</label>
						</div>
						<div class='col-xs-12'>
							<label>
								<input type='radio' name='option_type' id='option-type-cup' value='cup'/> Cup Competition
							</label>
						</div>
					</div>
					<div class='row'>
						<div class='form-group col-xs-6'>
							<label for='competitioncode'>Code</label>
							<input type='text' class='form-control' id='competitioncode' name='competitioncode'/>
						</div>
						<div class='form-group col-xs-6'>
							<label for='competitiondate'>Cutoff Date</label>
							<input type='text' class='form-control' id='competitiondate' name='competitiondate'/>
						</div>
					</div>
					<div class='row'>
						<div class='form-group col-xs-6'>
							<label for='competition-teamsize'>Team Size</label>
							<input type='text' class='form-control' id='competition-teamsize' name='competition-teamsize'/>
						</div>
						<div class='form-group col-xs-6'>
							<label for='competition-teamstars'>Team Stars</label>
							<input type='text' class='form-control' id='competition-teamstars' name='competition-teamstars'/>
						</div>
					</div>
				</form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-success" data-dismiss="modal" onclick="$('#add-competition form').submit()">Add</button>
        <button type="button" class="btn btn-warning" data-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div> <!-- #add-competition -->
