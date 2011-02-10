<?php

// load Tonic library
require_once '../lib/tonic.php';

// load all other libraries
$libraries = glob("../lib/*.php");
foreach ($libraries as $library) {
	// load library
	require_once $library;
}

// load classes
$classes = glob("../classes/*.class.php");
foreach ($classes as $class) {
	// load class
	require_once $class;
}

// handle request
$request = new Request();
$resource = $request->loadResource();
$response = $resource->exec($request);
$response->output();

?>
