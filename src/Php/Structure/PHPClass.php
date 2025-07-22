<?php

namespace GoetasWebservices\Xsd\XsdToPhp\Php\Structure;

class PHPClass
{
    protected ?string $name;

    protected ?string $namespace;

    protected ?string $doc = null;

    protected array $implements = [];

    protected array $checks = [];

    /**
     * @var PHPConstant[]
     */
    protected array $constants = [];

    /**
     * @var PHPProperty[]
     */
    protected array $properties = [];

    protected bool $abstract = false;

    protected ?PHPClass $extends = null;

    public function __construct(?string $name = null, ?string $namespace = null)
    {
        $this->name = $name;
        $this->namespace = $namespace;
    }

    public static function createFromFQCN(string $className): static
    {
        if (($pos = strrpos($className, '\\')) !== false) {
            return new self(substr($className, $pos + 1), substr($className, 0, $pos));
        } else {
            return new self($className);
        }
    }

    public function getImplements(): array
    {
        return $this->implements;
    }

    public function isSimpleType(bool $onlyParent = false): ?PHPProperty
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
                return $this->getPropertyInHierarchy('__value');
            }
        }

        return null;
    }

    public function setImplements(array $fqcn): void
    {
        $this->implements = $fqcn;
    }

    public function getPhpType(): string
    {
        if (!$this->getNamespace()) {
            if ($this->isNativeType()) {
                return $this->getName();
            }

            return '\\' . $this->getName();
        }

        return '\\' . $this->getFullName();
    }

    public function isNativeType(): bool
    {
        return !$this->getNamespace() && in_array($this->getName(), [
            'string',
            'int',
            'float',
            'bool',
            'array',
            'callable',

            'mixed', //todo this is not a php type but it's needed for now to allow mixed return tags
        ]);
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getNamespace(): ?string
    {
        return $this->namespace;
    }

    public function setNamespace(?string $namespace): static
    {
        $this->namespace = $namespace;

        return $this;
    }

    public function getDoc(): ?string
    {
        return $this->doc;
    }

    public function setDoc(string $doc): static
    {
        $this->doc = $doc;

        return $this;
    }

    public function __toString(): string
    {
        return $this->getFullName();
    }

    public function getFullName(): string
    {
        return "{$this->namespace}\\{$this->name}";
    }

    public function getChecks(string $property): array
    {
        return $this->checks[$property] ?? [];
    }

    public function addCheck(string $property, string $check, mixed $value): static
    {
        $this->checks[$property][$check][] = $value;

        return $this;
    }

    /**
     * @return PHPProperty[]
     */
    public function getProperties(): array
    {
        return $this->properties;
    }

    public function hasProperty(string $name): bool
    {
        return isset($this->properties[$name]);
    }

    public function hasPropertyInHierarchy(string $name, bool $onlyParents = false): bool
    {
        if (!$onlyParents && $this->hasProperty($name)) {
            return true;
        }

        if ($this->getExtends() && $this->getExtends()->hasPropertyInHierarchy($name)) {
            return true;
        }

        return false;
    }

    public function getPropertyInHierarchy(string $name, bool $onlyParents = false): ?PHPProperty
    {
        if (!$onlyParents && $this->hasProperty($name)) {
            return $this->getProperty($name);
        }

        if ($this->getExtends() && $this->getExtends()->hasPropertyInHierarchy($name)) {
            return $this->getExtends()->getPropertyInHierarchy($name);
        }

        return null;
    }

    /**
     * @return PHPProperty[]
     */
    public function getPropertiesInHierarchy(): array
    {
        $ps = $this->getProperties();

        if (($this instanceof PHPClass) && $this->getExtends()) {
            $ps = array_merge($ps, $this->getExtends()->getPropertiesInHierarchy());
        }

        return $ps;
    }

    public function getProperty(string $name): ?PHPProperty
    {
        return $this->properties[$name] ?? null;
    }

    public function addProperty(PHPProperty $property): static
    {
        $this->properties[$property->getName()] = $property;

        return $this;
    }

    public function getExtends(): ?PHPClass
    {
        return $this->extends;
    }

    public function setExtends(PHPClass $extends): static
    {
        $this->extends = $extends;

        return $this;
    }

    public function getAbstract(): bool
    {
        return $this->abstract;
    }

    public function setAbstract(bool $abstract): static
    {
        $this->abstract = $abstract;

        return $this;
    }
}
