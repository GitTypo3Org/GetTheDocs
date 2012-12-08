<?php

class File {

	/**
	 * Recursively remove directory
	 *
	 * @static
	 * @param string $directory
	 * @return void
	 */
	static public function removeDirectory($directory) {

		$command = "rm -rf $directory";
		if (is_dir($directory)) {
			exec($command);
		}
		#foreach (glob($dir . '/*') as $file) {
		#	if (is_dir($file))
		#		self::removeDirectory($file);
		#	else
		#		$result = unlink($file);
		#		if (! $result) {
		#			throw new Exception("problem deleting file $file");
		#		}
		#}
		#rmdir($dir);
	}
}


?>