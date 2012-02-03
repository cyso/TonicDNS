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
 * Validator
 * @namespace Tonic\Lib
 */
class Validator {

	/**
	 * Contains all internal fields. All values are translated to properties using __get and __set.
	 * This property can be loaded using initialize and __set.
	 * @access private
	 * @see __get()
	 * @see __set()
	 * @see initialize()
	 */
	private $properties = array();

	/**
	 * Contains all validation rules. Key/rule pairs in this property will be checked agains values
	 * in $properties with the same key. 
	 * @access protected
	 * @see $properties
	 */
	protected $rules = array();

	/**
	 * Counter of validator invocations.
	 * @access protected
	 * @see resetCounter()
	 */
	protected static $counter = -1;

	/**
	 * Contains all error messages that arose when validating the properties in this object using the
	 * set rules.
	 * @access public
	 */
	private $errors = array();

	/**
	 * Contains specific error codes that were provided durin validation.
	 * @access public
	 */
	private $error_details = array();

	/**
	 * Translates set calls from Validator->value to Validator->properties['value'].
	 * @access public
	 * @param mixed $name Name of variable to set.
	 * @param mixed $value Value of variable to set.
	 */
	public function __set($name, $value) {
		$this->properties[$name] = $value;
	}

	/**
	 * Translates get calls from Validator->value to Validator->properties['value'].
	 * @access public
	 * @param mixed $name Name of variable to get.
	 */
	public function __get($name) {
		return $this->properties[$name];
	}

	/**
	 * Translates isset calls from isset(Validator->value) to isset(Validator->properties['value']).
	 * @access public
	 * @param mixed $name Name of variable to check
	 */
	public function __isset($name) {
		return isset($this->properties[$name]);
	}

	/**
	 * Translates unset calls from unset(Validator->value) to unset(Validator->properties['value']).
	 * @access public
	 * @param mixed $name Name of variable to unset
	 */
	public function __unset($name) {
		unset($this->properties[$name]);
	}

	/**
	 * Convenience constructor to construct and load the Validator in one statement.
	 * @access public
	 * @param array $values Array of property/value pairs.
	 */
	public function __construct($values = null) {
		if (!empty($values)) {
			$this->initialize($values);
		}
		Validator::$counter += 1;
	}

	/**
	 * Reset the internal counter to the specified count.
	 * @access public
	 * @param int $count Count to set.
	 */
	public function resetCounter($count = 0) {
		Validator::$counter = $count;
	}

	/**
	 * Convenience method to load multiple values at once. Accepts an associative array:
	 * array('foo' => 'bar') will be translated to Validator->foo = bar.
	 * @access public
	 * @param array $values Array of property/value pairs.
	 */
	public function initialize($values) {
		if (is_array($values) || $values instanceof stdClass ) {
			foreach ($values as $key => $value) {
				$this->$key = $value;
			}
		} else {
			if (ValidatorConfig::DEBUG === true) {
				debug_print_backtrace();
			}
			trigger_error("Invalid parameters passed to Validator initialization. Check your input variables and try again.", E_USER_ERROR);
		}
	}

	/**
	 * Adds a error message to the named property.
	 * @access public
	 * @param mixed $property Name of the property to invalidate.
	 * @param mixed $message Message to associate with invalidated property.
	 */
	public function invalidate($property, $message, $code = null) {
		$this->errors[$property][] = $message;
		if ($code != null) {
			$this->error_details[] = $code;
		}
	}

	/**
	 * Matches all set properties with their set validation rule(s). Any property that fails to validate
	 * will be invalidated using invalidate.
	 * @return int Number of properties that failed to validate.
	 * @see invalidate()
	 */
	public function validates() {
		$pre_properties = array_keys($this->properties);

		foreach ($this->properties as $property => $value) {
			if (key_exists($property, $this->rules)) {
				$validators = $this->rules[$property];

				if (is_bool($value)) {
					$value = ($value)?1:0;
				}

				foreach ($validators as $validator) {
					$rule = $validator['rule'];
					$message = $validator['message'];
					if (isset($validator['code'])) {
						$code = $validator['code'];
					} else {
						$code = null;
					}

					if (is_array($rule)) {
						$method = $rule[0];
						$params = array($value);

						array_shift($rule);

						if (!empty($rule)) {
							array_splice($params, count($params), 0, $rule);
						}

						if (($result = call_user_func_array(array(&$this, $method), $params)) !== true) {
							if (is_string($result)) {
								$this->invalidate($property, $result, $code);
							} else if (is_array($result)) {
								if (isset($result['message']) && isset($result['code'])) {
									$this->invalidate($property, $result['message'], $result['code']);
								} else if (isset($result['message'])) {
									$this->invalidate($property, $result['message'], $code);
								} else {
									$this->invalidate($property, implode("\n", $result), $code);
								}
							} else {
								$this->invalidate($property, $message, $code);
							}
						}
					} else {
						if (!preg_match($rule, $value)) {
							$this->invalidate($property, $message, $code);
						}
					}
				}
			} else if (ValidatorConfig::DEBUG === true) {
				debug_print_backtrace();
				trigger_error("No validator for property: " . $property, E_USER_ERROR);
			}
		}

		$post_properties = array_keys($this->properties);

		$intersect = array_intersect($pre_properties, $post_properties);

		if (count($intersect) != count($this->properties)) {
			foreach ($intersect as $i) {
				unset($this->$i);
			}

			$this->validates();
		}

		if (count($this->errors) > 0) {
			return false;
		} else {
			return true;
		}
	}

	/**
	 * Returns all errors generated by validates() as an array.
	 * @access public
	 * @return array Associative array with fieldname/error pairs. Returns an empty array if no errors are found.
	 * @see validates()
	 */
	public function getErrors() {
		return $this->errors;
	}


	/**
	 * Returns all errors generated by validates as a formatted string.
	 * @access public
	 * @param boolean $header Determines if a descriptive header should be added to the message.
	 * @return string Formatted string with error messages. If no error were generated, an empty string will be returned, even if $header was set to <pre>true</pre>.
	 */
	public function getFormattedErrors($header = true) {
		$output = array();

		foreach ($this->errors as $property => $errors) {
			foreach ($errors as $error) {
				$output[] = $property . " - " . $error;
			}
		}

		if (!empty($output) && $header === true) {
			array_unshift($output, "The following properties were invalid:");
		}

		if (!empty($output)) {
			return implode("\n", $output);
		} else {
			return $output;
		}
	}

	/**
	 * Returns the error details generated by validates().
	 * @access public
	 * @return array Simple array with entries containing "code" and "id", where relevant.
	 * @see validates()
	 */
	public function getErrorDetails() {
		return array(
			"id" => Validator::$counter,
			"code" => $this->error_details
		);
	}
}

?>
