<?php
	class Competition {
		public $name;

		public function __construct($name) {
			$this->name = $name;
		}

		public static function entries() {
			$db = Db::getInstance();

			$req = $db->query("select tc.competition_id, t.club_id
				from team__competition tc 
				join team t on t.id = tc.team_id");

			return $req->fetchAll();
		}

		public static function all($club = null) {
			$list = array();
			$db = Db::getInstance();

			$req = $db->query("select distinct c.id, c.name, c.teamsize, c.teamstars, c.groups, c.format, c.sequence, s.name as section
				from competition c join team__competition e on c.id = e.competition_id
					left join section s on c.section_id = s.id
				order by sequence, name");

			return $req->fetchAll();
		}
	}
?>
