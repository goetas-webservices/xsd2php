<?php
namespace GoetasWebservices\Xsd\XsdToPhp\Tests\Converter\Validator;

class Xsd2ValidatorTest extends Xsd2ValidatorBase
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
        
        $this->assertCount(1, $classes);

        $this->assertEquals(
            [
                'Example\\ElementOne' => [
                    'properties' => [
                        '__value' => $ymlValidations
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
        //print_r($classes);         
        $this->assertCount(1, $classes);

        $this->assertEquals(
            [
                'Example\\ComplexType1Type' => [
                    'properties' => [
                        'column1' => array_merge(
                            $ymlValidations,
                            [
                                [
                                    'NotBlank' => null
                                ]
                            ]
                        ),                        
                        'column2' => [
                            [
                                'NotBlank' => null
                            ]
                        ],
                        'column4' => [
                            [
                                'Count' => [
                                    'min' => 1
                                ]
                            ],
                            [
                                'Count' => [
                                    'min' => 1
                                ]
                            ],
                            [
                                'NotBlank' => null
                            ]
                        ],
                        'column5' => [
                            [
                                'Count' => [
                                    'max' => 100
                                ]
                            ]
                        ],                      
                    ]
                ]
            ], $classes['Example\\ComplexType1Type']);

    }
    
}