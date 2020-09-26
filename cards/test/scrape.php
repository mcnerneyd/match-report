<?php
ini_set('display_errors',1);
ini_set('display_startup_errors',1);
ini_set('max_execution_time', 300); 
error_reporting(E_ALL);

require_once('../util.php');

$fixtures = scrape(file_get_contents('http://leinsterhockey.ie/league/110712/mens_e_y_h_l'));

echo "<pre>";
print_r($fixtures);
echo "</pre>";
?>
