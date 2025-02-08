<?php

namespace GoetasWebservices\Xsd\XsdToPhp;

use GoetasWebservices\XML\XSDReader\Schema\Element\ElementSingle;
use GoetasWebservices\XML\XSDReader\Schema\Schema;
use GoetasWebservices\XML\XSDReader\Schema\Type\ComplexType;
use GoetasWebservices\XML\XSDReader\Schema\Type\ComplexTypeSimpleContent;
use GoetasWebservices\XML\XSDReader\Schema\Type\SimpleType;
use GoetasWebservices\XML\XSDReader\Schema\Type\Type;
use GoetasWebservices\Xsd\XsdToPhp\Naming\NamingStrategy;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

abstract class AbstractConverter
{
    use LoggerAwareTrait;

    protected array $baseSchemas = [
        'http://www.w3.org/2001/XMLSchema',
        'http://www.w3.org/XML/1998/namespace',
    ];

    protected array $namespaces = [
        'http://www.w3.org/2001/XMLSchema' => '',
        'http://www.w3.org/XML/1998/namespace' => '',
    ];

    private NamingStrategy $namingStrategy;

    protected array $typeAliases = [];

    protected array $aliasCache = [];

    public function __construct(NamingStrategy $namingStrategy, ?LoggerInterface $logger = null)
    {
        $this->namingStrategy = $namingStrategy;
        $this->logger = $logger ?: new NullLogger();

        $this->addAliasMap('http://www.w3.org/2001/XMLSchema', 'gYearMonth', function (Type $type) {
            return 'int';
        });
        $this->addAliasMap('http://www.w3.org/2001/XMLSchema', 'gMonthDay', function (Type $type) {
            return 'int';
        });
        $this->addAliasMap('http://www.w3.org/2001/XMLSchema', 'gMonth', function (Type $type) {
            return 'int';
        });
        $this->addAliasMap('http://www.w3.org/2001/XMLSchema', 'gYear', function (Type $type) {
            return 'int';
        });
        $this->addAliasMap('http://www.w3.org/2001/XMLSchema', 'NMTOKEN', function (Type $type) {
            return 'string';
        });
        $this->addAliasMap('http://www.w3.org/2001/XMLSchema', 'NMTOKENS', function (Type $type) {
            return 'string';
        });
        $this->addAliasMap('http://www.w3.org/2001/XMLSchema', 'QName', function (Type $type) {
            return 'string';
        });
        $this->addAliasMap('http://www.w3.org/2001/XMLSchema', 'NCName', function (Type $type) {
            return 'string';
        });
        $this->addAliasMap('http://www.w3.org/2001/XMLSchema', 'decimal', function (Type $type) {
            return 'float';
        });
        $this->addAliasMap('http://www.w3.org/2001/XMLSchema', 'float', function (Type $type) {
            return 'float';
        });
        $this->addAliasMap('http://www.w3.org/2001/XMLSchema', 'double', function (Type $type) {
            return 'float';
        });
        $this->addAliasMap('http://www.w3.org/2001/XMLSchema', 'string', function (Type $type) {
            return 'string';
        });
        $this->addAliasMap('http://www.w3.org/2001/XMLSchema', 'normalizedString', function (Type $type) {
            return 'string';
        });
        $this->addAliasMap('http://www.w3.org/2001/XMLSchema', 'integer', function (Type $type) {
            return 'int';
        });
        $this->addAliasMap('http://www.w3.org/2001/XMLSchema', 'int', function (Type $type) {
            return 'int';
        });
        $this->addAliasMap('http://www.w3.org/2001/XMLSchema', 'unsignedInt', function (Type $type) {
            return 'int';
        });
        $this->addAliasMap('http://www.w3.org/2001/XMLSchema', 'negativeInteger', function (Type $type) {
            return 'int';
        });
        $this->addAliasMap('http://www.w3.org/2001/XMLSchema', 'positiveInteger', function (Type $type) {
            return 'int';
        });
        $this->addAliasMap('http://www.w3.org/2001/XMLSchema', 'nonNegativeInteger', function (Type $type) {
            return 'int';
        });
        $this->addAliasMap('http://www.w3.org/2001/XMLSchema', 'nonPositiveInteger', function (Type $type) {
            return 'int';
        });
        $this->addAliasMap('http://www.w3.org/2001/XMLSchema', 'long', function (Type $type) {
            return 'int';
        });
        $this->addAliasMap('http://www.w3.org/2001/XMLSchema', 'unsignedLong', function (Type $type) {
            return 'int';
        });
        $this->addAliasMap('http://www.w3.org/2001/XMLSchema', 'short', function (Type $type) {
            return 'int';
        });
        $this->addAliasMap('http://www.w3.org/2001/XMLSchema', 'boolean', function (Type $type) {
            return 'bool';
        });
        $this->addAliasMap('http://www.w3.org/2001/XMLSchema', 'nonNegativeInteger', function (Type $type) {
            return 'int';
        });
        $this->addAliasMap('http://www.w3.org/2001/XMLSchema', 'positiveInteger', function (Type $type) {
            return 'int';
        });
        $this->addAliasMap('http://www.w3.org/2001/XMLSchema', 'language', function (Type $type) {
            return 'string';
        });
        $this->addAliasMap('http://www.w3.org/2001/XMLSchema', 'token', function (Type $type) {
            return 'string';
        });
        $this->addAliasMap('http://www.w3.org/2001/XMLSchema', 'anyURI', function (Type $type) {
            return 'string';
        });
        $this->addAliasMap('http://www.w3.org/2001/XMLSchema', 'byte', function (Type $type) {
            return 'string';
        });
        $this->addAliasMap('http://www.w3.org/2001/XMLSchema', 'duration', function (Type $type) {
            return 'DateInterval';
        });

        $this->addAliasMap('http://www.w3.org/2001/XMLSchema', 'ID', function (Type $type) {
            return 'string';
        });
        $this->addAliasMap('http://www.w3.org/2001/XMLSchema', 'IDREF', function (Type $type) {
            return 'string';
        });
        $this->addAliasMap('http://www.w3.org/2001/XMLSchema', 'IDREFS', function (Type $type) {
            return 'string';
        });
        $this->addAliasMap('http://www.w3.org/2001/XMLSchema', 'Name', function (Type $type) {
            return 'string';
        });

        $this->addAliasMap('http://www.w3.org/2001/XMLSchema', 'NCName', function (Type $type) {
            return 'string';
        });
    }

