<?php
  class Player {
    public $name;
    public $club;

		// ------------------------------------------------------------------------
    public function __construct($name, $club) {
      $this->name = $name;
      $this->club = $club;
    }

		// ------------------------------------------------------------------------
		public static function addPlayerIncident($player, $club, $type, $detail) {
			$db = Db::getInstance();
			$db->exec("insert into incident (player, club_id, type, detail)
				select '$player', id, '$type', ".($detail==null?"null":"'$detail'")." 
					from club c where c.name = '$club'");
		}

		// ------------------------------------------------------------------------
		public static function find($name) {
			$db = Db::getInstance();
			$stmt = $db->prepare("SELECT DISTINCT TRIM(firstname) firstname, TRIM(lastname) lastname, name club 
				FROM registration r join club c on c.id = r.club_id
				    where concat(lastname, firstname) like :name
				ORDER BY lastname, firstname");
			$stmt->execute(array(":name"=>"%$name%"));
			return $stmt->fetchAll();
		}

		public static function registrationMigrate($current, $new, $date, $changeDate) {
			$s=""; foreach ($current as $player=>$detail) $s.="${detail['sequence']}: $player\n";
			debug("Existing Registration:\n".$s);

			$reg = array();		// reg is the list of records that is going to be pushed to the database
			$add = array();		// add is the list of records to add at the end of reg

			// restructure list and find any new players
			$ctr = 0;
			foreach ($new as $player=>$team) {
				if (!isset($current[$player])) {
					// if the player is new add them to the end
					$add[$player] = array('player'=>$player,'sequence'=>-1,'date'=>$date, 'team'=>$team);

					// add the player into the current list as a non existant player so that change is picked up
					$current[$player] = array('sequence'=>-1,'team'=>-1);
				}

				$reg[$player] = array('player'=>$player,'sequence'=>(++$ctr),'date'=>$date, 'team'=>$team);
			}

			// find any ordering changes
			debug("Order changes:");
			$ctr = 0;
			foreach ($current as $player=>$detail) {
				$s=substr($detail['sequence']." $player                                 ", 0,30);
				// if an existing player is in the new list...
				if (isset($new[$player])) {
					$rp = isset($reg[$player]['team']) ? $reg[$player]['team'] : -1;
					$cp = isset($current[$player]['team']) ? $current[$player]['team'] : -1;
					if ($detail['sequence'] != -1) ++$ctr;
					if ($reg[$player]['sequence'] == $ctr	and $rp == $cp) {
						// if there is no change then no need to update
						unset($reg[$player]);
					} else {
						// if there is a change it can only take place on the change date
						$effectiveDate = $changeDate ?: $date;
						$reg[$player]['date'] = $effectiveDate;
						$s.="	moves to ".$reg[$player]['sequence']."/$ctr $rp|$cp on $effectiveDate";
					}
				} else {
					// existing player is not in the new list, remove
					$reg[$player] = array('player'=>$player,'sequence'=>-1,'date'=>$date, 'team'=>null);
					$s.="	deleted";
					++$ctr;
				}
				debug($s);
			}

			$updates = array();

			$regAdds = 0;
			foreach ($add as $player=>$detail) {
				$ctr++;
				$detail['sequence'] = $ctr;
				$detail['player'] = $player;
				$updates[] = $detail;
				debug(substr($ctr." $player                                 ", 0,30)."  new on $date");
				$regAdds++;
			}

			$regChanges = 0;
			foreach ($reg as $player=>$detail) {
				$detail['player'] = $player;
				$ignore = false;
				foreach ($updates as $update) {
					if ($update['player'] != $player) continue;
					if ($update['sequence'] != $detail['sequence']) continue;
					$ignore = true;
				}
				if ($ignore) continue;
				$updates[] = $detail;
				$regChanges++;
			}

			$s=""; foreach ($updates as $detail) $s.="${detail['sequence']}/${detail['team']}: ${detail['player']} ${detail['date']}\n";
			debug("\nChange in registration: ".$regChanges."/New Players: ".$regAdds."\n".$s);

			return $updates;
		}

		// ------------------------------------------------------------------------
		public static function registrationSave($club, $date, $changeDate, $new) {
			debug("---- Registration Processing -----------------------------------\nClub:$club\nDate:$date\nChange Date:$changeDate");
			$current = Player::registration($club, $date);

			$reg = Player::registrationMigrate($current, $new, $date, $changeDate);

      $db = Db::getInstance();
			$req = $db->query("select max(batch) from registration");
			$row = $req->fetch();
			$batch = ($row[0] ?: 0) + 1;

			$req = $db->query("select id from club where name = '$club'");
			$row = $req->fetch();
			$clubId = $row[0];

			$stmt = $db->prepare("INSERT INTO registration 
				(firstname, lastname, sequence, club_id, batch, date, team_id, batch_date)
				SELECT :fname, :lname, :sequence, :clubid, :batch, :date, (select id
					FROM team WHERE club_id = :clubid AND team = :team) as teamid, :batch_date");
			foreach ($reg as $detail) {
				$player = $detail['player'];
				$a = strrpos($player, ',');
				$lastName = trim(substr($player, 0, $a));
				$firstName = trim(substr($player, $a + 1));
				$stmt->execute(array("fname"=>$firstName, "lname"=>$lastName, "sequence"=>$detail['sequence'], 
					"clubid"=>$clubId, "batch"=>$batch, "date"=>$detail['date'], 
					"team"=>$detail['team'], "batch_date"=>$date));
			}
			debug("----------------------------------------------------------------");
		}

		// ------------------------------------------------------------------------
    public static function scorerReport($page, $count, $club, $competition) {
      $list = array();
      $db = Db::getInstance();
      $start = $page * $count;

      $sql = "select x.name competition, c.name club, i.player, sum(detail) score
        from incident i
          join matchcard m on m.id = i.matchcard_id
          join club c on c.id = i.club_id
          join competition x on x.id = m.competition_id
        where type = 'Scored' and detail is not null";

      $params = array();
      if ($club) { $sql .= " and c.name = :club "; $params['club'] = $club; }
      if ($competition) { $sql .= " and x.name = :competition "; $params['competition'] = $competition; }

      $sql .= " group by x.name, c.name, i.player
        order by score desc
        limit $start, $count";

      $req = $db->prepare($sql);
      $req->execute($params);

      $count = 0;
      foreach ($req->fetchAll() as $row) {
        $row['rank'] = ++$count + $start;
        $list[] = $row;
      }

      return $list;
    }

		// ------------------------------------------------------------------------
    public static function get($name, $club) {
      $result = array();
      $db = Db::getInstance();
      $names = Player::splitName($name);

      /*$sql = "
        select firstname, lastname, c.name club, i.detail number, v.id image_id
          from registration r
            join club c 
              on r.club_id = c.id
            left join incident i 
              on i.player = concat(r.lastname,', ',r.firstname) and i.club_id = c.id and i.type = 'Number'
            left join image_player v 
              on v.name = concat(r.lastname,', ',r.firstname) and v.club_id = c.id
          where firstname = :first
            and lastname = :last
            and c.name = :club
          order by r.id desc, image_id desc limit 1
        ";

      $req = $db->prepare($sql);
      $req->execute(array('first'=>$names[0], 'last'=>$names[1], 'club'=>$club));

      $result = $req->fetch();

      $result['name'] = $result['lastname'].", ".$result['firstname'];*/

      $sql = "
       select i.date, 
             if (ch.id = i.club_id,'home','away') venue,
						 if (x.id is not null and x.teamsize is not null, true, false) league,
						 x.name competition,
             ch.name homeclub, th.team hometeam,
             ca.name awayclub, ta.team awayteam,
						 m.fixture_id,
						 m.id matchcard_id,
						 i.id incident_id,
						 x.code
          from incident i
            join matchcard m on i.matchcard_id = m.id
						left join competition x on m.competition_id = x.id
            left join team th on m.home_id = th.id
            left join club ch on th.club_id = ch.id
            left join team ta on m.away_id = ta.id
            left join club ca on ta.club_id = ca.id
          where
            i.player = :name
            and i.type in ('Played')
						and i.resolved = 0
            and (ch.name = :club or ca.name = :club)
          order by i.date
        ";

      $req = $db->prepare($sql);
      $req->execute(array('name'=>$names[1].', '.$names[0], 'club'=>$club));

      $result['matches'] = array();

      foreach ($req->fetchAll() as $match) {
        $resultMatch = array();
        $resultMatch['date'] = $match['date'];
				$resultMatch['competition'] = $match['competition'];
				$resultMatch['competition_code'] = $match['code'];
				$resultMatch['leaguematch'] = $match['league'];
				$resultMatch['fixtureid'] = $match['fixture_id'];
				$resultMatch['matchcardid'] = $match['matchcard_id'];
				$resultMatch['incidentid'] = $match['incident_id'];
        if ($match['venue'] == 'home') {
          $resultMatch['team'] = $match['hometeam'];
          $resultMatch['detail'] = $match['hometeam'].' versus '.$match['awayclub'].' '.$match['awayteam'].' (Home)';
          $resultMatch['opposition'] = $match['awayclub'].' '.$match['awayteam'];
          $resultMatch['venue'] ='Home';
        } else {
          $resultMatch['team'] = $match['awayteam'];
          $resultMatch['detail'] = $match['awayteam'].' versus '.$match['homeclub'].' '.$match['hometeam'].' (Away)';
          $resultMatch['opposition'] = $match['homeclub'].' '.$match['hometeam'];
          $resultMatch['venue'] ='Away';
        }

        $result['matches'][] = $resultMatch;
      }

      debug(print_r($result,true));

      return $result;
    }

		// ------------------------------------------------------------------------
    public static function getPlayerImage($id) {
      $db = Db::getInstance();
      $req = $db->prepare("select image from image_player where name = :name order by id desc");

      $req->execute(array("name"=>$id));

      $row = $req->fetch();

      if (!$row) {
        return file_get_contents('img/guest.jpg');
      }

      return $row['image'];
    }

		// ------------------------------------------------------------------------
		public static function setPlayerImage($name, $club, $img) {
      $db = Db::getInstance();

			$stmt = $db->prepare("insert into image_player (club_id, name, image) 
					select id, '{$name}', ?
					from club where name = '{$club}'");
			$stmt->bindParam(1, $img, PDO::PARAM_LOB);
			$stmt->execute();
		}

		public static function setPlayerNumber($name, $club, $number) {
      $db = Db::getInstance();

			$req = $db->query("SELECT detail FROM incident i
				JOIN club c ON i.club_id = c.id
				WHERE type = 'Number' and player = '$name' and c.Name = '$club' 
				ORDER BY date DESC LIMIT 1");

			$row = $req->fetch();

			if ($row and $row['detail'] == $number) return;

			$req = $db->exec("INSERT INTO incident (player, club_id, detail, type)
				SELECT '$name', id, '$number', 'Number' FROM club WHERE name = '$club'");
		}

		// ------------------------------------------------------------------------
    public static function registration($club, $date) {
      $db = Db::getInstance();
			//$date = date("Y-m-d", strtotime($date))." 23:59";
			$date = date("Y-m-d", strtotime("$date +1 day"))." 00:00";
			debug("Query Date:".$date);
      $sql = "select name, sequence, team
          from (
          select 
              @row_number:=if(@name=name,@row_number+1,1) as RowNumber,
              @name:=name name,
              sequence,
              coalesce(team, -1) team,
              batch
          from (select concat(trim(lastname),', ',trim(firstname)) name, sequence, team, batch
              from registration r
              left join club c 
                  on r.club_id = c.id
              left join team t 
                  on r.team_id = t.id
              where c.name = :club
                  and r.date <= :date
              order by trim(lastname), trim(firstname), batch desc, r.date desc, sequence desc) t0,
              (select @row_number:=0) as t1,
              (select @name:=' ') as t2
          ) t
          where rownumber = 1 and sequence <> -1
          order by sequence";

      $req = $db->prepare($sql);
      $req->execute(array("club"=>$club,"date"=>$date));

      $result = array();
      
      foreach ($req->fetchAll() as $row) {
        $row['teams'] = array();
				$name = Player::cleanName($row['name']);
				$row['name'] = $name;
        $result[$name] = $row;
      }

      $sql = "SELECT i.date,
              player,
              if (i.club_id = ta.club_id,ta.team,th.team) team,
							x.name competition,
							if (x.id is not null and x.teamsize is not null, true, false) league
          from incident i
              join club c on i.club_id = c.id
              join matchcard m on i.matchcard_id = m.id
							left join competition x on m.competition_id = x.id
              left join team th on m.home_id = th.id
              left join team ta on m.away_id = ta.id
          where i.type = 'Played'
              and c.name = :club
							and i.resolved = 0
          order by date, player, team";

      $req = $db->prepare($sql);
      $req->execute(array("club"=>$club));

      foreach ($req->fetchAll() as $row) {
				$name = Player::cleanName($row['player']);
				if (!isset($result[$name])) continue;
        $result[$name]['history'][] = array('date'=>$row['date'],
					'date_t'=>strtotime($row['date']),
					'competition'=>$row['competition'],
					'team'=>$row['team'],'leaguematch'=>$row['league']);
      }

			$s=""; foreach ($result as $player=>$detail) $s.="${detail['sequence']}: $player\n";
      debug("Registration:\n".$s);

      return $result;
    }

		// ------------------------------------------------------------------------
		public static function getBatch($batchId) {
				$db = Db::getInstance();

			return $db->query("SELECT * FROM registration WHERE batch = $batchId")->fetchAll();
		}

		// ------------------------------------------------------------------------
		public static function archive($date, $file) {
				$db = Db::getInstance();

				$sql = "select i.Date, i.Type, i.Player, c.code Club, 
										 coalesce(x.code,'') Competition, 
										 coalesce(concat(ch.code, th.team),'') Home, 
										 coalesce(concat(ca.code, ta.team),'') Away, 
										 coalesce(i.detail,'') Detail, i.ID, i.resolved Cancelled
									from incident i
										left join club c on i.club_id = c.id
										left join matchcard m on m.id = i.matchcard_id
										left join competition x on m.competition_id = x.id
										left join team th on m.home_id = th.id
										left join club ch on th.club_id = ch.id
										left join team ta on m.away_id = ta.id
										left join club ca on ta.club_id = ca.id
									where i.date < '$date'

									union all

									select m.date, 'Matchcard', null, ch.code, 
										 coalesce(x.code,'') Competition, 
										 coalesce(concat(ch.code, th.team),'') Home, 
										 coalesce(concat(ca.code, ta.team),'') Away, 
										 '', m.id, -1
									from matchcard m
										left join competition x on m.competition_id = x.id
										left join team th on m.home_id = th.id
										left join club ch on th.club_id = ch.id
										left join team ta on m.away_id = ta.id
										left join club ca on ta.club_id = ca.id
									where m.date < '$date'
										 
									union all

									select r.date, 'Registration', concat(lastname, ', ', firstname), 
										 c.code,
										 '',
										 coalesce(concat(c.code, t.team),''), 
										 '',
										 concat(r.batch, '/', r.sequence), r.id, -1
									from registration r
										left join club c on r.club_id = c.id
										left join team t on r.team_id = t.id
									where r.date < '$date'";

				$req = $db->query($sql);

				if (file_exists($file)) throw new Exception("Archive already exists ($file)");

				$f = fopen($file, 'w');

				fputcsv($f, array('DateTime', 'Type', 'Player', 'Club', 'Competition', 'HomeTeam', 
						'AwayTeam', 'Note', 'ID', 'Deleted'));

				$ct = 0;
				while ($row = $req->fetch(PDO::FETCH_NUM)) {
					fputcsv($f, $row);
					$ct++;
				}

				fclose($f);

				$ct1 = $db->exec("DELETE FROM incident WHERE date < '$date'");
				$ct2 = $db->exec("DELETE FROM matchcard WHERE date < '$date'");
				// FIXME Merge and clear registration

				echo "<pre>$ct record(s) archived in $file\n$ct1 incident(s) deleted\n$ct2 matchcard(s) deleted\n</pre>";
		}


    private static function splitName($name) {
      if (strpos($name, ',')) {
        preg_match('/^(.*), (.*)$/', $name, $matches);
        return array(ucwords(strtolower($matches[2])), strtoupper($matches[1]));
      } else {
        preg_match('/^(.*) ([^ ]*)$/', $name, $matches);
        return array(ucwords(strtolower($matches[1])), strtoupper($matches[2]));
      }
    }

		public static function cleanName($name) {
			$names = Player::splitName($name);

			return $names[1].", ".$names[0];
		}
  }
