<?php

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

define("COOKIE_TIMEOUT", 60 * 60 * 2);

class Controller_User extends Controller_Template
{
    // --------------------------------------------------------------------------
    public function action_index()
    {
        if (!\Auth::has_access('users.view')) {
            throw new HttpNoAccessException();
        }

        $data = array();
        $data['users'] = self::userlist();
        $data['clubs'] = Model_Club::find('all');
        $data['sections'] = Model_Section::find('all');

        $pins = array();
        foreach (DB::query("select hash, pin from pins")->execute() as $row) {
            $pins[$row['hash']] = $row['pin'];
        }

        foreach ($data['users'] as &$user) {
            $user['role'] = Auth::group('Simplegroup')->get_name($user['group']);
            $user['pin'] = isset($pins[$user['password']]) ? $pins[$user['password']] : "";
        }

        $this->template->title = "Users";
        $this->template->content = View::forge('user/index', $data);
    }

    public function action_sqlupdate()
    {
        if (!\Auth::has_access('users.view')) {
            throw new HttpNoAccessException();
        }
        foreach (\DB::query('select * from `user`')->execute() as $user) {
            if ($user['password']) {
                echo "UPDATE `user` SET `password`='" . \Auth::hash_password($user['old_password']) . "' WHERE id = ${user['id']};\n";
            }
        }

        return new Response("", 200);
    }

    public function action_resetpassword()
    {
        $username = Input::param('e');
        $user = Model_User::find_by_username($username);

        Log::info("Attempting to reset password for $username");

        if ($user->section['name'] !== 'test') {
            return new Response("", 403);
        }

        $newPassword = Input::param('p');
        $user['password'] = \Auth::hash_password($newPassword);
        $user->save();

        $this->template->title = "Password Reset";
        $this->template->content = View::forge('user/changepassword', array(
            "success" => true
        )
        );
    }

    // --------------------------------------------------------------------------
    public function action_forgottenpassword()
    {
        $username = Input::param('e');

        if (!isset($username)) {
            $this->template->title = "Forgotten Password";
            $this->template->content = View::forge('user/forgottenpassword');
            return;
        }

        $user = Model_User::find_by_email($username);

        // FIXME - get rid of these checks - security risk
        if (!$user) {
            Log::warning("Unknown user:$username");
            return new Response("User not found", 404);
        }
        if ($user['role'] == 'user' || $user['role'] == 'umpire') {
            return new Response("Cannot reset matchcard user password (only secretaries/admins)", 403);
        }

        $salt = Config::get("section.salt");
        $autoEmail = Config::get("section.automation.email");
        $title = Config::get("section.title");
        $hash = Input::param('h');

        if (!isset($hash)) {
            $ts = Date::forge()->get_timestamp();
            $hash = md5("$username $ts $salt");

            $email = Email::forge();
            $email->to($username);
            $email->subject("Leinster Hockey Cards - Password Reset");
            $email->html_body(View::forge("user/resetemail", array(
                "email" => $username,
                "timestamp" => $ts,
                "hash" => $hash
            )
            ));
            $email->send();

            Log::info("Password reset email sent to:$username");

            $this->template->title = "Email Sent";
            $this->template->content = View::forge(
                'user/forgottenpassword',
                array("email" => $username)
            );
            return;
        }

        $ts = Input::param('ts');
        $delta = Date::forge()->get_timestamp() - $ts;
        if ($delta > (5 * 60)) {
            return new Response("Expired hash", 401);
        }

        if ($hash != md5("$username $ts $salt")) {
            return new Response("Invalid hash", 401);
        }

        $newPassword = Input::param('p');
        if ($newPassword) {
            $user['password'] = \Auth::hash_password($newPassword);
            $user->save();
            $this->template->title = "Password Reset";
            $this->template->content = View::forge('user/changepassword', array(
                "success" => true
            )
            );
        } else {
            $this->template->title = "Reset Password";
            $this->template->content = View::forge('user/changepassword', array(
                "timestamp" => $ts,
                "email" => $username,
                "hash" => $hash
            )
            );
        }
    }

    public function action_resetlink()
    {
        if (!\Auth::has_access('user.impersonate')) {
            throw new HttpNoAccessException();
        }

        $username = Input::param('email');
        $user = Model_User::find_by_email($username);
        if (!$user) {
            Log::warning("Unknown user:$username");
            return new Response("User not found", 404);
        }
        if ($user['role'] == 'user' || $user['role'] == 'umpire') {
            return new Response("Cannot reset matchcard user password (only secretaries/admins)", 403);
        }

        $salt = Config::get("section.salt");
        $ts = Date::forge()->get_timestamp() + (24 * 60 * 60);
        $hash = md5("$site $username $ts $salt");

        $url = Uri::create("/User/ForgottenPassword?e=$username&ts=$ts&h=$hash");
        return new Response($url, 200);
    }

    // --------------------------------------------------------------------------
    public function action_accessdenied()
    {
        if (!Session::get('username')) {
            $loginPage = Uri::create('Login');
            Log::info("User is not logged in - redirecting to login page ($loginPage)");
            Response::redirect($loginPage);
        }

        Log::info("User is accessing restricted area: " . Session::get('username') . " - redirecting to " . Uri::base(false));
        //Response::redirect(Uri::base(false));
        $this->template->content = View::forge('user/403.php', array());
    }

