<script>
	$(document).ready(function() {
		$('#competitions-table').dataTable({
			"columns": [
				{ "width": "20%" },
				{ "width": "10%"},
				{ "width": "5%", "orderable": false, "className":"number"},
				{ "width": "5%", "orderable": false, "className":"number" },
				{ "width": "40%", "orderable": false },
				{ "width": "0", "orderable": false },
			]
		});
		$('#competitions-table tbody').show();
		$('#competitions-table').on('click','a[rel=edit]',function(e) {
			var id = $(this).closest('tr').data('id');
			var data = competitions[id];
			console.log("Editing: id=" + id)
			$('#add-competition [name=id]').val(id);
			$('#add-competition [name=section]').val(data['section_id']).attr('disabled', true);
			$('#add-competition [name=competitionname]').val(data['name']).attr('readonly', true);
			$('#add-competition [name=age-group]').val(data['groups']);
			$('#add-competition [name=competition-teamsize]').val(data['teamsize']);
			$('#add-competition [name=competition-teamstars]').val(data['teamstars']);
			$('#add-competition .btn-success').text('Save');
			$('#add-competition .modal-title').text('Edit Competition');

			$('#add-competition [value='+data['format']+']').prop('checked',true);

			$('#add-competition').modal('show');
		});
		$('a[rel=delete]').click(function(e) {
			e.preventDefault();
			var id = $(this).closest('tr').data('id');
			$.ajax('<?= Uri::create('Admin/Competition') ?>',
				{
					method:"DELETE",
					data:{ "id":id },
				},
				).done(function(data) { window.location.reload(); });
		});
	});

	<?php $lightCompetitions = array();
			foreach ($competitions as $competition) {
			    $arr = $competition->to_array();
			    $key = $competition['id'];
			    $lightCompetitions[$key] = $arr;
			    $lightCompetitions[$key]['zsection'] = $competition['section']['name'];
			} ?>
	var competitions = <?= json_encode($lightCompetitions) ?>;
</script>

<div class='form-group command-group hidden-sm hidden-xs'>
	<a class='btn btn-primary' role="button" href='#add-competition' data-bs-toggle='modal'><i class="fas fa-plus-circle"></i> Add Competition</a>
	<a class='btn btn-success' role="button" href='<?= Uri::create('competitions?rebuild=true') ?>'><i class="fas fa-sync-alt"></i> Rebuild</a>
</div>

<table id='competitions-table' class='table table-condensed table-striped'>
	<thead>
		<tr>
			<th>Section</th>
			<th>Competition</th>
			<th>Team Size</th>
			<th>Starred</th>
			<th>Teams</th>
			<th/>
		</tr>
	</thead>

	<tbody style='display:none'>
	<?php foreach ($competitions as $competition) {
	    echo "<tr data-id='{$competition['id']}'>
			<td>".($competition->section ? $competition->section->getProperty('shorttitle') : "")."</td>
			<td>{$competition['name']}</td>
			<td>{$competition['teamsize']}</td>
			<td>{$competition['teamstars']}</td>
			<td class='label-list'><div>";
	    foreach ($competition->team as $team) {
	        echo "<span class='badge label-team'>".$team['club']['name'].' '.$team['name']."</span>";
	    }
	    echo "</div></td>
				<td class='command-group'>
					<a class='btn btn-xs btn-sm btn-warning' rel='edit'><i class='fas fa-edit'></i></a>
					<a class='btn btn-xs btn-sm btn-danger' rel='delete'><i class='fas fa-trash-alt'></i></a>
				</td>
		</tr>";
	} ?>
	</tbody>
</table>

<div id='add-competition' class='modal' role='dialog'>
  <div class='modal-dialog'>
    <div class="modal-content">
      <div class="modal-header">
        <h4 class="modal-title">Add New Competition</h4>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <form action='<?= Uri::create('/Admin/Competition') ?>' method='POST'>
			<div class='row'>
				<input type='hidden' name='id'/>
					<div class='form-group col-md-12'>
						<select class='form-control' id='section-select' name='section' required>
							<option>Select Section...</option>
							<?php foreach ($sections as $s) {
								echo "<option value='".$s['id']."'>".$s['name']."</option>";
							} ?>
						</select>
					</div>

					<div class='form-group col-md-12'>
						<label for='competitionname'>Competition Name</label>
						<input type='text' class='form-control' id='competitionname' name='competitionname'  pattern='[A-Z][A-Za-z0-9 ]*'/>
					</div>
					<div class='col-md-12'>
						<label>
							<input type='radio' name='option_type' id='option-type-league' value='league'/> League Competition
						</label>
					</div>
					<div class='col-md-12'>
						<label>
							<input type='radio' name='option_type' id='option-type-cup' value='cup'/> Cup Competition
						</label>
					</div>

					<div class='form-group col-md-12'>
						<label>Age Group(s)</label>
						<textarea cols='30' rows='5' class='form-control' name='age-group'></textarea>
					</div>

					<div class='form-group col-md-6'>
						<label for='competition-teamsize'>Team Size</label>
						<input type='number' class='form-control' id='competition-teamsize' name='competition-teamsize'/>
					</div>
					<div class='form-group col-md-6'>
						<label for='competition-teamstars'>Team Stars</label>
						<input type='number' class='form-control' id='competition-teamstars' name='competition-teamstars'/>
					</div>
				</div>
			</form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-success" data-bs-dismiss="modal" onclick="$('#add-competition form').submit()">Add</button>
        <button type="button" class="btn btn-warning" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div> <!-- #add-competition -->
