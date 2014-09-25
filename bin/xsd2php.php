#!/usr/bin/env php
<?php
$paths = [
    __DIR__ . '/../vendor/autoload.php',
    __DIR__ . '/../../../autoload.php'
];
foreach ($paths as $path) {
    if (is_file($path)) {
        include $path;
        break;
    }
}

use Symfony\Component\Console\Application;
use Goetas\Xsd\XsdToPhp\Command;
error_reporting(error_reporting() &~E_NOTICE);
$cli = new Application('Convert XSD to PHP classes Command Line Interface', "2.0");
$cli->setCatchExceptions(true);
$cli->addCommands(array(
    new Command\ConvertToPHP(),
    new Command\ConvertToYaml()
));
$cli->run();
