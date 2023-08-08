<style>
	.table>tbody>tr>th 	{ border-top: none; }
	.btn .glyphicon 		{ vertical-align: -1px; }
	th, td 							{ text-align: right; }
	th:first-child, td:first-child 			
											{ text-align: left; }	
	#command-functions>.row, #matchcard-functions .row
											{ padding-top: 5px; }
	#player-functions pre, .panel 							
											{ margin-top: 30px; }
	#matchcard-detail 	{ margin: 10px 0; }
</style>

<script>
$(document).ready(function() {

	$('#find-player').on('click', function() {
			var fidField = $(this).parents('div').find("input[name='find-player']");

			window.location.assign('<?= url(null, 'index', 'admin') ?>&player='+fidField.val());
		});

	$('#find-card').on('click', function() {
			var fidField = $(this).parents('div').find("input[name='find-fid']");

			window.location.assign('<?= url(null, 'index', 'admin') ?>&fid='+fidField.val());
		});

	$('#close-card').on('click', function() {
			<?php if (isset($_REQUEST['fid'])) { ?>
			$.get('<?= url("fid=".$_REQUEST['fid'], 'close', 'card') ?>');
			<?php } ?>
		});

	$('#reset-card').on('click', function() {
			<?php if (isset($_REQUEST['fid'])) { ?>
			$.get('<?= url("fid=".$_REQUEST['fid'], 'reset', 'card') ?>');
			<?php } ?>
		});
});
</script>

<h1>Adminstration</h1>
<div class='row'>
	<div class='col-md-4 col-sm-12'>
		<h2>Reference</h2>
		<ul>
			<li><a href='<?= url(null, 'configuration', 'admin') ?>'>Competitions</a></li>
			<li><a href='<?= url(null, 'club', 'admin') ?>'>Clubs</a></li>
			<li><a href='<?= url(null, 'user', 'admin') ?>'>Users</a></li>
			<li><a href='<?= url(null, 'registration', 'admin') ?>'>Registrations</a></li>
			<li><a href='<?= url(null, 'log', 'admin') ?>'>Events</a></li>
		</ul>
	</div>

	<div class='col-md-8 col-sm-12' id='command-functions'>
		<h2>Command Functions</h2>
		<div class='row'>
			<div class='col-md-6'>
			<a class='btn btn-primary col-md-12' data-toggle='modal' href='#upload-config-modal'><span class='glyphicon glyphicon-upload'></span> Upload Configuration</a>
			</div>
			<div class='col-md-6'>
				<small>Upload a new configuration, i.e. competitions and clubs.</small>
			</div>
		</div>

		<div class='row'>
			<div class='col-md-6'>
			<a class='btn btn-primary col-md-12' href='<?= url(null, 'autosubmit', 'admin') ?>'><span class='glyphicon glyphicon-random'></span> Auto-Submit Cards</a>
			</div>
			<div class='col-md-6'>
				<small>Submit outstanding result to LHA website.</small>
			</div>
		</div>

		<div class='row'>
			<div class='col-md-6'>
				<a class='btn btn-warning col-md-12' href='<?= url(null, 'warn', 'admin') ?>'><span class='glyphicon glyphicon-alert'></span> Issue Warnings</a>
			</div>
			<div class='col-md-6'>
				<small>Issue a warning to all clubs which have outstanding matchcards.</small>
			</div>
		</div>


		<div class='row'>
			<div class='col-md-6'>
				<a class='btn btn-warning col-md-12' href='<?= url(null, 'archive', 'admin') ?>' data-toggle='confirmation' data-title='Archive all data before Aug-2016?' href='#'><span class='glyphicon glyphicon-alert'></span> Archive Data</a>
			</div>
			<div class='col-md-6'>
				<small>Removes previous years data and stores them in a file.</small>
			</div>
		</div>

	</div>	<!-- .col-md-6 -->
</div>	<!-- .row -->

	<div class='panel panel-default' id='matchcard-functions'>
		<div class='panel-heading'>Matchcard Functions</div>
		<div class='panel-body'>
			<div class='input-group col-md-12'>
				<input class='form-control' name='find-fid' placeholder='Fixture ID/Team/Competition/Date'/>
				<span class='input-group-btn'>
					<a id='find-card' class='btn btn-default' href='#'><span class='glyphicon glyphicon-search'></span></a>
				</span>
			</div>

			<?php if (isset($card)) { ?>
			<div class='col-md-12' id='matchcard-detail'>
				<samp>
				<?php 
					echo "#".$card['id'].": ".date('Y-m-d', $card['date'])." ".$card['competition']." - ".$card['home']['team']." -v- ".$card['away']['team']
				?>
				</samp>
			</div>

			<div class='col-md-12'>
				<div class='row'>
					<div class='col-md-4'>
						<a id='reset-card' class='btn btn-danger col-md-12' href='#'><span class='glyphicon glyphicon-alert'></span> Reset Card</a>
					</div>
					<div class='col-md-8'>
						<small>Reset the matchcard. Get the fixture ID by hovering over the fixtures on the Matches page.</small>
					</div>
				</div>

				<div class='row'>
					<div class='col-md-4'>
						<a id='close-card' class='btn btn-warning col-md-12' href='#'><span class='glyphicon glyphicon-alert'></span> Close Card</a>
					</div>
					<div class='col-md-8'>
						<small>Closes the card - this allows a card to be removed from the system.  Generally, this applies to invalid fixtures.</small>
					</div>
				</div>

			</div>

			<!--pre>
			<?php print_r($card); ?>
			</pre-->
			<?php } ?>

			</div>
			
		</div>  <!-- #matchcard-functions -->

	<div class='panel panel-default' id='player-functions'>
		<div class='panel-heading'>Player Search</div>
		<div class='panel-body'>
			<div class='input-group col-md-12'>
				<input class='form-control' name='find-player' placeholder='Player Name'/>
				<span class='input-group-btn'>
					<a id='find-player' class='btn btn-default' href='#'><span class='glyphicon glyphicon-search'></span></a>
				</span>
			</div>

			<div class='col-md-12'>
				<?php if (count($players) > 0) { ?>
				<pre><?php foreach ($players as $detail) { 
					$fullname = trim($detail['lastname'].", ".$detail['firstname']);
					echo "<a href='".url("name=$fullname&club=${detail['club']}","profile","player")."'>".$fullname."</a>".str_repeat(" ", 50-strlen($fullname))." ${detail['club']}</a>\n";
				} ?>
				</pre>
				<?php } ?>
			</div>
		</div>  <!-- #user-functions -->


<div id='upload-config-modal' class='modal' role='dialog'>
  <div class='modal-dialog'>
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal">&times;</button>
        <h4 class="modal-title">Upload League Configuration</h4>
      </div>
      <div class="modal-body">
        <form action='<?= url(null, 'uploadconfig') ?>' method='POST' enctype="multipart/form-data">
					<div class='form-group'>
						<label for='configfile'>File:</label>
						<input type='file' class='form-control' id='configfile' name='configfile'/>
					</div>

					<div class='checkbox'>
						<label><input type='checkbox' name='clearconfig' checked/>Clear existing configuration</label>
					</div>
				</form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal" onclick="$('form').submit()">Upload</button>
        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div> <!-- #upload-config-modal -->

