<?php
namespace GoetasWebservices\Xsd\XsdToPhp\Jms;

use Doctrine\Common\Inflector\Inflector;
use Exception;
use GoetasWebservices\XML\XSDReader\Schema\Attribute\AttributeContainer;
use GoetasWebservices\XML\XSDReader\Schema\Attribute\AttributeItem;
use GoetasWebservices\XML\XSDReader\Schema\Element\Element;
use GoetasWebservices\XML\XSDReader\Schema\Element\ElementContainer;
use GoetasWebservices\XML\XSDReader\Schema\Element\ElementDef;
use GoetasWebservices\XML\XSDReader\Schema\Element\ElementItem;
use GoetasWebservices\XML\XSDReader\Schema\Element\ElementRef;
use GoetasWebservices\XML\XSDReader\Schema\Item;
use GoetasWebservices\XML\XSDReader\Schema\Schema;
use GoetasWebservices\XML\XSDReader\Schema\SchemaItem;
use GoetasWebservices\XML\XSDReader\Schema\Type\BaseComplexType;
use GoetasWebservices\XML\XSDReader\Schema\Type\ComplexType;
use GoetasWebservices\XML\XSDReader\Schema\Type\SimpleType;
use GoetasWebservices\XML\XSDReader\Schema\Type\Type;
use GoetasWebservices\Xsd\XsdToPhp\AbstractConverter;
use GoetasWebservices\Xsd\XsdToPhp\Naming\NamingStrategy;
use GoetasWebservices\Xsd\XsdToPhp\Php\Structure\PHPClass;

class YamlConverter extends AbstractConverter
{

    public function __construct(NamingStrategy $namingStrategy)
    {

        parent::__construct($namingStrategy);

        $this->addAliasMap('http://www.w3.org/2001/XMLSchema', 'dateTime', function (Type $type) {
            return "GoetasWebservices\Xsd\XsdToPhp\XMLSchema\DateTime";
        });
        $this->addAliasMap('http://www.w3.org/2001/XMLSchema', 'time', function (Type $type) {
            return "GoetasWebservices\Xsd\XsdToPhp\XMLSchema\Time";
        });
        $this->addAliasMap("http://www.w3.org/2001/XMLSchema", "date", function (Type $type) {
            return "GoetasWebservices\Xsd\XsdToPhp\XMLSchema\Date";
        });
    }

    private $classes = [];

    public function convert(array $schemas)
    {
        $visited = array();
        $this->classes = array();
        foreach ($schemas as $schema) {
            $this->navigate($schema, $visited);
        }
        return $this->getTypes();
    }

    private function flattAttributes(AttributeContainer $container)
    {
        $items = array();
        foreach ($container->getAttributes() as $attr) {
            if ($attr instanceof AttributeContainer) {
                $items = array_merge($items, $this->flattAttributes($attr));
            } else {
                $items[] = $attr;
            }
        }
        return $items;
    }

    private function flattElements(ElementContainer $container)
    {
        $items = array();
        foreach ($container->getElements() as $attr) {
            if ($attr instanceof ElementContainer) {
                $items = array_merge($items, $this->flattElements($attr));
            } else {
                $items[] = $attr;
            }
        }
        return $items;
    }

    /**
     *
     * @return PHPClass[]
     */
    public function getTypes()
    {
        uasort($this->classes, function ($a, $b) {
            return strcmp(key($a), key($b));
        });

        $ret = array();

        foreach ($this->classes as $definition) {
            $classname = key($definition["class"]);
            if (strpos($classname, '\\') !== false && (!isset($definition["skip"]) || !$definition["skip"])) {
                $ret[$classname] = $definition["class"];
            }
        }

        return $ret;
    }

    private function navigate(Schema $schema, array &$visited)
    {
        if (isset($visited[spl_object_hash($schema)])) {
            return;
        }
        $visited[spl_object_hash($schema)] = true;

        foreach ($schema->getTypes() as $type) {
            $this->visitType($type);
        }
        foreach ($schema->getElements() as $element) {
            $this->visitElementDef($schema, $element);
        }

        foreach ($schema->getSchemas() as $schildSchema) {
            if (!in_array($schildSchema->getTargetNamespace(), $this->baseSchemas, true)) {
                $this->navigate($schildSchema, $visited);
            }
        }
    }

    private function visitTypeBase(&$class, &$data, Type $type, $name)
    {
        if ($type instanceof BaseComplexType) {
            $this->visitBaseComplexType($class, $data, $type, $name);
        }
        if ($type instanceof ComplexType) {
            $this->visitComplexType($class, $data, $type);
        }
        if ($type instanceof SimpleType) {
            $this->visitSimpleType($class, $data, $type, $name);
        }
    }

