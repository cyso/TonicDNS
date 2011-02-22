<?php

class AuthenticationValidator {
	protected $rules = array(
		"username" => array(
			"valid_username" => array(
				"rule" => "",
				"message" => ""
			)
		),
		"password" => array(
			"valid_password" => array(
				"rule" => "",
				"message" => ""
			)
		),
		"token" => array(
			"valid_token" => array(
				"rule" => "",
				"message" => ""
			)
		),
	);
}

class TemplateValidator {
	protected $rules = array(
		"identifier" => array(
			"valid_identifier" => array(
				"rule" => "",
				"message" => ""
			)
		),
		"description" => array(
			"valid_description" => array(
				"rule" => "",
				"message" => ""
			)
		),
		"entries" => array(
			"valid_entries" => array(
				"rule" => array("check_entries"),
				"message" => ""
			)
		),
	);
}

class ZoneValidator {
	protected $rules = array(
		"name" => array(
			"valid_name" => array(
				"rule" => "",
				"message" => ""
			)
		),
		"type" => array(
			"valid_type" => array(
				"rule" => "",
				"message" => ""
			)
		),
		"master" => array(
			"valid_master" => array(
				"rule" => "",
				"message" => ""
			)
		),
		"last_check" => array(
			"valid_last_check" => array(
				"rule" => "",
				"message" => ""
			)
		),
		"notified_serial" => array(
			"valid_notified_serial" => array(
				"rule" => "",
				"message" => ""
			)
		),
		"templates" => array(
			"valid_templates" => array(
				"rule" => "",
				"message" => ""
			)
		),
		"records" => array(
			"valid_records" => array(
				"rule" => "",
				"message" => ""
			)
		),
	);
}

class RecordValidator {
	protected $rules = array(
		"name" => array(
			"valid_name" => array(
				"rule" => "",
				"message" => ""
			)
		),
		"type" => array(
			"valid_type" => array(
				"rule" => "",
				"message" => ""
			)
		),
		"content" => array(
			"valid_content" => array(
				"rule" => "",
				"message" => ""
			)
		),
		"ttl" => array(
			"valid_ttl" => array(
				"rule" => "",
				"message" => ""
			)
		),
		"priority" => array(
			"valid_priority" => array(
				"rule" => "",
				"message" => ""
			)
		),
		"change_date" => array(
			"valid_change_date" => array(
				"rule" => "",
				"message" => ""
			)
		),

	);
}

?>
