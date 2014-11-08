<?php
namespace Goetas\Xsd\XsdToPhp;

use Goetas\XML\XSDReader\Schema\Schema;
use Goetas\XML\XSDReader\Schema\Type\Type;
use Goetas\XML\XSDReader\Schema\Type\ComplexType;
use Goetas\XML\XSDReader\Schema\Type\SimpleType;
use Goetas\XML\XSDReader\Schema\Element\ElementSingle;

abstract class AbstractConverter
{

    protected $baseSchemas = array(
        'http://www.w3.org/2001/XMLSchema',
        'http://www.w3.org/XML/1998/namespace'
    );

    protected $namespaces = array(
        'http://www.w3.org/2001/XMLSchema' => '',
        'http://www.w3.org/XML/1998/namespace' => ''
    );

    protected $baseTypes = [
        'string',
        'float',
        'boolean',
        'date',
        'integer'
    ];

    public abstract function convert(array $schemas);

    protected $typeAliases = array();

    protected $aliasCache = array();

    public function addAliasMap($ns, $name, callable $handler)
    {
        $this->typeAliases[$ns][$name] = $handler;
    }

    public function addAliasMapType($ns, $name, $type)
    {
        $this->addAliasMap($ns, $name, function () use($type)
        {
            return $type;
        });
    }

    public function getTypeAlias($type, Schema $schemapos = null)
    {
        $schema = $schemapos ?  : $type->getSchema();

        $cid = $schema->getTargetNamespace() . "|" . $type->getName();
        if (isset($this->aliasCache[$cid])) {
            return $this->aliasCache[$cid];
        }
        if (isset($this->typeAliases[$schema->getTargetNamespace()][$type->getName()])) {
            return $this->aliasCache[$cid] = call_user_func($this->typeAliases[$schema->getTargetNamespace()][$type->getName()], $type);
        }
    }

