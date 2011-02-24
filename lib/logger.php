<?php
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
