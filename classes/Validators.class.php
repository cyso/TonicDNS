<?php

define("VALID_NOTEMPTY", "#^.+$#");
define("VALID_INT", "#^[0-9]+$#");
define("VALID_NAME", "#^[\w_-]+$#");
define("VALID_STRING", "#^[\w -+.]*$#");
define("VALID_QUOTED", "#^[\"']{1}.*[\"']{1}$#");
define("VALID_TOKEN", "#^[0-9a-f]{40}$#");
define("VALID_ZONE_TYPE", "#MASTER|SLAVE|NATIVE#");
if (ValidatorConfig::BIND_COMPATABILITY === true) {
	define("VALID_DOMAIN", "#^(?:[a-zA-Z0-9](?:[a-zA-Z0-9\-]{0,61}[a-zA-Z0-9])?\.)+[a-zA-Z]{2,6}[\.]?$#");
} else {
	define("VALID_DOMAIN", "#^(?:[a-zA-Z0-9](?:[a-zA-Z0-9\-]{0,61}[a-zA-Z0-9])?\.)+[a-zA-Z]{2,6}$#");
}
define("VALID_QUERY", "#^[a-zA-Z0-9\-\.*]+$#");
define("VALID_RECORD_NAME", "#^(?:\*\.)?(?:[a-zA-Z0-9_](?:[a-zA-Z0-9\-_]{0,61}[a-zA-Z0-9])?\.)+[a-zA-Z]{2,6}$#");
define("VALID_RECORD_TYPE", "#^A|AAAA|CNAME|MX|NAPTR|NS|PTR|RP|SOA|SPF|SSHFP|SRV|TXT$#");
define("VALID_IPV4", "#^(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)$#");
define("VALID_IPV6", "#^(?:(?:[A-F0-9]{1,4}:){7}[A-F0-9]{1,4}|(?=(?:[A-F0-9]{0,4}:){0,7}[A-F0-9]{0,4}$)(([0-9A-F]{1,4}:){1,7}|:)((:[0-9A-F]{1,4}){1,7}|:))$#i");

class AuthenticationValidator extends Validator {
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
		"local_user" => array(
			"valid_local_user" => array(
				"rule" => VALID_NOTEMPTY,
				"message" => "Local user must be set."
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

class TemplateValidator extends Validator {
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
				"message" => "Description is not valid. May only contain word characters (a-z, 0-9), underscores (_), dashes (-), plus signs (+) or dots (.). May also be empty."
			)
		),
		"entries" => array(
			"valid_entries" => array(
				"rule" => array("check_entries"),
				"message" => "DNS entries are not valid."
			)
		),
	);

	public function check_entries($entries) {
		if (!is_array($entries)) {
			return "Template entries are not valid. Must be specified as an (empty) array.";
		}

		$errors = array();
		foreach ($entries as $entry) {
			$record = new RecordValidator();
			$record->initialize($entry);

			if (!$record->validates()) {
				$errors[] = $record->getFormattedErrors();
			}
		}

		if (!empty($errors)) {
			array_unshift($errors, "Template entries are not valid. The following errors occurred: ");
			return implode("\n", $errors);
		} else {
			return true;
		}
	}
}

class ZoneValidator extends Validator {
	protected $rules = array(
		"identifier" => array(
			"valid_identifier" => array(
				"rule" => VALID_DOMAIN,
				"message" => "Identifier is not valid. Must be a valid FQDN."
			)
		),
		"name" => array(
			"valid_name" => array(
				"rule" => VALID_DOMAIN,
				"message" => "Name is not valid. Must be a valid FQDN."
			)
		),
		"type" => array(
			"valid_type" => array(
				"rule" => array("check_zone_type"),
				"message" => ""
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
				"message" => "Templates are not valid."
			)
		),
		"records" => array(
			"valid_records" => array(
				"rule" => array("check_records"),
				"message" => "DNS records are not valid."
			)
		),
		"query" => array(
			"valid_query" => array(
				"rule" => VALID_QUERY,
				"message" => "Query is invalid. May only contain alphanumeric characters, dashes (-), dots (.) and wildcards (*)."
			)
		),
	);

