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

                if (!empty($property['validation'])) {


                    foreach ($property['validation'] as &$rule){
                        foreach ($rule as $type => &$item) {

                            if ($type === 'Valid') {
                                continue;
                            } else {
                                if (!is_array($item)){
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
                                'Regex' => ['pattern' => "~{$item['value']}~"]
                            ];
                        }
                        break;
                    case 'maxExclusive':
                        foreach ($check as $item) {
                            $rules[] = [
                                'LessThan' => ['value' => $item['value']]
                            ];
                        }
                        break;
                    case 'maxInclusive':
                        foreach ($check as $item) {
                            $rules[] = [
                                'LessThanOrEqual' => ['value' => $item['value']]
                            ];
                        }
                        break;
                    case 'minExclusive':
                        foreach ($check as $item) {
                            $rules[] = [
                                'GreaterThan' => ['value' => $item['value']]
                            ];
                        }
                        break;
                    case 'minInclusive':
                        foreach ($check as $item) {
                            $rules[] = [
                                'GreaterThanOrEqual' => ['value' => $item['value']]
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
     * from a schema element including required rule
     *
     * @param array $property
     * @param ElementItem $element
     * @param boolean $arrayize
     */
    private function loadValidatorElement(array &$property, ElementItem $element)
    {
        /* @var $element Element */
        $type = $element->getType();

        $attrs = [];
        $arrayized = strpos($property['type'], 'array<') === 0;

        if ($arrayized) {
            if ($itemOfArray = $this->isArrayNestedElement($type)) {
                $attrs = [
                    'min' => min($element->getMin(), $itemOfArray->getMin()),
                    'max' => $itemOfArray->getMax()
                ];
            }  elseif ($itemOfArray = $this->isArrayType($type)) {
                $attrs = [
                    'min' => min($element->getMin(), $itemOfArray->getMin()),
                    'max' => $itemOfArray->getMax()
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

        $rules = $this->loadValidatorType($property, $type, $arrayized );


        if ($element->getMin() > 0) {
            $property['validation'][] = [
                'NotNull' => null
            ];
        }

        if ($arrayized && count($attrs) > 0) {
            $property['validation'][] = [
                 'Count' => $attrs
            ];
        }
        if ($arrayized && count($rules) > 0) {
//            $rules[] = ['Valid' => null];
            $property['validation'][] = [
                'All' => ['constraints' => $rules]
            ];
        } elseif ($type instanceof ComplexType) {
            $property['validation'][] = [
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
                $property['validation'][] = [
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
    protected function &visitElement(&$class, Schema $schema, ElementItem $element, $arrayize = true)
    {
        $property = parent::visitElement($class, $schema, $element, $arrayize);

        $this->loadValidatorElement($property, $element);
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
    protected function &visitAttribute(&$class, Schema $schema, AttributeItem $attribute)
    {
        $property = parent::visitAttribute($class, $schema, $attribute);

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
    protected function &handleClassExtension(&$class, &$data, Type $type, $parentName)
    {
        $property = parent::handleClassExtension($class, $data, $type, $parentName);

        $rules = $this->loadValidatorType($property, $type, false );

        $property['validation'] = array_merge(!empty($property['validation']) ? $property['validation'] : [], $rules);
        return $property;
    }
}
