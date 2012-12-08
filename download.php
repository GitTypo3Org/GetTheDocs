<?php

require_once('Resources/Private/GetTheDocsClient/Phar/PharBuilder.php');
$pharFile = "Build/get-the-docs.phar";

$builder = new PharBuilder($pharFile);

if (php_sapi_name() == 'cli') {
	$builder->build();
} else {
	if (!file_exists($pharFile)) {
		$builder->build();
	}
	readfile($pharFile);
}

