<?php

namespace GoetasWebservices\Xsd\XsdToPhp\Jms;

use Doctrine\Inflector\InflectorFactory;
use Exception;
use GoetasWebservices\XML\XSDReader\Schema\Attribute\AttributeContainer;
use GoetasWebservices\XML\XSDReader\Schema\Attribute\AttributeItem;
use GoetasWebservices\XML\XSDReader\Schema\Element\Element;
use GoetasWebservices\XML\XSDReader\Schema\Element\ElementContainer;
use GoetasWebservices\XML\XSDReader\Schema\Element\ElementDef;
use GoetasWebservices\XML\XSDReader\Schema\Element\ElementItem;
use GoetasWebservices\XML\XSDReader\Schema\Element\ElementRef;
use GoetasWebservices\XML\XSDReader\Schema\Element\ElementSingle;
use GoetasWebservices\XML\XSDReader\Schema\Item;
use GoetasWebservices\XML\XSDReader\Schema\Schema;
use GoetasWebservices\XML\XSDReader\Schema\SchemaItem;
use GoetasWebservices\XML\XSDReader\Schema\Type\BaseComplexType;
use GoetasWebservices\XML\XSDReader\Schema\Type\ComplexType;
use GoetasWebservices\XML\XSDReader\Schema\Type\ComplexTypeSimpleContent;
use GoetasWebservices\XML\XSDReader\Schema\Type\SimpleType;
use GoetasWebservices\XML\XSDReader\Schema\Type\Type;
use GoetasWebservices\Xsd\XsdToPhp\AbstractConverter;
use GoetasWebservices\Xsd\XsdToPhp\Naming\NamingStrategy;
use GoetasWebservices\Xsd\XsdToPhp\Php\Structure\PHPClass;

class YamlConverter extends AbstractConverter
{
    protected $useCdata = true;

    public function __construct(NamingStrategy $namingStrategy)
    {
        parent::__construct($namingStrategy);

        $this->addAliasMap('http://www.w3.org/2001/XMLSchema', 'dateTime', function (Type $type) {
            return "GoetasWebservices\Xsd\XsdToPhp\XMLSchema\DateTime";
        });
        $this->addAliasMap('http://www.w3.org/2001/XMLSchema', 'time', function (Type $type) {
            return "GoetasWebservices\Xsd\XsdToPhp\XMLSchema\Time";
        });
        $this->addAliasMap('http://www.w3.org/2001/XMLSchema', 'date', function (Type $type) {
            return "GoetasWebservices\Xsd\XsdToPhp\XMLSchema\Date";
        });

        $this->addAliasMap('http://www.w3.org/2001/XMLSchema', 'base64Binary', function (Type $type) {
            return "GoetasWebservices\Xsd\XsdToPhp\Jms\Base64Encoded";
        });
    }

    public function setUseCdata($value)
    {
        $this->logger->info("Set useCdata $value");
        $this->useCdata = $value;

        return $this;
    }

    private $classes = [];

    public function convert(array $schemas)
    {
        $visited = [];
        $this->classes = [];
        foreach ($schemas as $schema) {
            $this->navigate($schema, $visited);
        }

        return $this->getTypes();
    }

