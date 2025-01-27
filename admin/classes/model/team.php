<?php

class Model_Team extends \Orm\Model
{
    public function getName()
    {
        return $this->club->name ." ". $this->name;
    }

    protected static $_properties = array(
        'id',
        'name',
        'club_id',
        'section_id'
    );

    protected static $_belongs_to = array('club'=> array(
        ),
        'section'=>array(
        ));

    protected static $_many_many = array(
        'competition' => array(
                'table_through' => 'team__competition',
                'conditions' => array(
                    'order_by' => array('sequence'=>'ASC')),
                ));

    protected static $_table_name = 'team';

    public static function find_by_name($name, Model_Section $section)
    {
        if ($section == null || $name == null) {
            \Log::error("No name/section provided");
            return null;
        }

        $matches = array();
        if (preg_match("/(.*) ([0-9]+)/i", $name, $matches)) {
            $rows = DB::query("SELECT t.id FROM team t JOIN club c ON t.club_id = c.id
				WHERE c.name = :clubname AND t.name = :teamname AND t.section_id = {$section['id']}")
                ->bind('clubname', $matches[1])
                ->bind('teamname', $matches[2])
                ->execute();

            foreach ($rows as $row) {
                return Model_Team::find($row['id']);
            }
        }

        \Log::warning("Unable to locate team: $name/$section");

        return null;
    }

    public static function parse(string $str)
    {
        $config = Config::get("section.pattern.team", []);

        $patterns = array();
        $replacements = array();
        foreach ($config as $pattern) {
            if (trim($pattern) == '') {
                break;
            }
            $parts = explode($pattern[0], $pattern);
            if (count($parts) < 3) {
                continue;
            }
            $patterns[] = "/".str_replace("/", "", $parts[1])."/i";
            $replacements[] = $parts[2];
        }

        $str = preg_replace($patterns, $replacements, trim($str));

        if (strpos($str, '!') !== false) {
            return false;
        }

        $matches = array();
        $result = array('raw'=>$str);

        if (preg_match('/^([a-z,\\/\' ]*[a-z])(?:\s+([0-9]+)[^0-9]*)?$/i', trim($str), $matches)) {
            if (count($matches) > 2) {
                $result['team'] = $matches[2];
            } else {
                $result['team'] = 1;
            }

            $result['club'] = $matches[1];
            $result['name'] = $result['club'] .' '. $result['team'];
        } else {
            $result['club'] = $str;
        }

        return $result;
    }

    public static function findTeam($club, $team)
    {
        $result = Model_Team::query()
            ->related('club')
            ->where('name', '=', $team)
            ->where('club.name', '=', $club)
            ->get();

        return reset($result);
    }

    public static function lastGame($teamName, $section) : ?Model_Matchcard
    {
        $team = self::find_by_name($teamName, $section);
        $teamId = $team->id;
        $rows = DB::query("SELECT x.id, x.date FROM (SELECT m.id, m.date FROM matchcard m WHERE home_id = $teamId
			UNION SELECT m.id, m.date FROM matchcard m WHERE away_id = $teamId) x
			INNER JOIN incident i ON i.type = 'Played' AND x.id = i.matchcard_id
			ORDER BY x.date DESC
			LIMIT 1")->execute();

        foreach ($rows as $row) {
            return Model_Matchcard::find($row['id']);
        }

        return null;
    }
}
