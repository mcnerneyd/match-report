<?php
class Controller_Card extends Controller_Template
{
	// --------------------------------------------------------------------------
	public function action_index() {
		$cardId = $this->param('id');
		$query = \Input::get('q', false);

		if ($cardId) {
            if (!preg_match('/n?[0-9]+/', $cardId)) {
                return new Response("Card/Fixture does not exist: $cardId", 404);
            }

			if (substr($cardId,0,1) == "n") {
				$card = Model_Matchcard::card(substr($cardId, 1));
			} else {
				$card = Model_Matchcard::find_by_fixture($cardId);
			}

			$matches = array();
			if (preg_match('/division ([0-9]*)/i', $card['competition'], $matches)) {
				$card['div-number'] = $matches[1];
			} else {
				$card['div-number'] = 0;
			}

			if (isset($card['home'])) {
				$card['home']['umpire'] = self::cleanUmpire($card['home']);
			}

			if (isset($card['away'])) {
				$card['away']['umpire'] = self::cleanUmpire($card['away']);
			}

			if (\Auth::check()) {
				Response::redirect('/cards/index.php?site='.Session::get('site').'&controller=card&action=get&fid='.$card['fixture_id']);
			} else {
				$data['card'] = $card;
				$data['searchUrl'] = Uri::create('Card/Index', array(), array('q'=>$query));

				$this->template->content = View::forge('card/card', $data);
			}
		} else if ($query) {
			$data['query'] = $query;
			$data['results'] = \Model_Matchcard::search($query);

			$this->template->content = View::forge('card/index', $data);
		} else {
			Response::redirect('/cards/index.php?site='.Session::get('site').'&controller=card&action=index');
		}
	}

