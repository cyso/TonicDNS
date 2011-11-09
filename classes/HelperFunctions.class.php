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
		if (strpos($ip, '::') !== false)
			$ip = str_replace('::', str_repeat(':0', 8 - substr_count($ip, ':')).':', $ip);

		if (strpos($ip, ':') === 0)
			$ip = '0'.$ip;

		return $ip;
	}

	public function ipv6_to_arpa($ip) {
		$ip = ZoneFunctions::ipv6_expand($ip);

		$p = explode(":", $ip);
		$n = '';

		foreach($p as $part)
			$n .= str_pad($part, 4, "0", STR_PAD_LEFT);

		$n = str_split(strrev($n));

		return implode(".", $n) . ".ip6.arpa";
	}

	public function ipv4_to_arpa($ip) {
		$p = explode(".", $ip);

		return "{$p[3]}.{$p[2]}.{$p[1]}.{$p[0]}.in-addr.arpa";
	}

	public function truncate_arpa($in, $n=0) {
		$parts = explode(".", $in);

		if ($n >= count($parts)-2)
			return false;

		for($i = 0; $i < $n; $i++)
			array_shift($parts);

		return implode(".", $parts);
	}
}
