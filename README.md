#Phanda_PathTranslator

This class supports cool URI.

##Examples

###/.htaccess
	RewriteEngine On
	RewriteCond %{REQUEST_FILENAME} !-d
	RewriteCond %{REQUEST_FILENAME} !-f
	RewriteRule .* __gateway.php [L]

###/__gateway.php
	<?php
	Phanda_PathTranslator::getInstance()->initialize()
	->setParameterDirectoryName('%VAR%')
	->setSearchExtensions('php')
	->execute();

If /categories/1/items/2/detail.json is requested,

directory is changed to /categories/%VAR%/items/%VAR%/ in the document root and detail.php is included.

And then environment variables (PHP_SELF, SCRIPT_NAME, SCRIPT_FILENAME, PATH_INFO, PATH_TRANSLATED) is rewritten.


###/categories/%VAR%/items/%VAR%/detail.php
	<?php
	$translator = Phanda_PathTranslator::getInstance();
	$categoryId = $translator->getParameter(0); // '1'
	$itemId     = $translator->getParameter(1); // '2'
	$extension  = $translator->getExtension();  // 'json'
