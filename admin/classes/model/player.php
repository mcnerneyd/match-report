<?php

class Model_Player extends \Model
{
    public static function getHistory(Model_Club $club, ?int $beforeDate = null)
    {
        $sql = "select distinct i.player, x.name, m.date, t.name team, m.id, m.fixture_id, i.date, ch.name home_name, ca.name away_name, c.name club_name
					from incident i join matchcard m on i.matchcard_id = m.id
					join competition x on m.competition_id = x.id
					join club c on i.club_id = c.id
					left join team__competition e on e.competition_id = x.id
					left join team t on e.team_id = t.id and c.id = t.club_id
					left join team th on th.id = m.home_id
					left join club ch on ch.id = th.club_id
					left join team ta on ta.id = m.away_id
					left join club ca on ca.id = ta.club_id
				where resolved = 0
				and t.name is not null
				and i.type = 'Played'
				and c.name = :club ";
        if ($beforeDate) {
            $sql .= " and i.date > '".date("Y-m-d", seasonStart($beforeDate)->get_timestamp())."'
						 and i.date < '".date("Y-m-d", $beforeDate)."' ";
        } else {
            $sql .= " and i.date > '".date("Y-m-d", currentSeasonStart()->get_timestamp())."'";
        }

        $sql .= " ORDER BY i.date DESC";

        $result = array();

        foreach (\DB::query($sql)->bind('club', $club->name)->execute() as $row) {
            $player = Model_Player::cleanName($row['player']);
            if ($row['club_name'] == $row['home_name']) {
                $row['opposition'] = $row['away_name'];
            } else {
                $row['opposition'] = $row['home_name'];
            }
            if (!isset($result[$player])) {
                $result[$player] = array();
            }
            $result[$player][] = $row;
        }

        return $result;
    }

    static function phone(string $player) : string
    {
        $result = "";

        foreach (explode(" ", $player) as $name) {
            if (!$name)
                continue;
            if ($result)
                $result .= " ";
            $result .= metaphone($name);
        }

        return $result;
    }

    static function cleanName(string $player, string $format = 'Fn LN')
    {
        if (!$player) {
            return $player;
        }

        if ($format === '') {
            return unicode_trim($player);
        }

        $player = trim(preg_replace("/[^A-Za-z, ]/", "", $player));
        $a = strpos($player, ",");
        if ($a) {
            $lastname = substr($player, 0, $a);
            $b = strpos($player, ",", $a + 1);
            if (!$b) {
                $b = strlen($player);
            }
            $firstname = substr($player, $a + 1, $b);
        } else {
            $c = strrpos($player, " ");
            $lastname = substr($player, $c + 1);
            $firstname = substr($player, 0, $c);
        }

        $firstname = trim(preg_replace('/[^A-Za-z ]/', '', $firstname));
        $lastname = trim(preg_replace('/[^A-Za-z]/', '', $lastname));
        if (!$firstname) {
            return self::cleanName($lastname);
        }

        switch ($format) {
            case "LN, Fn":
                $player = strtoupper($lastname) . ", " . ucwords(strtolower($firstname));
                break;

            case "[Fn][LN]":
                return array(
                    "Fn" => ucwords(strtolower($firstname)),
                    "LN" => strtoupper($lastname)
                );

            case "Fn LN":
            default:
                $player = ucwords(strtolower($firstname)) . " " . strtoupper($lastname);
                break;
        }

        $player = trim($player);
        if ($player == ',') {
            $player = "";
        }


        return $player;
    }
}
