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
					{ "className": 'dt-right' },
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

		$("#rename-player").submit(function(e) {
			e.preventDefault();
			var oldName = $(this).find('[name=oldname]').val();
			var newName = $(this).find('[name=newname]').val();
			debugger;
			$.post('<?= Uri::create('registrationapi/rename') ?>',
				{'c':'<?= $club ?>', 'o':oldName, 'n':newName }
				).done(function(data) { window.location.reload(); });
		});

		$('#registration-club select').change(function() {
			window.location.href='<?= Uri::create('Registration') ?>?c=' + $(this).val();
		});

		$('#registration-club select').val('<?= $club ?>');
		$.get('<?= Uri::create('registrationapi/errors.json') ?>?c=<?= $club ?>')
			.done(function(data) {
				for (var i=0;i<data.length;i++) {
					var error = data[i];
					$('#errors ul').append("<li class='"+error['class']+"'>"+error['msg']+"</li>");
				}
				if (data.length>0) $('#errors').show();
			});

		$('.form-confirmation input[type=checkbox]').on('change', function() {
				$('#upload-registration button[type=submit]').prop('disabled', !this.checked);
		});

		$('#errors button').click(function() {
			$.ajax('<?= Uri::create('registrationapi/errors') ?>',
				{
					method:'DELETE',
					data:{'club':'<?= $club ?>'},
				}).done(function(data) { window.location.reload(); });
		});
	});

	var tutorial = [
		{ target: "#registration-table", message: "This table shows all the registration files you have uploaded. The latest file is shown first.",dir:"top" },
		{ target: "#registration-table .btn-download:first", message: "Click Download button to download the actual registration file", dir:"top" },
		{ target: "#view-registration .btn", message: "Click the View button to see your registration", dir:"bottom" },
		{ target: "#view-registration input", message: "Select a date to view the registration for - the default is tomorrow", dir:"bottom" },
		{ target: "#upload-button", message: "Click Upload to upload a new registration file", dir:"left" },
		];
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
tr.error td {
	color: red;
}
.dt-right {
	text-align: right;
}
#errors {
	display: none;
}
#errors .error {
	color: red;
}
#errors .warn {
	color: orange;
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
	$class = "";
	if (Config::get("hockey.block_errors", false) && isset($registration['errors'])) {
		$class = "title='This registration has errors' class='error'";
	}
	echo "<tr $class data-filename='${registration['name']}' data-club='${registration['club']}' data-type='${registration['type']}'>
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

<div id='errors'>
<hr>
<?php if ($registrationAllowed === 'all') { ?>
<button class='btn btn-danger btn-sm pull-right'>Clear Errors</button>
<?php } ?>
<h3>Errors/Warnings</h3>
<p>Registration will not be valid if it has <span class='error'>errors</span>. <span class='warn'>Warnings</span> should be resolved but do not
block registration.<p>
<p>To remove errors, upload a new valid registration or get the Section registration secretary to clear the errors.</p>
<ul></ul>
</div>

<?php if ($registrationAllowed === 'all') { ?>
<h3>Rename Player</h3>
<form id='rename-player' class='form-inline'>
	<div class='form-group'>
		<label>Original Name</label>
		<input class='form-control' type='text' name='oldname'/>
	</div>
	<div class='form-group'>
		<label>New Name</label>
		<input class='form-control' type='text' name='newname'/>
	</div>
	<button type="submit" class="btn btn-danger">Rename</button>
</form>
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
