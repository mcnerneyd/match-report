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
	'bootstrap-confirmation.min.js',
	'notify.min.js',
	'raven.min.js',
	'jquery.validate.min.js',
	'jquery-ui.js',
	'code.js')) ?>

<?= Asset::css(array(
	'bootstrap-datetimepicker.css',
	'bootstrap.min.css',
	'datatables.min.css',
	'animate.css',
	'jquery-ui.css',
	'style.css')) ?>

	<script type="text/javascript" src="//cdnjs.cloudflare.com/ajax/libs/typeahead.js/0.10.4/typeahead.bundle.min.js"></script>
	<script  type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-3-typeahead/4.0.2/bootstrap3-typeahead.min.js"></script>
	<link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.3.1/css/all.css" integrity="sha384-mzrmE5qonljUremFsqc01SB46JvROS7bZs3IO2EmfFsd15uHvIt+Y8vEf7N7fWAU" crossorigin="anonymous">

	<script>
			function tutorialrun(config, index=0) {
				if (index >= config.length) return;
				var dirs = ["auto left","auto right","auto top","auto bottom"];
				var step = config[index];
				var dir = step['dir'] || dirs[index%4];
				var p = $(step['target']).popover({
						placement:dir,
						trigger:'manual',
						html:true,
						content:step['message'],
				})
				p.popover("show");

				window.setTimeout(function() {
					p.popover("destroy");
					tutorialrun(config, index+1);
				}, step['message'].length * 50);
			}

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

			$('[data-toggle=confirmation]').confirmation({
				rootSelector: '[data-toggle=confirmation]',
			});

			if (typeof tutorial === "object") {
				$('#help-me').show().click(function() {tutorialrun(tutorial);});
			} else {
				$('#help-me').hide();
			}
		});
	</script>
</head>

<body>
	<!-- Groups <?= print_r(Auth::get_groups(), true) ?> -->
	<?php if (Session::get('site')) { ?>
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
					<?php if (\Auth::has_access('registration.view')) { ?>
					<li><a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">Registration <span class="caret"></span></a>
						<ul class='dropdown-menu'>
							<li><a href='<?= Uri::create('Registration') ?>'>Registrations</a></li>
							<li><a href='<?= Uri::create('Registration/Info') ?>'>Club Info</a></li>
						</ul>
					</li>
					<?php } ?>
					<?php } ?>
					<li><a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">Reports <span class="caret"></span></a>
						<ul class='dropdown-menu'>
							<li><a href='<?= Uri::create('Report/Scorers') ?>'>Top Scorers</a></li>
							<li><a href='<?= Uri::create('Report/Mismatch') ?>'>Mismatch Results</a></li>
							<?php if (\Auth::has_access('nav.[umpire]')) { ?>
							<li role="separator" class="divider"></li>
							<li><a href='<?= Uri::create('Report/Cards') ?>'>Red/Yellow Cards</a></li>
							<?php } ?>
							<?php if (\Auth::has_access('nav.[admin]')) { ?>
							<li role="separator" class="divider"></li>
							<li><a href='<?= Uri::create('Report/RegSec') ?>'>Anomalies</a></li>
							<li><a href='<?= Uri::create('Report/Parsing') ?>'>Parsing</a></li>
							<?php } ?>
						</ul>
					</li>
				</ul>

				<!-- Search box -->
				<form class="navbar-form navbar-left hidden-sm">
					<div id='search' class="input-group">
						<input type="text" class="form-control" name='q' placeholder="Search Club, Competition, Date or Card/Fixture ID">
						<div class='input-group-btn'>
							<button class='btn btn-default' type='submit'><i class='glyphicon glyphicon-search'></i></button>
						</div>
					</div>
				</form>

				<!-- Admin Menu -->
				<ul class='nav navbar-nav navbar-right'>
					<li>
						<a id='help-me' class='disabled'>
							<i class="fas fa-chalkboard-teacher"></i> Help!
						</a>
					</li>
					<?php if (\Auth::has_access('nav.[admin]')) { ?>
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
					<?php if (\Auth::check()) { ?>
					<li><a href='<?= Uri::create('/Login') ?>'><i class="fas fa-sign-out-alt"></i> Logout</a></li>
					<?php } else { ?>
					<li><a href='<?= Uri::create('/Login') ?>'><i class='fas fa-sign-in-alt'></i> Login</a></li>
					<?php } ?>
				</ul>
			</div>
		</div>
	</nav>
	<?php } /* $site */ ?>

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

	<?php if (!Cookie::get('CONSENT', null)) { ?>
	<style>
	#cookie-consent {
		position: fixed;
		right: 0;
		left: 0;
		bottom: 0;
		background: lightblue;
		padding: 5%;
		z-index: 2000;
		text-align: center;
	}
	#lock {
		position: fixed;
		top:0;
		bottom:0;
		left:0;
		right:0;
		opacity: 50%;
		background: rgba(0,0,0,0.8);
		z-index: 1500;
	}
	</style>
	<script src="https://cdn.jsdelivr.net/npm/js-cookie@2/src/js.cookie.min.js"></script>
	<script>
		$(document).ready(function() {
			$('#cookie-consent .btn').click(function() {
				Cookies.set("CONSENT", "yes");
				$('#lock').remove();
				$('#cookie-consent').remove();
			});

		});
	</script>
	<div id='lock'></div>
	<div id='cookie-consent'>
		<p>cards.leinsterhockey.ie uses cookies and retains user data. Please
		see <a>here</a> for details. Are you happy to accept these
		cookies and for your data to be used and retained according
		to our stated policy?</p>

		<button class='btn btn-warning'>Yes</button>
	</div>
	<?php } ?>

	<div class='container'>
		<?php 
		if (isset($title)) echo "<h1>$title</h1>"; 

		echo $content; 
		?>	
	</div>

	<footer>
		<style>
			footer .btn {
				position:fixed;
				bottom: 1px;
				right: 1px;
				background: black;
				color: white;
				font-size: 60%;
				padding: 2px;
			}
		</style>
		<div>
			<p class='btn visible-xs-block'>XS</p>
			<p class='btn visible-sm-block'>SM</p>
			<p class='btn visible-md-block'>MD</p>
			<p class='btn visible-lg-block'>LG</p>
		</div>
	</footer>

	<!-- Site (Fuel): site=<?= Session::get('site') ?> user=<?= Session::get('username') ?> group=<?= Auth::group()->get_name() ?> -->

</body>
</html>
