<?php

class ServerRender {

	/**
	 * @var string
	 */
	protected $sourceDirectory = '';

	/**
	 * @var string
	 */
	protected $buildDirectory = '';

	/**
	 * @var string
	 */
	protected $publicBuildDirectory = '';

	/**
	 * @var string
	 */
	protected $warningsFile = '';

	/**
	 * @var string
	 */
	protected $extensionVersion = '';

	/**
	 * @var string
	 */
	protected $fileName = '';

	/**
	 * The file uploaded
	 *
	 * @var array
	 */
	protected $file = array();

	/**
	 * @var string
	 */
	protected $url = '';

	/**
	 * @var string
	 */
	protected $makeZip = '';

	/**
	 * @var array
	 */
	protected $formats = array();

	/**
	 * @var array
	 */
	protected $allowedFormats = array('html', 'json', 'gettext', 'epub');

	/**
	 * @var array
	 */
	protected $parameters = array();

	/**
	 * Constructor
	 *
	 * @param $parameters
	 * @param $files
	 * @return \ServerRender
	 */
	public function __construct($parameters, $files) {
		$this->parameters = $parameters;
		if (!empty($files['zip_file'])) {
			$this->file = $files['zip_file'];
		}
	}

	/**
	 * Process the User request
	 *
	 * @return void
	 */
	public function process() {
		$this->check();
		$this->initialize();
		$this->prepare();
		$this->unPack();
		$this->validateFileStructure();
		$this->render();
		$this->displayFeedback();
		$this->cleanUp();
	}

	/**
	 * Check that the value is correct
	 *
	 * @throws Exception
	 * @return void
	 */
	protected function check() {
		if (empty($this->file) || $this->file['error'] != 0) {
			throw new \Exception('missing zip file');
		}

		if ($this->parameters['doc_name'] == '') {
			throw new \Exception('missing doc_name parameter');
		}
	}

	/**
	 * Initialize
	 *
	 * @return void
	 */
	protected function initialize() {

		// Get file name value without extension
		$fileNameWithExtension = $this->parameters['doc_name'];
		$fileInfo = pathinfo($fileNameWithExtension);
		$this->fileName = $fileInfo['filename'];

		// Define formats to be generated
		$formats = explode(',', $this->parameters['format']);
		foreach ($formats as $format) {
			if (in_array($format, $this->allowedFormats)) {
				$this->formats[] = $format;
			}
		}

		// Define whether to make a zip file after rendering the doc
		if ($this->parameters['make_zip'] == 'zip') {
			$this->makeZip = 'zip';
		}
	}

	/**
	 * Return the public build directory
	 *
	 * @return string
	 */
	public function getPublicBuildDirectory() {
		if (!$this->publicBuildDirectory) {
			$this->publicBuildDirectory = sprintf('%s/%s',
				FILES_DIRECTORY,
				$this->getWorkspace()
			);
		}
		return $this->publicBuildDirectory;
	}

	/**
	 * Return the source directory
	 *
	 * @return string
	 */
	public function getSourceDirectory() {
		if (!$this->sourceDirectory) {
			$this->sourceDirectory = sprintf('%s/%s/%s',
				dirname($_SERVER['SCRIPT_FILENAME']),
				UPLOAD_DIRECTORY,
				$this->getWorkspace()
			);
		}
		return $this->sourceDirectory;
	}

	/**
	 * Return the build directory
	 *
	 * @return string
	 */
	public function getBuildDirectory() {
		return sprintf('%s/%s',
			dirname($_SERVER['SCRIPT_FILENAME']),
			$this->getPublicBuildDirectory()
		);
	}

	/**
	 * Return the workspace
	 *
	 * @return string
	 */
	public function getWorkspace() {
		return $_SERVER['REQUEST_TIME'];
	}

	/**
	 * Return the warning file
	 *
	 * @return string
	 */
	public function getWarningFile() {
		return sprintf('%s/%s', $this->getSourceDirectory(), 'Warnings.txt');
	}

	/**
	 * Return the URL
	 *
	 * @return string
	 */
	public function getUrl() {
		return 'http://' . $_SERVER['HTTP_HOST'] . str_replace('index.php', '', $_SERVER['PHP_SELF']);
	}

	/**
	 * Check whether the package is renderable, i.e Index.rst file exist
	 *
	 * @throws Exception
	 */
	protected function validateFileStructure() {
		$indexFile = $this->getSourceDirectory() . '/Index.rst';

		// Second attempt to detect whether the package can be rendered
		if (!is_file($indexFile)) {
			$indexFile = $this->getSourceDirectory() . '/Documentation/Index.rst';

			if (!is_file($indexFile)) {
				throw new \Exception('No Index.rst nor Documentation/Index.rst file in archive. Check out your file structure.', 1355425162);
			}
		}
	}

