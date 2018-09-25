<?php
Config::load('custom.db', 'config');

$registrationAllowed = FALSE;
if (Config::get('config.automation_allowrequest')) {
	$registrationAllowed = TRUE;
}
if (Auth::has_access('admin.all')) {
	$registrationAllowed = 'all';
}

echo "<!-- Registration Allowed: $registrationAllowed -->";
?>
<script>
	$(document).ready(function() {
		$('#registration-table').DataTable({
				"order": [[2, 'desc']],
				"columns":[
					{ "orderable": false },
					{ "orderable": false },
					{ },
					{ "orderable": false },
					{} 
				]
			});
		$('#registration-table tbody').show();
		$('#view-registration input').datepicker({
			dateFormat: "yy-mm-dd",
			showOtherMonths: true,
			selectOtherMonths: true,
			});
		$('#view-registration a').click(function(e) {
			e.preventDefault();
			window.location = "./Registration/Registration?d=" + $('#view-registration input').val()
				+ "&c=" + $("#registration-club select").val();
		});
		$("#upload-registration button[type=submit]").click(function(e) {
			$('#upload-registration form').submit();
		});

		$(".btn-download").click(function(e) {
			e.preventDefault();
			window.location.href = "./Registration/Registration?f=" + $(this).closest("tr").data("filename")
				+ "&c=" + $("#registration-club select").val();
		});

		$(".btn-delete").click(function(e) {
			e.preventDefault();
			var row = $(this).closest("tr");
			$.ajax('<?= Uri::create("RegistrationApi") ?>', {
					method:"DELETE",
					data:{"f":row.data("filename"), "c":row.data("club")},
			} ).done(function(data) { window.location.reload(); });
		});

		$('#registration-club select').change(function() {
			window.location.href='<?php Uri::create('Registration') ?>?c=' + $(this).val();
		});

		$('#registration-club select').val('<?= $club ?>');

		$('.form-confirmation input[type=checkbox]').on('change', function() {
				$('#upload-registration button[type=submit]').prop('disabled', !this.checked);
		});
	});
</script>
<style>
.form-confirmation {
		position: relative;
		height: 45px;
}
.form-confirmation p {
	position: absolute;
	top: 2px;
	left: 2em;
	font-size: 75%;
	font-style: italic;
}
.form-control label {
	margin-bottom:0px;
	font-size: 80%;
}
</style>

<div class='command-group form-inline col-xs-6'>
	<?php if ($registrationAllowed === 'all') { ?>
	<div id='registration-club' class='form-group'>
		<select class='form-control' name='club'>
			<?php foreach ($clubs as $c) {
				echo "<option>".$c['name']."</option>";
			}?>
		</select>
	</div>
	<?php } ?>
	<div class='form-group'>
		<?php if ($registrationAllowed) { ?>
		<a class='btn btn-primary' id='upload-button' data-target='#upload-registration' data-toggle='modal'><i class='glyphicon glyphicon-cloud-upload'></i><span class='hidden-xs hidden-sm'> Upload</span></a>
		<?php } ?>
	</div>
	<div id='view-registration' class="input-group form-group">
		<input type="text" class="form-control" placeholder="Date" aria-label="Date" aria-describedby="basic-addon2">
		<div class="input-group-btn">
			<a class="btn btn-success">View</a>
		</div>
	</div>
</div>

<table id='registration-table' class='table table-condensed table-striped'>
	<thead>
		<tr>
			<th>Club</th>
			<th>File</th>
			<th>Timestamp</th>
			<th>Checksum</th>
			<th></th>
		</tr>
	</thead>
	<tbody style='display:none'>
<?php
foreach ($registrations as $registration) {
	$date = Date::forge($registration['timestamp']);
	echo "<tr data-filename='${registration['name']}' data-club='${registration['club']}'>
		<td>${registration['club']}</td>
		<td>${registration['name']}</td>
		<td>".strtoupper($date->format("%Y-%m-%d %H:%M:%S"))."</td>
		<td>${registration['cksum']}</td>
		<td>
			<a class='btn btn-primary btn-xs btn-download'>Download <i class='glyphicon glyphicon-cloud-download'></i></a>";
		if ($registrationAllowed === 'all') {
			echo "\n<a class='btn btn-danger btn-xs btn-delete'
				data-toggle='confirmation' data-title='Delete Registration' 
				data-content='Are you sure?'>Delete <i class='glyphicon glyphicon-trash'></i></a>\n";
		}
		echo "</td>
		</tr>";
}
?>
	</tbody>
</table>

<?php echo "<!-- ".Config::get("config.allowassignment")." -->";
if (!Config::get("config.allowassignment")) { ?>
<p>Explicit team assignment is disabled.</p>
<?php } ?>

<div class="modal" id='upload-registration' tabindex="-1" role="dialog">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
        <h4 class="modal-title">Upload Registration</h4>
      </div>
      <div class="modal-body">
        <form action='<?= Uri::create("/Registration") ?>' method='POST' enctype='multipart/form-data'>
					<div class='form-group'>
						<label>Club</label>
						<input class='form-control' type='text' name='club' readonly value='<?= $club ?>'/>
					</div>
					<div class='form-group'>
						<label>File</label>
						<input class='form-control' type='file' name='file'/>
					</div>
					<div class='form-group form-confirmation'>
						<input type='checkbox' unchecked id='upload-permission-checkbox'/>
						<p>By clicking this checkbox, you are confirming that every person listed in this registration
						file has given express permission for their name to be uploaded, and that they give permission
						for their data to be used and retained as set forth in the Leinster Hockey Association GDPR data
						privacy guideines. In the case of minors, you are confirming that you have the permission of 
						their parent/guardian.</p>
					</div>
				</form>
      </div>
      <div class="modal-footer">
        <button type="submit" class="btn btn-primary" disabled>Save changes</button>
        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
      </div>
    </div>
  </div>

</div>
