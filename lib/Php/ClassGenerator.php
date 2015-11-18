<?php
namespace Goetas\Xsd\XsdToPhp\Php;

use Doctrine\Common\Inflector\Inflector;
use Goetas\Xsd\XsdToPhp\Php\Structure\PHPClass;
use Goetas\Xsd\XsdToPhp\Php\Structure\PHPProperty;
use Goetas\Xsd\XsdToPhp\Php\Structure\PHPClassOf;
use Zend\Code\Generator;
use Zend\Code\Generator\PropertyGenerator;
use Zend\Code\Generator\DocBlockGenerator;
use Zend\Code\Generator\DocBlock\Tag\ParamTag;
use Zend\Code\Generator\DocBlock\Tag\ReturnTag;
use Zend\Code\Generator\MethodGenerator;
use Zend\Code\Generator\ParameterGenerator;
use Zend\Code\Generator\DocBlock\Tag\PropertyTag;

class ClassGenerator
{

    private function handleBody(Generator\ClassGenerator $class, PHPClass $type)
    {
        foreach ($type->getProperties() as $prop) {
            if ($prop->getName() !== '__value') {
                $this->handleProperty($class, $prop);
            }
        }
        foreach ($type->getProperties() as $prop) {
            if ($prop->getName() !== '__value') {
                $this->handleMethod($class, $prop, $type);
            }
        }

        if (count($type->getProperties()) === 1 && $type->hasProperty('__value')) {
            return false;
        }

        return true;
    }

