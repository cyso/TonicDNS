#!/usr/bin/env php
<?php

require_once(__DIR__ . "/../docroot/load.php");

$files = glob(__DIR__ . "/../classes/*Resource.class.php");

foreach ($files as $file) {
	$name = basename($file, ".class.php");

	//require_once($file);
	$refl = new ReflectionClass($name);

	$m = $refl->getMethods();

	$methods = array();
	$comments = array();
	foreach ($m as $n) {
		// Filter inherited and magic methods
		if ($n->class != $name || strpos($n->name, "__") === 0 || !$n->isPublic()) {
			continue;
		}

		$methods[] = $n->name;
		$comments[] = sanitize_comment($n->getDocComment());
	}

	file_put_contents(sprintf("%s.md", $name), format_markdown($name, $methods, $comments));
}

function sanitize_comment($string) {
	$string = substr($string, 3, -2);
	$string = preg_replace("#\t \* #", "", $string);
	$string = preg_replace("#\t \*#", "", $string);
	$string = preg_replace("#~~~\n\{#", "```json\n{", $string);
	$string = preg_replace("#~~~\n\[#", "```json\n{", $string);
	$string = preg_replace("#~~~#", '```', $string);

	foreach (explode("\n", $string) as $line) {
		if (strpos($line, "@") === 0) {
			continue;
		}
		$out[] = $line;
	}

	return implode("\n", $out);;
}

function format_markdown($resource_name, $methods, $comments) {
	$md = array();

	for ($i = 0; $i < count($methods); $i++) {
		$md[] = sprintf("%s\n%s\n\n%s\n\n", strtoupper($methods[$i]), str_repeat("-", strlen($methods[$i])), $comments[$i]);
	}

	return sprintf("%s\n%s\n\n%s\n", $resource_name, str_repeat("=", strlen($resource_name)), implode("\n", $md));
}

?>
