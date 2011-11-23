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
/**
 * Arpa support functions
 */
class HelperFunctions {
	public function ipv6_expand($ip) {
		if (!preg_match(VALID_IPV6, $ip))
			return $ip;
		if (strpos($ip, '::') !== false)
			$ip = str_replace('::', str_repeat(':0', 8 - substr_count($ip, ':')).':', $ip);

		if (strpos($ip, ':') === 0)
			$ip = '0'.$ip;

		$p = explode(":", $ip);
		$n = array();

		foreach($p as $part)
			$n[] = str_pad($part, 4, "0", STR_PAD_LEFT);

		return implode(":", $n);
	}

	public function ipv6_compress($ip) {
		$ip = HelperFunctions::ipv6_expand($ip);
		$p = explode(":", $ip);

		$ranges = array();

		$i = 0;
		for ($i = 0; $i < 8; $i++) {
			$p[$i] = intval($p[$i]);
			$count = 0;
			for ($j = $i; $j < 8; $j++) {
				if (intval($p[$j]) !== 0 && $count !== 0) {
					break;
				} else if (intval($p[$j]) === 0) {
					$count++;
				}
			}
			$ranges[$count] = $i;
		}

		krsort($ranges);
		$s = each($ranges);
		$start = $s['value'];
		$length = $s['key'];

		if ($length === 0) {
			return implode(":", $p);
		} else {
			$part1 = implode(":", array_slice($p, 0, $start));
			$part2 = implode(":", array_slice($p, $start + $length));

			return implode("::", array($part1, $part2));
		}
	}

	public function ipv6_to_arpa($ip) {
		$ip = HelperFunctions::ipv6_expand($ip);

		$p = explode(":", $ip);
		$n = '';

		foreach($p as $part)
			$n .= $part;

		$n = str_split(strrev($n));

		return implode(".", $n) . ".ip6.arpa";
	}

	public function arpa_to_ipv6($arpa) {
		$arpa = str_replace(".ip6.arpa", "", $arpa);

		$p = explode(".", $arpa);
		$p = array_reverse($p);

		$q = implode("", $p);
		return implode(":", str_split($q, 4));
	}

	public function ipv4_to_arpa($ip) {
		$p = explode(".", $ip);

		return "{$p[3]}.{$p[2]}.{$p[1]}.{$p[0]}.in-addr.arpa";
	}

	public function arpa_to_ipv4($arpa) {
		$arpa = str_replace(".in-addr.arpa", "", $arpa);
		$p = explode(".", $arpa);
		while (count($p) < 4) {
			array_unshift($p, 0);
		}

		return "{$p[3]}.{$p[2]}.{$p[1]}.{$p[0]}";
	}

	public function arpa_to_ipv4_cidr($arpa) {
		$arpa = str_replace(".in-addr.arpa", "", $arpa);
		$p = explode(".", $arpa);
		$cidr = count($p) * 8;
		while (count($p) < 4) {
			array_unshift($p, 0);
		}

		return "{$p[3]}.{$p[2]}.{$p[1]}.{$p[0]}/{$cidr}";
	}

	public function arpa_to_ipv6_cidr($arpa) {
		$arpa = str_replace(".ip6.arpa", "", $arpa);
		$p = explode(".", $arpa);
		$cidr = count($p) * 4;
		while (count($p) < 32) {
			array_unshift($p, 0);
		}

		$p = array_reverse($p);

		return implode(":", str_split(implode("", $p), 4)) . "/" . $cidr;
	}

	public function ip_to_arpa($ip) {
		if (strpos($ip, ":") !== false) {
			return HelperFunctions::ipv6_to_arpa($ip);
		} else {
			return HelperFunctions::ipv4_to_arpa($ip);
		}
	}

	public function arpa_to_ip($arpa) {
		if (strpos($arpa, "ip6") !== false) {
			return HelperFunctions::arpa_to_ipv6($arpa);
		} else {
			return HelperFunctions::arpa_to_ipv4($arpa);
		}
	}

	public function arpa_to_ip_cidr($arpa) {
		if (strpos($arpa, "ip6") !== false) {
			return HelperFunctions::arpa_to_ipv6_cidr($arpa);
		} else {
			return HelperFunctions::arpa_to_ipv4_cidr($arpa);
		}
	}

	public function truncate_arpa($in, $n=0) {
		$parts = explode(".", $in);

		if ($n >= count($parts)-2)
			return false;

		for($i = 0; $i < $n; $i++)
			array_shift($parts);

		return implode(".", $parts);
	}

	public function calc_ipv4_range($in) {
		$p = explode("/", $in);
		$bits = $p[1];
		$oct = explode(".", $p[0]);

		$addr = ($oct[0] << 24) | ($oct[1] << 16) | ($oct[2] << 8) | $oct[3];
		$mask = ($bits == 0) ? 0 : (~0 << (32 - $bits));

		$min = ($addr & $mask);
		$max = ($addr | (~$mask & 0xFFFFFFFF));

		return array(
			'min' => sprintf("%d.%d.%d.%d", ($min>>24) & 0xff, ($min>>16) & 0xff, ($min>>8) & 0xff, $min & 0xff),
			'max' => sprintf("%d.%d.%d.%d", ($max>>24) & 0xff, ($max>>16) & 0xff, ($max>>8) & 0xff, $max & 0xff),
		);
	}

	public function is_ipv4_in_range($range, $ip) {
		$max = ip2long($range['max']);
		$min = ip2long($range['min']);
		$ip = ip2long($ip);

		if ($max >= $ip && $ip >= $min) {
			return true;
		} else {
			return false;
		}
	}

	public function expand_ipv4_range($range) {
		$min = ip2long($range['min']);
		$max = ip2long($range['max']);
		$out = array();

		if (!$min || !$max || $min > $max) {
			return $out;
		}

		for ($i = $min; $i <= $max; $i++) {
			$out[] = long2ip($i);
		}

		return $out;
	}
}
