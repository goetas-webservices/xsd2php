<?php
namespace Goetas\Xsd\XsdToPhp;

use Goetas\XML\XSDReader\Schema\Type\Type;
use Goetas\XML\XSDReader\Schema\Type\ComplexType;
use Goetas\XML\XSDReader\Schema\Element\ElementReal;
use Goetas\XML\XSDReader\Schema\Schema;

abstract class AbstractXsd2Converter
{

    protected $baseSchemas = array(
        'http://www.w3.org/2001/XMLSchema',
        'http://www.w3.org/XML/1998/namespace'
    );

    protected $namespaces = array(
        'http://www.w3.org/2001/XMLSchema' => '',
        'http://www.w3.org/XML/1998/namespace' => ''
    );

    protected $arrayCallbacks = array();

    protected $associationTypes = [
        'http://www.w3.org/2001/XMLSchema' => [
            'NMTOKEN' => ['string', ''],
            'token' => ['string', ''],
            'integer' => ['integer', ''],
            'negativeInteger' => ['integer', ''],
            'nonNegativeInteger' => ['integer', ''],
            'nonPositiveInteger' => ['integer', ''],
            'positiveInteger' => ['integer', ''],

            'boolean' => ['boolean', ''],

            'decimal' => ['float', ''],
            'boolean' => ['boolean', ''],
            'date' => ['DateTime', ''],
            'dateTime' => ['DateTime', ''],
            'token' => ['string', ''],
            'token' => ['string', ''],
            'string' => ['string', ''],
            'anyURI' => ['string', ''],
            'NMTOKENS' => ['string', ''],
            'NMTOKENS' => ['string', ''],
            'NMTOKENS' => ['string', ''],
            'NMTOKENS' => ['string', ''],
            'NMTOKENS' => ['string', ''],
            'language' => ['string', ''],
            'anySimpleType' => ['string', '']
            ]
        ];
    protected $baseTypes = ['string', 'float', 'boolean', 'date', 'integer'];


    public abstract function convert(array $schemas);


    protected $typeAliases = array();
    private $aliasCache = array();
    public function addAliasMap($ns, $name, $handler)
    {
        $this->typeAliases[$ns][$name]=$handler;
    }

    public function getTypeAlias($type, Schema $schemapos = null)
    {
        $schema = $schemapos?:$type->getSchema();

        $cid = $schema->getTargetNamespace()."|".$type->getName();
        if (isset($this->aliasCache[$cid])){
            return $this->aliasCache[$cid];
        }
        if (isset($this->typeAliases[$schema->getTargetNamespace()][$type->getName()])){
            return $this->aliasCache[$cid] = call_user_func($this->typeAliases[$schema->getTargetNamespace()][$type->getName()], $type);
        }
    }
    public function __construct()
    {
        $this->addAliasMap("http://www.w3.org/2001/XMLSchema", "anySimpleType", function(Type $type){
            return "string";
        });
        $this->addAliasMap("http://www.w3.org/2001/XMLSchema", "dateTime", function(Type $type){
            return "DateTime<'Y-m-d\TH:i:s'>";
        });
        $this->addAliasMap("http://www.w3.org/2001/XMLSchema", "date", function(Type $type){
            return "DateTime<'Y-m-d'>";
        });
        $this->addAliasMap("http://www.w3.org/2001/XMLSchema", "time", function(Type $type){
            return "DateTime<'H:i:s'>";
        });
        $this->addAliasMap("http://www.w3.org/2001/XMLSchema", "NMTOKEN", function(Type $type){
            return "string";
        });
        $this->addAliasMap("http://www.w3.org/2001/XMLSchema", "NMTOKENS", function(Type $type){
            return "string";
        });
        $this->addAliasMap("http://www.w3.org/2001/XMLSchema", "decimal", function(Type $type){
            return "float";
        });
        $this->addAliasMap("http://www.w3.org/2001/XMLSchema", "string", function(Type $type){
            return "string";
        });
        $this->addAliasMap("http://www.w3.org/2001/XMLSchema", "normalizedString", function(Type $type){
            return "string";
        });
        $this->addAliasMap("http://www.w3.org/2001/XMLSchema", "integer", function(Type $type){
            return "integer";
        });
        $this->addAliasMap("http://www.w3.org/2001/XMLSchema", "int", function(Type $type){
            return "integer";
        });
        $this->addAliasMap("http://www.w3.org/2001/XMLSchema", "long", function(Type $type){
            return "integer";
        });
        $this->addAliasMap("http://www.w3.org/2001/XMLSchema", "short", function(Type $type){
            return "integer";
        });
        $this->addAliasMap("http://www.w3.org/2001/XMLSchema", "boolean", function(Type $type){
            return "boolean";
        });
        $this->addAliasMap("http://www.w3.org/2001/XMLSchema", "nonNegativeInteger", function(Type $type){
            return "integer";
        });
        $this->addAliasMap("http://www.w3.org/2001/XMLSchema", "positiveInteger", function(Type $type){
            return "integer";
        });
        $this->addAliasMap("http://www.w3.org/2001/XMLSchema", "language", function(Type $type){
            return "string";
        });
        $this->addAliasMap("http://www.w3.org/2001/XMLSchema", "token", function(Type $type){
            return "string";
        });
        $this->addAliasMap("http://www.w3.org/2001/XMLSchema", "anyURI", function(Type $type){
            return "string";
        });
        $this->addAliasMap("http://www.w3.org/2001/XMLSchema", "byte", function(Type $type){
            return "string";
        });
        $this->addAliasMap("http://www.w3.org/2001/XMLSchema", "duration", function(Type $type){
            return "DateInterval";
        });



        $this->arrayCallbacks[] = function(Type $type){

            if ($type instanceof ComplexType && !$type->getParent() && !$type->getAttributes() && count($type->getElements())===1){

                $elements = $type->getElements();
                $element = reset($elements);

                if($element instanceof ElementReal && ($element->getMax()>1 || $element->getMax()===-1)){
                    return $element;
                }
            }
            return false;
        };
    }

    public function addNamespace($namesapce, $phpNamespace)
    {
        $this->namespaces[$namesapce] = $phpNamespace;
        return $this;
    }

    public function addAlias($xsdNs, $xsdType, $xsdType)
    {
        // $this->generator->addAlias($xsdNs, $xsdType, $xsdType);
    }

    public function addArrayType($xsdNs, $xsdType)
    {
        $this->arrayCallbacks[$xsdNs."|".$xsdType] = function (Type $type) use($xsdNs, $xsdType) {
            return $type->getName() == $xsdNs && $type->getSchema()->getTargetNamespace() == $xsdNs;
        };
    }

    public function addArrayTypeCallback($xsdNs, $callback)
    {
        $this->arrayCallbacks[] = $callback;
    }
    protected function isArray(Type $type)
    {
        $rest = array_filter(array_map(function($f)use($type){
            return call_user_func($f, $type);
        }, $this->arrayCallbacks));
        if ($rest) {
            return reset($rest);
        }
    }

}
