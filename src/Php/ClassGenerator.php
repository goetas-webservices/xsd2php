<?php

namespace GoetasWebservices\Xsd\XsdToPhp\Php;

use Doctrine\Inflector\Inflector;
use Doctrine\Inflector\InflectorFactory;
use GoetasWebservices\Xsd\XsdToPhp\Php\Structure\PHPClass;
use GoetasWebservices\Xsd\XsdToPhp\Php\Structure\PHPClassOf;
use GoetasWebservices\Xsd\XsdToPhp\Php\Structure\PHPProperty;
use Laminas\Code\Generator;
use Laminas\Code\Generator\ClassGenerator as LaminasClassGenerator;
use Laminas\Code\Generator\DocBlock\Tag\ParamTag;
use Laminas\Code\Generator\DocBlock\Tag\ReturnTag;
use Laminas\Code\Generator\DocBlock\Tag\VarTag;
use Laminas\Code\Generator\DocBlockGenerator;
use Laminas\Code\Generator\MethodGenerator;
use Laminas\Code\Generator\ParameterGenerator;
use Laminas\Code\Generator\PropertyGenerator;

class ClassGenerator
{
    private bool $strictTypes = false;

    protected Inflector $inflector;

    public function __construct()
    {
        $this->inflector = InflectorFactory::create()->build();
    }

    public function enableStrictTypes(): void
    {
        $this->strictTypes = true;
    }

    private function handleBody(Generator\ClassGenerator $class, PHPClass $type): bool
    {
        foreach ($type->getProperties() as $prop) {
            $name = $prop->getName();
            if ($name !== '__value') {
                $parentProp = $type->getPropertyInHierarchy($name, true);
                $fixed = $prop->getFixed();
                $valDiff = $parentProp &&
                    (($fixed ?? $prop->getDefault()) !== ($parentProp->getFixed() ?? $parentProp->getDefault()));

                if (!$parentProp || $valDiff) {
                    $this->handleProperty($class, $prop);
                }

                if (!$parentProp) {
                    $this->handleGetter($class, $prop, $type);
                    $this->handleSetter($class, $prop, $type);

                    if ($prop->getType() instanceof PHPClassOf) {
                        $this->handleAdder($class, $prop, $type);
                    }
                }
            }
        }

        if (count($type->getProperties()) === 1 && $type->hasProperty('__value')) {
            return false;
        }

        return true;
    }

    private function handleValueMethod(
        Generator\ClassGenerator $generator,
        PHPProperty $prop,
        PHPClass $class,
        bool $all = true
    ): void {
        $type = $prop->getType();

        $docblock = new DocBlockGenerator('Construct');
        $docblock->setWordWrap(false);
        $paramTag = new ParamTag('value');
        $paramTag->setTypes(($type ? $type->getPhpType() : 'mixed'));

        $docblock->setTag($paramTag);

        $param = new ParameterGenerator('value');
        if ($type && !$type->isNativeType()) {
            $param->setType($type->getPhpType());
        }
        $method = new MethodGenerator('__construct', [
            $param,
        ]);
        $method->setDocBlock($docblock);
        $method->setBody('$this->value($value);');

        $generator->addMethodFromGenerator($method);

        $docblock = new DocBlockGenerator('Gets or sets the inner value');
        $docblock->setWordWrap(false);
        $paramTag = new ParamTag('value');
        if ($type && $type instanceof PHPClassOf) {
            $paramTag->setTypes($type->getArg()->getType()->getPhpType() . '[]');
        } elseif ($type) {
            $paramTag->setTypes($prop->getType()->getPhpType());
        }
        $docblock->setTag($paramTag);

        $returnTag = new ReturnTag('mixed');

        if ($type && $type instanceof PHPClassOf) {
            $returnTag->setTypes($type->getArg()->getType()->getPhpType() . '[]');
        } elseif ($type) {
            $returnTag->setTypes($type->getPhpType());
        }
        $docblock->setTag($returnTag);

        $param = new ParameterGenerator('value');
        $param->setDefaultValue(null);

        if ($type && !$type->isNativeType()) {
            $param->setType($type->getPhpType());
        }
        $method = new MethodGenerator('value', []);
        $method->setDocBlock($docblock);

        $methodBody = 'if ($args = func_get_args()) {' . PHP_EOL;
        $methodBody .= '    $this->' . $prop->getName() . ' = $args[0];' . PHP_EOL;
        $methodBody .= '}' . PHP_EOL;
        $methodBody .= 'return $this->' . $prop->getName() . ';' . PHP_EOL;
        $method->setBody($methodBody);

        $generator->addMethodFromGenerator($method);

        $docblock = new DocBlockGenerator('Gets a string value');
        $docblock->setWordWrap(false);
        $docblock->setTag(new ReturnTag('string'));
        $method = new MethodGenerator('__toString');
        $method->setDocBlock($docblock);
        $method->setBody('return strval($this->' . $prop->getName() . ');');
        $generator->addMethodFromGenerator($method);
    }

