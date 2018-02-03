<?php

class Model_Registration 
{
	public static function find_all() {
		return \DB::query("select batch, c.name club, min(date) date, count(1) size,
					sum(if(sequence=-1,1,0)) deletions,
					sum(if(sequence<>-1,1,0)) additions,
					batch_date
				from registration r
					left join club c on r.club_id = c.id
				group by batch, c.name
				order by batch desc")->execute();
	}
}
