<?php
require_once "token_backend.php";
/**
 * Copyright (c) 2011 Cyso Managed Hosting < development [at] cyso . nl >
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
/**
 * Implements the TokenBackend interface using PDO. This class will throw exceptions.
 *
 * This class will use the connection string and credentials from the database.config.php
 * file in /conf.
 *
 * This backend expects a database with the following structure, compatible with PowerAdmin:
 *
 * CREATE TABLE `tokens` (
 *   `token_hash` varchar(40) NOT NULL,
 *   `token_valid_until` int(11) NOT NULL,
 *   `token_user_id` int(11) NOT NULL,
 *   PRIMARY KEY (`token_hash`),
 *   KEY `token_user_id` (`token_user_id`)
 * ) ENGINE=InnoDB DEFAULT CHARSET=latin1;
 *
 * CREATE TABLE `users` (
 *   `id` int(11) NOT NULL AUTO_INCREMENT,
 *   `username` varchar(16) NOT NULL,
 *   `password` varchar(34) NOT NULL,
 *   `fullname` varchar(255) NOT NULL,
 *   `email` varchar(255) NOT NULL,
 *   `description` varchar(1024) NOT NULL,
 *   `perm_templ` tinyint(4) NOT NULL DEFAULT '0',
 *   `active` tinyint(4) NOT NULL DEFAULT '0',
 *   PRIMARY KEY (`id`)
 * ) ENGINE=InnoDB  DEFAULT CHARSET=latin1;
 *
 * ALTER TABLE `tokens`
 *   ADD CONSTRAINT `tokens_ibfk_1` FOREIGN KEY (`token_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
 *
 * @namespace Token\Lib
 */
class PDOTokenBackend implements TokenBackend {
	/**
	 * SQLite database connection
	 * @access private
	 * @var resource
	 */
	private $connection = null;

	/**
	 * Constructs a new PDOTokenBackend, and connects to the database. Throws an Exception on error.
	 * a database connection could not be established.
	 * @access public
	 */
	public function __construct() {
		try {
			$this->connection = Database::getConnection();
		} catch (Exception $e) {
			throw new Exception("Failed to open database connection");
		}
	}

	/**
	 * Create a new Token, stores it in the backend and returns it.
	 * @access public
	 * @param mixed $token Token object with the username and password properties set.
	 * @return mixed Token object with valid_until date and a token hash set. Null if an error occurred.
	 */
	public function createToken($token) {
		if (!($token instanceof Token)) {
			return null;
		}
		if (empty($token->username) || empty($token->password)) {
			return null;
		}
		$stat1 = $this->connection->prepare(sprintf(
			"SELECT id FROM `%s` WHERE username = :username AND password = :password LIMIT 1;", PowerDnsConfig::DB_USER_TABLE
		));
		if (($result = $stat1->execute(array(":username" => $token->username, ":password" => md5($token->password)))) !== false) {
			$results = $stat1->fetchAll(PDO::FETCH_ASSOC);
			if (count($results) != 1) {
				return null;
			}
			$user = $results[0];
			$token->valid_until = strtotime(sprintf("+%d second", PowerDnsConfig::TOKEN_DEFAULT_DURATION));
			$token->valid_duration = PowerDnsConfig::TOKEN_DEFAULT_DURATION;
			$token->hash = hash_hmac("sha1", sprintf("%s:%s", $token->username, $token->password), PowerDnsConfig::TOKEN_SECRET);

			if ($this->refreshToken($token->hash) !== false) {
				unset($token->password);
				return $token;
			}
			$stat1->closeCursor();

			$this->connection->beginTransaction();

			$stat2 = $this->connection->prepare(sprintf(
				"INSERT INTO `%s` (token_hash, token_valid_until, token_user_id) VALUES (:hash, :valid, :user);", PowerDnsConfig::DB_TOKEN_TABLE
			));
			$r = $stat2->execute(array(":hash" => $token->hash, ":valid" => $token->valid_until, ":user" => $user['id']));
			if ($r === false || $stat2->rowCount() !== 1) {
				$this->connection->rollback();
				return null;
			} else {
				$this->connection->commit();
				unset($token->password);
				return $token;
			}
		} else {
			return null;
		}
	}

	/**
	 * Retrieve a Token based on the passed ID (Token hash).
	 * @access public
	 * @param string $token_id Token ID
	 * @return mixed Token object, or null if it could not be retrieved.
	 */
	public function retrieveToken($token_id) {
		$stat = $this->connection->prepare(sprintf(
			"SELECT token_hash, token_valid_until, username FROM `%s` INNER JOIN `%s` ON (token_user_id = id) WHERE token_hash = :hash LIMIT 1;", PowerDnsConfig::DB_TOKEN_TABLE, PowerDnsConfig::DB_USER_TABLE
		));
		if ($stat->execute(array(":hash" => $token_id)) === false) {
			return null;
		}
		$t = $stat->fetch(PDO::FETCH_ASSOC);
		if ($t === false) {
			return null;
		}
		$token = new Token($t['username'], null, $t['token_valid_until'], $t['token_hash']);
		return $token;
	}

	/**
	 * Refreshes the duration of the passed Token. If the Token is expired, destroy it.
	 * @access public
	 * @param string $token_id Token ID.
	 * @return boolean True if successful, false if the Token could not be refreshed.
	 */
	public function refreshToken($token_id) {
		$token = $this->retrieveToken($token_id);
		if ($token == null) {
			return false;
		}
		return $this->validateToken($token, true);
	}

	/**
	 * Validates the passed Token object, and determines if it is still valid. If the Token
	 * is invalid, it will be removed from the backend.
	 * @access public
	 * @param mixed $token Token object.
	 * @return boolean True if Token is still valid, false if it is not.
	 */
	public function validateToken($token, $refresh = false) {
		if (!($token instanceof Token)) {
			return false;
		}
		if (empty($token->username) || empty($token->valid_until) || empty($token->hash)) {
			return false;
		}
		if (strtotime("now") < $token->valid_until) {
			if (!$refresh) {
				return true;
			} else {
				$this->connection->beginTransaction();
				$stat = $this->connection->prepare(sprintf(
					"UPDATE `%s` SET token_valid_until = :valid WHERE token_hash = :hash;", PowerDnsConfig::DB_TOKEN_TABLE
				));
				if ($stat->execute(array(":valid" => strtotime(sprintf("+%d second", PowerDnsConfig::TOKEN_DEFAULT_DURATION)), ":hash" => $token->hash)) === false) {
					$this->connection->rollback();
					return false;
				} else {
					$this->connection->commit();
					return true;
				}
			}
		} else {
			$this->destroyToken($token);
			return false;
		}
	}

	/**
	 * Destroys the given Token object, by invalidating and removing it from the backend.
	 * @access public
	 * @param mixed $token Token object.
	 * @return boolean True if succesful, false if the Token could not be destroyed.
	 */
	public function destroyToken($token) {
		if (!($token instanceof Token)) {
			return false;
		}
		if (empty($token->username) || empty($token->valid_until) || empty($token->hash)) {
			return false;
		}

		$this->connection->beginTransaction();
		$stat = $this->connection->prepare(sprintf(
			"DELETE FROM `%s` WHERE token_hash = :hash;", PowerDnsConfig::DB_TOKEN_TABLE
		));
		if ($stat->execute(array(":hash" => $token->hash)) === false) {
			$this->connection->rollback();
			return false;
		} else {
			$this->connection->commit();
			return true;
		}
	}

}
?>