    abstract public function convert(array $schemas);

    public function addAliasMap(string $ns, string $name, callable $handler): void
    {
        $this->logger->info("Added map $ns $name");
        $this->typeAliases[$ns][$name] = $handler;
    }

    public function addAliasMapType(string $ns, string $name, $type): void
    {
        $this->addAliasMap($ns, $name, function () use ($type) {
            return $type;
        });
    }

    public function getTypeAliases(): array
    {
        return $this->typeAliases;
    }

    public function getTypeAlias($type, Schema $schemapos = null)
    {
        $schema = $schemapos ?: $type->getSchema();

        $cid = $schema->getTargetNamespace() . '|' . $type->getName();
        if (isset($this->aliasCache[$cid])) {
            return $this->aliasCache[$cid];
        }
        if (isset($this->typeAliases[$schema->getTargetNamespace()][$type->getName()])) {
            return $this->aliasCache[$cid] =
                call_user_func($this->typeAliases[$schema->getTargetNamespace()][$type->getName()], $type);
        }

        return null;
    }

    protected function getNamingStrategy(): NamingStrategy
    {
        return $this->namingStrategy;
    }

    public function addNamespace(string $ns, string $phpNamespace): static
    {
        $this->logger->info("Added ns mapping $ns, $phpNamespace");
        $this->namespaces[$ns] = $phpNamespace;

        return $this;
    }

    protected function cleanName(string $name): string
    {
        return preg_replace('/<.*>/', '', $name);
    }

    protected function isArrayType(Type $type): ?Type
    {
        if ($type instanceof SimpleType) {
            if ($type->getList()) {
                return $type->getList();
            } elseif (($rst = $type->getRestriction()) && $rst->getBase() instanceof SimpleType) {
                return $this->isArrayType($rst->getBase());
            }
        } elseif ($type instanceof ComplexTypeSimpleContent) {
            if ($ext = $type->getExtension()) {
                return $this->isArrayType($ext->getBase());
            }
        }

        return  null;
    }

    protected function isArrayNestedElement(Type $type): ?ElementSingle
    {
        if ($type instanceof ComplexType && !$type->getParent() && !$type->getAttributes() && count($type->getElements()) === 1) {
            $elements = $type->getElements();

            return $this->isArrayElement(reset($elements));
        }

        return null;
    }

    protected function isArrayElement(mixed $element): ?ElementSingle
    {
        if ($element instanceof ElementSingle && ($element->getMax() > 1 || $element->getMax() === -1)) {
            return $element;
        }

        return null;
    }

    public function getNamespaces(): array
    {
        return $this->namespaces;
    }
}
