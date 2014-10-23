<?php
namespace Goetas\Xsd\XsdToPhp\Generator;

use DOMXPath;
use DOMElement;
use DOMDocument;
use goetas\xml\wsdl\Exception;
use Goetas\Xsd\XsdToPhp\Structure\PHPType;
use Goetas\Xsd\XsdToPhp\Structure\PHPTrait;
use Goetas\Xsd\XsdToPhp\Structure\PHPProperty;
use Goetas\Xsd\XsdToPhp\Structure\PHPClass;
use Doctrine\Common\Inflector\Inflector;
use Goetas\Xsd\XsdToPhp\Structure\PHPClassOf;
use Goetas\Xsd\XsdToPhp\Structure\PHPConstant;

use Zend\Code\Generator;
use Zend\Code\Generator\PropertyGenerator;
use Zend\Code\Generator\DocBlockGenerator;
use Zend\Code\Generator\DocBlock\Tag\ParamTag;
use Zend\Code\Generator\DocBlock\Tag\ReturnTag;
use Zend\Code\Generator\MethodGenerator;
use Zend\Code\Generator\ParameterGenerator;

class ClassGenerator
{

    private function handleChecks(PHPType $type)
    {
        $str = '';

        if (($type instanceof PHPClass) && ($type->getChecks('__value') || $type->hasProperty('__value'))) {
            $doc = 'Get a list of allowed values for this type.'.PHP_EOL;
            $doc .= '@return array';

            $str .= $this->writeDocBlock($doc);
            $str .= "protected function values()" . PHP_EOL;
            $str .= "{" . PHP_EOL;
            $str .= $this->indent('return static::$allowedValues;').PHP_EOL;
            $str .= "}" . PHP_EOL.PHP_EOL;



            $str .= "protected function _checkValue(\$value)" . PHP_EOL;
            $str .= "{" . PHP_EOL;

            $methodBody = '';

            if ($type->getExtends()) {
                $methodBody .= '$value = parent::_checkValue($value);' . PHP_EOL;
            }

            foreach ($type->getChecks('__value') as $checkType => $checks) {
                if ($checkType == "enumeration") {
                    $methodBody .= 'if (!in_array($value, $this->values())) {' . PHP_EOL;
                    $methodBody .= $this->indent("throw new \InvalidArgumentException('The restriction $checkType with ' . implode(', ', \$this->values()) . ' is not true');") . PHP_EOL;
                    $methodBody .= '}' . PHP_EOL;
                } elseif ($checkType == "pattern") {
                    foreach ($checks as $check) {
                        $methodBody .= 'if (!preg_match(' . var_export("/" . $check["value"] . "/", true) . ', $value)) {' . PHP_EOL;
                        $methodBody .= $this->indent("throw new \\InvalidArgumentException('The restriction $checkType with value \\'" . $check["value"] . "\\' is not true');") . PHP_EOL;
                        $methodBody .= '}' . PHP_EOL;
                    }
                } elseif ($checkType == "minLength") {
                    foreach ($checks as $check) {
                        $methodBody .= 'if (strlen($value) < ' . $check['value'] . ') {' . PHP_EOL;
                        $methodBody .= $this->indent("throw new \\InvalidArgumentException('The restriction $checkType with value \\'" . $check["value"] . "\\' is not true');") . PHP_EOL;
                        $methodBody .= '}' . PHP_EOL;
                    }
                } elseif ($checkType == "maxLength") {
                    foreach ($checks as $check) {
                        $methodBody .= 'if (strlen($value) > ' . $check['value'] . ') {' . PHP_EOL;
                        $methodBody .= $this->indent("throw new \\InvalidArgumentException('The restriction $checkType with value \\'" . $check["value"] . "\\' is not true');") . PHP_EOL;
                        $methodBody .= '}' . PHP_EOL;
                    }
                }
            }

            $methodBody .= 'return $value;';

            $str .= $this->indent($methodBody) . PHP_EOL;
            $str .= "}" . PHP_EOL;
            $str .= PHP_EOL;
        }

        return $str;
    }

