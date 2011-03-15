<?php
/**
 * Zone Resource.
 * @uri /zone
 * @uri /zone/:identifier
 */
class ZoneResource extends TokenResource {

	/**
	 * Retrieves an existing DNS zone.
	 *
	 * If no identifier is specified and no body is supplied, all zones will be
	 * retrieved without records.
	 *
	 * Response:
	 *
	 * [
	 *      {
	 *            "name": <string>,
	 *            "type": MASTER|SLAVE|NATIVE,
	 *            "master": <ipv4 optional>,
	 *            "last_check": <int optional>,
	 *            "notified_serial": <int optional>
	 *      },0..n
	 * ]
	 *
	 * If a query is specified in the URL,  all zones matching the given wildcard
	 * are returned. The * wildcard is supported.
	 *
	 * If an identifier is specified, one zone will be retrieved with records.
	 *
	 * Response:
	 *
	 * {
	 *      "name": <string>,
	 *      "type": MASTER|SLAVE|NATIVE,
	 *      "master": <ipv4>,
	 *      "last_check": <int>,
	 *      "records": [ {
	 *              "name": <string>,
	 *              "type": <string>,
	 *              "content": <string>,
	 *              "ttl": <int optional>,
	 *              "priority: <int optional>,
	 *              "change_date": <int optional>
	 *      },0..n ]
	 * }
	 *
	 * @access public
	 * @param mixed $request Request parameters
	 * @return Response DNS zone data if successful, error message otherwise.
	 */
	public function get($request, $identifier = null) {
		$response = new FormattedResponse($request);
		$data = $request->parseData();

		if (empty($identifier)) {
			if ($data === null) {
				return ZoneFunctions::get_all_zones($response);
			} else {
				$validator = new ZoneValidator($data);

				if (!isset($data->query)) {
					$response->code = Response::BADREQUEST;
					$response->error = "Query was missing. Ensure that the body is in valid format and all required parameters are present.";
					return $response;
				}

				if (!$validator->validates()) {
					$response->code = Response::BADREQUEST;
					$response->error = $validator->getFormattedErrors();
					return $response;
				}

				return ZoneFunctions::query_zones($response, $data->query);
			}
		} else {
			$validator = new ZoneValidator();
			$validator->identifier = $identifier;

			if (!$validator->validates()) {
				$response->code = Response::BADREQUEST;
				$response->error = $validator->getFormattedErrors();
				return $response;
			}

			return ZoneFunctions::get_zone($response, $identifier);
		}
	}

	/**
	 * Create a new DNS zone, or insert records into an existing DNS zone.
	 *
	 * If no identifier is specified, a new DNS zone is created.
	 *
	 * Request:
	 *
	 * {
	 *     "name": <string>,
	 *     "type": MASTER|SLAVE|NATIVE,
	 *     "master": <ipv4 optional>,
	 *     "templates": [ {
	 *             "identifier": <string>
	 *     },0..n ]
	 *     "records": [ {
	 *             "name": <string>,
	 *             "type": <string>,
	 *             "content": <string>,
	 *             "ttl": <int optional>,
	 *             "priority": <int optional>
	 *     },0..n ]
	 * }
	 *
	 * Response:
	 *
	 * true
	 *
	 * If an identifier is specified, records will be inserted into an existing DNS zone.
	 *
	 * Request:
	 *
	 * {
	 *     "records": [ {
	 *             "name": <string>,
	 *             "type": <string>,
	 *             "content": <string>,
	 *             "ttl": <int optional>,
	 *             "priority": <int optional>
	 *     },0..n ]
	 * }
	 *
	 * Response:
	 *
	 * true
	 *
	 * @access public
	 * @param mixed $request Request parameters
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

		if ((!isset($data->name) || !isset($data->type)) && empty($identifier)) {
			$response->code = Response::BADREQUEST;
			$response->error = "Identifier and/or entries were missing or invalid. Ensure that the body is in valid format and all required parameters are present.";
			return $response;
		}

		$validator = new ZoneValidator($data);
		if (!empty($identifier)) {
			$validator->identifier = $identifier;
		}

		if (!$validator->validates()) {
			$response->code = Response::BADREQUEST;
			$response->error = $validator->getFormattedErrors();
			return $response;
		}

		if (!empty($identifier)) {
			return ZoneFunctions::create_records($response, $identifier, $data);
		} else {
			return ZoneFunctions::create_zone($response, $data);

		}
	}

	/**
	 * Update an existing DNS zone. Only works for zones, not records. At least one field has to be specified.
	 *
	 * Request:
	 *
	 * {
	 *     "name": <string>,
	 *     "type": MASTER|SLAVE|NATIVE,
	 *     "master": <ipv4 optional>,
	 * }
	 * Response:
	 *
	 * true
	 *
	 * @access public
	 * @param mixed $request Request parameters
	 * @return Response True if request was successful, error message otherwise.
	 */
	public function post($request, $identifier = null) {
		$response = new FormattedResponse($request);
		$data = $request->parseData();

		if ($data == null || empty($data)) {
			$response->code = Response::BADREQUEST;
			$response->error = "Request body was malformed. Ensure the body is in valid format, and that the body is not empty.";
			return $response;
		}

		if (empty($identifier)) {
			$response->code = Response::BADREQUEST;
			$response->error = "Identifier was missing or invalid. Ensure that the body is in valid format.";
			return $response;
		}

		$validator = new ZoneValidator($data);
		$validator->identifier = $identifier;

		if (!$validator->validates()) {
			$response->code = Response::BADREQUEST;
			$response->error = $validator->getFormattedErrors();
			return $response;
		}

		return ZoneFunctions::modify_zone($response, $identifier, $data);
	}

	/**
	 * Delete an existing DNS zone, or delete a record from the zone.
	 *
	 * If an identifier is specified, the entire zone will be deleted.
	 *
	 * Response:
	 *
	 * true
	 *
	 * If a body is specified, but no identifier, the specified entries will be deleted from the zone.
	 *
	 * Request:
	 *
	 * {
	 *     "name": <string>,
	 *     "records": [ {
	 *             "name": <string>,
	 *             "type": <string>,
	 *             "content": <string>,
	 *             "priority": <int>
	 *     },1..n ]
	 * }
	 *
	 * Response:
	 *
	 * true
	 *
	 * @access public
	 * @params mixed $request Request parameters
	 * @return Response True if zone was deleted, error message otherwise.
	 */
	public function delete($request, $identifier = null) {
		$response = new FormattedResponse($request);
		$data = $request->parseData();

		if (empty($identifier) && (empty($data) || !isset($data->name) || !isset($data->records) || empty($data->records))) {
			$response->code = Response::BADREQUEST;
			$response->error = "Identifier and/or records were missing or invalid. Ensure that the body is in valid format and all required parameters are present.";
			return $response;
		}

		$validator = new ZoneValidator($data);
		if (!empty($identifier)) {
			$validator->identifier = $identifier;
		}

		if (!$validator->validates()) {
			$response->code = Response::BADREQUEST;
			$response->error = $validator->getFormattedErrors();
			return $response;
		}

		if (!empty($identifier)) {
			return ZoneFunctions::delete_zone($response, $identifier);
		} else {
			return ZoneFunctions::delete_records($response, $data->name, $data);
		}
	}
}
?>
