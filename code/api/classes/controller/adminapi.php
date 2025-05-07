<?php
  use \Mailjet\Resources;
class Controller_AdminApi extends Controller_RestApi
{
    // --------------------------------------------------------------------------
    public function get_index()
    {
        return "This is an index";
    }

    public function get_email()
    {
      $mj = new \Mailjet\Client('cecdf92235559f2fabba85fd7d119132','88e0f7a41e5973bc178e3d5656ad3009',true,['version' => 'v3.1']);
      $body = [
        'Messages' => [
          [
            'From' => [
              'Email' => "lhamcs@gmail.com",
              'Name' => "Leinster Hockey Matchcard System"
            ],
            'To' => [
              [
                'Email' => "mcnerneyd@gmail.com",
                'Name' => "David"
              ]
            ],
            'Subject' => "Greetings from Mailjet.",
            'TextPart' => "My first Mailjet email",
            'HTMLPart' => "<h3>Dear passenger 1, welcome to <a href='https://www.mailjet.com/'>Mailjet</a>!</h3><br />May the delivery force be with you!",
            'CustomID' => "AppGettingStartedTest"
          ]
        ]
      ];
      $response = $mj->post(Resources::$Email, ['body' => $body]);
      $response->success() && var_dump($response->getData());
    }

    public function get_structure()
    {
        if (!Auth::has_access("configuration.edit")) {
            throw new HttpNoAccessException;
        }

        foreach (Model_Club::find('all') as $c) $c->log();

        foreach (Model_Competition::find('all') as $x) $x->log();

        foreach (Model_User::find('all') as $u) $u->log();
    }

    public function delete_test()
    {   // Reset the test section
        Log::debug("Deleting test section");

        $s = Model_Section::find_by_name('test');
        if ($s) {
            //$s->delete();
        }
        $u = Model_User::find_by_username('admin@test');
        if ($u) {
            //$u->delete();
        }
        foreach (array('Aardvarks', 'Bears', 'Camels') as $club) {
            $c = Model_Club::find_by_name($club);
            if ($c) {
                //$c->delete();
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

    public function get_trigger() {
        $frame = Input::param("frame");
        Log::debug("trigger ".$frame);

        switch ($frame) {
            case 'fivemins':
                Model_Fixture::refresh();
                break;
            default:
                return new Response("unknown frame: $frame", 400);
        }

        return new Response("triggered", 200);
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

    public function get_import() {
        self::import("data/logs/out.log");
    }

    public static function import(string $file) {
        $clubIdMap = array();
        $competitionIdMap = array();
        $fixtureIdMap = array();

        foreach (file($file) as $line) {
            $matches = array();

            if (preg_match("/^[0-9T:-]+ \[I\] \+CLUB \[(?<name>.*)\] #(?<id>[0-9]+)\/.*/", $line, $matches)) {
                $club = Model_Club::find_by_name($matches["name"]);
                if ($club == null) {
                    $club = new Model_Club();
                    $club->name = $matches["name"];
                    $club->save();
                    $club->log();
                }
                $clubIdMap[$matches["id"]] = $club->id;
            } else if (preg_match("/^[0-9T:-]+ \[I\] \+COMPETITION (?<format>cup|league) \[(?<name>.*)\/(?<section>.*)\](?: {(?<props>.*)})? #(?<id>[0-9]+)\/.*/", $line, $matches)) {
                $section = Model_Section::find_by_name($matches["section"]);

                if ($section == null) {
                    $section = new Model_Section();
                    $section->name = $matches["section"];
                    $section->save();
                }

                $competition = Model_Competition::getCompetition($section, $matches["name"]);
                if ($competition == null) {
                    echo "  Save\n";
                    $competition = new Model_Competition();
                    $competition->section = $section;
                    $competition->name = $matches["name"];
                    $competition->format = $matches["format"];

                    $props = $matches["props"];
                    if ($props != null) {
                        $props = explode(";", $props);
                        if (count($props)>0 && $props[0] != '') $competition->sequence = $props[0];
                        if (count($props)>1 && $props[1] != '') $competition->teamsize = $props[1];
                        if (count($props)>2 && $props[2] != '') $competition->teamstars = $props[2];
                        if (count($props)>3 && $props[3] != '') $competition->groups = $props[3];
                    }

                    $competition->save();
                    $competition->log();
                }
                $competitionIdMap[$matches["id"]] = $competition->id;
            } else if (preg_match("/^(?<date>[0-9T:-]+) \[I\] \+USER (?:\[(?<username>.*?)(?:<(?<email>.*)>)?\])?(?:@(?<club>.+?)\/(?<section>.+?))?(?:=(?<role>.*))? {(?<password>.*?)} #(?<id>[0-9]+)\/.*/", $line, $matches)) {
                $section = Model_Section::find_by_name($matches["section"]);

                if ($section == null && $matches["section"]) {
                    $section = new Model_Section();
                    $section->name = $matches["section"];
                    $section->save();
                }

                $user = new Model_User();
                $user->username = $matches["username"];
                $user->password = $matches["password"];
                $user->section = $section;

                switch ($matches['role']) {
                    case "admin":
                        $user->group = 99;
                        $user->email = $matches['username'];
                        break;
                    case "secretary":
                        $user->group = 25;
                        $user->email = $matches['username'];
                        $user->club = Model_Club::find_by_name($matches['club']);
                        break;
                    case "umpire":
                        $user->group = 2;
                        $user->email = $matches['email'];
                        $user->section = null;
                        break;
                    case null:
                        $user->group = 1;
                        $user->username = $matches['club'];
                        if ($section != null) $user->username .= " ({$section->name})";
                        $user->club = Model_Club::find_by_name($matches['club']);
                        break;
                }

                if (Model_User::find_by_username($user->username) == null) {
                    $user->save();
                    $user->log();
                }

            } else if (preg_match("/^(?<date>[0-9T:-]+) \[I\] CARD (?:\?(?<mid>[0-9]+)) #(?<id>[0-9]+).*/", $line, $matches)) {
                
                $card = Model_Matchcard::query()->where("fixture_id", $matches['id'])->get_one();

                if ($card == null) {
                    $card = new Model_Matchcard();
                    $card->fixture_id = $matches['id'];
                    $card->description = "imported";
                    $card->open = 0;
                    //$card->log();
                }

                $card->date = $matches['date'];
                $card->save();

                if ($matches['mid'] !== null) $fixtureIdMap[$matches['mid']] = $card->id;
            } else if (preg_match("/^(?<date>[0-9T:-]+) \[I\] PLAYED \[(?<player>.*)\/(?<club>.*)\] #(?<id>[0-9]+).*/", $line, $matches)) {
                $fixtureId = $fixtureIdMap[$matches['id']];
                $cardId = Model_Matchcard::query()->where("fixture_id", $matches['id'])->get_one();
                if ($cardId != null) $cardId = $cardId['id'];

                $incident = Model_Incident::query()
                    ->where("type", "PLAYED")
                    ->where("player", $matches['player'])
                    ->where("matchcard_id", $cardId)
                    ->get_one();

                if ($incident == null) {
                    $club = Model_Club::find_by_id($matches['club']);

                    $incident = new Model_Incident();
                    $incident->date = $matches['date'];
                    $incident->type = 'PLAYED';
                    $incident->matchcard_id = $cardId;
                    $incident->club = $club;
                    $incident->resolved = 0;
                    $incident->save();
                }
            }
        }
    }
}











