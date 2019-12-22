<?php

class Model_Fine extends \Orm\Model
{
	protected static $_properties = array(
		'id',
		'date',
		'type',
		'club_id',
		'matchcard_id',
		'detail',
		'resolved',
	);

	protected static $_belongs_to = array('club');

	protected static $_has_one = array(
		'card'=>array(
			'key_to'=>'id',
			'key_from'=>'matchcard_id',
			'model_to'=>'Model_Card',
		)
	);

	protected static $_table_name = 'incident';

	protected static $_conditions = array(
		'order_by' => array('date' => 'desc'),
		'where' => array(
			array('type', '=', 'Missing'),		// Missing is actually the type for fine (Late=Warning)
		),
	);

	public static function generate() {
		try {
			$fines = array();
			foreach (get_class_methods('Model_Fine') as $method) {
				if (!preg_match('/^generate.+$/', $method)) continue;

				$result = call_user_func(array('Model_Fine', $method));
				$fines = array_merge($fines, $result);
			}

			foreach ($fines as $fine) {
				if (!$fine) continue;

				try {
					$fine->save();
				} catch (Exception $e) {
					echo "Exception saving fine: ".$e->getMessage()."\n";
				}

				echo " * ".$fine->detail."\n"; 
			}
		} catch (Exception $e) {
			echo "Exception: ".$e->getMessage()."\n";
		}
	}

	private static function generateForNoPlayersAtStartOfMatch() {
		echo "Matchcard must have players at the start of the match\n";
		$delay = 0;
		$list = \DB::query("select m.id, m.fixture_id, t.club_id, c.name club
                from (select id, fixture_id, open, date, home_id team_id from matchcard
                     union select id, fixture_id, open, date, away_id from matchcard) m
                left join team t on t.id = m.team_id
                left join incident i on i.matchcard_id = m.id 
                    and type = 'Played' 
                    and i.club_id = t.club_id
                left join club c on t.club_id = c.id
                where m.fixture_id is not null
                    and i.id is null
                    and m.open < 60
                    and m.date < now()
				order by m.date")->execute();

		$fines = array();
		foreach ($list as $card) {
			$fixture = Model_Fixture::get($card['fixture_id']);
			if ($fixture['cover'] !== 'CHA') continue;
			$fixtureName = $fixture['competition'].":".$fixture['home']." v ${fixture['away']} (${fixture['fixtureID']})";
			$fines[] = self::createFine($card['club'], 0, null, "For fixture $fixtureName, {club} had no players at the start of the match (7 required)");
		}

		return $fines;
	}

	private static function generateForLessThan7PlayersAtStartOfMatch() {
		echo "Matchcard must have 7 players before start of match\n";
		$playerCount = 7;
		$delay = 0;
		$list = \DB::query("select m.id, m.fixture_id, c.name club, count(i.id) playerCount
				from matchcard m
				left join incident i on i.matchcard_id = m.id 
					and type = 'Played' 
					and i.date between m.date and date_add(m.date, interval $delay minute)
				left join club c on i.club_id = c.id
				where m.fixture_id is not null
					and m.open < 60
					and m.date < now()
				group by m.id, m.fixture_id, i.club_id
				having count(i.id) < $playerCount
				order by m.date")->execute();

		$fines = array();
		foreach ($list as $card) {
			$fixture = Model_Fixture::get($card['fixture_id']);
			if ($fixture['cover'] !== 'CHA') continue;
			$fixtureName = $fixture['competition'].":".$fixture['home']." v ".$fixture['away'];
			$fines[] = self::createFine($card['club'], 0, null, "For fixture $fixtureName, {club} had only ${card['playerCount']} players at the start of the match ($playerCount required)");
		}

		return $fines;
	}

	private static function generateCardNotSubmittedBeforeMidnight() {
		echo "Matchcard must be submitted before midnight\n";
		$nowDate = Date::forge();
		foreach (Model_Fixture::getAll() as $fixture) {
			if ($fixture['datetime'] > $nowDate) continue;
			$fixtureIds[] = $fixture['fixtureID'];
		}

		// Find the fixtures of all cards and eliminate them from missing cards
		$matchcardFixtures = Model_Card::query()->where('fixture_id','!=','null')->select('fixture_id')->get();
		$matchcardFixtures = array_map(function($a) { return $a->fixture_id; }, $matchcardFixtures);

		$fines = array();
		foreach (array_diff($fixtureIds, $matchcardFixtures) as $fixtureId) {
			$fixture = Model_Fixture::get($fixtureId);

			if ($fixture['cover'] !== 'CHA') continue;
			if (!isset($fixture['home_club']) || !isset($fixture['away_club'])) continue;

			$fixtureName = $fixture['competition'].":".$fixture['home']." v ".$fixture['away'];

			$fines[] = self::createFine($fixture['home_club'], 0, null, "For fixture $fixtureName, {club} has not submitted a matchcard by midnight");
			$fines[] = self::createFine($fixture['away_club'], 0, null, "For fixture $fixtureName, {club} has not submitted a matchcard by midnight");
		}

		return $fines;
	}

	public function __construct($club, $matchcardId, $value, $detail) {
		if ($value === null) $value = 20;

		$this->type = 'Missing';
		$this->club = $club['name'];
		$this->matchcard_id = $matchcardId;
		$this->detail = "Fine &euro;$value: $detail";
	}

	protected static function createFine($club, $matchcardId, $value, $detail) {	// FIXME probs should be constructor
		$club = Model_Club::find_by_name($club);
		if ($club == null) return null;

		$detail = str_replace("{club}", $club['name'], $detail);
		$detail = str_replace("{fixture}", $matchcardId, $detail);

		return new Model_Fine($club, $matchcardId, $value, $detail);
	}
}
