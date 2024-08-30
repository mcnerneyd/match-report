<?php

class Controller_Registration extends Controller_Template
{
    public function action_index()
    {
        if (!Auth::has_access("registration.view")) {
            throw new HttpNoAccessException();
        }

        $section = Input::param('s');
        Log::info("Getting configuration for: section=$section");
        if ($section) {
            loadSectionConfig($section);
            $section = Model_Section::find_by_name($section);
        }

        $club = null;
        if (Auth::has_access("registration.impersonate")) {
            $club = Input::param("c");
            $club = Model_Club::find_by_name($club);
        }

        $username = Session::get("username");
        $user = Model_User::find_by_username($username);

        $clubfixed = false;
        if ($user->club) {
            $club =  $user->club;
            $clubfixed = true;
        }

        $sectionfixed = false;
        if ($user->section) {
            $section = $user->section;
            $sectionfixed = true;
        }

        Log::info("Requesting registration for $section/$club");

        $registrations = $club && $section ? Model_Registration::find_all($section, $club) : array();

        $sectionName = $section != null ? $section->name : null;
        $clubName = $club != null ? $club->name : null;

        $this->template->title = "Registrations";
        $this->template->content = View::forge('registration/index', ['club'=>$clubName,
			'section'=>$sectionName,
			'clubs'=>Model_Club::find('all'),
            'clubfixed'=>$clubfixed,
            'sectionfixed'=>$sectionfixed,
			'sections'=>Model_Section::find('all'),
			'registrations'=>$registrations]);
    }

    public function action_registration()
    {

        if (!Auth::has_access("registration.view")) {
            throw new HttpNoAccessException();
        }

        $section = \Input::param('s', null);
        $section = Model_Section::find_by_name($section);

        $user = Model_User::find_by_username(Session::get("username"));
        $club = null;
        if ($user and $user['club']) {
            $club = $user['club'];
        }

        if (\Auth::has_access("registration.impersonate")) {
            $club = \Input::param('c', null);
            $club = Model_Club::find_by_name($club);
        }

        if (!$club) {
            return new Response("No club specified for registration", 404);
        }

        $file = Input::param('f', null);

        if ($file != null) {
            $filename = Model_Registration::getRoot($section, $club, $file);
            Log::info("Downloading '$filename' for $section/$club");
            File::download($filename, null, "text/csv");
        }

        $date = Input::param('d', null);
        if (!$date) {
            $date = Date::time()->format("%Y-%m-%d");
        }
        
        $date = Date::create_from_string($date, "%Y-%m-%d");

        $thurs = strtotime("first thursday of " . $date->format("%B %Y"));
        $thurs = strtotime("+1 day", $thurs);
        if ($thurs > $date->get_timestamp()) {
            $thurs = Date::forge(strtotime("-1 month", $date->get_timestamp()));
            $thurs = strtotime("first thursday of " . $thurs->format("%B %Y"));
            $thurs = strtotime("+1 day", $thurs);
        }

        $thursDate = Date::forge($thurs);
        $info = array();

        Model_Registration::flush($section, $club);
        $registration = Model_Registration::find_between_dates($section, $club, $thurs, $date->get_timestamp(), $info);
        $this->template->title = "Registrations";
        $this->template->content = View::forge('registration/list', array(
            'info'=> $info,
            'registration'=>$registration,
            //'history'=>$history,
            'club'=>$club->name,
            'section'=>$section->name,
            'all'=>Model_Registration::find_before_date($section, $club, Date::forge()->get_timestamp()),
            'ts'=>$date,
            'base'=>Date::forge($thurs)));
    }

    public function action_info()
    {

        if (!Auth::has_access("registration.view")) {
            throw new HttpNoAccessException();
        }

        $userObj = Model_User::find_by_username(Session::get('username'));
        if ($userObj == null) {
            throw new UserException("No such user: ".Session::get('username'));
        }

        if ($userObj->club == null) {
            throw new UserException("User does not have a club");
        }

        $club = $userObj->club['name'];

        Log::info("Request info for $club");

        $clubUsers = Model_User::find('all', [ 'where'=>[
                    ['club_id','=',$userObj->club['id']],
                    ['group','=',1]
                ]]);

        if ($userObj->section) {
            $sectionName = $userObj->section['name'];
            $clubUsers = array_filter($clubUsers, function ($a) use ($sectionName) {
                if ($a->section == null) {
                    return true;
                } else {
                    return $sectionName === null or $a->section['name'] === $sectionName;
                }
            });
        }

        foreach ($clubUsers as $clubUser) {
            if ($clubUser->section) {
                echo "<!-- ".print_r($clubUser->club->getTeamSizes($clubUser->section['name']), true). " -->";
            }
        }

        $this->template->title = "Club Info";
        $this->template->content = View::forge('registration/info', ['users'=>$clubUsers]);
    }

}
