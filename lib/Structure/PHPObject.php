<?php
namespace Goetas\Xsd\XsdToPhp\Structure;

trait PHPObject
{

    protected $checks = array();

    protected $constants = array();

    protected $properties = array();

    protected $traits = array();

    public function getProperties()
    {
        return $this->properties;
    }

    public function getChecks($property)
    {
        return isset($this->checks[$property]) ? $this->checks[$property] : array();
    }

    public function addCheck($property, $check, $value)
    {
        $this->checks[$property][$check][] = $value;
        return $this;
    }

    /**
     *
     * @param string $name
     * @return boolean
     */
    public function hasProperty($name)
    {
        return isset($this->properties[$name]);
    }

    /**
     *
     * @param string $name
     * @return boolean
     */
    public function hasPropertyInHierarchy($name)
    {
        if ($this->hasProperty($name)) {
            return true;
        }
        if (($this instanceof PHPClass) && $this->getExtends() && $this->getExtends()->hasPropertyInHierarchy($name)) {
            return true;
        }
        foreach ($this->traits as $trait) {
            if ($trait->hasPropertyInHierarchy($name)) {
                return true;
            }
        }
        return false;
    }

    /**
     *
     * @param string $name
     * @return PHPProperty
     */
    public function getPropertyInHierarchy($name)
    {
        if ($this->hasProperty($name)) {
            return $this->getProperty($name);
        }
        if (($this instanceof PHPClass) && $this->getExtends() && $this->getExtends()->hasPropertyInHierarchy($name)) {
            return $this->getExtends()->getPropertyInHierarchy($name);
        }
        foreach ($this->traits as $trait) {
            if ($trait->hasPropertyInHierarchy($name)) {
                return $trait->getPropertyInHierarchy($name);
            }
        }
        return null;
    }

    /**
     *
     * @param string $name
     * @return PHPProperty
     */
    public function getProperty($name)
    {
        return $this->properties[$name];
    }

    public function addProperty(PHPProperty $property)
    {
        $this->properties[$property->getName()] = $property;
        return $this;
    }

    public function addConstants(PHPConstant $const)
    {
        $this->constants[] = $const;
        return $this;
    }

    public function getConstants()
    {
        return $this->constants;
    }

    public function getExtends()
    {
        return $this->extends;
    }

    public function setExtends(PHPClass $extends)
    {
        $this->extends = $extends;
        return $this;
    }

    public function getTraits()
    {
        return $this->traits;
    }

    public function addTrait(PHPTrait $trait)
    {
        $this->traits[] = $trait;
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