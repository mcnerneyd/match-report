<?php
require_once("config.php");

mysql_connect('localhost',DB_USERNAME,DB_PASSWORD);
mysql_select_db(DB_DATABASE) or die('Cannot select database:'.mysql_error());

//header to give the order to the browser
header('Content-Type: text/csv');
header('Content-Disposition: attachment;filename=exported-data.csv');

//select table to export the data
$select_table=mysql_query("
select i.date, i.type, i.player, c.code Club, c1.code Competition, c2.code Home, c3.code Away, i.detail, i.id
from incident i
	left join code c on c.target = 'Club' and c.target_id = i.club_id
	left join matchcard m on m.id = i.matchcard_id
	left join code c1 on c1.target = 'Competition' and c1.target_id = m.competition_id
	left join code c2 on c2.target = 'Team' and c2.target_id = m.home_id
	left join code c3 on c3.target = 'Team' and c3.target_id = m.away_id
where i.resolved = 0

union all

select m.date, 'Matchcard', n.email, c.code, c1.code Competition, c2.code Home, c3.code Away, m.description, m.id
from matchcard m
	left join contact n on n.id = m.contact_id
	left join code c on c.target = 'Club' and c.target_id = n.club_id
	left join code c1 on c1.target = 'Competition' and c1.target_id = m.competition_id
	left join code c2 on c2.target = 'Team' and c2.target_id = m.home_id
	left join code c3 on c3.target = 'Team' and c3.target_id = m.away_id
where m.hidden = 0
");
$rows = mysql_fetch_assoc($select_table);

if ($rows)
{
	getcsv(array_keys($rows));
}
while($rows)
{
	getcsv($rows);
	$rows = mysql_fetch_assoc($select_table);
}

// get total number of fields present in the database
function getcsv($no_of_field_names)
{
	$separate = '';


	// do the action for all field names as field name
	foreach ($no_of_field_names as $field_name)
	{
		if (preg_match('/\\r|\\n|,|"/', $field_name))
		{
			$field_name = '"' . str_replace('"', '""', $field_name) . '"';
		}
		echo $separate . $field_name;

		//sepearte with the comma
		$separate = ',';
	}

	//make new row and line
	echo "\r\n";
}
?>
