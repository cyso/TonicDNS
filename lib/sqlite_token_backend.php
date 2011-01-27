<?php
require_once "token_backend.php";
/**
 * Implements the TokenBackend interface using and SQLite database backend. This class will throw exceptions.
 *
 * This backend expects an SQLite3 database with the following structure:
 *
 * CREATE TABLE "tokens" (
 * 	"token_hash" TEXT PRIMARY KEY NOT NULL UNIQUE ,
 * 	"token_valid_until" INTEGER NOT NULL ,
 * 	"token_user_id" INTEGER NOT NULL
 * );
 * CREATE TABLE "users" (
 * 	"user_id" INTEGER PRIMARY KEY NOT NULL UNIQUE ,
 * 	"username" TEXT NOT NULL UNIQUE ,
 * 	"password" TEXT NOT NULL
 * );

 * The database should be available in the /db dir, relative to the root of the Tonic dir.
 *
 * @namespace Token\Lib
 */
class SqliteTokenBackend implements TokenBackend {
	/**
	 * SQLite database location
	 * @access private
	 * @var string
	 */
	private $database_location = "../db/tokens.sqlite";

	/**
	 * SQLite database connection
	 * @access private
	 * @var resource
	 */
	private $connection = null;

	/**
	 * Token secret, used as a HMAC key while generating the Token hash.
	 * @access private
	 * @var string
	 */
	private $secret = "theix8rameijah5bohqu6rohL5Lah6zaidai5aepheekezooroung4RiweiP";

	/**
	 * Default duration for a new Token.
	 * @access private
	 * @var integer
	 */
	private $duration = 60;

	/**
	 * Constructs a new SqliteTokenBackend, and connects to the SQLite database. Throws an Exception on error.
	 * a database connection could not be established.
	 * @access public
	 */
	public function __construct() {
		try {
			$this->connection = new PDO("sqlite:" . $this->database_location);
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
		if (($result = $this->connection->query(sprintf("SELECT user_id FROM users WHERE username = '%s' AND password = '%s' LIMIT 1;", sqlite_escape_string($token->username), sha1($token->password)))) !== false) {
			$results = $result->fetchAll(PDO::FETCH_ASSOC);
			if (count($results) != 1) {
				return null;
			}
			$user = $results[0];
			$token->valid_until = strtotime(sprintf("+%d second", $this->duration));
			$token->hash = hash_hmac("sha1", sprintf("%s:%s", sqlite_escape_string($token->username), $token->password), $this->secret);

			if ($this->refreshToken($token->hash) !== false) {
				unset($token->password);
				return $token;
			}

			$r = $this->connection->exec(sprintf("INSERT INTO tokens (token_hash, token_valid_until, token_user_id) VALUES ('%s', '%s', %d);", $token->hash, $token->valid_until, $user['user_id']));
			if ($r === false || $r !== 1) {
				return null;
			} else {
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
		$tokens = $this->connection->query(sprintf("SELECT token_hash, token_valid_until, username FROM tokens INNER JOIN users ON (token_user_id = user_id) WHERE token_hash = '%s' LIMIT 1;", sqlite_escape_string($token_id)));
		if ($tokens === false) {
			return null;
		}
		$t = $tokens->fetch(PDO::FETCH_ASSOC);
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
		if (strtotime("now") < $token->valid_until) {
			if (!$this->connection->exec(sprintf("UPDATE tokens SET token_valid_until = %d WHERE token_hash = '%s';", strtotime(sprintf("+%d second", $this->duration)), $token->hash))) {
				return false;
			} else {
				return true;
			}
		} else {
			$this->destroyToken($token);
			return false;
		}
	}

	/**
	 * Validates the passed Token object, and determines if it is still valid. If the Token
	 * is invalid, it will be removed from the backend.
	 * @access public
	 * @param mixed $token Token object.
	 * @return boolean True if Token is still valid, false if it is not.
	 */
	public function validateToken($token) {
		if (!($token instanceof Token)) {
			return false;
		}
		if (empty($token->username) || empty($token->valid_until) || empty($token->hash)) {
			return false;
		}
		if (strtotime("now") > $token->valid_until) {
			return true;
		} else {
			$this->connection->exec(sprintf("DELETE FROM tokens WHERE token_hash = '%s';", sqlite_escape_string($token->hash)));
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
		if (!$this->connection->exec(sprintf("DELETE FROM tokens WHERE token_hash = '%s';", sqlite_escape_string($token->hash)))) {
			return false;
		} else {
			return true;
		}
	}

}
?>
