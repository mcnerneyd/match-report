<?php
class Controller_Card extends Controller_Hybrid
{

	// --------------------------------------------------------------------------
	public function get_index() {
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

	public function delete_index() {
		$fixtureId = $this->param('id');

		Log::info("Trying to delete $fixtureId");

		if (!\Auth::has_access('nav.[admin]')) return new Response("Access denied", 403);

		$cards = DB::select('id')->from('matchcard')->where('fixture_id', '=', $fixtureId)->execute();
		foreach ($cards as $card) {
			DB::delete('incident')->where('matchcard_id', '=', $card)->execute();
			DB::delete('matchcard')->where('id', '=', $card)->execute();
		}

		Log::warning("Card deleted: fixture_id=$fixtureId"); 

		return new Response("Card(s) deleted", 204);
	}

}
