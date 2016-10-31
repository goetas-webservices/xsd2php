<?php
namespace GoetasWebservices\Xsd\XsdToPhp\Tests\Converter\JMS;

class Xsd2JmsWithValidatorTest extends Xsd2JmsBase
{

    /**
     * @dataProvider getRestrictionsValidations
     */
    public function testSimpleTypeWithValidations($xsRestrictions, $ymlValidations)
    {
        $xml = "
             <xs:schema targetNamespace=\"http://www.example.com\" xmlns:xs=\"http://www.w3.org/2001/XMLSchema\">
                <xs:element name=\"element-one\">
                    <xs:simpleType>
                         <xs:restriction base=\"xs:string\">
                            {$xsRestrictions}
                         </xs:restriction>
                    </xs:simpleType>
                </xs:element>
               </xs:schema>
            ";

        $classes = $this->getClasses($xml);
        //print_r($classes); 
        $this->assertCount(1, $classes);

        $this->assertEquals(
            [
                'Example\\ElementOne' => [
                    'xml_root_name' => 'element-one',
                    'xml_root_namespace' => 'http://www.example.com',
                    'properties' => [
                        '__value' => [
                            'expose' => true,
                            'xml_value' => true,
                            'access_type' => 'public_method',
                            'accessor' => [
                                'getter' => 'value',
                                'setter' => 'value'
                            ],
                            'type' => 'string',
                            'validator' => $ymlValidations
                        ]
                    ]
                ]
            ], $classes['Example\ElementOne']);
    }
    
    /**
     * @dataProvider getRestrictionsValidations
     */
    public function testComplexTypeWithValidations($xsRestrictions, $ymlValidations)
    {
        $content = '
             <xs:schema targetNamespace="http://www.example.com" xmlns:xs="http://www.w3.org/2001/XMLSchema"  xmlns:ex="http://www.example.com">
                <xs:complexType name="complexType-1">
                    <xs:sequence>
                        <xs:element name="column1">
                            <xs:simpleType>
                                <xs:restriction base="xs:string">
                                    '.$xsRestrictions.'
                                </xs:restriction>
                            </xs:simpleType>
                        </xs:element>
                        <xs:element name="column2" type="xs:string"></xs:element>
                        <xs:element name="column3" type="xs:string" minOccurs="0"></xs:element>
                        <xs:element name="column4" type="xs:string" maxOccurs="unbounded"></xs:element>
                        <xs:element name="column5" type="xs:string" minOccurs="0" maxOccurs="100"></xs:element>
                    </xs:sequence>
                </xs:complexType>
            </xs:schema>
            ';
        $classes = $this->getClasses($content);
        
        $this->assertCount(1, $classes);

        $this->assertEquals(
            [
                'Example\\ComplexType1Type' => [
                    'properties' => [
                        'column1' => [
                            'expose' => 1,
                            'access_type' => 'public_method',
                            'serialized_name' => 'column1',
                            'accessor' => [
                                'getter' => 'getColumn1',
                                'setter' => 'setColumn1'
                            ],
                            'validator' => array_merge(
                                $ymlValidations,
                                [
                                    [
                                        'NotBlank' => null
                                    ]
                                ]
                            ),
                            'type' => 'string'
                        ],                        
                        'column2' => [
                            'expose' => 1,
                            'access_type' => 'public_method',
                            'serialized_name' => 'column2',
                            'accessor' => Array (
                                'getter' => 'getColumn2',
                                'setter' => 'setColumn2'
                            ),
                            'validator' => [
                                [
                                    'NotBlank' => null
                                ]
                            ],
                            'type' => 'string'
                        ],
                        'column3' => [
                            'expose' => 1,
                            'access_type' => 'public_method',
                            'serialized_name' => 'column3',
                            'accessor' => [
                                'getter' => 'getColumn3',
                                'setter' => 'setColumn3'
                            ],
                            'type' => 'string'
                        ],
                        'column4' => [
                            'expose' => 1,
                            'access_type' => 'public_method',
                            'serialized_name' => 'column4',
                            'accessor' => [
                                'getter' => 'getColumn4',
                                'setter' => 'setColumn4'
                            ],
                            'type' => 'string',
                            'validator' => [
                                [
                                    'Count' => [
                                        'min' => 1
                                    ]
                                ],
                                [
                                    'NotBlank' => null
                                ]
                            ],
                            'xml_list' => [
                                'inline' => 1,
                                'entry_name' => 'column4',
                                'namespace' => 'http://www.example.com'
                            ],
                            'type' => 'array<string>'
                        ],
                        'column5' => [
                            'expose' => 1,
                            'access_type' => 'public_method',
                            'serialized_name' => 'column5',
                            'accessor' => [
                                'getter' => 'getColumn5',
                                'setter' => 'setColumn5'
                            ],
                            'validator' => [
                                [
                                    'Count' => [
                                        'max' => 100
                                    ]
                                ]
                            ],
                            'xml_list' => [
                                'inline' => 1,
                                'entry_name' => 'column5',
                                'namespace' => 'http://www.example.com'
                            ],
                            'type' => 'array<string>'
                        ]                       
                    ]
                ]
            ], $classes['Example\\ComplexType1Type']);

    }
    
}