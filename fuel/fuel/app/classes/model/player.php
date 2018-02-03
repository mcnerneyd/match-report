<?php

class Model_Player extends \Model
{

		// ------------------------------------------------------------------------
		public static function archive($dt, $clean = false) {
				$date = $dt->format('%Y-%m-%d');
				$sql = "select i.Date, i.Type, i.Player, c.code Club, 
										 coalesce(x.code,'') Competition, 
										 coalesce(concat(ch.code, th.team),'') Home, 
										 coalesce(concat(ca.code, ta.team),'') Away, 
										 coalesce(i.detail,'') Detail, i.ID, i.resolved Cancelled
									from incident i
										left join club c on i.club_id = c.id
										left join matchcard m on m.id = i.matchcard_id
										left join competition x on m.competition_id = x.id
										left join team th on m.home_id = th.id
										left join club ch on th.club_id = ch.id
										left join team ta on m.away_id = ta.id
										left join club ca on ta.club_id = ca.id
									where i.date < '$date'

									union all

									select m.date, 'Matchcard', null, ch.code, 
										 coalesce(x.code,'') Competition, 
										 coalesce(concat(ch.code, th.team),'') Home, 
										 coalesce(concat(ca.code, ta.team),'') Away, 
										 '', m.id, -1
									from matchcard m
										left join competition x on m.competition_id = x.id
										left join team th on m.home_id = th.id
										left join club ch on th.club_id = ch.id
										left join team ta on m.away_id = ta.id
										left join club ca on ta.club_id = ca.id
									where m.date < '$date'
										 
									union all

									select r.date, 'Registration', concat(lastname, ', ', firstname), 
										 c.code,
										 '',
										 coalesce(concat(c.code, t.team),''), 
										 '',
										 concat(r.batch, '/', r.sequence), r.id, -1
									from registration r
										left join club c on r.club_id = c.id
										left join team t on r.team_id = t.id
									where r.date < '$date'";

				$req = \DB::query($sql)->execute();

				$ct = 0;
				$file = APPPATH."/archive".$date;
				while (file_exists($file."-".$ct.".csv")) {
					$ct++;
				}
				$file = $file."-".$ct.".csv";

				$f = fopen($file, 'w');

				fputcsv($f, array('DateTime', 'Type', 'Player', 'Club', 'Competition', 'HomeTeam', 
						'AwayTeam', 'Note', 'ID', 'Deleted'));

				$ct = 0;
				foreach ($req as $row) {
					fputcsv($f, $row);
					$ct++;
				}

				fclose($f);

				if ($clean) {
					$ct1 = \DB::query("DELETE FROM incident WHERE date < '$date'")->execute();
					$ct2 = \DB::query("DELETE FROM matchcard WHERE date < '$date'")->execute();

					echo "<pre>$ct record(s) archived in $file\n$ct1 incident(s) deleted\n$ct2 matchcard(s) deleted\n</pre>";
				}
				// FIXME Merge and clear registration

				return $ct;
		}
}
