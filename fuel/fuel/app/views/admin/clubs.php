<style>
#clubs-table th:nth-of-type(2) { width: 50px; }
</style>
<script>
	$(document).ready(function() {
		$('#clubs-table').DataTable({
			columns:[
				{width:"2em"},
				{width:"30%"},
				{orderable: false},
				{orderable: false, width:"1em"},
			]
			});
		$('#clubs-table tbody').show();
		// $('a[rel=delete]').click(function(e) {
		$('#clubs-table').on('click', 'a[rel=delete]', function(e) {
			e.preventDefault();
			var code = $(this).closest('tr').data('code');
			$.ajax('<?= Uri::create('Admin/Club') ?>',
				{
					method:"DELETE",
					data:{ "code":code },
				},
				).done(function(data) { window.location.reload(); });
		});
	});
</script>

<div class='form-group command-group'>
	<a class='btn btn-primary' href='#add-club' data-toggle='modal'><i class='glyphicon glyphicon-plus-sign'></i> Add Club</a>
</div>

<table id='clubs-table' class='table table-condensed table-striped'>
	<thead>
	<tr>
		<th>Code</th>
		<th>Club</th>
		<th class='desktop'>Competitions</th>
		<th/>
	</tr>
	</thead>

	<tbody style='display:none'>
<?php foreach ($clubs as $club) {
		$club->getTeamSizes();
		echo "\t\t<tr data-code='${club['code']}'>
			<td>${club['code']}</td>
			<td>${club['name']}</td>
			<td>";
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
			echo "<span class='hidden-sm hidden-xs label label-".($teamComp['teamsize']?'league':'cup')."'>${teamComp['name']}</span>";
			echo "<span class='hidden-md hidden-lg label label-".($teamComp['teamsize']?'league':'cup')."'>${teamComp['code']}</span>";
		}
		echo "</td>
			<td class='command-group'>
				<a class='btn btn-xs btn-danger' rel='delete'><i class='glyphicon glyphicon-trash'></i></a>
			</td>
		</tr>\n";
	} ?>
	</tbody>
</table>

<div id='add-club' class='modal' role='dialog'>
  <div class='modal-dialog'>
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal">&times;</button>
        <h4 class="modal-title">Add New Club</h4>
      </div>
      <div class="modal-body">
        <form action='<?= Uri::create('/Admin/Club') ?>' method='POST'>
					<div class='form-group'>
						<label for='clubname'>Club Name</label>
						<input type='text' class='form-control' id='clubname' name='clubname'/>
					</div>
					<div class='form-group'>
						<label for='clubcode'>Code</label>
						<input type='text' class='form-control' id='clubcode' name='clubcode'/>
					</div>
				</form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-success" data-dismiss="modal" onclick="$('form').submit()">Add</button>
        <button type="button" class="btn btn-warning" data-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div> <!-- #add-club -->