    private function handleStaticCheckProperty(Generator\ClassGenerator $class, PHPType $type, array $checkValues)
    {
        $vs = array_map(function ($v) {
            return $v["value"];
        }, $checkValues);

        $generatedProp = new PropertyGenerator('allowedValues', $vs, PropertyGenerator::FLAG_PRIVATE);
        $class->addPropertyFromGenerator($generatedProp);

        $docBlock = new DocBlockGenerator();
        $tag = new ParamTag('allowedValues');
        $tag->setTypes(array('array'));
        $docBlock->setTag($tag);

    }

    private function handleBody(Generator\ClassGenerator $class, PHPType $type)
    {
        /*
        foreach ($type->getConstants() as $const) {
            $str .= $this->handleConstant($const) . PHP_EOL . PHP_EOL;
        }
        */
        foreach ($type->getChecks('__value') as $checkType => $checkValues) {
            if ($checkType=="enumeration") {
                $this->handleStaticCheckProperty($class, $type, $checkValues) . PHP_EOL . PHP_EOL;
            }
        }


        foreach ($type->getProperties() as $prop) {
            $this->handleProperty($class, $prop);
        }
        /*
        foreach ($type->getChecks('__value') as $checkType => $checkValues) {
            if ($checkType=="enumeration") {
                foreach ($checkValues as $enumeration) {
                    $str .= $this->handleStaticCheckMethods($type, $enumeration) . PHP_EOL . PHP_EOL;
                }
            }
        }


        $str .= $this->handleChecks($type);
        */

        foreach ($type->getProperties() as $prop) {
            $this->handleMethods($class, $prop, $type);
        }
    }

    private function isNativeType(PHPClass $class)
    {
        return ! $class->getNamespace() && in_array($class->getName(), [
            'string',
            'int',
            'float',
            'integer',
            'boolean',
            'array'
        ]);
    }

    private function hasTypeHint(PHPClass $class)
    {
        return $class->getNamespace() || in_array($class->getName(), [
            'array'
        ]);
    }

    private function getPhpType(PHPClass $class)
    {
        if (! $class->getNamespace()) {
            if ($this->isNativeType($class)) {
                return $class->getName();
            }
            return "\\" . $class->getName();
        }
        return "\\" . $class->getFullName();
    }

    private function addValueMethods(PHPProperty $prop, PHPType $class)
    {

        $type = $prop->getType();

        $doc = 'Gets or sets the inner value.' . PHP_EOL . PHP_EOL;

        if ($c = $this->getFirstLineComment($prop->getDoc())) {
            $doc .= $c . PHP_EOL . PHP_EOL;
        }
        if ($type && $type instanceof PHPClassOf) {
            $doc .= "@param \$value " . $this->getPhpType($type->getArg()->getType()) . "[]";
        } elseif ($type) {
            $doc .= "@param \$value " . $this->getPhpType($prop->getType());
        } else {
            $doc .= "@param \$value mixed";
        }
        $doc .= PHP_EOL;

        if ($type && $type instanceof PHPClassOf) {
            $doc .= "@return " . $this->getPhpType($type->getArg()->getType()) . "[]";
        } elseif ($type) {
            $doc .= "@return " . $this->getPhpType($type);
        } else {
            $doc .= "@return mixed";
        }

        $str = $this->writeDocBlock($doc);

        $typedeclaration = '';
        if ($type && $this->hasTypeHint($type)) {
            $typedeclaration = $this->getPhpType($type) . " ";
        }

        $str .= "public function value($typedeclaration\$value = null)" . PHP_EOL;
        $str .= "{" . PHP_EOL;

        $methodBody = "if (\$value !== null) {" . PHP_EOL;
        $methodBody .= $this->indent("\$this->" . $prop->getName() . " = \$this->_checkValue(\$value);") . PHP_EOL;
        $methodBody .= "}" . PHP_EOL;

        $methodBody .= "return \$this->" . $prop->getName() . ";" . PHP_EOL;

        $str .= $this->indent($methodBody) . PHP_EOL;

        $str .= "}" . PHP_EOL;

        $str .= PHP_EOL;

        $str .= "public function __toString()" . PHP_EOL;
        $str .= "{" . PHP_EOL;
        $methodBody = "return strval(\$this->" . $prop->getName() . ");";
        $str .= $this->indent($methodBody) . PHP_EOL;

        $str .= "}" . PHP_EOL;

        $str .= PHP_EOL;

        $doc = "";
        $doc .= "@param \$value " . ($type ? $this->getPhpType($type) : "mixed");

        $str .= $this->writeDocBlock($doc);

        $str .= "protected function __construct(\$value)" . PHP_EOL;
        $str .= "{" . PHP_EOL;
        $methodBody = "\$this->value(\$value);";
        $str .= $this->indent($methodBody) . PHP_EOL;

        $str .= "}" . PHP_EOL;
        $str .= PHP_EOL;

        $doc = "";
        $doc .= "@param \$value " . ($type ? $this->getPhpType($type) : "mixed").PHP_EOL;
        $doc .= "@return " . $class->getName();

        $str .= $this->writeDocBlock($doc);

        $str .= "public static function create(\$value)" . PHP_EOL;
        $str .= "{" . PHP_EOL;
        $methodBody = "return new static(\$value);";
        $str .= $this->indent($methodBody) . PHP_EOL;

        $str .= "}" . PHP_EOL;

        return $str;
    }



