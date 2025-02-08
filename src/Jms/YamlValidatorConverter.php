<?php

namespace GoetasWebservices\Xsd\XsdToPhp\Jms;

use GoetasWebservices\XML\XSDReader\Schema\Attribute\Attribute;
use GoetasWebservices\XML\XSDReader\Schema\Attribute\AttributeItem;
use GoetasWebservices\XML\XSDReader\Schema\Element\ElementItem;
use GoetasWebservices\XML\XSDReader\Schema\Schema;
use GoetasWebservices\XML\XSDReader\Schema\Type\ComplexType;
use GoetasWebservices\XML\XSDReader\Schema\Type\SimpleType;
use GoetasWebservices\XML\XSDReader\Schema\Type\Type;
use GoetasWebservices\Xsd\XsdToPhp\Php\Structure\PHPClass;
use GoetasWebservices\Xsd\XsdToPhp\Php\Structure\PHPProperty;

class YamlValidatorConverter extends YamlConverter
{
    /**
     * Clean the properties for only remaining valid rules for Symfony Validation Constraints.
     *
     * @return PHPClass[]
     */
    public function getTypes(): array
    {
        $classes = parent::getTypes();

        foreach ($classes as $k => &$definition) {
            if (empty($definition[$k]['properties'])) {
                unset($classes[$k]);
                continue;
            }

            $properties = array_filter(array_map(function ($property) {
                if (!empty($property['validation'])) {
                    foreach ($property['validation'] as &$rule) {
                        foreach ($rule as $type => &$item) {
                            if ($type === 'Valid') {
                                continue;
                            } else {
                                if (!is_array($item)) {
                                    $item = [];
                                }
                                $item['groups'] = ['xsd_rules'];
                            }
                        }
                        unset($item);
                    }
                    unset($rule);

                    return $property['validation'];
                }

                return null;
            }, $definition[$k]['properties']));

            if (empty($properties)) {
                unset($classes[$k]);
                continue;
            }

            $definition[$k] = [
                'properties' => $properties,
            ];
        }

        return $classes;
    }

    /**
     * Load and convert XSD' restrictions to Symfony Validation Constraints
     * from a schema type.
     *
     * @param bool $arrayized
     */
    private function loadValidatorType(array &$property, Type $type, $arrayized = false)
    {
        $rules = [];

        if (($restrictions = $type->getRestriction()) && $checks = $restrictions->getChecks()) {
            $propertyType = isset($property['type']) ? $property['type'] : null;
            foreach ($checks as $key => $check) {
                switch ($key) {
                    case 'enumeration':
                        $rules[] = [
                            'Choice' => [
                                'choices' => array_map(function ($enum) use ($propertyType) {
                                    if ($propertyType === 'int') {
                                        return (int)$enum['value'];
                                    }
                                    if ($propertyType === 'float') {
                                        return (float)$enum['value'];
                                    }
                                    return $enum['value'];
                                }, $check),
                            ],
                        ];
                        break;
//                    fractionDigits totalDigits validation makes no sense in object validation
//                    mainly because they are represented as floats
//                    case 'fractionDigits':
//                        foreach ($check as $item) {
//                            if ($item['value']>0) {
//                                $rules[] = [
//                                    'Regex' => "/^\-?(\\d+\\.\\d{{$item['value']}})|\\d*$/"
//                                ];
//                            }
//                        }
//                        break;
//                    case 'totalDigits':
//                        foreach ($check as $item) {
//                            $rules[] = [
//                                'Regex' => "/^([^\d]*\d){{$item['value']}}[^\d]*$/"
//                            ];
//                        }
//                        break;
                    case 'length':
                        foreach ($check as $item) {
                            $rules[] = [
                                'Length' => [
                                    'min' => $item['value'],
                                    'max' => $item['value'],
                                ],
                            ];
                        }
                        break;
                    case 'maxLength':
                        foreach ($check as $item) {
                            $rules[] = [
                                'Length' => [
                                    'max' => $item['value'],
                                ],
                            ];
                        }
                        break;
                    case 'minLength':
                        foreach ($check as $item) {
                            $rules[] = [
                                'Length' => [
                                    'min' => $item['value'],
                                ],
                            ];
                        }
                        break;
                    case 'pattern':
                        foreach ($check as $item) {
                            // initial support for https://www.w3.org/TR/xsd-unicode-blocknames/
                            // not supported by standard php regex implementation
                            // \p{IsBasicLatin} represents a range, but the expression might be alraedy in a range,
                            // so we try our best and detect it if is in a range or not
                            $regexPattern =  $item['value'];
                            $unicodeClasses = [
                                '\p{IsBasicLatin}' => '\x{0000}-\x{007F}',
                                '\p{IsLatin-1Supplement}' => '\x{0080}-\x{00FF}',
                            ];
                            foreach ($unicodeClasses as $from => $to) {
                                if (preg_match('~\[.*' . preg_quote($from, '~') . '.*\]~', $regexPattern)) {
                                    $regexPattern = str_replace($from, $to, $regexPattern);
                                } else {
                                    $regexPattern = str_replace($from, "[$to]", $regexPattern);
                                }
                            }
                            $rules[] = [
                                'Regex' => ['pattern' => "~{$regexPattern}~u"],
                            ];
                        }
                        break;
                    case 'maxExclusive':
                        foreach ($check as $item) {
                            $rules[] = [
                                'LessThan' => ['value' => $item['value']],
                            ];
                        }
                        break;
                    case 'maxInclusive':
                        foreach ($check as $item) {
                            $rules[] = [
                                'LessThanOrEqual' => ['value' => $item['value']],
                            ];
                        }
                        break;
                    case 'minExclusive':
                        foreach ($check as $item) {
                            $rules[] = [
                                'GreaterThan' => ['value' => $item['value']],
                            ];
                        }
                        break;
                    case 'minInclusive':
                        foreach ($check as $item) {
                            $rules[] = [
                                'GreaterThanOrEqual' => ['value' => $item['value']],
                            ];
                        }
                        break;
                }
            }
        }

        if (!$arrayized) {
            $property['validation'] = array_merge(!empty($property['validation']) ? $property['validation'] : [], $rules);
        }

        return $rules;
    }

