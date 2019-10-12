<?php
class Controller_Test extends Controller_Template
{
	public function before() {
		Log::info("Test Controller: Site is ".\Session::get('site'));
		if (\Session::get('site', "") !== 'test') throw new HttpNoAccessException;

		parent::before();
	}

	// --------------------------------------------------------------------------
	public function action_exception() {
		throw new Exception("Just an exception");
	}

	public function action_index() {
		$data = array();
		$data['clubs'] = DB::query("select id, name from club")->execute();

		$fixtureId = Input::param('fixture_id', null);
		if ($fixtureId !== null) {
			$data['cards'] = DB::query("select id from matchcard where fixture_id = $fixtureId")->execute();
		}

		$this->template->title = "Test Page";
		$this->template->content = View::forge('test/index', $data);
	}

	public function action_incident() {
		$type = \Input::param('type');
		$player = \Input::param('player');
		$cardId = \Input::param('card_id');
		$clubId = \Input::param('club_id');
		$detail = \Input::param('detail');

		Log::debug("Add incident ($type) to $cardId: $player/$clubId=$detail");

		Session::set_flash('msg', 'Incident Created');

		Response::redirect('test');
	}

	public function action_reset() {
		$cardId = \Input::param('card_id');
		Log::debug("Reset card $cardId");

		Session::set_flash('msg', 'Card Reset');

		Response::redirect('test');
	}
}
