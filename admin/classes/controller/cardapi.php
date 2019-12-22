<?php

class Controller_CardApi extends Controller_Rest {

    // --------------------------------------------------------------------------
    public function delete_team() {
        if (!\Auth::has_access('card_team.delete'))
            return new Response("Forbidden", 401);

        $clubName = Session::get("club");
        $cardid = Input::param('cardid');

        if (!$cardid)
            return new Response("No such card", 404);
        if (!$clubName)
            return new Response("Invalid club", 405);

        Model_Incident::query()->where('matchcard_id', '=', $cardid)->delete();
    }

    /**
     * Reset the match card.
     */
    public function delete_index() {
        if (!\Auth::has_access('card.delete') && Session::get('site', null) != 'test')
            return new Response("Forbidden", 401);

        $fixtureId = Input::param('id');

        Log::info("Trying to delete $fixtureId");

        $cards = DB::select('id')->from('matchcard')->where('fixture_id', '=', $fixtureId)->execute();
        foreach ($cards as $card) {
            DB::delete('incident')->where('matchcard_id', '=', $card)->execute();
            DB::delete('matchcard')->where('id', '=', $card)->execute();
        }

        Log::warning("Card deleted: fixture_id=$fixtureId");

        return new Response("Card(s) deleted", 204);
    }

    /**
     * Add a note to the match card.
     */
    public function post_note() {
        if (!\Auth::has_access('card_note.create'))
            return new Response("Forbidden", 401);

				$clubId = \Auth::get('club_id');
				$club = Model_Club::find_by_id($clubId);
				/*$username = Session::get("username", null);
				if ($username == null) {
					$user = Model_User::find_by_username($username);
					$club = $user['club'];
					$clubId = $club['id'] ?: 0;
				} else {
					$clubId = 0;
				}*/

        $msg = Input::post('msg');

        $incident = new Model_Incident();
        $incident->player = '';
        $incident->matchcard_id = Input::post('card_id');
        $incident->detail = '"' . $msg . '"';
        $incident->type = 'Other';
        $incident->club_id = $clubId;
        $incident->resolved = 0;
        $incident->save();

        if ($msg == 'Match Postponed') {
            $msg = urlencode("PP by " . $club['name']);
            $card = Model_Card::card(Input::post('card_id'));
            $fixtureId = $card['fixture_id'];
            $url = "https://admin.sportsmanager.ie/fixtureFeed/push.php?fixtureId=$fixtureId&comment=$msg";

            Log::info("$url");
            echo file_get_contents($url);
        }

        return new Response("Note Added", 201);
    }

		public function delete_incident() {
        if (!\Auth::has_access('incident.delete')) return new Response("Forbidden", 401); 

				$incidentId = \Input::param("incident_id", null);

				if ($incidentId) {
					$incident = Model_Incident::find($incidentId);
					Log::warning("Deleting incident: $incidentId");
					$incident->delete();
				}

        return new Response("Incident Deleted:$incidentId", 204);
		}

    /**
     * Remove a player from the match card.
     */
    public function delete_player() {
				if (!\Auth::check()) return new Response("Forbidden", 401);

        $cardId = \Input::param('card_id');
        $player = \Input::param('player');

        foreach (Model_Incident::find('all', array(
            'where' => array(
                array('matchcard_id', $cardId),
                'player' => $player,))) as $incident) {
            switch ($incident['type']) {
                case 'Played':
                    $incident['resolved'] = 1;
                    $incident['detail'] = '';
                    $incident->save();
                    break;
                case 'Scored':
                case 'Red Card':
                case 'Yellow Card':
                case 'Ineligible':
                    $incident->delete();
                    break;
            }
        }

        Log::info("Removed: $player from card $cardId");
    }

    /**
     * Add a player or update player information on the match card.
     */
    public function post_player() {
		if (!\Auth::check()) return new Response("Forbidden", 401);

        try {
            $cid = \Input::param('card_id');
            $card = Model_Card::card($cid);

			if (\Auth::has_access("card.superedit") || \Auth::has_access("card.addcards")) {
				$clubName = \Input::param('club');
				$club = Model_Club::find_by_name($clubName);
			} else {
			$clubId = \Auth::get('club_id');
				$club = Model_Club::find_by_id($clubId);
				}

				$whoami = null;
				if ($card['home_name'] === $club['name']) {
					$whoami = 'home';
				} 
				
				if ($card['away_name'] === $club['name']) {
					$whoami = 'away';
				}

				if ($whoami === null) {
					return new Response("Forbidden: ${club['name']} not playing club", 401);
				}

            //$fixture = $this->getFixtureByCardId($cid);

            /* 			if (isset($_REQUEST['club'])) {
              $club = $_REQUEST['club'];

              if (!user('umpire') && $_SESSION['club'] != $club) {
              throw new LoginException("User is not in this club");
              }
              } else {
              $club = $_SESSION['club'];
              } */

            $key = \Input::param("key", "played");
            $value = \Input::param("value", null);

            $name = \Input::param('player');
            $name = cleanName($name);
            if (!$name) {
                Log::warning("Incident request with no name: " . $key);
                return;
            }

						if ($key == 'clearcards') {
							Model_Incident::clearCards($cid, $name);
							return;
						}

            Log::debug("Adding player $name to card $cid ($value)");

            Model_Incident::addIncident($cid, $club, $name, 'Played');

						$dateS = Date::forge()->format("%Y%m%d");
            $teamNo = $card[$whoami]['team'];
						$competition = Model_Competition::find_by_name($card['competition']);
            $players = Controller_RegistrationApi::getPlayers($club['name'], $dateS, $teamNo, $competition['groups']);
						$players = array_map(function($a) { return $a['name']; }, $players);
						$players = array_values($players);
            if (!in_array($name, $players)) {
                Log::warning("Player is ineligible for club ${club['name']}, team $teamNo: $name");
                Model_Incident::addIncident($cid, $club, $name, 'Ineligible');
            }

            switch ($key) {
                case 'goal':
										if (!$value) $value = 0;

                    Model_Incident::addIncident($cid, $club, $name, 'Scored', $value);
                    break;

                case 'red':
                    Model_Incident::addIncident($cid, $club, $name, 'Red Card', $value);
                    break;

                case 'yellow':
                    Model_Incident::addIncident($cid, $club, $name, 'Yellow Card', $value);
                    break;
            }
        } catch (Throwable $e) {
            Log::warn("Error adding incident: " . $e->getMessage());
        }

        //if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        //	$this->redirectGet($fixture);
        //}
    }

