<?php
namespace Goetas\Xsd\XsdToPhp\Php;

use Exception;
use Doctrine\Common\Inflector\Inflector;
use Goetas\Xsd\XsdToPhp\Php\Structure\PHPClassOf;
use Goetas\Xsd\XsdToPhp\Php\Structure\PHPArg;
use Goetas\Xsd\XsdToPhp\Php\Structure\PHPClass;
use Goetas\Xsd\XsdToPhp\Php\Structure\PHPProperty;
use Goetas\XML\XSDReader\SchemaReader;
use Goetas\XML\XSDReader\Schema\Schema;
use Goetas\XML\XSDReader\Schema\Type\Type;
use Goetas\XML\XSDReader\Schema\Type\BaseComplexType;
use Goetas\XML\XSDReader\Schema\Type\ComplexType;
use Goetas\XML\XSDReader\Schema\Element\Element;
use Goetas\XML\XSDReader\Schema\Item;
use Goetas\XML\XSDReader\Schema\Attribute\Group as AttributeGroup;
use Goetas\XML\XSDReader\Schema\Element\Group;
use Goetas\XML\XSDReader\Schema\Type\SimpleType;
use Goetas\XML\XSDReader\Schema\Element\ElementItem;
use Goetas\XML\XSDReader\Schema\Attribute\AttributeItem;
use Goetas\XML\XSDReader\Schema\Element\ElementRef;
use Goetas\XML\XSDReader\Schema\Element\ElementDef;
use Goetas\XML\XSDReader\Schema\Attribute\AttributeSingle;
use Goetas\XML\XSDReader\Schema\Attribute\AttributeContainer;
use Goetas\XML\XSDReader\Schema\Element\ElementSingle;
use Goetas\Xsd\XsdToPhp\AbstractConverter;

class PhpConverter extends AbstractConverter
{

