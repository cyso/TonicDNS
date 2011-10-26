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
 * Record Resource.
 * @uri /record
 */
class RecordResource extends TokenResource {
	/**
	 * Validates a single record.
	 *
	 * Response:
	 *
	 * true
	 *
	 * @access public
	 * @param mixed $request Request parameters
	 * @return Response True if record is valid, error message with parse errors otherwise.
	 */
	public function post($request) {
		$response = new FormattedResponse($request);
		$data = $request->parseData();

		if (empty($data)) {
			$response->code = Response::BADREQUEST;
			$response->error = "Request body was malformed. Ensure that all mandatory properties have been set.";
			return $response;
		}

		$validator = new RecordValidator();
		$validator->initialize($data);

		if (!$validator->validates()) {
			$response->code = Response::BADREQUEST;
			$response->error = $validator->getFormattedErrors();
			return $response;
		}

		$response->code = Response::OK;
		$response->body = true;
		$response->log_message = "Record was successfully validated.";

		return $response;
	}
}
