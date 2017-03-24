<?php
namespace GoetasWebservices\Xsd\XsdToPhp\Tests\Converter\JMS;

class Xsd2PhpGroupTest extends Xsd2JmsBase
{

    public function testGroupArray()
    {
        $content = '
             <xs:schema targetNamespace="http://www.example.com" xmlns:xs="http://www.w3.org/2001/XMLSchema">
                <xs:group name="EG_ExtensionList">
                    <xs:sequence>
                      <xs:element name="ext" type="xs:string" minOccurs="0" maxOccurs="unbounded"/>
                    </xs:sequence>
                </xs:group>
                <xs:complexType name="complexType-1">
                    <xs:sequence>
                        <xs:group ref="EG_ExtensionList"/>
                    </xs:sequence>
                </xs:complexType>
               </xs:schema>
            ';
        $classes = $this->getClasses($content);

        $this->assertCount(1, $classes);
        $this->assertArrayHasKey('Example\ComplexType1Type', $classes);
    }

    public function testAutoDiscoveryTraits()
    {
        $content = '
             <xs:schema targetNamespace="http://www.example.com" xmlns:xs="http://www.w3.org/2001/XMLSchema">
                <xs:group name="element-1">

                </xs:group>

                <xs:attributeGroup name="element-2"/>
               <xs:attribute name="element-3" type="xs:string"/>
               </xs:schema>
            ';
        $classes = $this->getClasses($content);

        $this->assertCount(0, $classes);
    }

    public function testSomeAnonymous()
    {
        error_reporting(error_reporting() & ~E_NOTICE);
        $content = '
             <xs:schema targetNamespace="http://www.example.com" xmlns:xs="http://www.w3.org/2001/XMLSchema"  xmlns:ex="http://www.example.com">
                    <xs:complexType name="complexType-1">
                        <xs:sequence>
                            <xs:element name="string1">
                                <xs:simpleType>
                                    <xs:restriction base="xs:string"></xs:restriction>
                                </xs:simpleType>
                            </xs:element>
                            <xs:element name="string2">
                                <xs:complexType>
                                    <xs:sequence>
                                        <xs:element name="string3" type="xs:string"/>
                                    </xs:sequence>
                                </xs:complexType>
                            </xs:element>
                            <xs:element name="string3">
                                <xs:simpleType>
                                    <xs:union memberTypes="xs:string xs:int"></xs:union>
                                </xs:simpleType>
                            </xs:element>  
                            <xs:element name="string4">
                                <xs:simpleType>
                                    <xs:restriction base="ex:foo"></xs:restriction>
                                </xs:simpleType>
                            </xs:element>   
                            <xs:element name="string5">
                                <xs:simpleType>
                                    <xs:union memberTypes="ex:foo"></xs:union>
                                </xs:simpleType>
                            </xs:element>                                
                        </xs:sequence>
                        <xs:attribute name="att">
                            <xs:simpleType>
                                <xs:restriction base="xs:string"></xs:restriction>
                            </xs:simpleType>
                        </xs:attribute>
                    </xs:complexType>
                    <xs:simpleType name="foo">
                        <xs:restriction base="xs:string"></xs:restriction>
                    </xs:simpleType>                    
            </xs:schema>
            ';
        $classes = $this->getClasses($content);
        $this->assertCount(2, $classes);
        $this->assertEquals(
            array(
                'Example\\ComplexType1Type' => array(
                    'properties' => array(
                        'att' => array(
                            'expose' => true,
                            'access_type' => 'public_method',
                            'serialized_name' => 'att',
                            'accessor' => array(
                                'getter' => 'getAtt',
                                'setter' => 'setAtt'
                            ),
                            'xml_attribute' => true,
                            'type' => 'string'
                        ),
                        'string1' => array(
                            'expose' => true,
                            'access_type' => 'public_method',
                            'serialized_name' => 'string1',
                            /*
                           'xml_element' => array(
                               'namespace' => 'http://www.example.com'
                           ),
                            */
                           'accessor' => array(
                               'getter' => 'getString1',
                               'setter' => 'setString1'
                           ),
                           'type' => 'string'
                       ),
                       'string2' => array(
                           'expose' => true,
                           'access_type' => 'public_method',
                           'serialized_name' => 'string2',
                           /*
                           'xml_element' => array(
                               'namespace' => 'http://www.example.com'
                           ),
                           */
                            'accessor' => array(
                                'getter' => 'getString2',
                                'setter' => 'setString2'
                            ),
                            'type' => 'Example\\ComplexType1Type\\String2AType'
                        ),
                        'string3' => array(
                            'expose' => true,
                            'access_type' => 'public_method',
                            'serialized_name' => 'string3',
                            /*
                            'xml_element' => array(
                                'namespace' => 'http://www.example.com'
                            ),
                            */
                            'accessor' => array(
                                'getter' => 'getString3',
                                'setter' => 'setString3'
                            ),
                            'type' => 'string'
                        ),
                        'string4' => array(
                            'expose' => true,
                            'access_type' => 'public_method',
                            'serialized_name' => 'string4',
                            /*
                            'xml_element' => array(
                                'namespace' => 'http://www.example.com'
                            ),
                            */
                            'accessor' => array(
                                'getter' => 'getString4',
                                'setter' => 'setString4'
                            ),
                            'type' => 'string'
                        ),
                        'string5' => array(
                            'expose' => true,
                            'access_type' => 'public_method',
                            'serialized_name' => 'string5',
                            /*
                            'xml_element' => array(
                                'namespace' => 'http://www.example.com'
                            ),
                            */
                            'accessor' => array(
                                'getter' => 'getString5',
                                'setter' => 'setString5'
                            ),
                            'type' => 'string'
                        ),
                    )
                )
            ), $classes['Example\\ComplexType1Type']);


        $this->assertEquals(
            array(
                'Example\\ComplexType1Type\\String2AType' => array(
                    'properties' => array(
                        'string3' => array(
                            'expose' => true,
                            'access_type' => 'public_method',
                            'serialized_name' => 'string3',
                             /*
                            'xml_element' => array(
                                'namespace' => 'http://www.example.com'
                            ),
                             */
                            'accessor' => array(
                                'getter' => 'getString3',
                                'setter' => 'setString3'
                            ),
                            'type' => 'string'
                        )
                    )
                )
            ), $classes['Example\\ComplexType1Type\\String2AType']);
    }

