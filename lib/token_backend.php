<?php
/**
 * Interface for a Token backend.
 * @namespace Tonic\Lib
 */
interface TokenBackend {
	/**
	 * Should create a new Token, store it in the backend and return it.
	 * @access public
	 * @param mixed $token Token object with the username and password properties set.
	 * @return mixed Token object.
	 */
	public function createToken($token);

	/**
	 * Should retrieve a Token based on the passed ID.
	 * @access public
	 * @param string $token_id Token ID
	 * @return mixed Token object, or null if it could not be retrieved.
	 */
	public function retrieveToken($token_id);

	/**
	 * Should refresh the duration of the passed Token.
	 * @access public
	 * @param mixed $token Token object.
	 * @return boolean True if successful, false if the Token could not be refreshed.
	 */
	public function refreshToken($token);

	/**
	 * Validate the passed Token object, and determine if it is still valid. Any invalid Token 
	 * found in the backend should be cleaned up by this function.
	 * @access public
	 * @param mixed $token Token object.
	 * @return boolean True if Token is still valid, false if it is not.
	 */
	public function validateToken($token);

	/**
	 * Destroy the given Token object, by invalidating and removing it from the backend.
	 * @access public
	 * @param mixed $token Token object.
	 * @return boolean True if succesful, false if the Token could not be destroyed.
	 */
	public function destroyToken($token);
}

/**
 * Defines the Token object for use with a Token backend.
 * @namespace Tonic\Lib
 */
class Token {
	/**
	 * Username for this Token. Used during createToken.
	 * @access public
	 * @var string
	 */
	public $username;
	/**
	 * Password for this Token. Used during createToken.
	 * @access public
	 * @var string
	 */
	public $password;
	/**
	 * Validity of this Token. If this date lies in the past, the Token is invalid. Used during refreshToken and validateToken.
	 * @access public
	 * @var date
	 */
	public $valid_until;
	/**
	 * Hashed version of the above information. Used during createToken, retrieveToken, refreshToken, validateToken and destroyToken.
	 * @access public
	 * @var string
	 */
	public $hash;

	/**
	 * Default constructor.
	 * @access public
	 */
	public function __construct() { }

	/**
	 * Convenience constructor to completely fill a Token object during construction.
	 * @access public
	 */
	public function __construct($username, $password, $valid_until, $hash) {
		$this->username = $username;
		$this->password = $password;
		$this->valid_until = $valid_until;
		$this->hash = $hash;
	}
}

?>
