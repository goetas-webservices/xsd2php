<?php
namespace Goetas\Xsd\XsdToPhp\Jms\PathGenerator;

use Goetas\Xsd\XsdToPhp\PathGenerator\PathGenerator as PathGeneratorBase;

interface PathGenerator extends PathGeneratorBase
{
    public function getPath($yaml);
}