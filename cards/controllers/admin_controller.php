<?php  class AdminController {

				public function archive() {
          checkuser('admin');

					$records = Player::archive('2016-08-01', 'sites/'.site().'/archive20160801.csv');

					require_once('views/admin/index.php');
				}

        public function log() {
					require_once('views/admin/log.php');
        }

				public function resetpin() {
					checkuser('admin');

					if (isset($_REQUEST['u'])) {
						debug("User:".$_REQUEST['u']);
						User::resetPassword($_REQUEST['u']);
					}

					redirect('admin', 'user');
				}

        public function index() {
            $club = checkuser('admin');

            $competitions = Competition::all();
            $clubs = Club::all();
            $users = User::all();
						$players = array();

						if (isset($_REQUEST['player'])) {
							$players = Player::find($_REQUEST['player']);
						}

						if (isset($_REQUEST['fid'])) {
							$card = Card::getFixture($_REQUEST['fid']);
						}

            require_once('views/admin/index.php');
        }

        public function testmail() {
            sendClubMessage('Avoca', "This is a test message","Body of the message");
        }

        public function configuration() {
            $club = checkuser('admin');

            $competitions = Competition::all();

            require_once('views/admin/configuration.php');
        }

        public function club() {
            $club = checkuser('admin');

            $clubs = Club::all();

            require_once('views/admin/club.php');
        }

        public function user() {
            $club = checkuser('admin');

            $users = User::all();

            require_once('views/admin/user.php');
        }

        public function registration() {
            $club = checkuser('admin');

            if (isset($_REQUEST['bx'])) {
                Club::deleteRegistration($_REQUEST['bx']);
            }

            $registrations = Club::getRegistrationSummary();

            require_once('views/admin/registration.php');
        }

        public function adduser() {
            $username = strtolower($_REQUEST['username']);
            $email = strtolower($_REQUEST['email']);
            $role = strtolower($_REQUEST['role']);

            User::addUser($username, $email, $role);
            
            AdminController::index();                
        }

				public function warn() {

					if (!isset($_REQUEST['c'])) {
						foreach (Club::all() as $club) {
							qpush2(url("c=${club['name']}", "warn", "admin"));
						}
						return;
					}

					$club = $_REQUEST['c'];

					$finishDate = date('Y-m-d 00:00');
					$startDate = date("Y-m-d 00:00", strtotime("$finishDate - 1 week"));

					$keys = array('club'=>$club, 'start'=>date('Y-m-d', strtotime($startDate)), 'finish'=>date('Y-m-d', strtotime($finishDate)));

					$submitted = array();
					foreach (Card::getSubmitted($startDate, $finishDate, $club) as $incard) {
						$card = Card::getFixtureByCardId($incard[0]);
						$submitted[] = array('date'=>date('Y-m-d', $card['date']), 
							'competition'=>$card['competition'],
							'home'=>$card['home']['team'], 'away'=>$card['away']['team']);
					}

					if (count($submitted) > 0) $keys['matchcards'] = $submitted;

					/*echo "<table width='100%'><tr>
						<th width='140em'>Competition</th>
						<th width='140em'>Opposition</th>
						<th>Date</th>";*/
					$lates = Card::getLateFixtures($finishDate, $club);
					$lateStrings = array();
					$lateIds = array();
					foreach ($lates as $late) {
						if (!isset($late['card'])) $cardId = Card::create($late);
						else $cardId = $late['card']['id'];

						$whoami = $late[$club];
						if (isset($late['card'][$whoami]['closed'])) continue;
						
						$opposition = ($whoami == 'home' ? 'away' : 'home');
						$lateStrings[] = array('date'=>date('Y-m-d', $late['date']),
							'competition'=>$late['competition'],
							'opposition'=>$late[$opposition]['team']);

						$lateIds[] = $cardId;
					}
					if (count($lateStrings) > 0) $keys['late'] = $lateStrings;

					//echo "<pre>".templatex('weekly_club', array('club'=>$club,'late'=>$lateStrings))."</pre>";
					$sec = createsecurekey('secretarylogin'.$club);
					$keys['validate'] = url("x=$sec&u=$club&validate=1", "loginUC", "club");
					$msg = templatex('weekly_club', $keys);
	
					if (isset($_REQUEST['test'])) {
						echo "<pre>Weekly Results Report - $club\n".$msg."</pre>";
						return;
					}

					//echo "</table>";
					sendClubMessage($club, "Weekly Results Report - $club", $msg);

					foreach ($lateIds as $lateId) Card::warn($lateId);
				}

        public function autosubmit() {
            $date = date("Y-m-d 00:00");    // Auto-submit any cards from yesterday

            echo "<pre>";
            foreach (Card::getUnsubmitted($date) as $row) {
                $card = Card::getFixture($row['fixture_id']);
                if ($card['submitted'] == 1) continue;

								$marker = "A";

                $homeScore = 0;
                $awayScore = 0;
                if (isset($card['card']['home']['closed'])) {
                    $homeScore = $card['card']['home']['score'];
                    if (isset($card['card']['away']['closed'])) {
                        $awayScore = $card['card']['away']['score'];
                    } else {
                        $awayScore = $card['card']['home']['oscore'];
                    }

										$marker .= "B$homeScore.$awayScore";
                } else {
                    $homeScore = $card['card']['away']['oscore'];
                    $awayScore = $card['card']['away']['score'];    

										$marker .= "b$homeScore.$awayScore";
                }

                if (!$homeScore) $homeScore = 0;
                if (!$awayScore) $awayScore = 0;

                $msg = "#".$card['id'].": ".date('Y-m-d', $card['date']).' '.$card['competition'].' - '.$card['home']['team'].' v '.$card['away']['team']." = $homeScore-$awayScore\n";
								echo $msg;
						
								$url = "http://admin.sportsmanager.ie/fixtureFeed/push.php?fixtureId=${card['id']}&homeScore=$homeScore&awayScore=$awayScore";

								info("SubmitA:$url =$marker");

								file_get_contents($url);

								info("autosubmit: $msg");
            }

            echo "</pre>";
        }

        public function query() {
            global $consolelog;

            checkuser('admin');

            echo "<pre>".print_r($_REQUEST,true)."</pre>";

            if (isset($_REQUEST['fid'])) {
                    $result=Card::getFixture($_REQUEST['fid'], user());
						} else if (isset($_REQUEST['cid'])) {
									 $result=Card::get($_REQUEST['cid']);
						} else if (isset($_REQUEST['batch'])) {
                    $result=Player::getBatch($_REQUEST['batch']);
            } else {
                switch ($_REQUEST['q']) {
                    case 'clubs': $result=Club::all();    break;
										case 'fixtures': 
											if (isset($_GET['club'])) {
												$result=Card::fixtures($_GET['club']); 
											} else {
												$result=Card::getFixtures();
											}
											break;
                    case 'club-getteammap': $result=Club::getTeamMap(); break;
                    case 'card': 
                        if (isset($_REQUEST['id'])) $result=Card::get($_REQUEST['id']);
                        else $result=Card::getFixture($_REQUEST['fixtureid'], user());    break;
                    case 'registration': 
                        $result=Player::registration($_REQUEST['c'], $_REQUEST['d']); break;
                    case 'player': 
                        $result=Player::get($_REQUEST['n'], $_REQUEST['c']); break;
										case 'late':
												$club = null;
												if (isset($_REQUEST['c'])) $club = $_REQUEST['c'];
												$result=Card::getLateFixtures(date('Y-m-d'), $club); break;
                }
            }

            echo "<pre>".print_r($result, true)."</pre>";
            echo "<pre>$consolelog</pre>";
        }

        public function uploadConfig() {

            if (isset($_FILES["configfile"])) {

                echo "<pre>";

                debug("File:".print_r($_FILES['configfile'], true));

                $data = loadFile($_FILES["configfile"]);

                Competition::clearConfig();

                $compNames = str_getcsv(array_shift($data));

                if ($compNames[0] or $compNames[1]) throw new Exception("Not a valid configuration file");

                $cs = array_shift($data);
                $compSizes = str_getcsv($cs);
                $compCodes = str_getcsv(array_shift($data));

                debug(print_r($cs,true));

                for ($i=3; $i<count($compNames);$i++) {
                    $matches = array();
                    $teamSize = null;
                    $teamStars = null;

                    if (preg_match("/([0-9]+)(?:\+([0-9]+))?/", $compSizes[$i], $matches)) {
                        if (count($matches) > 2) $teamStars = $matches[2];
                        else $teamStars = 0;
                        $teamSize = $matches[1] + $teamStars;
                    }

                    debug($compNames[$i].",".$compSizes[$i].",".$teamSize.",".$teamStars);

                    Competition::addCompetition($compNames[$i], $compCodes[$i], $teamSize, $teamStars, 
                        'user@email.com', $i-3);        // FIXME user@email.com is managers email
                }

                foreach ($data as $line) {
                    $club = str_getcsv($line);

		    if (!$club[0]) continue;

                    $entries = array();

                    for ($j=3;$j<count($compNames);$j++) {
                        if ($club[$j]) {
                            foreach (explode(",", $club[$j]) as $entry) {
                                $entries[] = array($compNames[$j],$entry);
                            }
                        }
                    }

                    Club::addClub($club[0], $club[1], $club[2], $entries);

                    echo "Added ${club[0]}/${club[1]}, secretary:${club[2]}\n";
                }

                echo "</pre>";

            } else {
                echo "No config file";
            }

            AdminController::index();
        }
  }

