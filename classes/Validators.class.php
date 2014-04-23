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
 */
define("VALID_NOTEMPTY", "#^(.+)$#");
define("VALID_INT", "#^[0-9]+$#");
define("VALID_NAME", "#^[\w_-]+$#");
define("VALID_STRING", "#^[\w -+.]*$#");
define("VALID_QUOTED", "#^[\"]{1}(.*)[\"]{1}$#");
define("NAPTR_FLAGS_VALID", "#^[a-z0-9]*$#i");
define("NAPTR_FLAGS_EXCLUSIVE", "#[sau]#i");
define("NAPTR_SERVICE_VALID", "#^(?:[a-z][a-z0-9]{0,31})?$|^(?:[a-z][a-z0-9]{0,31})(?:\+[a-z][a-z0-9]{0,31})*$#i");
define("NAPTR_REGEX_VALID_DELIMITER", "#^[^i0-9\\\\]$#");
define("NAPTR_REGEX_VALID_BACKREF", "#^\\\\[0-9]$#");
define("NAPTR_REGEX_VALID_FLAG", "#^[i]?$#");
define("VALID_TOKEN", "#^[0-9a-f]{40}$#");
define("VALID_HEX_40", "#^[0-9a-f]{40}$#i");
define("VALID_ZONE_TYPE", "#^MASTER$|^SLAVE$|^NATIVE$#");
if (ValidatorConfig::BIND_COMPATABILITY === true) {
	define("VALID_DOMAIN", "#^(?:[A-Z0-9_](?:[A-Z0-9\-_]{0,61}[A-Z0-9])?\.)+[A-Z]{2,61}[\.]?$#i");
	define("VALID_EMPTY_DOMAIN", "#^\.$|^$#");
} else {
	define("VALID_DOMAIN", "#^(?:[A-Z0-9_](?:[A-Z0-9\-_]{0,61}[A-Z0-9])?\.)+[A-Z]{2,61}$#i");
	define("VALID_EMPTY_DOMAIN", "#^$#");
}
define("VALID_TEMPLATE_DOMAIN", "#^(?:(?:[A-Z0-9_](?:[A-Z0-9\-_]{0,61}[A-Z0-9])?\.)*(?:[A-Z]{2,61}|\[ZONE\])|(?:\[ZONE\]))$#i");
define("VALID_QUERY", "#^[a-zA-Z0-9\-\.*]+$#");
define("VALID_RANGE_QUERY", "#^[a-zA-Z0-9\-\.:*,/]+|$#");
define("VALID_RECORD_NAME", "#^(?:\*\.)?(?:[A-Z0-9_](?:[A-Z0-9\-_]{0,61}[A-Z0-9])?\.)+[A-Z]{2,61}$#i");
define("VALID_TEMPLATE_NAME", "#^(?:(?:\*\.)?(?:[A-Z0-9_](?:[A-Z0-9\-_]{0,61}[A-Z0-9])?\.)*(?:[A-Z]{2,61}|\[ZONE\])|(?:\[ZONE\]))$#i");
define("VALID_RECORD_TYPE", "#^A|AAAA|CNAME|MX|NAPTR|NS|PTR|RP|SOA|SPF|SSHFP|SRV|TXT$#");
define("VALID_IPV4", "#^(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)$#");
define("VALID_IPV6", "#^(?:(?:[A-F0-9]{1,4}:){7}[A-F0-9]{1,4}|(?=(?:[A-F0-9]{0,4}:){0,7}[A-F0-9]{0,4}$)(([0-9A-F]{1,4}:){1,7}|:)((:[0-9A-F]{1,4}){1,7}|:))$#i");
define("VALID_IPV4_RANGE", "#^(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)/[0-9]{1,2}$#");
define("VALID_IPV6_RANGE", "#^(?:(?:[A-F0-9]{1,4}:){7}[A-F0-9]{1,4}|(?=(?:[A-F0-9]{0,4}:){0,7}[A-F0-9]{0,4}/[0-9]{1,3}$)(([0-9A-F]{1,4}:){1,7}|:)((:[0-9A-F]{1,4}){1,7}|:))/[0-9]{1,3}$#i");