    // --------------------------------------------------------------------------
    public function action_login()
    {
        Log::info("Login " . Request::main()->get_method() . ":" . print_r(Input::all(), true));

        \Session::delete('user');
        \Session::delete('username');
        \Session::delete('user-title');
        \Session::delete('club');

        if (\Input::param('consent')) {
            Cookie::set('CONSENT', 'YES');
        }

        if (\Auth::check()) {
            \Session::destroy();
            \Auth::logout();
            Response::redirect(Uri::create('Login'));
        }

        if (Input::param('site', null) === 'none') {
            Cookie::delete('site');
            Session::delete('site');
            Profiler::console("Site unselected");
        }

        $data = array();

        $data['selectedUser'] = Input::param('user', null);

        if (Input::post()) {
            Log::debug("Crypted password: " . Auth::hash_password(\Input::param('pin')));
            if (Auth::login()) {
                Input::param("remember-me", false) ? \Auth::remember_me() : \Auth::dont_remember_me();
                $username = Session::get('username');
                $user = Model_User::find_by_username($username);
                Log::info("Logged in user: $username " . ($user ? "User Found:" . $user['username'] . "/" . $user->getName() : "Not User Found"));

                Session::set('user', $user);
                Session::set('user-title', $user->getName());

                $r = new Response("Redirecting for single sign on", 302);
                if (Session::get('username') === 'admin') {
                    $r->set_header("location", Uri::create('Admin'));
                } else {
                    $r->set_header("location", '/cards/sso.php');
                }
                Cookie::set("jwt-token", self::encode("/cards/ui/"), COOKIE_TIMEOUT);
                return $r;
            } else {
                $data['username'] = Input::post('user');
                $data['login_error'] = 'Invalid credentials. Try again';
                Log::warning("Invalid credentials");
            }
        }

        $users = array_filter(self::userlist(), function ($k) {
            return $k['password'] && $k['group'] <= 2;
        });
        $users = array_map(function ($a) {
            return $a['username']; }, $users);
        sort($users);
        $data['users'] = $users;

        $this->template->content = View::forge('login', $data);
    }

    // --------------------------------------------------------------------------
    private static function encode($redirect = null)
    {
        $user = Session::get('user');

        $payload = [
            'base' => \Config::get('base_url'),
            'user' => Session::get('username', null),
            'user-title' => $user->getName(),
            'role' => $user['role'],
            'iat' => time(),
            'exp' => time() + COOKIE_TIMEOUT
        ];
        if ($user->section) {
            $payload['site'] = $user->section['name'];
            $payload['section'] = $user->section['name'];
            loadSectionConfig($user->section['name']);
            $payload['section-title'] = \Config::get('section.title');
        }
        if ($user['club']) {
            $payload['club'] = $user['club']['name'];
        }

        $payload['roles'] = array(Auth::group('Simplegroup')->get_name($user['group']));
        $perms = array();
        foreach (\Config::get('simpleauth.roles', array()) as $role => $rolev) {
            if (is_array($rolev)) {
                foreach ($rolev as $object => $values) {
                    if (is_array($values)) {
                        foreach ($values as $perm) {
                            $perms[] = $object . "." . $perm;
                        }
                    }
                }
            }
        }

        $payload['perms'] = array_values(array_filter($perms, function ($x) {
            return \Auth::has_access($x);
        }));
        $headers = [];
        return JWT::encode($payload, JWT_KEY, 'HS256', null, $headers);
    }

    public function action_switch()
    {
        $currentUser = \Session::get('username');

        if (!\Auth::has_access('user.impersonate')) {
            throw new HttpNoAccessException("User not entitled to impersonate: $currentUser");
        }

        $username = Input::param('u');
        $user = Model_User::find_by_username($username);
        $success = \Auth::force_login($user['id']);
        $a = \Session::get('login_hash');
        Session::set('user', $user);
        Session::set('user-title', $user->getName());
        Log::warning("User switched from $currentUser to $username ;$success/$a " . Session::get('site'));

        $r = new Response("Redirecting to sso", 302);
        $r->set_header("location", '/cards/sso.php');
        Cookie::set("jwt-token", self::encode("/cards/ui/"), COOKIE_TIMEOUT);
        return $r;
    }

    public function action_root()
    {
        Log::error("action_root");
        if (!Session::get('username')) {
            Response::redirect(Uri::create('Login'));
        } else {
            Response::redirect('/cards/ui/');
        }
    }

    private static function userlist()
    {
        $allusers = array();
        $clubs = array();

        foreach (Model_User::find('all') as $user) {
            $username = $user['username'];
            if ($username === null) {
                $username = $user['email'];
            }
            if ($username === null) {
                if ($user->club) {
                    $username = $user->club['name'];
                    if ($user->section) {
                        $username .= " (" . $user->section['name'] . ")";
                    }
                }
            }
            if ($username) {
                $allusers[$username] = $user;
            }
        }

        return $allusers;
    }
}