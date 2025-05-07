<!DOCTYPE html>
<html>
<head>
<?php require_once('head.php'); ?>
	<script>
		$(document).ready( function() {
			$.notify.defaults({
				className: 'info',
				globalPosition: 'bottom right',
				showAnimation: 'fadeIn', hideAnimation: 'fadeOut'
			});

			/*setInterval(function() {
                $.get('<?= Uri::create('UserAPI') ?>').fail(function() {
                    console.warn("Logging user out - timeout");
					window.location = '<?= Uri::create('User/Login') ?>';
				});
        }, 30000);*/

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

<body class='admin'>
	<?php require_once('nav.php'); ?>

	<div class='xcontainer'>
		<?php if (\Session::get('user-title')) { ?>
			<div id='user'><?= \Session::get('user-title') ?></div>
		<?php }  

		if (isset($title)) echo "<h1>$title</h1>"; 

		$flash = Session::get_flash("message");
		if ($flash) { ?>
			<div class='alert alert-<?= \Arr::get($flash, 'level', 'success') ?>'>
				<?= $flash['message'] ?>
			</div>
		<?php }

		echo $content; 
		?>	
	</div>

	<!--div id='sizer' style='background: blue; width: 600px; height: 20px;margin-inline:auto;'>X</div-->

	<footer>
		<script type="text/javascript" src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
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

	<!-- Site (Fuel): site=<?= Session::get('site') ?> user=<?= Session::get('user-title') ?> group=<?= Auth::group()->get_name() ?> -->

</body>
</html>
