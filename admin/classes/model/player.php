<?php

class Model_Player extends \Model
{

	public static function getHistory($club, $beforeDate = null) {
		$sql = "select distinct i.player, x.code, x.name, m.date, t.name, m.id, m.fixture_id
					from incident i join matchcard m on i.matchcard_id = m.id
					join competition x on m.competition_id = x.id
						join club c on i.club_id = c.id
						left join team__competition e on e.competition_id = x.id
						left join team t on e.team_id = t.id and c.id = t.club_id
				where resolved = 0
				and t.name is not null
				and i.type = 'Played'
				and c.name = '$club'";
				if ($beforeDate) {
					$sql .= " and i.date > '".date("Y-m-d", seasonStart($beforeDate)->get_timestamp())."'
						 and i.date < '".date("Y-m-d", $beforeDate)."' ";
				} else {
					$sql .= " and i.date > '".date("Y-m-d", currentSeasonStart()->get_timestamp())."'";
				}

				$sql .= " ORDER BY i.date DESC";

				$result = array();

				foreach (\DB::query($sql)->execute() as $row) {
					$player = cleanName($row['player']);
					if (!isset($result[$player])) $result[$player] = array();
					$result[$player][] = $row;
				}

				return $result;
		}


		// ------------------------------------------------------------------------
		public static function archive() {
				$siteDir = DATAPATH."/sites/".Session::get('site');
				print_r(static::listAllFiles($siteDir."/registration/"));

				$archiveDir = $siteDir."/tmp/archive/";
				if (!file_exists($archiveDir)) mkdir($archiveDir, 0777, true);
				$files = array();

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
										left join club ca on ta.club_id = ca.id";

				$f = fopen($archiveDir."incidents.csv", 'w');
				fwrite($f, "date, type, player, club, competition, home, away, detail, id, cancelled\n");
				foreach (\DB::query($sql)->execute() as $row) {
					fputcsv($f, $row);
				}
				fclose($f);

				$sql = "select m.date, ch.code, 
										 coalesce(x.code,'') Competition, 
										 coalesce(concat(ch.code, th.team),'') Home, 
										 coalesce(concat(ca.code, ta.team),'') Away, 
										 m.id
									from matchcard m
										left join competition x on m.competition_id = x.id
										left join team th on m.home_id = th.id
										left join club ch on th.club_id = ch.id
										left join team ta on m.away_id = ta.id
										left join club ca on ta.club_id = ca.id";

				$f = fopen($archiveDir."matchcards.csv", 'w');
				fwrite($f, "date, club, competition, home, away, id\n");
				foreach (\DB::query($sql)->execute() as $row) {
					fputcsv($f, $row);
				}
				fclose($f);

				$configFile = $siteDir."/config.json";

				$zipFile = $siteDir."/tmp/archive.zip";
				$zip = new ZipArchive;
				$zip->open($zipFile, ZipArchive::OVERWRITE);
				foreach (static::listAllFiles($siteDir."/registration") as $file) {
					$zip->addFile($file, substr($file, strlen($siteDir)));
				}
				$zip->addFile($archiveDir."incidents.csv", "incidents.csv");
				$zip->addFile($archiveDir."matchcards.csv", "matchcards.csv");
				if (file_exists($configFile)) {
					$zip->addFile($configFile, "config.json");
				}

				$zip->close();

				return $zipFile;
		}

		static function listAllFiles($root) {
			$result = array();

			if (file_exists($root)) {
				foreach (scandir($root) as $file) {
					if ($file[0] == '.') continue;
					$path = $root."/".$file;
					if (is_dir($path)) {
						$result = array_merge($result, static::listAllFiles($path));
					} else {
						$result[] = realpath($path);
					}
				}
			}

			return $result;
		}
}
