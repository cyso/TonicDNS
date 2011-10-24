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
 * Zone Resource support functions
 */
class ZoneFunctions {
	public function get_all_zones($response, &$out = null, $query = null) {
		try {
			$connection = new PDO(PowerDNSConfig::DB_DSN, PowerDNSConfig::DB_USER, PowerDNSConfig::DB_PASS);
		} catch (PDOException $e) {
			$response->code = Response::INTERNALSERVERERROR;
			$response->error = "Could not connect to PowerDNS server.";
			return $response;
		}

		if ($query === null) {
			$result = $connection->query(sprintf(
				"SELECT z.id as z_id, z.name as z_name, z.master as z_master, z.last_check as z_last_check, z.type as z_type, z.notified_serial as z_notified_serial
				 FROM `%s` z
				 ORDER BY z_name ASC;", PowerDNSConfig::DB_ZONE_TABLE)
			);

			if ($result === false) {
				$response->code = Response::INTERNALSERVERERROR;
				$response->error = "Could not query PowerDNS server.";
				return $response;
			}
		} else {
			$query = str_replace("*", "%", $query);

			$result = $connection->prepare(sprintf(
				"SELECT z.id as z_id, z.name as z_name, z.master as z_master, z.last_check as z_last_check, z.type as z_type, z.notified_serial as z_notified_serial
				 FROM `%s` z
				 WHERE z.name LIKE :query
				 ORDER BY z_name ASC;", PowerDNSConfig::DB_ZONE_TABLE
			 ));


			if ($result->execute(array(":query" => $query)) === false) {
				$response->code = Response::INTERNALSERVERERROR;
				$response->error = "Could not query PowerDNS server.";
				return $response;
			}
		}

		$output = array();
		while (($row = $result->fetch(PDO::FETCH_ASSOC)) !== false ) {
			$zone = array();
			$zone['name'] = $row['z_name'];
			$zone['type'] = $row['z_type'];
			if (!empty($row['z_master'])) { $zone['master'] = $row['z_master']; }
			if (!empty($row['z_last_check'])) { $zone['last_check'] = $row['z_last_check']; }
			if (!empty($row['z_notified_serial'])) { $zone['notified_serial'] = $row['z_notified_serial']; }

			$output[] = $zone;
		}

		$response->body = $output;
		$response->log_message = sprintf("Retrieved %d zones.", count($output));
		$out = $output;

		return $response;
	}

	public function query_zones($response, $query, &$out = null) {
		return ZoneFunctions::get_all_zones($response, $out, $query);
	}

	public function get_zone($response, $identifier, &$out = null, $details = true, $arpa_expand = true) {
		$arpa = null;
		if (preg_match(VALID_IPV4, $identifier) === 1) {
			$arpa = ZoneFunctions::ipv4_to_arpa($identifier);
		} else if (preg_match(VALID_IPV6, $identifier) === 1) {
			$arpa = ZoneFunctions::ipv6_to_arpa($identifier);
		}

		if ($arpa !== null && $arpa_expand === true) {
			for ($i = 0; ($ret = ZoneFunctions::truncate_arpa($arpa, $i)) !== false; $i++) {
				$response = ZoneFunctions::get_zone($response, $ret, $out, $details, false);

				if ($response->code !== Response::NOTFOUND) {
					return $response;
				}
			}

			$response->code = Response::NOTFOUND;
			$response->error = sprintf("Could not find a reverse DNS zone for %s", $identifier);
			return $response;
		}

		try {
			$connection = new PDO(PowerDNSConfig::DB_DSN, PowerDNSConfig::DB_USER, PowerDNSConfig::DB_PASS);
		} catch (PDOException $e) {
			$response->code = Response::INTERNALSERVERERROR;
			$response->error = "Could not connect to PowerDNS server.";
			return $response;
		}

		$statement = $connection->prepare(sprintf(
			"SELECT z.id as z_id, z.name as z_name, z.master as z_master, z.last_check as z_last_check, z.type as z_type, z.notified_serial as z_notified_serial,
			        r.id as r_id, r.name as r_name, r.type as r_type, r.content as r_content, r.ttl as r_ttl, r.prio as r_prio, r.change_date as r_change_date
			 FROM `%s` z
			 LEFT JOIN `%s` r ON (z.id = r.domain_id)
			 WHERE z.name = :name
			 ORDER BY r_name ASC,
			 r_type DESC,
			 r_prio ASC,
			 r_content ASC;", PowerDNSConfig::DB_ZONE_TABLE, PowerDNSConfig::DB_RECORD_TABLE)
		);

		if ($statement === false) {
			$response->code = Response::INTERNALSERVERERROR;
			$response->error = "Could not query PowerDNS server.";
			return $response;
		}

		if ($statement->execute(array(":name" => $identifier)) === false) {
			$response->code = Response::INTERNALSERVERERROR;
			$response->error = "Could not query PowerDNS server.";
			return $response;
		}

		$output = array();
		$first = true;
		while (($row = $statement->fetch(PDO::FETCH_ASSOC)) !== false) {
			if ($first) {
				$output['name'] = $row['z_name'];
				$output['type'] = $row['z_type'];
				if (!empty($row['z_master'])) { $output['master'] = $row['z_master']; }
				if (!empty($row['z_last_check'])) { $output['last_check'] = $row['z_last_check']; }
				if (!empty($row['z_notified_serial'])) { $output['notified_serial'] = $row['z_notified_serial']; }
				$first = false;

				if ($details === false) {
					break;
				}
			}

			if (empty($row['r_name']) && empty($row['r_content'])) {
				break;
			}

			$record = array();
			$record['name'] = $row['r_name'];
			$record['type'] = $row['r_type'];
			$record['content'] = $row['r_content'];
			$record['ttl'] = $row['r_ttl'];
			$record['priority'] = $row['r_prio'];
			if (!empty($row['r_change_date'])) { $record['change_date'] = $row['r_change_date']; }

			$output['records'][] = $record;
		}

		if (empty($output)) {
			$response->code = Response::NOTFOUND;
			$response->body = array();
			$response->log_message = sprintf("Zone %s was not found.", $identifier);
			$out = array();
		} else {
			$response->code = Response::OK;
			$response->body = $output;
			$response->log_message = sprintf("Zone %s with %d records was retrieved.", $identifier, count($output['records']));
			$out = $output;
		}

		return $response;
	}

	public function create_zone($response, $data, &$out = null) {
		ZoneFunctions::get_zone($response, $data->name, $o, false);

		if (!empty($o)) {
			$response->code = Response::INTERNALSERVERERROR;
			$response->error = "Resource already exists";
			$out = false;
			return $response;
		}

		unset($o);

		$records = array();
		if (isset($data->templates) && !empty($data->templates)) {
			foreach ($data->templates as $template) {
				$response = TemplateFunctions::get_template($response, $template->identifier, $p);

				if (empty($p)) {
					continue;
				} else {
					foreach ($p['entries'] as $entry) {
						$e = new stdClass();
						$e->name = str_replace(array("[ZONE]"), array($data->name), $entry['name']);
						$e->content = str_replace(array("[ZONE]"), array($data->name), $entry['content']);
						$e->type = $entry['type'];
						$e->ttl = $entry['ttl'];
						$e->priority = $entry['priority'];
						$records[] = $e;
					}
				}

				unset($p);
			}
		}

		if (isset($data->records) && !empty($data->records)) {
			$records = array_merge($records, $data->records);
		}

		try {
			$connection = new PDO(PowerDNSConfig::DB_DSN, PowerDNSConfig::DB_USER, PowerDNSConfig::DB_PASS);
		} catch (PDOException $e) {
			$response->code = Response::INTERNALSERVERERROR;
			$response->error = "Could not connect to PowerDNS server.";
			return $response;
		}

		$connection->beginTransaction();

		$zone = $connection->prepare(sprintf(
			"INSERT INTO `%s` (name, type, master) VALUES (:name, :type, :master);", PowerDNSConfig::DB_ZONE_TABLE
		));

		$zone->bindValue(":name", $data->name);
		$zone->bindValue(":type", strtoupper($data->type));
		if (isset($data->master) && !empty($data->master)) {
			$zone->bindValue(":master", $data->master);
		} else {
			$zone->bindValue(":master", null, PDO::PARAM_NULL);
		}

		if ($zone->execute() === false) {
			$response->code = Response::INTERNALSERVERERROR;
			$response->error = "Rolling back transaction, failed to insert zone.";

			$connection->rollback();
			$out = false;

			return $response;
		}

		if (!empty($records)) {
			$object = new stdClass();
			$object->records = $records;
			$response = ZoneFunctions::create_records($response, $connection->lastInsertId(), $object, $r, true, $connection);

			if ($r === false) {
				$connection->rollback();
				$out = false;

				return $response;
			}
		}

		$connection->commit();

		$response->code = Response::OK;
		$response->body = true;
		$response->log_message = sprintf("Zone %s was created with %d records.", $data->name, count($records));
		$out = true;

		return $response;
	}

	public function create_records($response, $identifier, $data, &$out = null, $skip_domain_check = false, $connection = null) {
		if ($skip_domain_check === false) {
			ZoneFunctions::get_zone($response, $identifier, $o, false);

			if (empty($o)) {
				$response->code = Response::NOTFOUND;
				$response->error = "Zone does not exist";
				$out = false;
				return $response;
			}

			unset($o);
		}

		$commit = false;
		if ($connection === null) {
			try {
				$connection = new PDO(PowerDNSConfig::DB_DSN, PowerDNSConfig::DB_USER, PowerDNSConfig::DB_PASS);
			} catch (PDOException $e) {
				$response->code = Response::INTERNALSERVERERROR;
				$response->error = "Could not connect to PowerDNS server.";
				return $response;
			}

			$connection->beginTransaction();
			$commit = true;
		}

		$orig_identifier = $identifier;
		if (!ctype_digit($identifier)) {
			$zone_id = $connection->prepare(sprintf(
				"SELECT id FROM `%s` WHERE name = :name LIMIT 1;", PowerDNSConfig::DB_ZONE_TABLE
			));

			$error = false;
			if ($zone_id->execute(array(":name" => $identifier)) === false) {
				$error = true;
			} else if (($identifier = $zone_id->fetchColumn()) === false) {
				$error = true;
			}

			if ($error) {
				$response->code = Response::NOTFOUND;
				$response->error = "Could not find zone to insert record into.";
				$out = false;

				if ($commit) {
					$connection->rollback();
				}

				return $response;
			}
		}

		$statement = $connection->prepare(sprintf(
			"INSERT INTO `%s` (domain_id, name, type, content, ttl, prio, change_date) VALUES (:id, :name, :type, :content, :ttl, :prio, :date);", PowerDNSConfig::DB_RECORD_TABLE
		));

		$statement->bindValue(":id", $identifier, PDO::PARAM_INT);
		$statement->bindParam(":name", $r_name);
		$statement->bindParam(":type", $r_type);
		$statement->bindParam(":content", $r_content);
		$statement->bindParam(":ttl", $r_ttl);
		$statement->bindParam(":prio", $r_prio);
		$statement->bindValue(":date", time(), PDO::PARAM_INT);

		foreach ($data->records as $record) {
			if (!isset($record->name) || !isset($record->content) || !isset($record->type)) {
				continue;
			}

			$r_name = $record->name;
			$r_type = $record->type;
			$r_content = $record->content;
			if (isset($record->ttl)) {
				$r_ttl = $record->ttl;
			} else {
				$r_ttl = PowerDNSConfig::DNS_DEFAULT_RECORD_TTL;
			}
			if (isset($record->priority)) {
				$r_prio = $record->priority;
			} else {
				$r_prio = PowerDNSConfig::DNS_DEFAULT_RECORD_PRIORITY;
			}

			if ($statement->execute() === false) {
				$response->code = Response::INTERNALSERVERERROR;
				$response->error = sprintf("Rolling back transaction, failed to insert zone record - name: '%s', type: '%s', content: '%s', ttl: '%s', prio: '%s'", $r_name, $r_type, $r_content, $r_ttl, $r_prio);

				$out = false;

				if ($commit) {
					$connection->rollback();
				}
				return $response;
			}
		}

		if ($commit) {
			$connection->commit();
		}

		$response->code = Response::OK;
		$response->body = true;
		$response->log_message = sprintf("Zone %s added %d records.", $orig_identifier, count($data->records));

		$out = true;

		return $response;
	}

	public function modify_zone($response, $identifier, $data, &$out = null) {
		ZoneFunctions::get_zone($response, $identifier, $o, false);

		if (empty($o)) {
			$response->code = Response::NOTFOUND;
			$response->error = "Resource does not exist";
			$out = false;
			return $response;
		}

		unset($o);

		try {
			$connection = new PDO(PowerDNSConfig::DB_DSN, PowerDNSConfig::DB_USER, PowerDNSConfig::DB_PASS);
		} catch (PDOException $e) {
			$response->code = Response::INTERNALSERVERERROR;
			$response->error = "Could not connect to PowerDNS server.";
			$out = false;
			return $response;
		}

		$parameters = array();
		$query = "UPDATE `%s` SET ";
		$q = array();

		if (isset($data->name)) {
			$q[] = "name = :name";
			$parameters[":name"] = $data->name;
		}
		if (isset($data->master)) {
			$q[] = "master = :master";
			$parameters[":master"] = $data->master;
		}
		if (isset($data->type)) {
			$q[] = "type = :type";
			$parameters[":type"] = $data->type;
		}

		if (empty($parameters)) {
			$response->code = Response::BADREQUEST;
			$response->error = "Nothing to change, check your request body.";
			$out = false;
			return $response;
		}

		$query .= implode(", ", $q);
		$query .= "WHERE name = :identifier";
		$parameters[":identifier"] = $identifier;

		$connection->beginTransaction();

		$statement = $connection->prepare(sprintf($query, PowerDNSConfig::DB_ZONE_TABLE));

		if ($statement->execute($parameters) === false) {
			$response->code = Response::INTERNALSERVERERROR;
			$response->error = "Rolling back transaction, failed to modify zone.";

			$connection->rollback();
			$out = false;

			return $response;
		}

		$connection->commit();

		$response->code = Response::OK;
		$response->body = true;
		$response->log_message = sprintf("Zone %s was modified.", $identifier);
		$out = true;

		return $response;
	}

	public function delete_zone($response, $identifier, &$out = null) {
		ZoneFunctions::get_zone($response, $identifier, $o, false);

		if (empty($o)) {
			$response->code = Response::NOTFOUND;
			$response->error = "Resource does not exist";
			$out = false;
			return $response;
		}

		unset($o);

		try {
			$connection = new PDO(PowerDNSConfig::DB_DSN, PowerDNSConfig::DB_USER, PowerDNSConfig::DB_PASS);
		} catch (PDOException $e) {
			$response->code = Response::INTERNALSERVERERROR;
			$response->error = "Could not connect to PowerDNS server.";
			$out = false;
			return $response;
		}

		$connection->beginTransaction();

		$statement = $connection->prepare(sprintf(
			"DELETE FROM `%s` WHERE name = :name;", PowerDNSConfig::DB_ZONE_TABLE
		));

		if ($statement->execute(array(":name" => $identifier)) === false) {
			$response->code = Response::INTERNALSERVERERROR;
			$response->error = "Could not query PowerDNS server.";

			$connection->rollback();
			$out = false;

			return $response;
		}

		$connection->commit();

		$response->code = Response::OK;
		$response->body = true;
		$response->log_message = sprintf("Zone %s was deleted.", $identifier);
		$out = true;

		return $response;
	}

	public function delete_records($response, $identifier, $data, &$out = null) {
		ZoneFunctions::get_zone($response, $identifier, $o, false);

		if (empty($o)) {
			$response->code = Response::NOTFOUND;
			$response->error = "Resource does not exist";
			$out = false;
			return $response;
		}

		unset($o);

		try {
			$connection = new PDO(PowerDNSConfig::DB_DSN, PowerDNSConfig::DB_USER, PowerDNSConfig::DB_PASS);
		} catch (PDOException $e) {
			$response->code = Response::INTERNALSERVERERROR;
			$response->error = "Could not connect to PowerDNS server.";
			$out = false;
			return $response;
		}

		$connection->beginTransaction();

		$statement = $connection->prepare(sprintf(
			"DELETE FROM `%s` WHERE name = :name AND type = :type AND prio = :priority AND content = :content;", PowerDNSConfig::DB_RECORD_TABLE
		));

		$statement->bindParam(":name", $r_name);
		$statement->bindParam(":type", $r_type);
		$statement->bindParam(":content", $r_content);
		$statement->bindParam(":priority", $r_prio);

		foreach ($data->records as $record) {
			if (!isset($record->name) || !isset($record->type) || !isset($record->priority) || !isset($record->content)) {
				continue;
			}

			$r_name = $record->name;
			$r_type = $record->type;
			$r_content = $record->content;
			$r_prio = $record->priority;

			if ($statement->execute() === false) {
				$response->code = Response::INTERNALSERVERERROR;
				$response->error = sprintf("Rolling back transaction, failed to delete zone record - name: '%s', type: '%s', prio: '%s'", $r_name, $r_type, $r_prio);

				$connection->rollback();
				$out = false;

				return $response;
			}
		}

		$connection->commit();

		$response->code = Response::OK;
		$response->body = true;
		$response->log_message = sprintf("Zone %s deleted %d records.", $identifier, count($data->records));
		$out = true;

		return $response;
	}

	private function ipv6_expand($ip) {
		if (strpos($ip, '::') !== false)
			$ip = str_replace('::', str_repeat(':0', 8 - substr_count($ip, ':')).':', $ip);

		if (strpos($ip, ':') === 0)
			$ip = '0'.$ip;

		return $ip;
	}

	private function ipv6_to_arpa($ip) {
		$ip = ipv6_expand($ip);

		$p = explode(":", $ip);
		$n = '';

		foreach($p as $part)
			$n .= str_pad($part, 4, "0", STR_PAD_LEFT);

		$n = str_split(strrev($n));

		return implode(".", $n) . ".ip6.arpa";
	}

	private function ipv4_to_arpa($ip) {
		$p = explode(".", $ip);

		return "{$p[3]}.{$p[2]}.{$p[1]}.{$p[0]}.in-addr.arpa";
	}

	private function truncate_arpa($in, $n=0) {
		$parts = explode(".", $in);

		if ($n >= count($parts)-2)
			return false;

		for($i = 0; $i < $n; $i++)
			array_shift($parts);

		return implode(".", $parts);
	}

}
?>
