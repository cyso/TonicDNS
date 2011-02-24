<?php
/**
 * Anonymous authentication resource
 * @namespace Tonic\Lib
 */
class AnonymousResource extends Resource {
	/**
	 * Execute a request on this resource.
	 * @param Request request
	 * @return Response
	 */
	function exec($request) {
		$response = new FormattedResponse($request);
		# good for debugging, remove this at some point
		$response->addHeader('X-Resource', get_class($this));

		$data = $request->parseData();

		$logger = null;
		if ($data !== null) {
			if (isset($data->username) && !empty($data->username) && isset($data->local_user) && !empty($data->local_user)) {
				$logger = new Logger($request->uri, $request->method, sprintf("%s -> %s", $data->local_user, $data->username));
			} else if (isset($data->username) && !empty($data->username)) {
				$logger = new Logger($request->uri, $request->method, sprintf("Anonymous -> %s", $data->username));
			} else {
				$logger = new Logger($request->uri, $request->method, "Anonymous");
			}
		} else {
			$logger = new Logger($request->uri, $request->method, "Anonymous");
		}

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
				$logger->writeLog($response->error);
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
			$logger->writeLog($response->error);
			return $response;
		}

		$logger->writeLog("Action completed");

		return $response;
	}
}
?>
