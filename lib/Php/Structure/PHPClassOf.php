<?php
namespace GoetasWebservices\Xsd\XsdToPhp\Php\Structure;

class PHPClassOf extends PHPClass
{

    /**
     * @var PHPArg
     */
    protected $arg;

    /**
     * @param PHPArg $arg
     */
    public function __construct(PHPArg $arg)
    {
        $this->arg = $arg;
        $this->name = 'array';
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return "array of " . $this->arg;
    }

    /**
     * @return PHPArg
     */
    public function getArg()
    {
        return $this->arg;
    }
}

