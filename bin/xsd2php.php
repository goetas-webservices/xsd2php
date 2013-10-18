#!/bin/php
<?php

if ($_ENV["SYMFONY_PATH"]) {
    $symfonyPath = $_ENV["SYMFONY_PATH"];
} else {
    $symfonyPath =  __DIR__.'/../lib';
}

require_once $symfonyPath.'/Symfony/Component/ClassLoader/UniversalClassLoader.php';

$loader = new \Symfony\Component\ClassLoader\UniversalClassLoader();

$loader->registerNamespaces(array(
    'Symfony'          => $symfonyPath,
    'Goetas\Xsd\XsdToPhp'      => __DIR__.'/../lib',
));

$loader->register();

\Goetas\Xsd\XsdToPhp\Console\ConsoleRunner::run();
