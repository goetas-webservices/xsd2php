<?php
namespace Goetas\Xsd\XsdToPhp\PhpWriter;

use Goetas\Xsd\XsdToPhp\Structure\PHPType;

interface ClassWriter
{
    public function write(PHPType $php, $content);
}