<pre>
<?php

echo "version:".phpversion()."\n";

$p="/var/www/vhosts/servingireland.com/lha.secureweb.ie/data/logs/2019/06";
echo "dir:".realpath(".")."\n";
echo "logdir:".is_dir($p);

phpinfo();

