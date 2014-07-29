<?php
namespace Goetas\Xsd\XsdToPhp\Structure;

class PHPConstant
{

    protected $doc;

    protected $name;

    protected $value;

    public function __construct($name = null)
    {
        $this->name = $name;
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

    public function getName()
    {
        return $this->name;
    }

    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    public function getValue()
    {
        return $this->value;
    }

    public function setValue($value)
    {
        $this->value = $value;
        return $this;
    }
}