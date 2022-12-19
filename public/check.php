<pre>
<?php

echo "version:".phpversion()."\n";

$p="/var/www/vhosts/servingireland.com/lha.secureweb.ie/data/logs/2019/06";
echo "dir:".realpath(".")."\n";
echo "logdir:".is_dir($p);

phpinfo();

$matches = array();
preg_match('/^([a-z\\/\' ]*[a-z])(?:\s+([0-9]+))?$/i', trim('abc/def 2'), $matches);
print_r($matches);


