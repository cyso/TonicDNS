<?php
/**
 * Token authentication enabled Resource
 * @namespace Tonic\Lib
 */
class TokenResource extends Resource {
	/**
	 * Execute a request on this resource.
	 * @param Request request
	 * @return Response
	 */
	function exec($request) {
		$response = new FormattedResponse($request);
		# good for debugging, remove this at some point
		$response->addHeader('X-Resource', get_class($this));

		$logger = new Logger($request->uri, $request->method, "Anonymous");
		$logger->setInput(json_encode($request->parseData()));

		if (!isset($request->requestToken) || empty($request->requestToken)) {
			$response->code = Response::UNAUTHORIZED;
			$response->error = "Authorization required";
			$response->addHeader('X-Debug', "No token supplied");
			$logger->writeLog($response->error, $response->code);

			return $response;
		}

		$backend = null;
		try {
			switch (PowerDnsConfig::TOKEN_BACKEND) {
			case "PDO":
				$backend = new PDOTokenBackend();
				break;
			default:
				$backend = new SqliteTokenBackend();
				break;
			}
		} catch (Exception $e) {
			$response->code = Response::INTERNALSERVERERROR;
			$response->error = $e->getMessage();
			$logger->writeLog($response->error, $response->code);

			return $response;
		}

		$token = $backend->retrieveToken($request->requestToken);

		if ($token == null) {
			$response->code = Response::FORBIDDEN;
			$response->error = "Authentication failed";
			$response->addHeader('X-Debug', "Token is null");
			$logger->writeLog($response->error, $response->code);

			return $response;
		}

		$logger->setUser($token->username);

		if ($backend->validateToken($token) === false) {
			$response->code = Response::FORBIDDEN;
			$response->error = "Authentication failed";
			$response->addHeader('X-Debug', "Token is invalid");
			$logger->writeLog($response->error, $response->code);

			return $response;
		}

		$backend->refreshToken($token->hash);

		if (method_exists($this, $request->method)) {
			$parameters = $this->parameters;
			array_unshift($parameters, $request);

			try {
				$response = call_user_func_array(
					array($this, $request->method),
					$parameters
				);
			} catch (Exception $e) {
				$response->code = Response::INTERNALSERVERERROR;
				$response->error = $e;
				$logger->writeLog($response->error, $response->code);
				return $response;
			}
		} else {
			// send 405 method not allowed
			$response->code = Response::METHODNOTALLOWED;
			$response->error = sprintf(
				'The HTTP method "%s" used for the request is not allowed for the resource "%s".',
				$request->method,
				$request->uri
			);
			$logger->writeLog($response->error, $response->code);
			return $response;
		}

		$logger->setOutput(json_encode($response->body));
		if (!empty($response->error)) {
			$logger->writeLog($response->error, $response->code);
		} else if (!empty($response->log_message)) {
			$logger->writeLog($response->log_message, $response->code);
		} else {
			$logger->writeLog("Action completed", $response->code);
		}

		return $response;
	}
}
?>
