<?php

echo "<pre>";

printf("%10.10s  %-20s %s\n",
	'Date', 'Competition', 'Home (Score) Away');
foreach ($data as $d) {
	printf("%10.10s  %-20s %s (%d-%d) %s\n",
		$d['date'],
		$d['competition'],
		$d['home']['name'],
		$d['home']['score'],
		$d['away']['score'],
		$d['away']['name']);

	foreach ($d['home']['notes'] as $note) echo "    - ".trim($note,'"')."\n";
	foreach ($d['away']['notes'] as $note) echo "    - ".trim($note,'"')."\n";
}

echo "</pre>";
