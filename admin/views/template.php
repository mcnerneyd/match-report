<!DOCTYPE html>
<html>
<head>
<?php require_once('head.php'); ?>
	<script>
			function tutorialrun(config, index) {
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

			setInterval(function() {
				$.get('<?= Uri::create('UserAPI') ?>').fail(function() {
					window.location = '<?= Uri::create('User/Login') ?>';
				});
			}, 300000);

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

			if (typeof tutorial === "object") {
				$('#help-me').show().click(function() {tutorialrun(tutorial);});
			} else {
				$('#help-me').hide();
			}
		});
	</script>
</head>

<body>
	<?php require_once('nav.php'); ?>

	<?php if (\Auth::check()) { ?>
	<div id='user'><?= \Session::get('username') ?><?= \Session::get('user')->section ? "/".\Session::get('user')->section['name'] : "" ?></div>
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
	<script>
		$(document).ready(function() {
			$('#cookie-consent .btn').click(function() {
				document.cookie = "CONSENT=yes";
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

		$flash = Session::get_flash("message");
		if ($flash) {
			$level = isset($flash['level']) ? $flash['level'] : 'success';
			?>
			<div class='alert alert-<?= $level ?>'>
				<?= $flash['message'] ?>
			</div>
		<?php }

		echo $content; 
		?>	
	</div>

	<footer>
		<style>
			footer div {
				position:fixed;
				bottom: 0;
				left: 0;
				background: black;
				color: white;
				font-size: 60%;
				padding: 2px;
			}
		</style>
		<div>
      <span>F</span>
      <span><?= substr(\Fuel::$env,0,1) ?></span>
			<span class='visible-xs-block'>X</span>
			<span class='visible-sm-block'>S</span>
			<span class='visible-md-block'>M</span>
			<span class='visible-lg-block'>L</span>
		</div>
	</footer>

	<!-- Site (Fuel): site=<?= Session::get('site') ?> user=<?= Session::get('username') ?> group=<?= Auth::group()->get_name() ?> -->

</body>
</html>
