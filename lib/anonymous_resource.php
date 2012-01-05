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
				$response->error_detail = "INTERNAL_SERVER_ERROR";
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
			$response->error_detail = "METHOD_NOT_ALLOWED";
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
