<?php

class Model_Section extends \Orm\Model
{
	protected static $_properties = array(
		'id',
    'name',
	);

	protected static $_has_many = array('competition', 'user');

	protected static $_table_name = 'section';

  public function getProperty($propertyname) {
    $name = $this['name'];
    \Config::load(DATAPATH."/sections/".$name."/config.json", $name);
    return \Config::get("$name.$propertyname", $name);
  }
}
