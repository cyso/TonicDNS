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
 * Smart Response class for auto-formatting responses based on mime-type
 * @namespace Tonic\Lib
 */
class FormattedResponse extends Response {

	/**
	 * If set, will replace the normal body with:
	 * {
	 * 	"error": <string>
	 * }
	 * @var string
	 */
	public $error = null;

	/**
	 * Log message that will replace the "Action complete" success message on logging.
	 * @var string
	 */
	public $log_message = null;

	/**
	 * Convert the object into a string suitable for printing
	 * @return str
	 */
	function __toString() {
		$str = parent::__toString();
		$str .= "\n\n" . $body;
		return $str;
	}

	/**
	 * Convert this response into a valid HTML response
	 */
	function output() {
		$type = null;

		foreach ($this->request->accept as $accept) {
			foreach ($accept as $a) {
				switch ($a) {
				case "xml":
					$type = "xml";
					break;
				case "json":
				default:
					$type = "json";
					break;
				}

				if ($type != null) {
					break;
				} else {
					$type = "json";
					break;
				}
			}
		}

		if ($this->error != null) {
			$this->body = array("error" => $this->error);
		}


		switch ($type) {
		case "json":
		default:
			$this->body = json_encode($this->body);
			$this->addHeader('Content-Type', 'application/json');
			break;
		case "xml":
			$xmlnode = new XMLNode("response");

			if (is_array($this->body)) {
				foreach ($this->body as $k => $v) {
					if (is_int($k)) {
						$k = "item";
					}
					if (is_array($v)) {
						$n = new XMLNode($k);
						$n->addArray($v);
						$xmlnode->addValue(null, $n);
					} else {
						$xmlnode->addValue($k, $v);
					}

				}
			} else {
				if (is_object($this->body) && method_exists($this->body, "__toString")) {
					$xmlnode->addValue(get_class($this->body), $this->body->__toString());
				} else if (is_object($this->body)) {
					$xmlnode->addValue(get_class($this->body), "Object");
				} else {
					$xmlnode->addValue(null, $this->body);
				}
			}

			$this->body = $xmlnode->generate();
			$this->addHeader('Content-Type', 'application/xml');
			break;
		}

		parent::output();
	}
}

/**
 * Multi functional XML generator class.
 * @author Nick Douma
 */ 
class XMLNode {
	protected $parameter_name = null;
	protected $parameter_elements = array();
	protected $values = array();
	protected $mainBody = false;

	public function __construct($parameter_name, $mainBody = false) {
		$this->parameter_name = $parameter_name;
		$this->mainBody = $mainBody;
	}

	public function addValue($name, $value) {
		$this->values[] = array("name" => $name, "value" => $value);
	}

	public function addArray($value) {
		foreach ($value as $k => $v) {
			if (is_int($k)) {
				$k = "item";
			}
			if (is_array($v)) {
				$node = new XMLNode($k);
				$node->addArray($v);
				$this->addValue(null, $node);
			} else {
				$this->addValue($k, $v);
			}
		}
	}

	public function generate() {
		if (!$this->mainBody) {
			$xml = "<{$this->parameter_name}";

			foreach ($this->parameter_elements as $element) {
				$xml .= " {$element['name']}=\"{$element['value']}\"";
			}

			if (empty($this->values)) {
				$xml .= " />";
				return $xml;
			}

			$xml .= ">\n";
		}
		foreach ($this->values as $value) {
			if (is_array($value) && is_bool($value['value'])) {
				$value['value'] = ($value['value'])?"TRUE":"FALSE";
			}
			if (get_class($value['value']) == "XMLNode") {
				$gen = explode("\n", $value['value']->generate());

				foreach ($gen as &$g) {
					if (!empty($g)) {
						$g = "\t" . $g;
					}
				}

				$xml .= implode("\n", $gen);
			} else if (empty($value['name'])) {
				$xml .= "{$value['value']}";
			} else {
				$value['value'] = htmlspecialchars($value['value']);
				$xml .= "\t<{$value['name']}>{$value['value']}</{$value['name']}>\n";
			}
		}

		if (!$this->mainBody) {
			$xml .= "</{$this->parameter_name}>\n";
		}

		return $xml;
	}
}
?>