    private function handleSetter(Generator\ClassGenerator $generator, PHPProperty $prop, PHPClass $class): void
    {
        $name = $prop->getName();
        $methodBody = '';
        $docblock = new DocBlockGenerator();
        $docblock->setWordWrap(false);
        $docblock->setShortDescription('Sets a new ' . $name);

        if ($prop->getDoc()) {
            $docblock->setLongDescription($prop->getDoc());
        }

        $patramTag = new ParamTag($name);
        $docblock->setTag($patramTag);

        $return = new ReturnTag('static');
        $docblock->setTag($return);

        $type = $prop->getType();

        $method = new MethodGenerator('set' . $this->inflector->classify($name));

        $parameter = new ParameterGenerator($name);

        if ($type && $type instanceof PHPClassOf) {
            $patramTag->setTypes($type->getArg()
                    ->getType()->getPhpType() . '[]');
            $parameter->setType('array');

            if (
                $p = $type->getArg()->getType()->isSimpleType()
            ) {
                if (($t = $p->getType())) {
                    $patramTag->setTypes($t->getPhpType());
                }
            }
        } elseif ($type) {
            if ($type->isNativeType()) {
                $patramTag->setTypes($type->getPhpType());
                if ($this->strictTypes) {
                    $parameter->setType($type->getPhpType());
                }
            } elseif ($p = $type->isSimpleType()) {
                if (($t = $p->getType()) && !$t->isNativeType()) {
                    $patramTag->setTypes($t->getPhpType());
                    $parameter->setType($t->getPhpType());
                } elseif ($t) {
                    $patramTag->setTypes($t->getPhpType());
                    if ($this->strictTypes) {
                        $parameter->setType($t->getPhpType());
                    }
                }
            } else {
                $patramTag->setTypes($type->getPhpType());
                $parameter->setType(($prop->getNullable() ? '?' : '') . $type->getPhpType());
            }
        }

        if ($this->strictTypes && $prop->getDefault() === null) {
            $parameter->setDefaultValue(null);
        }

        $methodBody .= '$this->' . $name . ' = $' . $name . ';' . PHP_EOL . 'return $this;';
        $method->setDocBlock($docblock);
        $method->setBody($methodBody);
        $method->setParameter($parameter);

        $generator->addMethodFromGenerator($method);
    }

