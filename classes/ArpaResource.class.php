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
 * Reverse DNS Resource.
 * @uri /arpa
 * @uri /arpa/:identifier
 */
class ArpaResource extends TokenResource {
	/**
	 * Retrieves an existing reverse DNS record.
	 *
	 * If a query is specified in the URL, all records matching the IPs or ranges
	 * are returned. Multiple queries may be specified, and must be comma seperated.
	 *
	 * If an identifier is specified, the reverse DNS record for one IP will be 
	 * retrieved.
	 *
	 * Response:
	 *
	 * [
	 *      {
	 *            "name": <string>,
	 *            "ip": <ip>,
	 *            "reverse_dns": <string>|null,
	 *            "arpa_zone": <string>|null
	 *      },0..n
	 * ]
	 *
	 * Errors (request without identifier):
	 *
	 *   508 - Invalid request, missing required parameters or input validation failed.
	 *   500 - Failed to connect to database or query execution error.
	 *
	 * Errors (request with identifier):
	 *
	 *   508 - Invalid request, missing required parameters or input validation failed.
	 *   500 - Failed to connect to database or query execution error.
	 *   404 - Could not find IP.
	 *
	 * @access public
	 * @param mixed $request Request parameters
	 * @param string $identifier IP address
	 * @return Response DNS zone data if successful, error message otherwise.
	 */
	public function get($request, $identifier = null) {
		$response = new FormattedResponse($request);
		$data = $request->parseData();

		if (empty($identifier)) {
			if ($data == null) {
				return ArpaFunctions::get_all_arpa($response, $out);
			} else {
				$validator = new ArpaValidator($data);

				if (!isset($data->query)) {
					$response->code = Response::BADREQUEST;
					$response->error = "Query was missing or invalid. Ensure that the body is in valid format and all required parameters are present.";
					return $response;
				}

				if (!$validator->validates()) {
					$response->code = Response::BADREQUEST;
					$response->error = $validator->getFormattedErrors();
					return $response;
				}

				return ArpaFunctions::query_arpa($response, $data->query, $out);
			}
		} else {
			$validator = new ArpaValidator();
			$validator->identifier = $identifier;

			if (!$validator->validates()) {
				$response->code = Response::BADREQUEST;
				$response->error = $validator->getFormattedErrors();
				return $response;
			}

			return ArpaFunctions::get_arpa($response, $identifier);
		}
	}

	/**
	 * Inserts a new Arpa record in an existing Arpa zone. The record will be inserted in the
	 * most specific Arpa zone available. If no zone is available, an error is returned. If a
	 * record already exists, an error is returned.
	 *
	 * Request:
	 *
	 * {
	 *     "reverse_dns": <string>,
	 * }
	 *
	 * Response:
	 *
	 * true
	 *
	 * Errors:
	 *
	 *   508 - Invalid request, missing required parameters or input validation failed.
	 *   500 - Failed to connect to database or query execution error.
	 *   409 - Record already exists, or tried to insert a record in a SALVE Arpa zone.
	 *   404 - Could not find suitable zone.
	 *
	 * @access public
	 * @param mixed $request Request parameters
	 * @param string $identifier IP address
	 * @return Response True if request was successful, error message otherwise.
	 */
	public function put($request, $identifier = null) {
		$response = new FormattedResponse($request);
		$data = $request->parseData();

		if ($data == null) {
			$response->code = Response::BADREQUEST;
			$response->error = "Request body was malformed. Ensure the body is in valid format.";
			return $response;
		}

		if (empty($identifier) || !isset($data->reverse_dns)) {
			$response->code = Response::BADREQUEST;
			$response->error = "Identifier and/or reverse_dns were missing or invalid. Ensure that the body is in valid format and all required parameters are present.";
			return $response;
		}

		$validator = new ArpaValidator($data);
		$validator->identifier = $identifier;

		if (!$validator->validates()) {
			$response->code = Response::BADREQUEST;
			$response->error = $validator->getFormattedErrors();
			return $response;
		}

		return ArpaFunctions::create_arpa($response, $identifier, $data->reverse_dns);
	}

	/**
	 * Delete an existing Arpa record.
	 *
	 * Response:
	 *
	 * true
	 *
	 * Errors:
	 *
	 *   508 - Invalid request, missing required parameters or input validation failed.
	 *   500 - Failed to connect to database or query execution error.
	 *   404 - Could not find Arpa zone.
	 *
	 * @access public
	 * @param mixed $request Request parameters
	 * @param string $identifier IP address
	 * @return Response True if zone was deleted, error message otherwise.
	 */
	public function delete($request, $identifier = null) {
		$response = new FormattedResponse($request);

		if (empty($identifier)) {
			$response->code = Response::BADREQUEST;
			$response->error = "Identifier was missing or invalid.";
			return $response;
		}

		$validator = new ArpaValidator();
		$validator->identifier = $identifier;
		if (!$validator->validates()) {
			$response->code = Response::BADREQUEST;
			$response->error = $validator->getFormattedErrors();
			return $response;
		}

		return ArpaFunctions::delete_arpa($response, $identifier);
	}

	/**
	 * Validates a set of Arpa requests.
	 *
	 * Request:
	 *
	 * {
	 *     "arpa": [ {
	 *         "identifier": <string>,
	 *         "reverse_dns": <string>
	 *     },0..n ]
	 * }
	 *
	 * Response:
	 *
	 * true
	 *
	 * Errors:
	 *
	 * 508 - Invalid request, missing required parameters or input validation failed.
	 *
	 * @access public
	 * @param mixed $request Request parameters
	 * @return Response True if record is valid, error message with parse errors otherwise.
	 */
	public function validate($request) {
		$response = new FormattedResponse($request);
		$data = $request->parseData();

		if (empty($data) || !isset($data->arpa) || !is_array($data_arpa)) {
			$response->code = Response::BADREQUEST;
			$response->error = "Request body was malformed. Ensure that all mandatory properties have been set.";
			return $response;
		}

		$output = array();
		$i = 0;
		foreach ($data->arpa as $d) {
			$i++;
			if (!isset($d->identifier) || !isset($d->reverse_dns)) {
				$output[] = sprintf("Missing required parameters in Arpa %d", $i);
				continue;
			}

			$validator = new ArpaValidator($d);

			if (!$validator->validates()) {
				$output[] = sprintf("Validation errors in Arpa %d:", $i);
				$output[] = $validator->getFormattedErrors(false);
			}
			continue;
		}

		if (empty($output)) {
			$response->code = Response::OK;
			$response->body = true;
			$response->log_message = "Arpa records were successfully validated.";
		} else {
			$response->code = Response::BADREQUEST;
			$response->error = implode("\n", $output);
		}

		return $response;
	}
}
?>
