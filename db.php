<?php
function getCommonsDB() {
	$ts_pw = posix_getpwuid(posix_getuid());
	$ts_mycnf = parse_ini_file($ts_pw['dir'] . "/replica.my.cnf");
	$mysqli = new mysqli('commonswiki.analytics.db.svc.wikimedia.cloud', $ts_mycnf['user'], $ts_mycnf['password'], 'commonswiki_p');
	return $mysqli;
}
