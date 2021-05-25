<?php
class Controller_AdminApi extends Controller_RestApi
{
	// --------------------------------------------------------------------------
	public function get_index() {
		return "This is an index";
	}

	public function get_config() {
		header('Access-Control-Allow-Origin: *');

		Log::debug("AdminAPI getConfig");

		$config = array(
			'site'=>array(
				'title'=>Config::get("section.title"),
			),
			'fixtures'=>array(
				'sources'=>Config::get("section.fixtures"),
				'fixes'=>array(
					'competitions'=>Config::get("section.pattern.competition"),
					'clubs'=>Config::get("section.pattern.team"),
				),
			),
			'registration'=>array(
				'upload'=>Config::get("section.automation.allowrequest") ? "secretary" : "admin",
				'restriction_date'=>Config::get("section.date.restrict"),
				'player_id'=>Config::get("section.registration.mandatoryhi", "noselect"),
				'allow_placeholder'=>Config::get("section.registration.placeholders", true),
				'allow_assignment'=>Config::get("section.allowassignment", true),
				'errors' => Config::get("section.registration.blockerrors") ? 'block' : 'warning',
			),	
			'cards'=>array(
				'post_results'=>Config::get("section.result.submit", 'no'),
			),
		);

		return $config;
	}

    public function post_config() {
        Log::info("Post config");
        Config::set("section.title", Input::post("title"));
        Config::set("section.salt", Input::post("salt"));
        Config::set("section.fine", Input::post("fine"));
        Config::set("section.elevation.password", Input::post("elevation_password"));
        Config::set("section.admin.email", Input::post("admin_email"));
        Config::set("section.cc.email", Input::post("cc_email"));
        Config::set("section.strict_comps", Input::post("strict_comps"));
        Config::set("section.automation.allowrequest", Input::post('allow_registration'));
        Config::set("section.allowassignment", Input::post('allow_assignment') == 'on');
        Config::set("section.registration.placeholders", Input::post('allow_placeholders') == 'on');
        Config::set("section.result.submit", Input::post("resultsubmit"));
        Config::set("section.blockerrors", Input::post("block_errors"));
        Config::set("section.registration.mandatoryhi", Input::post("mandatory_hi"));
        //Config::set("section.date.start", Input::post("seasonstart"));
        Config::set("section.date.restrict", Input::post("regrestdate"));
        Config::set("section.fixtures", explode("\r\n", trim(Input::post("fixtures"))));
        Config::set("section.pattern.competition", explode("\r\n", trim(Input::post("fixescompetition"))));
        Config::set("section.pattern.team", explode("\r\n", trim(Input::post("fixesteam"))));
        $configFile = DATAPATH."./sections/".Config::set("section.name");
        Log::info("Saving configuration for $configFile");

        Config::save($configFile, 'section');
				try {
	        Cache::delete_all();
				} catch (Exception $e) {
					Log::warning("Failed to flush cache");
				}

        return new Response("", 200);
    }
}
