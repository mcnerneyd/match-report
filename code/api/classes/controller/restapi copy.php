<?php
class Controller_Base extends Controller_Template
{
	protected $rest_format = 'json';

	/*public function after($response) {

		if ($response->status < 400) {
			Log::info($response->body);
		} else {
			Log::warning($response->body);
		}

		return $response;
	}*/

	public function simplify($arrayTree) {

		array_walk_recursive($arrayTree, function(&$item, $key) {
			if ($item instanceof \Date) {
				$item = $item->format();
			}
		});

		//self::stripnulls($arrayTree);

		return $arrayTree;
	}

/*	private static function stripnulls(&$array) {

		foreach ($array as $k=>$v) {
			if ($v === null) {
				unset($array[$k]);
				continue;
			}

			if (is_object($v)) {
				self::stripnulls((array)$v);
			}

			if (is_array($v)) {
				self::stripnulls($v);
			}
		}
	}*/
}
