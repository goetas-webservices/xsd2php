<?php

namespace GoetasWebservices\Xsd\XsdToPhp\Jms;

use GoetasWebservices\XML\XSDReader\Schema\Attribute\Attribute;
use GoetasWebservices\XML\XSDReader\Schema\Element\Element;
use GoetasWebservices\XML\XSDReader\Schema\Element\ElementItem;
use GoetasWebservices\XML\XSDReader\Schema\Schema;
use GoetasWebservices\XML\XSDReader\Schema\Type\ComplexType;
use GoetasWebservices\XML\XSDReader\Schema\Type\SimpleType;
use GoetasWebservices\XML\XSDReader\Schema\Type\Type;
use GoetasWebservices\XML\XSDReader\Schema\Attribute\AttributeItem;
use GoetasWebservices\Xsd\XsdToPhp\Php\Structure\PHPClass;
use GoetasWebservices\Xsd\XsdToPhp\Php\Structure\PHPProperty;

class YamlValidatorConverter extends YamlConverter
{
    /**
     * Clean the properties for only remaining valid rules for Symfony Validation Constraints
     *
     * @return PHPClass[]
     */
    public function getTypes()
    {
        $classes = parent::getTypes();

        foreach ($classes as $k => &$definition) {

            if (empty($definition[$k]['properties'])) {
                unset($classes[$k]);
                continue;
            }

            $properties = array_filter(array_map(function ($property) {
                unset($property['type']);
                return !empty($property) ? $property : null;
            }, $definition[$k]['properties']));

            if (empty($properties)) {
                unset($classes[$k]);
                continue;
            }

            $definition[$k] = [
                'properties' => $properties
            ];
        }

        return $classes;
    }

    /**
     * Load and convert XSD' restrictions to Symfony Validation Constraints
     * from a schema type
     *
     * @param array $property
     * @param Type $type
     * @param boolean $arrayized
     */
    private function loadValidatorType(array &$property, Type $type, $arrayized = false)
    {
        $rules = [];

        if (($restrictions = $type->getRestriction()) && $checks = $restrictions->getChecks()) {

            $isNumeric = false;
            foreach ($checks as $key => $check) {
                switch ($key) {
                    case 'enumeration':
                        $rules[] = [
                            'Choice' => [
                                'choices' => array_map(function ($enum) {
                                    return $enum['value'];
                                }, $check)
                            ]
                        ];
                        break;
                    case 'fractionDigits':
                        foreach ($check as $item) {
                            if ($item['value']>0) {
                                $rules[] = [
                                    'Regex' => "/^\-?(\\d+\\.\\d{0,{$item['value']}})|\\d*$/"
                                ];
                            }
                        }
                        break;
                    case 'totalDigits':
                        foreach ($check as $item) {
                            $rules[] = [
                                'Regex' => "/^\-?[\\d]{0,{$item['value']}}$/"
                            ];
                        }
                        break;
                    case 'length':
                        foreach ($check as $item) {
                            $rules[] = [
                                'Length' => [
                                    'min' => $item['value'],
                                    'max' => $item['value']
                                ]
                            ];
                        }
                        break;
                    case 'maxLength':
                        foreach ($check as $item) {
                            $rules[] = [
                                'Length' => [
                                    'max' => $item['value']
                                ]
                            ];
                        }
                        break;
                    case 'minLength':
                        foreach ($check as $item) {
                            $rules[] = [
                                'Length' => [
                                    'min' => $item['value']
                                ]
                            ];
                        }
                        break;
                    case 'pattern':
                        foreach ($check as $item) {
                            $rules[] = [
                                'Regex' => "/{$item['value']}/"
                            ];
                        }
                        break;
                    case 'maxExclusive':
                        foreach ($check as $item) {
                            $rules[] = [
                                'LessThan' => $item['value']
                            ];
                        }
                        break;
                    case 'maxInclusive':
                        foreach ($check as $item) {
                            $rules[] = [
                                'LessThanOrEqual' => $item['value']
                            ];
                        }
                        break;
                    case 'minExclusive':
                        foreach ($check as $item) {
                            $rules[] = [
                                'GreaterThan' => $item['value']
                            ];
                        }
                        break;
                    case 'minInclusive':
                        foreach ($check as $item) {
                            $rules[] = [
                                'GreaterThanOrEqual' => $item['value']
                            ];
                        }
                        break;
                }
            }
        }
        if (!$arrayized) {
            $property = array_merge($property, $rules);
        }

        return $rules;
    }

