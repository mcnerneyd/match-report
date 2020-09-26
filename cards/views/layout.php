<!DOCTYPE html>
<html>
  <head>
			<?php require_once('../admin/views/head.php'); ?>
  </head>

  <body>
			<script>
				Raven.config('https://773a5c2c3fc64be3961a669bf217015c@sentry.io/103038').install();

				$(document).ready(function () {
					$.notify.defaults({
						arrowSize: 10,
						className: 'info',
						globalPosition: 'bottom right',
						showAnimation: 'fadeIn', hideAnimation: 'fadeOut'
					});

					$('#search button').click(function(e) {
						e.preventDefault();
						window.location.href = '<?= Uri::create('Card') ?>?q='+$('#search input').val();
					});

				});
			</script>

			<?php require_once('../admin/views/nav.php'); ?>

			<?php if (user()) { ?>
			<div id='user'><?php 
				echo user();
				if ($_SESSION['club'] and $_SESSION['club'] != user()) echo "(".$_SESSION['club'].")"; ?></div>
			<?php } ?>

		<div class='container' data-controller='<?= $controller ?>' data-action='<?= $action ?>'>
			<?php 

			if (isset($_SESSION['lastpage'])) {
				echo "<script>window.location='".$_SESSION['lastpage']."'</script>";
			} else {
				require_once('routes.php'); 
			}

			?>
		</div>

    <footer class='center-block'>
			<!--span class='hidden-xs'>A</span>
			<span class='hidden-sm'>B</span>
			<span class='hidden-md'>C</span>
			<span class='hidden-lg'>D</span-->
		</footer>

		<div class="modal" id='help-modal' tabindex="-1" role="dialog">
			<div class="modal-dialog" role="document">
				<div class="modal-content">
					<div class="modal-header">
						<button type="button" class="close" data-dismiss="modal" aria-label="Close">&times;</button>
						<h4 class="modal-title">Help</h4>
					</div>
					<div class="modal-body">
						<p>Help Detail</p>
					</div>
				</div>
			</div>
		</div>
		<div class="modal" id='alert-modal' tabindex="-1" role="dialog">
			<div class="modal-dialog" role="document">
				<div class="modal-content">
					<div class="modal-body row">
						<p class='text-center col-xs-12'>Help Detail</p>
						<button data-dismiss='modal' class='btn btn-danger col-xs-4 col-xs-offset-4'>OK</button>
					</div>
				</div>
			</div>
		</div>

		<script>
		function doAlert(msg) {
			$('#alert-modal p').text(msg);
			$('#alert-modal').modal('show');
		}

		$(document).ready(function() {
			setInterval(function() {
				$.get('<?= Uri::create('UserAPI') ?>').fail(function() {
					window.location = '<?= Uri::create('User/Login') ?>';
				});
			}, 30000);

			$('[data-help]').each(function () {
				$(this).append("<span class='help glyphicon glyphicon-question-sign' data-helpid='"+$(this).data("help")+"'></span>");
			});

			$('[data-helpid]').click(function() {
				$.get('/cards/fuel/public/help?id='+$(this).data('helpid'), function(data) {
					$('#help-modal .modal-body').html(data);
					var title = $('#help-modal .modal-body :first').detach();
					$('#help-modal .modal-title').text(title.text());
					$('#help-modal').modal('show');
				});
			});
		});
		</script>
  </body>
</html>
