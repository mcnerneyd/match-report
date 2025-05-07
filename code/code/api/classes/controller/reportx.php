<?php class Controller_Reportx extends Controller
{

    private static function format($values, $format)
    {

        $result = "|";
        if ($values) {
            foreach ($values as $i => $value) {
                if ($i >= count($format))
                    break;
                $l = $format[$i];
                $pad = STR_PAD_RIGHT;
                if ($l < 0) {
                    $l = -$l;
                    $pad = STR_PAD_LEFT;
                }
                if (strlen($value) > $l)
                    $value = substr($value, 0, $l - 1) . "…";
                $result = $result . str_pad($value, $l, " ", $pad) . "|";
            }
        } else {
            foreach ($format as $l) {
                $result = $result . str_pad("", abs($l), "-") . "|";
            }
        }
        return $result;
    }

    private static function report($title, $query, $format = array())
    {
        try {
            $section = \Input::param("section", null);

            if (gettype($query) === 'string')
                $query = DB::query($query);

            $firstRow = true;
            $seasonStart = currentSeasonStart();
            echo "<h1>Report: $title</h1>";
            ?>
            <style>
                pre {
                    margin: 0;
                }
            </style>
            <?php
            $rows = $query->
                bind('section', $section)->
                bind('season_start', $seasonStart)->
                execute();
            echo "<pre>";
            foreach ($rows as $r) {
                if ($firstRow) {
                    $firstRow = false;
                    echo self::format(array_keys($r), $format) . "\n";
                    echo self::format(null, $format) . "</pre>\n<pre>";
                }
                echo self::format(array_values($r), $format) . "\n";
            }
            echo "</pre>";
        } catch (Exception $e) {
            echo "ERROR:" . $e->getMessage() . "\n";
            var_dump($e->getTraceAsString());
        }

        return new Response("", 200);
    }

    public function action_offenders()
    {
        try {
            $format = array(10, 30, 20, 80, -10);
            $section = \Input::param("section", null);
            $section = Model_Section::find_by_name($section);
            $currMonth = Date::time()->format("%m");
            echo "<h1>Report: Offenders</h1>";
            ?>
            <style>
                pre {
                    margin: 0;
                }
            </style>
            <?php
            $rows = Model_Fixture::all($section);
            $rows = array_filter($rows, function ($r) use ($currMonth) {
                if ($r['played'] == 'yes')
                    return false;
                $dt = $r['datetimeZ'];
                if (substr($dt, 5, 2) != $currMonth)
                    return false;
                return str_ends_with($dt, "T06:01:00Z");
            });
            $rows = array_values($rows);
            echo "<pre>\n";
            echo self::format(array("Fixture ID", "Competition", "Club", "Offense", "Fine"), $format) . "\n";
            echo self::format(null, $format) . "</pre>\n<pre>";
            foreach ($rows as $r) {
                echo self::format(array($r["fixtureID"], $r['competition'], $r["home_club"], "Match time not updated (Bye-Law 3.1.2) - " . $r['datetimeZ'], "€25"), $format) . "\n";
            }
            echo "</pre>";
        } catch (Exception $e) {
            echo "ERROR:" . $e->getMessage() . "\n";
            var_dump($e->getTraceAsString());
        }

        return new Response("", 200);

    }

    public function action_players()
    {
        return self::report(
            "Player Report",
            "SELECT player, count(*) ct FROM incidents i
                WHERE player IS NOT NULL AND TRIM(player) <> '' 
                    AND type = 'Played'
                    AND (:section IS NULL OR :section = section)
                GROUP BY player
                ORDER BY player",
            array(40, -10)
        );

    }
    public function action_topscorers()
    {
        return self::report(
            "Top Scorer Report",
            "SELECT player, club, competition, section, SUM(detail) score 
                FROM incidents
                WHERE type = 'Scored' 
                    AND detail > 0
                    AND date > :season_start
                    AND (:section IS NULL OR :section = section)
                GROUP BY player, club, competition, section
                ORDER BY score DESC",
            array(40, 40, 30, 10, -5)
        );
    }

    public function action_redyellow()
    {
        return self::report(
            "Red/Yellow Report",
            "SELECT date, competition, 
                    CONCAT(home_club,' ',home_team,' v ',away_club,' ',away_team) as `match`,
                    player,
                    type card 
                FROM incidents
                WHERE type in ('Red Card', 'Yellow Card') 
                    AND date > :season_start
                    AND (:section IS NULL OR :section = section)
                ORDER BY date DESC",
            array(20, 30, 50, 40, 12)
        );
    }

    public function action_playerteams()
    {
        return self::report(
            "Players on multiple teams",
            "SELECT player, club, count(*) teams FROM (
                SELECT DISTINCT player, club, 
                    CASE WHEN club = home_club THEN home_team ELSE away_team END as team
                FROM incidents WHERE type = 'Played'
                    AND (:section IS NULL OR :section = section)
                    AND date > :season_start
                ) t
                GROUP BY player, club
                HAVING teams > 1
                ORDER BY teams DESC, player",
            array(40, 30, -10)
        );
    }
}