    private function handleSetter(Generator\ClassGenerator $generator, PHPProperty $prop, PHPType $class)
    {

        $docblock = new DocBlockGenerator();
        if ($c = $this->getFirstLineComment($prop->getDoc())) {
            $docblock->setLongDescription($c);
        }
        if ($prop->getDoc()) {
            $docblock->setLongDescription($prop->getDoc());
        }

        $return = new ReturnTag();
        $return->setTypes("\\".$class->getFullName());
        $docblock->setTag($return);

        $patramTag = new ParamTag($prop->getName());
        $docblock->setTag($patramTag);

        $type = $prop->getType();

        $method = new MethodGenerator("set" . Inflector::classify($prop->getName()));

        $parameter = new ParameterGenerator($prop->getName());
        if ($type && $type instanceof PHPClassOf) {
            $patramTag->setTypes($this->getPhpType($type->getArg()->getType()) . "[]");
            $parameter->setType("array");
        } elseif ($type) {
            $patramTag->setTypes($this->getPhpType($prop->getType()));
            $parameter->setType($this->getPhpType($prop->getType()));
        } else {
            $patramTag->setTypes("mixed");
        }

        $methodBody = '';
        if ($type && $type instanceof PHPClassOf) {
            $methodBody .= "foreach ($" . $prop->getName() . " as \$item) {" . PHP_EOL;
            $methodBody .= $this->indent("if (!(\$item instanceof " . $this->getPhpType($type->getArg()->getType()) . ") ) {") . PHP_EOL;
            $methodBody .= $this->indent("throw new \\InvalidArgumentException('Argument 1 passed to ' . __METHOD__ . ' be an array of " . $this->getPhpType($type->getArg()->getType()) . "');", 2) .
            PHP_EOL;
            $methodBody .= $this->indent("}") . PHP_EOL;
            $methodBody .= "}" . PHP_EOL;
        }

        $methodBody .= "\$this->" . $prop->getName() . " = \$" . $prop->getName() . ";" . PHP_EOL;
        $methodBody .= "return \$this;";
        $method->setBody($methodBody);
        $method->setDocBlock($docblock);
        $method->setParameter($parameter);

        $generator->addMethodFromGenerator($method);

    }
    private function handleGetter(Generator\ClassGenerator $generator, PHPProperty $prop, PHPType $class)
    {
        $docblock = new DocBlockGenerator();
        if ($c = $this->getFirstLineComment($prop->getDoc())) {
             $docblock->setLongDescription($c);
        }
        if ($prop->getDoc()) {
            $docblock->setLongDescription($prop->getDoc());
        }

        $tag = new ReturnTag();
        $type = $prop->getType();
        if ($type && $type instanceof PHPClassOf) {
            $tag->setTypes(array($this->getPhpType($type->getArg()->getType())));
        } elseif ($type) {
            $tag->setTypes(array($this->getPhpType($type)));
        } else {
            $tag->setTypes(array('mixed'));
        }
        $docblock->setTag($tag);

        $method = new MethodGenerator("get".Inflector::classify($prop->getName()));
        $method->setDocBlock($docblock);
        $method->setBody("return \$this->" . $prop->getName() . ";");

        $generator->addMethodFromGenerator($method);
    }
    private function handleValueMethods(PHPProperty $prop, PHPType $class){
        $type = $prop->getType();
        $doc = '';
        $str = '';
        if ($c = $this->getFirstLineComment($prop->getDoc())) {
            $doc .= $c . PHP_EOL . PHP_EOL;
        }

        if ($type) {
            $doc .= "@return " . ($type->getPropertyInHierarchy('__value')->getType()?$this->getPhpType($type->getPropertyInHierarchy('__value')->getType()):"mixed");
        } else {
            $doc .= "@return mixed";
        }


        $str .= $this->writeDocBlock($doc);
        $str .= "public function extract" . Inflector::classify($prop->getName()) . "()" . PHP_EOL;
        $str .= "{" . PHP_EOL;
        $methodBody = "return \$this->" . $prop->getName() . " ? \$this->" . $prop->getName() . "->value() : null;";
        $str .= $this->indent($methodBody) . PHP_EOL;

        $str .= "}" . PHP_EOL;
        return $str;
    }

