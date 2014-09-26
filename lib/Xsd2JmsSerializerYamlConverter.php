<?php
namespace Goetas\Xsd\XsdToPhp;

use Exception;
use Goetas\XML\XSDReader\SchemaReader;
use Goetas\XML\XSDReader\Schema\Schema;
use Goetas\XML\XSDReader\Schema\Type\Type;
use Goetas\Xsd\XsdToPhp\Structure\PHPClass;
use Doctrine\Common\Inflector\Inflector;
use Goetas\XML\XSDReader\Schema\Type\BaseComplexType;
use Goetas\Xsd\XsdToPhp\Structure\PHPProperty;
use Goetas\XML\XSDReader\Schema\Type\ComplexType;
use Goetas\XML\XSDReader\Schema\Element\Element;
use Goetas\XML\XSDReader\Schema\Item;
use Goetas\XML\XSDReader\Schema\Attribute\Group as AttributeGroup;
use Goetas\Xsd\XsdToPhp\Structure\PHPTrait;
use Goetas\Xsd\XsdToPhp\Structure\PHPType;
use Goetas\XML\XSDReader\Schema\Element\Group;
use Goetas\XML\XSDReader\Schema\Type\SimpleType;
use Goetas\Xsd\XsdToPhp\Generator\ClassGenerator;
use Goetas\Xsd\XsdToPhp\Structure\PHPClassOf;
use Goetas\Xsd\XsdToPhp\Structure\PHPConstant;
use Goetas\XML\XSDReader\Schema\Attribute\AttributeItem;
use Goetas\XML\XSDReader\Schema\Element\ElementItem;
use Goetas\XML\XSDReader\Schema\Attribute\AttributeContainer;
use Goetas\XML\XSDReader\Schema\Element\ElementContainer;
use Goetas\XML\XSDReader\Schema\Element\ElementSingle;
use Goetas\XML\XSDReader\Schema\Element\ElementDef;

class Xsd2JmsSerializerYamlConverter extends AbstractXsd2Converter
{

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

    private function flattAttributes(AttributeContainer $container){
        $items = array();
        foreach ($container->getAttributes() as $attr){
            if ($attr instanceof AttributeContainer) {
                $items = array_merge($items, $this->flattAttributes($attr));
            } else {
                $items[] = $attr;
            }
        }
        return $items;
    }

