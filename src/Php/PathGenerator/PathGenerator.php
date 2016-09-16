<?php
namespace GoetasWebservices\Xsd\XsdToPhp\Php\PathGenerator;

use Zend\Code\Generator\ClassGenerator;

interface PathGenerator
{
    public function getPath(ClassGenerator $php);
}
