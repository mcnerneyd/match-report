<style>
#clubs-table th:nth-of-type(2) { width: 50px; }
</style>
<script>
	$(document).ready(function() {
		$('#clubs-table').DataTable({
			columns:[
				{width:"30%"},
				{width:"60%", orderable: false},
				{orderable: false, width:"1em"},
			]
			});
		$('#clubs-table tbody').show();
		// $('a[rel=delete]').click(function(e) {
		$('#clubs-table').on('click','a[rel=edit]',function(e) {
			var id = $(this).closest('tr').data('id');
			var data = clubs[id];
			$('#add-club [name=id]').val(data['id']);
			$('#add-club .btn-success').text('Save');
			$('#add-club .modal-title').text('Edit Club');
			$('#add-club [name=clubname]').val(data['name']);
			$('#add-club').modal();
		});
		$('#clubs-table').on('click', 'a[rel=delete]', function(e) {
			e.preventDefault();
			var id = $(this).closest('tr').data('id');
			$.ajax('<?= Uri::create('Admin/Club') ?>',
				{
					method:"DELETE",
					data:{ "id":id },
				},
				).done(function(data) { window.location.reload(); });
		});
	});
	<?php $lightClubs = array();
	foreach ($clubs as $club) {
		$arr = $club->to_array();
		$lightClubs[$arr['id']] = $arr;
	} ?>
	var clubs = <?= json_encode($lightClubs) ?>;
</script>

<div class='form-group command-group'>
	<a class='btn btn-primary' href='#add-club' data-bs-toggle='modal'><i class="fas fa-plus-circle"></i> Add Club</a>
</div>

<table id='clubs-table' class='table table-condensed table-striped'>
	<thead>
	<tr>
		<th>Club</th>
		<th class='desktop'>Competitions</th>
		<th/>
	</tr>
	</thead>

	<tbody style='display:none'>
<?php foreach ($clubs as $club) {
		echo "\t\t<tr data-id='{$club['id']}'>
			<td>{$club['name']}</td>
			<td class='label-list'>";
		$comps = array();
		foreach ($club['team'] as $team) {
			$comps = array_merge($comps, $team['competition']);
		}
		usort($comps, function($a,$b) {
			$sa = $a['sequence'];
			$sb = $b['sequence'];
			if ($sa == $sb) $ret = 0;
			else $ret = ($sa < $sb) ? -1 : 1;
			return $ret;
		});
		foreach ($comps as $teamComp) {
			echo "<span class='d-none d-md-inline'><span class='badge label-".($teamComp['teamsize']?'league':'cup')."'>{$teamComp['name']}</span></span>";
		}
		echo "</td>
			<td class='command-group'>
					<a class='btn btn-sm btn-warning' rel='edit'><i class='fas fa-edit'></i></a>
					<a class='btn btn-sm btn-danger' rel='delete'><i class='fas fa-trash-alt'></i></a>
			</td>
		</tr>\n";
	} ?>
	</tbody>
</table>

<div id='add-club' class='modal' role='dialog'>
  <div class='modal-dialog'>
    <div class="modal-content">
      <div class="modal-header">
        <h4 class="modal-title">Add New Club</h4>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <form action='<?= Uri::create('/Admin/Club') ?>' method='POST'>
					<input type='hidden' name='id'/>
					<div class='form-group'>
						<label for='clubname'>Club Name</label>
						<input type='text' class='form-control' id='clubname' name='clubname' pattern='[A-Z][A-Za-z ]+'/>
					</div>
				</form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-success" data-bs-dismiss="modal" onclick="$('form').submit()">Add</button>
        <button type="button" class="btn btn-warning" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div> <!-- #add-club -->
