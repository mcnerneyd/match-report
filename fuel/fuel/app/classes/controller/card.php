<?php
class Controller_Card extends Controller_Template
{

	// --------------------------------------------------------------------------
	public function action_index() {
		$cardId = $this->param('id');
		$query = \Input::get('q', false);

		if ($cardId) {
			if (substr($cardId,0,1) == "n") {
				$card = Model_Card::card(substr($cardId, 1));
			} else {
				$card = Model_Card::find_by_fixture($cardId);
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

			$data['card'] = $card;
			$data['searchUrl'] = Uri::create('Card/Index', array(), array('q'=>$query));

			$this->template->content = View::forge('card/card', $data);
		} else if ($query) {
			$data['query'] = $query;
			$data['results'] = \Model_Card::search($query);

			$this->template->content = View::forge('card/index', $data);
		} else {
			$base = Uri::base()."/../../..";
			Response::redirect($base.'/index.php?site='.Session::get('site').'&controller=card&action=index');
		}
	}

	// --------------------------------------------------------------------------
	public function action_sendmail() {
		$cardid = Input::param('id', null);

		if ($cardid == null) return new Response("No card specified", 404);

		$card = Model_Fixture::get($cardid);
		if ($card == null) return new Response("Card does not exist: $cardid", 404);

		$data = array();

		$data['id'] = $cardid;
		$data['description'] = "${card['competition']}: ${card['home']} v ${card['away']}";

		$emails = array();
		if (Session::get('site') == 'lhamen') {
			$emails[] = "acheyfour@gmail.com";
			
			if (preg_match("/^Division [0-9]/", $card['competition'])) {
				$emails[] = "md".substr($card['competition'], 9)."@leinsterhockey.ie";
			}

			if (preg_match("/.* Cup/", $card['competition'])) {
				$emails[] = "menscups@leinsterhockey.ie";
			}
		}

		$data['cc'] = $emails;
		$emails = array();

		foreach (DB::query("select email from user u join club c on u.club_id=c.id where role='secretary' and c.name='${card['home_club']}'")->execute() as $user) {
			$emails[] = $user['email'];
		}

		foreach (DB::query("select email from user u join club c on u.club_id=c.id where role='secretary' and c.name='${card['away_club']}'")->execute() as $user) {
			$emails[] = $user['email'];
		}

		$data['to'] = $emails;

		$msg = Input::post('message', null);

		if ($msg != null) {
			Config::load('custom.db', 'config');
			$autoEmail = Config::get("config.automation_email");
			$title = Config::get("config.title");
			$email = Email::forge();
			$email->from($autoEmail, "$title (No Reply)");
			$email->to($data['to']);
			$email->cc($data['cc']);
			$email->subject($data['description']." #".$data['id']);
			$email->body($msg);
			$email->send();
			Log::info("Email sent for ${data['description']}");
			$base = Uri::base()."/../../..";
			Response::redirect($base.'/index.php?site='.Session::get('site').'&controller=card&action=index');
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

		$card = Model_Card::card($cardId);

		echo "<!-- Card: $cardId\n".print_r($card, true)." -->";

		return new Response(View::forge("card/receipt", array(
			"card"=>$card,
			"club"=>$clubName)), 200);
	}
}
