<?php
/**
 * PHP versions 5
 *
 * @copyright  2011 k-holy <k.holy74@gmail.com>
 * @author     k.holy74@gmail.com
 * @license    http://www.opensource.org/licenses/mit-license.php  The MIT License (MIT)
 */
class Phanda_Tests_PathTranslatorTest extends PHPUnit_Framework_TestCase
{

	private $script = null;

	public function setUp()
	{
		Phanda_PathTranslator::getInstance()->initialize()
		->setDocumentRoot(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'PathTranslatorTest')
		->setParameterDirectoryName('%VAR%');
	}

	public function tearDown()
	{
		if (isset($this->script) && file_exists($this->script)) {
			unlink($this->script);
		}
	}

	public function testSingleton()
	{
		$this->assertSame(Phanda_PathTranslator::getInstance(), Phanda_PathTranslator::getInstance());
	}

	public function testReguralizationOfDirectorySeparatorWhenDocumentRootIsSettedOnWindows()
	{
		if (strncmp('WIN', PHP_OS, 3) !== 0) {
			$this->markTestSkipped('This test was skipped, because this server is not Windows.');
		}
		$this->assertEquals(Phanda_PathTranslator::getInstance()->setDocumentRoot('C:\Path\To\DocumentRoot')->getDocumentRoot(), 'C:/Path/To/DocumentRoot');
	}

	public function testImportEnv()
	{
		$_SERVER['DOCUMENT_ROOT'] = '/path/to/document/root';
		$_SERVER['REQUEST_URI'  ] = '/path/to/request/uri';
		$translator = Phanda_PathTranslator::getInstance()->importEnv();
		$this->assertEquals($_SERVER['DOCUMENT_ROOT'], $translator->getDocumentRoot());
		$this->assertEquals($_SERVER['REQUEST_URI'  ], $translator->getRequestUri());
	}

	public function testGetParameter()
	{
		$translator = Phanda_PathTranslator::getInstance()->prepare('/categories/1/items/2/');
		$this->assertEquals($translator->getParameter(0), '1');
		$this->assertEquals($translator->getParameter(1), '2');
	}

	public function testMetaVariablesAreRewritten()
	{
		$translator = Phanda_PathTranslator::getInstance()->prepare('/categories/1/items/2/detail.php');
		$this->assertEquals($translator->getMetaVariable('SCRIPT_NAME'    ), '/categories/1/items/2/detail.php');
		$this->assertEquals($translator->getMetaVariable('PHP_SELF'       ), '/categories/1/items/2/detail.php');
		$this->assertEquals($translator->getMetaVariable('SCRIPT_FILENAME'), $translator->getDocumentRoot() . '/categories/1/items/2/detail.php');
	}

	public function testMetaVariablesRewrittenWhenPathInfoIsSpecified()
	{
		$translator = Phanda_PathTranslator::getInstance()->setSearchExtensions('php')->prepare('/categories/1/modify/foo/bar?foo=bar#1');
		$this->assertEquals($translator->getMetaVariable('PHP_SELF'       ), '/categories/1/modify.php/foo/bar');
		$this->assertEquals($translator->getMetaVariable('SCRIPT_NAME'    ), '/categories/1/modify.php');
		$this->assertEquals($translator->getMetaVariable('SCRIPT_FILENAME'), $translator->getDocumentRoot() . '/categories/1/modify.php');
		$this->assertEquals($translator->getMetaVariable('PATH_INFO'      ), '/foo/bar');
		$this->assertEquals($translator->getMetaVariable('PATH_TRANSLATED'), $translator->getDocumentRoot() . '/foo/bar');
	}