    private function flattElements(ElementContainer $container){
        $items = array();
        foreach ($container->getElements() as $attr){
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
     * @return PHPType[]
     */
    public function getTypes()
    {
        uasort($this->classes, function ($a, $b)
        {
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
            if (! in_array($schildSchema->getTargetNamespace(), $this->baseSchemas, true)) {
                $this->navigate($schildSchema, $visited);
            }
        }
    }

    private function visitTypeBase(&$class, &$data, Type $type)
    {
        if ($type instanceof BaseComplexType) {
            $this->visitBaseComplexType($class, $data, $type);
        }
        if ($type instanceof ComplexType) {
            $this->visitComplexType($class, $data, $type);
        }
        if ($type instanceof SimpleType) {
            $this->visitSimpleType($class, $data, $type);
        }
    }

    private function &visitElementDef(Schema $schema, ElementDef $element)
    {
        $className = $this->findPHPName($element, $schema);
        $class = array();
        $data = array();
        $ns = $className;
        $class[$ns] = &$data;
        $data["xml_root_name"] = $element->getName();

        if ($schema->getTargetNamespace()) {
            $data["xml_root_namespace"] = $schema->getTargetNamespace();
        }

        if (isset($this->classes[$ns])) {
            return $this->classes[$ns]["class"];
        }
        $this->classes[$ns]["class"] = &$class;

        if ($element->isAnonymousType()) {
            $this->visitTypeBase($class, $data, $element->getType());
        } else {
            $this->handleClassExtension($class, $data, $element->getType());
        }

        return $class;
    }

    private function findPHPName($type, Schema $schemapos = null)
    {
        $schema = $schemapos ?  : $type->getSchema();

        if ($alias = $this->getTypeAlias($type, $schema)) {
            return $alias;
        }

        if (! isset($this->namespaces[$schema->getTargetNamespace()])) {
            throw new Exception(sprintf("Non trovo un namespace php per %s, nel file %s", $schema->getTargetNamespace(), $schema->getFile()));
        }
        $ns = $this->namespaces[$schema->getTargetNamespace()];
        $name = Inflector::classify($type->getName());
        return $ns . "\\" . $name;
    }

    private function isSimplePHP(Type $type)
    {
        $className = $this->findPHPName($type);
        return in_array(trim($className, "\\"), $this->baseTypes);
    }

    private function &visitType(Type $type)
    {
        if (! isset($this->classes[spl_object_hash($type)])) {

            $className = $this->findPHPName($type);

            $class = array();
            $data = array();
            $class[$className] = &$data;

            $this->classes[spl_object_hash($type)]["class"] = &$class;

            $this->visitTypeBase($class, $data, $type);

            if ($this->isArray($type) || $this->getTypeAlias($type)) {
                $this->classes[spl_object_hash($type)]["skip"] = true;
                return $class;
            }
        }
        return $this->classes[spl_object_hash($type)]["class"];
    }

    private function &visitAnonymousType(Schema $schema, Type $type, $name, &$parentClass)
    {
        $class = array();
        $data = array();

        $class[key($parentClass) . "\\" . Inflector::classify($name) . "Type"] = &$data;

        $this->visitTypeBase($class, $data, $type);

        $this->classes[spl_object_hash($type)]["class"] = &$class;

        return $class;
    }

    private function visitComplexType(&$class, &$data, ComplexType $type)
    {
        $schema = $type->getSchema();
        if (!isset($data["properties"])) {
            $data["properties"] = array();
        }
        foreach ($this->flattElements($type) as $element) {
            $data["properties"][Inflector::camelize($element->getName())] = $this->visitElement($class, $schema, $element);
        }
    }

    private function visitSimpleType(&$class, &$data, SimpleType $type)
    {
        if ($restriction = $type->getRestriction()) {
            $parent = $restriction->getBase();
            if ($parent instanceof Type) {
                $this->handleClassExtension($class, $data, $parent);
            }
        }
    }

    private function visitBaseComplexType(&$class, &$data, BaseComplexType $type)
    {
        $parent = $type->getParent();
        if ($parent) {
            $parentType = $parent->getBase();
            if ($parentType instanceof Type) {
                $this->handleClassExtension($class, $data, $parentType);
            }
        }

        $schema = $type->getSchema();
        if(!isset($data["properties"])){
            $data["properties"] = array();
        }
        foreach ($this->flattAttributes($type) as $attr) {
            $data["properties"][Inflector::camelize($attr->getName())] = $this->visitAttribute($class, $schema, $attr);
        }
    }

    private function handleClassExtension(&$class, &$data, Type $type)
    {
        if ($this->isSimplePHP($type)) {

            $extension = $this->visitType($type);

            $property = array();
            $property["expose"] = true;
            $property["xml_value"] = true;
            $property["access_type"] = "public_method";
            $property["accessor"]["getter"] = "value";
            $property["accessor"]["setter"] = "value";
            $property["type"] = key($extension);

            $data["properties"]["__value"] = $property;
        }
    }

    private function visitAttribute(&$class, Schema $schema, AttributeItem $attribute)
    {
        $property = array();
        $property["expose"] = true;
        $property["access_type"] = "public_method";
        $property["serialized_name"] = $attribute->getName();

        $property["accessor"]["getter"] = "get" . Inflector::classify($attribute->getName());
        $property["accessor"]["setter"] = "set" . Inflector::classify($attribute->getName());

        $property["xml_attribute"] = true;
        $property["type"] = $this->findPHPType($class, $schema, $attribute);

        if (! $this->isSimplePHP($attribute->getType()) && ! $this->getTypeAlias($attribute->getType())) {
            if ($valueProp = $this->typeHasValue($attribute->getType(), $class, $attribute->getName())) {
                $property["type"] = $this->nestType($property["type"], $valueProp['type'], 'Goetas\Xsd\XsdToPhp\BaseTypeValue');
            }
        }

        return $property;
    }

    private function typeHasValue(Type $type, &$parentClass, $name)
    {
        do {
            if ($this->isSimplePHP($type)) {
                if ($type->getName()) {
                    $class = $this->visitType($type);
                } else {
                    $class = $this->visitAnonymousType($type->getSchema(), $type, $name, $parentClass);
                }
                $props = reset($class);
                if (isset($props['properties']['__value'])) {
                    return $props['properties']['__value'];
                }
            }
        } while (method_exists($type, 'getRestriction') && $type->getRestriction() && $type = $type->getRestriction()->getBase());

        return false;
    }

    /**
     *
     * @param PHPType $class
     * @param Schema $schema
     * @param Element $element
     * @param boolean $arrayize
     * @return \Goetas\Xsd\XsdToPhp\Structure\PHPProperty
     */
    private function visitElement(&$class, Schema $schema, ElementItem $element, $arrayize = true)
    {
        $property = array();
        $property["expose"] = true;
        $property["access_type"] = "public_method";
        $property["serialized_name"] = $element->getName();

        if ($schema->getTargetNamespace()) {
            $property["xml_element"]["namespace"] = $schema->getTargetNamespace();
        }

        $property["accessor"]["getter"] = "get" . Inflector::classify($element->getName());
        $property["accessor"]["setter"] = "set" . Inflector::classify($element->getName());

        if (($t = $element->getType()) && ($itemOfArray = $this->isArray($t))) {
            $visited = $this->visitElement($class, $schema, $itemOfArray, false);
            $property["type"] = "array<" . $visited["type"] . ">";
            $property["xml_list"]["inline"] = false;
            $property["xml_list"]["entry_name"] = $itemOfArray->getName();
            $property["xml_list"]["entry_namespace"] = $schema->getTargetNamespace();
        } else {
            if ($arrayize && $element instanceof ElementSingle && ($element->getMax() > 1 || $element->getMax() === - 1)) {
                $property["xml_list"]["inline"] = true;
                $property["xml_list"]["entry_name"] = $element->getName();
                $property["xml_list"]["entry_namespace"] = $schema->getTargetNamespace();
                $property["type"] = "array<" . $this->findPHPType($class, $schema, $element) . ">";
            } else {

                $property["type"] = $this->findPHPType($class, $schema, $element);

                if (! $this->isSimplePHP($element->getType()) && ! $this->getTypeAlias($element->getType())) {
                    if ($valueProp = $this->typeHasValue($element->getType(), $class, $element->getName())) {
                        $property["type"] = $this->nestType($property["type"], $valueProp['type'], 'Goetas\Xsd\XsdToPhp\BaseTypeValue');
                    }
                }
            }
        }

        return $property;
    }

    private function nestType($outherType, $innerType, $newType)
    {
        $mch = array();
        if (preg_match("/([^<]+)<(.*)>/", $outherType, $mch)) {
            return $newType . "<'" . $mch[1] . "', '$innerType', $mch[2]>";
        } else {
            return $newType . "<'$outherType', '$innerType'>";
        }
    }

    private function findPHPType(&$class, Schema $schema, Item $node)
    {
        $type = $node->getType();

        if (isset($this->typeAliases[$schema->getTargetNamespace()][$type->getName()])) {
            return call_user_func($this->typeAliases[$schema->getTargetNamespace()][$type->getName()], $node);
        }

        if ($this->isSimplePHP($type)) {
            $className = $this->findPHPName($type);
            return $className;
        }

        if ($node->isAnonymousType()) {
            $visited = $this->visitAnonymousType($schema, $node->getType(), $node->getName(), $class);
        } else {
            $visited = $this->visitType($node->getType());
        }

        return key($visited);
    }
}
