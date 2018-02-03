<?php class ReportController {

	public function index() {
		require_once("views/report/index.php");
	}

	public function scorers() {
		$page = isset($_GET['page']) ? $_GET['page'] : 0;
		$club = isset($_GET['club']) ? $_GET['club'] : null;
		$competition = isset($_GET['competition']) ? $_GET['competition'] : null;

		$competitions = Competition::all();
		$clubs = Club::all();
		$scorers = Player::scorerReport($page, 10, $club, $competition);

		require_once('views/report/scorers.php');
	}

	public function cards() {
		checkuser('umpire');

		$date = date('Y-m-d');
		//$start = date('Y-m-d', strtotime("$date - 1 week"));
		$start = '2016-09-23';

		$cards = Incident::cards($start, $date);

		require_once("views/report/cards.php");
	}

	public function resultsMismatch() {
		$fixtures = Card::fixtures(null);	
		$mismatches = array();

		foreach ($fixtures as $fixture) {
			if (!isset($fixture['card'])) continue;
			if (!isset($fixture['submitted'])) continue;

			if (($fixture['home']['score'] == $fixture['card']['home']['score'])
					and ($fixture['away']['score'] == $fixture['card']['away']['score'])) continue;

			$mismatches[] = $fixture;
		}

		uasort($mismatches, function ($a, $b) {
			return $a['date'] - $b['date'];
		});

		require_once("views/report/mismatch.php");
	}
}?>