    private function handleAdder(PHPProperty $prop, PHPType $class)
    {
        $type = $prop->getType();

        $doc = '';
        $str = '';
        if ($c = $type->getArg()->getDoc()) {
            $doc .= $c . PHP_EOL . PHP_EOL;
        }

        $propName = $type->getArg()->getName() ?  : $prop->getName();

        $doc .= "@param $" . $propName . " " . $this->getPhpType($type->getArg()->getType());
        if ($type->getArg()->getType()->getDoc()) {
            $doc .= " " . $this->getFirstLineComment($type->getArg()->getType()->getDoc());
        }

        if ($doc) {
            $str .= $this->writeDocBlock($doc);
        }

        $typedeclaration = '';
        if ($this->hasTypeHint($type->getArg()->getType())) {
            $typedeclaration = $this->getPhpType($type->getArg()->getType()) . " ";
        }

        $r = array(
            'arrayof',
            'setof',
            'listof',
        );

        $str .= "public function add" . str_ireplace($r, "", Inflector::classify($prop->getName())) . "($typedeclaration\$" . $propName . ")" . PHP_EOL;
        $str .= "{" . PHP_EOL;
        $methodBody = "\$this->" . $prop->getName() . "[] = \$" . $propName . ";" . PHP_EOL;
        $methodBody .= "return \$this;";
        $str .= $this->indent($methodBody) . PHP_EOL;

        $str .= "}" . PHP_EOL;

        return $str;
    }

    private function handleMethods(Generator\ClassGenerator $generator, PHPProperty $prop, PHPType $class)
    {

        if ($prop->getName() == "__value") {
            $this->addValueMethods($prop, $class);
        } else {
            $type = $prop->getType();
            if ($prop->getType() && $prop->getType()->hasPropertyInHierarchy('__value')) {
                $this->handleValueMethods($prop, $class);
            }
            if ($type && $type instanceof PHPClassOf) {
                //$str .= $this->handleAdder($prop, $class);
            }

            $this->handleGetter($generator, $prop, $class);
            $this->handleSetter($generator, $prop, $class);

        }
    }

    private function getFirstLineComment($str)
    {
        $str = trim($str);
        if ($str && $str[0] !== '@' && ($p = strpos($str, '.')) !== false && $p < 91) {
            return substr($str, 0, $p + 1);
        }
        if ($str && $str[0] !== '@' && ($p = strpos($str, "\n")) !== false && $p < 91) {
            return substr($str, 0, $p + 1);
        }
        return '';
    }