    /**
     * Load and convert XSD' restrictions to Symfony Validation Constraints
     * from a schema element including required rule.
     *
     * @param bool $arrayize
     */
    private function loadValidatorElement(array &$property, ElementItem $element)
    {
        /* @var $element Element */
        $type = null;
        if (method_exists($element, 'getType')) {
            $type = $element->getType();
        }

        $attrs = [];
        $arrayized = strpos($property['type'] ?? '', 'array<') === 0;

        if ($arrayized && $type) {
            if ($itemOfArray = $this->isArrayNestedElement($type)) {
                $attrs = [
                    'min' => min($element->getMin(), $itemOfArray->getMin()),
                    'max' => $itemOfArray->getMax(),
                ];
            } elseif ($itemOfArray = $this->isArrayType($type)) {
                $attrs = [
                    'min' => min($element->getMin(), $itemOfArray->getMin()),
                    'max' => $itemOfArray->getMax(),
                ];
            } elseif ($this->isArrayElement($element)) {
//                $attrs = [
//                    'min' => $element->getMin(),
//                    'max' => $element->getMax()
//                ];
            }
        }

        if (isset($attrs['min']) && $attrs['min'] === 0) {
            unset($attrs['min']);
        }
        if (isset($attrs['max']) && $attrs['max'] === -1) {
            unset($attrs['max']);
        }

        if ($type) {
            $rules = $this->loadValidatorType($property, $type, $arrayized);
        } else {
            $rules = [];
        }

        if ($element->getMin() > 0) {
            $property['validation'][] = [
                'NotNull' => null,
            ];
        }

        if ($arrayized && count($attrs) > 0) {
            $property['validation'][] = [
                'Count' => $attrs,
            ];
        }
        if ($arrayized && count($rules) > 0) {
//            $rules[] = ['Valid' => null];
            $property['validation'][] = [
                'All' => ['constraints' => $rules],
            ];
        } elseif ($type instanceof ComplexType) {
            $property['validation'][] = [
                'Valid' => null,
            ];
        }
    }

    /**
     * Load and convert XSD' restrictions to Symfony Validation Constraints
     * from a schema attribute including required rule.
     *
     * @param AttributeItem $element
     * @param bool $arrayize
     */
    private function loadValidatorAttribute(array &$property, AttributeItem $attribute)
    {
        /* @var $element Element */
        $type = $attribute->getType();

        $this->loadValidatorType($property, $type, false);

        // Required properties
        if ($attribute instanceof Attribute) {
            if ($attribute->getUse() === Attribute::USE_REQUIRED) {
                $property['validation'][] = [
                    'NotNull' => null,
                ];
            }
        }
    }

    /**
     * Override necessary to improve method to load validations from schema type.
     *
     * @param PHPClass $class
     * @param array $data
     * @param string $name
     */
    protected function visitSimpleType(&$class, &$data, SimpleType $type, $name): void
    {
        parent::visitSimpleType($class, $data, $type, $name);

        if ($restriction = $type->getRestriction()) {
            $parent = $restriction->getBase();
            if ($parent instanceof Type) {
                if (!isset($data['properties']['__value'])) {
                    $data['properties']['__value'] = [];
                }
                $this->loadValidatorType($data['properties']['__value'], $type);
            }
        }
    }

    /**
     * Override necessary to improve method to load validations from schema element.
     *
     * @param PHPClass $class
     * @param bool $arrayize
     *
     * @return PHPProperty
     */
    protected function &visitElement(array &$class, Schema $schema, ElementItem $element, bool $arrayize = true): array
    {
        $property = parent::visitElement($class, $schema, $element, $arrayize);

        $this->loadValidatorElement($property, $element);

        return $property;
    }

    /**
     * Override necessary to improve method to load validations from schema attribute.
     *
     * @param PHPClass $class
     *
     * @return array
     */
    protected function &visitAttribute(array &$class, Schema $schema, AttributeItem $attribute): array
    {
        $property = parent::visitAttribute($class, $schema, $attribute);

        $this->loadValidatorAttribute($property, $attribute);

        return $property;
    }

    /**
     * Responsible for handler all properties from extension types.
     *
     * @param PHPClass $class
     * @param array $data
     * @param string $parentName
     */
    protected function &handleClassExtension(array &$class, array &$data, Type $type, string $parentName): array
    {
        $property = parent::handleClassExtension($class, $data, $type, $parentName);

        $rules = $this->loadValidatorType($property, $type, false);

        $property['validation'] = array_merge(!empty($property['validation']) ? $property['validation'] : [], $rules);

        return $property;
    }
}
