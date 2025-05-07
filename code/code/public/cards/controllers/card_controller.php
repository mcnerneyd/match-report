<?php

ini_set("auto_detect_line_endings", true);

class CardController
{
    // ------------------------------------------------------------------------
    public function index()
    {
        checkuser();

        $competitions = Competition::all();
        if (isset($_SESSION['section']) && $_SESSION['section']) {
            $competitions = array_filter($competitions, function ($a) {
                return $a['section'] === $_SESSION['section'];
            });
        }
        usort($competitions, function ($a, $b) {
            return strcmp($a['name'], $b['name']);
        });

        $clubs = array();

        foreach (Club::all() as $club) {
            $clubs[] = array('id' => $club['id'], 'name' => $club['name']);
        }
        $entries = array();
        foreach (Competition::entries() as $entry) {
            $entries[] = array($entry['competition_id'], $entry['club_id']);
        }

        require_once('views/card/index.php');
    }

    // ------------------------------------------------------------------------
    // Get a matchcard based on its id
    public function get()
    {
        //checkuser();

        $id = $_REQUEST['fid'];

        Log::info("Get card for fixture:$id");

        $fixture = Card::getFixture($id);

        Log::debug("Fixture: " . print_r($fixture, true));

        if (user('umpire')) {
            if (!isset($fixture['card'])) {
                Card::create($fixture);
                $fixture = Card::getFixture($id);
                Log::info("CARD [" . Arr::get($fixture, 'card.home.team') . "][" . Arr::get($fixture, 'card.away.team') . "] " . Arr::get($fixture, 'section') . ":" . Arr::get($fixture, 'competition') . " #{$fixture['fixtureID']}");
            }

            if (isset($_REQUEST['official']) && $_REQUEST['official'] == 'yes') {
                Card::addNote($fixture['card']['id'], user(), 'Official Umpire');
            }

            $players = array();

            Log::debug("Umpire card");
            if (in_array(user(), $fixture['card']['official']) || isset($_REQUEST['official'])) {
                require_once('views/card/matchcard.php');
            } else {
                require_once('views/card/umpire_check.php');
            }

            return;
        }

        $club = Arr::get($_SESSION, 'club', null);

        if ($club) {
            if (!isset($fixture['card'])) {
                $fixture['cardid'] = Card::create($fixture);
                $fixture = Card::getFixture($id);
                Log::info("CARD [" . Arr::get($fixture, 'card.home.team') . "][" . Arr::get($fixture, 'card.away.team') . "] " . Arr::get($fixture, 'section') . ":" . Arr::get($fixture, 'competition') . " #{$fixture['fixtureID']}");
            }

            $location = null;
            if (Arr::get($fixture, 'home_club') == $club)
                $location = 'home';
            else if (Arr::get($fixture, 'away_club') == $club)
                $location = 'away';

            if ($location == null) {
                // This club is not one of the clubs on the matchcard
                Log::debug("Club $club is not part of this fixture");
                require_once('views/card/matchcard.php');
                return;
            }

            Log::debug("For fixture {$fixture['fixtureID']}: $club is $location");

            $data = [
                'section' => $fixture['section'],
                'club' => $club,
                'date' => $fixture['datetimeZ'],
                'team' => $fixture["{$location}_team"]
            ];

            $teamcard = $fixture['card'][$location];
            if (!(isset($teamcard['locked']) or isset($teamcard['closed']))) {
                require_once('views/card/fixture.php');
                return;
            }
        }

        if (isset($fixture['card'])) {
            foreach ($fixture['card']['rycards'] as $rycard) {
                $player = &$fixture['card'][$rycard['side']]['players'][$rycard['player']];
                if (!isset($player['cards'])) {
                    $player['cards'] = array();
                }
                $player['cards'][] = array('type' => $rycard['type'], 'detail' => $rycard['detail']);
            }

            Log::debug("Edit/View matchcard ($club): cardid=" . $fixture['card']['id']);
            $fixture['home_score'] = emptyValue($fixture['card']['home']['score'], 0);
            $fixture['away_score'] = emptyValue($fixture['card']['away']['score'], 0);
            $fixture['card']['away']['suggested-score'] = emptyValue($fixture['card']['home']['oscore'], 0);
            $fixture['card']['home']['suggested-score'] = emptyValue($fixture['card']['away']['oscore'], 0);
        }

        require_once('views/card/matchcard.php');
        return;
    }

    // ------------------------------------------------------------------------
    public function lock()
    {
        checkuser();
        $cardid = $_REQUEST['cid'];

        securekey("card$cardid");

        $lockCode = Card::lock($cardid, $_SESSION['club']);

        $fixture = Card::getFixtureByCardId($cardid);

        redirect('card', 'get', "fid=" . $fixture['fixtureID'] . "&x=" . createsecurekey("card" . $fixture['fixtureID']));
    }

    public function search()
    {
        $competitions = Competition::all();
        $clubs = Club::all();
        $teams = Club::getTeamMap();

        require_once('views/card/search.php');
    }
}