    private function handleStaticCheckMethods(PHPType $type, array $enumeration)
    {
        $doc = "Create a new ".$type->getName()." instance with " . var_export($enumeration['value'], 1) . " as value.";
        $doc .= PHP_EOL;

        if ($enumeration['doc']){
            $doc .= $enumeration['doc'].PHP_EOL;
        }

        $doc .= "@return " . $type->getName();

        $str = '';
        if ($doc) {
            $str .= $this->writeDocBlock($doc);
        }
        $str .= "public static function " . strtolower(Inflector::tableize($enumeration['value'])) . "()" . PHP_EOL;
        $str .= "{" . PHP_EOL;
        $str .= $this->indent("return new static(" . var_export($enumeration['value'], 1) . ");") . PHP_EOL;

        $str .= "}";
        return $str;
    }

    private function handleConstant(PHPConstant $const)
    {
        $doc = '';

        if ($const->getDoc()) {
            $doc .= $const->getDoc() . PHP_EOL . PHP_EOL;
        }
        $str = "";
        if ($doc) {
            $str .= $this->writeDocBlock($doc);
        }

        $str .= "const " . $const->getName() . " = ";
        $str .= var_export($const->getValue(), true);
        $str .= ";";
        return $str;
    }

    private function handleProperty(Generator\ClassGenerator $class, PHPProperty $prop)
    {
        $generatedProp = new PropertyGenerator($prop->getName());
        $class->addPropertyFromGenerator($generatedProp);

        if ($prop->getType() && (! $prop->getType()->getNamespace() && $prop->getType()->getName() == "array")) {
            $generatedProp->setDefaultValue(array());
        }

        $docBlock = new DocBlockGenerator();
        $generatedProp->setDocBlock($docBlock);

        if ($prop->getDoc()) {
            $docBlock->setLongDescription($prop->getDoc());
        }
        $tag = new ParamTag($prop->getName());
        if ($prop->getType()) {
            $tag->setTypes(array($this->getPhpType($prop->getType())));
        } else {
            $tag->setTypes(array('mixed'));
        }
        $docBlock->setTag($tag);

    }

    public function generate(PHPType $type)
    {

        $class      = new Generator\ClassGenerator();
        $docblock = Generator\DocBlockGenerator::fromArray(array(
            'shortDescription' => 'Sample generated class',
            'longDescription'  => $type->getDoc(),
        ));
        $class->setNamespaceName($type->getNamespace());
        $class->setName($type->getName());
        $class->setDocblock($docblock);

        if ($extends = $type->getExtends()) {
            $class->setExtendedClass($extends->getName());

            if ($extends->getNamespace() != $type->getNamespace()) {
                if ($extends->getName() == $type->getName()) {
                    $class->addUse($type->getExtends()->getFullName(), $extends->getName()."Base");
                    $class->setExtendedClass($extends->getName()."Base");
                }else{
                    $class->addUse($extends->getFullName());
                }
            }
        }

        $this->handleBody($class, $type);

        return $class->generate();

    }

    private function writeDocBlock($str)
    {
        $content = '/**' . PHP_EOL;

        $lines = array();

        foreach (explode("\n", trim($str)) as $line) {
            if (! $line) {
                $lines[] = $line;
                continue;
            }
            if ($line[0] === '@') {
                $lines[] = $line;
            } else {
                foreach (explode("\n", wordwrap($line, 90)) as $l) {
                    $lines[] = $l;
                }
            }
        }

        foreach ($lines as $row) {
            $content .= ' * ' . $row . PHP_EOL;
        }
        $content .= ' */' . PHP_EOL;
        return $content;
    }

    private function indent($str, $times = 1)
    {
        $tabs = str_repeat("    ", $times);

        return $tabs . str_replace("\n", "\n" . $tabs, $str);
    }
}
