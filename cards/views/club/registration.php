<?php checkuser(); ?>
<?php define("SEASON_YEAR", 2015); ?>
<script src="//code.jquery.com/ui/1.11.4/jquery-ui.js"></script>
<script>
$(document).ready(function() {
	$('#datepicker').datepicker( {
			onSelect: function(dateText, inst) {
				window.location='<?= url() ?>&date=' + dateText;
			}
		}
	);
	$("#datepickerbtn").click(function () { $('#datepicker').datepicker("show"); });

	$('#dateselect').datepicker();

	$("#registrationformat").val('<?= UPLOAD_FORMAT ?>');

	$('tr[data-profile]').click(function() {
		window.location.href = $(this).data('profile');
	});
});
</script>
<style>
#registration { margin-top: 20px; }
#registration th:first-child, #registration td:first-child { width: 4em; }
#registration th:nth-child(3), #registration td:nth-child(3) { text-align:center; }
#registration th:nth-child(3), #registration td:nth-child(4) { text-align:left; padding-left: 10px; width: 1em; }
.fa-star { color: #eebc1d; padding: 2px 0 0 2px; }
.glyphicon-alert { color: #8a0707; padding: 2px 0 0 0; float:right; }
#date { font-size: 8pt; text-align:center; }
.error { background: #ffff00; color: #8a0707; padding-bottom: 0px; }
tr.error td { padding-bottom: 0 !important; }
.secondline { background: #ffff00; color: #8a0707; border-top: 0; font-size: 85%; font-style: italic; }
tr.secondline td { border-top:0 !important; padding: 2px 35px !important; }
.team-boundary { background: #669966; border-top: 3px solid #006600; }
.team-boundary td { padding: 1px 10px 2px 10px !important; font-weight: bold; color: white; }
.team-boundary td:nth-child(2) { text-align: right; }
#date-range { text-align: right; }
</style>

<div class='row'>

	<div class='col-md-6 col-xs-6'>
		<input type='hidden' id='datepicker'/>
		<a id='datepickerbtn' class='btn btn-default'>
			<i class="fa fa-calendar" aria-hidden="true"></i>
			<?= date('l, j F Y', strtotime($date)) ?>
		</a>
	</div>

	<div class='col-md-6 col-xs-6'>
		<?php if (user('admin')) { ?>
		<a class='pull-right btn btn-primary' data-toggle='modal' href='#upload-registration'><span class='glyphicon glyphicon-upload'></span> Upload Registration</a>
		<?php } ?>
	</div>

</div>

<div class='row'>
	<div class='col-md-12'>
		<div class='btn-group pull-right'>
		<a class='btn btn-xs btn-default' href='<?= url(null, 'register', 'club') ?>'>Current</a>
		<a class='btn btn-xs btn-warning' href='<?= url('validate=1', 'register', 'club') ?>'>Warnings</a>
		<a class='btn btn-xs btn-success' href='<?= url('fix=1', 'register', 'club') ?>'>Fix</a>
		</div>
	</div>
</div>

<table class='table' id='registration'>
	<tr>
		<th></th><th>Player</th><th>Rating</th><th>Team</th>
	</th>
	<?php 
		$lastTeam = null;
		foreach ($players as $player) { 
			if ($lastTeam == null or $lastTeam != $player['team']) {
				$lastTeam = $player['team']; 
				?>
				<tr class='team-boundary'>
					<td colspan='2'>Team <?= $lastTeam ?></td>
					<td colspan='2'><?= $teams[$lastTeam-1]['name'] ?></td>
				</tr>
			<?php }
				$club = user();
				$key = createsecurekey("profile".$player['name']."$club");
			 ?>

		<tr<?= isset($player['error']) ? " class='error'":"" ?>  
			data-profile='<?= url("name=".$player['name']."&club=$club&x=$key","profile","player") ?>'>
			<td><?= $player['sequence'] ?><?= isset($player['error']) ? "<span class='glyphicon glyphicon-alert'>":"" ?></span></td>
			<td><?= $player['name'] ?></td>
			<td><?= $player['score'] != null ? sprintf("%5.2f", $player['score']) : '' ?></td>
			<td><?= $player['team'] ?><?= $player['starred']?"<i class='fa fa-star' aria-hidden='true'></i>":"" ?></td>
		</tr>
		<?php if (isset($player['error'])) { ?>
		<tr class='error secondline'>
			<td colspan='4'><?= $player['error'] ?></td>
		</tr>
		<?php } ?>

	<?php } ?>
</table>

<div id='upload-registration' class='modal' role='dialog'>
  <div class='modal-dialog'>
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal">&times;</button>
        <h4 class="modal-title">Upload Registration</h4>
      </div>
      <div class="modal-body">
        <form action='<?= url(null, 'uploadregistration','club') ?>' method='POST' enctype="multipart/form-data">
					<div class='form-group'>
						<label for='registrationfile'>File:</label>
						<input type='file' class='form-control' id='registrationfile' name='registrationfile'/>
					</div>
					<div class='form-group'>
						<label for='registrationformat'>Format:</label>
						<select class='form-control' id='registrationformat' name='registrationformat'>
							<option value='standardlist'>Ordered List (LHA Men)</option>
							<option value='numberedlist'>Numbered List (LHA Ladies)</option>
						</select>
					</div>
					<div class='form-group'>
						<label for='dateselect'>Date:</label>
						<input type='text' class='form-control col-md-3' id='dateselect' name='date'/>
					</div>
					<div class='checkbox'>
						<label>
							<input type='checkbox' name='ignorewarnings'/> Ignore Warnings
						</label>
					</div>
				</form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal" onclick="$('form').submit()">Upload</button>
        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>
