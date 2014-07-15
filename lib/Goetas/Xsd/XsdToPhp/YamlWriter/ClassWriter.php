<?php
namespace Goetas\Xsd\XsdToPhp\YamlWriter;

use Goetas\Xsd\XsdToPhp\Structure\PHPType;

interface ClassWriter
{

    public function write($yaml, $content);
}