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

			$card['home']['umpire'] = self::cleanUmpire($card['home']);
			$card['away']['umpire'] = self::cleanUmpire($card['away']);

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
