<?php

class Model_Incident extends \Orm\Model
{
    protected static $_properties = array(
        'id',
        'date',
        'type',	// 	enum('Played', 'Red Card', 'Yellow Card', 'Ineligible', 'Scored', 'Missing', 'Postponed', 'Other', 'Locked', 'Reversed', 'Signed', 'Number', 'Late', 'Registered')
        'player',
        'club_id',
        'matchcard_id',
        'detail',
        'resolved',
        'user_id',
    );

    protected static $_belongs_to = array('club',

        'card'=>array(
            'key_to'=>'id',
            'key_from'=>'matchcard_id',
            'model_to'=>'Model_Matchcard',
        ),

    );

    protected static $_has_one = array(
        'user'=>array(
            'key_to'=>'id',
            'key_from'=>'user_id',
            'model_to'=>'Model_User',
        ),
    );

    public function delete($cascade = null, $use_transaction = false)
    {
        $this->resolved = 1;
        return $this->save(false);
    }

    protected static $_soft_delete = array(
        'deleted_field' => 'resolved',
    );

    protected static $_table_name = 'incident';

    public static function clearCards($cardId, $player, $clubId)
    {
        Log::debug("Deleting cards from $player on card $cardId");

        $q = \DB::delete('incident')
            ->where('matchcard_id', '=', $cardId)
            ->where('player', '=', $player)
            ->where('club_id', '=', $clubId)
            ->where('type', 'in', array('Red Card', 'Yellow Card'));
        Log::debug("db:".$q->compile());
        $q->execute();
    }

    public static function log($type, $fixtureId, $message) {
        try {
            $logpath = DATAPATH."/logs";
            $logfile = "incidents.log";
            if (!File::exists($logpath."/".$logfile)) {
                File::create($logpath,$logfile);
            }        
            $ts = Date::forge()->format("%y-%m-%dT%h%m:%sZ");

            File::append($logpath, $logfile, "$ts $type #$fixtureId $message\n");
        } catch(Exception $e) {
            Log::error("Failed to write incident".$e->getMessage());
        }
    }

    public static function addIncident($cardId, $club, $player, $type, $detail = null) {
        $clubId = $club['id'];
        $userId = \Auth::get('id');

        return self::addIncidentRaw($cardId, $clubId, $player, $type, $userId, $detail);
    }

    public static function addIncidentRaw($cardId, $clubId, $player, $type, $userId, $detail = null)
    {
        $incident = null;

        Log::debug("Adding incident: $cardId, $player, $type=$detail club=$clubId");

        foreach (Model_Incident::find('all', array(
                'where' => array(
                        array('matchcard_id', $cardId),
                        array('player', $player),
                        array('type', $type),
                        array('club_id', $clubId)))) as $incidentX) {
            $incident = $incidentX;
            break;
        }

        $new = false;
        if ($incident == null) {
            $incident = new Model_Incident();
            $incident['date'] = Date::time();
            $incident['player'] = $player;
            $incident['type'] = $type;
            $incident['club_id'] = $clubId;
            $incident['matchcard_id'] = $cardId;
            $incident['user_id'] = $userId;
            $new = true;
        }

        $incident['resolved'] = 0;
        if ($detail !== null) {
            Log::debug("Setting $detail");
            $incident['detail'] = $detail;
        }
        $incident->save();

        return $new;
    }
}
