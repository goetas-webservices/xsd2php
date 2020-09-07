<?php

namespace GoetasWebservices\Xsd\XsdToPhp\PathGenerator;

abstract class Psr4PathGenerator
{
    protected $namespaces = [];

    public function __construct(array $targets = [])
    {
        $this->setTargets($targets);
    }

    public function setTargets($namespaces)
    {
        $this->namespaces = $namespaces;

        foreach ($this->namespaces as $namespace => $dir) {
            if (!is_dir($dir)) {
                mkdir($dir, 0777, true);
            }
        }
    }
}
