<?php

require_once("fuel.php");

$arr = array("home"=>array("org"=>"value","arg"=>array("bill"=>"v1")));

assert(Arr::get($arr, "home.org") == "value");
assert(Arr::get($arr, "home.arg.bill") == "v1");