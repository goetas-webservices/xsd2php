<?php
namespace Goetas\Xsd\XsdToPhp\Tests\Converter\JMS;

class Xsd2PhpElementTest extends Xsd2JmsBase
{

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
                    'xml_root_name' => 'element-one',
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
                            'type' => $phpName
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
                    'xml_root_name' => 'element-one',
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
                            'type' => $phpName
                        )
                    )
                )
            ), $classes['Example\ElementOne']);
    }

    /**
     * @dataProvider getBaseTypeConversions
     */
    public function testElementOfBaseType($xsType, $phpName)
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
                'xml_root_name' => 'element-one',
                'xml_root_namespace' => 'http://www.example.com'
            )
        ), $classes['Example\ElementOne']);
    }

    /**
     * @dataProvider getBaseTypeConversions
     */
    public function testElementOfBaseTypeAnon($xsType, $phpName)
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

        $this->assertEquals(array(
            'Example\\ElementOne' => array(
                'xml_root_name' => 'element-one',
                'xml_root_namespace' => 'http://www.example.com'
            )
        ), $classes['Example\ElementOne']);

    }
}