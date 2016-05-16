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

        $classes = $this->converter->convert(array($this->reader->readString($content)));

        $this->assertCount(1, $classes);
        $this->assertInstanceOf('GoetasWebservices\Xsd\XsdToPhp\Php\Structure\PHPClass', $classes['Example\ElementOne']);
        $this->assertEquals('Example', $classes['Example\ElementOne']->getNamespace());
        $this->assertEquals('ElementOne', $classes['Example\ElementOne']->getName());

        $this->assertInstanceOf('GoetasWebservices\Xsd\XsdToPhp\Php\Structure\PHPClass', $classes['Example\ElementOne']->getExtends());

        $this->assertCount(0, $classes['Example\ElementOne']->getProperties());
        //$this->assertArrayHasKey("__value", $classes['Example\ElementOne']->getProperties());

        //$property = $classes['Example\ElementOne']->getProperty('__value');
        //$this->assertInstanceOf('GoetasWebservices\Xsd\XsdToPhp\Php\Structure\PHPProperty', $property);

        //$this->assertEquals('protected', $property->getVisibility());
        //$this->assertEquals('', $property->getType()->getNamespace());
        //$this->assertEquals($phpName, $property->getType()->getName());
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
        $classes = $this->converter->convert(array($this->reader->readString($content)));

        $this->assertCount(1, $classes);
        $this->assertInstanceOf('GoetasWebservices\Xsd\XsdToPhp\Php\Structure\PHPClass', $classes['Example\ElementOne']);
        $this->assertEquals('Example', $classes['Example\ElementOne']->getNamespace());
        $this->assertEquals('ElementOne', $classes['Example\ElementOne']->getName());

        $this->assertInstanceOf('GoetasWebservices\Xsd\XsdToPhp\Php\Structure\PHPClass', $classes['Example\ElementOne']->getExtends());

        $this->assertCount(0, $classes['Example\ElementOne']->getProperties());

        /*
        $this->assertArrayHasKey("__value", $classes['Example\ElementOne']->getProperties());

        $property = $classes['Example\ElementOne']->getProperty('__value');
        $this->assertInstanceOf('GoetasWebservices\Xsd\XsdToPhp\Php\Structure\PHPProperty', $property);

        $this->assertEquals('', $property->getType()->getNamespace());
        $this->assertEquals($phpName, $property->getType()->getName());
        */
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
        $classes = $this->converter->convert(array($this->reader->readString($content)));

        $this->assertCount(1, $classes);
        $this->assertInstanceOf('GoetasWebservices\Xsd\XsdToPhp\Php\Structure\PHPClass', $classes['Example\ElementOne']);
        $this->assertEquals('Example', $classes['Example\ElementOne']->getNamespace());
        $this->assertEquals('ElementOne', $classes['Example\ElementOne']->getName());

        $this->assertCount(0, $classes['Example\ElementOne']->getProperties());

        $extension = $classes['Example\ElementOne']->getExtends();
        $this->assertInstanceOf('GoetasWebservices\Xsd\XsdToPhp\Php\Structure\PHPClass', $extension);

        $this->assertEquals('', $extension->getNamespace());
        $this->assertEquals($phpName, $extension->getName());
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
        $classes = $this->converter->convert(array($this->reader->readString($content)));

        $this->assertCount(1, $classes);
        $this->assertInstanceOf('GoetasWebservices\Xsd\XsdToPhp\Php\Structure\PHPClass', $classes['Example\ElementOne']);
        $this->assertEquals('Example', $classes['Example\ElementOne']->getNamespace());
        $this->assertEquals('ElementOne', $classes['Example\ElementOne']->getName());

        $this->assertCount(0, $classes['Example\ElementOne']->getProperties());

        $extension = $classes['Example\ElementOne']->getExtends();
        $this->assertInstanceOf('GoetasWebservices\Xsd\XsdToPhp\Php\Structure\PHPClass', $extension);

        $this->assertEquals('', $extension->getNamespace());
        $this->assertEquals($phpName, $extension->getName());
    }
}