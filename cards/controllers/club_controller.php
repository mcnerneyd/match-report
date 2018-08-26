<?php
  class ClubController {

#		// ------------------------------------------------------------------------
#		public function login() {
#
#			throw new RedirectException(null, 'http://cards.leinsterhockey.ie/cards/fuel/public/Login');
#
#			global $site;
#
#			if (site()) {
#				$allusers = array();
#				$clubs = array();
#				
#				foreach (Club::all() as $club) $clubs[] = $club[0];
#				debug("Clubs:".print_r($clubs, true));
#				foreach (User::all() as $user) {
#					if (!$user['username']) continue;
#					if ($user['role'] == 'user' and !in_array($user['username'], $clubs)) continue;
#					if (!isset($allusers[$user['role']])) $allusers[$user['role']] = array();
#					$allusers[$user['role']][] = $user['username'];
#				}
#
#				if (isset($allusers['user'])) $users['Clubs']=$allusers['user'];
#				if (isset($allusers['umpire'])) $users['Umpires']=$allusers['umpire'];
#			}
#
#      require_once('views/club/login.php');
#		}
#
#		// ------------------------------------------------------------------------
#		public function logout() {
#			$this->login();
#		}
#
#		// ------------------------------------------------------------------------
#		public function export() {
#			checkuser('secretary');
#
#			$players = ClubController::getPlayers($date, $_SESSION['club'], $team);
#
#			$data = $_SESSION['club']."\nLast Name,First Name,Team\n";
#			foreach ($players as $player=>$detail) {
#				$team = $detail['team'];
#				if (!$team or $team == -1) $team = '';
#				$data .= $detail['lastname'].','.$detail['firstname'].','.$team;
#			}
#
#			require_once('view/club/export.php');
#		}

		// ------------------------------------------------------------------------
		public function register() {
			checkuser('secretary');
			$club = $_SESSION['club'];

			pushUrl();

			if (isset($_REQUEST['date'])) {
				$date = $_REQUEST['date'];
			} else {
				$date = date('Y-m-d');
			}

			$team = 1;
			if (isset($_REQUEST['team'])) $team = $_REQUEST['team'];
			$fix = isset($_REQUEST['fix']);

			$teams = Club::getTeamMap($club);
			$players = ClubController::getPlayers($date, $club, $team, $fix);

			if (isset($_REQUEST['validate'])) {
				$sequences = array();
				foreach ($players as $player=>$detail) $sequences[$player] = $detail['sequence'];
				$errors = ClubController::validateregistration($date, $club, $sequences);
				if ($errors) $players = $errors;
			}
			//echo "<pre>".print_r($players,true)."</pre>";

			require_once('views/club/registration.php');
		}

		// ------------------------------------------------------------------------
		public function uploadregistration() {

			checkuser('admin');

			if (!isset($_FILES["registrationfile"])) {
				throw new Exception("No file attached");
			}

			debug("File:".print_r($_FILES['registrationfile'], true));

			$data = loadFile($_FILES["registrationfile"]);

			$date = null;

			if (isset($_REQUEST['date'])) $date = $_REQUEST['date'];

			switch ($_REQUEST['registrationformat']) {
				case 'standardlist':
					$players = $this->orderedlistupload($data, $_SESSION['club'], $date);
					break;

				case 'numberedlist':
					$players = $this->numberedlistupload($data, $date);
					break;

				default:
					throw new Exception("Unknown format");
			}

			$date = date('Y-m-d');
			$teams = Club::getTeamMap($_SESSION['club']);

			require_once('views/club/registration.php');
		}

		// ------------------------------------------------------------------------
		private function numberedlistupload($data, $date = null) {
			if ($date == null) $date = date('Y-m-d');
			$date = date('Y-m-d 23:59', strtotime($date));
			$thisMonth = date('M Y', strtotime($date));
			$changeDate = date('Y-m-d', strtotime("first thursday of $thisMonth + 1 day"));
			if (strtotime($date) > strtotime($changeDate)) {
				$nextMonth = date("M Y", strtotime("$thisMonth + 1 month"));
				$changeDate = date('Y-m-d', strtotime("first thursday of $nextMonth + 1 day"));
			}

			$header = str_getcsv(array_shift($data));
			$club = trim($header[1]);

			if (!$club)
				throw new Exception("Registration does not specify a club (".$_SESSION['club']."/$club/Numbered)");

			if ((!user('admin')) and ($_SESSION['club'] != $club)) 
				throw new Exception("Registration is not for this club (".$_SESSION['club']."/$club/Numbered)");

			array_shift($data);		// pull headers
			array_shift($data);		// pull blank row

			$result = array();
			$ctr = 0;
			while (($line = array_shift($data)) != null) {
				$items = str_getcsv(trim($line));

				if (count($items)<2 or trim($items[1]) == '') continue;

				$player = str_replace(' ','',trim(strtoupper($items[1]))) .", ".trim($items[2]);
				$team = null;
				for ($i=5; $i<count($items); $i++) if ($items[$i]) {
					$matches = array();
					if (preg_match('/([0-9]+)[^0-9]*/', $items[$i], $matches)) {
						$team = $matches[1];
					}
				}

				$result[$player] = $team;
			}

			debug("Registering:".print_r($result, true));

			$range = Card::getDateRange();
			$openDate = $range['start'];

			if (strtotime($openDate) > strtotime($date)) $changeDate = $date;

			debug("Imported Data:".print_r($result, true));
			debug("Registering for: $date/$changeDate");

			Player::registrationSave($club, $date, $changeDate, $result);
			$players = ClubController::getPlayers($date, $club);

			return $players;

		}

		// ------------------------------------------------------------------------
		public static function orderedlistupload($data, $rclub, $date = null, $ignoreErrors = false) {

			if ($date == null) $date = date('Y-m-d');
			$date = date('Y-m-d 23:59', strtotime($date));
			$thisMonth = date('M Y', strtotime($date));
			$changeDate = date('Y-m-d', strtotime("first thursday of $thisMonth + 1 day"));
			if (strtotime($date) > strtotime($changeDate)) {
				$nextMonth = date("M Y", strtotime("$thisMonth + 1 month"));
				$changeDate = date('Y-m-d', strtotime("first thursday of $nextMonth + 1 day"));
			}


			$header = str_getcsv(array_shift($data));
			debug("Identity:".print_r($header,true));
			$club = trim($header[0]);

			if (!$club)
				throw new Exception("Registration does not specify a club (".$rclub."/$club/Standard)");

			if ((!user('admin')) and ($rclub != $club)) 
				throw new Exception("Registration is not for this club (".$rclub."/$club/Standard)");

			debug("Headers:".array_shift($data));		// pull headers

			$result = array();
			$ctr = 0;
			while (($line = array_shift($data)) != null) {
				if (trim(str_replace(",", "", $line)) == "") continue;

				$items = str_getcsv(trim($line));

				debug("L:".print_r($items, true));

				if (count($items) > 1) {
					//$player = Player::cleanName(str_replace(' ','',trim(strtoupper($items[0]))) .", ".trim($items[1]));
					$player = cleanName(str_replace(' ','',trim(strtoupper($items[0]))) .", ".trim($items[1]), 'LN, Fn');
				} else {
					//$player = Player::cleanName($items[0]);
					$player = cleanName($items[0], 'LN, Fn');
				}
				$result[$player] = -1;
			}

			debug("Registering:".print_r($result, true));

			$range = Card::getDateRange();
			$openDate = $range['start'];
			$validateDate = $range['validate'];
			$nextFirstThursday = nextFirstThursday($date);

			$validate = true;

			if (isset($_REQUEST['ignorewarnings'])) $validate = false;

			if (strtotime($validateDate) > strtotime($date)) {
				$validate = false;
			}

			if (strtotime($openDate) > strtotime($date)) {
				$changeDate = null;
			}

			debug("Imported Data:".print_r($result, true));
			debug("Registering for: $date/$changeDate/$openDate");

			$players = null;

			if ($validate) {
				$players = ClubController::validateregistration($date, $club, $result);
			} else {
				debug("No validation");
			}

			$errors = array();
			if ($players && $ignoreErrors) {
				foreach ($players as $player=>$detail) {
					if (isset($detail['error'])) {
						$errors[] = $detail['error'];
					}
				}
				$players = false;
			}

			if (!$players) {
				debug("Registration is valid");
				if (!isset($_REQUEST['test'])) {
					Player::registrationSave($club, $date, $changeDate, $result);
				}
				debug("Registration is saved");
				$players = ClubController::getPlayers($date, $club);
				debug("Registration is complete");
			} else {
				debug("Registration failure:".print_r($players, true));
			}

			if ($ignoreErrors) $players['errors'] = $errors;

			return $players;
		}

		// ------------------------------------------------------------------------
		// selectTeam: only select players for that specific team
		public static function getPlayers($date, $club, $selectTeam = 1, $fix = false) {
			/*
			$endTime = strtotime(date("Y-m-d 00:00:00", strtotime($date))." +1 day");
			$startTime = firstThursday($date);

			$startTime = firstThursday() + 24*60*60;
			*/
			/*
			$endTime = firstThursday($date);
			$startTime = firstThursday(date('Y-m-d H:i', $endTime - (2*24*60*60)));	// go back two back and get the last first thursday from there
			*/
			//if (isset($_REQUEST['d'])) $now = $_REQUEST['d'];
			//else $now = date('Y-m-d');
			if ($date == null) $date = date('Y-m-d');
			$range = rangeEnd($date);
			$startTime = $range[0];
			$endTime = $range[1];

			debug("Getting players for $club".
				" from ".date("Y-m-d H:i:s", $startTime)."/$startTime".
				" to ".date("Y-m-d H:i:s", $endTime)."/$endTime".
				" (team=$selectTeam)");

			$players = Player::registration($club, $date);

			$explicitTeam = false;		// if set, then team allocation is explicit

			// calculate rank for each player
			foreach ($players as $playerName=>$player) {

				$hiscore = null;

				// if any player has a specified team then we are using explicit teams
				if ($player['team'] and $player['team'] != -1) {
					$explicitTeam = true;
				}

				if (isset($player['history'])) {
					$hiscore = 10000;
					$players[$playerName]['teams'] = array_unique($player['history']);
					foreach ($player['history'] as $match) {
						$hiscore = min($hiscore, $match['team']);
					}

					$ct = 0;
					$ctLower = 0;
					foreach ($player['history'] as $match) {
						$matchTime = strtotime($match['date']);
						if ($matchTime < $startTime) continue;
						if ($matchTime > $endTime) continue;
						if (isset($match['leaguematch']) && $match['leaguematch'] == 0) continue;

						$ct++;
						
						if ($match['team'] != $hiscore) $ctLower++;	
					}

					$hiscore += ($ct > 0 ? $ctLower/$ct : 1.0);
				}

				$players[$playerName]['score'] = round($hiscore,2);
			}

			$teams = Club::getTeamMap($club);

			if (count($teams) == 0) throw new Exception("$club has no teams");

			$s=""; foreach ($teams as $team) $s.="${team['club']} ${team['team']} -- ${team['name']} ${team['teamsize']}/${team['teamstars']}*\n";
			debug("Team map:".$s);
			debug("Explicit Team:".($explicitTeam?"Yes":"No"));
			$s=""; foreach ($players as $player=>$detail) $s.="${detail['sequence']}: $player\n";
			debug("Registration Team:\n".$s);
			$lastTeam = count($teams);
			debug("Last Team=$lastTeam");

			if ($fix) {
				uasort($players, function($a, $b) {
					if ($a['score'] == $b['score']) {
						return $a['sequence'] < $b['sequence'] ? -1 : 1;
					}

					if ($a['score'] == 0) return 1;
					if ($b['score'] == 0) return -1;

					return $a['score'] < $b['score'] ? -1 : 1;
				});
			}

			$teamPlayers = array();

			$i = 0;
			$ct = 0;
			$team = 0;
			foreach ($players as $playerName => $player) {
				if ($explicitTeam) {
					$team = $player['team'];
					if ($team == -1) $team = $lastTeam;
					$isStarred = false;
				} else {
					if ($team == 0 or ++$i >= $ct) $ct += $teams[$team++]['teamsize'];
					if ($team >= $lastTeam) $ct = 10000;
					//$isStarred = ($i<$ct - $teams[$team-1]['teamstars']?false:true);
				}

				//if ($team < ($isStarred ? $selectTeam -1 : $selectTeam)) continue;

				$teamPlayers[$playerName]['team'] = $team;
				//$teamPlayers[$playerName]['starred'] = $isStarred;
				$teamPlayers[$playerName]['starred'] = false;
				$teamPlayers[$playerName]['name'] = $playerName;
				$teamPlayers[$playerName]['sequence'] = $players[$playerName]['sequence'];
				$teamPlayers[$playerName]['score'] = $players[$playerName]['score'];
				$teamsPlayed = array();
				foreach ($players[$playerName]['teams'] as $matchPlayed) {
					$teamsPlayed[] = $matchPlayed['team'];
				}
				$teamPlayers[$playerName]['teams'] = $teamsPlayed;
			}

			// sort by team and then by sequence
			uasort($teamPlayers, function ($a, $b) {
				if ($a['team'] == $b['team']) 
				{	if ($a['sequence'] == $b['sequence']) return 0;
					return $a['sequence'] < $b['sequence'] ? -1 : 1;
				}
				if ($a['team'] == null) return 1;
				if ($b['team'] == null) return -1;
				return $a['team'] < $b['team'] ? -1 : 1;
			});

			$players = $teamPlayers;
			$teamPlayers = array();

			$ct = 0;
			$team = 0;
			foreach ($players as $playerName => $player) {
				if ($player['team'] != $team) {
					$ct = 0;
					$team = $player['team'];
				}

				$ct++;

				$teamInfo = $teams[$team - 1];
				$isStarred = ($ct > $teamInfo['teamsize'] - $teamInfo['teamstars']);
				if ($team == $lastTeam) $isStarred = false;

				if ($team < ($isStarred ? $selectTeam -1 : $selectTeam)) continue;

				$player['teamSequence'] = $ct;
				$player['starred'] = $isStarred;

				$teamPlayers[$playerName] = $player;
			}

			$dump = "";
			foreach ($teamPlayers as $player=>$detail) $dump .= "${detail['sequence']}.${detail['name']} team=${detail['team']} score=${detail['score']}\n";
			debug("Result2:\n$dump");

			return $teamPlayers;
		}

		public static function renumber($players, $teams) {
			$lastTeamInfo = end($teams);
			$lastTeam = $lastTeamInfo['team'];
			$teamPlayers = array();

			$ct = 0;
			$team = 0;
			$teamEnd = -1;
			$teamStart = 0;
			$teamInfo = null;
			foreach ($players as $playerName => $player) {
				if ($team != $lastTeam) {
					if ($teamInfo == null || $ct > $teamEnd) {
						$team++;
						$teamInfo = $teams[$team - 1];
						$teamStart = $teamEnd;
						$teamEnd += $teamInfo['teamsize'];
					}
				}

				$player['team'] = $team;

				$ct++;

				$player['teamSequence'] = $ct - $teamStart;

				$isStarred = ($player['teamSequence'] > $teamInfo['teamsize'] - $teamInfo['teamstars']);
				if ($team == $lastTeam) $isStarred = false;

				$player['starred'] = $isStarred;

				$teamPlayers[$playerName] = $player;
			}

			return $teamPlayers;
		}

		// ------------------------------------------------------------------------
		private static function validateregistration($date, $club, $sequences) {

			$date = date('Y-m-d', strtotime($date))." 23:59";

			// Player is the list of players in the order that we are trying to register
			$players = ClubController::getPlayers($date, $club);

			// resequence - set sequence as player appears in list
			if ($sequences != null) {
				$removedPlayers = $players;

				$sequence = 1;	
				foreach ($sequences as $player=>$team) {

					if (isset($players[$player])) {
						$players[$player]['sequence'] = $sequence;
						unset($removedPlayers[$player]);
					}
					else
						// if the player is new then just add them
						$players[$player] = array('score'=>null, 'sequence'=>$sequence);

					$sequence++;
				}

				foreach ($removedPlayers as $player=>$detail) unset($players[$player]);
			}

			if (count($players) == 0) throw new Exception("Cannot register an empty list");

			uasort($players, function($a, $b) {
				if ($a['sequence'] == $b['sequence']) return 0;
					return $a['sequence'] < $b['sequence'] ? -1 : 1;
			});

			// This is the order of players that is valid
			$playersAsItShouldBe = $players;

			// sort by score and then by sequence
			uasort($playersAsItShouldBe, function ($a, $b) {
				if ($a['score'] == $b['score']) 
				{	if ($a['sequence'] == $b['sequence']) return 0;
					return $a['sequence'] < $b['sequence'] ? -1 : 1;
				}
				if ($a['score'] == null || $a['score'] == 0) return 1;
				if ($b['score'] == null || $b['score'] == 0) return -1;
				return $a['score'] < $b['score'] ? -1 : 1;
			});

			//debug("Player Copy:".print_r($playersAsItShouldBe, true));

			$teams = Club::getTeamMap($club); 
			$lastTeam = count($teams)-1;

			// calculate team limits based on ideal player scores
			$teamEnd = -1;
			$playerNames = array_keys($playersAsItShouldBe);
			for ($team = 0;$team<count($teams);$team++) {
				$teamStart = $teamEnd + 1;
				if ($teamStart >= count($playerNames)) break;
				if ($players[$playerNames[$teamStart]]['score'] == null) break;
				$teams[$team]['low'] = $players[$playerNames[$teamStart]]['score'];
				if ($team < $lastTeam) {
					$teamEnd += $teams[$team]['teamsize'];
					if ($teamEnd >= count($playerNames)) break;
					if ($players[$playerNames[$teamEnd]]['score'] == null) break;
					$teams[$team]['high'] = $players[$playerNames[$teamEnd]]['score'];
				}
			}

			for ($team = 0; $team<count($teams);$team++) {
				if (!isset($teams[$team]['low'])) {
					if ($team == 0) break;
					$teams[$team]['low'] = $teams[$team-1]['low'];
				}
			}

			// find errors
			$s=""; foreach ($teams as $team) $s.="${team['club']} ${team['team']}: ${team['name']} ${team['teamsize']}/${team['teamstars']}*\n";
			debug("Team config:\n".$s);

			// Finally, check the desired players to make sure that are all within the ranges
			$hasErrors = false;

			$i = 0;
			$ct = 0;
			$team = -1;
			foreach ($players as $playerName=>$details) {
				// clean up player names
				$players[$playerName]['name'] = $playerName;

				// calculate player's team
				if ($team == -1 or ++$i >= $ct) $ct += $teams[++$team]['teamsize'];
				if ($team > $lastTeam - 1) { $ct = 10000; $team = $lastTeam; }

				$score = $details['score'];
				if ($score == null) $score = 10000;
				$error = null;

				if ($score == null and $team == $lastTeam) continue;

				$teamDetails = $teams[$team];

				if (isset($teamDetails['high'])) {
					if ($score > $teamDetails['high']) {
						$error = $playerName." is in the wrong position. The maximum rating for players of this team is ".$teamDetails['high'].'. Move this player to a lower team.';
					}
				}

				if (isset($teamDetails['low'])) {
					if ($score < $teamDetails['low']) {
						$error = $playerName." is in the wrong position. The minimum rating for players of this team is ".$teamDetails['low'].'. Move this player to a higher team.';
					}
				}

				if (!is_null($error)) {
					$players[$playerName]['error'] = $error;
					$hasErrors = true;
				}
			}

			if ($hasErrors) return ClubController::renumber($players, $teams);

			return false;
		}
  }
