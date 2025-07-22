<?php

namespace GoetasWebservices\Xsd\XsdToPhp\Tests\PHP;

use PHPUnit\Framework\TestCase;

class PHPConversionTest extends TestCase
{
    use GetPhpYamlTrait;

    public function testSimpleContent(): void
    {
        $xml = '
            <xs:schema xmlns:xs="http://www.w3.org/2001/XMLSchema">
                <xs:complexType name="single">
                    <xs:simpleContent>
    					<xs:extension base="xs:string">
    						<xs:attribute name="code" type="xs:string"/>
    					</xs:extension>
				    </xs:simpleContent>
                </xs:complexType>
            </xs:schema>';

        $items = $this->getPhpClasses($xml);
        $this->assertCount(1, $items);

        $codegen = $items['Example\SingleType'];

        $this->assertTrue($codegen->hasMethod('value'));
        $this->assertTrue($codegen->hasMethod('__construct'));
        $this->assertTrue($codegen->hasMethod('__toString'));

        $this->assertTrue($codegen->hasMethod('getCode'));
        $this->assertTrue($codegen->hasMethod('setCode'));
    }

    public function testSimpleNoAttributesContent(): void
    {
        $xml = '
            <xs:schema xmlns:xs="http://www.w3.org/2001/XMLSchema">
                <xs:complexType name="single">
                    <xs:simpleContent>
    					<xs:extension base="xs:string"/>
				    </xs:simpleContent>
                </xs:complexType>
                <xs:simpleType name="double">
                    <xs:restriction base="xs:string"/>
                </xs:simpleType>
            </xs:schema>';

        $items = $this->getPhpClasses($xml);
        $this->assertCount(1, $items);

        $codegen = $items['Example\SingleType'];

        $this->assertTrue($codegen->hasMethod('value'));
        $this->assertTrue($codegen->hasMethod('__construct'));
        $this->assertTrue($codegen->hasMethod('__toString'));
    }

    public function testNoMulteplicity(): void
    {
        $xml = '
            <xs:schema xmlns:xs="http://www.w3.org/2001/XMLSchema">
                <xs:complexType name="single">
                    <xs:all>
                        <xs:element name="id" type="xs:long" minOccurs="0"/>
                    </xs:all>
                </xs:complexType>
            </xs:schema>';

        $items = $this->getPhpClasses($xml);
        $this->assertCount(1, $items);

        $codegen = $items['Example\SingleType'];
        $this->assertFalse($codegen->hasMethod('issetId'));
        $this->assertFalse($codegen->hasMethod('unsetId'));

        $this->assertTrue($codegen->hasMethod('getId'));
        $this->assertTrue($codegen->hasMethod('setId'));
    }

    public function testMulteplicity(): void
    {
        $xml = '
            <xs:schema xmlns:xs="http://www.w3.org/2001/XMLSchema">
                <xs:complexType name="single">
                    <xs:all>
                        <xs:element name="id" type="ary" minOccurs="0"/>
                    </xs:all>
                </xs:complexType>
                <xs:complexType name="ary">
                    <xs:all>
                        <xs:element name="id" type="xs:long" maxOccurs="2"/>
                    </xs:all>
                </xs:complexType>
            </xs:schema>';

        $items = $this->getPhpClasses($xml);

        $this->assertCount(2, $items);

        $codegen = $items['Example\SingleType'];
        $this->assertTrue($codegen->hasMethod('issetId'));
        $this->assertTrue($codegen->hasMethod('unsetId'));

        $this->assertTrue($codegen->hasMethod('getId'));
        $this->assertTrue($codegen->hasMethod('setId'));

        $this->assertNull($codegen->getMethod('issetId')->getParameters()['index']->getType());
        $this->assertNull($codegen->getMethod('issetId')->getParameters()['index']->getType());
    }

