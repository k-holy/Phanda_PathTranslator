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
ini_set('display_errors', 1);

set_include_path(implode(PATH_SEPARATOR, array(
    realpath(dirname(__FILE__) . '/../src'),
    get_include_path(),
)));

spl_autoload_register(function($className) {
    if (false !== ($path = stream_resolve_include_path(
        str_replace('_', DIRECTORY_SEPARATOR, $className) . '.php'))
    ) {
        return include $path;
    }
    return false;
}, true, true);
