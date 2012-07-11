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

require_once("conf/validator.conf.php");
require_once("lib/validator.php");
require_once("classes/Validators.class.php");

define("TESTING_MODE", true);

/**
 * Validators unit tests
 */
class ValidatorsTest extends PHPUnit_Framework_TestCase {
	public function test_validate_authentication() {
		$data = array("username" => "Lord_Gaav", "password" => "123StrongPassword", "local_user" => "gaav", "token" => "");
		$validator = new AuthenticationValidator($data);

		$this->assertFalse($validator->validates());
		$this->assertCount(1, $validator->getErrors());

		$data['token'] = "0123456789012345678901234567890123456789";

		$validator = new AuthenticationValidator($data);
		$this->assertTrue($validator->validates());
		$this->assertEmpty($validator->getErrors());
	}

	public function test_validate_template() {
		$data = array(
			"identifier" => "test_template", 
			"description" => "Test Description", 
			"entries" => array(
				array(
					"name" => "[ZONE].example.toolongtld",
					"type" => "A",
					"content" => "127.0.0.2"
				),
				array(
					"name" => "test.[ZONE].example.com",
					"type" => "MX",
					"content" => "mx.example.com",
				),
				array(
					"name" => "example.com.[ZONE]",
					"type" => "CNAME",
					"content" => "example.com"
				)
			)
		);

		// Entry 1 invalid TLD, Entry 2 missing priority
		// [ZONE] may only be used as record suffix
		$validator = new TemplateValidator($data);

		$this->assertFalse($validator->validates());
		$this->assertCount(1, $validator->getErrors());

		$data['entries'][0]['name'] = "example.[ZONE]";
		$data['entries'][1]['name'] = "example.[ZONE]";
		$data['entries'][1]['priority'] = 123;

		// Entry 1 and 2 fixed
		$validator = new TemplateValidator($data);

		$this->assertTrue($validator->validates());
		$this->assertEmpty($validator->getErrors());

		// Entry 3 name too long
		$data['entries'][2]['name'] = str_repeat(str_repeat("a", 60) . ".", 10) . "com";
		$validator = new TemplateValidator($data);

		$this->assertFalse($validator->validates());
		$this->assertCount(1, $validator->getErrors());

		$data['entries'] = null;

		// Empty templates not allowed
		$validator = new TemplateValidator($data);

		$this->assertFalse($validator->validates());
		$this->assertCount(1, $validator->getErrors());
	}

	public function test_validate_zone() {
		// Wrong identifier, type, master and templates.
		$data = array(
			"identifier" => "_example.com", 
			"name" => "example.com", 
			"type" => "master",
			"master" => "85.158.202.321",
			"templates" => "test_template++",
			"query" => "example*"
		);

		$validator = new ZoneValidator($data);

		$this->assertFalse($validator->validates());
		$this->assertCount(4, $validator->getErrors());

		// Wrong type, and templates
		unset($data['master']);
		$data['identifier'] = "example.com";
		$data['type'] = "MASTERRRR";
		$data['templates'] = array($data['templates']);

		$validator = new ZoneValidator($data);

		$this->assertFalse($validator->validates());
		$this->assertCount(2, $validator->getErrors());

		// Master should not have IP, invalid IP, empty template object
		$data['templates'][0] = new stdClass();
		$data['master'] = "85.158.202.321";
		$data['type'] = "MASTER";

		$validator = new ZoneValidator($data);

		$this->assertFalse($validator->validates());
		$this->assertCount(2, $validator->getErrors());

		// Invalid ip
		$data['templates'][0]->identifier = "test_template";
		$data['type'] = "SLAVE";

		$validator = new ZoneValidator($data);

		$this->assertFalse($validator->validates());
		$this->assertCount(1, $validator->getErrors());

		// Slave without IP
		unset($data['master']);

		$validator = new ZoneValidator($data);

		$this->assertFalse($validator->validates());
		$this->assertCount(1, $validator->getErrors());

		// All valid
		$data['master'] = "85.158.202.123";

		$validator = new ZoneValidator($data);

		$this->assertTrue($validator->validates());
		$this->assertEmpty($validator->getErrors());

		// Changing SLAVE zone records
		$data['records'] = array();

		$validator = new ZoneValidator($data);

		$this->assertFalse($validator->validates());
		$this->assertCount(1, $validator->getErrors());

		// Reset to valid NATIVE zone
		unset($data['query']);
		unset($data['templates']);
		unset($data['master']);
		$data['type'] = "NATIVE";

		$validator = new ZoneValidator($data);

		$this->assertTrue($validator->validates());
		$this->assertEmpty($validator->getErrors());

		// Invalid records type
		$data['records'] = new StdClass();

		$validator = new ZoneValidator($data);

		$this->assertFalse($validator->validates());
		$this->assertCount(1, $validator->getErrors());

		// Invalid record content
		$data['records'] = array(
			array(
				"name" => "example.com",
				"type" => "A",
				"content" => "127.0.0.22222"
			)
		);

		$validator = new ZoneValidator($data);

		$this->assertFalse($validator->validates());
		$this->assertCount(1, $validator->getErrors());

		// Record name label too long
		$data['records'] = array(
			array(
				"name" => str_repeat("a", 253) . ".com",
				"type" => "A",
				"content" => "127.0.0.2"
			)
		);

		$validator = new ZoneValidator($data);

		$this->assertFalse($validator->validates());
		$this->assertCount(1, $validator->getErrors());

		// Record name total size too long
		$data['records'] = array(
			array(
				"name" => str_repeat(str_repeat("a", 60) . ".", 10) . "com",
				"type" => "A",
				"content" => "127.0.0.2"
			)
		);

		$validator = new ZoneValidator($data);

		$this->assertFalse($validator->validates());
		$this->assertCount(1, $validator->getErrors());

		// All valid
		$data['records'] = array(
			array(
				"name" => "example.com",
				"type" => "A",
				"content" => "127.0.0.2"
			)
		);

		$validator = new ZoneValidator($data);

		$this->assertTrue($validator->validates());
		$this->assertEmpty($validator->getErrors());
	}

