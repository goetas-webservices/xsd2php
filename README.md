xsd2php
=======

Convert XSD into PHP classes

Installation with composer
--------------------------

`php composer.phar require goetas/xsd2php:dev-master`

Usage
-----


``` php
<?php

include __DIR__ . '/vendor/autoload.php';

use Goetas\Xsd\XsdToPhp\Console\ConsoleRunner;

$cli = new ConsoleRunner();
$cli->run();
```
