<?php

// Configuration
define('UPLOAD_DIRECTORY', 'upload');
define('FILES_DIRECTORY', 'files');
define('API_VERSION', '1.0.0');
define('NUMBER_OF_DOCUMENTS_KEPT', 500);

$doProcess = FALSE;

// Handle GET / POST
$parameters = array();
$dirtyParameters = array_merge($_POST, $_GET);
$dirtyParameters = array_map('trim', $dirtyParameters);
$files = $_FILES;

if (!empty($_FILES['zip_file']['error']) && $_FILES['zip_file']['error'] == 1) {
	die('Upload fail! It may happen the uploaded file is exceeding ' . ini_get('upload_max_filesize'));
}


// case 1: the request comes from the CLI client
if (strpos($_SERVER['HTTP_USER_AGENT'], 'PHP/') !== FALSE) {

	$clientApiVersion = $_POST['api_version'];
	$serverApiVersion = API_VERSION;
	if ($clientApiVersion != $serverApiVersion) {
		$content = <<<EOF

Hang on! It looks your client is out of date with version "$clientApiVersion"

Please update your script to version "$serverApiVersion".

curl -s http://docs.typo3.org/getthedocs/download.php > get-the-docs.phar
EOF;
		print $content;
		die();
	}

	$doProcess = TRUE;
	$parameters['doc_name'] = 'undefined';
	$parameters = array_merge($parameters, $dirtyParameters);

	Output::$format = 'text';

} elseif (! empty($_FILES)) { // case 2: the request comes from the Web form

	// computes format
	$outputs = array();
	$formats = array('html', 'pdf', 'epub');
	foreach ($formats as $format) {
		if (!empty($dirtyParameters[$format])) {
			$outputs[] = $format;
		}
	}

	// Render HTML if anything defined from the GUI
	if (empty($outputs)) {
		$outputs[] = 'html';
	}

	$parameters['doc_name'] = 'undefined';
	if (!empty($dirtyParameters['doc_name'])) {
		$parameters['doc_name'] = $dirtyParameters['doc_name'];
	}

	$parameters['make_zip'] = FALSE;
	if (!empty($dirtyParameters['make_zip'])) {
		$parameters['make_zip'] = $dirtyParameters['make_zip'];
	}

	// Computes a doc_workspace value
	$searches = array(' ', '"', "'");

	$parameters['action'] = $dirtyParameters['action'];
	$parameters['format'] = implode(',', $outputs);
	$parameters['debug'] = 0;
	$doProcess = TRUE;
	Output::$format = 'html';
}

if ($doProcess) {
	try {
		$server = new Server();
		$server->dispatch($parameters, $files);
	} catch (Exception $e) {
		print $e;
	}
} else {
	print file_get_contents('usage.html');
}

/**
 * Auto load handling
 *
 * @param string $className
 */
function __autoload($className) {
	$path = 'Classes/' . $className . '.php';
	if (!class_exists($className) && file_exists($path)) {
		include($path);
	}
}

?>