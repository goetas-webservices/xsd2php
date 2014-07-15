<?php
namespace Goetas\Xsd\XsdToPhp\Structure;

class PHPClass extends PHPType
{
    use PHPObject;

    /**
     *
     * @var PHPClass
     */
    protected $extends;

    protected $interfaces = array();

    public function getExtends()
    {
        return $this->extends;
    }

    public function setExtends(PHPClass $extends)
    {
        $this->extends = $extends;
        return $this;
    }


    public function getInterfaces()
    {
        return $this->interfaces;
    }

    public function setInterfaces($interfaces)
    {
        $this->interfaces = $interfaces;
        return $this;
    }

    public function __toString()
    {
        return "class " . $this->getFullName();
    }


}