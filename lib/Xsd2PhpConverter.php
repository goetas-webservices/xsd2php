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
use Goetas\XML\XSDReader\Schema\Attribute\AttributeReal;
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
use Goetas\Xsd\XsdToPhp\Structure\PHPArg;
use Goetas\XML\XSDReader\Schema\Element\ElementItem;
use Goetas\XML\XSDReader\Schema\Attribute\AttributeItem;
use Goetas\XML\XSDReader\Schema\Element\ElementRef;
use Goetas\XML\XSDReader\Schema\Element\ElementDef;
use Goetas\XML\XSDReader\Schema\Attribute\AttributeSingle;
use Goetas\XML\XSDReader\Schema\Attribute\AttributeContainer;
use Goetas\XML\XSDReader\Schema\Element\ElementSingle;

class Xsd2PhpConverter extends AbstractXsd2Converter
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

    /**
     *
     * @return PHPType[]
     */
    private function getTypes()
    {

        uasort($this->classes, function ($a, $b) {
            return strcmp($a["class"]->getFullName(), $b["class"]->getFullName());
        });
        $ret = array();
        foreach ($this->classes as $classData) {
            if(!isset($classData["skip"]) || !$classData["skip"]){
                $ret[$classData["class"]->getFullName()]=$classData["class"];
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

    private function visitTypeBase(PHPClass $class, Type $type)
    {
        if ($type instanceof BaseComplexType) {
            $this->visitBaseComplexType($class, $type);
        }
        if ($type instanceof ComplexType) {
            $this->visitComplexType($class, $type);
        }
        if ($type instanceof SimpleType) {
            $this->visitSimpleType($class, $type);
        }
    }

    private function visitGroup(Schema $schema, Group $group)
    {
        if (! isset($this->classes[spl_object_hash($group)])) {
            $this->classes[spl_object_hash($group)]["class"] = $trait = new PHPTrait();
            $trait->setName(Inflector::classify($group->getName()));
            $trait->setDoc($group->getDoc());

            if (! isset($this->namespaces[$schema->getTargetNamespace()])) {
                throw new Exception(sprintf("Can't find a PHP equivalent namespace for %s namespace", $schema->getTargetNamespace()));
            }
            $trait->setNamespace($this->namespaces[$schema->getTargetNamespace()]);

            foreach ($group->getElements() as $childGroup) {
                if ($childGroup instanceof Group) {
                    $trait->addTrait($this->visitGroup($schema, $childGroup));
                } else {
                    $property = $this->visitElement($trait, $schema, $childGroup);
                    $trait->addProperty($property);
                }
            }
        }
        return $this->classes[spl_object_hash($group)]["class"];
    }

    private function visitAttributeGroup(Schema $schema, AttributeGroup $att)
    {
        if (! isset($this->classes[spl_object_hash($att)])) {
            $this->classes[spl_object_hash($att)]["class"] = $trait = new PHPTrait();
            $trait->setName(Inflector::classify($att->getName()));
            $trait->setDoc($att->getDoc());

            if (! isset($this->namespaces[$schema->getTargetNamespace()])) {
                throw new Exception(sprintf("Can't find a PHP equivalent namespace for %s namespace", $schema->getTargetNamespace()));
            }
            $trait->setNamespace($this->namespaces[$schema->getTargetNamespace()]);

            foreach ($att->getAttributes() as $childAttr) {
                if ($childAttr instanceof AttributeGroup) {
                    $trait->addTrait($this->visitAttributeGroup($schema, $childAttr));
                } else {
                    $property = $this->visitAttribute($trait, $schema, $childAttr);
                    $trait->addProperty($property);
                }
            }
        }
        return $this->classes[spl_object_hash($att)]["class"];
    }

    private function visitElementDef(Schema $schema, ElementDef $element)
    {
        $class = new PHPClass();
        $class->setDoc($element->getDoc());
        $class->setName(Inflector::classify($element->getName()));
        $class->setDoc($element->getDoc());

        if (! isset($this->namespaces[$schema->getTargetNamespace()])) {
            throw new Exception(sprintf("Can't find a PHP equivalent namespace for %s namespace", $schema->getTargetNamespace()));
        }
        $class->setNamespace($this->namespaces[$schema->getTargetNamespace()]);

        if (isset($this->classes[$class->getFullName()])) {
            return $this->classes[$class->getFullName()]["class"];
        }
        $this->classes[$class->getFullName()]["class"] = $class;

        if ($element->isAnonymousType()) {
            $this->visitTypeBase($class, $element->getType());
        } else {
            $this->handleClassExtension($class, $element->getType());
        }

        return $class;
    }
    private function findPHPName(Type $type)
    {
        $schema = $type->getSchema();

        if (isset($this->typeAliases[$schema->getTargetNamespace()][$type->getName()])) {
            $className = call_user_func($this->typeAliases[$schema->getTargetNamespace()][$type->getName()], $type);

            if (($pos = strpos($className, '<')) !== false) {
                $className = substr($className, 0, $pos);
            }

            if (($pos = strrpos($className, '\\')) !== false) {
                return [
                    substr($className, $pos + 1),
                    substr($className, 0, $pos)
                ];
            } else {
                return [
                    $className,
                    null
                ];
            }
        }

        $name = Inflector::classify($type->getName());
        if ($name && substr($name, -4)!=='Type') {
            $name .= "Type";
        }

        if (! isset($this->namespaces[$schema->getTargetNamespace()])) {
            throw new Exception(sprintf("Can't find a PHP equivalent namespace for %s namespace", $schema->getTargetNamespace()));
        }
        $ns = $this->namespaces[$schema->getTargetNamespace()];
        return [
            $name,
            $ns
        ];
    }

    private function isSimplePHP(Type $type)
    {
        list ($name, $ns) = $this->findPHPName($type);
        return ! $ns && (!$name || in_array($name, $this->baseTypes));
    }

    private function visitType(Type $type)
    {
        if (! isset($this->classes[spl_object_hash($type)])) {

            $this->classes[spl_object_hash($type)]["class"] = $class = new PHPClass();

            list ($name, $ns) = $this->findPHPName($type);
            $class->setName($name);
            $class->setNamespace($ns);
            $class->setDoc($type->getDoc() . PHP_EOL . "XSD Type: " . ($type->getName() ?  : 'anonymous'));

            $this->visitTypeBase($class, $type);

            if ($this->isArray($type) || $this->isSimplePHP($type)) {
                $this->classes[spl_object_hash($type)]["skip"] = true;
                return $class;
            }

            $this->classes[spl_object_hash($type)]["skip"] = !!$this->getTypeAlias($type);
        }
        return $this->classes[spl_object_hash($type)]["class"];
    }

    private function visitAnonymousType(Schema $schema, Type $type, $name, PHPType $parentClass)
    {
        if (! isset($this->classes[spl_object_hash($type)])) {
            $this->classes[spl_object_hash($type)]["class"] = $class = new PHPClass();
            $class->setName(Inflector::classify($name) . "AType");

            $class->setNamespace($parentClass->getNamespace() . "\\" . $parentClass->getName());
            $class->setDoc($type->getDoc());
            $this->visitTypeBase($class, $type);
        }
        return $this->classes[spl_object_hash($type)]["class"];
    }

    private function visitComplexType(PHPClass $class, ComplexType $type)
    {
        $schema = $type->getSchema();
        foreach ($type->getElements() as $element) {

            if ($element instanceof Group) {
                $trait = $this->visitGroup($schema, $element);
                $class->addTrait($trait);
            } else {
                $property = $this->visitElement($class, $schema, $element);
                $class->addProperty($property);
            }
        }
    }

    private function visitSimpleType(PHPClass $class, SimpleType $type)
    {
        if ($restriction = $type->getRestriction()) {
            $parent = $restriction->getBase();

            if ($parent instanceof Type) {
                $this->handleClassExtension($class, $parent);
            }

            foreach ($restriction->getChecks() as $typeCheck => $checks) {
                foreach ($checks as $check) {
                    $class->addCheck('__value', $typeCheck, $check);
                }
            }
        } elseif ($unions = $type->getUnions()) {

            $types = array();
            foreach ($unions as $unon) {
                if ($this->isSimplePHP($unon)) {
                    list ($name) = $this->findPHPName($unon);
                    $types[$name] = $unon;
                } elseif ($unon->getRestriction() && $unon->getRestriction()->getBase() && $this->isSimplePHP($unon->getRestriction()->getBase())) {
                    list ($name) = $this->findPHPName($unon->getRestriction()->getBase());
                    $types[$name] = $unon->getRestriction()->getBase();
                }
            }
            $val = new PHPProperty('__value');
            if (count($types) == 1 && ($candidato = reset($types))) {
                $val->setType($this->visitType($candidato));
            }
            $class->addProperty($val);
        }
    }

    private function handleClassExtension(PHPClass $class, Type $type)
    {
        if (! $this->isSimplePHP($type)) {
            $extension = $this->visitType($type);
            $class->setExtends($extension);
        } else {
            $extension = $this->visitType($type);
            $val = new PHPProperty('__value');
            $val->setType($extension);
            $class->addProperty($val);
        }
    }

    private function visitBaseComplexType(PHPClass $class, BaseComplexType $type)
    {
        $parent = $type->getParent();
        if ($parent) {
            $parentType = $parent->getBase();
            if ($parentType instanceof Type) {
                $this->handleClassExtension($class, $parentType);
            }
        }
        $schema = $type->getSchema();

        foreach ($type->getAttributes() as $attr) {
            if ($attr instanceof AttributeGroup) {
                $trait = $this->visitAttributeGroup($schema, $attr);
                $class->addTrait($trait);
            } else {
                $property = $this->visitAttribute($class, $schema, $attr);
                $class->addProperty($property);
            }
        }
    }

    private function visitAttribute(PHPType $class, Schema $schema, AttributeItem $attribute)
    {
        $property = new PHPProperty();
        $property->setName(Inflector::camelize($attribute->getName()));
        $property->setType($this->findPHPType($class, $schema, $attribute));
        $property->setDoc($attribute->getDoc());
        return $property;
    }

    /**
     *
     * @param PHPType $class
     * @param Schema $schema
     * @param Element $element
     * @param boolean $arrayize
     * @return \Goetas\Xsd\XsdToPhp\Structure\PHPProperty
     */
    private function visitElement(PHPType $class, Schema $schema, ElementSingle $element, $arrayize = true)
    {
        $property = new PHPProperty();
        $property->setName(Inflector::camelize($element->getName()));

        if (($t = $element->getType()) && ($itemOfArray = $this->isArray($t))) {
            $property->setType(new PHPClassOf($this->visitElement($class, $schema, $itemOfArray, false)));
        } else {
            if ($arrayize && $element instanceof ElementItem && ($element->getMax() > 1 || $element->getMax() === - 1)) {

                $arg = new PHPArg();
                $arg->setType($this->findPHPType($class, $schema, $element));
                $arg->setDefault('array()');
                $arg->setName($element->getName());
                $property->setType(new PHPClassOf($arg));
            } else {
                $property->setType($this->findPHPType($class, $schema, $element));
            }
            $property->setDoc($element->getDoc());
        }

        return $property;
    }

    private function findPHPType(PHPType $class, Schema $schema, Item $node)
    {

        if ($node->isAnonymousType()) {
            return $this->visitAnonymousType($schema, $node->getType(), $node->getName(), $class);
        } else {
            return $this->visitType($node->getType());
        }
    }
}
