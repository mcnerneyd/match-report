<?php
class Controller_CardApi extends Controller_Rest
{
	public function before() {
		//if (!\Auth::has_access('admin.all')) throw new HttpNoAccessException;

		parent::before();
	}

	// --------------------------------------------------------------------------
	public function delete_team() {
		$clubName = Session::get("club");
		$cardid = Input::param('cardid');

		if (!$cardid) return new Response("No such card", 404);
		if (!$clubName) return new Response("Invalid club", 405);

		Model_Incident::query()->where('matchcard_id','=',$cardid)->delete();
	}

	// --------------------------------------------------------------------------
	public function delete_index() {
		$fixtureId = Input::param('id');

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

	// --------------------------------------------------------------------------
	public function post_note() {
		$clubName = Session::get("username");

		$club = Model_Club::find_by_name($clubName);
		$msg = Input::post('msg');

		$incident = new Model_Incident();
		$incident->player = '';
		$incident->matchcard_id = Input::post('card_id');
		$incident->detail = '"'.$msg.'"';
		$incident->type = 'Other';
		$incident->club = $club;
		$incident->resolved = 0;
		$incident->save();

		if ($msg == 'Match Postponed') {
			$msg = urlencode("PP by ".$club['name']);
			$card = Model_Card::card(Input::post('card_id'));
			$fixtureId = $card['fixture_id'];
			$url = "https://admin.sportsmanager.ie/fixtureFeed/push.php?fixtureId=$fixtureId&comment=$msg";

			Log::info("$url");
			echo file_get_contents($url);
		}

		return new Response("Note Added", 201);
	}

	// --------------------------------------------------------------------------
	public function post_signature() {
		$clubName = Input::param("c");

		$club = Model_Club::find_by_name($clubName);
		$player = Input::post('player');
		$score = Input::post('score');
		$umpire = Input::post('umpire');
		$emailAddress = Input::post('receipt');
		$cardId = Input::post('card_id');
		$detail = "";
		if ($score) $detail = $score;
		if ($umpire) $detail.= "/$umpire";
		if (!$player) $player = "";

		$incident = new Model_Incident();
		$incident->player = $player;
		$incident->matchcard_id = $cardId;
		$sig = Input::post('signature');
		if ($sig) {
			$sig = explode(',', $sig, 2);
			$sig = $sig[1];
			$sig = base64_decode($sig);
			$sig = gzcompress($sig);
			$sig = base64_encode($sig);
		}
		$incident->detail = "$detail;$sig";
		$incident->type = 'Signed';
		$incident->club = $club;
		$incident->resolved = 0;
		$incident->user_id = Model_User::find_by_username(Session::get('username'))->id; 
		$incident->save();

		if ($emailAddress) {
			Config::load('custom.db', 'config');
			$card = Model_Card::card($cardId);
			$autoEmail = Config::get("config.automation_email");
			$title = Config::get("config.title");
			$email = Email::forge();
			$email->from($autoEmail, "$title (No Reply)");
			$email->to($emailAddress);
			$body = View::forge("card/receipt", array(
				"card"=>$card,
				"club"=>$clubName));
			$matches = array();
			if (preg_match('/title>(.*)<\/title/', $body, $matches)) {
				$email->subject($matches[1]);
			}
			$email->html_body($body);
			$email->send();
			Log::info("Receipt email sent to $emailAddress");
		}

		Log::info("Card Signed $cardId/$clubName");

		if (static::closeCard($cardId)) {
			return new Response("Card closed", 201);
		}

		return new Response("Card signed", 201);
	}

	private static function closeCard($cardId, $force = false) {

		$card = Model_Card::card($cardId);

		// if the card is in post-processing state, this has been done already
		if ($card['open'] > 50) return false;

		$signatures = Model_Incident::query()->
			where('matchcard_id','=',$cardId)->
			where('type','=','Signed')->get();

		// if the card is in pre-signature state, cannot do this
		if (!$signatures) return false;

		$signatories = array();
		foreach ($signatures as $signature) {
			if (!$signature->user || $signature->user->role == 'user') {
				if ($signature->user) Log::info("Card signed by: ".$signature->user->username);
				else Log::warning("Card not signed by user");
				$signatories[] = $signature->club->name;
				continue;
			}

			// if an umpire has signed, card is closed
			if (!$force && $signature->user->role == 'umpire') {
				Log::info("Forcing submission because of official umpire");
				$force = true;
			}
		}

		$signatories = array_unique($signatories);

		if ($force || count($signatories) == 2) {

			$homeGoals = $card['home']['goals'];
			$awayGoals = $card['away']['goals'];

			$fixtureId = $card['fixture_id'];
			$url = "https://admin.sportsmanager.ie/fixtureFeed/push.php?fixtureId=$fixtureId&homeScore=$homeGoals&awayScore=$awayGoals";

			echo file_get_contents($url);
				//$response = static::curl($url);
				//echo $url."\n";
				//print_r($fc);

			$card = Model_Card::find($cardId);
			$card->open = 51;
			$card->save();

			Log::info("Result submitted: fixture=$fixtureId $homeGoals-$awayGoals ($url)");

			return true;
		}

		return false;
	}

	private static function curl($url) {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		// Set so curl_exec returns the result instead of outputting it.
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		// Get the response and close the channel.
		$response = curl_exec($ch);
		curl_close($ch);

		return $response;
	}

	public function get_close() {
		$cardId = Input::param('id', null);

		if ($cardId != null) {
			self::closeCard($cardId, true);
			return;
		}

		$idq = Db::query("select distinct m.id from incident i 
													join matchcard m on i.matchcard_id = m.id
												where i.type = 'Signed' and m.open < 50 and m.date < now()");

		foreach ($idq->execute() as $cardId) {
			self::closeCard($cardId['id'], true);
		}
	}

	// --------------------------------------------------------------------------
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