    public function testSomeInheritance()
    {
        $content = '
             <xs:schema targetNamespace="http://www.example.com" xmlns:xs="http://www.w3.org/2001/XMLSchema"  xmlns:ex="http://www.example.com">
                <xs:complexType name="complexType-1">
                     <xs:attribute name="attribute-2" type="xs:string"/>
                     <xs:sequence>
                            <xs:element name="complexType-1-el-1" type="xs:string"/>
                     </xs:sequence>
                </xs:complexType>
                <xs:complexType name="complexType-2">
                     <xs:complexContent>
                        <xs:extension base="ex:complexType-1">
                             <xs:sequence>
                                <xs:element name="complexType-2-el1" type="xs:string"></xs:element>
                            </xs:sequence>
                            <xs:attribute name="complexType-2-att1" type="xs:string"></xs:attribute>
                        </xs:extension>
                    </xs:complexContent>
                </xs:complexType>
            </xs:schema>
            ';
        $classes = $this->getClasses($content);
        $this->assertCount(2, $classes);

        $this->assertEquals(
            array(
                'Example\\ComplexType1Type' => array(
                    'properties' => array(
                        'attribute2' => array(
                            'expose' => true,
                            'access_type' => 'public_method',
                            'serialized_name' => 'attribute-2',
                            'accessor' => array(
                                'getter' => 'getAttribute2',
                                'setter' => 'setAttribute2'
                            ),
                            'xml_attribute' => true,
                            'type' => 'string'
                        ),
                        'complexType1El1' => array(
                            'expose' => true,
                            'access_type' => 'public_method',
                            'serialized_name' => 'complexType-1-el-1',
                             /*
                            'xml_element' => array(
                                'namespace' => 'http://www.example.com'
                            ),
                             */
                            'accessor' => array(
                                'getter' => 'getComplexType1El1',
                                'setter' => 'setComplexType1El1'
                            ),
                            'type' => 'string'
                        )
                    )
                )
            ), $classes['Example\\ComplexType1Type']);

        $this->assertEquals(
            array(
                'Example\\ComplexType2Type' => array(
                    'properties' => array(
                        'complexType2Att1' => array(
                            'expose' => true,
                            'access_type' => 'public_method',
                            'serialized_name' => 'complexType-2-att1',
                            'accessor' => array(
                                'getter' => 'getComplexType2Att1',
                                'setter' => 'setComplexType2Att1'
                            ),
                            'xml_attribute' => true,
                            'type' => 'string'
                        ),
                        'complexType2El1' => array(
                            'expose' => true,
                            'access_type' => 'public_method',
                            'serialized_name' => 'complexType-2-el1',
                             /*
                            'xml_element' => array(
                                'namespace' => 'http://www.example.com'
                            ),
                             */
                            'accessor' => array(
                                'getter' => 'getComplexType2El1',
                                'setter' => 'setComplexType2El1'
                            ),
                            'type' => 'string'
                        )
                    )
                )
            ), $classes['Example\\ComplexType2Type']);
    }

