<table>
<tr><th width='200px'>Date</th><th width='200px'>First</th><th width='200px'>Next</th><th>Range End</th></tr>
<?php
function debug($msg) { echo "<pre>".$msg."</pre>"; }

require_once("../util.php");

for ($i=0; $i<20; $i++) {
	$date = date('D Y-m-d', strtotime("2016-09-28 + $i days"));
	$range = rangeEnd($date);
	echo "<tr><td>$date</td><td>".date('D Y-m-d', firstThursday($date))."</td>
		<td>".date('D Y-m-d', nextFirstThursday($date))."</td>
		<td>".date('D Y-m-d H:i', $range[0])."-".date('Y-m-d H:i', $range[1])."</td>
		<tr>";
}



?>
</table>

<?php
$dt = firstThursday();
$t1 = $dt - (2*24*60*60);
$t2 = firstThursday(date('Y-m-d', $t1));
echo "<br>1:".date('D Y-m-d H:i', $dt)."<br>2:".date('D Y-m-d H:i', $t1)."<br>3:".date('D Y-m-d H:i', $t2);

$dt = firstThursday(date('2016-10-31 17:23'));
$t1 = $dt - (2*24*60*60);
$t2 = firstThursday(date('Y-m-d', $t1));
echo "<br><br>1:".date('D Y-m-d H:i', $dt)."<br>2:".date('D Y-m-d H:i', $t1)."<br>3:".date('D Y-m-d H:i', $t2);

$dt = firstThursday(date('2016-11-03 17:23'));
$t1 = $dt - (2*24*60*60);
$t2 = firstThursday(date('Y-m-d', $t1));
echo "<br><br>1:".date('D Y-m-d H:i', $dt)."<br>2:".date('D Y-m-d H:i', $t1)."<br>3:".date('D Y-m-d H:i', $t2);
?>
