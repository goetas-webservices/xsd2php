<?php
namespace GoetasWebservices\Xsd\XsdToPhp\Jms\PathGenerator;

use GoetasWebservices\Xsd\XsdToPhp\PathGenerator\PathGenerator as PathGeneratorBase;

interface PathGenerator
{
    public function getPath($yaml);
}