    private function handleGetter(Generator\ClassGenerator $generator, PHPProperty $prop, PHPClass $class): void
    {
        $name = $prop->getName();

        if ($prop->getType() instanceof PHPClassOf) {
            $docblock = new DocBlockGenerator();
            $docblock->setWordWrap(false);
            $docblock->setShortDescription("isset $name");
            if ($prop->getDoc()) {
                $docblock->setLongDescription($prop->getDoc());
            }

            $patramTag = new ParamTag('index', 'int|string');
            $docblock->setTag($patramTag);

            $docblock->setTag(new ReturnTag('bool'));

            $paramIndex = new ParameterGenerator('index');


            $method = new MethodGenerator('isset' . $this->inflector->classify($name), [$paramIndex]);
            $method->setDocBlock($docblock);
            $method->setBody('return isset($this->' . $name . '[$index]);');
            $generator->addMethodFromGenerator($method);

            $docblock = new DocBlockGenerator();
            $docblock->setWordWrap(false);
            $docblock->setShortDescription("unset $name");
            if ($prop->getDoc()) {
                $docblock->setLongDescription($prop->getDoc());
            }

            $patramTag = new ParamTag('index', 'int|string');
            $docblock->setTag($patramTag);
            $paramIndex = new ParameterGenerator('index');

            $docblock->setTag(new ReturnTag('void'));

            $method = new MethodGenerator('unset' . $this->inflector->classify($name), [$paramIndex]);
            $method->setDocBlock($docblock);
            $method->setBody('unset($this->' . $name . '[$index]);');

            $generator->addMethodFromGenerator($method);
        }

        $docblock = new DocBlockGenerator();
        $docblock->setWordWrap(false);

        $docblock->setShortDescription("Gets as $name");

        if ($prop->getDoc()) {
            $docblock->setLongDescription($prop->getDoc());
        }

        $tag = new ReturnTag('mixed');
        $type = $prop->getType();
        if ($type && $type instanceof PHPClassOf) {
            $tt = $type->getArg()->getType();
            $tag->setTypes($tt->getPhpType() . '[]');
            if ($p = $tt->isSimpleType()) {
                if (($t = $p->getType())) {
                    $tag->setTypes($t->getPhpType() . '[]');
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
        $method = new MethodGenerator('get' . $this->inflector->classify($name));
        $method->setDocBlock($docblock);
        $method->setBody('return $this->' . $name . ';');

        $generator->addMethodFromGenerator($method);
    }

    private function handleAdder(Generator\ClassGenerator $generator, PHPProperty $prop, PHPClass $class): void
    {
        /** @var PHPClassOf $type */
        $type = $prop->getType();
        $propName = $type->getArg()->getName();

        $docblock = new DocBlockGenerator();
        $docblock->setWordWrap(false);
        $docblock->setShortDescription("Adds as $propName");

        if ($prop->getDoc()) {
            $docblock->setLongDescription($prop->getDoc());
        }

        $return = new ReturnTag();
        $return->setTypes('static');
        $docblock->setTag($return);

        $patramTag = new ParamTag($propName, $type->getArg()->getType()->getPhpType());
        $docblock->setTag($patramTag);

        $method = new MethodGenerator('addTo' . $this->inflector->classify($prop->getName()));

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

    private function handleMethod(Generator\ClassGenerator $generator, PHPProperty $prop, PHPClass $class): void
    {
        if ($prop->getType() instanceof PHPClassOf) {
            $this->handleAdder($generator, $prop, $class);
        }

        $this->handleGetter($generator, $prop, $class);

        if ($prop->getFixed()) {
            $this->handleSetter($generator, $prop, $class);
        }
    }

    private function handleProperty(Generator\ClassGenerator $class, PHPProperty $prop): void
    {
        $generatedProp = new PropertyGenerator($prop->getName());
        $generatedProp->setVisibility($prop->getVisibility());

        $class->addPropertyFromGenerator($generatedProp);

        /* if ($prop->getType() && (!$prop->getType()->getNamespace() && $prop->getType()->getName() == 'array')) {
            $generatedProp
                ->setDefaultValue([], PropertyValueGenerator::TYPE_AUTO, PropertyValueGenerator::OUTPUT_SINGLE_LINE);
        } */

        $docBlock = new DocBlockGenerator();
        $docBlock->setWordWrap(false);
        $generatedProp->setDocBlock($docBlock);

        if ($prop->getDoc()) {
            $docBlock->setLongDescription($prop->getDoc());
        }
        $tag = new VarTag($prop->getName(), 'mixed');

        $type = $prop->getType();

        if ($type && $type instanceof PHPClassOf) {
            $tt = $type->getArg()->getType();
            $tag->setTypes($tt->getPhpType() . '[]');
            if ($p = $tt->isSimpleType()) {
                if (($t = $p->getType())) {
                    $tag->setTypes($t->getPhpType() . '[]');
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

            $value = $prop->getFixed() ?? $prop->getDefault();
            if ($value !== null) {
                $generatedProp->setDefaultValue($value);
            }
        }

        $docBlock->setTag($tag);
    }

    public function generate(PHPClass $type): ?LaminasClassGenerator
    {
        $class = new LaminasClassGenerator();
        $docblock = new DocBlockGenerator('Class representing ' . $type->getName());
        $docblock->setWordWrap(false);
        if ($type->getDoc()) {
            $docblock->setLongDescription($type->getDoc());
        }
        $class->setNamespaceName($type->getNamespace() ?: null);
        $class->setName($type->getName());
        $class->setDocBlock($docblock);
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

        return null;
    }
}
