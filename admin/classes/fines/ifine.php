<?php

abstract class Fines_Fine {
	abstract public function getDescription();
	abstract public function find();

	protected function createFine($club, $matchcardId) {
		$club = Model_Club::find_by_name($club);
		//if ($club == null) return;

		$f = new Model_Fine();
		$f->type = 'Missing';
		$f->detail = $this->getDescription();
		$f->club = $club['name'];
		$f->matchcard_id = $matchcardId;
		if (Input::param('test', null) != null) {
			echo "Type: Missing
Club: ${club['name']}
Description: $f->detail\n\n";
		} else {
			$f->save();
		}
	}
}

class Fines_CardNotOpenedByMidnight extends Fines_Fine {
	public function getDescription() {
		return "Card Not Submitted On Time
Matchcards must be submitted by midnight on the day of the match (card not open)";
	}

	public function find() {
		$nowDate = Date::forge();
		foreach (Model_Fixture::getAll() as $fixture) {
			if ($fixture['datetime'] > $nowDate) continue;
			$fixtureIds[] = $fixture['fixtureID'];
		}
		Log::info("Verify ".count($fixtureIds)." fixtures");

		// Find the fixtures of all cards and eliminate them from missing cards
		$matchcardFixtures = Model_Card::query()->where('fixture_id','!=','null')->select('fixture_id')->get();
		$matchcardFixtures = array_map(function($a) { return $a->fixture_id; }, $matchcardFixtures);

		$fines = array();
		foreach (array_diff($fixtureIds, $matchcardFixtures) as $fixtureId) {
			$fixture = Model_Fixture::get($fixtureId);

			$this->createFine($fixture['home_club'], 0);
			$this->createFine($fixture['away_club'], 0);
		}
	}
}
