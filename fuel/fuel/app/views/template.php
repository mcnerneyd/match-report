<?php Config::load('custom.db', 'config'); ?>
<!DOCTYPE html>
<html>
<head>
	<title>Matchcards</title>

	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1"/>

	<link rel="shortcut icon" href="<?= Uri::create('favicon.png') ?>">

<?= Asset::js(array(
	'less.min.js',
	'jquery-3.2.1.min.js',
	'bootstrap.min.js',
	'datatables.min.js',
	'moment.min.js',
	'bootstrap-datetimepicker.js',
	'notify.min.js',
	'raven.min.js',
	'code.js')) ?>

<?= Asset::css(array(
	'bootstrap-datetimepicker.less',
	'bootstrap.min.css',
	'datatables.min.css',
	'animate.css',
	'style.css')) ?>

	<script type="text/javascript" src="//cdnjs.cloudflare.com/ajax/libs/typeahead.js/0.10.4/typeahead.bundle.min.js"></script>
	<script  type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-3-typeahead/4.0.2/bootstrap3-typeahead.min.js"></script>
	<link href="https://use.fontawesome.com/73c8798688.css" media="all" rel="stylesheet">

	<script>
		$(document).ready( function() {
			$.notify.defaults({
				className: 'info',
				globalPosition: 'bottom right',
				showAnimation: 'fadeIn', hideAnimation: 'fadeOut'
			});

			<?php $flash = Session::get_flash("notify");
				if ($flash) { echo "var info_msg='${flash['msg']}';" ?>
					$('nav').notify(info_msg,
						{ position:"bottom center", arrowShow:false <?= isset($flash['className']) ? ",className:'${flash['className']}'":"" ?> }
					);
			<?php } ?>

			$('#search button').click(function(e) {
				e.preventDefault();
				window.location.href = '<?= Uri::create('Card') ?>?q='+$('#search input').val();
			});

			$('#user').click(function(e) {
				e.preventDefault();
				$('#user select').toggle();
			});

			$('#user option').click(function (e) {
				e.preventDefault();
				$.post('<?= Uri::create('User/Switch') ?>?u='+e.currentTarget.innerText, function(data) {
					location.reload();
				});
			});
		});
	</script>
</head>

<body>
	<nav class='navbar navbar-inverse navbar-fixed-top'>
		<div class='container-fluid'>
			<div class='navbar-header'>
				<div class='navbar-brand'><?= \Config::get('config.title') ?></div>
				<button type='button' class='navbar-toggle collapsed' data-toggle='collapse' data-target='.navbar-collapse' aria-expanded='false'>
					 <span class="sr-only">Toggle navigation</span>
					 <span class="icon-bar"></span>
					 <span class="icon-bar"></span>
					 <span class="icon-bar"></span>
				</button>
			</div>

			<div class='collapse navbar-collapse' id='navbar-collapse-menu'>
				<ul class='nav navbar-nav'>
					<?php if (\Auth::check()) { ?>
					<li><a href='http://cards.leinsterhockey.ie/cards/index.php?site=<?= Session::get('site') ?>&controller=card&action=index'>Matches</a></li>
					<li><a href='http://cards.leinsterhockey.ie/cards/index.php?site=<?= Session::get('site') ?>&controller=club&action=register'>Registration</a></li>
					<?php } ?>
					<li><a href='<?= Uri::create('Report') ?>'>Reports</a></li>
				</ul>

				<!-- Search box -->
				<form class="navbar-form navbar-left hidden-sm">
					<div id='search' class="input-group">
						<input type="text" class="form-control" placeholder="Search Club, Competition, Date or Card/Fixture ID">
						<div class='input-group-btn'>
							<button class='btn btn-default' type='submit'><i class='glyphicon glyphicon-search'></i></button>
						</div>
					</div>
				</form>

				<!-- Admin Menu -->
				<ul class='nav navbar-nav navbar-right'>
					<?php if (\Auth::has_access('nav.[admin]')) { ?>
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
					<?php if (\Auth::check()) { ?>
					<li><a href='<?= Uri::create('/Login') ?>'><i class='fa fa-sign-out'></i> Logout</a></li>
					<?php } else { ?>
					<li><a href='<?= Uri::create('/Login') ?>'><i class='fa fa-sign-in'></i> Login</a></li>
					<?php } ?>
				</ul>
			</div>
		</div>
	</nav>

	<?php if (\Auth::check()) { ?>
	<div id='user'><?= \Session::get('username') ?>
	<?php if (\Auth::has_access('nav.[admin]')) {
		echo "<style>#user { font-style:italic; }</style>";
		echo "<select id='userselect' size='8'>";

		foreach (Model_Club::find('all') as $club) {
			echo "<option>${club['name']}</option>";
		}

		echo "</select>";
	} ?></div>
	<?php } /* auth check */ ?>

	<div class='container'>
		<?php 
		if (isset($title)) echo "<h1>$title</h1>"; 

		echo $content; 
		?>	
	</div>

	<footer>
	</footer>

</body>
</html>
