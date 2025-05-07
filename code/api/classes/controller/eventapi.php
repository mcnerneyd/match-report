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

  public function post_index() {
    $event = Input::json();

    $clubId = \Auth::get('club_id');
    $club   = Model_Club::find_by_id($clubId);
    $user = Session::get('username');
    if ($user) {
        $user = Model_User::find_by_username($user)->id;
    }

    $card = Model_Matchcard::find_by_fixture_id($event['fixture_id']);
    if (!$card) {
      $card = Model_Matchcard::getx($event['fixture_id']);

      if (!$card) return new Response('{"error":"Fixture Not Found:'.$incidentId.'"}', 404);
    }

    $incident               = new Model_Incident();
    $incident->date       = Date::time();
    $incident->player       = Arr::get($event, 'player', null);
    $incident->matchcard_id = $card->id;
    $incident->detail       = Arr::get($event, 'detail', '{}');
    $incident->type         = $event['type'];
    $incident->club_id      = $clubId;
    $incident->resolved     = 0;
    $incident->user_id  		= $user;
    $incident->save();

    return new Response('{"success":"Incident created"}', 204);
  }

  public function delete_index() {
    $incidentId = $this->param('id');
        
    if ($incidentId) {
        $incident = Model_Incident::find($incidentId);

        if ($incident) {
        if (!\Auth::has_access('incident.delete')) {
          return new Response('{"error":"Forbidden"}', 401);
        }
  
        Log::warning("Deleting incident: $incidentId");
        $incident->resolved = 1;
        $incident->save();
    
        return new Response('{"success":"Incident Deleted:'.$incidentId.'"}', 204);
      } else {
        return new Response('{"error":"Incident Not Found:'.$incidentId.'"}', 404);
      }
    } else {
      return new Response('{"error":"Bad request for incident delete"}', 400);
    }
  }
}
