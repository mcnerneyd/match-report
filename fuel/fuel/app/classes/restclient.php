<?php
class RestClient
{
	private $url;

	public function __construct($url, $username, $password) {
		$this->url = $url;
	}

	public function get($target) {
		$process = curl_init($url.$target);
		curl_setopt($process, CURLOPT_RETURNTRANSFER, 1);
		$result = curl_exec($process);
		curl_close($process);

		print_r($result);

		return $result;
	}
}

$bar = new RestClient("http://cards.leinsterhockey.ie/svc");

$bar->get("?s=tst&c=Bray");
