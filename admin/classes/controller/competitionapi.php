<?php

class Controller_CompetitionApi extends Controller_RestApi
{
	public function get_index() {
		$id = $this->param('id');

		if ($id) {
			$competition = Model_Competition::find($id);
			if (!$competition) {
			}

			return array('data' => $this->simplify($competition));
		}

    $result = Model_Competition::find('all');
		foreach ($result as &$item) $item = $this->simplify($item);

		return array('data'=>$result);
	}
}
