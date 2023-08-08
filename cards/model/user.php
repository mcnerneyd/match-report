<?php
	class User {
		public $username;
		public $role;

		public function __construct($username, $role) {
			$this->username = $username;
			$this->role = $role;
		}

		public static function all() {
			$list = array();
			$db = Db::getInstance();

			$req = $db->query("SELECT u.username, u.role, u.password, c.name club
				FROM user u
					LEFT JOIN club c ON u.club_id = c.id
				ORDER BY username");
				//WHERE password IS NOT NULL AND password <> ''

			return $req->fetchAll();
		}

		public static function addUser($username, $email, $role) {
			$db = Db::getInstance();

			$req = $db->prepare("INSERT INTO user (username, role, email)
				VALUES (:username, :role, :email)");
			$req->execute(array("username"=>$username, "role"=>$role, "email"=>$email));

			switch ($role) {
				case 'umpire':
					User::resetPassword($username);
					break;
			}
		}

		public static function resetPassword($username) {
			$db = Db::getInstance();

			$db->prepare("UPDATE user 
					SET password = SUBSTRING(REVERSE(CONCAT('0000', ROUND(9999.0 * RAND()))),1,4) 
					WHERE username = :username")
					->execute(array("username"=>$username));

			$db->prepare("UPDATE user u JOIN club c ON c.id = u.club_id
					SET c.pin = u.password
					WHERE u.username = :username and u.club_id is not null")
					->execute(array("username"=>$username));

			debug("Reset password for $username");
		}
	}
?>
