<?php
class Controller_UserApi extends Controller_Rest
{
	public function before() {
		if (!\Auth::has_access('admin.all')) throw new HttpNoAccessException;

		parent::before();
	}

	// --------------------------------------------------------------------------
	public function get_refresh() {
		foreach (Model_Club::find('all') as $club) {
			$name = $club['name'];
			echo "Checking $name\n";
			$user = Model_user::find_by_username($name);

			if (!$user) {
				$user = new Model_User();
				$user->username = $name;
				$user->club = $club;
				$user->role = 'user';
				$user->password = '0000';
				$user->email = "user@$name.com";
				$user->save();
			}
		}
	}

	// --------------------------------------------------------------------------
	public function delete_index() {
		if (!\Auth::has_access('admin.all')) throw new HttpNoAccessException;
		$username = Input::param('username');
		$user = Model_user::find_by_username($username);

		if (!$user) {
			return new Response("User deleted", 409);
		}

		$user->delete();

		return new Response("User deleted", 204);
	}

	// --------------------------------------------------------------------------
	public function post_index() {
		if (!\Auth::has_access('admin.all')) throw new HttpNoAccessException;

		$clubName = Input::post('club');
		$club = null;

		if ($clubName != null) {
			$club = Model_Club::find_by_name($clubName);
		}

		$username = Input::post('username');
		$role = Input::post('role');
		$email = Input::post('email');

		if ($role == 'user' || $role == 'umpire') {
			if (Model_User::find_by_username($username)) {
				return new Response("User already exists", 409);
			}
		}

		if ($role == 'secretary') {
			$existingUser = Model_User::query()->where('club_id', $club['id'])->where('role','secretary')->get_one();
			if ($existingUser) {
				$existingUser->email = $email;
				$existingUser->password = null;
				$existingUser->save();
				return new Response("User updated", 201);
			}

			$username = $email;
		}


		$newUser = new Model_User();
		$newUser->username = $username;
		$newUser->email = Input::post('email');
		$newUser->password = generatePassword(4);
		$newUser->club = $club;
		$newUser->role = $role;
		$newUser->save();

		return new Response("Created user", 201);
	}

	// --------------------------------------------------------------------------
	public function put_refreshpin() {
		if (!\Auth::has_access('user.refreshpin')) throw new HttpNoAccessException;
		$username = Input::put('username');

		// FIXME Make sure secretary user matches

		$user = Model_User::find_by_username($username);
		if (!$user) {
			return new Response("User not found", 404);
		}

		$user->password = generatePassword(4);
		$user->save();

		Session::set_flash("notify", array("msg"=>"PIN updated for user $username",
			"className"=>"warn"));
	}
}
