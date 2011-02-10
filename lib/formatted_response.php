<?php
/**
 * Smart Response class for auto-formatting responses based on mime-type
 * @namespace Tonic\Lib
 */
class FormattedResponse extends Response {
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

		switch ($type) {
		case "json":
			$this->body = json_encode($this->body);
			break;
		case "xml":
			$xmlnode = new XMLNode("response");

			if (is_array($this->body)) {
				foreach ($this->body as $k => $v) {
					$xmlnode->addValue($k, $v);
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
						$g = "\n\t" . $g;
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