	// --------------------------------------------------------------------------
	public function action_sendmail() {
		if (!\Auth::check()) return new Response("Forbidden", 401);

		$fixtureid = Input::param('id', null);

		if ($fixtureid == null) return new Response("No fixture specified", 404);

		$fixture = Model_Fixture::get($fixtureid);
		if ($fixture == null) return new Response("Fixture does not exist: $fixtureid", 404);

		$data = array();

		$data['id'] = $fixtureid;
		$data['description'] = "${fixture['competition']}: ${fixture['home']} v ${fixture['away']}";

		$emails = array();
		$ccList = Config::get("section.cc.email");
		if ($ccList) $emails = array_merge($emails, array_map('trim', explode(",", $ccList)));

		if (Session::get('site') == 'lhamen') {
			if (preg_match("/^Division [0-9]/", $fixture['competition'])) {
				$emails[] = "md".substr($fixture['competition'], 9)."@leinsterhockey.ie";
			}

			if (preg_match("/.* Cup/", $fixture['competition'])) {
				$emails[] = "menscups@leinsterhockey.ie";
			}
		}

		$data['cc'] = $emails;
		$emails = array();

		
		foreach (DB::query(/* SQL! */"select email from user u join club c on u.club_id=c.id where role='secretary' and c.name='${fixture['home_club']}'")->execute() as $user) {
			$emails[] = $user['email'];
		}

		foreach (DB::query(/* SQL! */"select email from user u join club c on u.club_id=c.id where role='secretary' and c.name='${fixture['away_club']}'")->execute() as $user) {
			$emails[] = $user['email'];
		}

		$data['to'] = $emails;

		switch (Auth::group()->get_name()) {
			case "Secretaries":
				$user = Model_User::find_by_username(\Session::get('username'));
				$data['sender'] = $user['club']['name']." (Secretary)";
				break;

			case "Users":
				$user = Model_User::find_by_username(\Session::get('username'));
				$data['sender'] = $user['club']['name'];
				break;

			case "Administrators":
				$data['sender'] = "Administrator";
				break;

			default:
				$data['sender'] = null;
		}

		$data['lastteam'] = false;

		/* FIXME
		if (isset($user['club'])) {
			$sizes = $user['club']->getTeamSizes();
			$sizes = array_keys($sizes);
			$team = $user['club']['name']." ".(end($sizes)+1);
			$data['lastteam'] = $team === $fixture['home'] || $team === $fixture['away'];
		}
		*/

		$msg = Input::post('message', null);

		if ($msg != null) {
			$autoEmail = "lhamcs@gmail.com";
			$title = Config::get("section.title");
			$email = Email::forge();
			$email->from($autoEmail, "$title (No Reply)");
			$email->reply_to(array_merge($data['to'], $data['cc']));
			$email->to($data['to']);
			$email->cc($data['cc']);
			$subject = $data['description']." #".$data['id'];
			$notes = "";
			if (Input::param('postponement-request', false)) {
				$reason = Input::param('postponement-reason', false);
				if ($reason) {
					$subject = "POSTPONEMENT REQUEST ".$subject;
					$matchDate = $fixture['datetime']->get_timestamp();
					$auto = false;
					switch ($reason) {
						case 'earlier':
							$reasonMsg = "Request to play match earlier than currently scheduled date";
							$refix = null;
							$notice = null;
							$auto = " subject to acceptance by opposition and umpires";
							break;
						case 'lenservpost':
							$reasonMsg = "Leinster Service Postponement Request";
							$refix = strtotime("+22 days", $matchDate);
							$notice = strtotime("-10 days", $matchDate);
							break;
						case 'schedconflpost':
							$reasonMsg = "Schedule Conflict Postponement Request";
							$refix = strtotime("+8 days", $matchDate);
							$notice = strtotime("-10 days", $matchDate);
							break;
						case 'lastteampost':
							$reasonMsg = "Last Team Postponement Request";
							$refix = strtotime("+22 days", $matchDate);
							$notice = strftime("%Y/%m/%d", strtotime("-1 days", $matchDate))." 13:00";
							$notice = strtotime($notice);
							$auto = ". If this is the third or more postponement for this team then it is subject to a fine and/or points deduction";
							break;
						case 'univerpost':
							$reasonMsg = "University Postponement Request";
							$refix = null; //strtotime("+22 days");
							$notice = strtotime("-30 days", $matchDate);
							break;
					}

					$msg = $reasonMsg."\n$msg";

					$notes = "";
					if ($refix) { $notes .= "** Refix must be before ".strftime("%A, %-d %B, %Y", $refix)."\n\n"; }
					if ($notice) { 
						if ($notice < time()) {
						$notes .= "** This request is not within the required notification period. A fine will be issued ".
							"(This does not mean that the request is denied)\n\n";
						}
						//$reasonMsg .= "Notice:".strftime("%A, %-d %B, %Y %H:%M", $notice)."\n"; 
					}

					if ($auto) {
						$notes .= "** Unless explicitly overridden by the Division Manager this postponement IS GRANTED$auto\n\n";
					}
				}
			}
			if ($data['sender']) {
				$msg .= "\n\nRegards,\n${data['sender']}";
			}
			$msg.= "\n----------------------------------\n\n$notes";
			$email->subject($subject);
			$email->body($msg);
			if (strpos($msg, 'SPINDLE') !== false) {	// Include the word SPINDLE to make this a test
				echo "<pre>";
				echo "Subject: $subject\n";
				echo "Message: $msg\n";
				print_r($data);
				echo "</pre>";
				return new Response( "Received", 200);
			}

			$email->send();	
			Log::info("Email sent for ${data['description']}");
			Response::redirect(rootUrl()."/card/index.php?controller=card&action=index");
		}

		$this->template->title = "Send Email";
		$this->template->content = View::forge('card/sendmail', $data);
	}

	private static function cleanUmpire($root) {
		if (!isset($root['umpire'])) return '';

		$str = $root['umpire'];

		$a = strpos($str, ";");
		if ($a) {
			$umpire = substr($str, 0, $a);
		} else {
			return "";
		}

		$b = strpos($umpire, "/");

		if ($b) { 
			$umpire = substr($umpire, $b+1);
		}

		return $umpire;
	}

	public function action_report() {
		$cardId = Input::param('card_id');
		$clubName = Input::param("club");

		$card = Model_Matchcard::card($cardId);

		echo "<!-- Card: $cardId\n".print_r($card, true)." -->";

		return new Response(View::forge("card/receipt", array(
			"card"=>$card,
			"club"=>$clubName)), 200);
	}
}
