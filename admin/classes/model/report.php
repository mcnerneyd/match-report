<?php

class Model_Report
{
    public static function scorers()
    {
        return \DB::query("select i.player, c.name club, x.name competition, sum(detail) score 
			from incident i 
				left join club c on i.club_id = c.id
				left join matchcard m on i.matchcard_id = m.id
				left join competition x on m.competition_id = x.id
			where type = 'Scored' 
				and detail > 0
				and i.date > '".currentSeasonStart()."'
			group by player, c.name, x.id")->execute();
    }
}
