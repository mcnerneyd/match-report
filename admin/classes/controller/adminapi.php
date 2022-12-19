<?php
class Controller_AdminApi extends Controller_RestApi
{
    // --------------------------------------------------------------------------
    public function get_index()
    {
        return "This is an index";
    }

    public function delete_test()
    {   // Reset the test section
        Log::debug("Deleting test section");

        $s = Model_Section::find_by_name('test');
        if ($s) {
            $s->delete();
        }
        $u = Model_User::find_by_username('admin@test');
        if ($u) {
            $u->delete();
        }
        foreach (array('Aardvarks', 'Bears', 'Camels') as $club) {
            $c = Model_Club::find_by_name($club);
            if ($c) {
                $c->delete();
            }
        }

        self::deleteDir(DATAPATH."/sections/test/");

        $s = new Model_Section();
        $s['name'] = 'test';
        $s->save();

        $u = new Model_User();
        $u['email'] = 'admin@test';
        $u['username'] = 'admin@test';
        $u['password'] = \Auth::hash_password('password');
        $u['group'] = 99;
        $u->save();
    }
  
    private static function deleteDir($path)
    {
        if (empty($path)) {
            return false;
        }
        if (!file_exists($path)) {
            return false;
        }
        $class_func = array(__CLASS__, __FUNCTION__);
        return is_file($path) ?
            @unlink($path) :
            array_map($class_func, glob($path.'/*')) == @rmdir($path);
    }

    public function get_config()
    {
        header('Access-Control-Allow-Origin: *');

        Log::debug("AdminAPI getConfig");

        $config = array(
            'site'=>array(
                'title'=>Config::get("$section.title"),
            ),
            'fixtures'=>array(
                'sources'=>Config::get("$section.fixtures"),
                'fixes'=>array(
                    'competitions'=>Config::get("$section.pattern.competition"),
                    'clubs'=>Config::get("$section.pattern.team"),
                ),
            ),
            'registration'=>array(
                'upload'=>Config::get("$section.automation.allowrequest") ? "secretary" : "admin",
                'restriction_date'=>Config::get("$section.date.restrict"),
                'player_id'=>Config::get("$section.registration.mandatoryhi", "noselect"),
                'allow_placeholder'=>Config::get("$section.registration.placeholders", true),
                'allow_assignment'=>Config::get("$section.allowassignment", true),
                'errors' => Config::get("$section.registration.blockerrors") ? 'block' : 'warning',
            ),
            'cards'=>array(
                'post_results'=>Config::get("$section.result.submit", 'no'),
                'results_button'=>Config::get("$section.result.button", 'yes'),
            ),
        );

        return $config;
    }

    public function post_config()
    {
        if (!Auth::has_access("configuration.edit")) {
            throw new HttpNoAccessException;
        }

        $section = Input::post('section', null);

        if ($section) {
            $configFile = ensurePath(DATAPATH."./sections/$section/", "config.json");

            Log::info("Post config");
            Config::set("$section.title", Input::post("title"));
            Config::set("$section.salt", Input::post("salt"));
            Config::set("$section.fine", Input::post("fine"));
            Config::set("$section.elevation.password", Input::post("elevation_password"));
            Config::set("$section.admin.email", Input::post("admin_email"));
            Config::set("$section.cc.email", Input::post("cc_email"));
            Config::set("$section.strict_comps", Input::post("strict_comps"));
            Config::set("$section.automation.allowrequest", Input::post('allow_registration'));
            Config::set("$section.allowassignment", Input::post('allow_assignment') == 'on');
            Config::set("$section.registration.placeholders", Input::post('allow_placeholders') == 'on');
            Config::set("$section.result.submit", Input::post("resultsubmit"));
            Config::set("$section.result.button", Input::post("resultbutton") == 'on');
            Config::set("$section.blockerrors", Input::post("block_errors"));
            Config::set("$section.registration.mandatoryhi", Input::post("mandatory_hi"));
            //Config::set("$section.date.start", Input::post("seasonstart"));
            Config::set("$section.date.restrict", Input::post("regrestdate"));
            Config::set("$section.fixtures", explode("\r\n", trim(Input::post("fixtures"))));
            Config::set("$section.pattern.competition", explode("\r\n", trim(Input::post("fixescompetition"))));
            Config::set("$section.pattern.team", explode("\r\n", trim(Input::post("fixesteam"))));

            Log::info("Saving configuration for $configFile");

            Config::save($configFile, $section);

            try {
                Cache::delete_all();
            } catch (Exception $e) {
                Log::warning("Failed to flush cache");
            }
        }

        return new Response("", 200);
    }

    public function get_export()
    {
        if (!Auth::has_access("data.export")) {
            throw new HttpNoAccessException;
        }

        $result = array();

        foreach (Model_Section::find('all') as $section) {
            $result[] = array('type'=>'section',
          'name'=>$section['name']);
        }

        foreach (Model_Club::find('all') as $club) {
            $result[] = array('type'=>'club',
          'name'=>$club['name'],
          'code'=>$club['code']);
        }

        foreach (Model_Competition::find('all') as $competition) {
            $result[] = array('type'=>'competition',
          'section'=>$competition->section['name'],
          'name'=>$competition['name'],
          'code'=>$competition['code'],
          'groups'=>$competition['groups'],
          'format'=>$competition['format'],
          'teamsize'=>$competition['teamsize'],
          'teamstars'=>$competition['teamstars'],
          'sequence'=>$competition['sequence']);
        }

        foreach (Model_User::find('all') as $user) {
            $u = array('type'=>'user',
          'username'=>$user['username'],
          'password'=>$user['password'],
          'email'=>$user['email'],
          'group'=>$user['group']);

            if ($user->section) {
                $u['section'] = $user->section['name'];
            }

            if ($user->club) {
                $u['club'] = $user->club['name'];
            }

            $result[] = $u;
        }

        return $result;
    }
}