	public function test_validate_arpa() {
		$data = array(
			"identifier" => "2001:838:300:417:::",
			"reverse_dns" => "example.com",
			"query" => "2001:838:300:417::/80,127.0.0.1"
		);

		$validator = new ArpaValidator($data);

		$this->assertFalse($validator->validates());
		$this->assertCount(1, $validator->getErrors());

		$data['identifier'] = "2001:838:300:417::";

		$validator = new ArpaValidator($data);

		$this->assertTrue($validator->validates());
		$this->assertEmpty($validator->getErrors());
	}

	public function test_validate_record() {
		// Missing content
		$data = array(
			"name" => "example.toolongtld",
			"type" => "A",
			"content" => ""
		);

		$validator = new RecordValidator($data);

		$this->assertFalse($validator->validates());
		$this->assertCount(2, $validator->getErrors());

		// Missing type
		$data['name'] = "example.com";
		$data['type'] = "";
		$data['content'] = "127.0.0.2";

		$validator = new RecordValidator($data);

		// Invalid type
		$data['type'] = "ZZZZ";
		$data['content'] = "127.0.0.2";

		$validator = new RecordValidator($data);

		$this->assertFalse($validator->validates());
		$this->assertCount(1, $validator->getErrors());

		// Record content too long
		$data['type'] = "TXT";
		$data['content'] = '"' . str_repeat("a", 4096) . '"';

		$validator = new RecordValidator($data);

		$this->assertFalse($validator->validates());
		$this->assertCount(1, $validator->getErrors());

		// Invalid priority for non-MX/SRV record
		$data['content'] = '""';
		$data['priority'] = "a";

		$validator = new RecordValidator($data);

		$this->assertFalse($validator->validates());
		$this->assertCount(1, $validator->getErrors());

		// Valid priority for non-MX/SRV record
		$data['content'] = '""';
		$data['priority'] = 12;

		$validator = new RecordValidator($data);

		$this->assertTrue($validator->validates());
		$this->assertEmpty($validator->getErrors());

		// Invalid AAAA record
		unset($data['priority']);
		$data['type'] = "AAAA";
		$data['content'] = "2001:838:300:417:::";

		$validator = new RecordValidator($data);

		$this->assertFalse($validator->validates());
		$this->assertCount(1, $validator->getErrors());

		// Valid AAAA record
		$data['content'] = "2001:838:300:417::";

		$validator = new RecordValidator($data);

		$this->assertTrue($validator->validates());

		// Invalid NS record
		$data['type'] = "NS";
		$data['content'] = "example.toolongtld";

		$validator = new RecordValidator($data);

		$this->assertFalse($validator->validates());
		$this->assertCount(1, $validator->getErrors());

		// Invalid PTR record
		$data['type'] = "PTR";

		$validator = new RecordValidator($data);

		$this->assertFalse($validator->validates());
		$this->assertCount(1, $validator->getErrors());

		// Invalid NAPTR record
		$data['type'] = "NAPTR";
		$data['content'] = "only three parts";

		$validator = new RecordValidator($data);

		$this->assertFalse($validator->validates());
		$this->assertCount(1, $validator->getErrors());

		$data['content'] = 'a 10 "+A+B" "SIP" "" .';

		$validator = new RecordValidator($data);

		$this->assertFalse($validator->validates());
		$this->assertCount(1, $validator->getErrors());

		$data['content'] = '10 10 +A+B "SIP" "" .';

		$validator = new RecordValidator($data);

		$this->assertFalse($validator->validates());
		$this->assertCount(1, $validator->getErrors());

		$data['content'] = '10 10 "+A+B" "SIP" "" ';

		$validator = new RecordValidator($data);

		$this->assertFalse($validator->validates());
		$this->assertCount(1, $validator->getErrors());

		// Valid NAPTR record
		$data['content'] = '10 10 "+A+B" "SIP" "" .';

		$validator = new RecordValidator($data);
		$validator->validates();

		$this->assertTrue($validator->validates());
		$this->assertEmpty($validator->getErrors());

		// Invalid RP record
		$data['type'] = "RP";
		$data['content'] = "1 2 3";

		$validator = new RecordValidator($data);

		$this->assertFalse($validator->validates());
		$this->assertCount(1, $validator->getErrors());

		$data['content'] = "webmaster@example.com example.com";

		$validator = new RecordValidator($data);

		$this->assertFalse($validator->validates());
		$this->assertCount(1, $validator->getErrors());

		$data['content'] = "webmaster.example.com +invalid.example.com";

		$validator = new RecordValidator($data);

		$this->assertFalse($validator->validates());
		$this->assertCount(1, $validator->getErrors());

		// Valid RP record
		$data['content'] = "webmaster.example.com example.com";

		$validator = new RecordValidator($data);

		$this->assertTrue($validator->validates());
		$this->assertEmpty($validator->getErrors());

		// Invalid SOA record
		$data['type'] = "SOA";
		$data['content'] = "1 2";

		$validator = new RecordValidator($data);

		$this->assertFalse($validator->validates());
		$this->assertCount(1, $validator->getErrors());

		$data['content'] = "ns+.example.com hostmaster.example.com 0 86400 3600 86400 86400";

		$validator = new RecordValidator($data);

		$this->assertFalse($validator->validates());
		$this->assertCount(1, $validator->getErrors());

		$data['content'] = "ns.example.com hostmaster+.example.com 0 86400 3600 86400 86400";

		$validator = new RecordValidator($data);

		$this->assertFalse($validator->validates());
		$this->assertCount(1, $validator->getErrors());

		$data['content'] = "ns.example.com hostmaster.example.com a 86400 3600 86400 86400";

		$validator = new RecordValidator($data);

		$this->assertFalse($validator->validates());
		$this->assertCount(1, $validator->getErrors());

		// Valid SOA record
		$data['content'] = "ns.example.com hostmaster.example.com 0 86400 3600 86400 86400";

		$validator = new RecordValidator($data);

		$this->assertTrue($validator->validates());
		$this->assertEmpty($validator->getErrors());

		// Invalid SPF record
		$data['type'] = "SPF";
		$data['content'] = "unquoted string";

		$validator = new RecordValidator($data);

		$this->assertFalse($validator->validates());
		$this->assertCount(1, $validator->getErrors());

		// Invalid TXT record
		$data['type'] = "TXT";

		$validator = new RecordValidator($data);

		$this->assertFalse($validator->validates());
		$this->assertCount(1, $validator->getErrors());

		// Valid TXT/SPF record
		$data['content'] = '"quoted string"';

		$validator = new RecordValidator($data);

		$this->assertTrue($validator->validates());
		$this->assertEmpty($validator->getErrors());

		// Invalid SSHFP record
		$data['type'] = "SSHFP";
		$data['content'] = "1 2 3 4";

		$validator = new RecordValidator($data);

		$this->assertFalse($validator->validates());
		$this->assertCount(1, $validator->getErrors());

		$data['content'] = "3 1 0123456789ABCDEF0123456789ABCDEF01234567";

		$validator = new RecordValidator($data);

		$this->assertFalse($validator->validates());
		$this->assertCount(1, $validator->getErrors());

		$data['content'] = "1 2 0123456789ABCDEF0123456789ABCDEF01234567";

		$validator = new RecordValidator($data);

		$this->assertFalse($validator->validates());
		$this->assertCount(1, $validator->getErrors());

		$data['content'] = "1 1 0123456789ABCDEF0123456789ABCDEF01234567Z";

		$validator = new RecordValidator($data);

		$this->assertFalse($validator->validates());
		$this->assertCount(1, $validator->getErrors());

		// Valid SSHFP
		$data['content'] = "1 1 0123456789ABCDEF0123456789ABCDEF01234567";

		$validator = new RecordValidator($data);

		$this->assertTrue($validator->validates());
		$this->assertEmpty($validator->getErrors());

		// Invalid SRV record
		$data['type'] = "SRV";
		$data['content'] = "1 2 3 4";

		$validator = new RecordValidator($data);

		$this->assertFalse($validator->validates());
		$this->assertCount(1, $validator->getErrors());

		$data['priority'] = 10;
		$validator = new RecordValidator($data);

		$this->assertFalse($validator->validates());
		$this->assertCount(1, $validator->getErrors());

		$data['content'] = "a 443 service.example.com";
		$validator = new RecordValidator($data);

		$this->assertFalse($validator->validates());
		$this->assertCount(1, $validator->getErrors());

		$data['content'] = "100 a service.example.com";
		$validator = new RecordValidator($data);

		$this->assertFalse($validator->validates());
		$this->assertCount(1, $validator->getErrors());

		$data['content'] = "100 443 invalid+.service.example.com";
		$validator = new RecordValidator($data);

		$this->assertFalse($validator->validates());
		$this->assertCount(1, $validator->getErrors());

		// Valid SRV record
		$data['content'] = "100 443 service.example.com";
		$validator = new RecordValidator($data);

		$this->assertTrue($validator->validates());
		$this->assertEmpty($validator->getErrors());
	}
}

?>
