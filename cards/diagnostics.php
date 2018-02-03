<?php
require_once('util.php');

if (isset($_GET['scrape'])) {
	echo "<pre>";
	$url = $_GET['scrape'];

	echo "Scraping: $url\n";
	$src = file_get_contents($url);

	print_r(scrape($src, true));

	echo "</pre>";
	return;
}
?>
<pre>
<?php 

print_r($_SERVER);

print_r($_REQUEST);

?>
</pre>

<? 
php_info();
