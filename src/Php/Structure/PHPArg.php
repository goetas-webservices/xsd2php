<?php

namespace GoetasWebservices\Xsd\XsdToPhp\Php\Structure;

class PHPArg
{
    protected $doc;

    protected $type;

    protected $name;

    protected $default;

    public function __construct($name = null, $type = null)
    {
        $this->name = $name;
        $this->type = $type;
    }

    public function getDoc()
    {
        return $this->doc;
    }

    public function setDoc($doc)
    {
        $this->doc = $doc;

        return $this;
    }

    /**
     * @return PHPClass
     */
    public function getType()
    {
        return $this->type;
    }

    public function setType(PHPClass $type)
    {
        $this->type = $type;

        return $this;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    public function getDefault()
    {
        return $this->default;
    }

    public function setDefault($default)
    {
        $this->default = $default;

        return $this;
    }
}
