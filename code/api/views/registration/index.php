<?php
$registrationAllowed = false;

if (Config::get("section.automation.allowrequest")) {
    $registrationAllowed = true;
}

if (!$clubfixed && Auth::has_access('registration.impersonate')) {
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

		$("#registration-table").on("click", ".btn-download", function(e) {
			e.preventDefault();
      const filename = $(this).closest("tr").data("filename");
      const club = "<?= $club ?>";
      const section = "<?= $section ?>"
      console.log("Download", filename, club, section);
			window.location.href = `./Registration/Registration?f=${filename}&c=${club}&s=${section}`;
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
				{"c":"<?= $club ?>", "o":oldName, "n":newName }
				).done(function(data) { window.location.reload(); });
		});

    const reload = () => {
      const club = $('select[name="club"]').val() || "<?= $club ?>";
      const section = $('select[name="section"]').val() || "<?= $section ?>";
      console.log('Registration params (<?= $section ?>/<?= $clubfixed ?>,<?= $club ?>/<?= $sectionfixed ?>): c=', club, ' s=', section == '<?= $section ?>');
      $('.command-group a').addClass('disabled')
      if (club === undefined || !club || !section) return
      <?php if ($registrationAllowed) { ?>
      $('#upload-button').removeClass('disabled')
      <?php } ?>
      $('#view-button').removeClass('disabled')
      if (section == '<?= $section ?>' && club == "<?= $club ?>") return;
			window.location.href=`<?= Uri::create('Registration') ?>?c=${club}&s=${section}`;
    }

    reload()
		$('.command-group select').change(reload);

		<?php // select the section if it is set as a variable
      if ($section) { ?>
        console.log("Selecting section <?= $section ?>");
        $('select[name="section"]').val('<?= $section ?>');
		<?php } ?>
    
		<?php // select the club if it is set as a variable
      if ($club) { ?>
        console.log("Selecting club <?= $club ?>");
        $('select[name="club"]').val("<?= $club ?>");
		<?php } ?>

		$.get("<?= Uri::create('registrationapi/errors.json') ?>?c=<?= $club ?>")
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
					data:{'club':"<?= $club ?>"},
				}).done(function(data) { window.location.reload(); });
		});
	});
</script>

<div class='command-group form-group'>
    <?php if ($registrationAllowed === 'all') { ?>
      <select class='form-control' name='club'>
        <option selected value=''>Select Club...</option>
        <?php foreach ($clubs as $c) {
            echo "<option>".$c['name']."</option>";
        }?>
      </select>
    <?php } 
    
    if (!$sectionfixed) {?>
      <select class='form-control' name='section'>
        <option selected value=''>Select Section...</option>
        <?php foreach ($sections as $s) {
            echo "<option>".$s['name']."</option>";
        }?>
      </select>
      <?php } ?>

    <a class='btn btn-primary' id='upload-button' tabindex='1' data-bs-target='#upload-registration' data-bs-toggle='modal'>
      <i class="fas fa-upload"></i><span class='d-none d-sm-inline'> Upload</span>
    </a> 

    <a class="btn btn-success" id='view-button' tabindex="2" href="./Registration/Registration?c=<?= $club ?>&s=<?= $section ?>"><i class="far fa-eye"></i> View</a>
</div>

<div id='registration'>
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
    <?php foreach ($registrations as $registration) {
      $date = Date::forge($registration['timestamp']);
      $class = "";
      if (Config::get("hockey.block_errors", false) && isset($registration['errors'])) {
          $class = "title='This registration has errors' class='error'";
      }
      echo "<tr $class data-filename='{$registration['name']}' data-club='{$registration['club']}' data-type='{$registration['type']}'>
        <td>{$registration['club']}</td>
        <td>{$registration['name']}</td>
        <td>".strtoupper($date->format("%Y-%m-%d %H:%M:%S"))."</td>
        <td>{$registration['cksum']}</td>
        <td>
          <a class='btn btn-primary btn-sm btn-download'>Download <i class='fas fa-download'></i></a>";
        if (Auth::has_access('registration.delete')) {
            echo "\n<a class='btn btn-danger btn-sm btn-delete'
              data-bs-toggle='confirmation' data-title='Delete Registration' 
              data-content='Are you sure?'>Delete <i class='fas fa-trash-alt'></i></a>\n";
        }
        echo "</td>
          </tr>";
    } ?>
    </tbody>
  </table>

  <?php
  $hints = array();
  if (!Config::get("$section.config.allowassignment")) {
      $hints[] = "Explicit team assignment is disabled";
  }
  if ($registrationAllowed === false) {
      $hints[] = "Uploading of registration is not enabled ";
  }

  echo "<p class='hints'>".implode(" / ", $hints)."</p>";
  
  if ($registrationAllowed === 'all') { ?>
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

  <div class='smallprint'>IMPORTANT: The matchcard system is only a tool to assist in the completion of matchcards. It is up to clubs to check that their registrations are valid with respect to the LHA Bye-Laws, HI regulations and the Rules of Hockey</div>

  <div class="modal" id='upload-registration' tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h4 class="modal-title">Upload Registration</h4>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <form action='<?= Uri::create("/RegistrationAPI") ?>' method='POST' enctype='multipart/form-data'>
            <input type='hidden' name='section' value='<?= $section ?>'/>
            <div class='form-group'>
              <label>Club</label>
              <input class='form-control' type='text' name='club' readonly value="<?= $club ?>"/>
            </div>
            <div class='form-group'>
              <input type='hidden' name='MAX_FILE_SIZE' value='500000'/>
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
          <button type="button" class="btn btn-default" data-bs-dismiss="modal">Close</button>
        </div>
      </div>
    </div>

  </div>
</div>
