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

function site() {
	$site = null;

	if ($site == null && isset($_REQUEST['site'])) {
		$site = $_REQUEST['site'];
	}

	if ($site == null && isset($_COOKIE['site'])) {
		$site = $_COOKIE['site'];
	}

	if ($site == "") $site = null;
	
	if (!$site) {
		return false;
	}

	return $site;
}

function getConfig($config, $section, $setting, $default = null) {
	if (!isset($config[$section])) return $default;
	if (!isset($config[$section][$setting])) return $default;

	return $config[$section][$setting];
}

if (site() and !defined('DB_DATABASE')) {
	$root = dirname(__FILE__);
	$configFile = $root.'/sites/'.site().'/config.ini';
	$config = parse_ini_file($configFile, true);

	//echo "<!-- Config: $configFile\n".print_r($config, true)." -->";

	define("TITLE", getConfig($config,'main','title'));
	define("HASH_TEMPLATE", getConfig($config,'main','hashtemplate'));
	define("ADMIN_CODE", getConfig($config,'users','admin.code'));
	define("ADMIN_EMAIL", getConfig($config,'users','admin.email'));
	define("REPORT_CC", getConfig($config,'main','report.admin'));
	define("AUTO_EMAIL", getConfig($config,'automation','email'));
	define("AUTO_PASSWORD", getConfig($config,'automation','password'));
	define("DB_DATABASE", getConfig($config,'database','database'));
	define("DB_USERNAME", getConfig($config,'database','username'));
	define("DB_PASSWORD", getConfig($config,'database','password'));
	define("SITE_NAME", getConfig($config,'main','title'));
	define("FIXTURE_FEED", implode("\n", getConfig($config,'main','fixturefeed', array())));
	define("STRICT", implode("\n", getConfig($config,'main','strict', array())));
	define("EXPLICIT_TEAMS", getConfig($config,'main','allowassignment', true));
	define("UPLOAD_FORMAT", getConfig($config,'main','uploadformat'));
} else {
	define("TITLE", "");
}
