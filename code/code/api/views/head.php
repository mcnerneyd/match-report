	<title>Matchcards</title>

	<!-- Environment: <?= \Fuel::$env ?> --> 
	<!-- Base Path: <?= \Config::get('base_url') ?> --> 
	<!-- PHP Version: <?= phpversion() ?> -->

	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1"/>
	<meta http-equiv="X-UA-Compatible" content="IE=edge">

	<link rel='apple-touch-icon' sizes='57x57' href='<?= Uri::create('assets/favicon/apple-icon-57x57.png')?>'>
	<link rel='apple-touch-icon' sizes='60x60' href='<?= Uri::create('assets/favicon/apple-icon-60x60.png')?>'>
	<link rel='apple-touch-icon' sizes='72x72' href='<?= Uri::create('assets/favicon/apple-icon-72x72.png')?>'>
	<link rel='apple-touch-icon' sizes='76x76' href='<?= Uri::create('assets/favicon/apple-icon-76x76.png')?>'>
	<link rel='apple-touch-icon' sizes='114x114' href='<?= Uri::create('assets/favicon/apple-icon-114x114.png')?>'>
	<link rel='apple-touch-icon' sizes='120x120' href='<?= Uri::create('assets/favicon/apple-icon-120x120.png')?>'>
	<link rel='apple-touch-icon' sizes='144x144' href='<?= Uri::create('assets/favicon/apple-icon-144x144.png')?>'>
	<link rel='apple-touch-icon' sizes='152x152' href='<?= Uri::create('assets/favicon/apple-icon-152x152.png')?>'>
	<link rel='apple-touch-icon' sizes='180x180' href='<?= Uri::create('assets/favicon/apple-icon-180x180.png')?>'>
	<link rel='icon' type='image/png' sizes='192x192'  href='<?= Uri::create('assets/favicon/android-icon-192x192.png')?>'>
	<link rel='icon' type='image/png' sizes='32x32' href='<?= Uri::create('assets/favicon/favicon-32x32.png')?>'>
	<link rel='icon' type='image/png' sizes='96x96' href='<?= Uri::create('assets/favicon/favicon-96x96.png')?>'>
	<link rel='icon' type='image/png' sizes='16x16' href='<?= Uri::create('assets/favicon/favicon-16x16.png')?>'>
	<link rel='manifest' href='<?= Uri::create('assets/favicon/manifest.json')?>'>
	<link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
	<meta name='msapplication-TileColor' content='#ffffff'>
	<meta name='msapplication-TileImage' content='<?= Uri::create('assets/favicon/ms-icon-144x144.png')?>'>
	<meta name='theme-color' content='#ffffff'> 

	<script type="text/javascript" src="https://code.jquery.com/jquery-3.7.0.js"></script>
	<script type="text/javascript" src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
	<script type="text/javascript" src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.3.0/css/bootstrap.min.css"/>
	<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css"/>

<?= Asset::js(array(
	'moment.min.js',
	'notify.min.js',
	'raven.min.js',
	'jquery.validate.min.js',
	'jquery-ui.js',
	'selectize.min.js',
	'code.js')) ?>

<?= Asset::css(array(
	'bootstrap-datetimepicker.css',
	'animate.css',
	'jquery-ui.css',
	'selectize.css')) ?>

	<script type="text/javascript" src="//cdnjs.cloudflare.com/ajax/libs/typeahead.js/0.10.4/typeahead.bundle.min.js"></script>
	<link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.3.1/css/all.css" integrity="sha384-mzrmE5qonljUremFsqc01SB46JvROS7bZs3IO2EmfFsd15uHvIt+Y8vEf7N7fWAU" crossorigin="anonymous"/>
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-4bw+/aepP/YC94hEpVNVgiZdgIC5+VKNBQNGCHeKRQN+PtmoHDEXuppvnDJzQIu9" crossorigin="anonymous">
	<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.bundle.min.js" integrity="sha384-HwwvtgBNo3bZJJLYd8oVXjrBZt8cqVSpeBNS5n7C8IVInixGAoxmnlMuBnhbgrkm" crossorigin="anonymous"></script>
	<link rel="stylesheet" href="/assets/css/style.css?version=1.0"/>