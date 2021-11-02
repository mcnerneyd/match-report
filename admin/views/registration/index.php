<?php
$registrationAllowed = FALSE;

if (Config::get("section.automation.allowrequest")) {
	$registrationAllowed = TRUE;
}

if (Auth::has_access('registration.impersonate')) {
	$registrationAllowed = 'all';
}

echo "<!-- Registration Allowed: $registrationAllowed on $section -->";
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
			window.location = "./Registration/Registration?c=<?= $club ?>&s=<?= $section ?>";
		});
		$("#upload-registration button[type=submit]").click(function(e) {
			$('#upload-registration form').submit();
		});

		$(".btn-download").click(function(e) {
			e.preventDefault();
			window.location.href = "./Registration/Registration?f=" + $(this).closest("tr").data("filename")
				+ "&c=" + $("#registration-club select").val()
        + "&s=<?= $section ?>";
		});

		$(".btn-delete").click(function(e) {
			e.preventDefault();
			var row = $(this).closest("tr");
			$.ajax('<?= Uri::create("RegistrationApi") ?>', {
					method:"DELETE",
					data:{"f":row.data("filename"), "c":row.data("club"), "s":"<?= $section ?>"},
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

    const reload = () => {
      console.log("Reg");
      const club = $('#registration-club select').val();
      const section = $('#registration-section select').val();
      console.log('Registration params:', club, section);
      if (club !== undefined && (!club || club == '<?= $club ?>')) return;
      if (!section || section == '<?= $section ?>') return;
			window.location.href=`<?= Uri::create('Registration') ?>?c=${club}&s=${section}`;
    }

		$('#registration-club select').change(reload);
		$('#registration-section select').change(reload);

		<?php if ($section) { ?>
    console.log("Selecting <?= $section ?>");
		$('#registration-section select').val('<?= $section ?>');
		<?php } ?>
		<?php if ($club) { ?>
		$('#registration-club select').val('<?= $club ?>');
		<?php } ?>

		$.get('<?= Uri::create('registrationapi/errors.json') ?>?c=<?= $club ?>')
			.done(function(data) {
				if (typeof data !== 'undefined') {
					for (var i=0;i<data.length;i++) {
						var error = data[i];
						$('#errors ul').append("<li class='"+error['class']+"'>"+error['msg']+"</li>");
					}
					if (data.length>0) $('#errors').show();
				}
			});

		$('.form-confirmation input[type=checkbox]').on('change', function() {
				$('#upload-registration button[type=submit]').prop('disabled', !this.checked);
		});

		$('#validate').click(function() {
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

<div id='registration'>
  <div class='command-group form'>
    <div class='form-row'>
      <?php if ($registrationAllowed === 'all') { ?>
      <div id='registration-club' class='col-auto'>
        <select class='form-control' name='club'>
          <option selected value=''>Select Club...</option>
          <?php foreach ($clubs as $c) {
            echo "<option>".$c['name']."</option>";
          }?>
        </select>
      </div>
      <?php } ?>
      <div id='registration-section' class='col-auto'>
        <select class='form-control' name='section'>
          <option selected value=''>Select Section...</option>
          <?php foreach ($sections as $s) {
            echo "<option>".$s['name']."</option>";
          }?>
        </select>
      </div>

      <div class='col'>
      <?php if ($registrationAllowed) { ?>
      <a class='btn btn-primary' id='upload-button' tabindex='1' data-target='#upload-registration' data-toggle='modal'><i class="fas fa-upload"></i><span class='d-none d-sm-inline'> Upload</span></a> 
      <?php } ?>
      </div>

      <div id='view-registration' class='col'>
        <div class='input-group-append'>
          <a class="btn btn-success" tabindex="2"><i class="far fa-eye"></i> View</a>
        </div>
      </div>
    </div>	<!-- .form-row -->
  </div>

  <?php 
      $currentDate = time();
      $restrictionDate = Config::get('section.date.restrict', null);
      if ($restrictionDate) {
        $restrictionDate = strtotime($restrictionDate);

        if ($currentDate > $restrictionDate) {
          echo "<div class='alert alert-danger'>Full Registration Rules Apply (Since ".strftime("%A %e, %B %G", $restrictionDate).")</div>";
        } else {
          echo "<div class='alert alert-success'>Full Registration Rules Suspended (Until ".strftime("%A %e, %B %G", $restrictionDate).")</div>";
        }
      }
  ?>

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
        <a class='btn btn-primary btn-sm btn-download'>Download <i class='fas fa-download'></i></a>";
      if (Auth::has_access('registration.delete')) {
        echo "\n<a class='btn btn-danger btn-sm btn-delete'
          data-toggle='confirmation' data-title='Delete Registration' 
          data-content='Are you sure?'>Delete <i class='fas fa-trash-alt'></i></a>\n";
      }
      echo "</td>
      </tr>";
  }
  ?>
    </tbody>
  </table>

    <?php
      $hints = array();
      if (!Config::get("$section.config.allowassignment")) $hints[] = "Explicit team assignment is disabled";
      if ($registrationAllowed === FALSE) $hints[] = "Uploading of registration is not enabled ";

      echo "<p class='hints'>".implode(" / ", $hints)."</p>";
    ?>


  <?php if ($registrationAllowed === 'all') { ?>
  <button id='validate' class='btn btn-success btn-sm pull-right'>Revalidate</button>
  <?php } ?>
  <div id='errors'>
  <hr>
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
          <h4 class="modal-title">Upload Registration</h4>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <form action='<?= Uri::create("/RegistrationAPI") ?>' method='POST' enctype='multipart/form-data'>
            <input type='hidden' name='section' value='<?= $section ?>'/>
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
          <button type="submit" id='registration-save-changes' class="btn btn-primary" disabled>Save changes</button>
          <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
        </div>
      </div>
    </div>

  </div>
</div>
