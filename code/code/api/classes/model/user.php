<?php

class Model_User extends \Orm\Model
{
    protected static $_properties = array(
        'id',
        'username',
        'password',
        'club_id',
        'section_id',
        'email',
        'group',
    );

    protected static $_belongs_to = array('club', 'section' => array('cascade-save' => false));

    protected static $_table_name = 'user';

    public function getName()
    {
        //return $this->getName2()."/".$this->id;
        return $this->getName2();
    }

    public function getName2()
    {
        switch ($this->group) {
            case 1:   // user
                if (!$this->club) {
                    return "Bad name (1/club)";
                }

                return $this->club['name'] . ($this->section ? " (" . $this->section['name'] . ")" : "");
            case 2:
                return $this->username;
            case 25:
                $s = $this->section ? "/" . $this->section['name'] : "";
                if (!$this->club) {
                    return "Bad name (25/club)";
                }
                return $this->email . " (" . $this->club['name'] . $s . ")";
            case 99:
                $s = $this->section ? " (" . $this->section['name'] . ")" : "";
                return $this->email . $s;
        }
    }

    public function log()
    {
        $section = $this['section'] ? "/" . $this['section']['name'] : "";
        $userName = (string) $this['username'];
        $email = (string) $this['email'];
        $email = ($email && $email != $userName) ? $email = "<$email>" : "";
        $club = $this['club'] ? "@" . $this['club']['name'] : "";
        $password = (string) $this['password'];

        $userValue = "[$userName$email]$club$section";
        $id = (string) $this['id'];

        switch ($this['group']) {
            case 1:
                $userValue = "$club$section";
                $g = "";
                break;
            case 2:
                $g = '=umpire';
                break;
            case 25:
                $g = '=secretary';
                break;
            case 50:
                $g = '=moderator';
                break;
            case 99:
                $g = '=admin';
                break;
            case 100:
                $g = '=superuser';
                break;
            default:
                $g = '';
                break;
        }

        Log::info("+USER $userValue$g {{$password}} #$id/".Auth::get_screen_name());
    }

    public static function initialize()
    {
        Log::debug("Checking for admin user");

        $admin = Model_User::find_by_username('admin');

        if (!$admin) {
            Log::warning("Creating admin user");
            $admin = new Model_User();
            $admin['username'] = 'admin';
            $admin['password'] = \Auth::hash_password('password');
            $admin['group'] = 100;
            $admin->save();
        }
    }

    //-----------------------------------------------------------------------------
    static function generatePassword($length): string
    {
        return substr(str_pad(rand(0, pow(10, $length) - 1), $length, '0'), 0, $length);
    }
}
