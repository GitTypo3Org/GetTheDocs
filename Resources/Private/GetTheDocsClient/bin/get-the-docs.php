<?php
/*
 * This script handles documentation for the TYPO3 project
 *
 * @license http://www.gnu.org/licenses/gpl.html GNU General
 * Public License, version 3 or later
 */

if (!class_exists('ZipArchive')) {
	$message = "\nMissing PHP ZipArchive Class. Try to install \"php5-zip\" package.\n";
	die($message);
}

define('HOST', 'http://docs.typo3.org/getthedocs/');
define('API_VERSION', '1.0.0');

try {
	$client = new \TYPO3\GetTheDocs\Client\Client();
	$client->dispatch($argv);
} catch (Exception $e) {
	print $e;
}

/**
 * Auto-load class
 *
 * @param string $className
 * @return void
 */
function __autoload($className) {
	if (!class_exists($className)) {

		$className = ltrim($className, '\\');
		$fileName = '../Classes/';
		if ($lastNsPos = strrpos($className, '\\')) {
			$namespace = substr($className, 0, $lastNsPos);
			$className = substr($className, $lastNsPos + 1);
			$fileName .= str_replace('\\', DIRECTORY_SEPARATOR, $namespace) . DIRECTORY_SEPARATOR;
		}
		$fileName .= str_replace('_', DIRECTORY_SEPARATOR, $className) . '.php';
		require $fileName;
	}
}

?>