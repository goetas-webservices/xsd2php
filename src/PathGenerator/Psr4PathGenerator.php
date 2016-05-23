<?php
namespace GoetasWebservices\Xsd\XsdToPhp\PathGenerator;

abstract class Psr4PathGenerator
{

    protected $namespaces = array();

    public function __construct(array $namespaces)
    {
        $this->namespaces = $namespaces;

        foreach ($this->namespaces as $namespace => $dir) {
            if (!is_dir($dir)) {
                throw new PathGeneratorException("The folder '$dir' does not exist.");
            }
            if (!is_writable($dir)) {
                throw new PathGeneratorException("The folder '$dir' is not writable.");
            }
        }
    }
}

