<?php
namespace GoetasWebservices\Xsd\XsdToPhp\Php\Structure;

class PHPClass
{

    protected $name;

    protected $namespace;

    protected $doc;

    public static function createFromFQCN($className)
    {
        if (($pos = strrpos($className, '\\')) !== false) {
            return new self(substr($className, $pos + 1), substr($className, 0, $pos));
        } else {
            return new self($className);
        }
    }

    /**
     * @param bool $onlyParent
     * @return PHPProperty
     */
    public function isSimpleType($onlyParent = false)
    {
        if ($onlyParent) {
            $e = $this->getExtends();
            if ($e) {
                if ($e->hasProperty('__value')) {
                    return $e->getProperty('__value');
                }
            }
        } else {
            if ($this->hasPropertyInHierarchy('__value') && count($this->getPropertiesInHierarchy()) === 1) {
                return $this->getPropertyInHierarchy("__value");
            }
        }
    }

    public function getPhpType()
    {
        if (!$this->getNamespace()) {
            if ($this->isNativeType()) {
                return $this->getName();
            }
            return "\\" . $this->getName();
        }
        return "\\" . $this->getFullName();
    }

    public function isNativeType()
    {
        return !$this->getNamespace() && in_array($this->getName(), [
            'string',
            'int',
            'float',
            'bool',
            'array',
            'callable',

            'mixed' //todo this is not a php type but it's needed for now to allow mixed return tags
        ]);
    }


    public function __construct($name = null, $namespace = null)
    {
        $this->name = $name;
        $this->namespace = $namespace;
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

    public function getNamespace()
    {
        return $this->namespace;
    }

    public function setNamespace($namespace)
    {
        $this->namespace = $namespace;
        return $this;
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

    public function __toString()
    {
        return $this->getFullName();
    }

    public function getFullName()
    {
        return "{$this->namespace}\\{$this->name}";
    }

    protected $checks = array();

    /**
     *
     * @var PHPConstant[]
     */
    protected $constants = array();

    /**
     *
     * @var PHPProperty[]
     */
    protected $properties = array();

    /**
     *
     * @param
     *            $property
     * @return array
     */
    public function getChecks($property)
    {
        return isset($this->checks[$property]) ? $this->checks[$property] : array();
    }

    /**
     *
     * @param
     *            $property
     * @param
     *            $check
     * @param
     *            $value
     * @return $this
     */
    public function addCheck($property, $check, $value)
    {
        $this->checks[$property][$check][] = $value;
        return $this;
    }

    /**
     *
     * @return PHPProperty[]
     */
    public function getProperties()
    {
        return $this->properties;
    }

    /**
     *
     * @param string $name
     * @return bool
     */
    public function hasProperty($name)
    {
        return isset($this->properties[$name]);
    }

    /**
     *
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
        return null;
    }

    /**
     *
     * @param string $name
     * @return PHPProperty
     */
    public function getPropertiesInHierarchy()
    {
        $ps = $this->getProperties();

        if (($this instanceof PHPClass) && $this->getExtends()) {
            $ps = array_merge($ps, $this->getExtends()->getPropertiesInHierarchy());
        }

        return $ps;
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

    /**
     *
     * @param PHPProperty $property
     * @return $this
     */
    public function addProperty(PHPProperty $property)
    {
        $this->properties[$property->getName()] = $property;
        return $this;
    }

    /**
     *
     * @var bool
     */
    protected $abstract;

    /**
     *
     * @var PHPClass
     */
    protected $extends;

    /**
     *
     * @return PHPClass
     */
    public function getExtends()
    {
        return $this->extends;
    }

    /**
     *
     * @param PHPClass $extends
     * @return PHPClass
     */
    public function setExtends(PHPClass $extends)
    {
        $this->extends = $extends;
        return $this;
    }

    public function getAbstract()
    {
        return $this->abstract;
    }

    public function setAbstract($abstract)
    {
        $this->abstract = (bool)$abstract;
        return $this;
    }
}