	public function testEnvironmentVariablesAreRewrittenAfterExecutedBySuperGrobals()
	{
		$translator = Phanda_PathTranslator::getInstance()->setSearchExtensions('php')->execute('/categories/1/modify/foo/bar?foo=bar#1');
		$this->assertEquals($_SERVER['PHP_SELF'       ], '/categories/1/modify.php/foo/bar');
		$this->assertEquals($_SERVER['SCRIPT_NAME'    ], '/categories/1/modify.php');
		$this->assertEquals($_SERVER['SCRIPT_FILENAME'], $translator->getDocumentRoot() . '/categories/1/modify.php');
		$this->assertEquals($_SERVER['PATH_INFO'      ], '/foo/bar');
		$this->assertEquals($_SERVER['PATH_TRANSLATED'], $translator->getDocumentRoot() . '/foo/bar');
		$this->assertEquals($_SERVER['PHP_SELF'       ], $_ENV['PHP_SELF'       ]);
		$this->assertEquals($_SERVER['SCRIPT_NAME'    ], $_ENV['SCRIPT_NAME'    ]);
		$this->assertEquals($_SERVER['SCRIPT_FILENAME'], $_ENV['SCRIPT_FILENAME']);
		$this->assertEquals($_SERVER['PATH_INFO'      ], $_ENV['PATH_INFO'      ]);
		$this->assertEquals($_SERVER['PATH_TRANSLATED'], $_ENV['PATH_TRANSLATED']);
	}

	public function testEnvironmentVariablesAreRewrittenAfterExecutedByGetEnv()
	{
		$translator = Phanda_PathTranslator::getInstance()->setSearchExtensions('php')->execute('/categories/1/modify/foo/bar?foo=bar#1');
		$this->assertEquals(getenv('PHP_SELF'       ), '/categories/1/modify.php/foo/bar');
		$this->assertEquals(getenv('SCRIPT_NAME'    ), '/categories/1/modify.php');
		$this->assertEquals(getenv('SCRIPT_FILENAME'), $translator->getDocumentRoot() . '/categories/1/modify.php');
		$this->assertEquals(getenv('PATH_INFO'      ), '/foo/bar');
		$this->assertEquals(getenv('PATH_TRANSLATED'), $translator->getDocumentRoot() . '/foo/bar');
	}

	public function testRewritingOfDirectoryIndexWhenQueryStringAndFragmentIsSpecified()
	{
		$translator = Phanda_PathTranslator::getInstance()->prepare('/categories/1/?foo=bar#1');
		$this->assertEquals($translator->getMetaVariable('PHP_SELF'       ), '/categories/1/index.php');
		$this->assertEquals($translator->getMetaVariable('SCRIPT_NAME'    ), '/categories/1/index.php');
		$this->assertEquals($translator->getMetaVariable('SCRIPT_FILENAME'), $translator->getDocumentRoot() . '/categories/1/index.php');
		$this->assertEquals($translator->getIncludeFile()                  , $translator->getDocumentRoot() . '/categories/%VAR%/index.php');
		$this->assertEquals($translator->getTranslateDirectory()           , $translator->getDocumentRoot() . '/categories/%VAR%');
		$this->assertEquals($translator->getVirtualUri()                   , '/categories/%VAR%/index.php?foo=bar');
	}

	public function testReguralizationOfPathIncludingDoubleDot()
	{
		$translator = Phanda_PathTranslator::getInstance()->prepare('/categories/1/../../categories/1/');
		$this->assertEquals($translator->getMetaVariable('PHP_SELF'       ), '/categories/1/index.php');
		$this->assertEquals($translator->getMetaVariable('SCRIPT_NAME'    ), '/categories/1/index.php');
		$this->assertEquals($translator->getMetaVariable('SCRIPT_FILENAME'), $translator->getDocumentRoot() . '/categories/1/index.php');
		$this->assertEquals($translator->getIncludeFile()                  , $translator->getDocumentRoot() . '/categories/%VAR%/index.php');
		$this->assertEquals($translator->getTranslateDirectory()           , $translator->getDocumentRoot() . '/categories/%VAR%');
		$this->assertEquals($translator->getVirtualUri()                   , '/categories/%VAR%/index.php');
		$this->assertEquals($translator->getParameter(0), '1');
	}

	public function testSomeExtensionsAreSearched()
	{
		$translator = Phanda_PathTranslator::getInstance();
		$this->assertEquals($translator->setSearchExtensions('html,php')->prepare('/test')->getVirtualUri(), '/test.html');
		$this->assertEquals($translator->setSearchExtensions('php,html')->prepare('/test')->getVirtualUri(), '/test.php');
	}

