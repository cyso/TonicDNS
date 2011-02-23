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

		if (!isset($request->requestToken) || empty($request->requestToken)) {
			$response->code = Response::UNAUTHORIZED;
			$response->error = "Authorization required";
			$response->addHeader('X-Debug', "No token supplied");

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
			return $response;
		}

		$token = $backend->retrieveToken($request->requestToken);

		if ($token == null) {
			$response->code = Response::FORBIDDEN;
			$response->error = "Authentication failed";
			$response->addHeader('X-Debug', "Token is null");

			return $response;
		}

		if ($backend->validateToken($token) === false) {
			$response->code = Response::FORBIDDEN;
			$response->error = "Authentication failed";
			$response->addHeader('X-Debug', "Token is invalid");

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
		}

		# good for debugging, remove this at some point
		$response->addHeader('X-Resource', get_class($this));

		return $response;
	}
}
?>
