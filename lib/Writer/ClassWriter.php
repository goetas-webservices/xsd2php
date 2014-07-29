<?php
namespace Goetas\Xsd\XsdToPhp\Writer;

use Goetas\Xsd\XsdToPhp\Structure\PHPType;

interface ClassWriter
{

    public function write(PHPType $php, $content);
}