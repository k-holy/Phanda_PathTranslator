<?php
/**
 * PHP versions 5
 *
 * @copyright  2011 k-holy <k.holy74@gmail.com>
 * @author     k.holy74@gmail.com
 * @license    http://www.opensource.org/licenses/mit-license.php  The MIT License (MIT)
 */

/**
 * PathTranslator
 *
 * @author     k.holy74@gmail.com
 */
class Phanda_PathTranslator
{

	private static $instance;

	private static $requestUriPattern = '~\A(/[^?#]*)(\?([^#]*))?(#(.*))?\z~i';

	private $prepared = false;

	private $documentRoot = null;
	private $requestUri = null;

	private $parameterDirectoryName = null;
	private $searchExtensions = array();

	private $metaVariables = array();
	private $pathParameters = array();
	private $extension = null;
	private $translateDirectory = null;
	private $virtualUri = null;
	private $includeFile = null;
	private $statusCode = null;

	/**
	 * Constructor
	 */
	public function __construct()
	{
		$this->initialize();
	}

	/**
	 * The instance is returned Singleton.
	 * @return object Phanda_PathTranslator
	 */
	public static function getInstance()
	{
		if (!isset(self::$instance)) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Clear singleton instance
	 */
	public static function resetInstance()
	{
		self::$instance = null;
	}

	/**
	 * This object is initialized.
	 * @return object Phanda_PathTranslator
	 */
	public function initialize()
	{
		$this->prepared = false;
		$this->documentRoot = null;
		$this->requestUri = null;
		$this->parameterDirectoryName = '%VAR%';
		$this->searchExtensions = array('php','html');
		$this->metaVariables = array();
		$this->pathParameters = array();
		$this->extension = null;
		$this->translateDirectory = null;
		$this->virtualUri = null;
		$this->includeFile = null;
		$this->statusCode = null;
		clearstatcache();
		return $this;
	}

	/**
	 * @param string
	 * @return object Phanda_PathTranslator
	 */
	public function setDocumentRoot($documentRoot)
	{
		if (!is_string($documentRoot)) {
			throw new InvalidArgumentException('The document root is not valid.');
		}
		if (DIRECTORY_SEPARATOR == '\\') {
			$documentRoot = str_replace('\\', '/', $documentRoot);
		}
		$this->documentRoot = $documentRoot;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getDocumentRoot()
	{
		return $this->documentRoot;
	}

	/**
	 * @param string
	 * @return object Phanda_PathTranslator
	 */
	public function setRequestUri($requestUri)
	{
		if (!is_string($requestUri) || !preg_match(self::$requestUriPattern, $requestUri)) {
			throw new InvalidArgumentException('The requestUri is not valid.');
		}
		$this->requestUri = $requestUri;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getRequestUri()
	{
		return $this->requestUri;
	}

	/**
	 * @param string
	 * @return object Phanda_PathTranslator
	 */
	public function setParameterDirectoryName($parameterDirectoryName)
	{
		if (!is_string($parameterDirectoryName)) {
			throw new InvalidArgumentException('The parameterDirectoryName is not valid.');
		}
		$this->parameterDirectoryName = $parameterDirectoryName;
		return $this;
	}

	/**
	 * @param string
	 * @return object Phanda_PathTranslator
	 */
	public function setSearchExtensions($extensions)
	{
		if (is_string($extensions)) {
			$extensions = explode(',', $extensions);
		}
		if (!is_array($extensions)) {
			throw new InvalidArgumentException('The searchExtensions is not valid.');
		}
		$this->searchExtensions = $extensions;
		return $this;
	}

	/**
	 * @param  int
	 * @param  mixed
	 * @return mixed
	 */
	public function getParameter($index, $defaultValue = null)
	{
		if (array_key_exists($index, $this->pathParameters)) {
			return $this->pathParameters[$index];
		}
		return $defaultValue;
	}

	/**
	 * @return array
	 */
	public function getParameters()
	{
		return $this->pathParameters;
	}

	/**
	 * @return mixed
	 */
	public function getExtension()
	{
		return $this->extension;
	}

	/**
	 * @param  string
	 * @return mixed
	 */
	public function getMetaVariable($name)
	{
		if (array_key_exists($name, $this->metaVariables)) {
			return $this->metaVariables[$name];
		}
		return null;
	}

	/**
	 * @return int
	 */
	public function getStatusCode()
	{
		return $this->statusCode;
	}

	/**
	 * @return string
	 */
	public function getVirtualUri()
	{
		return $this->virtualUri;
	}

	/**
	 * @return string
	 */
	public function getTranslateDirectory()
	{
		return $this->translateDirectory;
	}

	/**
	 * @return string
	 */
	public function getIncludeFile()
	{
		return $this->includeFile;
	}

	/**
	 * @return object Phanda_PathTranslator
	 */
	public function importEnv()
	{
		if (isset($_SERVER['DOCUMENT_ROOT'])) {
			$this->setDocumentRoot($_SERVER['DOCUMENT_ROOT']);
		}
		if (isset($_SERVER['REQUEST_URI'])) {
			$this->setRequestUri($_SERVER['REQUEST_URI']);
		}
		return $this;
	}

	/**
	 * @param string requestURI
	 * @return object Phanda_PathTranslator
	 */
	public function prepare($requestUri=null)
	{
		if (!isset($this->documentRoot)) {
			throw new RuntimeException('The documentRoot is not set.');
		}

		if (isset($requestUri)) {
			$this->setRequestUri($requestUri);
		}

		preg_match(self::$requestUriPattern, $this->requestUri, $matches);

		$requestPath     = (isset($matches[1])) ? $matches[1] : '';
		$requestQuery    = (isset($matches[2])) ? $matches[2] : '';
		$requestFragment = (isset($matches[4])) ? $matches[4] : '';

		$translateDirectory = '';
		$scriptName = '';
		$filename = null;
		$fileSegmentIndex = -1;

		$segments = $this->parseRequestPath($requestPath);
		array_shift($segments); // The first segment is always empty, it is removed.
		$segmentCount = count($segments);

		foreach ($segments as $index => $segment) {
			$pos = strrpos($segment, '.');
			if ($pos !== false) {
				$filename = $this->findFile($this->documentRoot . $translateDirectory, $segment);
				if (isset($filename)) {
					$scriptName .= '/' . $filename;
					$fileSegmentIndex = $index;
					break;
				}
				$basename = substr($segment, 0, $pos);
				$extension = substr($segment, $pos + 1);
				if (!empty($this->searchExtensions) &&
					!in_array($extension, $this->searchExtensions)
				) {
					$filename = $this->findFile($this->documentRoot . $translateDirectory,
						$basename, $this->searchExtensions);
					if (isset($filename)) {
						$scriptName .= '/' . $filename;
						$fileSegmentIndex = $index;
						$this->extension = $extension;
						break;
					}
				}
			}
			if (is_dir($this->documentRoot . $translateDirectory . '/' . $segment)) {
				$scriptName .= '/' . $segment;
				$translateDirectory .= '/' . $segment;
				continue;
			}
			$filename = $this->findFile($this->documentRoot . $translateDirectory,
				$segment, $this->searchExtensions);
			if (isset($filename)) {
				$scriptName .= '/' . $filename;
				$fileSegmentIndex = $index;
				break;
			}
			if (is_dir($this->documentRoot . $translateDirectory . '/' .
				$this->parameterDirectoryName)
			) {
				$translateDirectory .= '/' . $this->parameterDirectoryName;
				$scriptName .= '/' . $segment;
				$this->pathParameters[] = $segment;
				continue;
			}
			throw new Phanda_PathTranslatorException(
				sprintf('The file that corresponds to the segment of Uri\'s path "%s" is not found in requestPath "%s".', $segment, $requestPath));
		}
		$translateDirectory = rtrim($translateDirectory, '/');

		if (!isset($filename)) {
			$filename = $this->findFile($this->documentRoot . $translateDirectory,
				'index', array('php', 'html'));
			if (isset($filename)) {
				$fileSegmentIndex = $segmentCount - 1;
				if (strcmp($segments[$segmentCount - 1], '') !== 0) {
					$scriptName .= '/' . $filename;
				} else {
					$scriptName .= $filename;
				}
			}
		}

		if (!isset($filename)) {
			throw new Phanda_PathTranslatorException(
				sprintf('The file that corresponds to the Uri\'s path "%s" is not found.', $requestPath));
		}

		$includeFile = $translateDirectory . '/' . $filename;

		// RFC 3875 Section 4.1. Request Meta-Variables
		$pathInfo = '';
		for ($i = $fileSegmentIndex + 1; $i < $segmentCount; $i++) {
			$pathInfo .= '/' . $segments[$i];
		}
		if (strlen($pathInfo) >= 1) {
			$this->metaVariables['PATH_INFO'      ] = $pathInfo;
			$this->metaVariables['PATH_TRANSLATED'] = $this->documentRoot . $pathInfo;
		}
		if (strlen($scriptName) >= 1) {
			$this->metaVariables['SCRIPT_NAME'    ] = $scriptName;
			$this->metaVariables['PHP_SELF'       ] = $scriptName . $pathInfo;
			$this->metaVariables['SCRIPT_FILENAME'] = $this->documentRoot . $scriptName;
		}
		if (strlen($translateDirectory) >= 1) {
			$this->translateDirectory = $this->documentRoot . $translateDirectory;
		}
		$this->virtualUri = $includeFile . $pathInfo . $requestQuery;
		$this->includeFile = $this->documentRoot . $includeFile;
		$this->prepared = true;
		return $this;
	}

	/**
	 * @param string requestURI
	 * @return object Phanda_PathTranslator
	 */
	public function execute($requestUri=null)
	{
		if (!$this->prepared) {
			$this->prepare($requestUri);
		}
		$apache_enabled = function_exists('apache_setenv');
		foreach ($this->metaVariables as $key => $value) {
			$_SERVER[$key] = $value;
			$_ENV[$key] = $value;
			if ($apache_enabled) {
				apache_setenv($key, $value);
			}
			putenv(sprintf('%s=%s', $key, $value));
		}
		$includeFile = $this->getIncludeFile();
		if (isset($includeFile)) {
			$translateDirectory = $this->getTranslateDirectory();
			if (isset($translateDirectory)) {
				chdir($translateDirectory);
			}
			include $includeFile;
		}
		return $this;
	}

	private function parseRequestPath($requestPath)
	{
		$segments = array();
		$segmentCount = 0;
		foreach (explode('/', $requestPath) as $segment) {
			if (strcmp($segment, '.') === 0) {
				continue;
			}
			if (strcmp($segment, '..') === 0) {
				if ($segmentCount >= 2) {
					array_pop($segments);
					$segmentCount--;
				}
				continue;
			}
			$segments[] = $segment;
			$segmentCount++;
		}
		return $segments;
	}

	private function findFile($dir, $filename, $extensions=array())
	{
		if (!empty($extensions)) {
			foreach ($extensions as $ext) {
				$path = $dir . '/' . $filename . '.'. $ext;
				if (file_exists($path) && is_file($path) && is_readable($path)) {
					return $filename . '.' . $ext;
				}
			}
		}
		$path = $dir . '/' . $filename;
		return (file_exists($path) && is_file($path) && is_readable($path)) ? $filename : null;
	}

}

class Phanda_PathTranslatorException extends RuntimeException {}
