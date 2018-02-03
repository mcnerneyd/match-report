<?php require_once('util.php');

#phpinfo();
print_r(get_loaded_extensions());

$xml = <<<XML
<?xml version='1.0'?>
<table>
	<tr>
		<td>Abcd</td>
		<td>Abcd</td>
		<td>Abcd</td>
	</tr>
</table>
XML;

style($xml,'//td','color="red"');
