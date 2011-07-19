<?php
/**
 * PHP versions 5
 *
 * @copyright  2011 k-holy <k.holy74@gmail.com>
 * @author     k.holy74@gmail.com
 * @license    http://www.opensource.org/licenses/mit-license.php  The MIT License (MIT)
 */
error_reporting(E_ALL|E_STRICT);
if (defined('E_DEPRECATED')) {
	error_reporting(error_reporting() & ~E_DEPRECATED);
}
set_include_path(implode(PATH_SEPARATOR, array(
	realpath(dirname(__FILE__) . '/../src'),
	get_include_path(),
)));
spl_autoload_register(function($className) {
	if (!class_exists($className, false) && !interface_exists($className, false)) {
		@include_once str_replace('_', '/', $className) . '.php';
	}
	return (class_exists($className, false) || interface_exists($className, false));
});
require_once 'PHPUnit/Autoload.php';
