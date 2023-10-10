<script>
	$(document).ready(function() {
		$('#clubs-table').DataTable({
			columns:[
				null,
				null,
				null,
				null,
			]
		});

		$('#tasks-table tbody').show();

		$('#datepicker').datetimepicker();
	});
</script>

<div class='form-group command-group'>
  <a class='btn btn-primary' href='#add-task' data-bs-toggle='modal'><i class='glyphicon glyphicon-th-list'></i> Add Task</a>
</div>

<table id='tasks-table' class='table table-condensed table-striped'>
	<thead>
		<tr>
			<th>Command</th>
			<th>Next Execution</th>
			<th>Recurrance</th>
			<th></th>
		</tr>
	</thead>

	<tbody>
<?php
foreach ($tasks as $task) {
	echo "<tr>
		<td>${task['command']}</td>
		<td>${task['datetime']}</td>
		<td>${task['recur']}</td>
		<td class='command-group'>
			<a class='btn btn-success btn-xs'><i class='glyphicon glyphicon-play'></i></a>
			<a class='btn btn-danger btn-xs'><i class='glyphicon glyphicon-trash'></i></a>
		</td>
	</tr>";
}
?>
	</tbody>
</table>

<div class="modal fade" tabindex="-1" role="dialog" id='add-task'>
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h4 class="modal-title">Add Task</h4>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
				<form class='form-horizontal'>

					<div class='form-group'>
						<label class='control-label col-sm-3'>Command</label>
						<div class='col-sm-8'>
							<input class='form-control' type='text' name='command'/>
						</div>
					</div>

					<div class='form-group'>
						<label class='control-label col-sm-3'>Next Execution</label>
						<div class='col-sm-6 input-group date'>
							<input type='text' class='form-control' id='datepicker'/>
						</div>
					</div>

					<div class='form-group'>
						<label class='control-label col-sm-3'>Recurrance</label>
						<div class='col-sm-8'>
							<select class='form-control' name='reason'>
								<option value='None'>--- Select Recurrance ---</option>
								<option>Quarter Hourly</option>
								<option>Hourly</option>
								<option>Daily</option>
								<option>Weekly</option>
								<option>Monthly</option>
								<option>Yearly</option>
							</select>
						</div>
					</div>

				</form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-bs-dismiss="modal">Close</button>
        <button type="button" class="btn btn-primary">Save</button>
      </div>
    </div><!-- /.modal-content -->
  </div><!-- /.modal-dialog -->
</div><!-- /.modal -->
