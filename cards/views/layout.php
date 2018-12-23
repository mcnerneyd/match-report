<!DOCTYPE html>
<?php class Uri { static function create($str) { return "fuel/public/$str"; } } ?>
<html>
  <head>
		<title>Matchcards</title>

		<meta charset="UTF-8">
		<meta name="viewport" content="width=device-width, initial-scale=1"/>

		<link rel="shortcut icon" href="img/favicon.png">
		<link rel="stylesheet" href="http://cards.leinsterhockey.ie/cards/fuel/public/assets/css/bootstrap.min.css"/>
		<link rel="stylesheet" href="//code.jquery.com/ui/1.11.4/themes/smoothness/jquery-ui.css">
		<link rel="stylesheet" href="http://cards.leinsterhockey.ie/cards/img/selectize.bootstrap3.css">
		<link rel="stylesheet" href="style.css"/>

		<script src="http://cards.leinsterhockey.ie/cards/fuel/public/assets/js/jquery-3.2.1.min.js"></script>
		<script src="http://cards.leinsterhockey.ie/cards/fuel/public/assets/js/jquery-ui.js"></script>
		<script src="http://cards.leinsterhockey.ie/cards/fuel/public/assets/js/bootstrap.min.js"></script>
		<script src="http://cards.leinsterhockey.ie/cards/fuel/public/assets/js/notify.min.js"></script>
		<script src="http://cards.leinsterhockey.ie/cards/fuel/public/assets/js/raven.min.js"></script>
		<script src="http://cards.leinsterhockey.ie/cards/fuel/public/assets/js/moment.min.js"></script>
		<script src="http://cards.leinsterhockey.ie/cards/img/selectize.min.js"></script>

		<script src="http://ajax.googleapis.com/ajax/libs/jqueryui/1.8/jquery-ui.min.js" type="text/javascript"></script>
		<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.12.3/umd/popper.min.js" integrity="sha384-vFJXuSJphROIrBnz7yo7oB41mKfc8JzQZiCq4NCceLEaO4IHwicKwpJf9c9IpFgh" crossorigin="anonymous"></script>


		<script src="img/bootstrap-confirmation.min.js"></script>
		<script src="img/validator.min.js"></script>
	<link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.3.1/css/all.css" integrity="sha384-mzrmE5qonljUremFsqc01SB46JvROS7bZs3IO2EmfFsd15uHvIt+Y8vEf7N7fWAU" crossorigin="anonymous">

		<style>
		<?php if (user('admin')) { ?>
		#user { font-style: italic; }
		<?php } ?>
		.help { padding: 2px 5px; color: #9999dd; }
		#alert-modal .modal-content {
				border: 3px solid #d43f3a;
				margin-top: 200px;
			}
		</style>
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

					$('[data-toggle=confirmation]').confirmation({
							rootSelector: '[data-toggle=confirmation]',
								// other options
					});

					$('#search button').click(function(e) {
						e.preventDefault();
						window.location.href = '<?= Uri::create('Card') ?>?q='+$('#search input').val();
					});

				});
			</script>

			<!--
			<?php
			if (isset($_SESSION['url-stack'])) {
				$stack = $_SESSION['url-stack'];
				foreach ($stack as $url) echo "$url\n";
			}
			?>
			-->

			<header class='navbar navbar-inverse navbar-fixed-top' role='navigation'>
				<div class='container-fluid'>
					<div class='navbar-header'>
						<button type='button' class='navbar-toggle collapsed' 
								data-toggle='collapse' data-target='.navbar-collapse'
								aria-expanded='false'>
							 <span class="sr-only">Toggle navigation</span>
						   <span class="icon-bar"></span>
							 <span class="icon-bar"></span>
							 <span class="icon-bar"></span>
						</button>
						<div class='navbar-brand'><?= TITLE ?></div>
					</div>

					<div class='collapse navbar-collapse' id='navbar-collapse-menu'>
						<ul class='nav navbar-nav'>
							<?php if (user()) { ?>
							<li><a href='<?= url(null,'index', 'card') ?>'>Matches</a></li>
							<?php } ?>

							<?php if (user('secretary') or user('admin')) { ?>
							<li><a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">Registration <span class="caret"></span></a>
								<ul class='dropdown-menu'>
									<li><a href='<?= Uri::create('Registration') ?>'>Registrations</a></li>
									<li><a href='<?= Uri::create('Registration/Info') ?>'>Club Info</a></li>
								</ul>
							</li>
							<?php } ?>

							<li><a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">Reports <span class="caret"></span></a>
							<ul class='dropdown-menu'>
								<li><a href='<?= Uri::create('Report/Scorers') ?>'>Top Scorers</a></li>
								<li><a href='<?= Uri::create('Report/Mismatch') ?>'>Mismatch Results</a></li>
								<?php if (user('admin') || user('umpire')) { ?>
								<li role="separator" class="divider"></li>
								<li><a href='<?= Uri::create('Report/Cards') ?>'>Red/Yellow Cards</a></li>
								<?php } ?>
								<?php if (user('admin')) { ?>
								<li role="separator" class="divider"></li>
								<li><a href='<?= Uri::create('Report/RegSec') ?>'>Anomalies</a></li>
								<li><a href='<?= Uri::create('Report/Parsing') ?>'>Parsing</a></li>
								<?php } ?>
							</ul>
						</ul>

						<!-- Search box -->
						<form class="navbar-form navbar-left hidden-sm">
							<div id='search' class="input-group">
								<input type="text" class="form-control" placeholder="Search">
								<div class='input-group-btn'>
									<button class='btn btn-default' type='submit'><i class="fas fa-search"></i></button>
								</div>
							</div>
						</form>

						<!-- Admin Menu -->
						<ul class='nav navbar-nav navbar-right'>
							<!-- Fuel -->
							<?php if (user('admin')) { ?>
							<li><a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">Admin <span class="caret"></span></a>
							<ul class="dropdown-menu">
								<li><a href="<?= Uri::create('competitions') ?>">Competitions</a></li>
								<li><a href="<?= Uri::create('clubs') ?>">Clubs</a></li>
								<li role="separator" class="divider"></li>
								<li><a href="<?= Uri::create('fixtures') ?>">Fixtures</a></li>
								<li><a href="<?= Uri::create('fines') ?>">Fines</a></li>
								<li role="separator" class="divider"></li>
								<li><a href="<?= Uri::create('users') ?>">Users</a></li>
								<li><a href="<?= Uri::create('Admin/Config') ?>">Configuration</a></li>
								<li><a href="<?= Uri::create('Admin/Log') ?>">System Log</a></li>
							</ul>
							</li>
							<?php } ?>
							<!-- /Fuel -->
							<li>
								<?php if (user()) { ?>
								<a style='display:inline-block' href='<?= Uri::create('/Login') ?>'><i class="fas fa-sign-out-alt" aria-hidden="true"></i> Logout</a>
						<?php } else { ?>
							<a class='navbar-brand' href='<?= Uri::create('/Login') ?>'><i class="fas fa-sign-in-alt" aria-hidden="true"></i> Login</a>
						<?php } ?>
							</li>
						</ul>
					</div>
				</div>
			</header>

			<?php if (user()) { ?>
			<div id='user'><?php 
				echo user();
				if ($_SESSION['club'] != user()) echo "(".$_SESSION['club'].")"; ?></div>
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
