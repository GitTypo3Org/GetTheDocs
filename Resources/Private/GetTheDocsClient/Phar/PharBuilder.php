<?php
class PharBuilder {

	/**
	 * @var string
	 */
	protected $sourceDirectory = "Resources/Private/GetTheDocsClient";

	/**
	 * @var string
	 */
	protected $buildDirectory = "Build";

	/**
	 * @var string
	 */
	protected $pharName = "get-the-docs.phar";

	/**
	 * @var string
	 */
	protected $pharFile;

	/**
	 * @var string
	 */
	protected $pharPath;

	/**
	 * @var string
	 */
	protected $stubFileName = 'bin/get-the-docs.php';

	/**
	 * Constructor
	 */
	public function __construct($pharFile) {
		$this->pharFile = $pharFile;
	}

	/**
	 * Build a phar archive containing a client
	 *
	 * @return void
	 */
	public function build() {

		/*
		 * Clean up from previous
		 */
		if (file_exists($this->pharPath)) {
			Phar::unlinkArchive($this->pharPath);
		}

		// Create a new phar file
		$phar = new Phar($this->pharFile,
			FilesystemIterator::CURRENT_AS_FILEINFO | FilesystemIterator::KEY_AS_FILENAME
		);

		// Possible compression
		#$phar->compressFiles(Phar::GZ);
		#$phar->setSignatureAlgorithm(Phar::SHA1);

		// Now build the array of files to be in the phar.
		// The first file is the stub file. The rest of the files are built from the directory.
		$files = array();

		$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($this->sourceDirectory));
		foreach ($iterator as $file) {
			if ($file->getFilename() != '..' && $file->getFilename() != '.') {
				$filePath = $file->getPath() . DIRECTORY_SEPARATOR . $file->getFilename();

				$sourceDirectoryTrimmed = ltrim($this->sourceDirectory, '/') . '/';
				$filePathInPhar = str_replace($sourceDirectoryTrimmed, '', $filePath);

				$files[$filePathInPhar] = $file->getPath() . DIRECTORY_SEPARATOR . $file->getFilename();
			}
		}

		// Now build the archive.
		$phar->startBuffering();
		$phar->buildFromIterator(new ArrayIterator($files));

		// Create and add the stub
		$defaultStub = $phar->createDefaultStub($this->stubFileName);

		// Create a custom stub to add the shebang
		$stub = "#!/usr/bin/env php \n" . $defaultStub;
		$phar->setStub($stub);

		$phar->stopBuffering();

		// Set executable flag
		chmod($this->pharFile, 0755);
	}

}
