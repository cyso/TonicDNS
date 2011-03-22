<?php
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
