<?php

namespace GoetasWebservices\Xsd\XsdToPhp\Php;

use Doctrine\Inflector\InflectorFactory;
use GoetasWebservices\Xsd\XsdToPhp\Php\Structure\PHPClass;
use GoetasWebservices\Xsd\XsdToPhp\Php\Structure\PHPClassOf;
use GoetasWebservices\Xsd\XsdToPhp\Php\Structure\PHPProperty;
use Laminas\Code\Generator;
use Laminas\Code\Generator\DocBlock\Tag\ParamTag;
use Laminas\Code\Generator\DocBlock\Tag\ReturnTag;
use Laminas\Code\Generator\DocBlock\Tag\VarTag;
use Laminas\Code\Generator\DocBlockGenerator;
use Laminas\Code\Generator\MethodGenerator;
use Laminas\Code\Generator\ParameterGenerator;
use Laminas\Code\Generator\PropertyGenerator;
use Laminas\Code\Generator\PropertyValueGenerator;

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

        $docblock = new DocBlockGenerator();
        $docblock->setWordWrap(false);
        $paramTag = new ParamTag('value');
        $paramTag->setTypes(($type ? $type->getPhpType() : 'mixed'));

        $param = new ParameterGenerator('value');
        $param->setDefaultValue(null);
        if ($type) {
            $param->setType($this->getTypeAsPhpString($type));
        } else {
            $docblock->setTag($paramTag);
        }
        $method = new MethodGenerator('__construct', [
            $param,
        ]);
        if ($docblock->getTags()) {
            $method->setDocBlock($docblock);
        }
        $method->setBody('$this->value($value);');

        $generator->addMethodFromGenerator($method);

        $docblock = new DocBlockGenerator('Gets or sets the inner value');
        $docblock->setWordWrap(false);

        $parameter = new ParameterGenerator('value');
        $parameter->setVariadic(true);

        $paramTag = new ParamTag('value');
        $paramTag->setDescription('if provided, allows to set the inner value');
        if ($type && $type instanceof PHPClassOf) {
            $paramTag->setTypes($type->getArg()->getType()->getPhpType() . '[]');
            $parameter->setType('?array');
        } elseif ($type) {
            $paramTag->setTypes($prop->getType()->getPhpType());
            $parameter->setType($this->getTypeAsPhpString($prop->getType()));
        } else {
            $docblock->setTag($paramTag);
        }


        $method = new MethodGenerator('value', [$parameter]);
        $returnTag = new ReturnTag('mixed');

        if ($type && $type instanceof PHPClassOf) {
            $returnTag->setTypes($type->getArg()->getType()->getPhpType() . '[]');
            $method->setReturnType('?array');
            $docblock->setTag($returnTag);
        } elseif ($type) {
            $method->setReturnType($this->getTypeAsPhpString($type));
        } else {
            $docblock->setTag($returnTag);
        }


        $param = new ParameterGenerator('value');
        $param->setDefaultValue(null);

        if ($type && !$type->isNativeType()) {
            $param->setType($type->getPhpType());
        }

        $method->setDocBlock($docblock);

        $methodBody = 'if ($value) {' . PHP_EOL;
        $methodBody .= '    $this->' . $prop->getName() . ' = $value[0];' . PHP_EOL;
        $methodBody .= '}' . PHP_EOL;
        $methodBody .= 'return $this->' . $prop->getName() . ';' . PHP_EOL;
        $method->setBody($methodBody);

        $generator->addMethodFromGenerator($method);

        $docblock = new DocBlockGenerator('Gets the inner value as string');
        $docblock->setWordWrap(false);
        $method = new MethodGenerator('__toString');
        $method->setReturnType('string');
        $method->setDocBlock($docblock);
        $method->setBody('return strval($this->' . $prop->getName() . ');');
        $generator->addMethodFromGenerator($method);
    }

    private function handleSetter(Generator\ClassGenerator $generator, PHPProperty $prop, PHPClass $class)
    {
        $methodBody = '';
        $docblock = new DocBlockGenerator();
        $docblock->setWordWrap(false);

        $docblock->setShortDescription('Sets a new ' . $prop->getName());

        if ($prop->getDoc()) {
            $docblock->setLongDescription($prop->getDoc());
        }

        $patramTag = new ParamTag($prop->getName());


        $type = $prop->getType();

        $inflector = InflectorFactory::create()->build();
        $method = new MethodGenerator('set' . $inflector->classify($prop->getName()));

        $parameter = new ParameterGenerator($prop->getName());

        if ($type && $type instanceof PHPClassOf) {
            $docblock->setTag($patramTag);
            $patramTag->setTypes($type->getArg()
                    ->getType()->getPhpType() . '[]');

            if ($type->getArg()->getDefault() === []) {
                $parameter->setType('array');
            } else {
                $parameter->setType('?array');
            }


            if ($p = $type->getArg()->getType()->isSimpleType()
            ) {
                if (($t = $p->getType())) {
                    $patramTag->setTypes($t->getPhpType());
                }
            }
        } elseif ($type) {
            if ($type->isNativeType()) {
                $parameter->setType($this->getTypeAsPhpString($type));
            } elseif ($p = $type->isSimpleType()) {
                if (($t = $p->getType()) && !$t->isNativeType()) {
                    $parameter->setType($t->getPhpType());
                } elseif ($t) {
                    $parameter->setType($this->getTypeAsPhpString($t));
                }
            } else {
                $parameter->setType(($prop->getNullable() ? '?' : '') . $type->getPhpType());
            }

            if ($prop->getNullable() && $parameter->getType()) {
                $parameter->setDefaultValue(null);
            }
        }

        $methodBody .= '$this->' . $prop->getName() . ' = $' . $prop->getName() . ';' . PHP_EOL;
        $methodBody .= 'return $this;';
        $method->setBody($methodBody);
        $method->setDocBlock($docblock);
        $method->setParameter($parameter);

        if ($prop->getDefault() === null) {
            $method->setReturnType('static');
        }

        $generator->addMethodFromGenerator($method);
    }

    private function handleGetter(Generator\ClassGenerator $generator, PHPProperty $prop, PHPClass $class)
    {
        $inflector = InflectorFactory::create()->build();

        if ($prop->getType() instanceof PHPClassOf) {
            $docblock = new DocBlockGenerator();
            $docblock->setWordWrap(false);
            $docblock->setShortDescription('isset ' . $prop->getName());
            if ($prop->getDoc()) {
                $docblock->setLongDescription($prop->getDoc());
            }

            $paramIndex = new ParameterGenerator('index');
            $paramIndex->setType('int|string');


            $method = new MethodGenerator('isset' . $inflector->classify($prop->getName()), [$paramIndex]);
            $method->setDocBlock($docblock);
            $method->setBody('return isset($this->' . $prop->getName() . '[$index]);');
            $method->setReturnType('bool');
            $generator->addMethodFromGenerator($method);

            $docblock = new DocBlockGenerator();
            $docblock->setWordWrap(false);
            $docblock->setShortDescription('unset ' . $prop->getName());
            if ($prop->getDoc()) {
                $docblock->setLongDescription($prop->getDoc());
            }

            $paramIndex = new ParameterGenerator('index');
            $paramIndex->setType('int|string');

            $method = new MethodGenerator('unset' . $inflector->classify($prop->getName()), [$paramIndex]);
            $method->setDocBlock($docblock);
            $method->setBody('unset($this->' . $prop->getName() . '[$index]);');
            $method->setReturnType('void');
            $generator->addMethodFromGenerator($method);
        }
        // ////

        $docblock = new DocBlockGenerator();
        $docblock->setWordWrap(false);

        $docblock->setShortDescription('Get the ' . $prop->getName());

        if ($prop->getDoc()) {
            $docblock->setLongDescription($prop->getDoc());
        }

        $method = new MethodGenerator('get' . $inflector->classify($prop->getName()));
        $method->setDocBlock($docblock);
        $method->setBody('return $this->' . $prop->getName() . ';');

        $tag = new ReturnTag('mixed');
        $type = $prop->getType();
        if ($type && $type instanceof PHPClassOf) {
            $tt = $type->getArg()->getType();
            $tag->setTypes($tt->getPhpType() . '[]');
            $docblock->setTag($tag);


            if ($type->getArg()->getDefault() === []) {
                $method->setReturnType('array');
            } else {
                $method->setReturnType('?array');
            }
        } elseif ($type) {
            if ($p = $type->isSimpleType()) {
                if ($t = $p->getType()) {
                    $method->setReturnType($this->getTypeAsPhpString($t));
                }
            } else {
                $method->setReturnType($this->getTypeAsPhpString($type));
            }
        }

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

        $patramTag = new ParamTag($propName, $type->getArg()->getType()->getPhpType());
        $docblock->setTag($patramTag);

        $inflector = InflectorFactory::create()->build();
        $method = new MethodGenerator('addTo' . $inflector->classify($prop->getName()));
        $method->setReturnType('static');

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

        $methodBody = '$this->' . $prop->getName() . '[] = $' . $propName . ';' . PHP_EOL;
        $methodBody .= 'return $this;';
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

        $docBlock = new DocBlockGenerator();
        $docBlock->setWordWrap(false);

        $tag = new VarTag($prop->getName(), 'mixed');

        $type = $prop->getType();

        if ($type && $type instanceof PHPClassOf) {

            if ($type->getArg()->getDefault() === []) {
                $generatedProp->setType(Generator\TypeGenerator::fromTypeString('array'));
                $generatedProp->setDefaultValue($type->getArg()->getDefault(), PropertyValueGenerator::TYPE_ARRAY, PropertyValueGenerator::OUTPUT_SINGLE_LINE);
            } else {
                $generatedProp->setType(Generator\TypeGenerator::fromTypeString('?array'));
                $generatedProp->setDefaultValue($type->getArg()->getDefault());
            }

            $tt = $type->getArg()->getType();
            $tag->setTypes($tt->getPhpType() . '[]');
            if ($p = $tt->isSimpleType()) {
                if (($t = $p->getType())) {
                    $tag->setTypes($t->getPhpType() . '[]');
                }
            }

            $docBlock->setTag($tag);
        } elseif ($type) {
            if ($type->isNativeType()) {
                $generatedProp->setType(Generator\TypeGenerator::fromTypeString($this->getTypeAsPhpString($type)));
            } elseif (($p = $type->isSimpleType()) && ($t = $p->getType())) {
                $generatedProp->setType(Generator\TypeGenerator::fromTypeString($this->getTypeAsPhpString($t)));
            } else {
                $generatedProp->setType(Generator\TypeGenerator::fromTypeString($this->getTypeAsPhpString($prop->getType())));
            }
        } else {
            $docBlock->setTag($tag);
        }

        if ($prop->getDoc()) {
            $docBlock->setLongDescription($prop->getDoc());
        }

        if ($prop->getDoc() || $docBlock->getTags()) {
            $generatedProp->setDocBlock($docBlock);
        }

    }

    public function generate(PHPClass $type)
    {
        $class = new \Laminas\Code\Generator\ClassGenerator();
        $docblock = new DocBlockGenerator('Class representing ' . $type->getName());
        $docblock->setWordWrap(false);
        if ($type->getDoc()) {
            $docblock->setLongDescription($type->getDoc());
        }
        $class->setNamespaceName($type->getNamespace() ?: null);
        $class->setName($type->getName());
        $class->setDocblock($docblock);
        $class->setImplementedInterfaces($type->getImplements());

        if ($extends = $type->getExtends()) {
            if ($p = $extends->isSimpleType()) {
                $this->handleProperty($class, $p);
                $this->handleValueMethod($class, $p, $extends);
            } else {
                $class->setExtendedClass($extends->getFullName());

                if ($extends->getNamespace() != $type->getNamespace()) {
                    if ($extends->getName() == $type->getName()) {
                        $class->addUse($type->getExtends()->getFullName(), $extends->getName() . 'Base');
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

    private function getTypeAsPhpString(PHPClass $type): string
    {
        return ($type->getPhpType() === 'mixed' ? '' : '?') . $type->getPhpType();
    }
}
