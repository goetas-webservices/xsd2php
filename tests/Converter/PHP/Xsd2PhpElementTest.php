<?php

namespace GoetasWebservices\Xsd\XsdToPhp\Tests\Converter\PHP;

class Xsd2PhpElementTest extends Xsd2PhpBase
{
    /**
     * @dataProvider getPrimitiveTypeConversions
     */
    public function testElementOfPrimitiveType($xsType, $phpName)
    {
        $content = '
             <xs:schema targetNamespace="http://www.example.com" xmlns:xs="http://www.w3.org/2001/XMLSchema">
                <xs:element name="element-one" type="' . $xsType . '">

                </xs:element>
               </xs:schema>
            ';

        $classes = $this->converter->convert([$this->reader->readString($content)]);

        $this->assertCount(0, $classes);
    }

    /**
     * @dataProvider getPrimitiveTypeConversions
     */
    public function testElementOfPrimitiveTypeAnon($xsType, $phpName)
    {
        $content = '
             <xs:schema targetNamespace="http://www.example.com" xmlns:xs="http://www.w3.org/2001/XMLSchema">
                <xs:element name="element-one">
                    <xs:simpleType>
                         <xs:restriction base="' . $xsType . '">
                         </xs:restriction>
                    </xs:simpleType>
                </xs:element>
               </xs:schema>
            ';
        $classes = $this->converter->convert([$this->reader->readString($content)]);

        $this->assertCount(1, $classes);
    }

    /**
     * @dataProvider getBaseTypeConversions
     */
    public function testElementOfBaseType($xsType, $phpName)
    {
        $content = '
             <xs:schema targetNamespace="http://www.example.com" xmlns:xs="http://www.w3.org/2001/XMLSchema">
                <xs:element name="element-one" type="' . $xsType . '">
                </xs:element>
               </xs:schema>
            ';
        $classes = $this->converter->convert([$this->reader->readString($content)]);

        $this->assertCount(0, $classes);
    }

    /**
     * @dataProvider getBaseTypeConversions
     */
    public function testElementOfBaseTypeAnon($xsType, $phpName)
    {
        $content = '
             <xs:schema targetNamespace="http://www.example.com" xmlns:xs="http://www.w3.org/2001/XMLSchema">
                <xs:element name="element-one">
                    <xs:simpleType>
                         <xs:restriction base="' . $xsType . '">
                         </xs:restriction>
                    </xs:simpleType>
                </xs:element>
               </xs:schema>
            ';
        $classes = $this->converter->convert([$this->reader->readString($content)]);

        $this->assertCount(1, $classes);
    }
}
