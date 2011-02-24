<?php
/**
 * Lightweight logger
 * @namespace Tonic\Lib
 */
class Logger {
	private $uri;
	private $method;
	private $user;
	private $input;
	private $output;
	private $message;
	private $ip;

	public function __construct($uri, $method, $user, $message = null, $input = null, $output = null, $ip = null) {
		$this->uri = $uri;
		$this->method = $method;
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

	public function writeLog($message = null) {
		if ($this->message === null && $message === null) {
			return;
		} else {
			$this->message = $message;
		}
		if (openlog(LoggingConfig::LOG_IDENT, LOG_PID | LOG_CONS, LoggingConfig::LOG_FACILITY) === true ) {
			syslog(LOG_INFO, sprintf("(%s) [%s] %s %s - %s", $this->ip, $this->user, strtoupper($this->method), $this->uri, $this->message));
			if (LoggingConfig::LOG_DEBUG == true && !empty($this->input)) {
				syslog(LOG_DEBUG, sprintf("Input: %s", $this->input));
			}
			if (LoggingConfig::LOG_DEBUG == true && !empty($this->output)) {
				syslog(LOG_DEBUG, sprintf("Output: %s", $this->output));
			}
			closelog();
		} else {
			trigger_error("Could not open syslog facility", E_USER_ERROR);
		}
	}
}
?>
