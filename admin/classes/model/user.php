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

	protected static $_belongs_to = array('club','section');

	protected static $_table_name = 'user';

  function getName() {
    switch ($this->group) {
      case 1:
        if ($this->club === null) return "!Bad user!";
        return $this->club['name']." (".$this->section['name'].")";
      case 2:
        return $this->username;
      case 25:
        $s = $this->section ? "/".$this->section['name'] : "";
        return $this->email." (".$this->club['name'].$s.")";
      case 99:
        return $this->email;
    }
  }

  static function initialize() {
    Log::info("Checking for admin user");

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

}
