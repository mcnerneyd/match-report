<?php
class Controller_Fixture extends Controller_Hybrid
{
	public function get_index() {

		$flush = Input::param("flush", false);
		if ($flush === 'true') $flush = true;

		$fixtures = Model_Fixture::getAll($flush);
		
		foreach ($fixtures as &$fixture) {
			echo "<!-- ".print_r($fixture, true)." -->";
			$fixture['show'] = true;
		}

		foreach (Model_Card::query()->where('open','<=','-50')->get() as $card) {
			$fixtures[$card['fixture_id']]['show'] = false;
		}

		echo "<!-- Fixtures:\n".print_r($fixtures, true)."\n-->";

		$this->template->title = "Fixtures";
		$this->template->content = View::forge('fixture/index', array('cards'=>$fixtures));
	}

	public function action_buttons() {
		$this->template->content = View::forge('fixture/buttons');
	}

	public function post_fine() {
		return $this->response(Model_Card::incompleteCards(60, 7));
	}

	public function put_index() {
		$fixtureId = $this->param('id');

		$show = Input::param("show", null);

		if ($show != null) {
			$card = Model_Card::find_by_fixture($fixtureId, true);
			echo "Card:".print_r($card,true);
			$card = Model_Card::find_by_id($card['id']);
			if ($card['open'] > 1) return;
			if ($show === 'true') $card['open'] = 0;
			else $card['open'] = -50;
			$card->save();
		}

		echo "Fixture ID:$fixtureId";
	}

	public function action_repair() {
		echo "<pre>Repairing Fixtures...\n";

		foreach (DB::query("select id from matchcard where fixture_id is null and date > '2018-08-01'")->execute() as $row) {
			echo "Processing matchcard: ${row['id']}\n";
			$card = Model_Card::find($row['id']);
			echo $card['competition']['name'].": ";
			echo $card['home']['club']['name']." ".$card['home']['team']['team']." -v- ";
			echo $card['away']['club']['name']." ".$card['away']['team']['team'];

			$fixture = Model_Fixture::match($card['competition']['name'], 
				$card['home']['club']['name']." ".$card['home']['team']['team'],
				$card['away']['club']['name']." ".$card['away']['team']['team']);

			echo " = ${fixture['fixtureID']}\n";

			$card['fixture_id'] = $fixture['fixtureID'];
			$card->save();
		}

		echo "\nDuplicate cards\n";
		foreach (DB::query("select fixture_id from matchcard group by fixture_id having count(*) > 1")->execute() as $row) {
			echo "${row['fixture_id']}\n";
		}

		echo "</pre>";

		return new Response("", 200);
	}

	public function get_summary() {

		$result = array();

		$codes = array();
		foreach (Model_Club::find('all') as $club) $codes[$club['name']] = $club['code'];

		foreach (Model_Fixture::getAll() as $fixture) {
			$x = $fixture['competition'];

			if (0 !== strpos($x, 'Division')) continue;

			if (!isset($result[$x])) $result[$x] = array('teams'=>array(), 'fixtures'=>array());

			$teams =& $result[$x]['teams'];

			if (!isset($codes[$fixture['home_club']])) continue;
			if (!isset($codes[$fixture['away_club']])) continue;

			$h = $codes[$fixture['home_club']];
			$a = $codes[$fixture['away_club']];

			$teams[$h] = $fixture['home_club'];
			$teams[$a] = $fixture['away_club'];
	
			$result[$x]['fixtures'][$h.$a] = $fixture;
		}

		$data['fixtures'] = $result;

		$this->template->content = View::forge('fixture/summary', $data);
	}
}
