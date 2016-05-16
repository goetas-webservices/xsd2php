<?php
namespace GoetasWebservices\Xsd\XsdToPhp\Php\PathGenerator;

use GoetasWebservices\Xsd\XsdToPhp\PathGenerator\PathGenerator as PathGeneratorBase;
use GoetasWebservices\Xsd\XsdToPhp\Php\Structure\PHPClass;

interface PathGenerator
{
    public function getPath(PHPClass $php);
}