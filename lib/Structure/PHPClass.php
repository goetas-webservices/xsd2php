<?php
namespace Goetas\Xsd\XsdToPhp\Structure;

class PHPClass extends PHPType
{
    use PHPObject;

    /**
     * @var PHPClass
     */
    protected $extends;

    /**
     * @var PHPInterface[]
     */
    protected $interfaces = array();

    /**
     * @return PHPClass
     */
    public function getExtends()
    {
        return $this->extends;
    }

    /**
     * @param PHPClass $extends
     * @return PHPClass
     */
    public function setExtends(PHPClass $extends)
    {
        $this->extends = $extends;
        return $this;
    }

    /**
     * @return PHPInterface[]
     */
    public function getInterfaces()
    {
        return $this->interfaces;
    }

    /**
     * @param PHPInterface[] $interfaces
     * @return PHPClass
     */
    public function setInterfaces($interfaces)
    {
        $this->interfaces = $interfaces;
        return $this;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return "class " . $this->getFullName();
    }
}
