<?php
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class Controller_UserApi extends Controller_RestApi
{
    // --------------------------------------------------------------------------
    public function options_index()
    {
        return new Response("OK", 200);
    }

    function generateJwt()
    {
        $username = Session::get('username');

        if ($username) {
            $user = Model_User::find_by_username($username);
            $userData = array(
                'id' => $user['id'],
                'section' => $user->section ? $user->section['name'] : null,
                'user' => $user->username,
                'title' => $user->getName(),
                'role' => $user['role'],
                'club' => $user->club ? $user->club['name'] : null
            );

            $userData['jwt-token'] = JWT::encode($userData, JWT_KEY, 'HS256');
            return $userData;
        } else {
            return false;
        }
    }



    // --------------------------------------------------------------------------
    public function get_refresh()
    {
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
    public function delete_index()
    {
        if (!\Auth::has_access('users.delete'))
            throw new HttpNoAccessException;
        $username = Input::param('username');
        $user = Model_user::find_by_username($username);

        if (!$user) {
            return new Response("No such user: $username", 404);
        }

        //$user->delete();

        return new Response("User deleted: $username", 204);
    }

    // --------------------------------------------------------------------------
    public function post_index()
    {
        Log::debug("Create user");
        if (!\Auth::has_access('users.create'))
            return new Response("Forbidden", 401);

        $clubName = Input::post('club');
        $club = null;

        if ($clubName != null) {
            $club = Model_Club::find_by_name($clubName);
        }

        $username = Input::post('username');
        $role = Input::post('role');
        $email = Input::post('email');
        $section = Input::post('section', null);

        if ($section == 'all') {
            $section = null;
        }

        if ($section !== null) {
            $section = Model_Section::find_by_name($section);
        }

        $password = null;
        $oldPassword = null;

        if ($role == 'secretary') {
            $username = $email;
            $password = 'password';
            $group = 25;
        } else if ($role == 'umpire') {
            $club = null;
            $password = Model_User::generatePassword(4);
            $oldPassword = $password;
            $group = 2;
        } else if ($role == 'admin') {
            $username = $email;
            $club = null;
            $group = 99;
        } else {
            $username = $clubName;
            $password = "0000";
            $oldPassword = $password;
            $email = "";
            $group = 1;
        }

        $user = new Model_User();
        $user->username = $username;
        $user->section = $section;
        $user->password = \Auth::hash_password($password);
        $user->email = $email;
        $user->club_id = $club ? $club['id'] : null;
        $user->group = $group;
        $user->save();
        $user->log();

        return new Response("Created $role: $username", 201);
    }

    public function post_missingusers()
    {
        if (!\Auth::has_access('users.create'))
            return new Response("Forbidden", 401);

        $missingClubs = Db::query("SELECT c.name, c.id 
            FROM club c 
                LEFT JOIN user u ON c.id = u.club_id AND u.group = 1 
            WHERE u.id IS NULL");

        foreach ($missingClubs->execute() as $missingClub) {
            $user = new Model_User();
            $user->role = 'User';
            $user->username = $missingClub['name'];
            $user->pin = Model_User::generatePassword(4);
            $user->password = \Auth::hash_password($user->pin);
            $user->club_id = $missingClub['id'];
            $user->group = 1;
            $user->email = "";
            $user->save();
        }

        return new Response("Created missing clubs", 201);
    }

    // --------------------------------------------------------------------------
    public function put_refreshpin()
    {
        if (!\Auth::has_access('user.refreshpin'))
            throw new HttpNoAccessException;
        $username = Input::put('username');

        // FIXME Make sure secretary user matches

        $user = Model_User::find_by_username($username);
        if (!$user) {
            return new Response("User not found", 404);
        }

        $oldPassword = $user->password;

        $user->pin = Model_User::generatePassword(4);
        $user->old_password = $user->pin;
        $user->password = \Auth::hash_password($user->pin);
        $user->save();

        Log::info("Previous PIN: $oldPassword");

        Session::set_flash("notify", array(
            "msg" => "PIN updated for user $username",
            "className" => "warn"
        )
        );

        return new Response("PIN Updated for user: " . $username, 201);
    }
}
