<?php
class Controller_Registration extends Controller_Template
{
	public function action_index() {
		if (!Auth::has_access("registration.view")) {
			throw new HttpNoAccessException;
		}

    $section = Input::param('s');
    if ($section) loadSectionConfig($section);

		if (Auth::has_access("registration.impersonate")) {
			$club = Input::param("c");
		}

		if (!isset($club)) {
			$username = Session::get("username");
			$user = Model_User::find_by_username($username);
      if ($user['club']) {
  			$club = $user['club']['name'];
      }
		}

		Log::info("Requesting registration for $section/$club");

		$registrations = $club && $section ? Model_Registration::find_all($section, $club) : array();
		
		$this->template->title = "Registrations";
		$this->template->content = View::forge('registration/index', array('club'=>$club,
      'section'=>$section,
			'clubs'=>Model_Club::find('all'),
      'sections'=>Model_Section::find('all'),
			'registrations'=>$registrations));
	}

	public function action_registration() {

		if (!Auth::has_access("registration.view")) {
			throw new HttpNoAccessException;
		}

    $section = \Input::param('s', null);

		$user = Model_User::find_by_username(Session::get("username"));
    $club = null;
    if ($user and $user['club']) {
      $club = $user['club']['name'];
    }
    
		if (\Auth::has_access("registration.impersonate")) {
      $club = \Input::param('c', null);
		}

		if (!$club) {
			return new Response("No club specified for registration", 404);
		}

		$file = Input::param('f', null);

		if ($file != null) {
			$filename = Model_Registration::getRoot($section, $club, $file);
			Log::info("Downloading $filename");
			File::download($filename, null, "text/csv");
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

		Model_Registration::flush($section, $club);
		$registration = Model_Registration::find_between_dates($section, $club, $thurs, $date->get_timestamp());
		$this->template->title = "Registrations";
		$this->template->content = View::forge('registration/list', array(
			'registration'=>$registration,
			//'history'=>$history,
			'club'=>$club,
			'section'=>$section,
			'all'=>Model_Registration::find_before_date($section, $club, Date::forge()->get_timestamp()),
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

		if ($userObj->club === null) {
			Log::error("User does not have a club");
			return;
		}

		$club = $userObj->club['name'];

		Log::info("Request info for $club");

		$clubUsers = Model_User::find('all', array(
				'where'=>array(
					array('club_id','=',$userObj->club['id']),
					array('group','=',1)	
				)
			));

		if ($userObj->section) {
			$sectionName = $userObj->section['name'];
			$clubUsers = array_filter($clubUsers, function($a) use ($sectionName) { return $a->section['name'] === $sectionName; });
		}

		$this->template->title = "Club Info";
		$this->template->content = View::forge('registration/info',
			array('users'=>$clubUsers));
	}

}
