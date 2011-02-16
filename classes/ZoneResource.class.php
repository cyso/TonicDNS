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
	 * If no identifier is specified, all zones will be retrieved without records.
	 * If an identifier is specified, one zone will be retrieved with records.
	 *
	 * @access public
	 * @param mixed $request Request parameters
	 * @return Response DNS zone data if successful, false with error message otherwise.
	 */
	public function get($request, $identifier = null) {
		$response = new FormattedResponse($request);
		$data = $request->parseData();

		if (empty($identifier)) {
			return $this->get_all_zones($response);
		} else {
			return $this->get_zones($response, $identifier);
		}
	}

	/**
	 * Create a new DNS zone.
	 *
	 * {
	 * 	"name": <string>,
	 * 	"master": ipv4,
	 * 	"type": master|slave|native,
	 * 	"records": 0..n {
	 * 		"name": <string>,
	 * 		"type": <string>,
	 * 		"content": <string>,
	 * 		"ttl": <int>,
	 * 		"priority": <int>
	 * 	}
	 * }
	 *
	 * @access public
	 * @param mixed $request Request parameters
	 * @return Response True if request was successful, false with error message otherwise.
	 */
	public function put($request, $identifier = null) {
		$response = new FormattedResponse($request);
		$data = $request->parseData();

		if ($data == null) {
			$response->code = Response::BADREQUEST;
			$response->error = "Request body was malformed. Ensure the body is in valid format.";
			return $response;
		}

		if (!isset($data->identifier) || !isset($data->description) || !isset($data->entries) || empty($data->entries)) {
			$response->code = Response::BADREQUEST;
			$response->error = "Identifier and/or entries were missing or invalid. Ensure that the body is in valid format and all required parameters are present.";
			return $response;
		}

		return $this->create_zone($response, $data);
	}

	/**
	 * Update an existing DNS zone. This method will overwrite the entire Zone. Only works for zones, not records.
	 *
	 * {
	 * 	"name": <string>,
	 * 	"master": ipv4,
	 * 	"type": master|slave|native,
	 * }
	 *
	 * @access public
	 * @param mixed $request Request parameters
	 * @return Response True if request was successful, false with error message otherwise.
	 */
	public function post($request, $identifier = null) {
		$response = new FormattedResponse($request);
		$data = $request->parseData();

		if ($data == null) {
			$response->code = Response::BADREQUEST;
			$response->error = "Request body was malformed. Ensure the body is in valid format.";
			return $response;
		}

		if (empty($identifier) || !isset($data->identifier) || !isset($data->entries) || empty($data->entries)) {
			$response->code = Response::BADREQUEST;
			$response->error = "Identifier and/or entries were missing or invalid. Ensure that the body is in valid format and all required parameters are present.";
			return $response;
		}

		return $this->modify_zone($response, $data);
	}

	/**
	 * Delete an existing DNS zone, or delete a record from the zone.
	 *
	 * If an identifier is specified, the entire zone will be deleted.
	 * If a body is specified, but no identifier, the specified entries will be deleted from the zone.
	 *
	 * {
	 * 	"name": <string>,
	 * 	"records": 1..n {
	 * 		"name": <string>,
	 * 		"type": <string>,
	 * 		"priority": <int>,
	 * 	}
	 *
	 * @access public
	 * @params mixed $request Request parameters
	 * @return Response True if zone was deleted, false with error message otherwise.
	 */
	public function delete($request, $identifier = null) {
		$response = new FormattedResponse($request);
		$data = $request->parseData();

		if (empty($identifier)) {
			$response->code = Response::BADREQUEST;
			$response->error = "Identifier and/or entries were missing or invalid. Ensure that the body is in valid format and all required parameters are present.";
			return $response;
		}

		return $this->delete_zone($response, $identifier);
	}

	private function get_all_zones($response, &$out = null) {

	}

	private function get_zone($response, $identifier, &$out = null) {

	}

	private function create_zone($response, $data, &$out = null) {

	}

	private function create_record($response, $identifier, $data, &$out = null) {

	}

	private function modify_zone($response, $data, &$out = null) {

	}

	private function delete_zone($response, $identifier, &$out = null) {

	}

	private function delete_record($response, $identifier, $records, &$out = null) {

	}
}
