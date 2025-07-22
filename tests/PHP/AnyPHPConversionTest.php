<?php

namespace GoetasWebservices\Xsd\XsdToPhp\Tests\PHP;

use PHPUnit\Framework\TestCase;

class AnyPHPConversionTest extends TestCase
{
    use GetPhpYamlTrait;

    protected const XML = '
        <xs:schema xmlns:xs="http://www.w3.org/2001/XMLSchema">
            <xs:complexType name="AnyXMLContent">
                <xs:sequence>
                    <xs:any minOccurs="0"
                            maxOccurs="unbounded"
                            namespace="##any"
                            processContents="skip"/>
                </xs:sequence>
            </xs:complexType>
            <xs:complexType name="FormulardateiXML">
                <xs:sequence>
                    <xs:element name="xmldaten" type="AnyXMLContent"/>
                </xs:sequence>
            </xs:complexType>
        </xs:schema>';

    public function testSimpleAnyPHP(): void
    {
        $items = $this->getPhpClasses(self::XML);

        $this->assertCount(2, $items);

        $type = $items['Example\FormulardateiXMLType'];
        $method = $type->getMethod('setXmldaten');
        $this->assertTrue($method !== false);
        $this->assertTrue($method->getParameters()['xmldaten']->getType() === 'Example\AnyXMLContentType');

        $type = $items['Example\AnyXMLContentType'];
        $method = $type->getMethod('setAny');
        $this->assertTrue($method !== false);
        $this->assertTrue($method->getParameters()['any']->getType() === null);
    }

    public function testSimpleAnyTypeYaml()
    {
        $items = $this->getYamlFiles(self::XML);

        $this->assertCount(2, $items);
        $this->assertEquals([
            'Example\\AnyXMLContentType' => [
                'properties' => [
                    'any' => [
                        'expose' => true,
                        'access_type' => 'public_method',
                        'serialized_name' => 'any',
                        'accessor' => [
                            'getter' => 'getAny',
                            'setter' => 'setAny',
                        ],
                    ],
                ],
            ],
        ], $items['Example\AnyXMLContentType']);
        $this->assertEquals([
            'Example\\FormulardateiXMLType' => [
                'properties' => [
                    'xmldaten' => [
                        'expose' => true,
                        'access_type' => 'public_method',
                        'serialized_name' => 'xmldaten',
                        'accessor' => [
                            'getter' => 'getXmldaten',
                            'setter' => 'setXmldaten',
                        ],
                        'type' => 'Example\\AnyXMLContentType',
                    ],
                ],
            ],
        ], $items['Example\FormulardateiXMLType']);
    }
}
