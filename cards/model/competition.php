<?php
	class Competition {
		public $name;
		public $code;

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

			$req = $db->query("select distinct c.id, c.name, c.teamsize, c.teamstars, c.code, c.groups, c.format, c.sequence, s.name as section
				from competition c join team__competition e on c.id = e.competition_id
					left join section s on c.section_id = s.id
				order by sequence, name");

			return $req->fetchAll();
		}

		public static function allAll($club = null) {
			$list = array();
			$db = Db::getInstance();

			$req = $db->query("select distinct c.name, c.teamsize, c.teamstars, c.code, c.groups, c.format, c.sequence
				from competition c
				order by sequence, name");

			return $req->fetchAll();
		}

		public static function clearConfig() 
		{
			$db = Db::getInstance();

			$db->exec("DELETE FROM code");
			$db->exec("DELETE FROM team__competition");
		}

		public static function addCompetition($name, $code, $teamsize, $teamstars, $regsec, $sequence) 
		{
			if (!($name and $code)) return;

			$db = Db::getInstance();

			$req = $db->prepare("INSERT INTO competition (code, name, teamsize, teamstars, sequence) 
					VALUES (:code, :name, :teamsize, :teamstars, :sequence)
					ON DUPLICATE KEY UPDATE teamsize=:teamsize, teamstars=:teamstars, sequence=:sequence");
			$req->execute(array("code"=>$code,"name"=>$name,"sequence"=>$sequence,
				":teamstars"=>isset($teamstars)?$teamstars:null,
				":teamsize"=>isset($teamsize)?$teamsize:null));

			$db->exec("REPLACE INTO code (code, target, target_id)
					SELECT code, 'Competition', id FROM competition WHERE code = '$code'");
		}
	}
?>
