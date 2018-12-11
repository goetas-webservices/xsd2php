<?php
namespace GoetasWebservices\Xsd\XsdToPhp\Tests\Converter\JMS;

use GoetasWebservices\Xsd\XsdToPhp\Jms\YamlConverter;
use GoetasWebservices\Xsd\XsdToPhp\Naming\ShortNamingStrategy;
use GoetasWebservices\XML\XSDReader\SchemaReader;

class Xsd2PhpElementNoCdataTest extends Xsd2JmsBase
{

    public function setUp()
    {
        $this->converter = new YamlConverter(new ShortNamingStrategy());
        $this->converter->addNamespace('http://www.example.com', "Example");
        $this->converter->setCdata(false);

        $this->reader = new SchemaReader();
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
        $this->assertCount(1, $classes);

        $this->assertEquals(
            array(
                'Example\ElementOne' => array(
                    'xml_root_name' => 'ns-8ece61d2:element-one',
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
                            'type' => $phpName,
                            'xml_element' => array(
                                'cdata' => false
                            )
                        )
                    )
                )
            ), $classes['Example\ElementOne']);
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

        $this->assertEquals(
            array(
                'Example\\ElementOne' => array(
                    'xml_root_name' => 'ns-8ece61d2:element-one',
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
                            'type' => $phpName,
                            'xml_element' => array(
                                'cdata' => false
                            )
                        )
                    )
                )
            ), $classes['Example\ElementOne']);
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
        $this->assertCount(1, $classes);

        $this->assertEquals(array(
            'Example\\ElementOne' => array(
                'xml_root_name' => 'ns-8ece61d2:element-one',
                'xml_root_namespace' => 'http://www.example.com',
                'properties' => array(
                    '__value' => array(
                        'expose' => true,
                        'xml_value' => true,
                        'access_type' => 'public_method',
                        'accessor' => array(
                            'getter' => 'value',
                            'setter' => 'value',
                        ),
                        'type' => $jmsType,
                        'xml_element' => array(
                            'cdata' => false
                        )
                    ),
                ))
        ), $classes['Example\ElementOne']);
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

        $this->assertEquals(array(
            'Example\\ElementOne' => array(
                'xml_root_name' => 'ns-8ece61d2:element-one',
                'xml_root_namespace' => 'http://www.example.com',
                'properties' => array(
                    '__value' => array(
                        'expose' => true,
                        'xml_value' => true,
                        'access_type' => 'public_method',
                        'accessor' => array(
                            'getter' => 'value',
                            'setter' => 'value',
                        ),
                        'type' => $jmsType,
                        'xml_element' => array(
                            'cdata' => false
                        )
                    ),
                ),

            )
        ), $classes['Example\ElementOne']);

    }

    public function testGroupGeneralParts()
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
                            'type' => 'string',
                            'xml_element' => array(
                                'cdata' => false
                            )
                        )
                    )
                )
            ), $classes['Example\\Element1']);
    }
}
