<?php

namespace GoetasWebservices\Xsd\XsdToPhp\Tests\Converter\JMS;

class Xsd2JmsElementTest extends Xsd2JmsBase
{
    /**
     * @dataProvider getPrimitiveTypeConversions
     */
    public function testElementOfPrimitiveTypeWithCdata($xsType, $phpName)
    {
        $xml = '
             <xs:schema targetNamespace="http://www.example.com" xmlns:xs="http://www.w3.org/2001/XMLSchema">
                <xs:element name="element-one" type="' . $xsType . '">

                </xs:element>
               </xs:schema>
            ';

        $this->converter->setUseCdata(true);
        $classes = $this->getClasses($xml);

        $this->assertCount(0, $classes);
    }

    /**
     * @dataProvider getPrimitiveTypeConversions
     */
    public function testElementOfPrimitiveTypeWithoutCdata($xsType, $phpName)
    {
        $xml = '
             <xs:schema targetNamespace="http://www.example.com" xmlns:xs="http://www.w3.org/2001/XMLSchema">
                <xs:element name="element-one" type="' . $xsType . '">

                </xs:element>
               </xs:schema>
            ';

        $this->converter->setUseCdata(false);
        $classes = $this->getClasses($xml);
        $this->assertCount(0, $classes);
    }

    /**
     * @dataProvider getPrimitiveTypeConversions
     */
    public function testElementOfPrimitiveType($xsType, $phpName)
    {
        $xml = '
             <xs:schema targetNamespace="http://www.example.com" xmlns:xs="http://www.w3.org/2001/XMLSchema">
                <xs:element name="element-one" type="' . $xsType . '">

                </xs:element>
               </xs:schema>
            ';
        $classes = $this->getClasses($xml);
        $this->assertCount(0, $classes);
    }

    /**
     * @dataProvider getPrimitiveTypeConversions
     */
    public function testElementOfPrimitiveTypeAnon($xsType, $phpName)
    {
        $xml = '
             <xs:schema targetNamespace="http://www.example.com" xmlns:xs="http://www.w3.org/2001/XMLSchema">
                <xs:element name="element-one">
                    <xs:simpleType>
                         <xs:restriction base="' . $xsType . '">
                         </xs:restriction>
                    </xs:simpleType>
                </xs:element>
               </xs:schema>
            ';

        $classes = $this->getClasses($xml);
        $this->assertCount(1, $classes);
    }

    /**
     * @dataProvider getBaseTypeConversions
     */
    public function testElementOfBaseType($xsType, $phpName, $jmsType)
    {
        $xml = '
             <xs:schema targetNamespace="http://www.example.com" xmlns:xs="http://www.w3.org/2001/XMLSchema">
                <xs:element name="element-one" type="' . $xsType . '">
                </xs:element>
               </xs:schema>
            ';

        $classes = $this->getClasses($xml);
        $this->assertCount(0, $classes);
    }

    /**
     * @dataProvider getBaseTypeConversions
     */
    public function testElementOfBaseTypeAnon($xsType, $phpName, $jmsType)
    {
        $xml = '
             <xs:schema targetNamespace="http://www.example.com" xmlns:xs="http://www.w3.org/2001/XMLSchema">
                <xs:element name="element-one">
                    <xs:simpleType>
                         <xs:restriction base="' . $xsType . '"/>
                    </xs:simpleType>
                </xs:element>
               </xs:schema>
            ';

        $classes = $this->getClasses($xml);
        $this->assertCount(1, $classes);
    }

    public function testUnqualifiedNsQualifiedElement()
    {
        $xsd = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
            <xs:schema version="1.0" 
                targetNamespace="http://www.example.com" 
                xmlns:tns="http://www.example.com"
                xmlns:xs="http://www.w3.org/2001/XMLSchema" 
                elementFormDefault="unqualified">
            
                <xs:complexType name="childType">
                    <xs:sequence>
                        <xs:element name="id" type="xs:string"/>
                    </xs:sequence>
                </xs:complexType>
            
                <xs:element name="root">
                    <xs:complexType>
                        <xs:sequence>
                            <xs:element name="child" type="tns:childType" maxOccurs="unbounded"/>
                            <xs:element form="qualified" name="childRoot" type="tns:childType"/>
                        </xs:sequence>
                    </xs:complexType>
                </xs:element>
            </xs:schema>
            ';
        $classes = $this->getClasses($xsd);

        $expected = [
            'Example\\Root\\RootAType' => [
                'Example\\Root\\RootAType' => [
                    'properties' => [
                        'child' => [
                            'expose' => true,
                            'access_type' => 'public_method',
                            'serialized_name' => 'child',
                            'accessor' => [
                                'getter' => 'getChild',
                                'setter' => 'setChild',
                            ],
                            'xml_list' => [
                                'inline' => true,
                                'entry_name' => 'child',
                            ],
                            'type' => 'array<Example\\ChildType>',
                        ],
                        'childRoot' => [
                            'expose' => true,
                            'access_type' => 'public_method',
                            'serialized_name' => 'childRoot',
                            'xml_element' => [
                                'namespace' => 'http://www.example.com',
                            ],
                            'accessor' => [
                                'getter' => 'getChildRoot',
                                'setter' => 'setChildRoot',
                            ],
                            'type' => 'Example\\ChildType',
                        ],
                    ],
                ],
            ],
            'Example\\Root' => [
                'Example\\Root' => [
                    'xml_root_name' => 'ns-8ece61d2:root',
                    'xml_root_namespace' => 'http://www.example.com',
                ],
            ],
            'Example\\ChildType' => [
                'Example\\ChildType' => [
                    'properties' => [
                        'id' => [
                            'expose' => true,
                            'access_type' => 'public_method',
                            'serialized_name' => 'id',
                            'accessor' => [
                                'getter' => 'getId',
                                'setter' => 'setId',
                            ],
                            'type' => 'string',
                        ],
                    ],
                ],
            ],
        ];

        $this->assertEquals($expected, $classes);
    }

