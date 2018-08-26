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

	public function post_note() {
		$clubName = Session::get("username");

		$club = Model_Club::find_by_name($clubName);

		$incident = new Model_Incident();
		$incident->player = '';
		$incident->matchcard_id = Input::post('card_id');
		$incident->detail = '"'.Input::post('msg').'"';
		$incident->type = 'Other';
		$incident->club = $club;
		$incident->resolved = 0;
		$incident->save();

		return new Response("Note Added", 201);
	}

	public function post_signature() {
		$clubName = Input::param("c");

		$club = Model_Club::find_by_name($clubName);
		$player = Input::post('player');
		$score = Input::post('score');
		$umpire = Input::post('umpire');
		$detail = "";
		if ($score) $detail = $score;
		if ($umpire) $detail.= "/$umpire";
		if (!$player) $player = "";

		$incident = new Model_Incident();
		$incident->player = $player;
		$incident->matchcard_id = Input::post('card_id');
		$sig = Input::post('signature');
		$sig = explode(',', $sig, 2);
		$sig = $sig[1];
		$sig = base64_decode($sig);
		$sig = gzcompress($sig);
		$sig = base64_encode($sig);
		$incident->detail = "$detail;$sig";
		$incident->type = 'Signed';
		$incident->club = $club;
		$incident->resolved = 0;
		$incident->save();

		return new Response("Card signed", 201);
	}

	public function get_signatures() {
		$cardId = Input::get('card_id');
		$signatures = array();
		$incidents = Model_Incident::find('all', array(
			'where' => array(
				array('matchcard_id', $cardId),
				array('type','Signed')
				)));
			
		foreach ($incidents as $incident) {
			try {
				$sig = $incident['detail'];
				$sig = explode(';',$sig);
				$sig = $sig[1];
				$sig = base64_decode($sig);
				$sig = gzuncompress($sig);
				$sig = base64_encode($sig);

				$signatures[] = array('player'=>$incident['player'],
					'club'=>$incident['club']['name'],
					'signature'=>"image/png;base64,$sig");
			} catch (Exception $e) {
				$signatures[] = array('player'=>$incident['player'],
					'error'=>$e->getMessage());
			}
		}

		return $this->response($signatures);
	}
}
