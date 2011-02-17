<?php
/**
 * Zone Resource.
 * @uri /zone
 * @uri /zone/:identifier
 */
class ZoneResource extends TokenResource {

	/**
	 * Retrieves an existing DNS zone.
	 *
	 * If no identifier is specified, all zones will be retrieved without records.
	 * If an identifier is specified, one zone will be retrieved with records.
	 *
	 * @access public
	 * @param mixed $request Request parameters
	 * @return Response DNS zone data if successful, false with error message otherwise.
	 */
	public function get($request, $identifier = null) {
		$response = new FormattedResponse($request);
		$data = $request->parseData();

		if (empty($identifier)) {
			return $this->get_all_zones($response);
		} else {
			return $this->get_zone($response, $identifier);
		}
	}

	/**
	 * Create a new DNS zone.
	 *
	 * {
	 * 	"name": <string>,
	 * 	"type": master|slave|native,
	 * 	"master": ipv4,
	 * 	"templates": 0..n {
	 *		"identifier": <string>
	 * 	},
	 * 	"records": 0..n {
	 * 		"name": <string>,
	 * 		"type": <string>,
	 * 		"content": <string>,
	 * 		"ttl": <int>,
	 * 		"priority": <int>
	 * 	}
	 * }
	 *
	 * @access public
	 * @param mixed $request Request parameters
	 * @return Response True if request was successful, false with error message otherwise.
	 */
	public function put($request, $identifier = null) {
		$response = new FormattedResponse($request);
		$data = $request->parseData();

		if ($data == null) {
			$response->code = Response::BADREQUEST;
			$response->error = "Request body was malformed. Ensure the body is in valid format.";
			return $response;
		}

		if (!isset($data->name) || !isset($data->type)) {
			$response->code = Response::BADREQUEST;
			$response->error = "Identifier and/or entries were missing or invalid. Ensure that the body is in valid format and all required parameters are present.";
			return $response;
		}

		return $this->create_zone($response, $data);
	}

	/**
	 * Update an existing DNS zone. Only works for zones, not records. At least one field has to be specified.
	 *
	 * {
	 * 	"name": <string>,
	 * 	"master": ipv4,
	 * 	"type": master|slave|native,
	 * }
	 *
	 * @access public
	 * @param mixed $request Request parameters
	 * @return Response True if request was successful, false with error message otherwise.
	 */
	public function post($request, $identifier = null) {
		$response = new FormattedResponse($request);
		$data = $request->parseData();

		if ($data == null || empty($data)) {
			$response->code = Response::BADREQUEST;
			$response->error = "Request body was malformed. Ensure the body is in valid format, and that the body is not empty.";
			return $response;
		}

		if (empty($identifier)) {
			$response->code = Response::BADREQUEST;
			$response->error = "Identifier was missing or invalid. Ensure that the body is in valid format.";
			return $response;
		}

		return $this->modify_zone($response, $identifier, $data);
	}

	/**
	 * Delete an existing DNS zone, or delete a record from the zone.
	 *
	 * If an identifier is specified, the entire zone will be deleted.
	 * If a body is specified, but no identifier, the specified entries will be deleted from the zone.
	 *
	 * {
	 * 	"name": <string>,
	 * 	"records": 1..n {
	 * 		"name": <string>,
	 * 		"type": <string>,
	 * 		"priority": <int>,
	 * 	}
	 * }
	 *
	 * @access public
	 * @params mixed $request Request parameters
	 * @return Response True if zone was deleted, false with error message otherwise.
	 */
	public function delete($request, $identifier = null) {
		$response = new FormattedResponse($request);
		$data = $request->parseData();

		if (empty($identifier) && (empty($data) || !isset($data->name) || !isset($data->records) || empty($data->records))) {
			$response->code = Response::BADREQUEST;
			$response->error = "Identifier and/or entries were missing or invalid. Ensure that the body is in valid format and all required parameters are present.";
			return $response;
		}

		if (!empty($identifier)) {
			return $this->delete_zone($response, $identifier);
		} else {
			return $this->delete_record($response, $data->name, $data);
		}
	}

	public function get_all_zones($response, &$out = null) {
		try {
			$connection = new PDO(PowerDNSConfig::DB_DSN, PowerDNSConfig::DB_USER, PowerDNSConfig::DB_PASS);
		} catch (PDOException $e) {
			$response->code = Response::INTERNALSERVERERROR;
			$response->error = "Could not connect to PowerDNS server.";
			return $response;
		}

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
		$out = $output;
		return $response;
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
			 ORDER BY r_name ASC;", PowerDNSConfig::DB_ZONE_TABLE, PowerDNSConfig::DB_RECORD_TABLE)
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
			$out = array();
		} else {
			$response->code = Response::OK;
			$response->body = $output;
			$out = $output;
		}

		return $response;
	}

	public function create_zone($response, $data, &$out = null) {
		$this->get_zone($response, $data->name, $o, false);

		if (!empty($o)) {
			$response->code = Response::INTERNALSERVERERROR;
			$response->error = "Resource already exists";
			$out = false;
			return $response;
		}

		unset($o);

		$records = array();
		if (isset($data->templates) && !empty($data->templates)) {
			$tr = new TemplateResource();
			foreach ($data->templates as $template) {
				$response = $tr->get_template($response, $template, $p);

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
			$response = $this->create_records($response, $connection->lastInsertId(), $records, $r, true, $connection);

			if ($r === false) {
				$connection->rollback();
				$out = false;

				return $response;
			}
		}

		$connection->commit();

		$response->code = Response::OK;
		$response->body = true;
		$out = true;

		return $response;
	}

	public function create_records($response, $identifier, $data, &$out = null, $skip_domain_check = false, $connection = null) {
		if ($skip_domain_check === false) {
			$this->get_zone($response, $data->name, $o, false);

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

		if (!ctype_digit($identifier)) {
			$zone_id = $connection->prepare(sprintf(
				"SELECT domain_id FROM `%s` WHERE name = :name LIMIT 1;", PowerDNSConfig::DB_ZONE_TABLE
			));

			$error = false;
			if (($result = $zone_id->execute(array(":name" => $identifier))) === false) {
				$error = true;
			} else if (($identifier = $result->fetchColumn()) === false) {
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

		foreach ($data as $record) {
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

		$response->code = Response::OK;
		$response->body = true;

		$out = true;

		return $response;
	}

	public function modify_zone($response, $identifier, $data, &$out = null) {
		$this->get_zone($response, $identifier, $o, false);

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
		$out = true;

		return $response;
	}

	public function delete_zone($response, $identifier, &$out = null) {
		$this->get_zone($response, $identifier, $o, false);

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

		$response->code = Response::OK;
		$response->body = true;
		$out = true;

		$connection->commit();

		return $response;
	}

	public function delete_record($response, $identifier, $records, &$out = null) {

	}
}
