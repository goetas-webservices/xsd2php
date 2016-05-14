<?php
namespace Goetas\Xsd\XsdToPhp\Php\PathGenerator;

use Goetas\Xsd\XsdToPhp\PathGenerator\PathGenerator as PathGeneratorBase;
use Goetas\Xsd\XsdToPhp\Php\Structure\PHPClass;

interface PathGenerator extends PathGeneratorBase
{
    public function getPath(PHPClass $php);
}