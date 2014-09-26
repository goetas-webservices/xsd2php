<?php
namespace Goetas\Xsd\XsdToPhp\Structure;

trait PHPObject
{

    protected $checks = array();

    /**
     * @var PHPConstant[]
     */
    protected $constants = array();

    /**
     * @var PHPProperty[]
     */
    protected $properties = array();

    /**
     * @var PHPTrait[]
     */
    protected $traits = array();

    /**
     * @param $property
     * @return array
     */
    public function getChecks($property)
    {
        return isset($this->checks[$property]) ? $this->checks[$property] : array();
    }

    /**
     * @param $property
     * @param $check
     * @param $value
     * @return $this
     */
    public function addCheck($property, $check, $value)
    {
        $this->checks[$property][$check][] = $value;
        return $this;
    }

    /**
     * @return PHPProperty[]
     */
    public function getProperties()
    {
        return $this->properties;
    }

    /**
     * @param string $name
     * @return boolean
     */
    public function hasProperty($name)
    {
        return isset($this->properties[$name]);
    }

    /**
     * @param string $name
     * @return bool
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
     * @param string $name
     * @return PHPProperty
     */
    public function getProperty($name)
    {
        return $this->properties[$name];
    }

    /**
     * @param PHPProperty $property
     * @return $this
     */
    public function addProperty(PHPProperty $property)
    {
        $this->properties[$property->getName()] = $property;
        return $this;
    }

    /**
     * @param PHPConstant $const
     * @return $this
     */
    public function addConstants(PHPConstant $const)
    {
        $this->constants[] = $const;
        return $this;
    }

    /**
     * @return PHPConstant[]
     */
    public function getConstants()
    {
        return $this->constants;
    }

    /**
     * @return PHPTrait[]
     */
    public function getTraits()
    {
        return $this->traits;
    }

    /**
     * @param PHPTrait $trait
     * @return $this
     */
    public function addTrait(PHPTrait $trait)
    {
        $this->traits[] = $trait;
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
