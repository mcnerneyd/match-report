<?php
  class PlayerController {
		public function update() {
			$club = $_POST['c'];
			$name = $_POST['p'];

			if (count($_FILES) > 0) {
				$file = $_FILES['file'];
				$filename = $file['tmp_name'];
				$data = file_get_contents($filename);
				//trace(print_r($data, true));
				$res = imagecreatefromstring($data);
				$width = imagesx($res);
				$height = imagesy($res);
				$preferredWidth = 100;
				$preferredHeight = $preferredWidth * 1.25;
				$newheight = $height * $preferredWidth / $width;

				if ($newheight > $preferredHeight) {
					$newheight = $preferredHeight;
					$height = $preferredHeight * $width / $preferredWidth;
				}

				// Load
				$thumb = imagecreatetruecolor($preferredWidth, $newheight);

				// Resize
				$offset = ($height - $newheight * $width / $preferredWidth) /2;
				imagecopyresized($thumb, $res, 0, 0, 0, $offset, $preferredWidth, $newheight, $width, $height);

				ob_start();
				imagejpeg($res);
				$d = ob_get_contents();
				ob_end_clean();

				Player::setPlayerImage($name, $club, $d);
			}

			if (isset($_POST['n'])) {
				Player::setPlayerNumber($name, $club, $_POST['n']);
			}
		}

		public function profile() {
			$playerName = $_REQUEST['name'];
			$club = $_REQUEST['club'];
			securekey("profile$playerName$club");
			$player = Player::get($playerName, $club);

			if (!isset($player['club'])) {
				$player['club'] = $club; // ineligible player
				$player['name'] = $playerName; // ineligible player
			}

			/*
			$startDate = firstThursday() + 24*60*60;
			$endDate = firstThursday();
			$startDate = firstThursday(date('Y-m-d H:i', $endDate - (2*24*60*60)));
			*/
			if (isset($_REQUEST['d'])) $now = $_REQUEST['d'];
			else $now = date('Y-m-d');
			$range = rangeEnd($now);
			$startDate = $range[0];
			$endDate = $range[1];

			// Calculcate the top team
			$topTeam = -1;
			foreach ($player['matches'] as $match) {
				$dt = strtotime($match['date']);
				if ($dt > $endDate) continue;
				if ($topTeam == -1) $topTeam = $match['team'];
				$topTeam = min($topTeam, $match['team']);
			}

			// Get the match count and lower match count
			$rank = array();
			$matchCount = 0;
			$lowerTeamMatchCount = 0;
			foreach ($player['matches'] as $match) {
				$dt = strtotime($match['date']);

				if ($dt < $startDate) continue;
				if ($dt > $endDate) continue;
				if (isset($match['leaguematch']) && $match['leaguematch'] == 0) continue;

				$currMonth = date('m',$dt);
				$value = array();
				$value['date'] = date('Y',$dt).'-'.$currMonth.'-'.date('d',$dt);


				$matchCount++;
				if ($topTeam != $match['team']) $lowerTeamMatchCount++;

				if ($matchCount == 0) $delta = 1.0;
				else $delta = $lowerTeamMatchCount / $matchCount;

				$value['value'] = $topTeam + $delta;
				$value['top'] = $topTeam;
				$value['offset'] = $delta;

				$value['k'] = $lowerTeamMatchCount."/".$matchCount;
				$rank[] = $value;
			}

			$player['rank'] = $rank;
			if ($topTeam == -1) {
				$player['current'] = "";
				$explain = "This player has not played this year, and so is unrated.";
			} else {
				if (count($player['rank']) > 0) {
					$lastRank = end($player['rank']);
					$player['current'] = number_format($lastRank['value'], 2);
				} else {
					$player['current'] = number_format($topTeam + 1.0, 2);
				}

				$explain = "The highest team this player has played for this year is Team ".$topTeam.", so his ".($lowerTeamMatchCount > 0 || $matchCount == 0 ? "initial ":"")."rating is ".$topTeam.". ";
				if ($matchCount == 0) $explain .= "Because the player has not played since ".date('jS F', $startDate).", his rating is increased by 1. ";
				if ($lowerTeamMatchCount > 0) $explain .= "Because the player has played $lowerTeamMatchCount out of $matchCount matches for a lower team since ".date('jS F', $startDate).", his rating is increased by $lowerTeamMatchCount/$matchCount. ";
			}

			$player['explain'] = $explain;

			$back = getBackUrl();
			$dateRange = Card::getDateRange();

			debug("Player: date=".date("Y-m-d H:i:s", $startDate)." matches=$matchCount lower=$lowerTeamMatchCount\n".print_r($player, true));

			require_once('views/player/profile.php');
		}

		public function image() {
			require_once('views/player/image.php');
		}

		public function number() {
			$club = $_REQUEST['c'];
			$player = $_REQUEST['p'];
			$num = $_REQUEST['n'];

			Player::addPlayerIncident($player, $club, 'Number', $num);
		}

		public function unplay() {
			checkuser('admin');

			Incident::resolve($_REQUEST['i']);
		}
  }
?>
