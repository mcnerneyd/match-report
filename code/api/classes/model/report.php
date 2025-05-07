<?php

use \Mailjet\Resources;

class Model_Report
{
	public static function scorers()
	{
		return \DB::query("select i.player, c.name club, x.name competition, s.name section, sum(detail) score 
			from incident i 
				left join club c on i.club_id = c.id
				left join matchcard m on i.matchcard_id = m.id
				left join competition x on m.competition_id = x.id
				left join section s on x.section_id = s.id
			where type = 'Scored' 
				and detail > 0
				and i.date > '" . currentSeasonStart() . "'
			group by player, c.name, x.id")->execute();
	}

	public static function emailForgottenPassword(string $email, string $salt)
	{
		$ts = Date::forge()->get_timestamp();
		$hash = md5("$email $ts $salt");

		$link = Uri::create("/User/ForgottenPassword?e=$email&ts=$ts&h=$hash");



		$mj = new \Mailjet\Client('cecdf92235559f2fabba85fd7d119132', '88e0f7a41e5973bc178e3d5656ad3009', true, ['version' => 'v3.1']);
		$body = [
			'Messages' => [
				[
					'From' => [
						'Email' => "lhamcs@gmail.com",
						'Name' => "Leinster Hockey Matchcard System"
					],
					'To' => [
						[
							'Email' => $email
						]
					],
					'Subject' => "Leinster Hockey Cards - Password Reset",
					'TextPart' => "You have requested a password reset. Please open the following url in your browser: $link",
					'HTMLPart' => "<h3>Password Reset Requested</h3>
                        <p>Someone has requested a password reset for this email address</p>
                        <p>To reset the password please click the following link: $link</p>",
					'CustomID' => "Password Reset"
				]
			]
		];
		$response = $mj->post(Resources::$Email, ['body' => $body]);
		if ($response->success()) {
			$ts = Date::forge()->get_timestamp() + (60*60*1000);
			$hash = md5("$email $ts $salt");
			$link = Uri::create("/User/ForgottenPassword?e=$email&ts=$ts&h=$hash");

			Log::info("Password reset email sent to:$email (1h hash=$link)");
		}

	}

}