	public function check_zone_type($value) {
		if (!ctype_upper($value)) {
			return "Zone type is not valid. Must be an uppercase string.";
		}
		if (preg_match(VALID_ZONE_TYPE, $value) === 0) {
			return "Zone type is not valid. Must be one of (case-sensitive): MASTER, SLAVE or NATIVE.";
		}
		if ($value == "SLAVE" && !isset($this->master)) {
			return "Zone type is not valid. If set to SLAVE, a master IP must be specified.";
		}
		return true;
	}

	public function check_zone_master($value) {
		if (isset($this->type) && $this->type === "SLAVE") {
			if (preg_match(VALID_IPV4, $value) === 0) {
				return "Zone master is not valid. Type is set to SLAVE, but the master IP is not a valid IPv4 address.";
			}
		} else {
			if (!empty($value)) {
				return "Zone master is not valid. If type is not set to SLAVE, master must be empty.";
			}
		}
		return true;
	}

	public function check_templates($templates) {
		if (!is_array($templates)) {
			return "Zone templates are not valid. Must be specified as an (empty) array.";
		}

		$errors = array();
		foreach ($templates as $entry) {
			$template = new TemplateValidator();
			$template->identifier = $entry;

			if (!$template->validates()) {
				$errors[] = $template->getFormattedErrors();
			}
		}

		if (!empty($errors)) {
			array_unshift($errors, "Zone templates are not valid. The following errors occurred: ");
			return implode("\n", $errors);
		} else {
			return true;
		}

	}

	public function check_records($records) {
		if (!is_array($records)) {
			return "Zone records are not valid. Must be specified as an (empty) array.";
		}

		$errors = array();
		foreach ($records as $entry) {
			$record = new RecordValidator();
			$record->initialize($entry);

			if (!$record->validates()) {
				$errors[] = $record->getFormattedErrors();
			}
		}

		if (!empty($errors)) {
			array_unshift($errors, "Zone records are not valid. The following errors occurred: ");
			return implode("\n", $errors);
		} else {
			return true;
		}
	}
}

