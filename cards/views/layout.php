<!DOCTYPE html>
<?php class Uri { static function create($str) { return "fuel/public/$str"; } } ?>
<html>
  <head>
		<title>Matchcards</title>

		<meta charset="UTF-8">
		<meta name="viewport" content="width=device-width, initial-scale=1"/>

		<link rel="shortcut icon" href="img/favicon.png">
		<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap.min.css" integrity="sha384-1q8mTJOASx8j1Au+a5WDVnPi2lkFfwwEAa8hDDdjZlpLegxhjVME1fgjWPGmkzs7" crossorigin="anonymous">
		<link rel="stylesheet" href="//code.jquery.com/ui/1.11.4/themes/smoothness/jquery-ui.css">
		<link rel="stylesheet" href="style.css"/>

		<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.0/jquery.min.js"></script>
		<script src="http://ajax.googleapis.com/ajax/libs/jqueryui/1.8/jquery-ui.min.js" type="text/javascript"></script>
		<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.12.3/umd/popper.min.js" integrity="sha384-vFJXuSJphROIrBnz7yo7oB41mKfc8JzQZiCq4NCceLEaO4IHwicKwpJf9c9IpFgh" crossorigin="anonymous"></script>


		<script src="img/bootstrap.min.js"></script>
		<script src="img/bootstrap-confirmation.min.js"></script>
		<script src="img/validator.min.js"></script>
		<script type="text/javascript" src="http://cards.leinsterhockey.ie/cards/fuel/public/assets/js/notify.min.js?1508015068"></script>
		<script src="https://cdn.ravenjs.com/3.7.0/raven.min.js"></script>
		<script src="https://use.fontawesome.com/73c8798688.js"></script>

		<style>
		<?php if (user('admin')) { ?>
		#user { font-style: italic; }
		<?php } ?>
		.help { padding: 2px 5px; color: #9999dd; }
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

			function incident(type,club,player,detail,cardid) {
				debugger;

				var command = 'i=' + type;

				if (club) command += '&c=' + club;
				if (player) command += '&p=' + player;
				if (detail) command += '&d=' + detail;

				var baseUrl = window.location.href;
				baseUrl = baseUrl.substr(0, baseUrl.lastIndexOf('/'));
				baseUrl = baseUrl.substr(0, baseUrl.lastIndexOf('/'));
				var url = baseUrl + '/services.php?' + command;

				var req = new XMLHttpRequest();
				req.open('PUT', url, true);
				req.send('');
			}
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
						<div class='navbar-brand' data-help='general'><?= TITLE ?></div>
					</div>

					<div class='collapse navbar-collapse' id='navbar-collapse-menu'>
						<ul class='nav navbar-nav'>
							<?php if (user()) { ?>
							<li><a href='<?= url(null,'index', 'card') ?>'>Matches</a></li>
							<?php } ?>

							<?php if (user('secretary') or user('admin')) { ?>
							<li><a href='<?= url(null,'register', 'club') ?>'>Registration</a></li>
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
								<li><a href='<?= Uri::create('Report/Parsing') ?>'>Parsing</a></li>
								<?php } ?>
							</ul>
						</ul>

						<!-- Search box -->
						<form class="navbar-form navbar-left hidden-sm">
							<div id='search' class="input-group">
								<input type="text" class="form-control" placeholder="Search">
								<div class='input-group-btn'>
									<button class='btn btn-default' type='submit'><i class='glyphicon glyphicon-search'></i></button>
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
								<li><a href="<?= Uri::create('Admin/Registration') ?>">Registrations</a></li>
								<li role="separator" class="divider"></li>
								<li><a href="<?= Uri::create('fixtures') ?>">Fixtures</a></li>
								<li><a href="<?= Uri::create('fines') ?>">Fines</a></li>
								<li role="separator" class="divider"></li>
								<li><a href="<?= Uri::create('users') ?>">Users</a></li>
								<li><a href="<?= Uri::create('Admin/Config') ?>">Configuration</a></li>
							</ul>
							</li>
							<?php } ?>
							<!-- /Fuel -->
							<li>
								<?php if (user()) { ?>
								<a style='display:inline-block' href='<?= Uri::create('/Login') ?>'><i class="fa fa-sign-out" aria-hidden="true"></i> Logout</a>
						<?php } else { ?>
							<a class='navbar-brand' href='<?= Uri::create('/Login') ?>'><i class="fa fa-sign-in" aria-hidden="true"></i> Login</a>
						<?php } ?>
							</li>
						</ul>
					</div>
				</div>
			</header>

			<?php if (user()) { ?>
			<div id='user'><?= user() ?></div>
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
		<script>
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
