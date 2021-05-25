<?php
class RestClient
{
	private $url;

	public function __construct($url, $username, $password) {
		$this->url = $url;
	}

	public function get($target) {
		$process = curl_init($this->url.$target);
		curl_setopt($process, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($process, CURLOPT_FOLLOWLOCATION, true);
		$result = curl_exec($process);
		curl_close($process);

		return json_decode($result);
	}
}

echo "HERE";

$bar = new RestClient("http://cards.leinsterhockey.ie/svc/index.php");

var_dump($bar->get("?s=tst&c=Bray"));
