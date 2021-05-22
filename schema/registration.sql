CREATE TABLE IF NOT EXISTS club (
  id int(10) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  name varchar(50) NOT NULL,
  code varchar(4) NOT NULL,
  UNIQUE(name),
  UNIQUE(code)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1  ;

CREATE TABLE IF NOT EXISTS competition (
  id int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  section varchar(30) NOT NULL,
  name varchar(30) NOT NULL,
  groups VARCHAR(128) NULL,
  code varchar(4) NOT NULL,
  sequence INT NULL,
  manager varchar(128) NOT NULL,
  format ENUM('cup','league') NOT NULL DEFAULT 'league',
  user_id int(11) NOT NULL,
  teamsize tinyint(3) unsigned DEFAULT NULL,
  teamstars tinyint(3) unsigned DEFAULT NULL,
  UNIQUE (section, name),
  UNIQUE (section, code)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1  ;

CREATE TABLE IF NOT EXISTS team__competition (
  team_id int(11) NOT NULL REFERENCES team(id) ON DELETE CASCADE,
  competition_id int(11) NOT NULL REFERENCES competition(id) ON DELETE CASCADE,
  UNIQUE (team_id,competition_id)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS image_player (
  id varchar(11) NOT NULL PRIMARY KEY,
  image mediumblob NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS incident (
  id int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  user_id int(11) REFERENCES user(id) ON DELETE CASCADE,
  player varchar(80) NOT NULL,
  club_id int(11) DEFAULT NULL REFERENCES club(id) ON DELETE CASCADE,
  matchcard_id int(11) DEFAULT NULL REFERENCES matchcard_id ON DELETE SET NULL,
  date timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  type enum('Played','Red Card','Yellow Card','Ineligible','Scored','Missing','Postponed','Other','Locked','Reversed','Signed','Number','Late') NOT NULL,
  detail text,
  resolved tinyint(1) NOT NULL DEFAULT '0',
  archived tinyint(1) NOT NULL DEFAULT '0',
  jdoc TEXT NULL DEFAULT NULL
) ENGINE=InnoDB  DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS matchcard (
  id int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  fixture_id int(11),
  description varchar(100) NOT NULL,
  competition_id int(11) DEFAULT NULL REFERENCES competition(id) ON DELETE CASCADE,
  home_id int(11) DEFAULT NULL REFERENCES team(id) ON DELETE CASCADE,
  away_id int(11) DEFAULT NULL REFERENCES team(id) ON DELETE CASCADE,
  contact_id int(11) NOT NULL REFERENCES user(id) ON DELETE CASCADE,
  date timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  cards tinyint(1) NOT NULL DEFAULT '0',
  hidden tinyint(1) NOT NULL DEFAULT '0',
  ecard tinyint(1) NOT NULL DEFAULT '0',
  open tinyint(1) NOT NULL DEFAULT '1'
) ENGINE=InnoDB  DEFAULT CHARSET=latin1  ;

CREATE TABLE IF NOT EXISTS team (
  id int(10) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  club_id int(11) NOT NULL REFERENCES club(id) ON DELETE CASCADE,
  name varchar(8) NOT NULL,
  UNIQUE (club_id,name),
  INDEX (club_id)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1  ;

CREATE TABLE IF NOT EXISTS `user` (
  id int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  username varchar(128) NOT NULL,
  password varchar(255) NOT NULL,
  role enum('admin','user','manager','secretary','umpire') NOT NULL,
  club_id int(11) DEFAULT NULL REFERENCES club(id) ON DELETE CASCADE,
  email varchar(128) NOT NULL,
  `group` INT(11),
  last_login VARCHAR(255),
  login_hash VARCHAR(255),
  UNIQUE (username)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1  ;

CREATE TABLE IF NOT EXISTS task ( 
	id INT(10) NOT NULL AUTO_INCREMENT ,
	command INT(10) NOT NULL , 
	datetime DATETIME NOT NULL , 
	status ENUM('Queued','Success','Failure') NOT NULL DEFAULT 'Queued' , 
	recur ENUM('Quarter','Hour','Day','Week','Month','Year'),
	PRIMARY KEY (id)
) ENGINE = InnoDB;

INSERT INTO user (username, role, password, email, `group`)
VALUES ('admin', 'admin', 'QEcCEs9WqrFRFm8lqene0ilcRJZWCvONIsZfeDsTaYo=', 'user@nomail.com', 100);