    public function &visitElementDef(Schema $schema, ElementDef $element)
    {
        if (!isset($this->classes[spl_object_hash($element)])) {
            $className = $this->findPHPNamespace($element) . "\\" . $this->getNamingStrategy()->getItemName($element);
            $class = array();
            $data = array();
            $ns = $className;
            $class[$ns] = &$data;
            $data["xml_root_name"] = $element->getName();

            if ($schema->getTargetNamespace()) {
                $data["xml_root_namespace"] = $schema->getTargetNamespace();

                if (!$schema->getElementsQualification() && !($element instanceof Element && $element->isQualified())) {
                    $data["xml_root_name"] = "ns-" . substr(sha1($data["xml_root_namespace"]), 0, 8) . ":" . $data["xml_root_name"];
                }
            }
            $this->classes[spl_object_hash($element)]["class"] = &$class;

            if (!$element->getType()->getName()) {
                $this->visitTypeBase($class, $data, $element->getType(), $element->getName());
            } else {
                $this->handleClassExtension($class, $data, $element->getType(), $element->getName());
            }
        }
        $this->classes[spl_object_hash($element)]["skip"] = in_array($element->getSchema()->getTargetNamespace(), $this->baseSchemas, true);
        return $this->classes[spl_object_hash($element)]["class"];
    }

    private function findPHPNamespace(SchemaItem $item)
    {
        $schema = $item->getSchema();

        if (!isset($this->namespaces[$schema->getTargetNamespace()])) {
            throw new Exception(sprintf("Can't find a PHP namespace to '%s' namespace", $schema->getTargetNamespace()));
        }
        return $this->namespaces[$schema->getTargetNamespace()];
    }


    private function findPHPName(Type $type)
    {
        $schema = $type->getSchema();

        if ($alias = $this->getTypeAlias($type, $schema)) {
            return $alias;
        }

        $ns = $this->findPHPNamespace($type);
        $name = $this->getNamingStrategy()->getTypeName($type);

        return $ns . "\\" . $name;
    }


    public function &visitType(Type $type, $force = false)
    {
        $skip = in_array($type->getSchema()->getTargetNamespace(), $this->baseSchemas, true);

        if (!isset($this->classes[spl_object_hash($type)])) {

            $this->classes[spl_object_hash($type)]["skip"] = $skip;
            if ($alias = $this->getTypeAlias($type)) {
                $class = array();
                $class[$alias] = array();

                $this->classes[spl_object_hash($type)]["class"] = &$class;
                $this->classes[spl_object_hash($type)]["skip"] = true;
                return $class;
            }

            $className = $this->findPHPName($type);

            $class = array();
            $data = array();

            $class[$className] = &$data;

            $this->classes[spl_object_hash($type)]["class"] = &$class;

            $this->visitTypeBase($class, $data, $type, $type->getName());

            if ($type instanceof SimpleType) {
                $this->classes[spl_object_hash($type)]["skip"] = true;
                return $class;
            }

            if (!$force && ($this->isArrayType($type) || $this->isArrayNestedElement($type))) {
                $this->classes[spl_object_hash($type)]["skip"] = true;
                return $class;
            }
        } elseif ($force) {
            if (!($type instanceof SimpleType) && !$this->getTypeAlias($type)) {
                $this->classes[spl_object_hash($type)]["skip"] = $skip;
            }
        }
        return $this->classes[spl_object_hash($type)]["class"];
    }

    /**
     * @param Type $type
     * @param string $parentName
     * @param string $parentClass
     * @return array
     */
    private function &visitTypeAnonymous(Type $type, $parentName, $parentClass)
    {
        if (!isset($this->classes[spl_object_hash($type)])) {
            $class = array();
            $data = array();

            $name = $this->getNamingStrategy()->getAnonymousTypeName($type, $parentName);

            $class[$parentClass . "\\" . $name] = &$data;

            $this->visitTypeBase($class, $data, $type, $parentName);
            $this->classes[spl_object_hash($type)]["class"] = &$class;
            if ($type instanceof SimpleType) {
                $this->classes[spl_object_hash($type)]["skip"] = true;
            }
        }
        return $this->classes[spl_object_hash($type)]["class"];
    }

    private function visitComplexType(&$class, &$data, ComplexType $type)
    {
        $schema = $type->getSchema();
        if (!isset($data["properties"])) {
            $data["properties"] = array();
        }
        foreach ($this->flattElements($type) as $element) {
            $data["properties"][$this->getNamingStrategy()->getPropertyName($element)] = $this->visitElement($class, $schema, $element);
        }
    }

