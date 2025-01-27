<!DOCTYPE html>
<html>

<head>
	<?php require_once(APPPATH . '/views/head.php'); ?>
</head>

<body class='cards'>
	<script>
		Raven.config('https://773a5c2c3fc64be3961a669bf217015c@sentry.io/103038').install();
		Raven.setUserContext(<?php
		$username = \Session::get('user-title');
		if ($username)
			echo "{ username: '$username'}";
		else
			echo "null";
		?>);

		$(document).ready(function () {
			$.notify.defaults({
				arrowSize: 10,
				className: 'info',
				globalPosition: 'bottom right',
				showAnimation: 'fadeIn', hideAnimation: 'fadeOut'
			});

			$('#search button').click(function (e) {
				e.preventDefault();
				window.location.href = '<?= Uri::create('Card') ?>?q=' + $('#search input').val();
			});

		});

	</script>

	<?php require_once(APPPATH . '/views/nav.php'); ?>

	<div class='xcontainer container-cards' data-controller='<?= $controller ?>' data-action='<?= $action ?>'>
		<?php if (\Session::get('user-title')) { ?>
			<div id='user'><?= \Session::get('user-title') ?></div>
		<?php }

		if (isset($_SESSION['lastpage'])) {
			echo "<script>window.location='" . $_SESSION['lastpage'] . "'</script>";
		} else {
			require_once('routes.php');
		}

		?>
	</div>

	<footer class='center-block'>
		<script type="text/javascript" src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
	</footer>

	<script>
		function doAlert(msg) {
			$('#alert-modal p').text(msg);
			$('#alert-modal').modal('show');
		}

		$(document).ready(function () {

			$('[data-help]').each(function () {
				$(this).append("<span class='help glyphicon glyphicon-question-sign' data-helpid='" + $(this).data("help") + "'></span>");
			});

			$('[data-helpid]').click(function () {
				$.get('/cards/fuel/public/help?id=' + $(this).data('helpid'), function (data) {
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