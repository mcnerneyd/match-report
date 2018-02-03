<?php
class Controller_Fixture extends Controller_Hybrid
{
	public function get_index() {

		$data['cards'] = Model_Fixture::getAll();

		$this->template->title = "Fixtures";
		$this->template->content = View::forge('fixture/cards', $data);
	}

	public function action_buttons() {
		$this->template->content = View::forge('fixture/buttons');
	}

	public function post_fine() {
		return $this->response(Model_Card::incompleteCards(60, 7));
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
