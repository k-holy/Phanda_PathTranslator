Phanda_PathTranslator
========
This class supports cool URI.

Examples
--------

**/.htaccess
```
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-d
RewriteCond %{REQUEST_FILENAME} !-f
RewriteRule .* __gateway.php [L]
```

**/__gateway.php**
```php
<?php
Phanda_PathTranslator::getInstance()->initialize()
->setParameterDirectoryName('%VAR%')
->setSearchExtensions('php')
->execute();
```

If request to /categories/1/items/2/detail.json
current directory is moved to "/categories/%VAR%/items/%VAR%/" and
"detail.php" is included.

**/categories/%VAR%/items/%VAR%/detail.php**
```php
<?php
$translator = Phanda_PathTranslator::getInstance();
$categoryId = $translator->getParameter(0); // '1'
$itemId     = $translator->getParameter(1); // '2'
$extension  = $translator->getExtension();  // 'json'
```