    public function testUnqualifiedNs()
    {
        $xsd = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
            <xs:schema version="1.0" 
                targetNamespace="http://www.example.com" 
                xmlns:tns="http://www.example.com"
                xmlns:xs="http://www.w3.org/2001/XMLSchema" 
                elementFormDefault="unqualified">
            
                <xs:complexType name="childType">
                    <xs:sequence>
                        <xs:element name="id" type="xs:string"/>
                    </xs:sequence>
                </xs:complexType>
            
                <xs:element name="root">
                    <xs:complexType>
                        <xs:sequence>
                            <xs:element name="child" type="tns:childType" maxOccurs="unbounded"/>
                            <xs:element name="childRoot" type="tns:childType"/>
                        </xs:sequence>
                    </xs:complexType>
                </xs:element>
            </xs:schema>
            ';
        $classes = $this->getClasses($xsd);

        $expected = [
            'Example\\Root\\RootAType' => [
                'Example\\Root\\RootAType' => [
                    'properties' => [
                        'child' => [
                            'expose' => true,
                            'access_type' => 'public_method',
                            'serialized_name' => 'child',
                            'accessor' => [
                                'getter' => 'getChild',
                                'setter' => 'setChild',
                            ],
                            'xml_list' => [
                                'inline' => true,
                                'entry_name' => 'child',
                            ],
                            'type' => 'array<Example\\ChildType>',
                        ],
                        'childRoot' => [
                            'expose' => true,
                            'access_type' => 'public_method',
                            'serialized_name' => 'childRoot',
                            'accessor' => [
                                'getter' => 'getChildRoot',
                                'setter' => 'setChildRoot',
                            ],
                            'type' => 'Example\\ChildType',
                        ],
                    ],
                ],
            ],
            'Example\\Root' => [
                'Example\\Root' => [
                    'xml_root_name' => 'ns-8ece61d2:root',
                    'xml_root_namespace' => 'http://www.example.com',
                ],
            ],
            'Example\\ChildType' => [
                'Example\\ChildType' => [
                    'properties' => [
                        'id' => [
                            'expose' => true,
                            'access_type' => 'public_method',
                            'serialized_name' => 'id',
                            'accessor' => [
                                'getter' => 'getId',
                                'setter' => 'setId',
                            ],
                            'type' => 'string',
                        ],
                    ],
                ],
            ],
        ];

        $this->assertEquals($expected, $classes);
    }

    public function testSetterNamingStrategy()
    {
        $xsd = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
            <xs:schema version="1.0"
                targetNamespace="http://www.example.com"
                xmlns:tns="http://www.example.com"
                xmlns:xs="http://www.w3.org/2001/XMLSchema"
                elementFormDefault="unqualified">

                <xs:complexType name="childType">
                    <xs:sequence>
                        <xs:element name="ns.value" type="xs:string" />
                    </xs:sequence>
                </xs:complexType>

            </xs:schema>
            ';
        $classes = $this->getClasses($xsd);

        $this->assertCount(1, $classes);

        $this->assertEquals(
            [
                'Example\\ChildType' => [
                    'Example\\ChildType' => [
                        'properties' => [
                            'nsValue' => [
                                'expose' => true,
                                'access_type' => 'public_method',
                                'serialized_name' => 'ns.value',
                                'accessor' => [
                                    'getter' => 'getNsValue',
                                    'setter' => 'setNsValue',
                                ],
                                'type' => 'string',
                            ],
                        ],
                    ],
                ],
            ],
            $classes
        );
    }
}
