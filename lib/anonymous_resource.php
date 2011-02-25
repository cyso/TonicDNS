<?php
/**
 * Anonymous authentication resource
 * @namespace Tonic\Lib
 */
class AnonymousResource extends Resource {
	/**
	 * Resource constructor
	 * @param str[] parameters Parameters passed in from the URL as matched from the URI regex
	 */
	function  __construct($parameters) {
		parent::__construct($parameters);
	}

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

		$logger->setInput(json_encode($data));

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
			$logger->writeLog($response->error, $resonse->code);
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
