<?php
namespace GoetasWebservices\Xsd\XsdToPhp\Jms;

use GoetasWebservices\XML\XSDReader\Schema\Element\Element;
use GoetasWebservices\XML\XSDReader\Schema\Element\ElementItem;
use GoetasWebservices\XML\XSDReader\Schema\Schema;
use GoetasWebservices\XML\XSDReader\Schema\Type\ComplexType;
use GoetasWebservices\XML\XSDReader\Schema\Type\SimpleType;
use GoetasWebservices\XML\XSDReader\Schema\Type\Type;

class YamlValidatorConverter extends YamlConverter
{
    /**
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
                return !empty($property['validator']) ? $property['validator'] : null;
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
     * @param array $property
     * @param Type $type
     */
    private function loadValidatorType(array &$property, Type $type, $arrayized = false)
    {
        if (!isset($property["validator"])) {
            $property["validator"] = [];
        }
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
                    case 'fractionDigits':
                        foreach ($check as $item) {
                            $rules[] = [
                                'Regex' => "/^(\\d+\.\\d{1,{$item['value']}})|\\d*$/"
                            ];
                        }
                        $rules[] = [
                            'Range' => [
                                'min' => 0
                            ]
                        ];
                        break;
                    case 'totalDigits':
                        foreach ($check as $item) {
                            $rules[] = [
                                'Regex' => "/^[\\d]{0,{$item['value']}}$/"
                            ];
                        }
                        $rules[] = [
                            'Range' => [
                                'min' => 0
                            ]
                        ];
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
                                'Regex' => "/^{$item['value']}$/"
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
        } else 
        if ($type instanceof ComplexType) {
            $rules[] = [
                'Valid' => null
            ];
        }
        
        if (count($rules) !== 0) {
            if ($arrayized){
                $rules = [
                    ['All' => $rules]
                ];
            }
            // Merge validator items implemented before
            $property["validator"] = array_merge($property["validator"], $rules);
        }

    }

    private function loadValidatorElement(array &$property, ElementItem $element, $arrayize)
    {
        /* @var $element Element */
        $type = $element->getType();
        
        $attrs = [];

        $arrayized = false;
        if ($arrayize) {

//            if ($itemOfArray = $this->isArrayNestedElement($type)) {
//                $attrs = [
//                    'min' => $itemOfArray->getMin(),
//                    'max' => $itemOfArray->getMax()
//                ];
//                $arrayized = true;
//            } else
            if ($itemOfArray = $this->isArrayType($type)) {
                $attrs = [
                    'min' => $itemOfArray->getMin(),
                    'max' => $itemOfArray->getMax()
                ];
                $arrayized = true;
            } else
            if ($this->isArrayElement($element)) {
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
                if (count($attrs) !== 0) {
                    $property["validator"][] = [
                        'Count' => $attrs
                    ];
                }
            }

        }

        $this->loadValidatorType($property, $type, $arrayized);

        // Required properties
        if ($classType = $this->visitType($type)) {
            if ($element->getMin() !== 0) {
                if ($arrayized && count($attrs) === 0){
                    $property["validator"][] = [
                        'Count' => ['min' => 1]
                    ];
                }
                $property["validator"][] = [
                    'NotNull' => null
                ];
            }
        } 
    }

    protected function visitSimpleType(&$class, &$data, SimpleType $type, $name)
    {
        parent::visitSimpleType($class, $data, $type, $name);

        if ($restriction = $type->getRestriction()) {
            $parent = $restriction->getBase();
            if ($parent instanceof Type) {
                $this->loadValidatorType($data["properties"]['__value'], $type);
            }
        }
    }

    /**
     *
     * @param PHPClass $class
     * @param Schema $schema
     * @param ElementItem $element
     * @param boolean $arrayize
     * @return \GoetasWebservices\Xsd\XsdToPhp\Structure\PHPProperty
     */
    protected function visitElement(&$class, Schema $schema, ElementItem $element, $arrayize = true)
    {
        $property = parent::visitElement($class, $schema, $element, $arrayize);
        $this->loadValidatorElement($property, $element, $arrayize);
        return $property;
    }
    
    public function visitType(Type $type, $force = true) {
        return parent::visitType($type, $force);
    }
    
}
