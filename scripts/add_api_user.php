#!/usr/bin/env php
<?php
/**
 * Copyright (c) 2012 Cyso Managed Hosting < development [at] cyso . nl >
 *
 * This file is part of TonicDNS.
 *
 * TonicDNS is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * TonicDNS is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with TonicDNS.  If not, see <http://www.gnu.org/licenses/>.
 */

require_once(dirname(__FILE__) . "/../conf/database.conf.php");
require_once(dirname(__FILE__) . "/../classes/Database.class.php");

function usage() {
	printf("\n");
	printf("Usage: add_api_user.php <username> <password> <email> <fullname> <description>\n");
	printf("\n");
	printf("If the user does not exist, it will be added. If it does exist, it will be updated\n");
}

try {
	$db = Database::getConnection();
} catch (PDOException $e) {
	printf("An error occured while connecting to the database.\n");
	exit(1);
}

if (count($argv) != 6) {
	usage();
	exit(1);
}

$stat = $db->prepare(sprintf(
	"SELECT username
	FROM `%s`
	WHERE username = :user;", PowerDNSConfig::DB_USER_TABLE
	)
);

if ($stat === false || $stat->execute(array(":user" => $argv[1])) === false) {
	printf("Failed to execute check SQL statement\n");
	exit(1);
}

$exists = $stat->fetchAll();
$update = false;

if (count($exists) > 0) {
	printf("User already exists, updating...\n");
	$update = true;
}

$stat = null;
if ($update) {
	$stat = $db->prepare(sprintf(
		"UPDATE `%s`
		SET 
			username = :username, 
			password = :password,
			fullname = :fullname,
			email = :email, 
			description = :description
		WHERE
			username = :username;", PowerDNSConfig::DB_USER_TABLE
		)
	);
} else {
	$stat = $db->prepare(sprintf(
		"INSERT INTO `%s`
			(username, password, fullname, email, description, perm_templ, active)
		VALUES
			(:username, :password, :fullname, :email, :description, 1, 1);", PowerDNSConfig::DB_USER_TABLE
		)
	);
}

if ($stat === false || $stat->execute(array(":username" => $argv[1], ":password" => md5($argv[2]), ":fullname" => $argv[4], ":email" => $argv[3], ":description" => $argv[5])) === false) {
	printf("Failed to execute insert SQL statement\n");
	exit(1);
}

printf("User added\n");
exit(0);

?>
