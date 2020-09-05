<?php
namespace GoetasWebservices\Xsd\XsdToPhp\Tests\Converter\PHP;

use GoetasWebservices\Xsd\XsdToPhp\Php\Structure\PHPClassOf;

class Xsd2PhpGroupTest extends Xsd2PhpBase
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
        $this->assertInstanceOf('GoetasWebservices\Xsd\XsdToPhp\Php\Structure\PHPClass', $classes['Example\ComplexType1Type']);
    }

    public function testSomeAnonymous()
    {
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

        $this->assertInstanceOf('GoetasWebservices\Xsd\XsdToPhp\Php\Structure\PHPClass', $complexType1 = $classes['Example\ComplexType1Type']);
        $this->assertInstanceOf('GoetasWebservices\Xsd\XsdToPhp\Php\Structure\PHPClass', $s2 = $classes['Example\ComplexType1Type\String2AType']);

        $s1Prop = $complexType1->getProperty('string1');
        $this->assertSame('\string', $s1Prop->getType()->getFullName());

        $s2Prop = $complexType1->getProperty('string2');
        $this->assertSame($s2, $s2Prop->getType());

        $s3Prop = $complexType1->getProperty('string3');
        $this->assertSame('\string', $s3Prop->getType()->getFullName());

        $s4Prop = $complexType1->getProperty('string4');
        $this->assertSame('\string', $s4Prop->getType()->getFullName());

        $s5Prop = $complexType1->getProperty('string5');
        $this->assertSame('\string', $s5Prop->getType()->getFullName());

        $a1Prop = $complexType1->getProperty('att');
        $this->assertSame('\string', $a1Prop->getType()->getFullName());

    }

    public function testSomeAnonymousWithRefs()
    {
        $content = '
             <xs:schema targetNamespace="http://www.example.com" xmlns:xs="http://www.w3.org/2001/XMLSchema"  xmlns:ex="http://www.example.com">
                    <xs:complexType name="AddressBook">
                        <xs:sequence>
                            <xs:element ref="Contacts" minOccurs="0"/>                                                            
                        </xs:sequence>
                    </xs:complexType>    
                    <xs:element name="Contacts">
                        <xs:complexType>
                             <xs:sequence>
                                <xs:element name="Contact" maxOccurs="unbounded">
                                    <xs:complexType>
                                        <xs:sequence>
                                             <xs:element name="Phone" type="xs:string"/>        
                                        </xs:sequence>
                                    </xs:complexType>
                                </xs:element>                                                                         
                            </xs:sequence>
                        </xs:complexType>
                    
                    </xs:element>
            </xs:schema>
            ';
        $classes = $this->getClasses($content);

        $this->assertCount(4, $classes);


        $this->assertInstanceOf('GoetasWebservices\Xsd\XsdToPhp\Php\Structure\PHPClass', $book = $classes['Example\AddressBookType']);
        $this->assertInstanceOf('GoetasWebservices\Xsd\XsdToPhp\Php\Structure\PHPClass', $contacts = $classes['Example\Contacts']);
        $this->assertInstanceOf('GoetasWebservices\Xsd\XsdToPhp\Php\Structure\PHPClass', $contactsContact = $classes['Example\Contacts\ContactsAType\ContactAType']);
        $this->assertInstanceOf('GoetasWebservices\Xsd\XsdToPhp\Php\Structure\PHPClass', $contactsType = $classes['Example\Contacts\ContactsAType']);
        $this->assertSame($book->getProperty('contacts')->getType()->getArg()->getType()->getFullName(), $contactsContact->getFullName());
    }

    public function testListOfRestriction()
    {
        $xml = '
            <xs:schema targetNamespace="http://www.example.com" xmlns:xs="http://www.w3.org/2001/XMLSchema"  xmlns:ex="http://www.example.com">
            
                <xs:simpleType name="JustRestriction">
                    <xs:restriction base="xs:float"/>
                </xs:simpleType>            
                
                <xs:simpleType name="RestrictionOfJustRestriction">
                    <xs:restriction base="JustRestriction"/>
                </xs:simpleType>               
                
                <xs:simpleType name="ListOfXSDFloats">
                    <xs:list itemType="xs:float"/>
                </xs:simpleType>

                <xs:simpleType name="ListOfJustRestriction">
                    <xs:list itemType="JustRestriction"/>
                </xs:simpleType>
                
                <xs:simpleType name="ListOfRestrictionOfJustRestriction">
                    <xs:list itemType="JustRestriction"/>
                </xs:simpleType>
                                            
                <xs:simpleType name="ListOfListOfJustRestriction">
                    <xs:restriction base="ListOfJustRestriction"/>
                </xs:simpleType>
                 <xs:complexType name="ComplexListOfXSDFloats">
                  <xs:simpleContent>
                    <xs:extension base="ListOfXSDFloats"/>
                  </xs:simpleContent>
                </xs:complexType>
                
                <xs:complexType name="ComplexListOfListOfJustRestriction">
                  <xs:simpleContent>
                    <xs:extension base="ListOfListOfJustRestriction"/>
                  </xs:simpleContent>
                </xs:complexType>
                
                 <xs:complexType name="ElementsCt">
                    <xs:sequence>
                         <xs:element minOccurs="1" maxOccurs="1" name="AttListOfXSDFloats" type="ListOfXSDFloats"/>
                         <xs:element minOccurs="1" maxOccurs="1" name="AttListOfJustRestriction" type="ListOfJustRestriction"/>
                         <xs:element minOccurs="1" maxOccurs="1" name="AttListOfRestrictionOfJustRestriction" type="ListOfRestrictionOfJustRestriction"/>
                         <xs:element minOccurs="1" maxOccurs="1" name="AttListOfListOfJustRestriction" type="ListOfListOfJustRestriction"/>
                         
                         <xs:element minOccurs="1" maxOccurs="1" name="AttrComplexListOfXSDFloats" type="ComplexListOfXSDFloats"/>
                         <xs:element minOccurs="1" maxOccurs="1" name="AttrAnonComplexListOfXSDFloats">
                              <xs:simpleType>
                                <xs:restriction base="ListOfJustRestriction"/>
                            </xs:simpleType>
                         </xs:element>
                         <xs:element minOccurs="1" maxOccurs="1" name="AttrComplexListOfListOfJustRestriction" type="ComplexListOfListOfJustRestriction"/>
                     </xs:sequence>
                </xs:complexType>
                
                <xs:complexType name="Ct">
                     <xs:attribute name="AttListOfXSDFloats" type="ListOfXSDFloats"/>
                     <xs:attribute name="AttListOfJustRestriction" type="ListOfJustRestriction"/>
                     <xs:attribute name="AttListOfRestrictionOfJustRestriction" type="ListOfRestrictionOfJustRestriction"/>
                     <xs:attribute name="AttListOfListOfJustRestriction" type="ListOfListOfJustRestriction"/>
                     
                     <xs:attribute name="AttrComplexListOfXSDFloats" type="ComplexListOfXSDFloats"/>
                     <xs:attribute name="AttrComplexListOfListOfJustRestriction" type="ComplexListOfListOfJustRestriction"/>
                     
                     <xs:attribute name="AttrAnonComplexListOfXSDFloats">
                          <xs:simpleType>
                            <xs:restriction base="ListOfJustRestriction"/>
                        </xs:simpleType>
                     </xs:attribute>
                </xs:complexType>
            </xs:schema>';
        $classes = $this->getClasses($xml);

        $this->assertCount(2, $classes);
        $complexType = $classes['Example\CtType'];

        $properties = $complexType->getProperties();
        $this->assertCount(7, $properties);
        foreach ($properties as $property) {
            $type = $property->getType();
            self::assertTrue($type instanceof PHPClassOf); // are array

            $tt = $type->getArg()->getType();
            $typ = $tt->getPhpType();

            if (($p = $tt->isSimpleType()) && ($t = $p->getType())) {
                $typ = $t->getPhpType();
            }
            self::assertSame("float", $typ);
        }

        $complexType = $classes['Example\ElementsCtType'];

        $properties = $complexType->getProperties();
        $this->assertCount(7, $properties);
        foreach ($properties as $property) {
            $type = $property->getType();
            self::assertTrue($type instanceof PHPClassOf); // are array

            $tt = $type->getArg()->getType();
            $typ = $tt->getPhpType();

            if (($p = $tt->isSimpleType()) && ($t = $p->getType())) {
                $typ = $t->getPhpType();
            }
            self::assertSame("float", $typ);
        }
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

        $this->assertInstanceOf('GoetasWebservices\Xsd\XsdToPhp\Php\Structure\PHPClass', $complexType1 = $classes['Example\ComplexType1Type']);
        $this->assertInstanceOf('GoetasWebservices\Xsd\XsdToPhp\Php\Structure\PHPClass', $complexType2 = $classes['Example\ComplexType2Type']);

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

    public function testArray()
    {
        $content = '
             <xs:schema targetNamespace="http://www.example.com" xmlns:xs="http://www.w3.org/2001/XMLSchema"  xmlns:ex="http://www.example.com">
                   <xs:complexType name="complexType-1">
                        <xs:sequence>
                            <xs:element name="strings" type="ex:ArrayOfStrings"></xs:element>
                        </xs:sequence>
                    </xs:complexType>

                    <xs:complexType name="ArrayOfStrings">
                        <xs:sequence>
                            <xs:element name="string" type="xs:string" maxOccurs="unbounded" minOccurs="1"></xs:element>
                        </xs:sequence>
                    </xs:complexType>
            </xs:schema>
            ';
        $classes = $this->getClasses($content);
        $this->assertCount(1, $classes);
        $this->assertInstanceOf('GoetasWebservices\Xsd\XsdToPhp\Php\Structure\PHPClass', $complexType1 = $classes['Example\ComplexType1Type']);

        $property = $complexType1->getProperty('strings');

        $this->assertInstanceOf('GoetasWebservices\Xsd\XsdToPhp\Php\Structure\PHPClassOf', $typeOf = $property->getType());
        $this->assertInstanceOf('GoetasWebservices\Xsd\XsdToPhp\Php\Structure\PHPProperty', $typeProp = $typeOf->getArg());
        $this->assertInstanceOf('GoetasWebservices\Xsd\XsdToPhp\Php\Structure\PHPClass', $typePropType = $typeProp->getType());

        $this->assertEquals('', $typePropType->getNamespace());
        $this->assertEquals('string', $typePropType->getName());

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
        $this->assertInstanceOf('GoetasWebservices\Xsd\XsdToPhp\Php\Structure\PHPClass', $complexType1 = $classes['Example\ComplexType1Type']);

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

        $this->assertCount(1, $classes);

        $this->assertInstanceOf('GoetasWebservices\Xsd\XsdToPhp\Php\Structure\PHPClass', $complexType1 = $classes['Example\ComplexType1Type']);



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
