<?php
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

	public function get_zone($response, $identifier, &$out = null, $details = true) {
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
				$response = TemplateFunctions::get_template($response, $template, $p);

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
			ZoneFunctions::get_zone($response, $data->name, $o, false);

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

		if (isset($data->name)) {
			$query .= "name = :name ";
			$parameters[":name"] = $data->name;
		}
		if (isset($data->master)) {
			$query .= "master = :master ";
			$parameters[":master"] = $data->master;
		}
		if (isset($data->type)) {
			$query .= "type = :type ";
			$parameters[":type"] = $data->type;
		}

		if (empty($parameters)) {
			$response->code = Response::BADREQUEST;
			$response->error = "Nothing to change, check your request body.";
			$out = false;
			return $response;
		}

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
			"DELETE FROM `%s` WHERE name = :name AND type = :type AND prio = :priority;", PowerDNSConfig::DB_RECORD_TABLE
		));

		$statement->bindParam(":name", $r_name);
		$statement->bindParam(":type", $r_type);
		$statement->bindParam(":priority", $r_prio);

		foreach ($data->records as $record) {
			if (!isset($record->name) || !isset($record->type) || !isset($record->priority) ) {
				continue;
			}

			$r_name = $record->name;
			$r_type = $record->type;
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
}
?>
