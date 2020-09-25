SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;


CREATE TABLE IF NOT EXISTS `club` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `pin` varchar(4) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1  ;

CREATE TABLE IF NOT EXISTS `code` (
  `code` varchar(8) NOT NULL,
  `target` enum('Competition','Club','Team') NOT NULL,
  `target_id` int(11) NOT NULL,
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS `competition` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(30) NOT NULL,
  `manager` varchar(128) NOT NULL,
  `user_id` int(11) NOT NULL,
  `teamsize` tinyint(3) unsigned DEFAULT NULL,
  `teamstars` tinyint(3) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1  ;

CREATE TABLE IF NOT EXISTS `entry` (
  `team_id` int(11) NOT NULL,
  `competition_id` int(11) NOT NULL,
  UNIQUE KEY `unique_entry` (`team_id`,`competition_id`),
  KEY `team_id` (`team_id`),
  KEY `competition_id` (`competition_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS `image` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `matchcard_id` int(11) DEFAULT NULL,
  `image_mimetype` varchar(30) NOT NULL,
  `image` mediumblob NOT NULL,
  PRIMARY KEY (`id`),
  KEY `matchcard_id` (`matchcard_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=2000 ;

CREATE TABLE IF NOT EXISTS `image_player` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(80) NOT NULL,
  `club_id` int(11) NOT NULL,
  `image` mediumblob NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=278 ;

CREATE TABLE IF NOT EXISTS `incident` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `player` varchar(80) NOT NULL,
  `club_id` int(11) DEFAULT NULL,
  `matchcard_id` int(11) DEFAULT NULL,
  `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `type` enum('Played','Red Card','Yellow Card','Ineligible','Scored','Missing','Postponed','Other','Locked','Reversed','Signed','Number','Late') NOT NULL,
  `detail` text,
  `resolved` tinyint(1) NOT NULL DEFAULT '0',
  `archived` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `fk_matchcard` (`matchcard_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=69359 ;

CREATE TABLE IF NOT EXISTS `matchcard` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `description` varchar(100) NOT NULL,
  `competition_id` int(11) DEFAULT NULL,
  `home_id` int(11) DEFAULT NULL,
  `away_id` int(11) DEFAULT NULL,
  `contact_id` int(11) NOT NULL,
  `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `cards` tinyint(1) NOT NULL DEFAULT '0',
  `hidden` tinyint(1) NOT NULL DEFAULT '0',
  `ecard` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `competition_id` (`competition_id`),
  KEY `home_id` (`home_id`),
  KEY `away_id` (`away_id`),
  KEY `contact_id` (`contact_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1  ;

CREATE TABLE IF NOT EXISTS `registration` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `firstname` varchar(30) NOT NULL,
  `lastname` varchar(30) NOT NULL,
  `sequence` int(11) NOT NULL,
  `club_id` int(11) NOT NULL,
  `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `team_id` int(11) DEFAULT '-1',
  `batch` int(11) NOT NULL DEFAULT '-1',
  PRIMARY KEY (`id`),
  KEY `club_id` (`club_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1  ;

CREATE TABLE IF NOT EXISTS `team` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `club_id` int(11) NOT NULL,
  `team` varchar(8) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_team` (`club_id`,`team`),
  KEY `club_id` (`club_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1  ;

CREATE TABLE IF NOT EXISTS `user` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(128) NOT NULL,
  `password` varchar(32) NOT NULL,
  `role` enum('admin','user','manager','secretary','umpire') NOT NULL,
  `club_id` int(11) DEFAULT NULL,
  `email` varchar(128) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1  ;


ALTER TABLE `entry`
  ADD CONSTRAINT `entry_ibfk_1` FOREIGN KEY (`competition_id`) REFERENCES `competition` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `entry_ibfk_2` FOREIGN KEY (`team_id`) REFERENCES `team` (`id`) ON DELETE CASCADE;

ALTER TABLE `image`
  ADD CONSTRAINT `image_ibfk_1` FOREIGN KEY (`matchcard_id`) REFERENCES `matchcard` (`id`) ON DELETE CASCADE;

ALTER TABLE `matchcard`
  ADD CONSTRAINT `matchcard_ibfk_1` FOREIGN KEY (`competition_id`) REFERENCES `competition` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `matchcard_ibfk_2` FOREIGN KEY (`home_id`) REFERENCES `team` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `matchcard_ibfk_3` FOREIGN KEY (`away_id`) REFERENCES `team` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `matchcard_ibfk_4` FOREIGN KEY (`contact_id`) REFERENCES `user` (`id`) ON DELETE CASCADE;

ALTER TABLE `registration`
  ADD CONSTRAINT `registration_ibfk_1` FOREIGN KEY (`club_id`) REFERENCES `club` (`id`) ON DELETE CASCADE;

ALTER TABLE `team`
  ADD CONSTRAINT `team_ibfk_1` FOREIGN KEY (`club_id`) REFERENCES `club` (`id`) ON DELETE CASCADE;

ALTER TABLE competition ADD code VARCHAR( 10 ) NOT NULL AFTER id;
ALTER TABLE competition ADD sequence INT NOT NULL;
ALTER TABLE competition ADD UNIQUE (code);
ALTER TABLE competition ADD UNIQUE (name);

ALTER TABLE club ADD code VARCHAR( 10 ) NOT NULL AFTER id;
ALTER TABLE club ADD UNIQUE (code);
ALTER TABLE club ADD UNIQUE (name);

ALTER TABLE incident ADD user_id INT NULL;

ALTER TABLE matchcard ADD fixture_id INT NULL AFTER id;

ALTER TABLE incident MODIFY type enum('Played','Red Card','Yellow Card','Ineligible','Scored','Missing','Postponed','Other','Locked','Reversed','Signed','Number','Late','Registered') NOT NULL;

ALTER TABLE user MODIFY role enum('admin','user','manager','secretary','umpire') NOT NULL;

DROP VIEW IF EXISTS incidents;

CREATE VIEW incidents AS
SELECT i.id AS id,i.date AS date,m.id AS Matchcard,
	c.name AS Competition,
	concat(ch.name,' ',th.team) AS Home,
	concat(ca.name,' ',ta.team) AS Away,
	i.player AS player,cc.name AS club,
	i.type AS type,i.detail AS detail,
	u.username,
	u.role 
FROM incident i 
	LEFT JOIN matchcard m on i.matchcard_id = m.id
	LEFT JOIN competition c on m.competition_id = c.id
	LEFT JOIN team th on m.home_id = th.id
	LEFT JOIN team ta on m.away_id = ta.id
	LEFT JOIN club cc on i.club_id = cc.id
	LEFT JOIN club ch on th.club_id = ch.id
	LEFT JOIN club ca on ta.club_id = ca.id
	LEFT JOIN user u on i.user_id = u.id
WHERE i.resolved = 0;

CREATE TABLE IF NOT EXISTS config (
  identifier char(100) NOT NULL,
  config longtext NOT NULL,
  hash char(13) NOT NULL,
  PRIMARY KEY (identifier)
);

CREATE TABLE IF NOT EXISTS task ( 
	id INT(10) NOT NULL AUTO_INCREMENT ,
	command INT(10) NOT NULL , 
	datetime DATETIME NOT NULL , 
	status ENUM('Queued','Success','Failure') NOT NULL DEFAULT 'Queued' , 
	recur VARCHAR(100) NULL , 
	PRIMARY KEY (`id`)
) ENGINE = InnoDB;

ALTER TABLE task CHANGE recur recur ENUM('Quarter','Hour','Day','Week','Month','Year') CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL;

ALTER TABLE registration ADD `batch_date` datetime DEFAULT NULL;
ALTER TABLE matchcard ADD `open` tinyint(1) DEFAULT 1;
ALTER TABLE competition ADD format ENUM('cup','league') NOT NULL DEFAULT 'league';
ALTER TABLE competition ADD groups VARCHAR(128) NULL;

ALTER TABLE `user` ADD `old_password` VARCHAR(50);
ALTER TABLE `user` ADD `last_login` VARCHAR(25);
ALTER TABLE `user` ADD `login_hash` VARCHAR(255);
ALTER TABLE `user` ADD `group` INT(11);
ALTER TABLE `user` MODIFY `password` VARCHAR(255);

UPDATE `user` SET `group` = CASE
		WHEN role = 'umpire' THEN 2
		WHEN role = 'admin' THEN 99
		WHEN role = 'secretary' THEN 25
		WHEN role IS NULL THEN 25
		ELSE 1
	END,
	old_password = password
WHERE old_password IS NULL;

ALTER TABLE `user` ADD `pin` VARCHAR(4) NULL AFTER `password`;
UPDATE `user` SET pin = old_password WHERE role IN ('umpire', 'user') AND pin IS NULL;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
