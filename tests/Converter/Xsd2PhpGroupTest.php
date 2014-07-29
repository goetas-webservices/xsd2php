<?php
namespace Goetas\Xsd\XsdToPhp\Tests\Converter;

class Xsd2PhpGroupTest  extends Xsd2PhpBase
{

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

        $this->assertInstanceOf('Goetas\Xsd\XsdToPhp\Structure\PHPClass', $complexType1 = $classes['Example\ComplexType1']);
        $this->assertInstanceOf('Goetas\Xsd\XsdToPhp\Structure\PHPClass', $complexType2 = $classes['Example\ComplexType2']);

        $this->assertSame($complexType1, $complexType2->getExtends());

        $property = $complexType2->getProperty('complexType2Att1');
        $this->assertEquals('complexType2Att1', $property->getName());
        $this->assertEquals('', $property->getType()->getNamespace());
        $this->assertEquals('string', $property->getType()->getName());

        $property = $complexType2->getProperty('complexType2El1');
        $this->assertEquals('complexType2El1', $property->getName());
        $this->assertEquals('', $property->getType()->getNamespace());
        $this->assertEquals('string', $property->getType()->getName());

    }
    public function getMaxOccurs()
    {
        return [
            [null, false],
            ['1', false],
            /*
            ['2', true],
            ['3', true],
            ['10', true],
            ['unbounded', true]
            */
        ];
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
                            <xs:element '.($max!==null?(' maxOccurs="'.$max.'"'):"").' name="complexType-1-el-1" type="xs:string"/>
                     </xs:sequence>
                </xs:complexType>
            </xs:schema>
            ';
        $classes = $this->getClasses($content);
        $this->assertCount(1, $classes);
        $this->assertInstanceOf('Goetas\Xsd\XsdToPhp\Structure\PHPClass', $complexType1 = $classes['Example\ComplexType1']);

        $property = $complexType1->getProperty('complexType1El1');

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

        $this->assertCount(6, $classes);

        $this->assertInstanceOf('Goetas\Xsd\XsdToPhp\Structure\PHPTrait', $attGroup1 = $classes['Example\AttributeGroup1']);
        $this->assertInstanceOf('Goetas\Xsd\XsdToPhp\Structure\PHPTrait', $attGroup2 = $classes['Example\AttributeGroup2']);
        $this->assertInstanceOf('Goetas\Xsd\XsdToPhp\Structure\PHPTrait', $group1 = $classes['Example\Group1']);
        $this->assertInstanceOf('Goetas\Xsd\XsdToPhp\Structure\PHPTrait', $group2 = $classes['Example\Group2']);

        $this->assertInstanceOf('Goetas\Xsd\XsdToPhp\Structure\PHPClass', $complexType1 = $classes['Example\ComplexType1']);
        $this->assertInstanceOf('Goetas\Xsd\XsdToPhp\Structure\PHPClass', $element1 = $classes['Example\Element1']);

        $this->assertContains($attGroup2, $attGroup1->getTraits());
        $this->assertContains($group2, $group1->getTraits());

        $this->assertContains($attGroup1, $complexType1->getTraits());
        $this->assertContains($group1, $complexType1->getTraits());

        //$attGroup1
        $property = $attGroup1->getProperty('attributeGroup1Att1');
        $this->assertEquals('attributeGroup1Att1', $property->getName());
        $this->assertEquals('', $property->getType()->getNamespace());
        $this->assertEquals('string', $property->getType()->getName());

        $property = $attGroup1->getProperty('attribute1');
        $this->assertEquals('attribute1', $property->getName());
        $this->assertEquals('', $property->getType()->getNamespace());
        $this->assertEquals('string', $property->getType()->getName());

        //$attGroup2
        $property = $attGroup2->getProperty('attributeGroup2Att2');
        $this->assertEquals('attributeGroup2Att2', $property->getName());
        $this->assertEquals('', $property->getType()->getNamespace());
        $this->assertEquals('string', $property->getType()->getName());

        //$group1
        $property = $group1->getProperty('group1El1');
        $this->assertEquals('group1El1', $property->getName());
        $this->assertEquals('', $property->getType()->getNamespace());
        $this->assertEquals('string', $property->getType()->getName());

        //$group2
        $property = $group2->getProperty('group2El1');
        $this->assertEquals('group2El1', $property->getName());
        $this->assertEquals('', $property->getType()->getNamespace());
        $this->assertEquals('string', $property->getType()->getName());


        //$complexType1
        $property = $complexType1->getProperty('attribute1');
        $this->assertEquals('attribute1', $property->getName());
        $this->assertEquals('', $property->getType()->getNamespace());
        $this->assertEquals('string', $property->getType()->getName());

        $property = $complexType1->getProperty('attribute2');
        $this->assertEquals('attribute2', $property->getName());
        $this->assertEquals('', $property->getType()->getNamespace());
        $this->assertEquals('string', $property->getType()->getName());

        $property = $complexType1->getProperty('complexType1El1');
        $this->assertEquals('complexType1El1', $property->getName());
        $this->assertEquals('', $property->getType()->getNamespace());
        $this->assertEquals('string', $property->getType()->getName());

    }

}