    private function visitSimpleType(&$class, &$data, SimpleType $type, $name)
    {
        if ($restriction = $type->getRestriction()) {
            $parent = $restriction->getBase();
            if ($parent instanceof Type) {
                $this->handleClassExtension($class, $data, $parent, $name);
            }
        } elseif ($unions = $type->getUnions()) {
            foreach ($unions as $i => $unon) {
                $this->handleClassExtension($class, $data, $unon, $name . $i);
                break;
            }
        }
    }

    private function visitBaseComplexType(&$class, &$data, BaseComplexType $type, $name)
    {
        $parent = $type->getParent();
        if ($parent) {
            $parentType = $parent->getBase();
            if ($parentType instanceof Type) {
                $this->handleClassExtension($class, $data, $parentType, $name);
            }
        }

        $schema = $type->getSchema();
        if (!isset($data["properties"])) {
            $data["properties"] = array();
        }
        foreach ($this->flattAttributes($type) as $attr) {
            $data["properties"][$this->getNamingStrategy()->getPropertyName($attr)] = $this->visitAttribute($class, $schema, $attr);
        }
    }

    private function handleClassExtension(&$class, &$data, Type $type, $parentName)
    {
        if ($alias = $this->getTypeAlias($type)) {


            $property = array();
            $property["expose"] = true;
            $property["xml_value"] = true;
            if (!$this->getCdata()) {
                $property["xml_element"]["cdata"] = false;
            }
            $property["access_type"] = "public_method";
            $property["accessor"]["getter"] = "value";
            $property["accessor"]["setter"] = "value";
            $property["type"] = $alias;

            $data["properties"]["__value"] = $property;


        } else {
            $extension = $this->visitType($type, true);

            if (isset($extension['properties']['__value']) && count($extension['properties']) === 1) {
                $data["properties"]["__value"] = $extension['properties']['__value'];
            } else {
                if ($type instanceof SimpleType) { // @todo ?? basta come controllo?
                    $property = array();
                    $property["expose"] = true;
                    $property["xml_value"] = true;
                    if (!$this->getCdata()) {
                        $property["xml_element"]["cdata"] = false;
                    }
                    $property["access_type"] = "public_method";
                    $property["accessor"]["getter"] = "value";
                    $property["accessor"]["setter"] = "value";

                    if ($valueProp = $this->typeHasValue($type, $class, $parentName)) {
                        $property["type"] = $valueProp;
                    } else {
                        $property["type"] = key($extension);
                    }

                    $data["properties"]["__value"] = $property;

                }
            }
        }
    }

    private function visitAttribute(&$class, Schema $schema, AttributeItem $attribute)
    {
        $property = array();
        $property["expose"] = true;
        $property["access_type"] = "public_method";
        $property["serialized_name"] = $attribute->getName();

        $property["accessor"]["getter"] = "get" . Inflector::classify($this->getNamingStrategy()->getPropertyName($attribute));
        $property["accessor"]["setter"] = "set" . Inflector::classify($this->getNamingStrategy()->getPropertyName($attribute));

        $property["xml_attribute"] = true;

        if ($alias = $this->getTypeAlias($attribute)) {
            $property["type"] = $alias;

        } elseif ($itemOfArray = $this->isArrayType($attribute->getType())) {

            if ($valueProp = $this->typeHasValue($itemOfArray, $class, 'xx')) {
                $property["type"] = "GoetasWebservices\Xsd\XsdToPhp\Jms\SimpleListOf<" . $valueProp . ">";
            } else {
                $property["type"] = "GoetasWebservices\Xsd\XsdToPhp\Jms\SimpleListOf<" . $this->findPHPName($itemOfArray) . ">";
            }

            $property["xml_list"]["inline"] = false;
            $property["xml_list"]["entry_name"] = $itemOfArray->getName();
            if ($schema->getTargetNamespace() && ($schema->getElementsQualification() || ($itemOfArray instanceof Element && $itemOfArray->isQualified()))) {
                $property["xml_list"]["entry_namespace"] = $schema->getTargetNamespace();
            }
        } else {
            $property["type"] = $this->findPHPClass($class, $attribute);
        }
        return $property;
    }

    private function typeHasValue(Type $type, $parentClass, $name)
    {
        do {
            if (!($type instanceof SimpleType)) {
                return false;
            }

            if ($alias = $this->getTypeAlias($type)) {
                return $alias;
            }

            if ($type->getName()) {
                $parentClass = $this->visitType($type);
            } else {
                $parentClass = $this->visitTypeAnonymous($type, $name, key($parentClass));
            }
            $props = reset($parentClass);
            if (isset($props['properties']['__value']) && count($props['properties']) === 1) {
                return $props['properties']['__value']['type'];
            }
        } while (method_exists($type, 'getRestriction') && $type->getRestriction() && $type = $type->getRestriction()->getBase());

        return false;
    }

