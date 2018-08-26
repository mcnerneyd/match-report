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

			$req = $db->query("select distinct c.name, c.code
				from club c
					inner join team t on c.id = t.club_id
					inner join entry e on t.id = e.team_id
				order by name");

			return $req->fetchAll();
		}

		public static function fromSecretaryEmail($email) {
			$db = Db::getInstance();

			$req = $db->query("SELECT c.name
				FROM user u JOIN club c ON u.club_id = c.id
				WHERE role = 'secretary' AND u.email = '$email'
				ORDER BY u.id DESC
				LIMIT 1");

			$row = $req->fetch();

			if ($row) {
				return $row[0];
			}

			return null;
		}

		public static function getPlayerHistorySummary($club) {
			$db = Db::getInstance();

			$req = $db->query("select distinct player, c.name club, COALESCE(th.team, ta.team) team from incident i 
					join club c on i.club_id = c.id 
						join matchcard m on i.matchcard_id = m.id
						left join team th on m.home_id = th.id and th.club_id = c.id
						left join team ta on m.away_id = ta.id and ta.club_id = c.id
						where c.name = '$club'");

			$result = array();
			foreach ($req->fetchAll() as $row) {
				$playerName = cleanName($row['player']);
				$team = $row['team'];
				if (!isset($result[$playerName])) {
					$result[$playerName] = array('teams'=>array());
				}

				if (!in_array($team, $result[$playerName]['teams'])) {
					$result[$playerName]['teams'][] = $team;
				}
			}

			return $result;
		}

		public static function getEmail($club) {
			$db = Db::getInstance();

			$req = $db->query("SELECT email
				FROM user
				WHERE role = 'user' AND username = '$club'
				ORDER BY id DESC
				LIMIT 1");

			$row = $req->fetch();

			if ($row) {
				return $row[0];
			}

			return null;
		}

		public static function getPINNumber($club) {
			$db = Db::getInstance();

			$req = $db->query("SELECT u.password
				FROM user u 
				WHERE role = 'user' AND username = '$club'");

			$row = $req->fetch();

			if ($row) {
				return $row[0];
			}

			return null;
		}

		public static function getRegistrationSummary() {
		  $db = Db::getInstance();

		  $req = $db->query("SELECT c.name, r.batch, min(r.date) date, r1.ct registers, r2.ct deregisters FROM registration r 
					JOIN club c ON r.club_id = c.id
				  LEFT JOIN (select batch, count(1) ct from registration where sequence <> -1 group by batch) r1 ON r.batch = r1.batch
					LEFT JOIN (select batch, count(1) ct from registration where sequence = -1 group by batch) r2 ON r.batch = r2.batch
				GROUP BY c.name, r.batch, r1.ct, r2.ct
				ORDER BY r.batch");

			return $req->fetchAll();
		}

		public static function deleteRegistration($batchId) {
		  $db = Db::getInstance();

			$db->exec("DELETE FROM registration WHERE batch = $batchId");

			debug("Deleted registration batch=$batchId");
		}

		public static function getTeamMap($club = null) {
		  $db = Db::getInstance();

		  $sql = "SELECT c.name club, team, x.name, x.teamsize, x.teamstars
					FROM club c
					JOIN team t ON c.id = t.club_id
					JOIN entry e ON e.team_id = t.id
					JOIN competition x ON x.id = e.competition_id
					WHERE x.teamsize IS NOT NULL";

			$params = array();

			if ($club != null) {
					$sql .= " AND c.name = :club";
					$params['club'] = $club;
			}

			$req = $db->prepare($sql);
			$req->execute($params);

			return $req->fetchAll();

		}

		public static function getTeamSizes($club) {
		  $db = Db::getInstance();

		  $sql = "SELECT distinct teamsize, teamstars, sequence from competition x join entry e on e.competition_id = x.id
	join team t on e.team_id = t.id
    join club c on t.club_id = c.id
where c.name = '$club' and teamsize is not null
    order by x.sequence";

			$req = $db->prepare($sql);
			$req->execute();

			$result = array();
			$carry = 0;
			foreach ($req->fetchAll() as $row) {
				$size = $row['teamsize'];
				$size += $carry;
				$carry = $row['teamstars'];		
				$size -= $carry;
				$result[] = $size;
			}

			return $result;
		}

		public static function addClub($name, $code, $regsec, $entries) {
			if (!($name and $code)) return;
			if (!($regsec)) return;

			debug("-- Updating club code:$name/$code");

			$db = Db::getInstance();

			$db->beginTransaction();

			// Create Club
			$stmt = $db->prepare("INSERT IGNORE INTO club (name) VALUES (:name)") ;
			$stmt->execute(array(":name"=>$name));

			$stmt = $db->prepare("UPDATE club SET code = :code WHERE name = :name");
			$stmt->execute(array(":name"=>$name,":code"=>$code));

			$stmt = $db->prepare("REPLACE INTO code (code, target, target_id) 
				SELECT code,'Club',id FROM club WHERE code = :code");
			$stmt->execute(array(":code"=>$code));

			debug('Updating teams ('.count($entries).')');

			// Create Teams
			$stmt = $db->prepare("INSERT IGNORE INTO team (club_id, team) 
					SELECT id, :team FROM club WHERE code = :code");
			foreach ($entries as $entry) {
				$stmt->execute(array(":team"=>$entry[1],":code"=>$code));
			}

			// Create Users
			$stmt = $db->prepare("INSERT IGNORE INTO user (username, email, club_id, role) 
					SELECT name, :email, id, 'user' FROM club WHERE code = :code");
			$stmt->execute(array(":email"=>$regsec,":code"=>$code));

			$stmt = $db->prepare("SELECT LOWER(u.email), u.club_id FROM user u 
					JOIN club c ON u.club_id = c.id 
					WHERE c.code = :code AND role = 'secretary'");
			$stmt->execute(array(":code"=>$code));
			$row = $stmt->fetch();

			//if ($row) echo "${row[0]} ... $regsec\n";

			if (!$row or $row[0] != strtolower($regsec)) { 
				if ($row) {
					$db->exec("DELETE FROM user WHERE club_id = ${row[1]} AND role = 'secretary'");
				}

				$stmt = $db->prepare("INSERT INTO user (username, email, club_id, role) 
						SELECT :email, :email, id, 'secretary' FROM club WHERE code = :code");
				$stmt->execute(array(":email"=>$regsec,":code"=>$code));
			}

			$stmt = $db->prepare("INSERT IGNORE INTO entry (team_id, competition_id) 
				SELECT t.id, x.id
				FROM team t
				JOIN club c ON t.club_id = c.id,
					competition x
				WHERE t.team = :team
				AND c.code = :code
				AND x.name = :competition");

			foreach ($entries as $entry) {
				$stmt->execute(array(":team"=>$entry[1],":code"=>$code,":competition"=>$entry[0]));
			}

			debug('Updating team codes');

			$db->exec("REPLACE INTO code (target_id, target, code) 
				SELECT t.id, 'Team', CONCAT(c.code,t.team) teamcode 
				FROM team t 
				JOIN code c ON t.club_id = c.target_id AND c.target = 'Club'") ;

			debug("Update complete ($code)");

			$db->exec("UPDATE club 
					SET pin = SUBSTRING(REVERSE(CONCAT('0000', ROUND(9999.0 * RAND()))),1,4) 
					WHERE pin IS NULL");

			$db->exec("UPDATE user u JOIN club c ON c.name = u.username
					SET u.password = c.pin, u.club_id = c.id
					WHERE u.role = 'secretary'");

			$db->commit();
		}
	}
?>