    private function flattAttributes(AttributeContainer $container)
    {
        $items = [];
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
        $items = [];
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
     * @return PHPClass[]
     */
    public function getTypes()
    {
        uasort($this->classes, function ($a, $b) {
            return strcmp(key($a), key($b));
        });

        $ret = [];

        foreach ($this->classes as $definition) {
            $classname = key($definition['class']);
            if (strpos($classname, '\\') !== false && (!isset($definition['skip']) || !$definition['skip'])) {
                unset($definition['class'][$classname]['extends']);
                $ret[$classname] = $definition['class'];
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
            $this->visitType($type, true);
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
            $className = $this->findPHPNamespace($element) . '\\' . $this->getNamingStrategy()->getItemName($element);
            $class = [];
            $data = [];
            $ns = $className;
            $class[$ns] = &$data;
            $data['xml_root_name'] = $element->getName();

            if ($schema->getTargetNamespace()) {
                $data['xml_root_namespace'] = $schema->getTargetNamespace();

                if (!$schema->getElementsQualification() && !($element instanceof Element && $element->isQualified())) {
                    $data['xml_root_name'] = 'ns-' . substr(sha1($data['xml_root_namespace']), 0, 8) . ':' . $element->getName();
                }
            }
            $this->classes[spl_object_hash($element)]['class'] = &$class;

            $type = $element->getType();
            if (!$type->getName()) {
                $visitedTypeClass = $this->visitTypeAnonymous($type, $element->getName(), key($class));
            } else {
                $visitedTypeClass = $this->visitType($type);
            }

            if ($prefix = $this->getRootPrefix($ns, $schema->getTargetNamespace())) {
                $data['xml_root_prefix'] = $prefix;
            }

            $data['extends'] = $visitedTypeClass;
            $this->classes[spl_object_hash($element)]['skip'] = in_array($element->getSchema()->getTargetNamespace(), $this->baseSchemas, true)
                || $this->getTypeAlias($type, $type->getSchema())
            ;

            if (!$this->classes[spl_object_hash($element)]['skip'] && ($p = $this->getPropertyInHierarchy($visitedTypeClass, '__value'))) {
                $data['properties']['__value'] = $p;
            }
        }

        return $this->classes[spl_object_hash($element)]['class'];
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

        return $ns . '\\' . $name;
    }

    public function &visitType(Type $type, $force = false)
    {
        $skip = in_array($type->getSchema()->getTargetNamespace(), $this->baseSchemas, true);

        if (!isset($this->classes[spl_object_hash($type)])) {
            $this->classes[spl_object_hash($type)]['skip'] = $skip;
            if ($alias = $this->getTypeAlias($type)) {
                $class = [];
                $class[$alias] = [];

                $this->classes[spl_object_hash($type)]['class'] = &$class;
                $this->classes[spl_object_hash($type)]['skip'] = true;

                return $class;
            }

            $className = $this->findPHPName($type);

            $class = [];
            $data = [];

            $class[$className] = &$data;

            $this->classes[spl_object_hash($type)]['class'] = &$class;

            $this->visitTypeBase($class, $data, $type, $type->getName());

            if ($type instanceof SimpleType || $this->isArrayType($type)) {
                $this->classes[spl_object_hash($type)]['skip'] = true;

                return $class;
            }

            if (!$force && ($this->isArrayType($type) || $this->isArrayNestedElement($type))) {
                $this->classes[spl_object_hash($type)]['skip'] = true;

                return $class;
            }
        } elseif ($force) {
            if (!($type instanceof SimpleType) && !$this->getTypeAlias($type)) {
                $this->classes[spl_object_hash($type)]['skip'] = $skip;
            }
        }

        return $this->classes[spl_object_hash($type)]['class'];
    }

    /**
     * @param string $parentName
     * @param string $parentClass
     *
     * @return array
     */
    private function &visitTypeAnonymous(Type $type, $parentName, $parentClass)
    {
        if (!isset($this->classes[spl_object_hash($type)])) {
            $class = [];
            $data = [];

            $name = $this->getNamingStrategy()->getAnonymousTypeName($type, $parentName);

            $class[$parentClass . '\\' . $name] = &$data;

            $this->visitTypeBase($class, $data, $type, $parentName);
            $this->classes[spl_object_hash($type)]['class'] = &$class;
            if ($type instanceof SimpleType) {
                $this->classes[spl_object_hash($type)]['skip'] = true;
            }
        }

        return $this->classes[spl_object_hash($type)]['class'];
    }

    private function visitComplexType(&$class, &$data, ComplexType $type)
    {
        $schema = $type->getSchema();
        if (!isset($data['properties'])) {
            $data['properties'] = [];
        }
        foreach ($this->flattElements($type) as $element) {
            $data['properties'][$this->getNamingStrategy()->getPropertyName($element)] = $this->visitElement($class, $schema, $element);
        }
    }

    protected function visitSimpleType(&$class, &$data, SimpleType $type, $name)
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
        if (!isset($data['properties'])) {
            $data['properties'] = [];
        }
        foreach ($this->flattAttributes($type) as $attr) {
            $data['properties'][$this->getNamingStrategy()->getPropertyName($attr)] = $this->visitAttribute($class, $schema, $attr);
        }
    }

    protected function &handleClassExtension(&$class, &$data, Type $type, $parentName)
    {
        $property = [];
        if ($alias = $this->getTypeAlias($type)) {
            $property = [];
            $property['expose'] = true;
            $property['xml_value'] = true;
            $property['access_type'] = 'public_method';
            $property['accessor']['getter'] = 'value';
            $property['accessor']['setter'] = 'value';
            $property['type'] = $alias;
            if (!$this->useCdata) {
                $property['xml_element']['cdata'] = $this->useCdata;
            }

            $data['properties']['__value'] = $property;

            return $property;
        } else {
            if (!$type->getName()) {
                $extension = $this->visitTypeAnonymous($type, $parentName, key($class));
            } else {
                $extension = $this->visitType($type, true);
            }

            if ($prop = $this->getPropertyInHierarchy($extension, '__value')) {
                $data['properties']['__value'] = $prop;

                return $property;
            } else {
                $data['extends'] = $extension;
            }
        }

        return $property;
    }

    protected function &visitAttribute(&$class, Schema $schema, AttributeItem $attribute)
    {
        $property = [];
        $property['expose'] = true;
        $property['access_type'] = 'public_method';
        $property['serialized_name'] = $attribute->getName();

        $inflector = InflectorFactory::create()->build();
        $property['accessor']['getter'] = 'get' . $inflector->classify($this->getNamingStrategy()->getPropertyName($attribute));
        $property['accessor']['setter'] = 'set' . $inflector->classify($this->getNamingStrategy()->getPropertyName($attribute));

        $property['xml_attribute'] = true;

        if ($alias = $this->getTypeAlias($attribute)) {
            $property['type'] = $alias;
        } elseif ($itemOfArray = $this->isArrayType($attribute->getType())) {
            if ($valueProp = $this->typeHasValue($itemOfArray, $class, 'xx')) {
                $property['type'] = "GoetasWebservices\Xsd\XsdToPhp\Jms\SimpleListOf<" . $valueProp . '>';
            } else {
                $property['type'] = "GoetasWebservices\Xsd\XsdToPhp\Jms\SimpleListOf<" . $this->findPHPName($itemOfArray) . '>';
            }
        } else {
            $property['type'] = $this->findPHPClass($class, $attribute);
        }

        return $property;
    }

    protected function typeHasValue(Type $type, $parentClass, $name)
    {
        $newType = null;
        do {
            if ($newType) {
                $type = $newType;
                $newType = null;
            }
            if (!($type instanceof SimpleType) && !($type instanceof ComplexTypeSimpleContent)) {
                return false;
            }

            if ($type instanceof ComplexTypeSimpleContent && $type->getAttributes()) {
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

            if ($prop = $this->getPropertyInHierarchy($parentClass, '__value')) {
                return $prop['type'];
            }
        } while (
            (method_exists($type, 'getRestriction') && $type->getRestriction() && ($newType = $type->getRestriction()->getBase()))
            ||
            (method_exists($type, 'getExtension') && $type->getExtension() && ($newType = $type->getExtension()->getBase()))
        );

        return false;
    }

    public function getPropertyInHierarchy($class, $prop)
    {
        $props = reset($class);

        if (
            (isset($props['properties']) && count($props['properties']) > 0 && !isset($props['properties'][$prop])) ||
            (isset($props['properties']) && count($props['properties']) > 1)) {
            return false;
        }

        if (isset($props['properties'][$prop])) {
            return $props['properties'][$prop];
        }

        if (!empty($props['extends'])) {
            return $this->getPropertyInHierarchy($props['extends'], $prop);
        }

        return false;
    }

    /**
     * @param Element|ElementSingle $element
     *
     * @return string|null
     */
    protected function getElementNamespace(Schema $schema, ElementItem $element)
    {
        if ($element->getSchema()->getTargetNamespace() &&
            ($schema->getElementsQualification() || ($element instanceof Element && $element->isQualified()) || !$element->isLocal())
        ) {
            return $element->getSchema()->getTargetNamespace();
        }

        return null;
    }

    /**
     * @param PHPClass $class
     * @param Element  $element
     * @param bool     $arrayize
     *
     * @return array
     */
    protected function &visitElement(&$class, Schema $schema, ElementItem $element, $arrayize = true)
    {
        $property = [];
        $property['expose'] = true;
        $property['access_type'] = 'public_method';
        $property['serialized_name'] = $element->getName();

        if (!$this->useCdata) {
            $property['xml_element']['cdata'] = $this->useCdata;
        }
        $elementNamespace = $this->getElementNamespace($schema, $element);
        if ($elementNamespace) {
            $property['xml_element']['namespace'] = $elementNamespace;
        }

        $inflector = InflectorFactory::create()->build();
        $property['accessor']['getter'] = 'get' . $inflector->classify($this->getNamingStrategy()->getPropertyName($element));
        $property['accessor']['setter'] = 'set' . $inflector->classify($this->getNamingStrategy()->getPropertyName($element));
        $t = $element->getType();

        if ($arrayize) {
            if ($itemOfArray = $this->isArrayNestedElement($t)) {
                if (!$t->getName()) {
                    if ($element instanceof ElementRef) {
                        $elRefClass = $this->visitElementDef($element->getSchema(), $element->getReferencedElement());
                        $itemClass = $this->findPHPClass($elRefClass, $element);
                    } else {
                        $itemClass = key($class);
                    }

                    $classType = $this->visitTypeAnonymous($t, $element->getName(), $itemClass);
                } else {
                    $classType = $this->visitType($t);
                }

                $visited = $this->visitElement($classType, $schema, $itemOfArray, false);

                $property['type'] = 'array<' . $visited['type'] . '>';
                $property['xml_list']['inline'] = false;
                $property['xml_list']['entry_name'] = $itemOfArray->getName();
                $property['xml_list']['skip_when_empty'] = ($element->getMin() === 0);

                $elementNamespace = $this->getElementNamespace($schema, $itemOfArray);
                if ($elementNamespace) {
                    $property['xml_list']['namespace'] = $elementNamespace;
                }

                return $property;
            } elseif ($itemOfArray = $this->isArrayType($t)) {
                if (!$t->getName()) {
                    if ($element instanceof ElementRef) {
                        $elRefClass = $this->visitElementDef($element->getSchema(), $element->getReferencedElement());
                        $itemClass = $this->findPHPClass($elRefClass, $element);
                    } else {
                        $itemClass = key($class);
                    }

                    $visitedType = $this->visitTypeAnonymous($itemOfArray, $element->getName(), $itemClass);
                } else {
                    $visitedType = $this->visitType($itemOfArray);
                }
                if ($prop = $this->typeHasValue($itemOfArray, $class, 'xx')) {
                    $property['type'] = "GoetasWebservices\Xsd\XsdToPhp\Jms\SimpleListOf<" . $prop . '>';
                } else {
                    $property['type'] = "GoetasWebservices\Xsd\XsdToPhp\Jms\SimpleListOf<" . key($visitedType) . '>';
                }

                $elementNamespace = $this->getElementNamespace($schema, $element);

                if ($elementNamespace != null) {
                    $property['xml_list']['namespace'] = $elementNamespace;
                }

                return $property;
            } elseif ($this->isArrayElement($element)) {
                $property['xml_list']['inline'] = true;
                $property['xml_list']['entry_name'] = $element->getName();

                $elementNamespace = $this->getElementNamespace($schema, $element);
                if ($elementNamespace != null) {
                    $property['xml_list']['namespace'] = $elementNamespace;
                }

                $property['type'] = 'array<' . $this->findPHPElementClassName($class, $element) . '>';

                return $property;
            }
        }

        $property['type'] = $this->findPHPElementClassName($class, $element);

        return $property;
    }

    protected function findPHPClass(&$class, Item $node)
    {
        $type = $node->getType();

        if ($alias = $this->getTypeAlias($type)) {
            return $alias;
        }

        if ($node instanceof ElementRef) {
            $elementRef = $this->visitElementDef($node->getSchema(), $node->getReferencedElement());

            return key($elementRef);
        }

        if ($valueProp = $this->typeHasValue($type, $class, $node->getName())) {
            return $valueProp;
        }
        if (!$node->getType()->getName()) {
            $visited = $this->visitTypeAnonymous($node->getType(), $node->getName(), key($class));
        } else {
            $visited = $this->visitType($node->getType());
        }

        return key($visited);
    }

    private function findPHPElementClassName(&$class, ElementItem $element): string
    {
        if ($element instanceof ElementRef) {
            $elRefClass = $this->visitElementDef($element->getSchema(), $element->getReferencedElement());
            $refType = $this->findPHPClass($elRefClass, $element->getReferencedElement());

            if ($this->typeHasValue($element->getReferencedElement()->getType(), $elRefClass, $element->getReferencedElement())) {
                return $refType;
            }
        }
        return $this->findPHPClass($class, $element);
    }
}