    /**
     *
     * @param PHPClass $class
     * @param Schema $schema
     * @param Element $element
     * @param bool $arrayize
     * @return \GoetasWebservices\Xsd\XsdToPhp\Php\Structure\PHPProperty
     */
    private function visitElement(&$class, Schema $schema, ElementItem $element, $arrayize = true)
    {
        $property = array();
        $property["expose"] = true;
        $property["access_type"] = "public_method";
        $property["serialized_name"] = $element->getName();

        if ($element->getSchema()->getTargetNamespace() && ($schema->getElementsQualification() || ($element instanceof Element && $element->isQualified()))) {
            $property["xml_element"]["namespace"] = $element->getSchema()->getTargetNamespace();
        }

        $property["accessor"]["getter"] = "get" . Inflector::classify($this->getNamingStrategy()->getPropertyName($element));
        $property["accessor"]["setter"] = "set" . Inflector::classify($this->getNamingStrategy()->getPropertyName($element));
        $t = $element->getType();

        if ($arrayize) {

            if ($itemOfArray = $this->isArrayNestedElement($t)) {
                if (!$t->getName()) {

                    if ($element instanceof ElementRef) {
                        $itemClass = $this->findPHPClass($class, $element);
                    } else {
                        $itemClass = key($class);
                    }

                    $classType = $this->visitTypeAnonymous($t, $element->getName(), $itemClass);
                } else {
                    $classType = $this->visitType($t);
                }

                $visited = $this->visitElement($classType, $schema, $itemOfArray, false);

                $property["type"] = "array<" . $visited["type"] . ">";
                $property["xml_list"]["inline"] = false;
                $property["xml_list"]["entry_name"] = $itemOfArray->getName();
                $property["xml_list"]["skip_when_empty"] = ($element->getMin() === 0);

                if ($itemOfArray->getSchema()->getTargetNamespace() && ($itemOfArray->getSchema()->getElementsQualification())) {
                    $property["xml_list"]["namespace"] = $itemOfArray->getSchema()->getTargetNamespace();
                }
                return $property;
            } elseif ($itemOfArray = $this->isArrayType($t)) {

                if (!$t->getName()) {
                    if ($element instanceof ElementRef) {
                        $itemClass = $this->findPHPClass($class, $element);
                    } else {
                        $itemClass = key($class);
                    }

                    $visitedType = $this->visitTypeAnonymous($itemOfArray, $element->getName(), $itemClass);

                    if ($prop = $this->typeHasValue($itemOfArray, $class, 'xx')) {
                        $property["type"] = "array<" . $prop . ">";
                    } else {
                        $property["type"] = "array<" . key($visitedType) . ">";
                    }
                } else {
                    $this->visitType($itemOfArray);
                    $property["type"] = "array<" . $this->findPHPName($itemOfArray) . ">";
                }

                $property["xml_list"]["inline"] = false;
                $property["xml_list"]["entry_name"] = $itemOfArray->getName();
                $property["xml_list"]["skip_when_empty"] = ($element->getMin() === 0);

                if ($schema->getTargetNamespace() && ($schema->getElementsQualification() || ($element instanceof Element && $element->isQualified()))) {
                    $property["xml_list"]["namespace"] = $schema->getTargetNamespace();
                }
                return $property;
            } elseif ($this->isArrayElement($element)) {
                $property["xml_list"]["inline"] = true;
                $property["xml_list"]["entry_name"] = $element->getName();
                if ($schema->getTargetNamespace() && ($schema->getElementsQualification() || ($element instanceof Element && $element->isQualified()))) {
                    $property["xml_list"]["namespace"] = $schema->getTargetNamespace();
                }

                $property["type"] = "array<" . $this->findPHPClass($class, $element) . ">";
                return $property;
            }
        }

        $property["type"] = $this->findPHPClass($class, $element);
        return $property;
    }

    private function findPHPClass(&$class, Item $node)
    {
        $type = $node->getType();

        if ($alias = $this->getTypeAlias($node->getType())) {
            return $alias;
        }
        if ($node instanceof ElementRef) {
            $elementRef = $this->visitElementDef($node->getSchema(), $node->getReferencedElement());
            return key($elementRef);
        }
        if ($valueProp = $this->typeHasValue($type, $class, '')) {
            return $valueProp;
        }
        if (!$node->getType()->getName()) {
            $visited = $this->visitTypeAnonymous($node->getType(), $node->getName(), key($class));
        } else {
            $visited = $this->visitType($node->getType());
        }

        return key($visited);
    }
}
