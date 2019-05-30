<?php
	class Competition {
		public $name;
		public $code;

		public function __construct($name) {
			$this->name = $name;
		}

		public static function all($club = null) {
			$list = array();
			$db = Db::getInstance();

			$req = $db->query("select distinct c.name, c.teamsize, c.teamstars, c.code, c.groups
				from competition c join entry e on c.id = e.competition_id
				order by sequence");

			return $req->fetchAll();
		}

		public static function clearConfig() 
		{
			$db = Db::getInstance();

			$db->exec("DELETE FROM code");
			$db->exec("DELETE FROM entry");
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
