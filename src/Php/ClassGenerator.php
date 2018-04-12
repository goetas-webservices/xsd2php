<?php
namespace GoetasWebservices\Xsd\XsdToPhp\Php;

use Doctrine\Common\Inflector\Inflector;
use GoetasWebservices\Xsd\XsdToPhp\Php\Structure\PHPClass;
use GoetasWebservices\Xsd\XsdToPhp\Php\Structure\PHPClassOf;
use GoetasWebservices\Xsd\XsdToPhp\Php\Structure\PHPProperty;
use Zend\Code\Generator;
use Zend\Code\Generator\DocBlock\Tag\ParamTag;
use Zend\Code\Generator\DocBlock\Tag\PropertyTag;
use Zend\Code\Generator\DocBlock\Tag\ReturnTag;
use Zend\Code\Generator\DocBlockGenerator;
use Zend\Code\Generator\MethodGenerator;
use Zend\Code\Generator\ParameterGenerator;
use Zend\Code\Generator\PropertyGenerator;

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

    private function handleValueMethod(Generator\ClassGenerator $generator, PHPProperty $prop, PHPClass $class, $all = true)
    {
        $type = $prop->getType();

        $docblock = new DocBlockGenerator('Construct');
        $docblock->setWordWrap(false);
        $paramTag = new ParamTag("value");
        $paramTag->setTypes(($type ? $type->getPhpType() : "mixed"));

        $docblock->setTag($paramTag);

        $param = new ParameterGenerator("value");
        if ($type && !$type->isNativeType()) {
            $param->setType($type->getPhpType());
        }
        $method = new MethodGenerator("__construct", [
            $param
        ]);
        $method->setDocBlock($docblock);
        $method->setBody("\$this->value(\$value);");

        $generator->addMethodFromGenerator($method);

        $docblock = new DocBlockGenerator('Gets or sets the inner value');
        $docblock->setWordWrap(false);
        $paramTag = new ParamTag("value");
        if ($type && $type instanceof PHPClassOf) {
            $paramTag->setTypes($type->getArg()->getType()->getPhpType() . "[]");
        } elseif ($type) {
            $paramTag->setTypes($prop->getType()->getPhpType());
        }
        $docblock->setTag($paramTag);

        $returnTag = new ReturnTag("mixed");

        if ($type && $type instanceof PHPClassOf) {
            $returnTag->setTypes($type->getArg()->getType()->getPhpType() . "[]");
        } elseif ($type) {
            $returnTag->setTypes($type->getPhpType());
        }
        $docblock->setTag($returnTag);

        $param = new ParameterGenerator("value");
        $param->setDefaultValue(null);

        if ($type && !$type->isNativeType()) {
            $param->setType($type->getPhpType());
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
        $docblock->setWordWrap(false);
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
        $docblock->setWordWrap(false);

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

        $parameter = new ParameterGenerator($prop->getName());

        if ($type && $type instanceof PHPClassOf) {
            $patramTag->setTypes($type->getArg()
                    ->getType()->getPhpType() . "[]");
            $parameter->setType("array");

            if ($p = $type->getArg()->getType()->isSimpleType()
            ) {
                if (($t = $p->getType())) {
                    $patramTag->setTypes($t->getPhpType());
                }
            }
        } elseif ($type) {
            if ($type->isNativeType()) {
                $patramTag->setTypes($type->getPhpType());
            } elseif ($p = $type->isSimpleType()) {
                if (($t = $p->getType()) && !$t->isNativeType()) {
                    $patramTag->setTypes($t->getPhpType());
                    $parameter->setType($t->getPhpType());
                } elseif ($t && !$t->isNativeType()) {
                    $patramTag->setTypes($t->getPhpType());
                    $parameter->setType($t->getPhpType());
                } elseif ($t) {
                    $patramTag->setTypes($t->getPhpType());
                }
            } else {
                $patramTag->setTypes($type->getPhpType());
                $parameter->setType($type->getPhpType());
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

        if ($prop->getType() instanceof PHPClassOf) {
            $docblock = new DocBlockGenerator();
            $docblock->setWordWrap(false);
            $docblock->setShortDescription("isset " . $prop->getName());
            if ($prop->getDoc()) {
                $docblock->setLongDescription($prop->getDoc());
            }

            $patramTag = new ParamTag("index", "int|string");
            $docblock->setTag($patramTag);

            $docblock->setTag(new ReturnTag("bool"));

            $paramIndex = new ParameterGenerator("index");

            $method = new MethodGenerator("isset" . Inflector::classify($prop->getName()), [$paramIndex]);
            $method->setDocBlock($docblock);
            $method->setBody("return isset(\$this->" . $prop->getName() . "[\$index]);");
            $generator->addMethodFromGenerator($method);

            $docblock = new DocBlockGenerator();
            $docblock->setWordWrap(false);
            $docblock->setShortDescription("unset " . $prop->getName());
            if ($prop->getDoc()) {
                $docblock->setLongDescription($prop->getDoc());
            }

            $patramTag = new ParamTag("index", "int|string");
            $docblock->setTag($patramTag);
            $paramIndex = new ParameterGenerator("index");

            $docblock->setTag(new ReturnTag("void"));


            $method = new MethodGenerator("unset" . Inflector::classify($prop->getName()), [$paramIndex]);
            $method->setDocBlock($docblock);
            $method->setBody("unset(\$this->" . $prop->getName() . "[\$index]);");
            $generator->addMethodFromGenerator($method);
        }
        // ////

        $docblock = new DocBlockGenerator();
        $docblock->setWordWrap(false);

        $docblock->setShortDescription("Gets as " . $prop->getName());

        if ($prop->getDoc()) {
            $docblock->setLongDescription($prop->getDoc());
        }

        $tag = new ReturnTag("mixed");
        $type = $prop->getType();
        if ($type && $type instanceof PHPClassOf) {
            $tt = $type->getArg()->getType();
            $tag->setTypes($tt->getPhpType() . "[]");
            if ($p = $tt->isSimpleType()) {
                if (($t = $p->getType())) {
                    $tag->setTypes($t->getPhpType() . "[]");
                }
            }
        } elseif ($type) {

            if ($p = $type->isSimpleType()) {
                if ($t = $p->getType()) {
                    $tag->setTypes($t->getPhpType());
                }
            } else {
                $tag->setTypes($type->getPhpType());
            }
        }

        $docblock->setTag($tag);

        $method = new MethodGenerator("get" . Inflector::classify($prop->getName()));
        $method->setDocBlock($docblock);
        $method->setBody("return \$this->" . $prop->getName() . ";");

        $generator->addMethodFromGenerator($method);
    }

    private function handleAdder(Generator\ClassGenerator $generator, PHPProperty $prop, PHPClass $class)
    {
        $type = $prop->getType();
        $propName = $type->getArg()->getName();

        $docblock = new DocBlockGenerator();
        $docblock->setWordWrap(false);
        $docblock->setShortDescription("Adds as $propName");

        if ($prop->getDoc()) {
            $docblock->setLongDescription($prop->getDoc());
        }

        $return = new ReturnTag();
        $return->setTypes("self");
        $docblock->setTag($return);

        $patramTag = new ParamTag($propName, $type->getArg()->getType()->getPhpType());
        $docblock->setTag($patramTag);

        $method = new MethodGenerator("addTo" . Inflector::classify($prop->getName()));

        $parameter = new ParameterGenerator($propName);
        $tt = $type->getArg()->getType();

        if (!$tt->isNativeType()) {

            if ($p = $tt->isSimpleType()) {
                if (($t = $p->getType())) {
                    $patramTag->setTypes($t->getPhpType());

                    if (!$t->isNativeType()) {
                        $parameter->setType($t->getPhpType());
                    }
                }
            } elseif (!$tt->isNativeType()) {
                $parameter->setType($tt->getPhpType());
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

        if ($prop->getType() && (!$prop->getType()->getNamespace() && $prop->getType()->getName() == "array")) {
            // $generatedProp->setDefaultValue(array(), PropertyValueGenerator::TYPE_AUTO, PropertyValueGenerator::OUTPUT_SINGLE_LINE);
        }

        $docBlock = new DocBlockGenerator();
        $docBlock->setWordWrap(false);
        $generatedProp->setDocBlock($docBlock);

        if ($prop->getDoc()) {
            $docBlock->setLongDescription($prop->getDoc());
        }
        $tag = new PropertyTag($prop->getName(), 'mixed');

        $type = $prop->getType();

        if ($type && $type instanceof PHPClassOf) {
            $tt = $type->getArg()->getType();
            $tag->setTypes($tt->getPhpType() . "[]");
            if ($p = $tt->isSimpleType()) {
                if (($t = $p->getType())) {
                    $tag->setTypes($t->getPhpType() . "[]");
                }
            }
            $generatedProp->setDefaultValue($type->getArg()->getDefault());
        } elseif ($type) {

            if ($type->isNativeType()) {
                $tag->setTypes($type->getPhpType());
            } elseif (($p = $type->isSimpleType()) && ($t = $p->getType())) {
                $tag->setTypes($t->getPhpType());
            } else {
                $tag->setTypes($prop->getType()->getPhpType());
            }
        }
        $docBlock->setTag($tag);
    }

    public function generate(PHPClass $type)
    {
        $class = new \Zend\Code\Generator\ClassGenerator();
        $docblock = new DocBlockGenerator("Class representing " . $type->getName());
        $docblock->setWordWrap(false);
        if ($type->getDoc()) {
            $docblock->setLongDescription($type->getDoc());
        }
        $class->setNamespaceName($type->getNamespace() ?: NULL);
        $class->setName($type->getName());
        $class->setDocblock($docblock);

        if ($extends = $type->getExtends()) {

            if ($p = $extends->isSimpleType()) {
                $this->handleProperty($class, $p);
                $this->handleValueMethod($class, $p, $extends);
            } else {

                //zend code ^3.0.3 wants FQCNs and resolves the short name based on provided use statements
                //https://github.com/zendframework/zend-code/commit/4a6f4ab33480923ac6553ded903c97de168f37fe
                //TypeGenerator is new to v3
                if(class_exists('\Zend\Code\Generator\TypeGenerator')) {
                    $class->setExtendedClass($extends->getFullName());
                } else {
                    //v2 simply used the string that was passed directly
                    $class->setExtendedClass($extends->getName());
                }

                if ($extends->getNamespace() != $type->getNamespace()) {
                    if ($extends->getName() == $type->getName()) {
                        $class->addUse($type->getExtends()->getFullName(), $extends->getName() . "Base");
                        $class->setExtendedClass($extends->getName() . "Base");
                    } else {
                        $class->addUse($extends->getFullName());
                    }
                }
            }
        }

        if ($this->handleBody($class, $type)) {
            return $class;
        }
    }
}
