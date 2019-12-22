<?php
require APPPATH.'classes/fines/ifine.php';

class Controller_Fine extends Controller_Hybrid
{
	// --------------------------------------------------------------------------
	// index get gets a list of fines
	public function get_index() {
		echo "<pre>";
		Model_Fine::generate();
		echo "</pre>";

		return new Response("Fines", 200);
//		$data['fines'] = Model_Fine::find('all', array(
//			'where' => array(
//				array('resolved','=','0'),
//			),
//		));
//
//		$fines = array();
//		foreach ($data['fines'] as $fine) {
//			try {
//				$card = Model_Card::card($fine['matchcard_id']);
//			} catch (Exception $e) {
//				Log::error("Problem with card ${fine['matchcard_id']}:".$e->getMessage());
//				continue;
//			}
//			$fine['competition'] = $card['competition'];
//			$fine['home_team'] = $card['home']['club']." ".$card['home']['team'];
//			$fine['away_team'] = $card['away']['club']." ".$card['away']['team'];
//			$applies_to = $card['home']['club'] == $fine['club']['name'] ? "home" : "away";
//			$fine['applies_to'] = $applies_to;
//
//			$fine['date'] = $card['date'];
//			$fine['has_notes'] = false;
//			
//			if ($card[$applies_to]['notes']) $fine['has_notes'] = true;
//			if ($card['comment']) $fine['has_notes'] = true;
//
//			echo "<!-- ".print_r($fine, true)." -->\n";
//
//			$fines[] = $fine;
//		}
//		$data['fines'] = $fines;
//
//		$this->template->title = "Fines";
//		$this->template->content = View::forge('fixture/fines', $data);
	}

	// --------------------------------------------------------------------------
	// index post create a new fine
	public function post_index() {
		$fixtureid = $_POST['fixtureid'];

		$card = \Model_Card::find_by_fixture($fixtureid, true);
		$side = $_POST['optionsTeam'];
		$team = $card[$side];
		$detail = $_POST['amount'].':'.$_POST['reason'];

		print_r($card);

		$newfine = new Model_Fine();
		$newfine->matchcard_id = $card['id'];
		$newfine->club_id = $team['club_id'];
		$newfine->detail = $detail;
		$newfine->type = 'Missing';
		$newfine->resolved = 0;
		$newfine->save();

		$response = new Response("Fine created", 201);
		$response->set_header("Location", Uri::create("fine/".$newfine['id']));
		return $response;
	}

	public function get_index2() {
		$f = new Fines_CardNotOpenedByMidnight();

		$f->find();
	}

	// --------------------------------------------------------------------------
	// index delete deletes a fine or a list of fines
	public function delete_index() {
		$count = 0;

		foreach ($this->getIds() as $id) {
			$fine = Model_Fine::find_by_id($id);
			$fine->resolved = 1;
			$fine->save();
			$count++;
			\Log::warning("Deleted fine: id=$id");
		}

		return new Response("$count fine(s) deleted", 200);
	}

	// --------------------------------------------------------------------------
	public function post_generate() {
		Model_Fine::generate();

		return new Response("Fines", 200);
	}

	public function post_issuefines() {
		if (!\Input::post('ids')) {
			echo "No fines selected";
			return $this->response(null, 400);
		}

		try
		{
			DB::start_transaction();

			$result = array();

			foreach (\Input::post('ids') as $id) {
				$fine = Model_Fine::find_by_id($id);
				$fine->resolved = 1;
				$fine->save();

				$card = Model_Card::card($fine['matchcard_id']);
				$fine['competition'] = $card['competition'];
				$fine['home_team'] = $card['home']['club']." ".$card['home']['team'];
				$fine['away_team'] = $card['away']['club']." ".$card['away']['team'];
				$fine['date'] = $card['date'];

				$result[] = $fine;
			}

			usort($result, function($a, $b) {
				return strcmp($a['club_id'], $b['club_id']);
			});

			$admin_email = Config::get("config.admin_email");

			if ($admin_email) {
				$email = Email::forge();
				$email->to($admin_email);
				$email->subject('Fines');
				$email->html_body(\View::forge('fine/email', array('fines'=>$result)));
				$email->send();
				Log::info("Fines email sent to: $admin_email");
			} else {
				$this->response("<pre>".\View::forge('fine/email', array('fines'=>$result))."</pre>");
				Log::info("No admin email for fines");
			}

			DB::commit_transaction();

			return new Response(count($result)." fine(s) issued", 200);
		}
		catch(\EmailSendingFailedException $e)
		{
			DB::rollback_transaction();

			Log:error("Failed to send fines email: ".$e->getMessage());

			return new Response("Failure:".$e->getMessage(), 500);
		}
	}

	// Get a list of ids from the parameters
	private function getIds() {
		$ids = null;

		if (\Input::param('ids')) {
			$ids = \Input::param('ids');
		} else {
			$ids = array();
		}

		$id = $this->param('id', null);

		if ($id) $ids[] = $id;

		return $ids;
	}
}
