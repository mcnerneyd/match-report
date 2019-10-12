<?php
class Controller_RestApi extends Controller_Rest
{
	public function after($response) {

		if ($response->status < 400) {
			Log::info($response->body);
		} else {
			Log::warning($response->body);
		}

		return $response;
	}
}
