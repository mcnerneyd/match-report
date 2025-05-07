<?php
class Club {
	public $name;
	public $code;

	public function __construct($name) {
		$this->name = $name;
	}

	public static function all() {
		$list = array();
		$db = Db::getInstance();

		$req = $db->query("select distinct c.id, c.name
			from club c
				inner join team t on c.id = t.club_id
				inner join team__competition e on t.id = e.team_id
			order by name");

		return $req->fetchAll();
	}

	public static function getTeamMap($club = null)
	{
		$db = Db::getInstance();

		$sql = "SELECT c.name club, team, x.name, x.teamsize, x.teamstars
					FROM club c
					JOIN team t ON c.id = t.club_id
					JOIN team__competition e ON e.team_id = t.id
					JOIN competition x ON x.id = e.competition_id
					WHERE 1=1";
		//WHERE x.teamsize IS NOT NULL";

		$params = array();

		if ($club != null) {
			$sql .= " AND c.name = :club";
			$params['club'] = $club;
		}

		$req = $db->prepare($sql);
		$req->execute($params);

		return $req->fetchAll();

	}
}
