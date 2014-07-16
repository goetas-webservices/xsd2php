<?php
namespace Goetas\Xsd\XsdToPhp\Structure;

class PHPClassOf extends PHPClass
{

    protected $arg;

    public function __construct(PHPArg $arg)
    {
        $this->arg = $arg;
        $this->name = 'array';
    }

    public function __toString()
    {
        return "array of " . $this->arg;
    }

    public function getNamed()
    {
        return $this->named;
    }

    /**
     *
     * @return PHPArg
     */
    public function getArg()
    {
        return $this->arg;
    }
}

