<?php
/**
 * Template Resource.
 * @uri /template([\/\w]*)
 */
class TemplateResource extends TokenResource {

	/**
	 * Retrieves an existing DNS template.
	 *
	 * {
	 * 	"identifier": <string>
	 * }
	 *
	 * @access public
	 * @param mixed $request Request parameters
	 * @return Response DNS template data if successful, false with error message otherwise.
	 */
	public function get($request) {
		$response = new FormattedResponse($request);
		$data = $request->parseData();

		if ($data == null) {
			$response->code = Response::BADREQUEST;
			$response->body = "Request body was malformed. Ensure the body is in valid format.";
			return $response;
		}

		if (!isset($data->identifier)) {
			$response->code = Response::BADREQUEST;
			$response->body = "Identifier and/or entries were missing or invalid. Ensure that the body is in valid format and all required parameters are present.";
			return $response;
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
			$response->body = "Request body was malformed. Ensure the body is in valid format.";
			return $response;
		}

		if (!isset($data->identifier) || !isset($data->entries) || empty($data->entries)) {
			$response->code = Response::BADREQUEST;
			$response->body = "Identifier and/or entries were missing or invalid. Ensure that the body is in valid format and all required parameters are present.";
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
			$response->body = "Request body was malformed. Ensure the body is in valid format.";
			return $response;
		}

		if (!isset($data->identifier) || !isset($data->entries) || empty($data->entries)) {
			$response->code = Response::BADREQUEST;
			$response->body = "Identifier and/or entries were missing or invalid. Ensure that the body is in valid format and all required parameters are present.";
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
			$response->body = "Request body was malformed. Ensure the body is in valid format.";
			return $response;
		}

		if (!isset($data->identifier)) {
			$response->code = Response::BADREQUEST;
			$response->body = "Identifier and/or entries were missing or invalid. Ensure that the body is in valid format and all required parameters are present.";
			return $response;
		}

		return $response;
	}
}