    private function isNativeType(PHPClass $class)
    {
        return ! $class->getNamespace() && in_array($class->getName(), [
            'string',
            'int',
            'float',
            'integer',
            'boolean',
            'array',
            'mixed',
            'callable'
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

    private function handleValueMethod(Generator\ClassGenerator $generator, PHPProperty $prop, PHPClass $class, $all = true)
    {
        $type = $prop->getType();

        $docblock = new DocBlockGenerator('Construct');
        $paramTag = new ParamTag("value", "mixed");
        $paramTag->setTypes(($type ? $this->getPhpType($type) : "mixed"));

        $docblock->setTag($paramTag);

        $param = new ParameterGenerator("value");
        if ($type && ! $this->isNativeType($type)) {
            $param->setType($this->getPhpType($type));
        }
        $method = new MethodGenerator("__construct", [
            $param
        ]);
        $method->setDocBlock($docblock);
        $method->setBody("\$this->value(\$value);");

        $generator->addMethodFromGenerator($method);

        $docblock = new DocBlockGenerator('Gets or sets the inner value');
        $paramTag = new ParamTag("value", "mixed");
        if ($type && $type instanceof PHPClassOf) {
            $paramTag->setTypes($this->getPhpType($type->getArg()
                ->getType()) . "[]");
        } elseif ($type) {
            $paramTag->setTypes($this->getPhpType($prop->getType()));
        }
        $docblock->setTag($paramTag);

        $returnTag = new ReturnTag("mixed");

        if ($type && $type instanceof PHPClassOf) {
            $returnTag->setTypes($this->getPhpType($type->getArg()
                ->getType()) . "[]");
        } elseif ($type) {
            $returnTag->setTypes($this->getPhpType($type));
        }
        $docblock->setTag($returnTag);

        $param = new ParameterGenerator("value");
        $param->setDefaultValue(null);

        if ($type && ! $this->isNativeType($type)) {
            $param->setType($this->getPhpType($type));
        }
        $method = new MethodGenerator("value", []);
        $method->setDocBlock($docblock);

        $methodBody = "if (\$args = func_get_args()) {" . PHP_EOL;
        $methodBody .= "    \$this->" . $prop->getName() . " = \$args[0];" . PHP_EOL;
        $methodBody .= "}" . PHP_EOL;
        $methodBody .= "return \$this->" . $prop->getName() . ";" . PHP_EOL;
        $method->setBody($methodBody);

        $generator->addMethodFromGenerator($method);

        $docblock = new DocBlockGenerator('Gets a string value');
        $docblock->setTag(new ReturnTag("string"));
        $method = new MethodGenerator("__toString");
        $method->setDocBlock($docblock);
        $method->setBody("return strval(\$this->" . $prop->getName() . ");");
        $generator->addMethodFromGenerator($method);
    }

    private function handleSetter(Generator\ClassGenerator $generator, PHPProperty $prop, PHPClass $class)
    {
        $methodBody = '';
        $docblock = new DocBlockGenerator();

        $docblock->setShortDescription("Sets a new " . $prop->getName());

        if ($prop->getDoc()) {
            $docblock->setLongDescription($prop->getDoc());
        }

        $patramTag = new ParamTag($prop->getName());
        $docblock->setTag($patramTag);

        $return = new ReturnTag("self");
        $docblock->setTag($return);

        $type = $prop->getType();

        $method = new MethodGenerator("set" . Inflector::classify($prop->getName()));

        $parameter = new ParameterGenerator($prop->getName(), "mixed");

        if ($type && $type instanceof PHPClassOf) {
            $patramTag->setTypes($this->getPhpType($type->getArg()
                ->getType()) . "[]");
            $parameter->setType("array");

            if ($p = $this->isOneType($type->getArg()
                ->getType())) {
                if (($t = $p->getType())) {
                    $patramTag->setTypes($this->getPhpType($t));
                }
            }
        } elseif ($type) {
            if ($this->isNativeType($type)) {
                $patramTag->setTypes($this->getPhpType($type));
            } elseif ($p = $this->isOneType($type)) {
                if (($t = $p->getType()) && ! $this->isNativeType($t)) {
                    $patramTag->setTypes($this->getPhpType($t));
                    $parameter->setType($this->getPhpType($t));
                } elseif ($t && ! $this->isNativeType($t)) {
                    $patramTag->setTypes($this->getPhpType($t));
                    $parameter->setType($this->getPhpType($t));
                } elseif ($t) {
                    $patramTag->setTypes($this->getPhpType($t));
                }
            } else {
                $patramTag->setTypes($this->getPhpType($type));
                $parameter->setType($this->getPhpType($type));
            }
        }

        $methodBody .= "\$this->" . $prop->getName() . " = \$" . $prop->getName() . ";" . PHP_EOL;
        $methodBody .= "return \$this;";
        $method->setBody($methodBody);
        $method->setDocBlock($docblock);
        $method->setParameter($parameter);

        $generator->addMethodFromGenerator($method);
    }

    private function handleGetter(Generator\ClassGenerator $generator, PHPProperty $prop, PHPClass $class)
    {

        if ($prop->getType() instanceof PHPClassOf){
            $docblock = new DocBlockGenerator();
            $docblock->setShortDescription("isset " . $prop->getName());
            if ($prop->getDoc()) {
                $docblock->setLongDescription($prop->getDoc());
            }

            $patramTag = new ParamTag("index", "scalar");
            $docblock->setTag($patramTag);

            $docblock->setTag(new ReturnTag("boolean"));

            $paramIndex = new ParameterGenerator("index", "mixed");

            $method = new MethodGenerator("isset" . Inflector::classify($prop->getName()), [$paramIndex]);
            $method->setDocBlock($docblock);
            $method->setBody("return isset(\$this->" . $prop->getName() . "[\$index]);");
            $generator->addMethodFromGenerator($method);

            $docblock = new DocBlockGenerator();
            $docblock->setShortDescription("unset " . $prop->getName());
            if ($prop->getDoc()) {
                $docblock->setLongDescription($prop->getDoc());
            }

            $patramTag = new ParamTag("index", "scalar");
            $docblock->setTag($patramTag);
            $paramIndex = new ParameterGenerator("index", "mixed");

            $docblock->setTag(new ReturnTag("void"));



            $method = new MethodGenerator("unset" . Inflector::classify($prop->getName()), [$paramIndex]);
            $method->setDocBlock($docblock);
            $method->setBody("unset(\$this->" . $prop->getName() . "[\$index]);");
            $generator->addMethodFromGenerator($method);
        }
        // ////

        $docblock = new DocBlockGenerator();

        $docblock->setShortDescription("Gets as " . $prop->getName());

        if ($prop->getDoc()) {
            $docblock->setLongDescription($prop->getDoc());
        }

        $tag = new ReturnTag("mixed");
        $type = $prop->getType();
        if ($type && $type instanceof PHPClassOf) {
            $tt = $type->getArg()->getType();
            $tag->setTypes($this->getPhpType($tt) . "[]");
            if ($p = $this->isOneType($tt)) {
                if (($t = $p->getType())) {
                    $tag->setTypes($this->getPhpType($t) . "[]");
                }
            }
        } elseif ($type) {

            if ($p = $this->isOneType($type)) {
                if ($t = $p->getType()) {
                    $tag->setTypes($this->getPhpType($t));
                }
            } else {
                $tag->setTypes($this->getPhpType($type));
            }
        }

        $docblock->setTag($tag);

        $method = new MethodGenerator("get" . Inflector::classify($prop->getName()));
        $method->setDocBlock($docblock);
        $method->setBody("return \$this->" . $prop->getName() . ";");

        $generator->addMethodFromGenerator($method);
    }

    private function isOneType(PHPClass $type, $onlyParent = false)
    {
        if ($onlyParent) {
            $e = $type->getExtends();
            if ($e) {
                if ($e->hasProperty('__value')) {
                    return $e->getProperty('__value');
                }
            }
        } else {
            if ($type->hasPropertyInHierarchy('__value') && count($type->getPropertiesInHierarchy()) === 1) {
                return $type->getPropertyInHierarchy("__value");
            }
        }
    }

    private function handleAdder(Generator\ClassGenerator $generator, PHPProperty $prop, PHPClass $class)
    {
        $type = $prop->getType();
        $propName = $type->getArg()->getName();

        $docblock = new DocBlockGenerator();
        $docblock->setShortDescription("Adds as $propName");

        if ($prop->getDoc()) {
            $docblock->setLongDescription($prop->getDoc());
        }

        $return = new ReturnTag();
        $return->setTypes("self");
        $docblock->setTag($return);

        $patramTag = new ParamTag($propName, $this->getPhpType($type->getArg()
            ->getType()));
        $docblock->setTag($patramTag);

        $method = new MethodGenerator("addTo".Inflector::classify($prop->getName()));

        $parameter = new ParameterGenerator($propName);
        $tt = $type->getArg()->getType();

        if (! $this->isNativeType($tt)) {

            if ($p = $this->isOneType($tt)) {
                if (($t = $p->getType())) {
                    $patramTag->setTypes($this->getPhpType($t));

                    if (! $this->isNativeType($t)) {
                        $parameter->setType($this->getPhpType($t));
                    }
                }
            } elseif (! $this->isNativeType($tt)) {
                $parameter->setType($this->getPhpType($tt));
            }
        }

        $methodBody = "\$this->" . $prop->getName() . "[] = \$" . $propName . ";" . PHP_EOL;
        $methodBody .= "return \$this;";
        $method->setBody($methodBody);
        $method->setDocBlock($docblock);
        $method->setParameter($parameter);

        $generator->addMethodFromGenerator($method);
    }

    private function handleMethod(Generator\ClassGenerator $generator, PHPProperty $prop, PHPClass $class)
    {
        if ($prop->getType() instanceof PHPClassOf) {
            $this->handleAdder($generator, $prop, $class);
        }

        $this->handleGetter($generator, $prop, $class);
        $this->handleSetter($generator, $prop, $class);
    }

    private function handleProperty(Generator\ClassGenerator $class, PHPProperty $prop)
    {
        $generatedProp = new PropertyGenerator($prop->getName());
        $generatedProp->setVisibility(PropertyGenerator::VISIBILITY_PRIVATE);

        $class->addPropertyFromGenerator($generatedProp);

        if ($prop->getType() && (! $prop->getType()->getNamespace() && $prop->getType()->getName() == "array")) {
            // $generatedProp->setDefaultValue(array(), PropertyValueGenerator::TYPE_AUTO, PropertyValueGenerator::OUTPUT_SINGLE_LINE);
        }

        $docBlock = new DocBlockGenerator();
        $generatedProp->setDocBlock($docBlock);

        if ($prop->getDoc()) {
            $docBlock->setLongDescription($prop->getDoc());
        }
        $tag = new PropertyTag($prop->getName(), 'mixed');

        $type = $prop->getType();

        if ($type && $type instanceof PHPClassOf) {
            $tt = $type->getArg()->getType();
            $tag->setTypes($this->getPhpType($tt) . "[]");
            if ($p = $this->isOneType($tt)) {
                if (($t = $p->getType())) {
                    $tag->setTypes($this->getPhpType($t) . "[]");
                }
            }
        } elseif ($type) {

            if ($this->isNativeType($type)) {
                $tag->setTypes($this->getPhpType($type));
            } elseif (($p = $this->isOneType($type)) && ($t = $p->getType())) {
                $tag->setTypes($this->getPhpType($t));
            } else {
                $tag->setTypes($this->getPhpType($prop->getType()));
            }
        }
        $docBlock->setTag($tag);
    }

    public function generate(Generator\ClassGenerator $class, PHPClass $type)
    {
        $docblock = new DocBlockGenerator("Class representing " . $type->getName());
        if ($type->getDoc()) {
            $docblock->setLongDescription($type->getDoc());
        }
        $class->setNamespaceName($type->getNamespace() ?: NULL);
        $class->setName($type->getName());
        $class->setDocblock($docblock);

        if ($extends = $type->getExtends()) {

            if ($p = $this->isOneType($extends)) {
                $this->handleProperty($class, $p);
                $this->handleValueMethod($class, $p, $extends);
            } else {

                $class->setExtendedClass($extends->getName());

                if ($extends->getNamespace() != $type->getNamespace()) {
                    if ($extends->getName() == $type->getName()) {
                        $class->addUse($type->getExtends()
                            ->getFullName(), $extends->getName() . "Base");
                        $class->setExtendedClass($extends->getName() . "Base");
                    } else {
                        $class->addUse($extends->getFullName());
                    }
                }
            }
        }

        if ($this->handleBody($class, $type)) {
            return true;
        }
    }
}
