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
 * Lightweight logger
 * @namespace Tonic\Lib
 */
class Logger {
	private $uri;
	private $method;
	private $code;
	private $user;
	private $input;
	private $output;
	private $message;
	private $ip;

	public function __construct($uri, $method, $user, $message = null, $code = null, $input = null, $output = null, $ip = null) {
		$this->uri = $uri;
		$this->method = $method;
		$this->code = $code;
		$this->user = $user;
		$this->message = $message;
		$this->input = $input;
		$this->output = $output;
		$this->ip = ($ip == null) ? $_SERVER['REMOTE_ADDR'] : $ip;
	}

	public function setUser($user)
	{
		$this->user = $user;
	}

	public function setInput($input)
	{
		$this->input = $input;
	}

	public function setOutput($output)
	{
		$this->output = $output;
	}

	public function writeLog($message = null, $code = null) {
		if (empty($this->message) && empty($message)) {
			return;
		} else {
			$this->message = $message;
		}
		if (empty($this->code) && empty($code)) {
			$this->code = 0;
		} else {
			$this->code = $code;
		}
		if (openlog(LoggingConfig::LOG_IDENT, LOG_PID | LOG_CONS, LoggingConfig::LOG_FACILITY) === true ) {
			$entries = explode("\n", $this->message);
			foreach ($entries as $entry) {
				syslog(LOG_INFO, sprintf("(%s) [%s] %s %s %s - %s", $this->ip, $this->user, strtoupper($this->method), $this->code, $this->uri, $entry));
			}
			if (LoggingConfig::LOG_DEBUG == true && !empty($this->input) && $this->input != "null") {
				syslog(LOG_DEBUG, sprintf("(%s) [%s] Input: %s", $this->ip, $this->user, $this->input));
			}
			if (LoggingConfig::LOG_DEBUG == true && !empty($this->output) && $this->output != "null") {
				syslog(LOG_DEBUG, sprintf("(%s) [%s] Output: %s", $this->ip, $this->user, $this->output));
			}
			closelog();
		} else {
			trigger_error("Could not open syslog facility", E_USER_ERROR);
		}
	}
}
?>
