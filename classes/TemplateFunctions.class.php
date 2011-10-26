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
 * Template Resource support functions
 */
class TemplateFunctions {
	public static function get_all_templates($response, &$out = null) {
		try {
			$connection = new PDO(PowerDNSConfig::DB_DSN, PowerDNSConfig::DB_USER, PowerDNSConfig::DB_PASS);
		} catch (PDOException $e) {
			$response->code = Response::INTERNALSERVERERROR;
			$response->error = "Could not connect to PowerDNS server.";
			return $response;
		}

		$result = $connection->query(sprintf(
			"SELECT z.id as z_id, z.name as z_name, z.descr as z_descr
			 FROM `%s` z;", PowerDNSConfig::DB_TEMPLATE_TABLE)
		);

		if ($result === false) {
			$response->code = Response::INTERNALSERVERERROR;
			$response->error = "Could not query PowerDNS server.";
			return $response;
		}

		$output = array();
		while (($row = $result->fetch(PDO::FETCH_ASSOC)) !== false ) {
			TemplateFunctions::get_template($response, $row['z_name'], $o);

			if (!empty($o)) {
				$output[] = $o;
			}
			unset($o);
		}

		$response->body = $output;
		$response->log_message = sprintf("Retrieved %d templates.", count($output));
		$out = $output;
		return $response;
	}

	public static function get_template($response, $identifier, &$out = null) {
		try {
			$connection = new PDO(PowerDNSConfig::DB_DSN, PowerDNSConfig::DB_USER, PowerDNSConfig::DB_PASS);
		} catch (PDOException $e) {
			$response->code = Response::INTERNALSERVERERROR;
			$response->error = "Could not connect to PowerDNS server.";
			return $response;
		}

		$statement = $connection->prepare(sprintf(
			"SELECT z.id as z_id, z.name as z_name, z.descr as z_descr, r.name as r_name, r.type as r_type, r.content as r_content, r.ttl as r_ttl, r.prio as r_prio
			 FROM `%s` z
			 INNER JOIN `%s` r ON (z.id = r.zone_templ_id)
			 WHERE z.name = :name
			 ORDER BY r.id, r.prio;", PowerDNSConfig::DB_TEMPLATE_TABLE, PowerDNSConfig::DB_TEMPLATE_RECORDS_TABLE)
		);

		if ($statement === false) {
			$response->code = Response::INTERNALSERVERERROR;
			$response->error = "Could not query PowerDNS server.";
			return $response;
		}

		if ($statement->execute(array(":name" => $identifier)) === false) {
			$response->code = Response::INTERNALSERVERERROR;
			$response->error = "Could not query PowerDNS server.";
			$out = array();
			return $response;
		}

		$output = array();
		$output['identifier'] = $identifier;
		$output['entries'] = array();

		while (($row = $statement->fetch(PDO::FETCH_ASSOC)) !== false) {
			$output['description'] = $row['z_descr'];
			$output['entries'][] = array(
				"name" => $row['r_name'],
				"type" => $row['r_type'],
				"content" => $row['r_content'],
				"ttl" => $row['r_ttl'],
				"priority" => $row['r_prio']
			);
		}

		if (empty($output['entries'])) {
			$response->code = Response::NOTFOUND;
			$response->body = array();
			$response->log_message = sprintf("Template %s was not found.", $identifier);
			$out = array();
		} else {
			$response->code = Response::OK;
			$response->body = $output;
			$response->log_message = sprintf("Template %s was retrieved.", $identifier);
			$out = $output;
		}

		return $response;
	}

	public static function create_template($response, $data, &$out = null) {
		$response = TemplateFunctions::get_template($response, $data->identifier, $o);

		if (!empty($o)) {
			$response->code = Response::CONFLICT;
			$response->error = "Resource already exists";
			$out = false;
			return $response;
		}

		try {
			$connection = new PDO(PowerDNSConfig::DB_DSN, PowerDNSConfig::DB_USER, PowerDNSConfig::DB_PASS);
		} catch (PDOException $e) {
			$response->code = Response::INTERNALSERVERERROR;
			$response->error = "Could not connect to PowerDNS server.";
			$out = false;
			return $response;
		}

		$connection->beginTransaction();

		$insert = $connection->prepare(sprintf("INSERT INTO `%s` (name, descr) VALUES (:name, :descr);", PowerDNSConfig::DB_TEMPLATE_TABLE));

		if ($insert->execute(array(":name" => $data->identifier, ":descr" => $data->description)) === false) {
			$response->code = Response::INTERNALSERVERERROR;
			$response->error = "Rolling back transaction, failed to insert template.";

			$connection->rollback();
			$out = false;

			return $response;
		}

		$last_id = $connection->lastInsertId();

		$record = $connection->prepare(sprintf("INSERT INTO `%s` (zone_templ_id, name, type, content, ttl, prio) VALUES (:templ_id, :name, :type, :content, :ttl, :prio);", PowerDNSConfig::DB_TEMPLATE_RECORDS_TABLE));
		$record->bindParam(":templ_id", $r_templ_id);
		$record->bindParam(":name", $r_name);
		$record->bindParam(":type", $r_type);
		$record->bindParam(":content", $r_content);
		$record->bindParam(":ttl", $r_ttl, PDO::PARAM_INT);
		$record->bindParam(":prio", $r_prio, PDO::PARAM_INT);

		$r_templ_id = $last_id;

		foreach ($data->entries as $entry) {
			$r_name = $entry->name;
			$r_type = $entry->type;
			$r_content = $entry->content;
			if (!isset($entry->ttl)) {
				$r_ttl = PowerDNSConfig::DNS_DEFAULT_RECORD_TTL;
			} else {
				$r_ttl = $entry->ttl;
			}
			if (!isset($entry->priority)) {
				$r_prio = PowerDNSConfig::DNS_DEFAULT_RECORD_PRIORITY;
			} else {
				$r_prio = $entry->priority;
			}

			if ($record->execute() === false) {
				$response->code = Response::INTERNALSERVERERROR;
				$response->error = sprintf("Rolling back transaction, failed to insert template record - name: '%s', type: '%s', content: '%s', ttl: '%s', prio: '%s'", $r_name, $r_type, $r_content, $r_ttl, $r_prio);

				$connection->rollback();
				$out = false;

				return $response;
			}
		}

		$connection->commit();

		$response->code = Response::OK;
		$response->body = true;
		$response->log_message = sprintf("Template %s was created or modified with %d records.", $data->identifier, count($data->entries));
		$out = true;

		return $response;
	}

	public static function modify_template($response, $identifier, $data, &$out = null) {
		$response = TemplateFunctions::delete_template($response, $identifier, $o);

		if ($o === false) {
			return $response;
		}

		return TemplateFunctions::create_template($response, $data);
	}


	public static function delete_template($response, $identifier, &$out = null) {
		$response = TemplateFunctions::get_template($response, $identifier, $o);

		if (empty($o)) {
			$response->code = Response::NOTFOUND;
			$response->error = "Resource does not exist";
			$out = false;

			return $response;
		}

		try {
			$connection = new PDO(PowerDNSConfig::DB_DSN, PowerDNSConfig::DB_USER, PowerDNSConfig::DB_PASS);
		} catch (PDOException $e) {
			$response->code = Response::INTERNALSERVERERROR;
			$response->error = "Could not connect to PowerDNS server.";
			$out = false;

			return $response;
		}

		$connection->beginTransaction();

		$delete = $connection->prepare(sprintf("DELETE FROM `%s` WHERE %s.name = :name;", PowerDNSConfig::DB_TEMPLATE_TABLE, PowerDNSConfig::DB_TEMPLATE_TABLE));

		if ($delete->execute(array(":name" => $identifier)) === false) {
			$response->code = Response::INTERNALSERVERERROR;
			$response->error = "Rolling back transaction, failed to delete template.";

			$connection->rollback();
			$out = false;

			return $response;
		}

		$connection->commit();

		$response->code = Response::OK;
		$response->body = true;
		$response->log_message = sprintf("Template %s was deleted.", $identifier);
		$out = true;

		return $response;
	}
}
?>
