<?php

ini_set("auto_detect_line_endings", true);

class CardController {

    // ------------------------------------------------------------------------
    public function index() {
        checkuser();

        $competitions = array_column(Competition::all(), "name");
        $clubs = array_column(Club::all(), "name");

        if ($_SESSION['club'] && !in_array($_SESSION['club'], $clubs)) {
          $clubs[] = $_SESSION['club'];
        }
        sort($clubs);

        require_once('views/card/index.php');
    }

    // ------------------------------------------------------------------------
    // Get a matchcard based on its id
    public function get() {
        checkuser();

        $id = $_REQUEST['fid'];

        if (!$id) throw new Exception("No fixture specified");

        Log::info("Get card for fixture:$id");

        $fixture = Card::getFixture($id);

        if (!$fixture)
            throw new Exception("No such fixture (fid=$id)");

        if (user('umpire')) {
            if (!isset($fixture['card'])) {
                Log::info("Creating new card for ${fixture['id']}");
                Card::create($fixture);
                $fixture = Card::getFixture($id);
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

        $club = $_SESSION['club'];

        if ($club) {
            if (!isset($fixture['card'])) {
                    Log::debug("Creating new card for ${fixture['id']}");
                    $fixture['cardid'] = Card::create($fixture);
                    $fixture = Card::getFixture($id);
            }

            $data = array(
                    'section' => $fixture['section'],
                    'club' => $club,
                    'date' => date('Ymd', $fixture['date']),
                    'team' => $fixture[$fixture[$club]]['teamnumber'],
                    'groups' => $fixture['groups']);

            $teamcard = $fixture['card'][$fixture[$club]];
            if (!(isset($teamcard['locked']) or isset($teamcard['closed']))) {
                require_once('views/card/fixture.php');
                return;
            }
        }

        if (isset($fixture['card'])) {
            foreach ($fixture['card']['rycards'] as $rycard) {
                    $player = &$fixture['card'][$rycard['side']]['players'][$rycard['player']];
                    if (!isset($player['cards']))
                            $player['cards'] = array();
                    $player['cards'][] = array('type' => $rycard['type'], 'detail' => $rycard['detail']);
            }

            Log::debug("Edit/View matchcard ($club): cardid=" . $fixture['card']['id']);
            $fixture['home']['score'] = emptyValue($fixture['card']['home']['score'], 0);
            $fixture['away']['score'] = emptyValue($fixture['card']['away']['score'], 0);
            $fixture['card']['away']['suggested-score'] = emptyValue($fixture['card']['home']['oscore'], 0);
            $fixture['card']['home']['suggested-score'] = emptyValue($fixture['card']['away']['oscore'], 0);
        }

        require_once('views/card/matchcard.php');
        return;
    }

    // ------------------------------------------------------------------------
    public function lock() {
        checkuser();
        $cardid = $_REQUEST['cid'];

        securekey("card$cardid");

        $lockCode = Card::lock($cardid, $_SESSION['club']);

	      $fixture = Card::getFixtureByCardId($cardid);

        redirect('card', 'get', "fid=" . $fixture['id'] . "&x=" . createsecurekey("card" . $fixture['id']));
    }

    public function search() {
        $competitions = Competition::all();
        $clubs = Club::all();
        $teams = Club::getTeamMap();

        require_once('views/card/search.php');
    }

    public function searchAJAX() {
        if (!(isset($_REQUEST['club']) or isset($_REQUEST['competition'])))
            return "";

        $result = Card::fixtures(isset($_REQUEST['club']) ? $_REQUEST['club'] : null);

        if (isset($_REQUEST['competition'])) {
            $result = array_filter($result, function($item) {
                return $item['competition'] == $_REQUEST['competition'];
            });
        }

        echo json_encode($result);
    }
}
