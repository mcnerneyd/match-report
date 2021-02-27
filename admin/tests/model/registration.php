<?php
use PHPUnit\Framework\TestCase;

function dump($players) {
	foreach ($players as $player) {
		echo "\n${player['membershipid']}=${player['name']}(${player['firstname']}/${player['lastname']}/${player['phone']})?${player['status']}#${player['score']}/${player['team']}[".array_search($player, $players)."]";
	}
}

class Test_Model_Registration extends TestCase
{
    public function test_buildRegistration_initialRegistration()
    {
				$current = self::generate(5);

				$result = Model_Registration::buildRegistration($current);

				$this->assertStatus(5, 'added', $result);
				// players are in the order they are added
				$this->assertPlayers($result,'ME99','ME98','ME97','ME96','ME95');
    }

    public function test_buildRegistration_addedPlayer()
    {
				$current = self::generate(5);
				$initial = $current;
				unset($initial[0]);

				$result = Model_Registration::buildRegistration($current, $initial);

				$this->assertStatus(1, 'added', $result);
				$this->assertStatus(4, 'registered', $result);
				// the added player should be at the end
				$this->assertPlayers($result,'ME98','ME97','ME96','ME95','ME99');
    }

    public function test_buildRegistration_deletedPlayer()
    {
				$current = self::generate(5);
				$initial = $current;
				unset($current[0]);

				$result = Model_Registration::buildRegistration($current, $initial);

				$this->assertStatus(1, 'deleted', $result);
				$this->assertStatus(4, 'registered', $result);
				// the deleted player should stay in place
				$this->assertPlayers($result,'ME99','ME98','ME97','ME96','ME95');
    }

    public function xtest_buildRegistration_duplicatePlayer()
    {
				$current = self::generate(5);
				$initial = $current;
				$initial[] = $current[0];

				$result = Model_Registration::buildRegistration($current, $initial);

    }

    public function test_buildRegistration_teams()
    {
			$current = self::generate(50);

			$result = Model_Registration::buildRegistration($current, null, array(5,5,5,5));

			$this->assertSame(1, $result[4]['team']);
			$this->assertSame(3, $result[10]['team']);
			$this->assertSame(4, $result[15]['team']);
			$this->assertSame(4, $result[49]['team']);
    }

		public function test_buildRegistration_withScores() 
		{
			$current = self::generate(4);
			$history = array(
				'Selma Blazek'=>array(
					array('date'=>'2000-01-01','team'=>'1')
					),
				'Kali Taubman'=>array(
					array('date'=>'2000-01-01','team'=>'3')
					),
				'Sheryl Sears'=>array(
					array('date'=>'2000-01-02','team'=>'1'),
					array('date'=>'2000-01-01','team'=>'2')
					)
				);

			$result = Model_Registration::buildRegistration($current, null, null, $history);

			$this->assertSame(1.0, $result[0]['score']);
			$this->assertSame(1.5, $result[1]['score']);
			$this->assertSame(3.0, $result[2]['score']);
			$this->assertSame(99, $result[3]['score']);
		}

		public function test_placeholders() {
			$teamSizes = array(5,5,5);
			$current = self::generate(20);
			$history = array();
			foreach ($current as $k=>$v) {
				if ($k == 2) continue;
				if ($k == 7) continue;
				$history[$v['name']] = array(array('date'=>'2000-01-02','team'=>'1'));
			}

			$result = Model_Registration::buildRegistration($current, null, $teamSizes, $history);

			$this->assertSame('Kali Taubman', $result[10]['name']);
			$this->assertSame('Myung Rye', $result[11]['name']);
		}

		// ----- Internals --------------------------------------------------------
		private function assertPlayers() {
			$ids = func_get_args();
			$players = array_shift($ids);
			while ($players) {
				$this->assertTrue(count($ids)>0);
				$player = array_shift($players);
				$this->assertSame(array_shift($ids), $player['membershipid']);
			}

			$this->assertCount(0, $players);
		}

		private function assertStatus($count, $status, $values) {
				$this->assertCount($count, array_filter($values, function($x) use ($status) { return $x['status'] == $status; }));
		}

		private static function generate($ct) {

			$result = array();

			$names = file("admin/tests/model/namelist.txt");

			while (count($result) < $ct) {
				$player = cleanName(array_shift($names), '');
				$playerArr = cleanName($player, "[Fn][LN]");

				$result[] = array("name"=>$player,
					"lastname"=>$playerArr['LN'],
					"firstname"=>$playerArr['Fn'],
					"membershipid"=>"ME".count($names),
					"status"=>"registered",
					"phone"=>phone($player), 
					"team"=>null,
					"club"=>"myclub");

			}

			return $result;

		}
}