	public function testDirectoryAndFilenameIncludingDot()
	{
		$translator = Phanda_PathTranslator::getInstance()->prepare('/.foo.bar.baz/1/.foo.bar.baz');
		$this->assertEquals($translator->getMetaVariable('SCRIPT_NAME'    ), '/.foo.bar.baz/1/.foo.bar.baz.php');
		$this->assertEquals($translator->getMetaVariable('PHP_SELF'       ), '/.foo.bar.baz/1/.foo.bar.baz.php');
		$this->assertEquals($translator->getMetaVariable('SCRIPT_FILENAME'), $translator->getDocumentRoot() . '/.foo.bar.baz/1/.foo.bar.baz.php');
		$this->assertEquals($translator->getIncludeFile()                  , $translator->getDocumentRoot() . '/.foo.bar.baz/%VAR%/.foo.bar.baz.php');
		$this->assertEquals($translator->getTranslateDirectory()           , $translator->getDocumentRoot() . '/.foo.bar.baz/%VAR%');
		$this->assertEquals($translator->getVirtualUri()                   , '/.foo.bar.baz/%VAR%/.foo.bar.baz.php');
		$this->assertEquals($translator->getParameter(0), '1');
	}

	public function testGetExtension()
	{
		$translator = Phanda_PathTranslator::getInstance()->setSearchExtensions('php')->prepare('/categories/search.json?q=test');
		$this->assertEquals($translator->getVirtualUri(), '/categories/search.php?q=test');
		$this->assertEquals($translator->getExtension(), 'json');
	}

	public function testPathIncludingDoubleDot()
	{
		$translator = Phanda_PathTranslator::getInstance()->prepare('/categories/../categories/../../');
		$this->assertEquals($translator->getMetaVariable('SCRIPT_NAME'    ), '/index.php');
		$this->assertEquals($translator->getMetaVariable('PHP_SELF'       ), '/index.php');
		$this->assertEquals($translator->getMetaVariable('SCRIPT_FILENAME'), $translator->getDocumentRoot() . '/index.php');
		$this->assertEquals($translator->getIncludeFile()                  , $translator->getDocumentRoot() . '/index.php');
		$this->assertNull($translator->getTranslateDirectory());
		$this->assertEquals($translator->getVirtualUri()                   , '/index.php');
	}

	public function testRequestPathIsOutOfRoot()
	{
		$translator = Phanda_PathTranslator::getInstance()->prepare('/../../../');
		$this->assertEquals($translator->getMetaVariable('SCRIPT_NAME'    ), '/index.php');
		$this->assertEquals($translator->getMetaVariable('PHP_SELF'       ), '/index.php');
		$this->assertEquals($translator->getMetaVariable('SCRIPT_FILENAME'), $translator->getDocumentRoot() . '/index.php');
		$this->assertEquals($translator->getIncludeFile()                  , $translator->getDocumentRoot() . '/index.php');
		$this->assertNull($translator->getTranslateDirectory());
		$this->assertEquals($translator->getVirtualUri()                   , '/index.php');
	}

	/**
	 * @expectedException InvalidArgumentException
	 */
	public function testRaiseExceptionWhenUriIsNotValid()
	{
		$translator = Phanda_PathTranslator::getInstance();
		$translator->prepare('#');
	}

	/**
	 * @expectedException InvalidArgumentException
	 */
	public function testRaiseExceptionWhenUriIsNotString()
	{
		$translator = Phanda_PathTranslator::getInstance();
		$translator->prepare(array());
	}

	public function testScriptPlacedDirectlyUnderOfDocumentRootCanBeInclude()
	{
		$translator = Phanda_PathTranslator::getInstance()->setSearchExtensions('php');
		$this->script = $translator->getDocumentRoot() . '/echo-test.php';
		file_put_contents($this->script, '<?php echo "TEST";');
		ob_start();
		$translator->execute('/echo-test');
		$this->assertEquals('TEST', ob_get_contents());
		ob_end_clean();
	}

}
