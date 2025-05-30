<?php

class Controller_CardApi extends Controller_RestApi
{
    public function simplify($card)
    {
        $card = parent::simplify($card);

        $card['fixtureID'] = $card['fixture_id'];

        unset($card['goals']);
        unset($card['home_name']);
        unset($card['home_team']);
        unset($card['home_id']);
        unset($card['home']['goals']);
        unset($card['home']['captain']);
        foreach ($card['home']['scorers'] as $player => $score) {
            $card['home']['players'][$player]['score'] = $score;
        }
        unset($card['home']['scorers']);
        unset($card['home']['incidents']);
        unset($card['home-opposition-score']);
        unset($card['away_name']);
        unset($card['away_team']);
        unset($card['away_id']);
        unset($card['away']['goals']);
        unset($card['away']['captain']);
        foreach ($card['away']['scorers'] as $player => $score) {
            $card['away']['players'][$player]['score'] = $score;
        }
        unset($card['away']['scorers']);
        unset($card['away']['incidents']);
        unset($card['away-opposition-score']);
        unset($card['open']);
        unset($card['signed']);

        return $card;
    }

    public function options_index()
    {
        return array();
    }

    public function get_index()
    {
        header('Access-Control-Allow-Origin: *');

        $id = $this->param('id');

        if ($id) {
            $card = Model_Matchcard::card($id);
            if (!$card) {
                return new Response("No such card", 404);
            }

            if (Input::param("signatures", null) !== null) {
                return $this->get_signatures();
            }

            return array('data' => $this->simplify($card));
        }

        $limit = \Input::param('limit', 10);
        $offset = \Input::param('offset', 0);
        $query = \Input::param('q');
        if (!is_array($query)) {
            $query = array($query);
        }
        $total = Model_Matchcard::search2($query);

        $result = Model_Matchcard::search2($query, $limit, $offset);
        foreach ($result as &$item) {
            $item = $this->simplify($item);
        }

        return array(
            'pagination' => array('offset' => $offset, 'limit' => $limit, 'total' => $total),
            'data' => $result
        );
    }

    /**
     * Edit a matchcard.
     */
    public function post_index()
    {
        if (Input::param('player', null) !== null) {
            return $this->post_player();
        }

        if (Input::param('note', null) !== null) {
            return $this->post_note();
        }

        if (Input::param('signature', null) !== null) {
            return $this->post_signature();
        }

        $data = file_get_contents('php://input');
        $data = json_decode($data, false);

        if (\Input::param("_method") === 'PATCH') {
            return $this->patch_index($data);
        }

        $competition = Model_Competition::query()
            ->where('name', '=', $data->competition)
            ->related('section')
            ->where('section.name', $data->section)
            ->get_one();
        $home = Model_Team::findTeam($data->home->club, $data->home->team);
        $away = Model_Team::findTeam($data->away->club, $data->away->team);

        Log::debug("Create card :" . print_r($competition, true));

        //echo $competition['name'].":".$home->club->name." ".$home->name." v ".$away->club->name." ".$away->name."\n";

        $card = new Model_Matchcard();
        $card->date = Date::time();
        $card->home = $home;
        $card->away = $away;
        $card->competition = $competition;
        $card->open = 1;
        $card->description = 'RestAPI';
        $card->save();

        return $this->response($card)->set_status(201);
    }

    private function patch_index($data)
    {
        print_r($data);
        return new Response("PATCH Not available yet", 400);
    }

    /**
     * Delete a matchcard.
     */
    public function delete_team()
    {
        if (!\Auth::has_access('card_team.delete')) {
            return new Response("Forbidden", 401);
        }

        $clubName = Session::get("club");
        $cardid = Input::param('cardid');

        if (!$cardid) {
            return new Response("No such card", 404);
        }
        if (!$clubName) {
            return new Response("Invalid club", 405);
        }

        Model_Incident::query()->where('matchcard_id', '=', $cardid)->delete();
    }

