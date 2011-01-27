<?php
/**
 * Authentication Resource.
 * @uri /authenticate
 * @uri /authentication
 */
class AuthenticationResource extends Resource {
	/**
	 * Corresponds to login.
	 *
	 * {
	 * 	"username": <username>,
	 * 	"password": <password>
	 * }
	 *
	 * @access public
	 * @param mixed $request Request parameters
	 * @return Response Authentication Token if successful, error message if false.
	 */
	public function put($request) {
		$response = new Response($request);
		$body = $request->data;
		$json = json_decode($body);

		if ($json == null) {
			$response->code = Response::BADREQUEST;
			$response->body = json_encode("Request body was malformed. Ensure the body is in valid JSON format.");
			return $response;
		}

		if (!isset($json->username) || !isset($json->password)) {
			$response->code = Response::BADREQUEST;
			$response->body = json_encode("Username and/or password was missing or invalid. Ensure that the body is in valid JSON format and all required parameters are present.");
			return $response;
		}

		$token = new Token();
		$token->username = $json->username;
		$token->password = $json->password;

		$backend = new SqliteTokenBackend();
		$token = $backend->createToken($token);

		if ($token == null) {
			$response->code = Response::FORBIDDEN;
			$response->body = json_encode("Username and/or password was invalid.");
			return $response;
		}

		$response->body = json_encode($token);

		return $response;
	}

	/**
	 * Corresponds to session validation. If the session is valid, the duration is refreshed. If it is 
	 * not, but it does exist, it will be destroyed.
	 *
	 * {
	 * 	"token": <token>
	 * }
	 *
	 * @access public
	 * @param mixed $request Request parameters
	 * @return Response True if session is still valid, false otherwise.
	 */
	public function post($request) {
		$response = new Response($request);
		$body = $request->data;
		$json = json_decode($body);

		if ($json == null) {
			$response->code = Response::BADREQUEST;
			$response->body = json_encode("Request body was malformed. Ensure the body is in valid JSON format.");
			return $response;
		}

		if (!isset($json->token)) {
			$response->code = Response::BADREQUEST;
			$response->body = json_encode("Token was missing or invalid. Ensure that the body is in valid JSON format and all required parameters are present.");
			return $response;
		}

		$backend = new SqliteTokenBackend();
		$token = $backend->refreshToken($json->token);

		if ($token == null) {
			$response->code = Response::FORBIDDEN;
			$response->body = json_encode("Token was invalid.");
			return $response;
		}

		$response->body = json_encode(true);

		return $response;
	}

	/**
	 * Corresponds to session logout.
	 *
	 * {
	 * 	"token": <token>
	 * }
	 *
	 * @access public
	 * @params mixed $request Request parameters
	 * @return Response True if session was terminated, false otherwise.
	 */
	public function delete($request) {
		$response = new Response($request);
		$body = $request->data;
		$json = json_decode($body);

		if ($json == null) {
			$response->code = Response::BADREQUEST;
			$response->body = json_encode("Request body was malformed. Ensure the body is in valid JSON format.");
			return $response;
		}

		if (!isset($json->token)) {
			$response->code = Response::BADREQUEST;
			$response->body = json_encode("Token was missing or invalid. Ensure that the body is in valid JSON format and all required parameters are present.");
			return $response;
		}

		$backend = new SqliteTokenBackend();
		$token = $backend->retrieveToken($json->token);

		if ($token == null) {
			$response->code = Response::FORBIDDEN;
			$response->body = json_encode("Token was invalid.");
			return $response;
		}

		if (!$backend->destroyToken($token)) {
			$response->code = Response::INTERNALSERVERERROR;
			$response->body = json_encode("Token could not be destroyed.");
			return $response;
		}

		$response->body = json_encode(true);

		return $response;
	}
}
