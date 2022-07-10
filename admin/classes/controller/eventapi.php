<?php

class Controller_EventApi extends Controller_RestApi
{

		public function get_index() {
			header('Access-Control-Allow-Origin: *');
      echo "[\n";

        Log::info("Events");
			$id = $this->param('id');

			if ($id) {
			}

			$limit = \Input::param('limit', 10);

			$result = Model_Incident::query()
        ->where('jdoc', null)
        ->order_by('id', 'asc')
        ->limit($limit)->get();
      foreach ($result as &$item) {
        $type = strtolower($item['type']);
        $timestamp = Date::create_from_string($item['date'], 'mysql')->get_timestamp();
        $obj = array('id'=>$item['id']+0, 
          't'=>$type, 
          'ts'=>$timestamp);

        if ($item['user'] !== null) {
          $obj['u']=$item['user']['username'];
        }

        switch ($type) {
          case 'played':
            $obj['p'] = $item['player'];
            $obj['c'] = $item['club']['name'];
            break;
        }

        echo json_encode($obj)."\n";
      }

      echo "]\n";
		}
}