    public function getMaxOccurs()
    {
        return [
            [
                null,
                false
            ],
            [
                '1',
                false
            ],
            /*
            ['2', true],
            ['3', true],
            ['10', true],
            ['unbounded', true]
            */
        ];
    }

    public function testArray()
    {
        $content = '
             <xs:schema 
                targetNamespace="http://www.example.com" 
                xmlns:xs="http://www.w3.org/2001/XMLSchema"  
                xmlns:ex="http://www.example.com">
                   <xs:complexType name="complexType-1">
                        <xs:sequence>
                            <xs:element name="strings" type="ex:ArrayOfStrings"/>
                        </xs:sequence>
                    </xs:complexType>

                    <xs:complexType name="ArrayOfStrings">
                        <xs:sequence>
                            <xs:element name="string" type="xs:string" maxOccurs="unbounded" minOccurs="1"/>
                        </xs:sequence>
                    </xs:complexType>
            </xs:schema>
            ';
        $classes = $this->getClasses($content);
        $this->assertCount(1, $classes);

        $this->assertEquals(
            array(
                'Example\\ComplexType1Type' => array(
                    'properties' => array(
                        'strings' => array(
                            'expose' => true,
                            'access_type' => 'public_method',
                            'serialized_name' => 'strings',
                             /*
                            'xml_element' => array(
                                'namespace' => 'http://www.example.com'
                            ),
                             */
                            'accessor' => array(
                                'getter' => 'getStrings',
                                'setter' => 'setStrings'
                            ),
                            'type' => 'array<string>',
                            'xml_list' => array(
                                'inline' => false,
                                'entry_name' => 'string',
                                'skip_when_empty' => false
                            )
                        )
                    )
                )
            ), $classes['Example\\ComplexType1Type']);
    }

    /**
     * @dataProvider getMaxOccurs
     */
    public function testMaxOccurs($max, $isArray)
    {
        $content = '
             <xs:schema targetNamespace="http://www.example.com" xmlns:xs="http://www.w3.org/2001/XMLSchema">
                <xs:complexType name="complexType-1">
                     <xs:sequence>
                            <xs:element ' . ($max !== null ? (' maxOccurs="' . $max . '"') : "") . ' name="complexType-1-el-1" type="xs:string"/>
                     </xs:sequence>
                </xs:complexType>
            </xs:schema>
            ';
        $classes = $this->getClasses($content);
        $this->assertCount(1, $classes);

        $this->assertEquals(
            array(
                'Example\\ComplexType1Type' => array(
                    'properties' => array(
                        'complexType1El1' => array(
                            'expose' => true,
                            'access_type' => 'public_method',
                            'serialized_name' => 'complexType-1-el-1',
                             /*
                            'xml_element' => array(
                                'namespace' => 'http://www.example.com'
                            ),
                             */
                            'accessor' => array(
                                'getter' => 'getComplexType1El1',
                                'setter' => 'setComplexType1El1'
                            ),
                            'type' => 'string'
                        )
                    )
                )
            ), $classes['Example\\ComplexType1Type']);
    }

