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
 *
 * @package resources
 * @license http://www.gnu.org/licenses/gpl-3.0.html
 */
/**
 * Template Resource.
 * @uri /template
 * @uri /template/:identifier
 */
class TemplateResource extends TokenResource {

	/**
	 * Retrieves an existing DNS template.
	 *
	 * ### Response: ###
	 *
	 * ~~~
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
	 * ~~~
	 *
	 * ### Errors (request without identifier): ###
	 *
	 * * 500 - Failed to connect to database or query execution error.
	 *
	 * ### Errors (request with identifier): ###
	 *
	 * * 500 - Failed to connect to database or query execution error.
	 * * 404 - Could not find template.
	 *
	 * @access public
	 * @param mixed $request Request parameters
	 * @param string $identifier Template identifier
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
				$response->error_detail = $validator->getErrorDetails();
				return $response;
			}

			return TemplateFunctions::get_template($response, $identifier);
		}
	}

	/**
	 * Create a new DNS template.
	 *
	 * ### Request: ###
	 *
	 * ~~~
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
	 * ~~~
	 *
	 * ### Response: ###
	 *
	 * ~~~
	 * true
	 * ~~~
	 *
	 * ### Errors: ###
	 *
	 * * 508 - Invalid request, missing required parameters or input validation failed.
	 * * 500 - Failed to connect to database or query execution error.
	 * * 409 - Template already exists.
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
			$response->error_detail = "BODY_MALFORMED";
			return $response;
		}

		if (!isset($data->identifier) || !isset($data->description) || !isset($data->entries) || empty($data->entries)) {
			$response->code = Response::BADREQUEST;
			$response->error = "Identifier, description and/or entries were missing or invalid. Ensure that the body is in valid format and all required parameters are present.";
			$response->error_detail = "MISSING_REQUIRED_PARAMETERS";
			return $response;
		}

		$validator = new TemplateValidator($data);
		$validator->mode_override = "add";

		if (!$validator->validates()) {
			$response->code = Response::BADREQUEST;
			$response->error = $validator->getFormattedErrors();
			$response->error_detail = $validator->getErrorDetails();
			return $response;
		}

		return TemplateFunctions::create_template($response, $data);
	}

	/**
	 * Update an existing DNS template. This method will overwrite the entire Template.
	 *
	 * ### Request: ###
	 *
	 * ~~~
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
	 * ~~~
	 *
	 * ### Response: ###
	 *
	 * ~~~
	 * true
	 * ~~~
	 *
	 * ### Errors: ###
	 *
	 * * 508 - Invalid request, missing required parameters or input validation failed.
	 * * 500 - Failed to connect to database or query execution error.
	 * * 404 - Could not find template.
	 *
	 * @access public
	 * @param mixed $request Request parameters
	 * @param string $identifier Template identifier
	 * @return Response True if request was successful, error message otherwise.
	 */
	public function post($request, $identifier = null) {
		$response = new FormattedResponse($request);
		$data = $request->parseData();

		if ($data == null) {
			$response->code = Response::BADREQUEST;
			$response->error = "Request body was malformed. Ensure the body is in valid format.";
			$response->error_detail = "BODY_MALFORMED";
			return $response;
		}

		if (empty($identifier) || !isset($data->identifier) || !isset($data->entries) || empty($data->entries)) {
			$response->code = Response::BADREQUEST;
			$response->error = "Identifier and/or entries were missing or invalid. Ensure that the body is in valid format and all required parameters are present.";
			$response->error_detail = "MISSING_REQUIRED_PARAMETERS";
			return $response;
		}

		$validator = new TemplateValidator($data);

		if (!$validator->validates()) {
			$response->code = Response::BADREQUEST;
			$response->error = $validator->getFormattedErrors();
			$response->error_detail = $validator->getErrorDetails();
			return $response;
		}

		return TemplateFunctions::modify_template($response, $identifier, $data);
	}

	/**
	 * Delete an existing DNS template.
	 *
	 * ### Response: ### 
	 *
	 * ~~~
	 * true
	 * ~~~
	 *
	 * ### Errors: ###
	 *
	 * * 508 - Invalid request, missing required parameters or input validation failed.
	 * * 500 - Failed to connect to database or query execution error.
	 * * 404 - Could not find template.
	 *
	 * @access public
	 * @param mixed $request Request parameters
	 * @param string $identifier Template identifier
	 * @return Response True if template was deleted, error message otherwise.
	 */
	public function delete($request, $identifier = null) {
		$response = new FormattedResponse($request);
		$data = $request->parseData();

		if (empty($identifier)) {
			$response->code = Response::BADREQUEST;
			$response->error = "Identifier and/or entries were missing or invalid. Ensure that the body is in valid format and all required parameters are present.";
			$response->error_detail = "BODY_MALFORMED";
			return $response;
		}

		$validator = new TemplateValidator();
		$validator->identifier = $identifier;

		if (!$validator->validates()) {
			$response->code = Response::BADREQUEST;
			$response->error = $validator->getFormattedErrors();
			$response->error_detail = $validator->getErrorDetails();
			return $response;
		}

		return TemplateFunctions::delete_template($response, $identifier);
	}
}

?>
