<?php
/**
 * Template Resource.
 * @uri /template/:identifier
 */
class TemplateResource extends TokenResource {

	/**
	 * Retrieves an existing DNS template.
	 *
	 * @access public
	 * @param mixed $request Request parameters
	 * @return Response DNS template data if successful, false with error message otherwise.
	 */
	public function get($request, $identifier) {
		$response = new FormattedResponse($request);
		$data = $request->parseData();

		if (empty($identifier)) {
			$response->code = Response::BADREQUEST;
			$response->error = "Identifier was missing or invalid. Ensure that the identifier is in valid format.";
			return $response;
		}

		try {
			$connection = new PDO(PowerDNSConfig::DB_DSN, PowerDNSConfig::DB_USER, PowerDNSConfig::DB_PASS);
		} catch (PDOException $e) {
			$response->code = Response::INTERNALSERVERERROR;
			$response->error = "Could not connect to PowerDNS server." . $e;
			return $response;
		}

		$statement = $connection->prepare(
			"SELECT z.id as z_id, z.name as z_name, z.descr as z_descr, r.name as r_name, r.type as r_type, r.content as r_content, r.ttl as r_ttl, r.prio as r_prio
			 FROM " . PowerDNSConfig::DB_TEMPLATE_TABLE . " z
			 INNER JOIN " . PowerDNSConfig::DB_TEMPLATE_RECORDS_TABLE . " r
			 WHERE z.name = :name
			 ORDER BY r.id, r.prio;"
		);

		if ($statement === false) {
			$response->code = Response::INTERNALSERVERERROR;
			$response->error = "Could not query PowerDNS server.";
			return $response;
		}

		if ($statement->execute(array(":name" => $identifier)) === false) {
			$response->code = Response::INTERNALSERVERERROR;
			$response->error = "Could not query PowerDNS server.";
			return $response;
		}

		$output = array();
		$output['identifier'] = $identifier;
		$output['entries'] = array();

		while (($row = $statement->fetch(PDO::FETCH_ASSOC)) !== false) {
			$output['entries'][] = array(
				"name" => $row['r_name'],
				"type" => $row['r_type'],
				"content" => $row['r_content'],
				"ttl" => $row['r_ttl'],
				"priority" => $row['r_prio']
			);
		}

		if (empty($output['entries'])) {
			$response->code = Response::NOTFOUND;
			$response->body = array();
		} else {
			$response->body = $output;
		}

		return $response;
	}

	/**
	 * Create a new DNS template.
	 *
	 * {
	 * 	"identifier": <string>,
	 * 	"entries": 
	 * 		"type": <string>,
	 * 		"value": <string>
	 * }
	 *
	 * @access public
	 * @param mixed $request Request parameters
	 * @return Response True if request was successful, false with error message otherwise.
	 */
	public function put($request) {
		$response = new FormattedResponse($request);
		$data = $request->parseData();

		if ($data == null) {
			$response->code = Response::BADREQUEST;
			$response->error = "Request body was malformed. Ensure the body is in valid format.";
			return $response;
		}

		if (!isset($data->identifier) || !isset($data->entries) || empty($data->entries)) {
			$response->code = Response::BADREQUEST;
			$response->error = "Identifier and/or entries were missing or invalid. Ensure that the body is in valid format and all required parameters are present.";
			return $response;
		}

		return $response;
	}

	/**
	 * Update an existing DNS template. This method will overwrite the entire Template.
	 *
	 * {
	 * 	"identifier": <string>,
	 * 	"entries": 
	 * 		"type": <string>,
	 * 		"value": <string>
	 * }
	 *
	 * @access public
	 * @param mixed $request Request parameters
	 * @return Response True if request was successful, false with error message otherwise.
	 */
	public function post($request) {
		$response = new FormattedResponse($request);
		$data = $request->parseData();

		if ($data == null) {
			$response->code = Response::BADREQUEST;
			$response->error = "Request body was malformed. Ensure the body is in valid format.";
			return $response;
		}

		if (!isset($data->identifier) || !isset($data->entries) || empty($data->entries)) {
			$response->code = Response::BADREQUEST;
			$response->error = "Identifier and/or entries were missing or invalid. Ensure that the body is in valid format and all required parameters are present.";
			return $response;
		}

		return $response;
	}

	/**
	 * Delete an existing DNS template.
	 *
	 * {
	 * 	"identifier": <string>
	 * }
	 *
	 * @access public
	 * @params mixed $request Request parameters
	 * @return Response True if template was deleted, false with error message otherwise.
	 */
	public function delete($request) {
		$response = new FormattedResponse($request);
		$data = $request->parseData();

		if ($data == null) {
			$response->code = Response::BADREQUEST;
			$response->error = "Request body was malformed. Ensure the body is in valid format.";
			return $response;
		}

		if (!isset($data->identifier)) {
			$response->code = Response::BADREQUEST;
			$response->error = "Identifier and/or entries were missing or invalid. Ensure that the body is in valid format and all required parameters are present.";
			return $response;
		}

		return $response;
	}
}
