CREATE DATABASE IF NOT EXISTS hockey;

USE hockey;

CREATE TABLE IF NOT EXISTS `section` (
  id int(11) NOT NULL AUTO_INCREMENT,
  name varchar(255) NOT NULL,
  active boolean not null default true,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1  ;

CREATE TABLE IF NOT EXISTS club (
  id int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  name varchar(50) NOT NULL,
  UNIQUE(name)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1  ;

CREATE TABLE IF NOT EXISTS `user` (
  id int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  username varchar(128) NOT NULL,
  password varchar(255) NOT NULL,
  club_id int(11) DEFAULT NULL,
  section_id int(11) DEFAULT NULL,
  email varchar(128) DEFAULT NULL,
  `group` INT(11),
  last_login VARCHAR(255),
  login_hash VARCHAR(255),
  UNIQUE (username),
    CONSTRAINT user_club FOREIGN KEY (club_id) REFERENCES club(id) ON DELETE CASCADE,
    CONSTRAINT user_section FOREIGN KEY (section_id) REFERENCES section(id) ON DELETE CASCADE
) ENGINE=InnoDB  DEFAULT CHARSET=latin1  ;

CREATE TABLE IF NOT EXISTS competition (
  id int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  section_id int(11) DEFAULT NULL,
  name varchar(30) NOT NULL,
  `groups` VARCHAR(512) NULL,
  sequence INT NULL,
  manager varchar(128),
  format ENUM('cup','league') NOT NULL DEFAULT 'league',
  teamsize tinyint(3) unsigned DEFAULT NULL,
  teamstars tinyint(3) unsigned DEFAULT NULL,
  UNIQUE (section_id, name),
  CONSTRAINT competition_section FOREIGN KEY (section_id) REFERENCES section(id) ON DELETE CASCADE
) ENGINE=InnoDB  DEFAULT CHARSET=latin1  ;

CREATE TABLE IF NOT EXISTS team (
  id int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  club_id int(11) NOT NULL,
  name varchar(50) NOT NULL,
  section_id int(11) NOT NULL,
  UNIQUE (section_id,club_id,name),
  INDEX (club_id),
    CONSTRAINT team_club FOREIGN KEY (club_id) REFERENCES club(id) ON DELETE CASCADE,
    CONSTRAINT team_section FOREIGN KEY (section_id) REFERENCES section(id) ON DELETE CASCADE
) ENGINE=InnoDB  DEFAULT CHARSET=latin1  ;

CREATE TABLE IF NOT EXISTS image (
  id varchar(11) NOT NULL PRIMARY KEY,
  image mediumblob NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


CREATE TABLE IF NOT EXISTS matchcard (
  id int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  fixture_id int(11),
  description varchar(100) NOT NULL,
  competition_id int(11) DEFAULT NULL,
  home_id int(11) DEFAULT NULL,
  away_id int(11) DEFAULT NULL,
  date timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  cards tinyint(1) NOT NULL DEFAULT '0',
  hidden tinyint(1) NOT NULL DEFAULT '0',
  ecard tinyint(1) NOT NULL DEFAULT '0',
  open tinyint(1) NOT NULL DEFAULT '1',
    CONSTRAINT matchcard_hometeam FOREIGN KEY (home_id) REFERENCES team(id) ON DELETE CASCADE,
    CONSTRAINT matchcard_awayteam FOREIGN KEY (away_id) REFERENCES team(id) ON DELETE CASCADE,
    CONSTRAINT matchcard_competition FOREIGN KEY (competition_id) REFERENCES competition(id) ON DELETE CASCADE
) ENGINE=InnoDB  DEFAULT CHARSET=latin1  ;

CREATE TABLE IF NOT EXISTS incident (
  id int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  user_id int(11),
  player varchar(80),
  club_id int(11) DEFAULT NULL,
  matchcard_id int(11) DEFAULT NULL,
  date timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  type enum('Played','Red Card','Yellow Card','Ineligible','Scored','Missing','Postponed','Other','Locked','Reversed','Signed','Number','Late') NOT NULL,
  detail text,
  resolved tinyint(1) NOT NULL DEFAULT '0',
  archived tinyint(1) NOT NULL DEFAULT '0',
  jdoc TEXT NULL DEFAULT NULL,
    CONSTRAINT incident_club FOREIGN KEY (club_id) REFERENCES club(id) ON DELETE CASCADE,
    CONSTRAINT incident_matchcard FOREIGN KEY (matchcard_id) REFERENCES matchcard(id) ON DELETE CASCADE
) ENGINE=InnoDB  DEFAULT CHARSET=latin1;


CREATE TABLE IF NOT EXISTS team__competition (
  team_id int(11) NOT NULL,
  competition_id int(11) NOT NULL,
  UNIQUE (team_id,competition_id),
    CONSTRAINT tc_team FOREIGN KEY (team_id) REFERENCES team(id) ON DELETE CASCADE,
    CONSTRAINT tc_competition FOREIGN KEY (competition_id) REFERENCES competition(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS task ( 
	id INT(10) NOT NULL AUTO_INCREMENT ,
	command INT(10) NOT NULL , 
	datetime DATETIME NOT NULL , 
	status ENUM('Queued','Success','Failure') NOT NULL DEFAULT 'Queued' , 
	recur ENUM('Quarter','Hour','Day','Week','Month','Year'),
	PRIMARY KEY (id)
) ENGINE = InnoDB;

CREATE TABLE IF NOT EXISTS pins (
  `pin` varchar(4) DEFAULT NULL,
  `hash` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE OR REPLACE VIEW incidents AS
SELECT i.player, i.date, i.type, i.detail,
	c.name club,
	x.name competition,
	s.name section,
	hc.name home_club, h.name home_team,
	ac.name away_club, a.name away_team,
	i.id incident_id,
	m.id matchcard_id,
	i.club_id
FROM incident i
	JOIN club c ON i.club_id = c.id
	JOIN matchcard m ON i.matchcard_id = m.id
	JOIN competition x ON m.competition_id = x.id
	JOIN section s ON x.section_id = s.id
	JOIN team h ON m.home_id = h.id
	JOIN team a ON m.away_id = a.id
	JOIN club hc ON h.club_id = hc.id
	JOIN club ac ON a.club_id = ac.id
WHERE i.resolved = FALSE;

/*
drop table pins;
drop table task;
drop table team__competition;
drop table incident;
drop table matchcard;
drop table team;
drop table image;
drop table competition;
drop table user;
drop table club;
drop table section;
 */
