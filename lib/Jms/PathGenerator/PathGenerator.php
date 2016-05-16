<?php
namespace Goetas\Xsd\XsdToPhp\Jms\PathGenerator;

use Goetas\Xsd\XsdToPhp\PathGenerator\PathGenerator as PathGeneratorBase;

interface PathGenerator
{
    public function getPath($yaml);
}