class RecordValidator extends Validator {
	public function __construct() {
		$r = VALID_RECORD_TYPE;
		$r = str_replace(array("#", "^", "$"), array("", "", ""), $r);
		$records = explode("|", $r);
		$this->rules['type']['valid_type']['message'] .= implode(", ", $records) . ".";
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

	public function check_record_content($content) {
		$prefix = "Record content is not valid. ";
		if (empty($content)) {
			return $prefix . "Content may never be empty.";
		}

		if (!isset($this->type) || empty($this->type)) {
			return false;
		}

		switch ($this->type) {
		case "A":
			if (preg_match(VALID_IPV4, $content) === 0) {
				return $prefix . "An A record requires a valid IPv4 address without trailing dot.";
			}
			break;
		case "AAAA":
			if (preg_match(VALID_IPV6, $content) === 0) {
				return $prefix . "An AAAA record requires a valid IPv6 address without trailing dot. IPv4 addresses in IPv6 notation are not supported.";
			}
			break;
		case "MX":
			if (!isset($this->priority)) {
				return $prefix . "A MX record must also specify a priority.";
			}
			if (!isset($type)) {
				$type = "MX";
			}
		case "NS":
			if (!isset($type)) {
				$type = "NS";
			}
		case "CNAME":
			if (!isset($type)) {
				$type = "CNAME";
			}
			if (preg_match(VALID_DOMAIN, $content) === 0) {
				return $prefix . "A $type record must contain a valid FQDN without trailing dot.";
			}
			break;
		case "NAPTR":
			$parts = explode(" ", $content);
			if (count($parts) !== 6) {
				return $prefix . "A NAPTR record must provide all 6 parts (note the quotes and trailing dot): <order> <preference> '<flags>' '<service>' '<regex>' replacement.";
			}
			for ($i = 0; $i < count($parts); $i++) {
				switch ($i) {
				case 0:
				case 1:
					if (!ctype_digit($parts[$i])) {
						return $prefix . "NAPTR record part $i must be a valid integer.";
					}
					break;
				case 2:
				case 3:
				case 4:
					if (preg_match(VALID_QUOTED, $parts[$i]) === 0) {
						return $prefix . "NAPTR record part $i must be a valid quoted string.";
					}
					break;
				case 5:
					if (preg_match(VALID_NOTEMPTY, $parts[$i]) === 0) {
						return $prefix . "NAPTR record part $i must be a valid record pointer, or a single dot (.).";
					}
					break;
				}
			}
			break;
		case "RP":
			$parts = explode(" ", $content);
			if (count($parts) !== 2) {
				return $prefix . "A RP record must provide all 2 parts: <mailbox name> <more-info pointer>";
			}
			if (preg_match(VALID_DOMAIN, $parts[0]) === 0) {
				return $prefix . "A RP records mailbox name must be an email address with the at-sign replaced by a dot (.).";
			}
			if (preg_match(VALID_DOMAIN, $parts[1]) === 0) {
				return $prefix . "A RP records more-info pointer must be a valid FQDN.";
			}
			break;
		case "SOA":
			$parts = explode(" ", $content);
			if (count($parts) !== 7) {
				return $prefix . "A SOA record must provide all 7 parts: <primary> <hostmaster> <serial> <refresh> <retry> <expire> <default_ttl>";
			}
			for ($i = 0; $i < count($parts); $i++) {
				switch ($i) {
				case 0:
					if (preg_match(VALID_DOMAIN, $parts[$i]) === 0 && $parts[$i] !== "default-soa-name") {
						return $prefix . "A SOA record must provide a valid FQDN or 'default-soa-name' as primary hostname.";
					}
					break;
				case 1:
					if (filter_var($parts[$i], FILTER_VALIDATE_EMAIL) === false && preg_match(VALID_DOMAIN, $parts[$i]) === 0) {
						return $prefix . "A SOA record must provide a valid email address as hostmaster.";
					}
					break;
				case 2:
				case 3:
				case 4:
				case 5:
				case 6:
					if (!ctype_digit($parts[$i])) {
						return "SOA record part $i must be a valid integer.";
					}
					break;
				}
			}
			break;
		case "SPF":
			if (!isset($type)) {
				$type = "SPF";
			}
		case "TXT":
			if (!isset($type)) {
				$type = "TXT";
			}
			if (preg_match(VALID_QUOTED, $content) === 0) {
				return $prefix . "A $type record must provide a valid quoted string.";
			}
			break;
		case "SSHFP":
			$parts = explode(" ", $content);
			if (count($parts) !== 3) {
				return $prefix . "A SSHFP record must provide all 3 parts: <algorithm> <fp-type> <fingeprint>";
			}
			for ($i = 0; $i < count($parts); $i++) {
				switch ($i) {
				case 0:
					if ($parts[$i] != "1" || $parts[$i] != "2") {
						return $prefix . "A SSHFP record must provide either 1 (RSA) or 2 (DSA) as algorithm.";
					}
					break;
				case 1:
					if ($parts[$i] != "1") {
						return $prefix . "A SSHFP record must provide 1 (SHA-1) as fp-type.";
					}
					break;
				case 2:
					if (strlen($parts[$i]) !== 40) {
						return $prefix . "A SSHFTP record must provide a fingerprint as a 40 character ASCII hexadecimal string.";
					}
					break;
				}
			}
			break;
		case "SRV":
			if (!isset($this->priority)) {
				return $prefix . "A SRV record must also provide a priority.";
			}
			$parts = explode(" ", $content);
			if (count($parts) !== 3) {
				return $prefix . "A SRV record must provide all 3 parts: <weight> <port> <service>";
			}
			for ($i = 0; $i < count($parts); $i++) {
				switch ($i) {
				case 0:
				case 1:
					if (!ctype_digit($parts[$i])) {
						return $prefix . "SRV record part $i must be a valid integer.";
					}
					break;
				case 2:
					if (preg_match(VALID_DOMAIN, $parts[$i]) === 0) {
						return $prefix . "A SRV record must provide a valid FQDN as service.";
					}
					break;
				}
			}
			break;
		default:
			break;
		}

		return true;
	}
}

?>