    public function testGeneralParts()
    {
        $content = '
             <xs:schema targetNamespace="http://www.example.com" xmlns:xs="http://www.w3.org/2001/XMLSchema">
                <xs:group name="group-1">
                    <xs:sequence>
                        <xs:element name="group-1-el-1" type="xs:string"/>
                        <xs:group ref="group-2"/>
                    </xs:sequence>
                </xs:group>

                <xs:group name="group-2">
                    <xs:sequence>
                        <xs:element name="group-2-el-1" type="xs:string"/>
                    </xs:sequence>
                </xs:group>

               <xs:element name="element-1" type="xs:string"/>

                <xs:attributeGroup name="attributeGroup-1">
                    <xs:attribute name="attributeGroup-1-att-1" type="xs:string"/>
                    <xs:attribute ref="attribute-1" />
                    <xs:attributeGroup ref="attributeGroup-2" />
                </xs:attributeGroup>

                <xs:attributeGroup name="attributeGroup-2">
                    <xs:attribute name="attributeGroup-2-att-2" type="xs:string"/>
                </xs:attributeGroup>

                <xs:attribute name="attribute-1" type="xs:string"/>

                <xs:complexType name="complexType-1">
                     <xs:attribute ref="attribute-1"/>
                     <xs:attribute name="attribute-2" type="xs:string"/>
                     <xs:attributeGroup ref="attributeGroup-1"/>

                     <xs:sequence>
                            <xs:group ref="group-1"/>
                            <xs:element ref="element-1"/>
                            <xs:element name="complexType-1-el-1" type="xs:string"/>
                     </xs:sequence>
                </xs:complexType>
            </xs:schema>
            ';
        $classes = $this->getClasses($content);

        $this->assertCount(2, $classes);

        $this->assertEquals(
            array(
                'Example\\ComplexType1Type' => array(
                    'properties' => array(
                        'attribute1' => array(
                            'expose' => true,
                            'access_type' => 'public_method',
                            'serialized_name' => 'attribute-1',
                            'accessor' => array(
                                'getter' => 'getAttribute1',
                                'setter' => 'setAttribute1'
                            ),
                            'xml_attribute' => true,
                            'type' => 'string'
                        ),
                        'attribute2' => array(
                            'expose' => true,
                            'access_type' => 'public_method',
                            'serialized_name' => 'attribute-2',
                            'accessor' => array(
                                'getter' => 'getAttribute2',
                                'setter' => 'setAttribute2'
                            ),
                            'xml_attribute' => true,
                            'type' => 'string'
                        ),
                        'attributeGroup1Att1' => array(
                            'expose' => true,
                            'access_type' => 'public_method',
                            'serialized_name' => 'attributeGroup-1-att-1',
                            'accessor' => array(
                                'getter' => 'getAttributeGroup1Att1',
                                'setter' => 'setAttributeGroup1Att1'
                            ),
                            'xml_attribute' => true,
                            'type' => 'string'
                        ),
                        'attributeGroup2Att2' => array(
                            'expose' => true,
                            'access_type' => 'public_method',
                            'serialized_name' => 'attributeGroup-2-att-2',
                            'accessor' => array(
                                'getter' => 'getAttributeGroup2Att2',
                                'setter' => 'setAttributeGroup2Att2'
                            ),
                            'xml_attribute' => true,
                            'type' => 'string'
                        ),
                        'group1El1' => array(
                            'expose' => true,
                            'access_type' => 'public_method',
                            'serialized_name' => 'group-1-el-1',
//                            'xml_element' => array(
//                                'namespace' => 'http://www.example.com'
//                            ),
                            'accessor' => array(
                                'getter' => 'getGroup1El1',
                                'setter' => 'setGroup1El1'
                            ),
                            'type' => 'string'
                        ),
                        'group2El1' => array(
                            'expose' => true,
                            'access_type' => 'public_method',
                            'serialized_name' => 'group-2-el-1',
//                            'xml_element' => array(
//                                'namespace' => 'http://www.example.com'
//                            ),
                            'accessor' => array(
                                'getter' => 'getGroup2El1',
                                'setter' => 'setGroup2El1'
                            ),
                            'type' => 'string'
                        ),
                        'element1' => array(
                            'expose' => true,
                            'access_type' => 'public_method',
                            'serialized_name' => 'element-1',
//                            'xml_element' => array(
//                                'namespace' => 'http://www.example.com'
//                            ),
                            'accessor' => array(
                                'getter' => 'getElement1',
                                'setter' => 'setElement1'
                            ),
                            'type' => 'string'
                        ),
                        'complexType1El1' => array(
                            'expose' => true,
                            'access_type' => 'public_method',
                            'serialized_name' => 'complexType-1-el-1',
//                            'xml_element' => array(
//                                'namespace' => 'http://www.example.com'
//                            ),
                            'accessor' => array(
                                'getter' => 'getComplexType1El1',
                                'setter' => 'setComplexType1El1'
                            ),
                            'type' => 'string'
                        )
                    )
                )
            ), $classes['Example\\ComplexType1Type']);
        $this->assertEquals(
            array(
                'Example\\Element1' => array(
                    'xml_root_name' => 'ns-8ece61d2:element-1',
                    'xml_root_namespace' => 'http://www.example.com',
                    'properties' => array(
                        '__value' => array(
                            'expose' => true,
                            'xml_value' => true,
                            'access_type' => 'public_method',
                            'accessor' => array(
                                'getter' => 'value',
                                'setter' => 'value'
                            ),
                            'type' => 'string'
                        )
                    )
                )
            ), $classes['Example\\Element1']);
    }
}
