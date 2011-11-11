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
class ArpaFunctions {
	public function get_all_arpa($response, &$out = null, $query = null) {
		$zones = array();
		$filter_ranges = array();
		if (empty($query)) {
			ZoneFunctions::query_zones($response, "*.in-addr.arpa", $temp);
			if (!empty($temp)) {
				foreach ($temp as $t) {
					$zones[] = $t['name'];
				}
			}
			ZoneFunctions::query_zones($response, "*.ip6.arpa", $temp);
			if (!empty($temp)) {
				foreach ($temp as $t) {
					$zones[] = $t['name'];
				}
			}
		} else {
			$q = explode(",", $query);
			$singles = array();
			$singles6 = array();
			$ranges = array();

			foreach ($q as $s) {
				if (preg_match(VALID_IPV4_RANGE, $s)) {
					$range = HelperFunctions::calc_ipv4_range($s);

					if (!empty($singles)) {
						for ($i = count($singles) - 1; $i >= 0; $i--) {
							if (HelperFunctions::is_ipv4_in_range($range, $singles[$i])) {
								unset($singles[$i]);
							}
						}
					}
					$ranges[] = $range;
					continue;
				}
				if (preg_match(VALID_IPV4, $s)) {
					if (!empty($ranges)) {
						$in_range = false;
						foreach ($ranges as $range) {
							if (HelperFunctions::is_ipv4_in_range($range, $s)) {
								$in_range = true;
								break;
							}
						}
						if (!$in_range) {
							$singles[] = $s;
						}
					} else {
						$singles[] = $s;
					}
					continue;
				}
				if (preg_match(VALID_IPV6_RANGE, $s)) {
					$r = explode("/", $s, 2);
					$s = $r[0];
				}
				if (preg_match(VALID_IPV6, $s)) {
					if (!in_array($s, $singles6)) {
						$singles6[] = $s;
					}
					continue;
				}
			}

			$output = array();

			foreach ($ranges as $range) {
				$singles = array_merge($singles, HelperFunctions::expand_ipv4_range($range));
			}
			$singles = array_merge($singles, $singles6);

			foreach ($singles as $single) {
				$arpa = HelperFunctions::ip_to_arpa($single);
				$found = null;
				for ($i = 0; ($ret = HelperFunctions::truncate_arpa($arpa, $i)) !== false; $i++) {
					if (in_array($ret, $zones) !== false) {
						$found = true;
						break;
					}
					$res = ZoneFunctions::get_zone($response, $ret, $out, false, false);

					if ($res->code !== Response::NOTFOUND) {
						$found = $ret;
						break;
					}
				}

				if ($found === true) {
					continue;
				} elseif ($found) {
					$zones[] = $found;
				} else {
					$output[] = array(
						"name" => $arpa,
						"ip" => $single,
						"reverse_dns" => null,
						"arpa_zone" => null
					);
				}
			}
			$filter_ranges = $ranges;
		}

		foreach ($zones as $zone) {
			$records = null;
			ZoneFunctions::get_zone($response, $zone, $records);
			if (empty($records)) {
				continue;
			}
			foreach ($records['records'] as $record) {
				if ($record['type'] == "PTR") {
					$ip = HelperFunctions::arpa_to_ip($record['name']);
					if (!empty($filter_ranges) && 
						strpos(":", $ip) === false) {
							$allowed = false;
							foreach ($filter_ranges as $filter) {
								if (HelperFunctions::is_ipv4_in_range($filter, $ip)) {
									$allowed = true;
									break;
								}
							}
							if (!$allowed) {
								continue;
							}
					}
					$output[] = array(
						"name" => $record['name'],
						"ip" => HelperFunctions::arpa_to_ip($record['name']),
						"reverse_dns" => $record['content'],
						"arpa_zone" => $zone
					);
				}
			}
		}

		if (empty($output)) {
			$response->code = Response::NOTFOUND;
			$response->error = "Could not find any records for given query.";
			$out = false;
		} else {
			$response->code = Response::OK;
			$response->body = $output;
			$response->log_message = sprintf("Query returned %d Arpa records", count($output));
			$out = $output;
		}
		return $response;
	}

	public function query_arpa($response, $query, &$out = null) {
		return ArpaFunctions::get_all_arpa($response, $out, $query);
	}

	public function get_arpa($response, $identifier, &$out = null, $extra_props = false) {
		$arpa = HelperFunctions::ip_to_arpa($identifier);

		for ($i = 0; ($ret = HelperFunctions::truncate_arpa($arpa, $i)) !== false; $i++) {
			$response = ZoneFunctions::get_zone($response, $ret, $out);

			if ($response->code !== Response::NOTFOUND) {
				foreach ($out['records'] as $record) {
					if ($record['type'] == "PTR" && 
						HelperFunctions::ipv6_expand($identifier) == HelperFunctions::arpa_to_ip($record['name'])) {
							$output = array(
								"name" => $record['name'],
								"ip" => $identifier,
								"reverse_dns" => $record['content'],
								"arpa_zone" => $ret
							);

							if ($extra_props) {
								$output['ttl'] = $record['ttl'];
								$output['priority'] = $record['priority'];
							}

							$response->code = Response::OK;
							$response->body = $output;
							$response->log_message = sprintf("Retrieved Arpa zone '%s' for IP '%s'", $record['name'], $identifier);
							$out = $output;
							return $response;
						}
				}
			}
		}

		$response->code = Response::NOTFOUND;
		$response->error = "Could not find the reverse zone for " . $identifier;
		$out = false;
		return $response;
	}

	public function create_arpa($response, $identifier, $data, &$out = null) {
		ArpaFunctions::get_arpa($response, $identifier, $o);

		if (!empty($o)) {
			$response->code = Response::CONFLICT;
			$response->error = "Resource already exists";
			$out = false;
			return $response;
		}

		unset($o);

		ZoneFunctions::get_zone($response, $identifier, $o, false);

		if (empty($o)) {
			$response->code = Response::NOTFOUND;
			$response->error = sprintf("Could not find Arpa zone for ip '%s'", $identifier);
			$out = false;
			return $response;
		}

		$zone = $o['name'];

		unset($o);

		$record = array(
			array(
				"name" => ZoneFunctions::ip_to_arpa($identifier),
				"type" => "PTR",
				"content" => $data
			)
		);

		$response = ZoneFunctions::create_records($response, $zone, $record, $o, true);

		if (empty($o)) {
			return $response;
		}

		$response->code = Response::OK;
		$response->body = true;
		$response->log_message = sprintf("Added '%s' to Arpa zone '%s'", $identifier, $zone);

		$out = true;

		return $response;
	}

	public function delete_arpa($response, $identifier, &$out = null) {
		ArpaFunctions::get_arpa($response, $identifier, $o, true);

		if (empty($o)) {
			$response->code = Response::NOTFOUND;
			$response->error = sprintf("Could not find Arpa zone for IP %s", $identifier);
			$out = false;
			return $response;
		}

		unset($o);

		$record = new stdClass();
		$record->name = $o['name'];
		$record->type = "PTR";
		$record->content = $o['reverse_dns'];
		$record->priority = $o['priority'];

		$req = new stdClass();
		$req->records = array($record);

		$response = ZoneFunctions::delete_records($response, $o['arpa_zone'], $req, $o);

		if (empty($o)) {
			$out = false;
			return $response;
		}

		$response->code = Response::OK;
		$response->body = true;
		$response->log_message = sprintf("Deleted Arpa record for IP %s", $identifier);

		$out = true;

		return $response;
	}
}
?>