    public function __construct(){
        parent::__construct();
        $this->addAliasMap("http://www.w3.org/2001/XMLSchema", "dateTime", function (Type $type)
        {
            return "DateTime";
        });
        $this->addAliasMap("http://www.w3.org/2001/XMLSchema", "time", function (Type $type)
        {
            return "DateTime";
        });
    }
    public function getTypeAlias($type, Schema $schemapos = null)
    {
        if ($alias = parent::getTypeAlias($type, $schemapos)){
            if (($pos = strpos($alias, '<')) !== false) {
                $alias = substr($alias, 0, $pos);
            }
            return $alias;
        }
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

    /**
     *
     * @return PHPClass[]
     */
    private function getTypes()
    {
        uasort($this->classes, function ($a, $b)
        {
            return strcmp($a["class"]->getFullName(), $b["class"]->getFullName());
        });
        $ret = array();
        foreach ($this->classes as $classData) {
            if (! isset($classData["skip"]) || ! $classData["skip"]) {
                $ret[$classData["class"]->getFullName()] = $classData["class"];
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
        $class->setAbstract($type->isAbstract());

        if ($type instanceof SimpleType) {
            $this->visitSimpleType($class, $type);
        }
        if ($type instanceof BaseComplexType) {
            $this->visitBaseComplexType($class, $type);
        }
        if ($type instanceof ComplexType) {
            $this->visitComplexType($class, $type);
        }
    }

    private function visitGroup(PHPClass $class, Schema $schema, Group $group)
    {
        foreach ($group->getElements() as $childGroup) {
            if ($childGroup instanceof Group) {
                $this->visitGroup($class, $schema, $childGroup);
            } else {
                $property = $this->visitElement($class, $schema, $childGroup);
                $class->addProperty($property);
            }
        }
    }

    private function visitAttributeGroup(PHPClass $class, Schema $schema, AttributeGroup $att)
    {
        foreach ($att->getAttributes() as $childAttr) {
            if ($childAttr instanceof AttributeGroup) {
                $this->visitAttributeGroup($class, $schema, $childAttr);
            } else {
                $property = $this->visitAttribute($class, $schema, $childAttr);
                $class->addProperty($property);
            }
        }
    }

    private function visitElementDef(Schema $schema, ElementDef $element)
    {
        if (! isset($this->classes[spl_object_hash($element)])) {
            $class = new PHPClass();
            $class->setDoc($element->getDoc());
            $class->setName(Inflector::classify($element->getName()));
            $class->setDoc($element->getDoc());

            if (! isset($this->namespaces[$schema->getTargetNamespace()])) {
                throw new Exception(sprintf("Can't find a PHP equivalent namespace for %s namespace", $schema->getTargetNamespace()));
            }
            $class->setNamespace($this->namespaces[$schema->getTargetNamespace()]);

            $this->classes[spl_object_hash($element)]["class"] = $class;

            if (! $element->getType()->getName()) {
                $this->visitTypeBase($class, $element->getType());
            } else {
                $this->handleClassExtension($class, $element->getType());
            }
        }
        return $this->classes[spl_object_hash($element)]["class"];
    }

    private function findPHPName(Type $type)
    {
        $schema = $type->getSchema();

        if ($className = $this->getTypeAlias($type)) {

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
        if ($name && substr($name, - 4) !== 'Type') {
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

    /**
     *
     * @param Type $type
     * @param boolean $force
     * @return \Goetas\Xsd\XsdToPhp\Php\Structure\PHPClass
     */
    private function visitType(Type $type, $force = false)
    {
        /*
        var_dump($type->getName());
        var_dump($force);
        echo "\n\n";
        */
        if (! isset($this->classes[spl_object_hash($type)])) {

            $this->classes[spl_object_hash($type)]["class"] = $class = new PHPClass();

            if ($alias = $this->getTypeAlias($type)) {
                $class->setName($alias);
                $this->classes[spl_object_hash($type)]["skip"] = true;
                return $class;
            }

            list ($name, $ns) = $this->findPHPName($type);
            $class->setName($name);
            $class->setNamespace($ns);

            $class->setDoc($type->getDoc() . PHP_EOL . "XSD Type: " . ($type->getName() ?  : 'anonymous'));

            $this->visitTypeBase($class, $type);

            if ($type instanceof SimpleType){
                $this->classes[spl_object_hash($type)]["skip"] = true;
                return $class;
            }
            if ($this->isArray($type) && !$force) {
                $this->classes[spl_object_hash($type)]["skip"] = true;
                return $class;
            }

            $this->classes[spl_object_hash($type)]["skip"] = !!$this->getTypeAlias($type);
        }elseif ($force) {
            if (!($type instanceof SimpleType) && !$this->getTypeAlias($type)){
                $this->classes[spl_object_hash($type)]["skip"] = false;
            }
        }
        return $this->classes[spl_object_hash($type)]["class"];
    }

    /**
     * @param Type $type
     * @param string $name
     * @param PHPClass $parentClass
     * @return \Goetas\Xsd\XsdToPhp\Php\Structure\PHPClass
     */
    private function visitTypeAnonymous(Type $type, $name, PHPClass $parentClass)
    {
        if (! isset($this->classes[spl_object_hash($type)])) {
            $this->classes[spl_object_hash($type)]["class"] = $class = new PHPClass();
            $class->setName(Inflector::classify($name) . "AType");

            $class->setNamespace($parentClass->getNamespace() . "\\" . $parentClass->getName());
            $class->setDoc($type->getDoc());

            $this->visitTypeBase($class, $type);

            if ($type instanceof SimpleType){
                $this->classes[spl_object_hash($type)]["skip"] = true;
            }
        }
        return $this->classes[spl_object_hash($type)]["class"];
    }

    private function visitComplexType(PHPClass $class, ComplexType $type)
    {
        $schema = $type->getSchema();
        foreach ($type->getElements() as $element) {
            if ($element instanceof Group) {
                $this->visitGroup($class, $schema, $element);
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
            foreach ($unions as $i => $unon) {
                if (! $unon->getName()) {
                    $types[] = $this->visitTypeAnonymous($unon, $type->getName() . $i, $class);
                } else {
                    $types[] = $this->visitType($unon);
                }
            }

            if ($candidato = reset($types)) {
                $class->setExtends($candidato);
            }
        }
    }

    private function handleClassExtension(PHPClass $class, Type $type)
    {

        if ($alias = $this->getTypeAlias($type)) {
            $c = PHPClass::createFromFQCN($alias);
            $val = new PHPProperty('__value');
            $val->setType($c);
            $c->addProperty($val);
            $class->setExtends($c);
        } else {
            $extension = $this->visitType($type, true);
            $class->setExtends($extension);
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
                $this->visitAttributeGroup($class, $schema, $attr);
            } else {
                $property = $this->visitAttribute($class, $schema, $attr);
                $class->addProperty($property);
            }
        }
    }

    private function visitAttribute(PHPClass $class, Schema $schema, AttributeItem $attribute, $arrayize = true)
    {
        $property = new PHPProperty();
        $property->setName(Inflector::camelize($attribute->getName()));

        if ($arrayize && $itemOfArray = $this->isArray($attribute->getType())) {
            if ($attribute->getType()->getName()) {
                $arg = new PHPArg($attribute->getName());

                $arg->setType($this->visitType($itemOfArray));
                $property->setType(new PHPClassOf($arg));
            } else {
                $property->setType($this->visitTypeAnonymous($attribute->getType(), $attribute->getName(), $class));
            }
        } else {
            $property->setType($this->findPHPClass($class, $schema, $attribute, true));
        }

        $property->setDoc($attribute->getDoc());
        return $property;
    }

    /**
     *
     * @param PHPClass $class
     * @param Schema $schema
     * @param Element $element
     * @param boolean $arrayize
     * @return \Goetas\Xsd\XsdToPhp\Structure\PHPProperty
     */
    private function visitElement(PHPClass $class, Schema $schema, ElementSingle $element, $arrayize = true)
    {
        $property = new PHPProperty();
        $property->setName(Inflector::camelize($element->getName()));

        if ($arrayize && ($t = $element->getType()) && ($itemOfArray = $this->isArray($t))) {

            if($itemOfArray instanceof Type){
                if(!$itemOfArray->getName()){
                    $classType = $this->visitTypeAnonymous($itemOfArray, $element->getName(), $class);
                }else{
                    $classType = $this->visitType($itemOfArray);
                }
                $elementProp = new PHPProperty();
                $elementProp->setName(Inflector::camelize($element->getName()));
                $elementProp->setType($classType);
            }else{
                if(!$t->getName()){
                    $classType = $this->visitTypeAnonymous($t, $element->getName(), $class);
                }else{
                    $classType = $this->visitType($t);
                }
                $elementProp = $this->visitElement($classType, $schema, $itemOfArray, false);
            }
            $property->setType(new PHPClassOf($elementProp));
        } else {

            if ($arrayize && $element instanceof ElementItem && ($element->getMax() > 1 || $element->getMax() === - 1)) {

                $arg = new PHPArg();
                $arg->setType($this->findPHPClass($class, $schema, $element));
                $arg->setDefault('array()');
                $arg->setName($element->getName());
                $property->setType(new PHPClassOf($arg));
            } else {
                $property->setType($this->findPHPClass($class, $schema, $element, true));
            }
            $property->setDoc($element->getDoc());
        }

        return $property;
    }

    private function findPHPClass(PHPClass $class, Schema $schema, Item $node, $force = false)
    {

        if ($node instanceof ElementRef && $node->getReferencedElement() instanceof ElementDef && !($node->getReferencedElement() instanceof Element)) {
            return $this->visitElementDef($schema, $node->getReferencedElement());
        }

        if (! $node->getType()->getName()) {
            return $this->visitTypeAnonymous($node->getType(), $node->getName(), $class);
        } else {
            return $this->visitType($node->getType(), $force);
        }
    }
}
