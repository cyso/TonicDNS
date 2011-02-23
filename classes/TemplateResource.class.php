<?php
/**
 * Template Resource.
 * @uri /template
 * @uri /template/:identifier
 */
class TemplateResource extends TokenResource {

	/**
	 * Retrieves an existing DNS template.
	 *
	 * Response:
	 *
	 * [
	 *      {
	 *            "identifier": <string>,
	 *            "description": <string>,
	 *            "entries": [ {
	 *                  "name": <string>,
	 *                  "type": <string>,
	 *                  "content": <string>,
	 *                  "ttl": <int>,
	 *                  "priority": <int>
	 *            },0..n ]
	 *      },0..n
 	 * ]
	 *
	 * @access public
	 * @param mixed $request Request parameters
	 * @return Response DNS template data if successful, error message otherwise.
	 */
	public function get($request, $identifier = null) {
		$response = new FormattedResponse($request);
		$data = $request->parseData();

		if (empty($identifier)) {
			return TemplateFunctions::get_all_templates($response);
		} else {
			$validator = new TemplateValidator();
			$validator->identifier = $identifier;

			if (!$validator->validates()) {
				$response->code = Response::BADREQUEST;
				$response->error = $validator->getFormattedErrors();
				return $response;
			}

			return TemplateFunctions::get_template($response, $identifier);
		}
	}

	/**
	 * Create a new DNS template.
	 *
	 * Request:
	 *
	 * {
	 *      "identifier": <string>,
	 *      "description": <string>,
	 *      "entries": [ {
	 *            "name": <string>,
	 *            "type": <string>,
	 *            "content": <string>,
	 *            "ttl": <int>,
	 *            "priority": <int>
	 *      },0..n ]
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
	public function put($request) {
		$response = new FormattedResponse($request);
		$data = $request->parseData();

		if ($data == null) {
			$response->code = Response::BADREQUEST;
			$response->error = "Request body was malformed. Ensure the body is in valid format.";
			return $response;
		}

		if (!isset($data->identifier) || !isset($data->description) || !isset($data->entries) || empty($data->entries)) {
			$response->code = Response::BADREQUEST;
			$response->error = "Identifier, description and/or entries were missing or invalid. Ensure that the body is in valid format and all required parameters are present.";
			return $response;
		}

		$validator = new TemplateValidator($data);

		if (!$validator->validates()) {
			$response->code = Response::BADREQUEST;
			$response->error = $validator->getFormattedErrors();
			return $response;
		}

		return TemplateFunctions::create_template($response, $data);
	}

	/**
	 * Update an existing DNS template. This method will overwrite the entire Template.
	 *
	 * Request:
	 *
	 * {
	 *     "identifier": <string>,
	 *     "description": <string>,
	 *     "entries": [ {
	 *            "name": <string>,
	 *            "type": <string>,
	 *            "content": <string>,
	 *            "ttl": <int optional>,
	 *            "priority": <int optional>
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

		$validator = new TemplateValidator($data);

		if (!$validator->validates()) {
			$response->code = Response::BADREQUEST;
			$response->error = $validator->getFormattedErrors();
			return $response;
		}

		return TemplateFunctions::modify_template($response, $identifier, $data);
	}

	/**
	 * Delete an existing DNS template.
	 *
	 * Response: true
	 *
	 * @access public
	 * @params mixed $request Request parameters
	 * @return Response True if template was deleted, error message otherwise.
	 */
	public function delete($request, $identifier = null) {
		$response = new FormattedResponse($request);
		$data = $request->parseData();

		if (empty($identifier)) {
			$response->code = Response::BADREQUEST;
			$response->error = "Identifier and/or entries were missing or invalid. Ensure that the body is in valid format and all required parameters are present.";
			return $response;
		}

		$validator = new TemplateValidator();
		$validator->identifier = $identifier;

		if (!$validator->validates()) {
			$response->code = Response::BADREQUEST;
			$response->error = $validator->getFormattedErrors();
			return $response;
		}

		return TemplateFunctions::delete_template($response, $identifier);
	}
}

?>
