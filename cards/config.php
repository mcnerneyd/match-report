<?php
/* ---------------------------------------------------------------------
	Team Registration Administration System - Hockey (TRASH)
	Copyright (C) 2014  David McNerney

	This program is free software: you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation, either version 3 of the License, or
	(at your option) any later version.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program.  If not, see http://www.gnu.org/licenses/.
--------------------------------------------------------------------- */


/*
function getConfig($config, $section, $setting = null, $default = null) {
	if (!$config) return $default;
	if (!isset($config[$section])) return $default;
	if ($setting == null) {
		return $config[$section];
	}

	if (!isset($config[$section][$setting])) return $default;

	return $config[$section][$setting];
}
*/

/*if (site() and !defined('DB_DATABASE')) {
	$configFile = DATAPATH.'/sites/'.site().'/config.json';
	$config = json_decode(file($configFile));

	define("TITLE", getConfig($config,'title'));
	define("HASH_TEMPLATE", getConfig($config,'hashtemplate'));
	define("ADMIN_CODE", getConfig($config,'users','admin.code'));
	define("ADMIN_EMAIL", getConfig($config,'users','admin.email'));
	define("REPORT_CC", getConfig($config,'main','report.admin'));
	define("AUTO_EMAIL", getConfig($config,'automation','email'));
	define("AUTO_PASSWORD", getConfig($config,'automation','password'));
	define("DB_DATABASE", getConfig($config,'database','database'));
	define("DB_USERNAME", getConfig($config,'database','username'));
	define("DB_PASSWORD", getConfig($config,'database','password'));
	define("SITE_NAME", getConfig($config,'title'));
	define("FIXTURE_FEED", implode("\n", getConfig($config,'main','fixturefeed', array())));
	define("STRICT", implode("\n", getConfig($config,'main','strict', array())));
	define("EXPLICIT_TEAMS", getConfig($config,'main','allowassignment', true));
	define("UPLOAD_FORMAT", getConfig($config,'main','uploadformat'));
} else {
	define("TITLE", "");
}*/

