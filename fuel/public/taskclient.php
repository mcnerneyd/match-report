<?php
$class = "\\tasks.".$_GET['c'];

Autoloader::load($class);

$command = $_GET['x'];

$class->$command();