    public function __construct()
    {
        $this->addAliasMap("http://www.w3.org/2001/XMLSchema", "anySimpleType", function (Type $type)
        {
            return "string";
        });
        $this->addAliasMap("http://www.w3.org/2001/XMLSchema", "gYearMonth", function (Type $type)
        {
            return "integer";
        });
        $this->addAliasMap("http://www.w3.org/2001/XMLSchema", "gMonthDay", function (Type $type)
        {
            return "integer";
        });
        $this->addAliasMap("http://www.w3.org/2001/XMLSchema", "gMonth", function (Type $type)
        {
            return "integer";
        });
        $this->addAliasMap("http://www.w3.org/2001/XMLSchema", "gYear", function (Type $type)
        {
            return "integer";
        });
        $this->addAliasMap("http://www.w3.org/2001/XMLSchema", "NMTOKEN", function (Type $type)
        {
            return "string";
        });
        $this->addAliasMap("http://www.w3.org/2001/XMLSchema", "NMTOKENS", function (Type $type)
        {
            return "string";
        });
        $this->addAliasMap("http://www.w3.org/2001/XMLSchema", "QName", function (Type $type)
        {
            return "string";
        });
        $this->addAliasMap("http://www.w3.org/2001/XMLSchema", "NCName", function (Type $type)
        {
            return "string";
        });
        $this->addAliasMap("http://www.w3.org/2001/XMLSchema", "decimal", function (Type $type)
        {
            return "float";
        });
        $this->addAliasMap("http://www.w3.org/2001/XMLSchema", "float", function (Type $type)
        {
            return "float";
        });
        $this->addAliasMap("http://www.w3.org/2001/XMLSchema", "double", function (Type $type)
        {
            return "float";
        });
        $this->addAliasMap("http://www.w3.org/2001/XMLSchema", "string", function (Type $type)
        {
            return "string";
        });
        $this->addAliasMap("http://www.w3.org/2001/XMLSchema", "normalizedString", function (Type $type)
        {
            return "string";
        });
        $this->addAliasMap("http://www.w3.org/2001/XMLSchema", "integer", function (Type $type)
        {
            return "integer";
        });
        $this->addAliasMap("http://www.w3.org/2001/XMLSchema", "int", function (Type $type)
        {
            return "integer";
        });
        $this->addAliasMap("http://www.w3.org/2001/XMLSchema", "unsignedInt", function (Type $type)
        {
            return "integer";
        });
        $this->addAliasMap("http://www.w3.org/2001/XMLSchema", "negativeInteger", function (Type $type)
        {
            return "integer";
        });
        $this->addAliasMap("http://www.w3.org/2001/XMLSchema", "positiveInteger", function (Type $type)
        {
            return "integer";
        });
        $this->addAliasMap("http://www.w3.org/2001/XMLSchema", "nonNegativeInteger", function (Type $type)
        {
            return "integer";
        });
        $this->addAliasMap("http://www.w3.org/2001/XMLSchema", "nonPositiveInteger", function (Type $type)
        {
            return "integer";
        });
        $this->addAliasMap("http://www.w3.org/2001/XMLSchema", "long", function (Type $type)
        {
            return "integer";
        });
        $this->addAliasMap("http://www.w3.org/2001/XMLSchema", "unsignedLong", function (Type $type)
        {
            return "integer";
        });
        $this->addAliasMap("http://www.w3.org/2001/XMLSchema", "short", function (Type $type)
        {
            return "integer";
        });
        $this->addAliasMap("http://www.w3.org/2001/XMLSchema", "boolean", function (Type $type)
        {
            return "boolean";
        });
        $this->addAliasMap("http://www.w3.org/2001/XMLSchema", "nonNegativeInteger", function (Type $type)
        {
            return "integer";
        });
        $this->addAliasMap("http://www.w3.org/2001/XMLSchema", "positiveInteger", function (Type $type)
        {
            return "integer";
        });
        $this->addAliasMap("http://www.w3.org/2001/XMLSchema", "language", function (Type $type)
        {
            return "string";
        });
        $this->addAliasMap("http://www.w3.org/2001/XMLSchema", "token", function (Type $type)
        {
            return "string";
        });
        $this->addAliasMap("http://www.w3.org/2001/XMLSchema", "anyURI", function (Type $type)
        {
            return "string";
        });
        $this->addAliasMap("http://www.w3.org/2001/XMLSchema", "byte", function (Type $type)
        {
            return "string";
        });
        $this->addAliasMap("http://www.w3.org/2001/XMLSchema", "duration", function (Type $type)
        {
            return "DateInterval";
        });

        $this->addAliasMap("http://www.w3.org/2001/XMLSchema", "ID", function (Type $type)
        {
            return "string";
        });
        $this->addAliasMap("http://www.w3.org/2001/XMLSchema", "IDREF", function (Type $type)
        {
            return "string";
        });
        $this->addAliasMap("http://www.w3.org/2001/XMLSchema", "IDREFS", function (Type $type)
        {
            return "string";
        });
        $this->addAliasMap("http://www.w3.org/2001/XMLSchema", "Name", function (Type $type)
        {
            return "string";
        });

        $this->addAliasMap("http://www.w3.org/2001/XMLSchema", "NCName", function (Type $type)
        {
            return "string";
        });
    }

    public function addNamespace($namesapce, $phpNamespace)
    {
        $this->namespaces[$namesapce] = $phpNamespace;
        return $this;
    }

    protected function cleanName($name)
    {
        return preg_replace("/<.*>/", "", $name);
    }

    /**
     * @param Type $type
     * @return \Goetas\XML\XSDReader\Schema\Type\Type|null
     */
    protected function isArrayType(Type $type)
    {
        if ($type instanceof SimpleType) {
            return $type->getList();
        }
    }

    /**
     * @param Type $type
     * @return \Goetas\XML\XSDReader\Schema\Element\ElementSingle|null
     */
    protected function isArrayNestedElement(Type $type)
    {
        if ($type instanceof ComplexType && ! $type->getParent() && ! $type->getAttributes() && count($type->getElements()) === 1) {
            $elements = $type->getElements();
            return $this->isArrayElement(reset($elements));
        }
    }
    /**
     * @param ElementSingle $type
     * @return \Goetas\XML\XSDReader\Schema\Element\ElementSingle|null
     */
    protected function isArrayElement($element)
    {
        if ($element instanceof ElementSingle && ($element->getMax() > 1 || $element->getMax() === - 1)) {
            return $element;
        }
    }

}
