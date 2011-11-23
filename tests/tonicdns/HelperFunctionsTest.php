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

require_once("../conf/validator.conf.php");
require_once("../lib/validator.php");
require_once("../classes/HelperFunctions.class.php");
require_once("../classes/Validators.class.php");

/**
 * HelperFunctions unit tests
 */
class HelperFunctionsTest extends PHPUnit_Framework_TestCase {
	private $helper = null;

	private $ipv6 = array(
		"normal" => "2001:838:300:417::",
		"normal_expanded" => "2001:0838:0300:0417:0000:0000:0000:0000",
		"short" => "2001::2",
		"long" => "2001:1:2:3:4:5:6:7",
		"half_range" => "2001:0838:0300:0417:0000:0000:0000:0000/64",
	);

	private $arpa6 = array(
		"normal" => "0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.7.1.4.0.0.0.3.0.8.3.8.0.1.0.0.2.ip6.arpa",
		"half" => "7.1.4.0.0.0.3.0.8.3.8.0.1.0.0.2.ip6.arpa",
	);

	private $ipv4 = array(
		"normal" => "85.158.202.0",
		"half_range" => "85.158.202.0/24",
		"quarter_range" => "85.158.202.0/25",
		"quarter_max" => "85.158.202.127",
		"quarter_min" => "85.158.202.0",
		"quarter_in" => "85.158.202.1",
		"quarter_out" => "85.158.202.128",
	);

	private $arpa4 = array(
		"normal" => "0.202.158.85.in-addr.arpa",
		"half" => "202.158.85.in-addr.arpa"
	);

	public function __construct() {
		$this->helper = new HelperFunctions();
	}

	public function test_ipv6_expand() {
		$result = $this->helper->ipv6_expand($this->ipv6['normal']);

		$this->assertNotEquals($this->ipv6['normal'], $result);
		$this->assertFalse(strpos("::", $result));
		$this->assertEquals($this->ipv6['normal_expanded'], $result);
	}

	public function test_ipv6_compress() {
		$result = $this->helper->ipv6_compress($this->ipv6['normal']);

		$this->assertStringEndsWith("::", $result);
		$this->assertTrue(substr_count($result, "::") === 1);
		$this->assertEquals($this->ipv6['normal'], $result);

		$result = $this->helper->ipv6_compress($this->ipv6['short']);

		$this->assertTrue(substr_count($result, "::") === 1);
		$this->assertEquals($this->ipv6['short'], $result);

		$result = $this->helper->ipv6_compress($this->ipv6['long']);

		$this->assertFalse(strpos(":", $result));
		$this->assertEquals($this->ipv6['long'], $result);
	}

	public function test_ipv6_to_arpa() {
		$result = $this->helper->ip_to_arpa($this->ipv6['normal']);

		$this->assertFalse(strpos(".in-addr.arpa", $result));
		$this->assertStringEndsWith(".ip6.arpa", $result);
		$this->assertFalse(strpos(":", $result));
		$this->assertEquals($this->arpa6['normal'], $result);
	}

	public function test_ipv4_to_arpa() {
		$result = $this->helper->ip_to_arpa($this->ipv4['normal']);

		$this->assertFalse(strpos(".ip6.arpa", $result));
		$this->assertStringEndsWith(".in-addr.arpa", $result);
		$this->assertEquals($this->arpa4['normal'], $result);
	}

	public function test_arpa_to_ipv6() {
		$result = $this->helper->arpa_to_ip($this->arpa6['normal']);

		$this->assertFalse(strpos(".ip6.arpa", $result));
		$this->assertFalse(strpos("::", $result));
		$this->assertEquals($this->ipv6['normal_expanded'], $result);
	}

	public function test_arpa_to_ipv4() {
		$result = $this->helper->arpa_to_ip($this->arpa4['half']);

		$this->assertFalse(strpos(".in-addr.arpa", $result));
		$this->assertEquals($this->ipv4['normal'], $result);
	}

	public function test_arpa_to_ipv6_cidr() {
		$result = $this->helper->arpa_to_ip_cidr($this->arpa6['half']);

		$this->assertFalse(strpos(".ip6.arpa", $result));
		$this->assertFalse(strpos("::", $result));
		$this->assertEquals($this->ipv6['half_range'], $result);
	}

	public function test_arpa_to_ipv4_cidr() {
		$result = $this->helper->arpa_to_ip_cidr($this->arpa4['half']);

		$this->assertFalse(strpos(".in-addr.arpa", $result));
		$this->assertFalse(strpos(":", $result));
		$this->assertEquals($this->ipv4['half_range'], $result);
	}

	public function test_truncate_arpa() {
		$result = $this->helper->truncate_arpa($this->arpa6['normal'], 2);

		$this->assertTrue($result !== false);
		$this->assertEquals(substr($this->arpa6['normal'], 4), $result);

		$result = $this->helper->truncate_arpa($this->arpa6['normal'], 32);

		$this->assertFalse($result);
	}

	public function test_calc_ipv4_range() {
		$result = $this->helper->calc_ipv4_range($this->ipv4['quarter_range']);

		$this->assertCount(2, $result);
		$this->assertContainsOnly("string", $result);
		$this->assertEquals($this->ipv4['quarter_min'], $result['min']);
		$this->assertEquals($this->ipv4['quarter_max'], $result['max']);

		return $result;
	}

	/**
	 * @depends test_calc_ipv4_range
	 */
	public function test_is_ipv4_in_range(array $range) {
		$result = $this->helper->is_ipv4_in_range($range, $this->ipv4['quarter_in']);

		$this->assertTrue($result);

		$result = $this->helper->is_ipv4_in_range($range, $this->ipv4['quarter_out']);

		$this->assertFalse($result);
	}

	/**
	 * @depends test_calc_ipv4_range
	 */
	public function test_expand_ipv4_range(array $range) {
		$result = $this->helper->expand_ipv4_range($range);

		$this->assertInternalType("array", $result);
		$this->assertCount(128, $result);
		$this->assertContainsOnly("string", $result);

		$wrong = array("min" => $this->ipv4['quarter_max'], "max" => $this->ipv4['quarter_min']);

		$result = $this->helper->expand_ipv4_range($wrong);

		$this->assertEmpty($result);
	}
}
?>
