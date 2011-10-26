<?php
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
 * Authentication Resource.
 * @uri /authenticate
 * @uri /authenticate/:token
 * @uri /authentication
 * @uri /authentication/:token
 */
class AuthenticationResource extends AnonymousResource {
	private $backend = null;

	/**
	 * Resource constructor
	 * @param str[] parameters Parameters passed in from the URL as matched from the URI regex
	 */
	function  __construct($parameters) {
		parent::__construct($parameters);

		try {
			switch (PowerDnsConfig::TOKEN_BACKEND) {
			case "PDO":
				$this->backend = new PDOTokenBackend();
				break;
			default:
				$this->backend = new SqliteTokenBackend();
				break;
			}
		} catch (Exception $e) {
			trigger_error($e->message, E_USER_ERROR);
		}
	}


	/**
	 * Corresponds to login.
	 *
	 * Request:
	 *
	 * {
	 * 	"username": <username>,
	 * 	"password": <password>,
	 * 	"local_user": <username>
	 * }
	 *
	 * Response:
	 *
	 * {
	 *      "username": <string>,
	 *      "valid_until": <int>,
	 *      "hash": <string>,
	 *      "token": <string>
	 * }
	 *
	 * Errors:
	 *
	 *   500 - Invalid request or missing username/password.
	 *   403 - Username/password incorrect.
	 *
	 * @access public
	 * @param mixed $request Request parameters
	 * @return Response Authentication Token if successful, error message if false.
	 */
	public function put($request) {
		$response = new FormattedResponse($request);
		$data = $request->parseData();

		if ($data == null) {
			$response->code = Response::BADREQUEST;
			$response->error = "Request body was malformed. Ensure the body is in valid format.";
			return $response;
		}

		if (!isset($data->username) || !isset($data->password)) {
			$response->code = Response::BADREQUEST;
			$response->error = "Username and/or password was missing or invalid. Ensure that the body is in valid format and all required parameters are present.";
			return $response;
		}

		$validator = new AuthenticationValidator($data);

		if (!$validator->validates()) {
			$response->code = Response::BADREQUEST;
			$response->error = $validator->getFormattedErrors();
			return $response;
		}

		$token = new Token();
		$token->username = $data->username;
		$token->password = $data->password;

		$token = $this->backend->createToken($token);

		if ($token == null) {
			$response->code = Response::FORBIDDEN;
			$response->error = "Username and/or password was invalid.";
			return $response;
		}

		$response->code = Response::OK;
		$response->body = $token->toArray();
		$response->log_message = "Token was successfully created.";

		return $response;
	}

	/**
	 * Corresponds to session validation. If the session is valid, the duration is refreshed. If it is 
	 * not, but it does exist, it will be destroyed.
	 *
	 * Response:
	 *
	 * true
	 *
	 * Errors:
	 *
	 *   500 - Missing or token with invalid format.
	 *   403 - Invalid token.
	 *
	 * @access public
	 * @param mixed $request Request parameters
	 * @param string $token Authentication token
	 * @return Response True if session is still valid, error message otherwise.
	 */
	public function post($request, $token = null) {
		$response = new FormattedResponse($request);
		$data = $request->parseData();

		if (empty($token)) {
			$response->code = Response::BADREQUEST;
			$response->error = "Token was missing or invalid.";
			return $response;
		}

		$validator = new AuthenticationValidator();
		$validator->token = $token;

		if (!$validator->validates()) {
			$response->code = Response::BADREQUEST;
			$response->error = $validator->getFormattedErrors();
			return $response;
		}

		$t = $this->backend->refreshToken($token);

		if ($t == null) {
			$response->code = Response::FORBIDDEN;
			$response->error = "Token was invalid.";
			return $response;
		}

		$response->code = Response::OK;
		$response->body = true;
		$response->log_message = "Token was successfully validated.";

		return $response;
	}

	/**
	 * Corresponds to session logout.
	 *
	 * Response:
	 *
	 * true
	 *
	 * Errors:
	 *
	 *   500 - Missing or token with invalid format.
	 *   500 - Could not destroy token.
	 *   403 - Invalid token.
	 *
	 * @access public
	 * @param mixed $request Request parameters
	 * @param string $token Authentication token
	 * @return Response True if session was terminated, error message otherwise.
	 */
	public function delete($request, $token = null) {
		$response = new FormattedResponse($request);
		$data = $request->parseData();

		if (!isset($token)) {
			$response->code = Response::BADREQUEST;
			$response->error = "Token was missing or invalid.";
			return $response;
		}

		$validator = new AuthenticationValidator();
		$validator->token = $token;

		if (!$validator->validates()) {
			$response->code = Response::BADREQUEST;
			$response->error = $validator->getFormattedErrors();
			return $response;
		}

		$t = $this->backend->retrieveToken($token);

		if ($t == null) {
			$response->code = Response::FORBIDDEN;
			$response->body = array("error" => "Token was invalid.");
			return $response;
		}

		if (!$this->backend->destroyToken($t)) {
			$response->code = Response::INTERNALSERVERERROR;
			$response->body = array("error" => "Token could not be destroyed.");
			return $response;
		}

		$response->code = Response::OK;
		$response->body = true;
		$response->log_message = "Token was successfully invalidated.";

		return $response;
	}
}
