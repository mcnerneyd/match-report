	<title>Matchcards</title>

	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1"/>
	<meta http-equiv="X-UA-Compatible" content="IE=edge">

	<link rel='apple-touch-icon' sizes='57x57' href='<?= Uri::create('favicon/apple-icon-57x57.png')?>'>
	<link rel='apple-touch-icon' sizes='60x60' href='<?= Uri::create('favicon/apple-icon-60x60.png')?>'>
	<link rel='apple-touch-icon' sizes='72x72' href='<?= Uri::create('favicon/apple-icon-72x72.png')?>'>
	<link rel='apple-touch-icon' sizes='76x76' href='<?= Uri::create('favicon/apple-icon-76x76.png')?>'>
	<link rel='apple-touch-icon' sizes='114x114' href='<?= Uri::create('favicon/apple-icon-114x114.png')?>'>
	<link rel='apple-touch-icon' sizes='120x120' href='<?= Uri::create('favicon/apple-icon-120x120.png')?>'>
	<link rel='apple-touch-icon' sizes='144x144' href='<?= Uri::create('favicon/apple-icon-144x144.png')?>'>
	<link rel='apple-touch-icon' sizes='152x152' href='<?= Uri::create('favicon/apple-icon-152x152.png')?>'>
	<link rel='apple-touch-icon' sizes='180x180' href='<?= Uri::create('favicon/apple-icon-180x180.png')?>'>
	<link rel='icon' type='image/png' sizes='192x192'  href='<?= Uri::create('favicon/android-icon-192x192.png')?>'>
	<link rel='icon' type='image/png' sizes='32x32' href='<?= Uri::create('favicon/favicon-32x32.png')?>'>
	<link rel='icon' type='image/png' sizes='96x96' href='<?= Uri::create('favicon/favicon-96x96.png')?>'>
	<link rel='icon' type='image/png' sizes='16x16' href='<?= Uri::create('favicon/favicon-16x16.png')?>'>
	<link rel='manifest' href='<?= Uri::create('favicon/manifest.json')?>'>
	<meta name='msapplication-TileColor' content='#ffffff'>
	<meta name='msapplication-TileImage' content='<?= Uri::create('favicon/ms-icon-144x144.png')?>'>
	<meta name='theme-color' content='#ffffff'> 

<?= Asset::js(array(
	'less.min.js',
	'jquery-3.3.1.js',
	'popper.min.js',
	'bootstrap.min.js',
	'moment.min.js',
	'bootstrap-datetimepicker.js',
	'bootstrap-confirmation.min.js',
	'jquery.dataTables.min.js',
	'dataTables.bootstrap4.min.js',
	'dataTables.responsive.min.js',
	'responsive.bootstrap4.min.js',
	'bootstrap-toggle.min.js',
	'notify.min.js',
	'raven.min.js',
	'jquery.validate.min.js',
	'jquery-ui.js',
	'selectize.min.js',
	'code.js')) ?>

<?= Asset::css(array(
	'bootstrap-datetimepicker.css',
	'bootstrap.min.css',
	'bootstrap-toggle.min.css',
	'dataTables.bootstrap4.min.css',
	'responsive.bootstrap4.min.css',
	'animate.css',
	'jquery-ui.css',
	'selectize.css',
	'style.css')) ?>

	<script type="text/javascript" src="//cdnjs.cloudflare.com/ajax/libs/typeahead.js/0.10.4/typeahead.bundle.min.js"></script>
	<script  type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-3-typeahead/4.0.2/bootstrap3-typeahead.min.js"></script>
	<link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.3.1/css/all.css" integrity="sha384-mzrmE5qonljUremFsqc01SB46JvROS7bZs3IO2EmfFsd15uHvIt+Y8vEf7N7fWAU" crossorigin="anonymous">
	<script type="text/javascript">
	function defined(x) { typeof x !== 'undefined'; }
	</script>