class AuthenticationValidator extends Validator {
	protected $rules = array(
		"username" => array(
			"valid_username" => array(
				"rule" => VALID_NAME,
				"code" => "AUTH_INVALID_NAME",
				"message" => "Username is not valid. May only contain word characters (a-z, 0-9), underscores (_) or dashes (-)."
			)
		),
		"password" => array(
			"valid_password" => array(
				"rule" => VALID_NOTEMPTY,
				"code" => "AUTH_INVALID_PASSWORD",
				"message" => "Password is not valid. This field is mandatory, and must be set."
			)
		),
		"local_user" => array(
			"valid_local_user" => array(
				"rule" => VALID_NOTEMPTY,
				"code" => "AUTH_INVALID_LOCAL_USER",
				"message" => "Local user must be set."
			)
		),
		"token" => array(
			"valid_token" => array(
				"rule" => VALID_TOKEN,
				"code" => "AUTH_INVALID_TOKEN",
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
			$record = new RecordValidator($entry);
			$record->record_type = "TEMPLATE";

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
	public $mode_override = null;

	protected $rules = array(
		"identifier" => array(
			"valid_identifier" => array(
				"rule" => array("check_valid_domain"),
				"code" => "ZONE_INVALID_DOMAIN",
				"message" => "Identifier is not valid. Must be a valid FQDN."
			)
		),
		"name" => array(
			"valid_name" => array(
				"rule" => VALID_DOMAIN,
				"code" => "ZONE_INVALID_DOMAIN",
				"message" => "Name is not valid. Must be a valid FQDN."
			)
		),
		"type" => array(
			"valid_type" => array(
				"rule" => array("check_zone_type"),
				"code" => "ZONE_INVALID_TYPE",
				"message" => ""
			)
		),
		"master" => array(
			"valid_master" => array(
				"rule" => array("check_zone_master"),
				"code" => "ZONE_INVALID_MASTER",
				"message" => "Master is not valid."
			)
		),
		"last_check" => array(
			"valid_last_check" => array(
				"rule" => VALID_INT,
				"code" => "ZONE_INVALID_LAST_CHECK",
				"message" => "Last check is not valid. Must be an integer."
			)
		),
		"notified_serial" => array(
			"valid_notified_serial" => array(
				"rule" => VALID_INT,
				"code" => "ZONE_INVALID_NOTIFIED_SERIAL",
				"message" => "Notified serial is not valid. Must be an integer."
			)
		),
		"templates" => array(
			"valid_templates" => array(
				"rule" => array("check_templates"),
				"code" => "ZONE_INVALID_TEMPLATES",
				"message" => "Templates are not valid."
			)
		),
		"records" => array(
			"valid_records" => array(
				"rule" => array("check_records"),
				"code" => "ZONE_INVALID_RECORDS",
				"message" => "DNS records are not valid."
			)
		),
		"query" => array(
			"valid_query" => array(
				"rule" => VALID_QUERY,
				"code" => "ZONE_INVALID_QUERY",
				"message" => "Query is invalid. May only contain alphanumeric characters, dashes (-), dots (.) and wildcards (*)."
			)
		),
	);

	public function check_valid_domain($value) {
		if (preg_match(VALID_DOMAIN, $value) ||
			preg_match(VALID_IPV4, $value) ||
			preg_match(VALID_IPV6, $value)) {
				return true;
			} else {
				return false;
			}
	}

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
		if ($value == "SLAVE" && isset($this->records)) {
			return array(
				"message" => "Cannot modify records. Zone type is being changes to SLAVE.",
				"code" => "ZONE_IS_SLAVE"
			);
		}
		return true;
	}

	public function check_zone_master($value) {
		if (isset($this->type) && $this->type === "SLAVE") {
			if (preg_match(VALID_IPV4, $value) === 0 && preg_match(VALID_IPV6, $value) === 0) {
				return "Zone master is not valid. Type is set to SLAVE, but the master IP is not a valid IPv4 or IPv6 address.";
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
			if ($entry instanceof stdClass) {
				if (!isset($entry->identifier)) {
					$errors[] = "Zone template identifier was not set.";
					continue;
				}
				$template->identifier = $entry->identifier;
			} else {
				$template->identifier = $entry;
			}

			if (!$template->validates()) {
				$errors[] = $template->getFormattedErrors(true);
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
			$record = new RecordValidator($entry);

			if (!empty($this->mode_override)) {
				$record->mode = $this->mode_override;
			}

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
	public $record_type = "NORMAL";
	public $record_mode = "ADD";
	public static $cnames = array();
	public static $others = array();
	public static $deletions = array();

	public function __construct($data = null) {
		$r = VALID_RECORD_TYPE;
		$r = str_replace(array("#", "^", "$"), array("", "", ""), $r);
		$records = explode("|", $r);
		$this->rules['type']['valid_type']['message'] .= implode(", ", $records) . ".";

		parent::__construct($data);
	}

	public function initialize($data = null) {
		if (is_object($data) && $data instanceof StdClass && isset($data->mode) && $data->mode === "delete") {
			$this->record_mode = "DELETE";
		} else if (is_array($data) && isset($data['mode']) && $data['mode'] === "delete") {
			$this->record_mode = "DELETE";
		}

		parent::initialize($data);
	}

	protected $rules = array(
		"name" => array(
			"valid_name" => array(
				"rule" => array("check_record_name"),
				"code" => "RECORD_INVALID_NAME",
				"message" => "Record name is not valid. Must start with an alphanumeric character, and may only contain alphanumeric characters and dots (.). Must end in a valid tld. May start with '*.' to indicate a wildcard domain. Subdomains must be 61 characters or less."
			)
		),
		"priority" => array(
			"valid_priority" => array(
				"rule" => array("check_record_priority"),
				"code" => "RECORD_INVALID_PRIORITY",
				"message" => "Record priority is not valid. Must be an integer."
			)
		),
		"type" => array(
			"valid_type" => array(
				"rule" => array("check_record_type"),
				"code" => "RECORD_INVALID_TYPE",
				"message" => "Record type is not valid. Must be one of: "
			)
		),
		"content" => array(
			"valid_content" => array(
				"rule" => array("check_record_content"),
				"code" => "RECORD_INVALID_CODE",
				"message" => "Record content is not valid."
			)
		),
		"ttl" => array(
			"valid_ttl" => array(
				"rule" => VALID_INT,
				"code" => "RECORD_INVALID_TTL",
				"message" => "Record TTL is not valid. Must be an integer."
			)
		),
		"change_date" => array(
			"valid_change_date" => array(
				"rule" => VALID_INT,
				"code" => "RECORD_INVALID_CHANGE_DATE",
				"message" => "Record change date is not valid. Must be an Unix timestamp."
			)
		),

	);

	public function check_record_name($content) {
		if ($this->record_type === "TEMPLATE") {
			if (preg_match(VALID_TEMPLATE_NAME, $content) !== 1) {
				return "Template record name is not valid. Must start with an alphanumeric character, and may only contain alphanumeric characters and dots (.). Must end in a valid tld or '[ZONE]'. May start with '*.' to indicate a wildcard domain.";
			}
			if (strlen($content) > 127) {
				return "Template record name is too long, must be less than 127 characters.";
			}
		} else {
			if (preg_match(VALID_RECORD_NAME, $content) !== 1) {
				return "Record name is not valid. Must start with an alphanumeric character, and may only contain alphanumeric characters and dots (.). Must end in a valid tld. May start with '*.' to indicate a wildcard domain. Subdomains must be 61 characters or less.";
			}
			if (strlen($content) > 253) {
				return "Record name is too long, must be less than 253 characters.";
			}
		}

		return true;
	}

	public function check_record_type($content) {
		if (preg_match(VALID_RECORD_TYPE, $content) === 0) {
			return array(
				"message" => "Record has invalid type",
				"code" => "RECORD_INVALID_TYPE"
			);
		}
		if ($this->record_mode == "ADD") {
			if (!defined("TESTING_MODE")) {
				if ($this->type != 'CNAME' && in_array($this->name, RecordValidator::$cnames)) {
					return array(
						"message" => sprintf("Cannot add a new record of type %s when a CNAME record is being inserted for %s", $this->type, $this->name),
						"code" => "RECORD_CNAME_ALREADY_INSERT"
					);
				} else if ($this->type == 'CNAME' && in_array($this->name, RecordValidator::$others)) {
					return array(
						"message" => sprintf("Cannot add a new CNAME record when a record of another type is being inserted for %s", $this->name),
						"code" => "RECORD_CNAME_OTHER_INSERT"
					);
				}

				if ($this->type != 'CNAME' && HelperFunctions::has_records_of_type($this->name, array("CNAME"), RecordValidator::$deletions) != false) {
					return array(
						"message" => sprintf("Cannot add a new record of type %s when a CNAME record is already present for %s", $this->type, $this->name),
						"code" => "RECORD_CNAME_ALREADY_PRESENT"
					);
				} else if ($this->type == 'CNAME' && HelperFunctions::has_records_of_type($this->name, array("!CNAME"), RecordValidator::$deletions) != false) {
					return array(
						"message" => sprintf("Cannot add a new CNAME record when a record of another type is already present for %s", $this->name),
						"code" => "RECORD_CNAME_OTHER_PRESENT"
					);
				}
			}

			if ($this->type == 'CNAME') {
				RecordValidator::$cnames[] = $this->name;
			} else {
				RecordValidator::$others[] = $this->name;
			}
		}

		if ($this->record_mode == "DELETE") {
			if ($this->type == "MX" || $this->type == "SRV") {
				RecordValidator::$deletions[] = array(
					"name" => $this->name,
					"type" => $this->type,
					"content" => $this->content,
					"priority" => $this->priority,
				);
			} else {
				RecordValidator::$deletions[] = array(
					"name" => $this->name,
					"type" => $this->type,
					"content" => $this->content,
				);
			}
		}
		return true;
	}

	public function check_record_content($content) {
		$prefix = "Record content is not valid. ";
		if (empty($content)) {
			return array(
				"message" => $prefix . "Content may never be empty.",
				"code" => "RECORD_RHS_EMPTY"
			);
		}

		if (!isset($this->type) || empty($this->type)) {
			return $prefix . "Type may never be empty.";
		}

		if (strlen($content) > 4096) {
			return array(
				"message" => $prefix . "Content is too long, must be less than 4096 characters.",
				"code" => "RECORD_RHS_TOO_LONG"
			);
		}

		switch ($this->type) {
		case "A":
			if (preg_match(VALID_IPV4, $content) === 0) {
				return array(
					"message" => $prefix . "An A record requires a valid IPv4 address without trailing dot.",
					"code" => "RECORD_RHS_INVALID_IPV4"
				);
			}
			break;
		case "AAAA":
			if (preg_match(VALID_IPV6, $content) === 0) {
				return array(
					"message" => $prefix . "An AAAA record requires a valid IPv6 address without trailing dot. IPv4 addresses in IPv6 notation are not supported.",
					"code" => "RECORD_RHS_INVALID_IPV6"
				);
			}
			break;
		case "MX":
			if (!isset($this->priority)) {
				return array(
					"message" => $prefix . "A MX record must also specify a priority.",
					"code" => "RECORD_RHS_MISSING_PRIORITY"
				);
			}
			if (!isset($type)) {
				$type = "MX";
			}
		case "NS":
			if (!isset($type)) {
				$type = "NS";
			}
		case "PTR":
			if (!isset($type)) {
				$type = "PTR";
			}
		case "CNAME":
			if (!isset($type)) {
				$type = "CNAME";
			}
			if ($this->record_type === "TEMPLATE" && preg_match(VALID_TEMPLATE_DOMAIN, $content) === 0) {
				return array(
					"message" => $prefix . "A $type template record must contain a valid FQDN without trailing dot. May also end with [ZONE].",
					"code" => "RECORD_RHS_INVALID_FQDN"
				);
			} else if ($this->record_type !== "TEMPLATE" && preg_match(VALID_DOMAIN, $content) === 0) {
				return array(
					"message" => $prefix . "A $type record must contain a valid FQDN without trailing dot.",
					"code" => "RECORD_RHS_INVALID_FQDN"
				);
			}
			break;
		case "NAPTR":
			$parts = explode(" ", $content);
			if (count($parts) !== 6) {
				return array(
					"message" => $prefix . "A NAPTR record must provide all 6 parts (note the quotes and trailing dot): <order> <preference> \"<flags>\" \"<service>\" \"<regexp>\" replacement.",
					"code" => "RECORD_RHS_NAPTR_PARTS_MISSING"
				);
			}
			$naptr_terminal = false;
			$naptr_regex = false;
			for ($i = 0; $i < count($parts); $i++) {
				switch ($i) {
				case 0: // Order
				case 1: // Preference
					if (!ctype_digit($parts[$i])) {
						return array(
							"message" => $prefix . sprintf("NAPTR record part %d must be a valid integer.", $i+1),
							"code" => "RECORD_RHS_NAPTR_INVALID_PART_" . $i
						);
					}
					break;
				case 2: // Flags
					if (preg_match(VALID_QUOTED, $parts[$i], $p) === 0) {
						return array(
							"message" => $prefix . sprintf("NAPTR record part %d must be a valid quoted string.", $i+1),
							"code" => "RECORD_RHS_NAPTR_INVALID_PART_" . $i
						);
					}
					if (preg_match(NAPTR_FLAGS_VALID, $p[1]) === 0) {
						return array(
							"message" => $prefix . sprintf("NAPTR record part %d contains invalid characters. May only contain alphanumeric characters.", $i+1),
							"code" => "RECORD_RHS_NAPTR_INVALID_PART_" . $i
						);
					}
					if (preg_match_all(NAPTR_FLAGS_EXCLUSIVE, $p[1], $q) > 1) {
						return array(
							"message" => $prefix . sprintf("NAPTR record part %d contains too many multiple exclusive FLAGS: S, A, U). Use only one at a time.", $i+1),
							"code" => "RECORD_RHS_NAPTR_INVALID_PART_" . $i
						);
					}
					switch (strtolower($p[1])) {
						case "s":
						case "a":
						case "u":
							$naptr_terminal = true;
							break;
						default:
							$naptr_terminal = false;
							break;
					}
					unset($p);
					unset($q);
					break;
				case 3: // Service
					if (preg_match(VALID_QUOTED, $parts[$i], $p) === 0) {
						return array(
							"message" => $prefix . sprintf("NAPTR record part %d must be a valid quoted string.", $i+1),
							"code" => "RECORD_RHS_NAPTR_INVALID_PART_" . $i
						);
					}
					if ($naptr_terminal && empty($p[1])) {
						return array(
							"message" => $prefix . sprintf("NAPTR record part %d is invalid. A SERVICE must be specified if the FLAGS include a terminal flag.", $i+1),
							"code" => "RECORD_RHS_NAPTR_INVALID_PART_" . $i
						);
					}
					if (preg_match(NAPTR_SERVICE_VALID, $p[1]) === 0) {
						return array(
							"message" => $prefix . sprintf("NAPTR record part %d is invalid.", $i+1),
							"code" => "RECORD_RHS_NAPTR_INVALID_PART_" . $i
						);
					}
					unset($p);
					break;
				case 4: // Regexp
					if (preg_match(VALID_QUOTED, $parts[$i], $p) === 0) {
						return array(
							"message" => $prefix . sprintf("NAPTR record part %d must be a valid quoted string.", $i+1),
							"code" => "RECORD_RHS_NAPTR_INVALID_PART_" . $i
						);
					}
					if (!empty($p[1])) {
						$naptr_regex = true;
						$delimiter = substr($p[1], 0, 1);
						$reg = substr($p[1], 1);
						if (preg_match(NAPTR_REGEX_VALID_DELIMITER, $delimiter) === 0) {
							return array(
								"message" => $prefix . sprintf("NAPTR record part %d contains an invalid POSIX replacement regexp. Delimiter may be any character except 'i', '\\' and may not be a digit. ", $i+1),
								"code" => "RECORD_RHS_NAPTR_INVALID_PART_" . $i
							);
						}
						$partz = explode($delimiter, $reg);
						if (count($partz) !== 3) {
							return array(
								"message" => $prefix . sprintf("NAPTR record %d contains an invalid POSIX replacement regexp. Not all parts were specified.", $i+1),
								"code" => "RECORD_RHS_NAPTR_INVALID_PART_" . $i
							);
						}
						if (preg_match(NAPTR_REGEX_VALID_BACKREF, $partz[1]) === 0) {
							return array(
								"message" => $prefix . sprintf("NAPTR record part %d contains an invalid POSIX replacement regexp. May only contain one backref in the form of '\\1'.", $i+1),
								"code" => "RECORD_RHS_NAPTR_INVALID_PART_" . $i
							);
						}
						if (preg_match(NAPTR_REGEX_VALID_FLAG, $partz[2]) === 0) {
							return array(
								"message" => $prefix . sprintf("NAPTR record part %d contains an invalid POSIX regexp flag. May optionally contain 'i', or nothing at all.", $i+1),
								"code" => "RECORD_RHS_NAPTR_INVALID_PART_" . $i
							);
						}
						unset($delimiter);
						unset($partz);
					}
					unset($p);
					break;
				case 5: // Replacement
					if (preg_match(VALID_NOTEMPTY, $parts[$i], $p) === 0) {
						return array(
							"message" => $prefix . sprintf("NAPTR record part %d must be a valid record pointer, or a single dot (.).", $i+1),
							"code" => "RECORD_RHS_NAPTR_INVALID_PART_" . $i
						);
					}
					if (ValidatorConfig::BIND_COMPATABILITY === true) {
						$replacement = $p[1];
						if ($naptr_regex && $replacement != ".") {
							return array(
								"message" => $prefix . sprintf("NAPTR record part %d is invalid. REGEXP and REPLACEMENT should not be used at the same time.", $i+1),
								"code" => "RECORD_RHS_NAPTR_INVALID_PART_" . $i
							);
						}
					} else {
						$replacement = HelperFunctions::str_replace_last(".", "", $p[1]);
						if ($naptr_regex && $replacement != "") {
							return array(
								"message" => $prefix . sprintf("NAPTR record part %d is invalid. REGEXP and REPLACEMENT should not be used at the same time.", $i+1),
								"code" => "RECORD_RHS_NAPTR_INVALID_PART_" . $i
							);
						}
					}
					if (!empty($replacement) && preg_match(VALID_DOMAIN, $replacement) === 0 && preg_match(VALID_EMPTY_DOMAIN, $replacement) === 0) {
						return array(
							"message" => $prefix . sprintf("NAPTR record part %d is invalid. REPLACEMENT must be either '.' or a valid FQDN.", $i+1),
							"code" => "RECORD_RHS_NAPTR_INVALID_PART_" . $i
						);
					}
					unset($replacement);
					unset($p);
					break;
				}
			}
			break;
		case "RP":
			$parts = explode(" ", $content);
			if (count($parts) !== 2) {
				return array(
					"message" => $prefix . "A RP record must provide all 2 parts: <mailbox name> <more-info pointer>",
					"code" => "RECORD_RHS_RP_PARTS_MISSING"
				);
			}
			if ($this->record_type === "TEMPLATE") {
				if (preg_match(VALID_TEMPLATE_DOMAIN, $parts[0]) === 0) {
					return array(
						"message" => $prefix . "A RP records mailbox name must be an email address with the at-sign replaced by a dot (.). May also end with [ZONE]",
						"code" => "RECORD_RHS_RP_INVALID_PART_0"
					);
				}
				if (preg_match(VALID_TEMPLATE_DOMAIN, $parts[1]) === 0) {
					return array(
						"message" => $prefix . "A RP records more-info pointer must be a valid FQDN. May also end with [ZONE]",
						"code" => "RECORD_RHS_RP_INVALID_PART_1"
					);
				}
			} else {
				if (preg_match(VALID_DOMAIN, $parts[0]) === 0) {
					return array(
						"message" => $prefix . "A RP records mailbox name must be an email address with the at-sign replaced by a dot (.).",
						"code" => "RECORD_RHS_RP_INVALID_PART_0"
					);
				}
				if (preg_match(VALID_DOMAIN, $parts[1]) === 0) {
					return array(
						"message" => $prefix . "A RP records more-info pointer must be a valid FQDN.",
						"code" => "RECORD_RHS_RP_INVALID_PART_1"
					);
				}

			}
			break;
		case "SOA":
			$parts = explode(" ", $content);
			if (count($parts) !== 7) {
				return array(
					"message" => $prefix . "A SOA record must provide all 7 parts: <primary> <hostmaster> <serial> <refresh> <retry> <expire> <default_ttl>",
					"code" => "RECORD_RHS_SOA_PARTS_MISSING"
				);
			}
			for ($i = 0; $i < count($parts); $i++) {
				switch ($i) {
				case 0:
					if (preg_match(VALID_DOMAIN, $parts[$i]) === 0) {
						return array(
							"message" => $prefix . "A SOA record must provide a valid FQDN as primary hostname.",
							"code" => "RECORD_RHS_SOA_INVALID_PART_" . $i
						);
					}
					break;
				case 1:
					if (filter_var($parts[$i], FILTER_VALIDATE_EMAIL) === false && preg_match(VALID_DOMAIN, $parts[$i]) === 0) {
						return array(
							"message" => $prefix . "A SOA record must provide a valid email address as hostmaster.",
							"code" => "RECORD_RHS_SOA_INVALID_PART_" . $i
						);
					}
					break;
				case 2:
				case 3:
				case 4:
				case 5:
				case 6:
					if (!ctype_digit($parts[$i])) {
						return array(
							"message" => sprintf("SOA record part %d must be a valid integer.", $i+1),
							"code" => "RECORD_RHS_SOA_INVALID_PART_" . $i
						);
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
				return array(
					"message" => $prefix . "A $type record must provide a valid quoted string.",
					"code" => "RECORD_RHS_INVALID_QUOTED_STRING"
				);
			}
			break;
		case "SSHFP":
			$parts = explode(" ", $content);
			if (count($parts) !== 3) {
				return array(
					"message" => $prefix . "A SSHFP record must provide all 3 parts: <algorithm> <fp-type> <fingeprint>",
					"code" => "RECORD_RHS_SSHFP_PARTS_MISSING"
				);
			}
			for ($i = 0; $i < count($parts); $i++) {
				switch ($i) {
				case 0:
					if ($parts[$i] != "1" && $parts[$i] != "2" && $parts[$i] != "3") {
						return array(
							"message" => $prefix . "A SSHFP record must provide either 1 (RSA), 2 (DSA) or 3 (ECDSA) as algorithm.",
							"code" => "RECORD_RHS_SSHFP_INVALID_PART_0"
						);
					}
					break;
				case 1:
					if ($parts[$i] != "1" && $parts[$i] != "2") {
						return array(
							"message" => $prefix . "A SSHFP record must provide 1 (SHA-1) or 2 (SHA-256) as fp-type.",
							"code" => "RECORD_RHS_SSHFP_INVALID_PART_1"
						);
					}
					break;
				case 2:
					if ($parts[1] == "1" && strlen($parts[$i]) !== 40) {
						return array(
							"message" => $prefix . "A SSHFP record must provide a SHA-1 fingerprint as a 40 character ASCII hexadecimal string.",
							"code" => "RECORD_RHS_SSHFP_INVALID_PART_2"
						);
					} else if ($parts[1] == "2" && strlen($parts[$i]) !== 64) {
						return array(
							"message" => $prefix . "A SSHFP record must provide a SHA-256 fingerprint as a 64 character ASCII hexadecimal string.",
							"code" => "RECORD_RHS_SSHFP_INVALID_PART_2"
						);
					}
					break;
				}
			}
			break;
		case "SRV":
			if (!isset($this->priority)) {
				return array(
					"message" => $prefix . "A SRV record must also provide a priority.",
					"code" => "RECORD_RHS_MISSING_PRIORITY"
				);
			}
			$parts = explode(" ", $content);
			if (count($parts) !== 3) {
				return array(
					"message" => $prefix . "A SRV record must provide all 3 parts: <weight> <port> <service>",
					"code" => "RECORD_RHS_SRV_PARTS_MISSING"
				);
			}
			for ($i = 0; $i < count($parts); $i++) {
				switch ($i) {
				case 0:
				case 1:
					if (!ctype_digit($parts[$i])) {
						return array(
							"message" => $prefix . sprintf("SRV record part %d must be a valid integer.", $i+1),
							"code" => "RECORD_RHS_SRV_INVALID_PART_" . $i
						);
					}
					break;
				case 2:
					if ($this->record_type === "TEMPLATE" && preg_match(VALID_TEMPLATE_DOMAIN, $parts[$i]) === 0) {
						return array(
							"message" => $prefix . "A SRV record must provide a valid FQDN as service. May also end with [ZONE]",
							"code" => "RECORD_RHS_SRV_INVALID_PART_" . $i
						);
					} else if ($this->record_type !== "TEMPLATE" && preg_match(VALID_DOMAIN, $parts[$i]) === 0) {
						return array(
							"message" => $prefix . "A SRV record must provide a valid FQDN as service.",
							"code" => "RECORD_RHS_SRV_INVALID_PART_" . $i
						);
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

	public function check_record_priority($content) {
		if (($this->type == "MX") || ($this->type == "SRV")) {
			if (preg_match(VALID_INT, $content)) {
				return true;
			}
		} else {
			if (preg_match(VALID_INT, $content) || !isset($content)) {
				return true;
			}
		}
		return array(
			"message" => "Record has invalid priority",
			"code" => "RECORD_INVALID_PRIORITY"
		);
	}
}

class ArpaValidator extends Validator {
	protected $rules = array(
		"identifier" => array(
			"valid_identifier" => array(
				"rule" => array("check_valid_identifier"),
				"code" => "ARPA_INVALID_IDENTIFIER",
				"message" => "Identifier is not valid. Must be a single IPv4 or IPv6 address."
			)
		),
		"reverse_dns" => array(
			"valid_reverse_dns" => array(
				"rule" => VALID_DOMAIN,
				"code" => "RECORD_RHS_INVALID_FQDN",
				"message" => "Reverse DNS is not valid. Must be a valid FQDN."
			)
		),
		"query" => array(
			"valid_query" => array(
				"rule" => VALID_RANGE_QUERY,
				"code" => "ARPA_INVALID_QUERY",
				"message" => "Query is invalid. May only contain alphanumeric characters, dashes (-), dots (.) and wildcards (*). Multiple queries must be separated by comma's."
			)
		),
	);

	public function check_valid_identifier($content) {
		if (preg_match(VALID_IPV4, $content) || preg_match(VALID_IPV6, $content)) {
			return true;
		}
		return false;
	}
}

?>
