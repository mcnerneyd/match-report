<?php
class Event {
	public static function log($obj) {
		$obj['d'] = time();
		$str = json_encode($obj);
	}

	private static function write($str) {
	try {
		$filename = sitepath()."/logs/event.log"
		$dir = dirname($filename);

		if (!file_exists($dir)) {
			mkdir($dir, 0777, true);
		}

		file_put_contents($filename, $str."\n", FILE_APPEND);
	} catch (Exception $e) {
		echo "Log Failure: ".$e->getMessage();
	}

	return true;
	}
}