    public function testNestedMulteplicity(): void
    {
        $xml = '
            <xs:schema xmlns:xs="http://www.w3.org/2001/XMLSchema">
                <xs:complexType name="single">
                    <xs:all>
                        <xs:element name="id" type="ary" minOccurs="0"/>
                    </xs:all>
                </xs:complexType>
                <xs:complexType name="ary">
                    <xs:all>
                        <xs:element name="idA" type="ary2" maxOccurs="2"/>
                    </xs:all>
                </xs:complexType>
                <xs:complexType name="ary2">
                    <xs:all>
                        <xs:element name="idB" type="xs:long" maxOccurs="2"/>
                    </xs:all>
                </xs:complexType>
            </xs:schema>';

        $items = $this->getPhpClasses($xml);

        $this->assertCount(3, $items);

        $single = $items['Example\SingleType'];
        $this->assertTrue($single->hasMethod('issetId'));
        $this->assertTrue($single->hasMethod('unsetId'));

        $this->assertTrue($single->hasMethod('getId'));
        $this->assertTrue($single->hasMethod('setId'));

        $ary = $items['Example\Ary2Type'];
        $this->assertTrue($ary->hasMethod('issetIdB'));
        $this->assertTrue($ary->hasMethod('unsetIdB'));

        $this->assertTrue($ary->hasMethod('getIdB'));
        $this->assertTrue($ary->hasMethod('setIdB'));

        $ary = $items['Example\AryType'];
        $this->assertTrue($ary->hasMethod('addToIdA'));
    }

    public function testMultipleArrayTypes()
    {
        $xml = '
            <xs:schema xmlns:xs="http://www.w3.org/2001/XMLSchema">
                <xs:complexType name="ArrayOfStrings">
                    <xs:all>
                        <xs:element name="string" type="xs:string" maxOccurs="unbounded"/>
                    </xs:all>
                </xs:complexType>
                <xs:complexType name="Single">
                    <xs:all>
                        <xs:element name="a" type="ArrayOfStrings"/>
                        <xs:element name="b" type="ArrayOfStrings"/>
                    </xs:all>
                </xs:complexType>
            </xs:schema>';

        $items = $this->getPhpClasses($xml);

        $this->assertCount(2, $items);

        $single = $items['Example\SingleType'];
        $this->assertTrue($single->hasMethod('addToA'));
        $this->assertTrue($single->hasMethod('addToB'));

        $this->assertNotEmpty($single->getMethod('addToB')->getParameters()['string']);

        // this is not $items['Example\ArrayOfStrings']; important
    }

    public function testSimpleMulteplicity()
    {
        $xml = '
            <xs:schema xmlns:xs="http://www.w3.org/2001/XMLSchema">
                <xs:complexType name="single">
                    <xs:all>
                        <xs:element name="id" type="ary" minOccurs="0"/>
                    </xs:all>
                </xs:complexType>
                <xs:simpleType name="ary">
                    <xs:list itemType="xs:integer" />
                </xs:simpleType>
            </xs:schema>';

        $items = $this->getPhpClasses($xml);

        $this->assertCount(1, $items);

        $single = $items['Example\SingleType'];
        $this->assertTrue($single->hasMethod('issetId'));
        $this->assertTrue($single->hasMethod('unsetId'));

        $this->assertTrue($single->hasMethod('getId'));
        $this->assertTrue($single->hasMethod('setId'));
    }

    public function testNillableElement()
    {
        $xml = '
            <xs:schema xmlns:xs="http://www.w3.org/2001/XMLSchema">
                <xs:complexType name="single">
                    <xs:all>
                        <xs:element name="date1" type="xs:date" nillable="true"/>
                        <xs:element name="date2" type="xs:date"/>
                        <xs:element name="str1" type="xs:string" nillable="true"/>
                        <xs:element name="str2" type="xs:string"/>
                    </xs:all>
                </xs:complexType>
            </xs:schema>';

        $items = $this->getPhpClasses($xml);
        $codegen = $items['Example\SingleType'];

        $this->assertTrue($codegen->hasMethod('setDate1'));
        $this->assertNull($codegen->getMethod('setDate1')->getParameters()['date1']->getDefaultValue());
        $this->assertTrue($codegen->hasMethod('setDate2'));
        $this->assertNull($codegen->getMethod('setDate2')->getParameters()['date2']->getDefaultValue());
        $this->assertTrue($codegen->hasMethod('setStr1'));
        $this->assertNull($codegen->getMethod('setStr1')->getParameters()['str1']->getDefaultValue());
        $this->assertTrue($codegen->hasMethod('setStr2'));
        $this->assertNull($codegen->getMethod('setStr2')->getParameters()['str2']->getDefaultValue());
    }

