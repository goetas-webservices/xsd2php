<?php
namespace GoetasWebservices\Xsd\XsdToPhp\Writer;

use GoetasWebservices\Xsd\XsdToPhp\Php\ClassWriter;
use GoetasWebservices\Xsd\XsdToPhp\Php\PathGenerator\PathGenerator;

class PHPWriter extends Writer
{
    protected $pathGenerator;
    public function __construct(PathGenerator $pathGenerator)
    {
        $this->pathGenerator = $pathGenerator;
    }
    public function write(array $items)
    {
        $writer = new ClassWriter($this->pathGenerator);
        $writer->write($items);
    }
}