    /**
     * Load and convert XSD' restrictions to Symfony Validation Constraints
     * from a schema element including required rule
     *
     * @param array $property
     * @param ElementItem $element
     * @param boolean $arrayize
     */
    private function loadValidatorElement(array &$property, ElementItem $element, $arrayize)
    {
        /* @var $element Element */
        $type = $element->getType();

        $attrs = [];

        $arrayized = false;
        $typeToVisit = $type;
        if ($arrayize) {

//            if ($itemOfArray = $this->isArrayNestedElement($type)) {
//                $attrs = [
//                    'min' => $itemOfArray->getMin(),
//                    'max' => $itemOfArray->getMax()
//                ];
//                $arrayized = true;
//                $typeToVisit = $itemOfArray->getType();
//             } else

                 if ($itemOfArray = $this->isArrayType($type)) {
                $attrs = [
                    'min' => $itemOfArray->getMin(),
                    'max' => $itemOfArray->getMax()
                ];
                $arrayized = true;
                $typeToVisit = $itemOfArray->getType();
            } elseif ($this->isArrayElement($element)) {
                $attrs = [
                    'min' => $element->getMin(),
                    'max' => $element->getMax()
                ];
                $arrayized = true;
            }

            if (count($attrs) !== 0) {
                if ($attrs['min'] === 0) {
                    unset($attrs['min']);
                }
                if ($attrs['max'] === -1) {
                    unset($attrs['max']);
                }
            }
        }

        $rules = $this->loadValidatorType($property, $arrayized ? $typeToVisit : $type, $arrayized );

        // Required properties
        if ($this->visitType($type)) {
            if ($element->getMin() !== 0) {
                if ($arrayized && count($attrs) === 0) {
                    $property[] = [
                        'Count' => ['min' => 1]
                    ];
                }
                $property[] = [
                    'NotNull' => null
                ];
            }
        }

        if ($arrayized && count($attrs) > 0) {
            $property[] = [
                 'Count' => $attrs
            ];
        }
        if ($arrayized && count($rules) > 0 && $attrs) {
            $property[] = [
                'All' => $rules
            ];
        } elseif ($type instanceof ComplexType) {
            $property[] = [
                'Valid' => null
            ];
        }
    }

    /**
     * Load and convert XSD' restrictions to Symfony Validation Constraints
     * from a schema attribute including required rule
     *
     * @param array $property
     * @param AttributeItem $element
     * @param boolean $arrayize
     */
    private function loadValidatorAttribute(array &$property, AttributeItem $attribute)
    {
        /* @var $element Element */
        $type = $attribute->getType();

        $this->loadValidatorType($property, $type, false);

        // Required properties
        if ($attribute instanceof Attribute) {
            if ($attribute->getUse() === Attribute::USE_REQUIRED) {
                $property[] = [
                    'NotNull' => null
                ];
            }
        }
    }

    /**
     * Override necessary to improve method to load validations from schema type
     *
     * @param PHPClass $class
     * @param array $data
     * @param SimpleType $type
     * @param string $name
     */
    protected function visitSimpleType(&$class, &$data, SimpleType $type, $name)
    {
        parent::visitSimpleType($class, $data, $type, $name);

        if ($restriction = $type->getRestriction()) {
            $parent = $restriction->getBase();
            if ($parent instanceof Type) {
                if (!isset($data["properties"]['__value'])) {
                    $data["properties"]['__value'] = [];
                }
                $this->loadValidatorType($data["properties"]['__value'], $type);
            }
        }
    }

    /**
     * Override necessary to improve method to load validations from schema element
     *
     * @param PHPClass $class
     * @param Schema $schema
     * @param ElementItem $element
     * @param boolean $arrayize
     * @return PHPProperty
     */
    protected function visitElement(&$class, Schema $schema, ElementItem $element, $arrayize = true)
    {
        $property = array();

        $this->findPHPClass($class, $element);

        $this->loadValidatorElement($property, $element, $arrayize);

        return $property;
    }

    /**
     * Override necessary to improve method to load validations from schema attribute
     *
     * @param PHPClass $class
     * @param Schema $schema
     * @param AttributeItem $attribute
     * @return array
     */
    protected function visitAttribute(&$class, Schema $schema, AttributeItem $attribute)
    {
        $property = array();

        $this->loadValidatorAttribute($property, $attribute);

        return $property;
    }

    /**
     * Responsible for handler all properties from extension types
     *
     * @param PHPClass $class
     * @param array $data
     * @param Type $type
     * @param string $parentName
     */
    protected function handleClassExtension(&$class, &$data, Type $type, $parentName)
    {
        if (!isset($data["properties"])) {
            $data["properties"] = [];
        }

        if ($alias = $this->getTypeAlias($type)) {
            $property["type"] = $alias;
            $data["properties"]["__value"] = $property;
        } else {
            $extension = $this->visitType($type, true);
            $extension = reset($extension);

            if (isset($extension['properties']['__value']) && count($extension['properties']) === 1) {
                $data["properties"]["__value"] = $extension['properties']['__value'];
            } elseif ($type instanceof SimpleType) { // @todo ?? basta come controllo?
                $property = array();
                if ($valueProp = $this->typeHasValue($type, $class, $parentName)) {
                    $property["type"] = $valueProp;
                } else {
                    $property["type"] = key($extension);
                }
                $data["properties"]["__value"] = $property;
            }
        }
    }
}