    /**
     * 
     * @return \ResponseAdd a signature to the match card.
     */
    public function post_signature() {
        if (!\Auth::has_access('card_signature.create'))
            return new Response("Forbidden", 401);

        $clubName = Input::param("c");

        $club = Model_Club::find_by_name($clubName);
        $player = Input::post('player');
        $score = Input::post('score');
        $umpire = Input::post('umpire');
        $emailAddress = Input::post('receipt');
        $cardId = Input::post('card_id');
        $detail = "";
        if ($score)
            $detail = $score;
        if ($umpire)
            $detail .= "/$umpire";
        if (!$player)
            $player = "";

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
            $card = Model_Card::card($cardId);
            $title = Config::get("config.title");
            $email = Email::forge();
            //$email->from($autoEmail, "$title (No Reply)");
            $email->to($emailAddress);
            $body = View::forge("card/receipt", array(
                        "card" => $card,
                        "club" => $clubName));
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

    // --------------------------------------------------------------------------
    public function get_signatures() {
        if (!\Auth::has_access('card.view'))
            return new Response("Forbidden", 401);

        $cardId = Input::get('card_id');
        $signatures = array();
        $incidents = Model_Incident::find('all', array(
                    'where' => array(
                        array('matchcard_id', $cardId),
                        array('type', 'Signed')
        )));

        foreach ($incidents as $incident) {
            try {
                $sig = $incident['detail'];
                $sig = explode(';', $sig);

                if (count($sig) < 2)
                    continue;

                $sig = $sig[1];
                if (!$sig)
                    continue;

                $sig = base64_decode($sig);
                $sig = gzuncompress($sig);
                $sig = base64_encode($sig);

                $signatures[] = array('player' => $incident['player'],
                    'club' => $incident['club']['name'],
                    'signature' => "image/png;base64,$sig");
            } catch (Exception $e) {
                $signatures[] = array('player' => $incident['player'],
                    'error' => $e->getMessage());
            }
        }

        return $this->response($signatures);
    }

    // -- Internals -------------------------------------------------------------

    private static function closeCard($cardId, $force = false) {

        $card = Model_Card::card($cardId);

        // if the card is in post-processing state, this has been done already
        if ($card['open'] > 50) {
            Log::debug("Card already in post-processing");
            return false;
        }

        $signatures = Model_Incident::query()->
                        where('matchcard_id', '=', $cardId)->
                        where('type', '=', 'Signed')->get();

        // if the card is in pre-signature state, cannot do this
        if (!$signatures) {
            Log::debug("Card has no signatures");
            return false;
        }

        $signatories = array();
        foreach ($signatures as $signature) {
            if (!$signature->user || $signature->user->role == 'user') {
                if ($signature->user)
                    Log::info("Card signed by: " . $signature->user->username);
                else
                    Log::warning("Card not signed by user");
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
            $resultSubmit = Config::get("config.result_submit");
            Log::debug("RRS:" . $resultSubmit);

            if ($resultSubmit === 'new') {
                $fixtures = Model_Fixture::getAll($fixtureId);
                $fixture = $fixtures[$fixtureId];
                if ($homeScore === null && $awayScore === null) {
                    Log::debug("Fixture result not set");
                    $resultSubmit = 'yes';
                }
            }

            if ($resultSubmit === 'yes') {
                $url = "https://admin.sportsmanager.ie/fixtureFeed/push.php?fixtureId=$fixtureId&homeScore=$homeGoals&awayScore=$awayGoals";

                echo file_get_contents($url);
                Log::info("Result submitted: fixture=$fixtureId $homeGoals-$awayGoals ($url)");
            } else {
                Log::info("Result (not submitted): fixture=$fixtureId $homeGoals-$awayGoals");
            }

            $card = Model_Card::find($cardId);
            $card->open = 51;
            $card->save();

            Log::debug("Card close: $cardId");

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

    private static function getPlayers($club, $currentDate, $teamNo, $groups = array()) {
        $result = array();

        $initialDate = strtotime("first thursday of " . date("M YY", $currentDate));
        if ($initialDate > $currentDate) {
            $initialDate = strtotime("-1 month", $currentDate);
            $initialDate = strtotime("first thursday of " . date("M YY", $initialDate));
        }
        $initialDate = strtotime("+1 day", $initialDate);
        $players = Model_Registration::find_between_dates($club['name'], $initialDate, $currentDate);

        $history = $club->getPlayerHistorySummary();

        foreach ($players as $player) {
            if ($player['team'] < $teamNo)
                continue;
            if ($groups) {
                if (!in_array($player['team'], $groups)) {
                    continue;
                }
            }

            if (isset($history[$player['name']]))
                $teams = $history[$player['name']]['teams'];
            else
                $teams = array();

            $result[$player['name']] = array('teams' => $teams);
        }

        return $result;
    }
}
