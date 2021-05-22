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
				'title'=>Config::get("config.title"),
			),
			'fixtures'=>array(
				'sources'=>Config::get("config.fixtures"),
				'fixes'=>array(
					'competitions'=>Config::get("config.pattern.competition"),
					'clubs'=>Config::get("config.pattern.team"),
				),
			),
			'registration'=>array(
				'upload'=>Config::get("config.automation.allowrequest") ? "secretary" : "admin",
				'restriction_date'=>Config::get("config.date.restrict"),
				'player_id'=>Config::get("config.registration.mandatoryhi", "noselect"),
				'allow_placeholder'=>Config::get("config.registration.placeholders", true),
				'allow_assignment'=>Config::get("config.allowassignment", true),
				'errors' => Config::get("config.registration.blockerrors") ? 'block' : 'warning',
			),	
			'cards'=>array(
				'post_results'=>Config::get("config.result.submit", 'no'),
			),
		);

		return $config;
	}
}
