<?php 
class Incident {

	public static function cards($startDate, $endDate) {
		$db = Db::getInstance();

		$res = $db->query("
			SELECT date, player, competition, home, away, player, 
					club, type, detail, username user
				FROM incidents 
				WHERE type in ('Red Card', 'Yellow Card')
					AND date between '$startDate' and '$endDate'
				ORDER BY date DESC");

		return $res->fetchAll();
	}

	public static function resolve($incidentId, $resolve = true) {
		$db = Db::getInstance();
		$db->exec("UPDATE incident SET resolved = ".($resolve?1:0). " WHERE id = $incidentId");
	}
}
?>