    public function testNoCodeDuplicationInExtendingClass(): void
    {
        $xml = '
            <xs:schema xmlns:xs="http://www.w3.org/2001/XMLSchema">
                <xs:complexType name="Code">
                    <xs:sequence>
                        <xs:element name="code" type="xs:token" form="unqualified"/>
                        <xs:element name="name" type="xs:normalizedString" form="unqualified" fixed="foo"/>
                    </xs:sequence>
                    <xs:attribute name="listURI" type="xs:anyURI" use="optional"/>
                    <xs:attribute name="listVersionID" type="xs:normalizedString" use="optional"/>
                </xs:complexType>
                <xs:simpleType name="Codeliste.Zeitserientyp">
                    <xs:restriction base="xs:token">
                      <xs:enumeration value="01"/>
                      <xs:enumeration value="02"/>
                    </xs:restriction>
                </xs:simpleType>
                <xs:complexType name="Code.Zeitserientyp">
                    <xs:complexContent>
                      <xs:restriction base="Code">
                        <xs:sequence>
                          <xs:element name="code" type="Codeliste.Zeitserientyp" form="unqualified"/>
                        </xs:sequence>
                        <xs:attribute name="listURI" type="xs:anyURI" use="optional" fixed="urn:xoev-de:fim:codeliste:xzufi.zeitserientyp"/>
                        <xs:attribute name="listVersionID" type="xs:normalizedString" use="optional" fixed="1.1"/>
                      </xs:restriction>
                    </xs:complexContent>
                  </xs:complexType>
            </xs:schema>';

        $items = $this->getPhpClasses($xml);
        $codeType = $items['Example\CodeType'];
        $codeZeitserientypType = $items['Example\CodeZeitserientypType'];

        $this->assertTrue($codeType->hasMethod('setCode'));
        $this->assertTrue($codeType->hasMethod('getCode'));
        $this->assertTrue($codeType->hasMethod('setName'));
        $this->assertTrue($codeType->hasMethod('getName'));
        $this->assertTrue($codeType->hasMethod('setListURI'));
        $this->assertTrue($codeType->hasMethod('getListURI'));
        $this->assertNull($codeType->getProperty('listURI')->getDefaultValue());
        $this->assertTrue($codeType->hasMethod('setListVersionID'));
        $this->assertTrue($codeType->hasMethod('getListVersionID'));
        $this->assertNull($codeType->getProperty('listVersionID')->getDefaultValue());
        $this->assertTrue($codeZeitserientypType->getExtendedClass() === 'Example\CodeType');
        $this->assertFalse($codeZeitserientypType->hasMethod('setCode'));
        $this->assertFalse($codeZeitserientypType->hasMethod('getCode'));
        $this->assertFalse($codeZeitserientypType->hasMethod('setName'));
        $this->assertFalse($codeZeitserientypType->hasMethod('getName'));
        $this->assertFalse($codeZeitserientypType->hasMethod('setListURI'));
        $this->assertFalse($codeZeitserientypType->hasMethod('getListURI'));
        $value = $codeZeitserientypType->getProperty('listURI')->getDefaultValue()->getValue();
        $this->assertTrue($value === "urn:xoev-de:fim:codeliste:xzufi.zeitserientyp");
        $this->assertFalse($codeZeitserientypType->hasMethod('setListVersionID'));
        $this->assertFalse($codeZeitserientypType->hasMethod('getListVersionID'));
        $value = $codeZeitserientypType->getProperty('listVersionID')->getDefaultValue()->getValue();
        $this->assertTrue($value === "1.1");
    }
}
