<?php

define("VALID_NOTEMPTY", "#^.+$#");
define("VALID_INT", "#^[0-9]+$#");
define("VALID_NAME", "#^[\w_-]+$#");
define("VALID_STRING", "#^[\w -+.]*$#");
define("VALID_TOKEN", "#^[0-9a-f]{40}$#");
define("VALID_ZONE_TYPE", "#MASTER|SLAVE|NATIVE#");
define("VALID_DOMAIN", "#^(?:[a-zA-Z0-9](?:[a-zA-Z0-9\-]{0,61}[a-zA-Z0-9])?\.)+[a-zA-Z]{2,6}$#");
define("VALID_RECORD_NAME", "#^(?:\*\.)?(?:[a-zA-Z0-9](?:[a-zA-Z0-9\-]{0,61}[a-zA-Z0-9])?\.)+[a-zA-Z]{2,6}$#");
define("VALID_RECORD_TYPE", "#^A|AAAA|CNAME|MX|NAPTR|NS|PTR|RP|SOA|SPF|SSHFP|SRV|TXT$#");
define("VALID_IPV4", "#^(?:25[0-5]|2[0-4][0-9]|[0-1]{1}[0-9]{2}|[1-9]{1}[0-9]{1}|[1-9])\.(?:25[0-5]|2[0-4][0-9]|[0-1]{1}[0-9]{2}|[1-9]{1}[0-9]{1}|[1-9]|0)\.(?:25[0-5]|2[0-4][0-9]|[0-1]{1}[0-9]{2}|[1-9]{1}[0-9]{1}|[1-9]|0)\.(?:25[0-5]|2[0-4][0-9]|[0-1]{1}[0-9]{2}|[1-9]{1}[0-9]{1}|[0-9])$#");

class AuthenticationValidator {
	protected $rules = array(
		"username" => array(
			"valid_username" => array(
				"rule" => VALID_NAME,
				"message" => "Username is not valid. May only contain word characters (a-z, 0-9), underscores (_) or dashes (-)."
			)
		),
		"password" => array(
			"valid_password" => array(
				"rule" => VALID_NOTEMPTY,
				"message" => "Password is not valid. This field is mandatory, and must be set."
			)
		),
		"token" => array(
			"valid_token" => array(
				"rule" => VALID_TOKEN,
				"message" => "Token is not valid. Must be a 40 character hexadecimal string."
			)
		),
	);
}

class TemplateValidator {
	protected $rules = array(
		"identifier" => array(
			"valid_identifier" => array(
				"rule" => VALID_NAME,
				"message" => "Identifier is not valid. May only contain word characters (a-z, 0-9), underscores (_) or dashes (-)."
			)
		),
		"description" => array(
			"valid_description" => array(
				"rule" => VALID_STRING,
				"message" => "Description is not valid. May only contain word characters (a-z, 0-9), underscores (_), dashes (-), plus signs (+) or dots (.)."
			)
		),
		"entries" => array(
			"valid_entries" => array(
				"rule" => array("check_entries"),
				"message" => "DNS entries are invalid."
			)
		),
	);
}

class ZoneValidator {
	protected $rules = array(
		"name" => array(
			"valid_name" => array(
				"rule" => VALID_NAME,
				"message" => "Identifier is not valid. May only contain word characters (a-z, 0-9), underscores (_) or dashes (-)."
			)
		),
		"type" => array(
			"valid_type" => array(
				"rule" => VALID_ZONE_TYPE,
				"message" => "Type is not valid. Must be one of (case-sensitive): MASTER, SLAVE or NATIVE."
			)
		),
		"master" => array(
			"valid_master" => array(
				"rule" => array("check_zone_master"),
				"message" => "Master is not valid."
			)
		),
		"last_check" => array(
			"valid_last_check" => array(
				"rule" => VALID_INT,
				"message" => "Last check is not valid. Must be an integer."
			)
		),
		"notified_serial" => array(
			"valid_notified_serial" => array(
				"rule" => VALID_INT,
				"message" => "Notified serial is not valid. Must be an integer."
			)
		),
		"templates" => array(
			"valid_templates" => array(
				"rule" => array("check_templates"),
				"message" => "Templates are invalid."
			)
		),
		"records" => array(
			"valid_records" => array(
				"rule" => array("check_records"),
				"message" => "DNS records are invalid."
			)
		),
	);
}

class RecordValidator {

	public function __construct() {
		$r = VALID_RECORD_TYPE;
		$r = str_replace(array("#", "^", "$"), array("", "", ""), $r);
		$records = explode("|", $r);
		$this->rules['type']['valid_type']['message'] .= implode(", ", $records);
	}

	protected $rules = array(
		"name" => array(
			"valid_name" => array(
				"rule" => VALID_RECORD_NAME,
				"message" => "Record name is not valid. Must start with an alphanumeric character, and may only contain alphanumeric characters and dots (.). Must end in a valid tld. May start with '*.' to indicate a wildcard domain."
			)
		),
		"type" => array(
			"valid_type" => array(
				"rule" => VALID_RECORD_TYPE,
				"message" => "Record type is not valid. Must be one of: "
			)
		),
		"content" => array(
			"valid_content" => array(
				"rule" => array("check_record_content"),
				"message" => "Record content is not valid."
			)
		),
		"ttl" => array(
			"valid_ttl" => array(
				"rule" => VALID_INT,
				"message" => "Record TTL is not valid. Must be an integer."
			)
		),
		"priority" => array(
			"valid_priority" => array(
				"rule" => VALID_INT,
				"message" => "Record priority is not valid. Must be an integer."
			)
		),
		"change_date" => array(
			"valid_change_date" => array(
				"rule" => VALID_INT,
				"message" => "Record change date is not valid. Must be an Unix timestamp."
			)
		),

	);
}

?>
