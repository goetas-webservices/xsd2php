<?php

namespace GoetasWebservices\Xsd\XsdToPhp\Tests\PHP;

use PHPUnit\Framework\TestCase;

class AnyTypePHPConversionTest extends TestCase
{
    use GetPhpYamlTrait;

    public function testSimpleAnyTypePHP()
    {
        $xml = '
            <xs:schema xmlns:xs="http://www.w3.org/2001/XMLSchema">
                <xs:complexType name="single">
                    <xs:all>
                        <xs:element name="id" type="xs:anyType"/>
                    </xs:all>
                </xs:complexType>
            </xs:schema>';

        $items = $this->getPhpClasses($xml, [
            ['http://www.w3.org/2001/XMLSchema', 'anyType', 'mixed'],
        ]);

        $this->assertCount(1, $items);

        $single = $items['Example\SingleType'];

        $this->assertTrue($single->hasMethod('getId'));
        $this->assertTrue($single->hasMethod('setId'));

        $returnTags = $single->getMethod('getId')->getDocBlock()->getTags();

        $this->assertCount(1, $returnTags);
        $this->assertEquals(['mixed'], $returnTags[0]->getTypes());
    }

    public function testSimpleAnyTypeYaml()
    {
        $xml = '
            <xs:schema xmlns:xs="http://www.w3.org/2001/XMLSchema">
                <xs:complexType name="single">
                    <xs:all>
                        <xs:element name="id" type="xs:anyType"/>
                    </xs:all>
                </xs:complexType>
            </xs:schema>';

        $items = $this->getYamlFiles($xml, [
            ['http://www.w3.org/2001/XMLSchema', 'anyType', 'My\Custom\MixedTypeHandler'],
        ]);

        $this->assertCount(1, $items);

        $single = $items['Example\SingleType'];

        $this->assertEquals([
            'Example\\SingleType' => [
                'properties' => [
                    'id' => [
                        'expose' => true,
                        'access_type' => 'public_method',
                        'serialized_name' => 'id',
                        'accessor' => [
                            'getter' => 'getId',
                            'setter' => 'setId',
                        ],
                        'type' => 'My\\Custom\\MixedTypeHandler',
                    ],
                ],
            ],
        ], $single);
    }
}
