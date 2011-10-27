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
	 * Validates a set of records.
	 *
	 * Request:
	 *
	 * {
	 * 		entries: [ {
	 *              "name": <string>,
	 *              "type": <string>,
	 *              "content": <string>,
	 *              "ttl": <int optional>,
	 *              "priority: <int optional>,
	 *              "change_date": <int optional>
	 *      },0..n ],
	 * 		records: [ {
	 *              "name": <string>,
	 *              "type": <string>,
	 *              "content": <string>,
	 *              "ttl": <int optional>,
	 *              "priority: <int optional>,
	 *              "change_date": <int optional>
	 *      },0..n ]
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
	public function post($request) {
		$response = new FormattedResponse($request);
		$data = $request->parseData();

		if (empty($data) || (!isset($data->entries) && !isset($data->records))) {
			$response->code = Response::BADREQUEST;
			$response->error = "Request body was malformed. Ensure that all mandatory properties have been set.";
			return $response;
		}


		if (isset($data->entries) && is_array($data->entries)) {
			$output = array();
			$i = 0;
			foreach ($data->entries as $d) {
				$i++;
				if (!isset($d->name) || !isset($d->type) || !isset($d->content)) {
					$output[] = sprintf("Missing required parameters in entry %d", $i);
					continue;
				}

				$validator = new RecordValidator();
				$validator->initialize($d);
				$validator->record_type = "TEMPLATE";

				if (!$validator->validates()) {
					$output[] = sprintf("Validation errors in entry %d:", $i);
					$output[] = $validator->getFormattedErrors(false);
				}
				continue;
			}
		}

		if (isset($data->records) && is_array($data->entries)) {
			$output = array();
			$i = 0;
			foreach ($data->records as $d) {
				$i++;
				if (!isset($d->name) || !isset($d->type) || !isset($d->content)) {
					$output[] = sprintf("Missing required parameters in record %d", $i);
					continue;
				}

				$validator = new RecordValidator();
				$validator->initialize($d);

				if (!$validator->validates()) {
					$output[] = sprintf("Validation errors in record %d:", $i);
					$output[] = $validator->getFormattedErrors(false);
				}
				continue;
			}
		}

		if (empty($output)) {
			$response->code = Response::OK;
			$response->body = true;
			$response->log_message = "Records were successfully validated.";
		} else {
			$response->code = Response::BADREQUEST;
			$response->error = implode("\n", $output);
		}

		return $response;
	}
}