	/**
	 * Check that the value is correct
	 *
	 * @return void
	 */
	protected function render() {

		// Generate configuration files
		$view = new Template('Resources/Private/Template/ServerRender/conf.py');
		$view->set('version', '');
		$view->set('extensionName', $this->fileName);
		$content = $view->fetch();
		file_put_contents($this->getSourceDirectory() . '/conf.py', $content);

		$view = new Template('Resources/Private/Template/ServerRender/Makefile');
		$view->set('buildDirectory', $this->getBuildDirectory());

		if (is_file($this->getSourceDirectory() . '/Index.rst')) {
			$view->set('sourceDirectory', '.');
		} else {
			$view->set('sourceDirectory', 'Documentation');
		}

		$content = $view->fetch();
		file_put_contents($this->getSourceDirectory() . '/Makefile', $content);

		$commands = array();
		// First clean directory
		$commands[] = sprintf("cd %s; make clean --quiet;",
			$this->getSourceDirectory()
		);

		foreach ($this->formats as $format) {
			$commands[] = sprintf("cd %s; make %s --quiet 2> Warnings.txt;",
				$this->getSourceDirectory(),
				$format
			);
		}

		// @todo check if I need to be activated by default for PDF generation
		#$commands[] = "cd $this->getSourceDirectory(); make latex --quiet;";

		if ($this->makeZip == 'zip') {
			$commands[] = sprintf("cd %s/..; zip -qr %s.zip %s",
				$this->getBuildDirectory(),
				$this->getWorkspace(),
				$this->getWorkspace()
			);
		}

		Command::execute($commands);
	}

	/**
	 * Create directory
	 *
	 * @throws Exception
	 * @return void
	 */
	protected function prepare() {
		$directories = array($this->getSourceDirectory(), $this->getBuildDirectory());
		foreach ($directories as $directory) {
			if (!is_dir($directory)) {
				$result = mkdir($directory, 0755, TRUE);

				if ($result === FALSE) {
					throw new Exception('Exception: directory not created on the server "' . $directory . '"');
				}
			}
		}
	}

	/**
	 * Unzip zip file
	 *
	 * @throws Exception
	 * @return void
	 */
	protected function unPack() {
		$zip = new \ZipArchive();
		$res = $zip->open($this->file['tmp_name']);

		if ($res === TRUE) {
			$zip->extractTo($this->getSourceDirectory());
			$zip->close();
		} else {
			throw new Exception('Exception: something when wrong with the zip file');
		}
	}

	/**
	 * Clean up environment
	 *
	 * @return void
	 */
	protected function cleanUp() {
		// Remove upload directory
		File::removeDirectory($this->getSourceDirectory());

		// Fetch all directories and remove the old one
		$buildDirectories = glob(FILES_DIRECTORY . '/*', GLOB_ONLYDIR);
		$this->removeExceeding($buildDirectories);

		$zipFiles = glob(FILES_DIRECTORY . '/*.zip');
		$this->removeExceeding($zipFiles);
	}

	/**
	 * Remove exceeded files / directories
	 *
	 * @param array $items
	 */
	public function removeExceeding(array $items) {
		// Remove the "old" ones according to a limit
		while (count($items) > NUMBER_OF_DOCUMENTS_KEPT) {
			$item = array_pop($items);
			$command = 'rm -rf ' . $item;
			exec($command);
		}
	}

	/**
	 * Unzip zip file
	 *
	 * @return void
	 */
	protected function displayFeedback() {

		$rendered = '';
		if (in_array('html', $this->formats)) {
			$rendered .= "URL to HTML docs:\n";
			$rendered .= sprintf("%s%s\n\n", $this->getUrl(), $this->getPublicBuildDirectory());
		}
		if (in_array('json', $this->formats)) {
			$rendered .= "URL to JSON docs:\n";
			$rendered .= sprintf("%s%s/json\n\n", $this->getUrl(), $this->getPublicBuildDirectory());
		}
		if (in_array('gettext', $this->formats)) {
			$rendered .= "URL to GetText docs:\n";
			$rendered .= sprintf("%s%s/local\n\n", $this->getUrl(), $this->getPublicBuildDirectory());
		}
		if (in_array('epub', $this->formats)) {
			$rendered .= "URL to ePub docs:\n";
			$rendered .= sprintf("%s%s/epub\n\n", $this->getUrl(), $this->getPublicBuildDirectory());
		}

		if ($this->makeZip == 'zip') {
			$rendered .= "Zip file to download:\n";
			$rendered .= sprintf("%s%s.zip\n\n", $this->getUrl(), $this->getPublicBuildDirectory());
		}

		$warnings = '';
		if (file_exists($this->getWarningFile())) {
			$content = trim(file_get_contents($this->getWarningFile()));
			if ($content) {
				$warnings = file_get_contents($this->getWarningFile());
				$warnings .= "\n... Some warnings have been detected. Try to fix them if you can!\n";
			}
		}

		$content = <<< EOF

$warnings

$rendered
Notice, generated files are automatically removed after a grace period!

EOF;

		Output::write($content);
	}
}

?>