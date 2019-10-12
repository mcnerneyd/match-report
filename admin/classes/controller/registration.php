<?php
class Controller_Registration extends Controller_Template
{
	public function action_index() {
		if (!Auth::has_access("registration.view")) {
			throw new HttpNoAccessException;
		}

		if (Auth::has_access("registration.impersonate")) {
			$club = Input::param("c");
		}

		if (!isset($club)) {
			$username = Session::get("username");
			$user = Model_User::find_by_username($username);
			$club = $user['club']['name'];
		}

		Log::info("Requesting registration for $club");

		$registrations = Model_Registration::find_all($club);
		
		$this->template->title = "Registrations";
		$this->template->content = View::forge('registration/index', array('club'=>$club,
			'clubs'=>Model_Club::find('all'),
			'registrations'=>$registrations));
	}

	public function action_registration() {

		if (!Auth::has_access("registration.view")) {
			throw new HttpNoAccessException;
		}

		$user = Model_User::find_by_username(Session::get("username"));
		$club = $user['club']['name'];

		if (\Auth::has_access("registration.impersonate")) {
			$club = \Input::param('c', $club);
		}

		if (!$club) {
			return new Response("No club specified for registration", 404);
		}

		$file = Input::param('f', null);

		if ($file != null) {
			File::download(Model_Registration::getRoot($club, $file),
				 null, "text/csv");
		}

		$date = Input::param('d', null);
		if (!$date) {
			$date = Date::time();
		} else {
			$date = Date::create_from_string($date, "%Y-%m-%d");
		}

		$thurs = strtotime("first thursday of " . $date->format("%B %Y"));
		$thurs = strtotime("+1 day", $thurs);
		if ($thurs > $date->get_timestamp()) {
			$thurs = Date::forge(strtotime("-1 month", $date->get_timestamp()));
			$thurs = strtotime("first thursday of " . $thurs->format("%B %Y"));
			$thurs = strtotime("+1 day", $thurs);
		}

		Model_Registration::flush($club);
		$registration = Model_Registration::find_between_dates($club, $thurs, $date->get_timestamp());

		$this->template->title = "Registrations";
		$this->template->content = View::forge('registration/list', array(
			'registration'=>$registration,
			//'history'=>$history,
			'club'=>$club,
			'all'=>Model_Registration::find_before_date($club, Date::forge()->get_timestamp()),
			'ts'=>$date, 
			'base'=>Date::forge($thurs)));
	}

	public function action_info() {

		if (!Auth::has_access("registration.view")) {
			throw new HttpNoAccessException;
		}

		$userObj = Model_User::find_by_username(Session::get('username'));
		if ($userObj == null) {
			Log::error("No such user: ".Session::get('username'));
			return;
		}

		$club = $userObj->club['name'];

		$clubUser = Model_User::find('first', array(
				'where'=>array(
					array('username','=',$club),
					array('role','=','user')	
				)
			));

		$this->template->title = "Club Info";
		$this->template->content = View::forge('registration/info',
			array('user'=>$clubUser));
	}

}