    /**
     * Reset the match card.
     */
    public function delete_index()
    {
        $fixtureId = Input::param('id');

        if (\Input::param('key', null) === 'remove') {
            return $this->delete_player();
        }

        if (!\Auth::has_access('card.delete') && substr($fixtureId, 0, 5) !== 'test.') {
            return new Response("Forbidden: card.delete", 401);
        }

        Log::info("Trying to delete $fixtureId");

        $matches = Model_Matchcard::find_by_key($fixtureId);

        Log::debug("ResultM" . print_r($matches, true));

        foreach ($matches as $card) {
            $id = $card['id'];
            Log::debug("Deleting: $id");
            DB::delete('incident')->where('matchcard_id', '=', $card['id'])->execute();
            DB::delete('matchcard')->where('id', '=', $card['id'])->execute();
        }

        Log::warning("Card deleted: $fixtureId");

        return new Response("Card(s) deleted", 204);
    }

    /**
     * Add a note to the match card.
     */
    public function post_note()
    {
        $clubId = \Auth::get('club_id');
        $club = Model_Club::find_by_id($clubId);
        $user = Session::get('username');
        if ($user) {
            $user = Model_User::find_by_username($user)->id;
        }

        #$msg = Input::post('msg', file_get_contents("php://input"));
        $msg = Input::param('note', null);

        if ($msg) {
            $incident = new Model_Incident();
            $incident->date = Date::time();
            $incident->player = '';
            $incident->matchcard_id = $this->param("id");
            $incident->detail = '"' . $msg . '"';
            $incident->type = 'Other';
            $incident->club_id = $clubId;
            $incident->resolved = 0;
            $incident->user_id = $user;
            $incident->save();

            if ($msg == 'Match Postponed') {
                $msg = urlencode("PP by " . $club['name']);
                $card = Model_Matchcard::card($incident->matchcard_id);
                $fixtureId = $card['fixture_id'];
                $url = "https://admin.sportsmanager.ie/fixtureFeed/push.php?fixtureId=$fixtureId&comment=$msg";

                Log::info("$url");
                echo file_get_contents($url);
            }
        }

        return new Response("Note Added", 201);
    }

    public function delete_incident()
    {
        if (!\Auth::has_access('incident.delete')) {
            return new Response("Forbidden", 401);
        }

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
    public function delete_player()
    {
        if (!\Auth::check()) {
            return new Response("Forbidden", 401);
        }

        $cardId = $this->param('id');  // FIXME mixed up
        $card = Model_Matchcard::card($cardId);

        $player = \Input::param('player');

        foreach (Model_Incident::find('all', array('where' => array('matchcard_id' => $cardId, 'player' => $player))) as $incident) {
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

        Log::info("!PLAYED [$player] #{$card['fixture_id']}/" . Auth::get_screen_name());
    }

    private static function getClub($clubName)
    {
        if (\Auth::has_access("card.addcards")) {
            return Model_Club::find_by_name($clubName);
        }

        $clubId = \Auth::get('club_id');
        return Model_Club::find_by_id($clubId);
    }

    /**
     * Add a player or update player information on the match card.
     */
    public function post_player()
    {
        Log::debug("Post player");

        if (!\Auth::check()) {
            Log::info("Profile:" . print_r(\Auth::get('id'), true));
            return new Response("Post player forbidden", 401);
        }

        $key = \Input::param("key", "played");
        $upperkey = str_replace(" ", "_", strtoupper($key));

        try {
            $id = $this->param('id') ?? \Input::param('matchcardid', null);
            if (!$id) {
                Log::warning("No matchcard specified for player update");
                return new Response("No card specified", 400);
            }

            $card = Model_Matchcard::card($id);

            if ($card === null) {
                Log::warning("No matchcard exists ($id) for player update");
                return new Response("Card specified does not exist: $id", 400);
            }

            $club = \Input::param('club');
            $club = self::getClub($club);

            if (!$club) {
                Log::warning("No club specified for player update");
                return new Response("No club specified", 400);
            }

            $name = \Input::param('player');
            $name = Model_Player::cleanName($name);
            $clubName = $club['name'];

            $whoami = null;
            if ($card['home_name'] === $clubName) {
                $whoami = 'home';
                if ($upperkey == "PLAYED")
                    $upperkey .= " (H)";
            }

            if ($card['away_name'] === $clubName) {
                $whoami = 'away';
                if ($upperkey == "PLAYED")
                    $upperkey .= " (A)";
            }

            if ($whoami === null) {
                return new Response("Forbidden: {$clubName} not playing", 401);
            }

            $teamNo = $card[$whoami]['team'];

            if (!$name) {
                Log::warning("Incident request with no name: " . $key);
                return new Response("Invalid request command", 404);
            }

            if ($key == 'clearcards') {
                Log::info("!CARDS [$name/$clubName $teamNo] #{$card['fixture_id']}/" . Auth::get_screen_name());

                Model_Incident::clearCards($id, $name, $club['id']);
                return new Response("Cards cleared", 201);
            }

            $detail = null;

            if (
                Model_Incident::query()
                    ->where('matchcard_id', $id)
                    ->where('club_id', $club->id)
                    ->where('type', 'Played')
                    ->where('resolved', '0')
                    ->count() == 0
            ) {
                $detail = '{"roles":["C"]}';	// first player is Captain
            }

            Model_Incident::addIncident($id, $club, $name, 'Played', $detail);

            $value = \Input::param("value", null);
            $detail = ($value == null ? "" : $value) . ($detail == null ? "" : $detail);

            $dateS = Date::forge()->format("%Y%m%d");
            $section = Model_Section::find_by_name($card['section']);
            $competition = Model_Competition::query()
                ->where('section_id', $section->id)
                ->where('name', $card['competition'])
                ->get_one();

            $cacheKey = preg_replace("/[^a-z0-9.-]+/i", "_", "registrationapi.players.{$section['name']}-{$club['name']}-{$competition['name']}-$dateS");

            try {
                $players = Cache::get($cacheKey);
                Log::debug("Using cached data for $cacheKey");
            } catch (Exception $e) {
                Log::debug("Cache expired: $cacheKey - reloading");
                $players = Controller_RegistrationApi::getPlayers($section, $club, $dateS, $teamNo, $competition);
                $players = array_map(function ($a) { return $a['name']; }, $players);
                $players = array_values($players);

                Cache::set($cacheKey, $players, 300);
            }

            Log::info("$upperkey [$name/$clubName $teamNo] {{$detail}} #{$card['fixture_id']}/" . Auth::get_screen_name());

            if (!in_array($name, $players)) {
                Log::warning("INELIGIBLE [$name/$clubName $teamNo] #{$card['fixture_id']}/" . Auth::get_screen_name());

                Model_Incident::addIncident($id, $club, $name, 'Ineligible');
            }

            switch ($key) {
                case 'goal':
                    if (!$value) {
                        $value = 0;
                    }

                    Model_Incident::addIncident($id, $club, $name, 'Scored', $value);
                    break;

                case 'red':
                    Model_Incident::addIncident($id, $club, $name, 'Red Card', $value);
                    break;

                case 'yellow':
                    Model_Incident::addIncident($id, $club, $name, 'Yellow Card', $value);
                    break;
            }
        } catch (Throwable $e) {
            Log::error("Error adding incident:\n" . $e);
            return new Response("Error adding incident:" . $e->getMessage(), 500);
        }

        $n = Model_Player::cleanName($name, "[Fn][LN]");

        return array("name" => $name, "firstname" => $n['Fn'], "lastname" => $n['LN']);
    }

    /**
     * Update player information
     */
    public function put_index()
    {
        if (!\Auth::check()) {
            return new Response("Forbidden", 401);
        }

        $matchcardId = $this->param('id');
        $card = Model_Matchcard::card($matchcardId);

        $club = self::getClub(\Input::param('c', null));
        $player = \Input::param('p', null);
        $detail = \Input::param('detail', null);
        if (!$detail) {
            return new Response("No change", 200);
        }

        if (!($club and $player and $matchcardId)) {
            return new Response("Missing parameter $club $player $matchcardId", 404);
        }

        Log::debug("Updating player details");

        $incident = Model_Incident::query()
            ->where('club_id', $club->id)
            ->where('matchcard_id', $matchcardId)
            ->where('player', $player)
            ->where('type', 'Played')
            ->get_one();

        $incident->detail = $detail == null ? null : json_encode($detail);

        $incident->save();

        Log::info("PLAYED+ [$player/{$club['name']}] {{" . $incident->detail . "}} #{$card['fixture_id']}");
        return new Response("Player updated", 200);
    }

    /**
     * Add a signature to the match card.
     * @return \Response
     */
    public function post_signature()
    {
        if (!\Auth::has_access('card_signature.create')) {
            return new Response("Forbidden", 401);
        }

        $clubName = Input::param("c");

        $club = Model_Club::find_by_name($clubName);
        $player = Input::post('player');
        $score = Input::post('score');
        $umpire = Input::post('umpire');
        $emailAddress = Input::post('receipt');
        $cardId = $this->param('id');
        $detail = "";
        if ($score) {
            $detail = $score;
        }
        if ($umpire) {
            $detail .= "/$umpire";
        }
        if (!$player) {
            $player = "";
        }

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
        $incident->date = Date::time();
        $incident->detail = "$detail;$sig";
        $incident->type = 'Signed';
        $incident->club = $club;
        $incident->resolved = 0;
        $incident->user_id = Model_User::find_by_username(Session::get('username'))->id;
        $incident->save();

        $card = Model_Matchcard::card($cardId);

        if ($emailAddress) {
            $title = Config::get("section.title");
            $email = Email::forge();
            //$email->from($autoEmail, "$title (No Reply)");
            $email->to($emailAddress);
            $body = View::forge("card/receipt", array(
                "card" => $card,
                "club" => $clubName
            )
            );
            $matches = array();
            if (preg_match('/title>(.*)<\/title/', $body, $matches)) {
                $email->subject($matches[1]);
            }
            $email->html_body($body);
            $email->send();
            Log::info("Receipt email sent to $emailAddress");
        }

        Log::info("SIGNED [$clubName] {{$detail}} #{$card['fixture_id']}/" . Auth::get_screen_name());

        if (static::closeCard($cardId)) {
            return new Response("Card closed", 201);
        }

        return new Response("Card signed", 201);
    }

    // --------------------------------------------------------------------------
    public function get_signatures()
    {
        if (!\Auth::has_access('card.view')) {
            return new Response("Forbidden", 401);
        }

        $cardId = $this->param('id');
        $signatures = array();
        $incidents = Model_Incident::find('all', array(
            'where' => array(
                array(
                    'matchcard_id',
                    $cardId
                ),
                array(
                    'type',
                    'Signed'
                )
            )
        )
        );

        foreach ($incidents as $incident) {
            try {
                $sig = $incident['detail'];
                $sig = explode(';', $sig);

                if (count($sig) < 2) {
                    continue;
                }

                $sig = $sig[1];
                if (!$sig) {
                    continue;
                }

                $sig = base64_decode($sig);
                $sig = gzuncompress($sig);
                $sig = base64_encode($sig);

                $club = $incident['user'];
                $club = $club ? $club['club'] : $incident['club'];
                if ($club)
                    $club = $club['name'];

                $signatures[] = array(
                    'player' => $incident['player'],
                    'club' => $club,
                    'signature' => "image/png;base64,$sig"
                );
            } catch (Exception $e) {
                $signatures[] = array(
                    'player' => $incident['player'],
                    'error' => $e->getMessage()
                );
            }
        }

        return $this->response($signatures);
    }

    public function post_result()
    {
        Log::debug("User:" . print_r(\Auth::get_profile_fields(), true));

        if (!\Auth::check()) {
            return new Response("Only users can update scores", 403);
        }

        $matchcardId = \Input::param('id');
        $card = Model_Matchcard::card($matchcardId);

        $homeGoals = $card['home']['goals'];
        $awayGoals = $card['away']['goals'];

        $fixtureId = $card['fixture_id'];

        $url = "https://admin.sportsmanager.ie/fixtureFeed/push.php?fixtureId=$fixtureId&homeScore=$homeGoals&awayScore=$awayGoals";

        Log::info("Update score: $url");

        file_get_contents($url);

        return new Response("Result fixture #$fixtureId submitted", 201);
    }

    // -- Internals -------------------------------------------------------------

    private static function closeCard($cardId, $force = false)
    {
        $card = Model_Matchcard::card($cardId);

        // if the card is in post-processing state, this has been done already
        if ($card['open'] > 50) {
            Log::debug("Card already in post-processing");
            return false;
        }

        $signatures = Model_Incident::query()->where('matchcard_id', '=', $cardId)->where('type', '=', 'Signed')->get();

        // if the card is in pre-signature state, cannot do this
        if (!$signatures) {
            Log::debug("Card has no signatures");
            return false;
        }


        $signatories = array();
        foreach ($signatures as $signature) {
            if (!$signature->user || $signature->user->group == 1) {
                if ($signature->user) {
                    Log::info("Card signed by: " . $signature->user->username);
                } else {
                    Log::warning("Card not signed by user");
                }

                if ($signature->club) {
                    $signatories[] = $signature->club['name'];
                }
                continue;
            }

            // if an umpire has signed, card is closed
            if (!$force && $signature->user->group == 2) {
                Log::info("Forcing submission because of official umpire");
                $force = true;
            }
        }

        $signatories = array_unique($signatories);

        if ($force || count($signatories) == 2) {
            $homeGoals = $card['home']['goals'];
            $awayGoals = $card['away']['goals'];
            $fixtureId = $card['fixture_id'];

            loadSectionConfig($card['section']);
            $resultSubmit = Config::get("section.result.submit");
            Log::debug("RRS:${card['section']}=[$resultSubmit]");

            if ($resultSubmit === 'new') {
                $fixtures = Model_Fixture::getAll();
                $fixture = Model_Fixture::get($fixtureId);
                if ($fixture['home_score'] === null && $fixture['away_score'] === null) {
                    Log::debug("Fixture result not set: fixture=$fixtureId");
                    $resultSubmit = 'yes';
                } else {
                    Log::info("Fixture already has result: fixture=$fixtureId");
                }
            }

            if ($resultSubmit === 'yes') {
                $url = "https://admin.sportsmanager.ie/fixtureFeed/push.php?fixtureId=$fixtureId&homeScore=$homeGoals&awayScore=$awayGoals";

                echo file_get_contents($url);
                Log::info("Result submitted: fixture=$fixtureId $homeGoals-$awayGoals ($url)");
            } else {
                Log::info("Result (not submitted): fixture=$fixtureId $homeGoals-$awayGoals");
            }

            $card = Model_Matchcard::find($cardId);
            $card->open = 51;
            $card->save();

            Log::debug("Card close: $cardId");

            return $card;
        }

        return false;
    }

    private static function curl($url)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        // Set so curl_exec returns the result instead of outputting it.
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        // Get the response and close the channel.
        $response = curl_exec($ch);
        curl_close($ch);

        return $response;
    }

    public function get_close()
    {
        $cardId = Input::param('id', null);

        if ($cardId != null) {
            self::closeCard($cardId, true);
            return;
        }

        $section = Input::param('s', null);
        $date = Input::param('dt', null);
        if ($date == null) {
            $date = Date::forge()->format('mysql');
        }
        $idq = Db::query(/* SQL! */ "select distinct m.id from incident i 
                join matchcard m on i.matchcard_id = m.id
                join competition x on m.competition_id = x.id
                join section s on x.section_id = s.id
                where i.type = 'Signed' and m.open < 50 and m.date < :date and (:section is null or s.name = :section)")
            ->bind('section', $section)
            ->bind('date', $date);

        foreach ($idq->execute() as $cardId) {
            #$c = Model_Matchcard::card($cardId['id']);
            $c = self::closeCard($cardId['id'], true);
            echo "Closed: " . $c['description'] . "\n";
        }
    }

    private static function getPlayers($club, $currentDate, $teamNo, $groups = array())
    {
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
            if ($player['team'] < $teamNo) {
                continue;
            }
            if ($groups) {
                if (!in_array($player['team'], $groups)) {
                    continue;
                }
            }

            if (isset($history[$player['name']])) {
                $teams = $history[$player['name']]['teams'];
            } else {
                $teams = array();
            }

            $result[$player['name']] = array(
                'teams' => $teams
            );
        }

        return $result;
    }
}
