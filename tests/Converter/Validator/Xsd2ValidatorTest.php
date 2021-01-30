<?php

namespace GoetasWebservices\Xsd\XsdToPhp\Tests\Converter\Validator;

use GoetasWebservices\XML\XSDReader\SchemaReader;
use GoetasWebservices\Xsd\XsdToPhp\Jms\YamlValidatorConverter;
use GoetasWebservices\Xsd\XsdToPhp\Naming\ShortNamingStrategy;

class Xsd2ValidatorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var YamlValidatorConverter
     */
    protected $converter;

    /**
     * @var SchemaReader
     */
    protected $reader;

    /**
     * Set up converter and reader properties.
     */
    public function setUp()
    {
        $this->converter = new YamlValidatorConverter(new ShortNamingStrategy());
        $this->converter->addNamespace('http://www.example.com', 'Example');

        $this->reader = new SchemaReader();
    }

    /**
     * Return classes coverted through YamlValidatorConverter.
     *
     * @param string $xml
     *
     * @return array
     */
    protected function getClasses($xml)
    {
        $schema = $this->reader->readString($xml);

        return $this->converter->convert([$schema]);
    }

    public function getRestrictionsValidations()
    {
        return [
            // enumeration / Choice->choices
            [
                '<xs:enumeration value="201115"/>
                <xs:enumeration value="203015"/>
                <xs:enumeration value="213150"/>
                <xs:enumeration value="225105"/>',
                [
                    [
                        'Choice' => [
                            'choices' => [
                                '201115',
                                '203015',
                                '213150',
                                '225105',
                            ],
                            'groups' => ['xsd_rules'],
                        ],
                    ],
                ],
            ],
            //            // fractionDigits / Regex
            //            //                / Range
            //            [
            //                '<xs:fractionDigits value="2"/>',
            //                [
            //                    [
            //                        'Regex' => '/^\-?(\\d+\.\\d{2})|\\d*$/',
            //                    ]
            //                ]
            //            ],
            //            // fractionDigits / Regex
            //            //                / Range
            //            [
            //                '<xs:totalDigits value="4"/>',
            //                [
            //                    [
            //                        'Regex' => '/^\-?[\\d]{0,4}$/',
            //                    ],
            //                ]
            //            ],
            // length / Length(min/max)
            [
                '<xs:length value="12"/>',
                [
                    [
                        'Length' => [
                            'min' => 12,
                            'max' => 12,
                            'groups' => ['xsd_rules'],
                        ],
                    ],
                ],
            ],
            // maxLength / Length(max)
            [
                '<xs:maxLength value="100"/>',
                [
                    [
                        'Length' => [
                            'max' => 100,
                            'groups' => ['xsd_rules'],
                        ],
                    ],
                ],
            ],
            // minLength / Length(min)
            [
                '<xs:minLength value="3"/>',
                [
                    [
                        'Length' => [
                            'min' => 3,
                            'groups' => ['xsd_rules'],
                        ],
                    ],
                ],
            ],
            // pattern / Regex
            [
                '<xs:pattern value="\\([0-9]{2}\\)\\s[0-9]{4}-[0-9]{4,5}"/>',
                [
                    [
                        'Regex' => [
                            'pattern' => '~\\([0-9]{2}\\)\\s[0-9]{4}-[0-9]{4,5}~',
                            'groups' => ['xsd_rules'],
                        ],
                    ],
                ],
            ],
            // maxExclusive / LessThan
            [
                '<xs:maxExclusive value="50"/>',
                [
                    [
                        'LessThan' => [
                            'value' => 50,
                            'groups' => ['xsd_rules'],
                        ],
                    ],
                ],
            ],
            // maxInclusive / LessThanOrEqual
            [
                '<xs:maxInclusive value="60"/>',
                [
                    [
                        'LessThanOrEqual' => [
                            'value' => 60,
                            'groups' => ['xsd_rules'],
                        ],
                    ],
                ],
            ],
            // minExclusive / GreaterThan
            [
                '<xs:minExclusive value="10"/>',
                [
                    [
                        'GreaterThan' => [
                            'value' => 10,
                            'groups' => ['xsd_rules'],
                        ],
                    ],
                ],
            ],
            // minInclusive / GreaterThanOrEqual
            [
                '<xs:minInclusive value="10"/>',
                [
                    [
                        'GreaterThanOrEqual' => [
                            'value' => 10,
                            'groups' => ['xsd_rules'],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * @dataProvider getRestrictionsValidations
     */
    public function testSimpleTypeWithValidations($xsRestrictions, $ymlValidations)
    {
        $xml = '
             <xs:schema targetNamespace="http://www.example.com" xmlns:xs="http://www.w3.org/2001/XMLSchema">
                <xs:element name="element-one">
                    <xs:simpleType>
                         <xs:restriction base="xs:string">
                            ' . $xsRestrictions . '
                         </xs:restriction>
                    </xs:simpleType>
                </xs:element>
                <xs:complexType name="type-one">
                    <xs:sequence>
                        <xs:element ref="element-one" minOccurs="0"/>
                    </xs:sequence>
                </xs:complexType>
               </xs:schema>
            ';

        $classes = $this->getClasses($xml);

        $this->assertCount(2, $classes);

        $this->assertEquals(
            [
                'Example\\TypeOneType' => [
                    'properties' => [
                        'elementOne' => $ymlValidations,
                    ],
                ],
            ], $classes['Example\TypeOneType']);
    }

    public function testComplexTypeWithRequired()
    {
        $content = '
             <xs:schema targetNamespace="http://www.example.com" xmlns:xs="http://www.w3.org/2001/XMLSchema"  xmlns:ex="http://www.example.com">
                <xs:complexType name="complexType-1">
                    <xs:sequence>
                        <xs:element name="column1" base="xs:string"/>
                    </xs:sequence>
                </xs:complexType>
            </xs:schema>
            ';
        $classes = $this->getClasses($content);

        $this->assertCount(1, $classes);

        $this->assertEquals(
            [
                'Example\\ComplexType1Type' => [
                    'properties' => [
                        'column1' => [
                            [
                                'NotNull' => [
                                    'groups' => ['xsd_rules'],
                                ],
                            ],
                        ],
                    ],
                ],
            ], $classes['Example\\ComplexType1Type']);
    }

    public function testComplexTypeWithNoRequired()
    {
        $content = '
             <xs:schema targetNamespace="http://www.example.com" xmlns:xs="http://www.w3.org/2001/XMLSchema"  xmlns:ex="http://www.example.com">
                <xs:complexType name="complexType-1">
                    <xs:sequence>
                        <xs:element name="column1" base="xs:string" minOccurs="0"/>
                    </xs:sequence>
                </xs:complexType>
            </xs:schema>
            ';
        $classes = $this->getClasses($content);

        $this->assertCount(0, $classes);
    }

    /**
     * @dataProvider getRestrictionsValidations
     */
    public function testComplexTypeWithRestrictionRequired($xsRestrictions, $ymlValidations)
    {
        $content = '
             <xs:schema targetNamespace="http://www.example.com" xmlns:xs="http://www.w3.org/2001/XMLSchema"  xmlns:ex="http://www.example.com">
                <xs:complexType name="complexType-1">
                    <xs:sequence>
                        <xs:element name="column1">
                            <xs:simpleType>
                                <xs:restriction base="xs:string">
                                    ' . $xsRestrictions . '
                                </xs:restriction>
                            </xs:simpleType>
                        </xs:element>
                    </xs:sequence>
                </xs:complexType>
            </xs:schema>
            ';
        $classes = $this->getClasses($content);

        $this->assertCount(1, $classes);

        $this->assertEquals(
            [
                'Example\\ComplexType1Type' => [
                    'properties' => [
                        'column1' => array_merge(
                            $ymlValidations,
                            [
                                [
                                    'NotNull' => [
                                        'groups' => ['xsd_rules'],
                                    ],
                                ],
                            ]
                        ),
                    ],
                ],
            ], $classes['Example\\ComplexType1Type'], print_r([array_merge(
            $ymlValidations,
            [
                [
                    'NotNull' => [
                        'groups' => ['xsd_rules'],
                    ],
                ],
            ]
        ), $classes['Example\\ComplexType1Type']], 1));
    }

    /**
     * @dataProvider getRestrictionsValidations
     */
    public function testComplexTypeWithRestrictionNoRequired($xsRestrictions, $ymlValidations)
    {
        $content = '
             <xs:schema targetNamespace="http://www.example.com" xmlns:xs="http://www.w3.org/2001/XMLSchema"  xmlns:ex="http://www.example.com">
                <xs:complexType name="complexType-1">
                    <xs:sequence>
                        <xs:element name="column1" minOccurs="0">
                            <xs:simpleType>
                                <xs:restriction base="xs:string">
                                    ' . $xsRestrictions . '
                                </xs:restriction>
                            </xs:simpleType>
                        </xs:element>
                    </xs:sequence>
                </xs:complexType>
            </xs:schema>
            ';
        $classes = $this->getClasses($content);

        $this->assertCount(1, $classes);

        $this->assertEquals(
            [
                'Example\\ComplexType1Type' => [
                    'properties' => [
                        'column1' => $ymlValidations,
                    ],
                ],
            ], $classes['Example\\ComplexType1Type']);
    }

    public function testComplexTypeWithArray_1()
    {
        $content = '
             <xs:schema targetNamespace="http://www.example.com" xmlns:xs="http://www.w3.org/2001/XMLSchema"  xmlns:ex="http://www.example.com">
                <xs:complexType name="complexType-1">
                    <xs:sequence>
                        <xs:element name="column1" type="xs:string" maxOccurs="unbounded"></xs:element>
                    </xs:sequence>
                </xs:complexType>
            </xs:schema>
            ';
        $classes = $this->getClasses($content);

        $this->assertCount(1, $classes);

        $this->assertEquals(
            [
                'Example\\ComplexType1Type' => [
                    'properties' => [
                        'column1' => [
                            [
                                'NotNull' => [
                                    'groups' => ['xsd_rules'],
                                ],
                            ],
                        ],
                    ],
                ],
            ], $classes['Example\\ComplexType1Type'], print_r($classes['Example\\ComplexType1Type'], 1));
    }

    public function testComplexTypeWithArray_2()
    {
        $content = '
             <xs:schema targetNamespace="http://www.example.com" xmlns:xs="http://www.w3.org/2001/XMLSchema"  xmlns:ex="http://www.example.com">
                <xs:complexType name="complexType-1">
                    <xs:sequence>
                        <xs:element name="column1" type="xs:string" minOccurs="1" maxOccurs="100"></xs:element>
                    </xs:sequence>
                </xs:complexType>
            </xs:schema>
            ';
        $classes = $this->getClasses($content);

        $this->assertCount(1, $classes);

        $this->assertEquals(
            [
                'Example\\ComplexType1Type' => [
                    'properties' => [
                        'column1' => [
                            [
                                'NotNull' => [
                                    'groups' => ['xsd_rules'],
                                ],
                            ],
                        ],
                    ],
                ],
            ], $classes['Example\\ComplexType1Type']);
    }

    public function testComplexTypeWithArray_3()
    {
        $content = '
             <xs:schema targetNamespace="http://www.example.com" xmlns:xs="http://www.w3.org/2001/XMLSchema"  xmlns:ex="http://www.example.com">
                <xs:complexType name="complexType-1">
                    <xs:sequence>
                        <xs:element name="column1" type="xs:string" minOccurs="0" maxOccurs="unbounded"></xs:element>
                    </xs:sequence>
                </xs:complexType>
            </xs:schema>
            ';
        $classes = $this->getClasses($content);

        $this->assertCount(0, $classes);
    }

    public function testComplexTypeWithElementArrayRestriction()
    {
        $content = '
             <xs:schema targetNamespace="http://www.example.com" xmlns:xs="http://www.w3.org/2001/XMLSchema"  xmlns:ex="http://www.example.com">
                <xs:complexType name="complexType-2">
                    <xs:sequence>
                        <xs:element name="protocols" maxOccurs="10">
                            <xs:simpleType>
                                <xs:restriction base="xs:string">
                                    <xs:minLength value="1"/>
                                    <xs:maxLength value="12"/>
                                </xs:restriction>
                            </xs:simpleType>          
                        </xs:element>
                    </xs:sequence>
                </xs:complexType>
            </xs:schema>
            ';

        $classes = $this->getClasses($content);

        $this->assertCount(1, $classes);

        $this->assertEquals(
            [
                'Example\\ComplexType2Type' => [
                    'properties' => [
                        'protocols' => [
                            [
                                'NotNull' => [
                                    'groups' => ['xsd_rules'],
                                ],
                            ],
                            [
                                'All' => [
                                    'groups' => ['xsd_rules'],
                                    'constraints' => [
                                        [
                                            'Length' => [
                                                'min' => 1,
                                            ],
                                        ],
                                        [
                                            'Length' => [
                                                'max' => 12,
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ], $classes['Example\\ComplexType2Type'], print_r($classes['Example\\ComplexType2Type'], 1));
    }

    public function testComplexTypeWithArrayNestedRestriction()
    {
        $content = '
             <xs:schema targetNamespace="http://www.example.com" xmlns:xs="http://www.w3.org/2001/XMLSchema"  xmlns:ex="http://www.example.com">
                <xs:complexType name="complexType-1">
                    <xs:sequence>
                        <xs:element name="protocols">
                            <xs:complexType>
                                <xs:sequence>
                                    <xs:element name="protocolNumber" maxOccurs="30">
                                        <xs:simpleType>
                                            <xs:restriction base="xs:string">
                                                <xs:minLength value="1"/>
                                                <xs:maxLength value="12"/>
                                            </xs:restriction>
                                        </xs:simpleType>
                                    </xs:element>
                                </xs:sequence>
                            </xs:complexType>
                        </xs:element>
                    </xs:sequence>
                </xs:complexType>
            </xs:schema>
            ';

        $classes = $this->getClasses($content);

        $this->assertCount(2, $classes);

        $this->assertEquals(
            [
                'Example\\ComplexType1Type\\ProtocolsAType' => [
                    'properties' => [
                        'protocolNumber' => [
                            [
                                'NotNull' => [
                                    'groups' => ['xsd_rules'],
                                ],
                            ],
                            [
                                'All' => [
                                    'groups' => ['xsd_rules'],
                                    'constraints' => [
                                        [
                                            'Length' => [
                                                'min' => 1,
                                            ],
                                        ],
                                        [
                                            'Length' => [
                                                'max' => 12,
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ], $classes['Example\\ComplexType1Type\\ProtocolsAType'], print_r($classes['Example\\ComplexType1Type\\ProtocolsAType'], 1));

        $this->assertEquals(
            [
                'Example\\ComplexType1Type' => [
                    'properties' => [
                        'protocols' => [
                            [
                                'NotNull' => [
                                    'groups' => ['xsd_rules'],
                                ],
                            ],
                            [
                                'Count' => [
                                    'min' => 1,
                                    'max' => 30,
                                    'groups' => ['xsd_rules'],
                                ],
                            ],
                            [
                                'Valid' => null,
                            ],
                        ],
                    ],
                ],
            ], $classes['Example\\ComplexType1Type'], print_r($classes['Example\\ComplexType1Type'], 1));
    }

    public function testComplexTypeWithElementArrayRestrictionNoRequired()
    {
        $content = '
             <xs:schema targetNamespace="http://www.example.com" xmlns:xs="http://www.w3.org/2001/XMLSchema"  xmlns:ex="http://www.example.com">
                <xs:complexType name="complexType-2">
                    <xs:sequence>
                        <xs:element name="protocols" minOccurs="0" maxOccurs="10">
                            <xs:simpleType>
                                <xs:restriction base="xs:string">
                                    <xs:minLength value="1"/>
                                    <xs:maxLength value="12"/>
                                </xs:restriction>
                            </xs:simpleType>          
                        </xs:element>
                    </xs:sequence>
                </xs:complexType>
            </xs:schema>
            ';

        $classes = $this->getClasses($content);

        $this->assertCount(1, $classes);

        $this->assertEquals(
            [
                'Example\\ComplexType2Type' => [
                    'properties' => [
                        'protocols' => [
                            //                            [
                            //                                'Count' => [
                            //                                    'max' => 10
                            //                                ]
                            //                            ],
                            [
                                'All' => [
                                    'groups' => ['xsd_rules'],
                                    'constraints' => [
                                        [
                                            'Length' => [
                                                'min' => 1,
                                            ],
                                        ],
                                        [
                                            'Length' => [
                                                'max' => 12,
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ], $classes['Example\\ComplexType2Type']);
    }

    public function testComplexTypeWithArrayNestedRestrictionNoRequired_1()
    {
        $content = '
             <xs:schema targetNamespace="http://www.example.com" xmlns:xs="http://www.w3.org/2001/XMLSchema"  xmlns:ex="http://www.example.com">
                <xs:complexType name="complexType-1">
                    <xs:sequence>
                        <xs:element name="protocols">
                            <xs:complexType>
                                <xs:sequence>
                                    <xs:element name="protocolNumber" minOccurs="0" maxOccurs="30">
                                        <xs:simpleType>
                                            <xs:restriction base="xs:string">
                                                <xs:minLength value="1"/>
                                                <xs:maxLength value="12"/>
                                            </xs:restriction>
                                        </xs:simpleType>
                                    </xs:element>
                                </xs:sequence>
                            </xs:complexType>
                        </xs:element>
                    </xs:sequence>
                </xs:complexType>
            </xs:schema>
            ';

        $classes = $this->getClasses($content);

        $this->assertCount(2, $classes);

        $this->assertEquals(
            [
                'Example\\ComplexType1Type\\ProtocolsAType' => [
                    'properties' => [
                        'protocolNumber' => [
                            [
                                'All' => [
                                    'groups' => ['xsd_rules'],
                                    'constraints' => [
                                        [
                                            'Length' => [
                                                'min' => 1,
                                            ],
                                        ],
                                        [
                                            'Length' => [
                                                'max' => 12,
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ], $classes['Example\\ComplexType1Type\\ProtocolsAType']);

        $this->assertEquals(
            [
                'Example\\ComplexType1Type' => [
                    'properties' => [
                        'protocols' => [
                            [
                                'NotNull' => [
                                    'groups' => ['xsd_rules'],
                                ],
                            ],
                            [
                                'Count' => [
                                    'max' => 30,
                                    'groups' => ['xsd_rules'],
                                ],
                            ],
                            [
                                'Valid' => null,
                            ],
                        ],
                    ],
                ],
            ], $classes['Example\\ComplexType1Type']);
    }

    public function testComplexTypeWithArrayNestedRestrictionNoRequired_2()
    {
        $content = '
             <xs:schema targetNamespace="http://www.example.com" xmlns:xs="http://www.w3.org/2001/XMLSchema"  xmlns:ex="http://www.example.com">
                <xs:complexType name="complexType-1">
                    <xs:sequence>
                        <xs:element name="protocols" minOccurs="0">
                            <xs:complexType>
                                <xs:sequence>
                                    <xs:element name="protocolNumber" minOccurs="0" maxOccurs="30">
                                        <xs:simpleType>
                                            <xs:restriction base="xs:string">
                                                <xs:minLength value="1"/>
                                                <xs:maxLength value="12"/>
                                            </xs:restriction>
                                        </xs:simpleType>
                                    </xs:element>
                                </xs:sequence>
                            </xs:complexType>
                        </xs:element>
                    </xs:sequence>
                </xs:complexType>
            </xs:schema>
            ';

        $classes = $this->getClasses($content);

        $this->assertCount(2, $classes);

        $this->assertEquals(
            [
                'Example\\ComplexType1Type\\ProtocolsAType' => [
                    'properties' => [
                        'protocolNumber' => [
                            [
                                'All' => [
                                    'groups' => ['xsd_rules'],
                                    'constraints' => [
                                        [
                                            'Length' => [
                                                'min' => 1,
                                            ],
                                        ],
                                        [
                                            'Length' => [
                                                'max' => 12,
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ], $classes['Example\\ComplexType1Type\\ProtocolsAType']);

        $this->assertEquals(
            [
                'Example\\ComplexType1Type' => [
                    'properties' => [
                        'protocols' => [
                            [
                                'Count' => [
                                    'max' => 30,
                                    'groups' => ['xsd_rules'],
                                ],
                            ],
                            [
                                'Valid' => null,
                            ],
                        ],
                    ],
                ],
            ], $classes['Example\\ComplexType1Type']);
    }

    public function testComplexTypeWithNestedComplexRestrictionRequired()
    {
        $content = '
             <xs:schema targetNamespace="http://www.example.com" xmlns:xs="http://www.w3.org/2001/XMLSchema"  xmlns:ex="http://www.example.com">
                <xs:complexType name="complexType-1">
                    <xs:sequence>
                        <xs:element name="diagnostcs">
                            <xs:complexType>
                                <xs:sequence>
                                    <xs:element name="table" type="xs:string"/>
                                    <xs:element name="code">
                                        <xs:simpleType>
                                            <xs:restriction base="xs:string">
                                                <xs:minLength value="1"/>
                                                <xs:maxLength value="10"/>
                                            </xs:restriction>
                                        </xs:simpleType>
                                    </xs:element>
                                </xs:sequence>
                            </xs:complexType>
                        </xs:element>
                    </xs:sequence>
                </xs:complexType>
            </xs:schema>
            ';

        $classes = $this->getClasses($content);

        $this->assertCount(2, $classes);

        $this->assertEquals(
            [
                'Example\ComplexType1Type' => [
                    'properties' => [
                        'diagnostcs' => [
                            [
                                'NotNull' => [
                                    'groups' => ['xsd_rules'],
                                ],
                            ],
                            [
                                'Valid' => null,
                            ],
                        ],
                    ],
                ],
            ], $classes['Example\ComplexType1Type']);

        $this->assertEquals(
            [
                'Example\\ComplexType1Type\\DiagnostcsAType' => [
                    'properties' => [
                        'table' => [
                            [
                                'NotNull' => [
                                    'groups' => ['xsd_rules'],
                                ],
                            ],
                        ],
                        'code' => [
                            [
                                'Length' => [
                                    'min' => 1,
                                    'groups' => ['xsd_rules'],
                                ],
                            ],
                            [
                                'Length' => [
                                    'max' => 10,
                                    'groups' => ['xsd_rules'],
                                ],
                            ],
                            [
                                'NotNull' => [
                                    'groups' => ['xsd_rules'],
                                ],
                            ],
                        ],
                    ],
                ],
            ], $classes['Example\\ComplexType1Type\\DiagnostcsAType']);
    }

    public function testComplexTypeWithArrayNestedComplexRestrictionRequired()
    {
        $content = '
             <xs:schema targetNamespace="http://www.example.com" xmlns:xs="http://www.w3.org/2001/XMLSchema"  xmlns:ex="http://www.example.com">
                <xs:complexType name="complexType-1">
                    <xs:sequence>
                        <xs:element name="consult">
                            <xs:complexType>
                                <xs:sequence>
                                    <xs:element name="diagnostcs" maxOccurs="unbounded">
                                        <xs:complexType>
                                            <xs:sequence>
                                                <xs:element name="table" type="xs:string"/>
                                                <xs:element name="code">
                                                    <xs:simpleType>
                                                        <xs:restriction base="xs:string">
                                                            <xs:minLength value="1"/>
                                                            <xs:maxLength value="10"/>
                                                        </xs:restriction>
                                                    </xs:simpleType>
                                                </xs:element>
                                            </xs:sequence>
                                        </xs:complexType>
                                    </xs:element>
                                </xs:sequence>
                            </xs:complexType>
                        </xs:element>
                    </xs:sequence>
                </xs:complexType>
            </xs:schema>
            ';

        $classes = $this->getClasses($content);

        $this->assertCount(3, $classes);

        $this->assertEquals(
            [
                'Example\\ComplexType1Type' => [
                    'properties' => [
                        'consult' => [
                            [
                                'NotNull' => [
                                    'groups' => ['xsd_rules'],
                                ],
                            ],
                            [
                                'Count' => [
                                    'min' => 1,
                                    'groups' => ['xsd_rules'],
                                ],
                            ],
                            [
                                'Valid' => null,
                            ],
                        ],
                    ],
                ],
            ], $classes['Example\\ComplexType1Type']);

        $this->assertEquals(
            [
                'Example\\ComplexType1Type\\ConsultAType\\DiagnostcsAType' => [
                    'properties' => [
                        'table' => [
                            [
                                'NotNull' => [
                                    'groups' => ['xsd_rules'],
                                ],
                            ],
                        ],
                        'code' => [
                            [
                                'Length' => [
                                    'min' => 1,
                                    'groups' => ['xsd_rules'],
                                ],
                            ],
                            [
                                'Length' => [
                                    'max' => 10,
                                    'groups' => ['xsd_rules'],
                                ],
                            ],
                            [
                                'NotNull' => [
                                    'groups' => ['xsd_rules'],
                                ],
                            ],
                        ],
                    ],
                ],
            ], $classes['Example\\ComplexType1Type\\ConsultAType\\DiagnostcsAType']);

        $this->assertEquals(
            [
                'Example\\ComplexType1Type\\ConsultAType' => [
                    'properties' => [
                        'diagnostcs' => [
                            [
                                'NotNull' => [
                                    'groups' => ['xsd_rules'],
                                ],
                            ],
                            //                            [
                            //                                'Count' => [
                            //                                    'min' => 1
                            //                                ]
                            //                            ],
                            [
                                'Valid' => null,
                            ],
                        ],
                    ],
                ],
            ], $classes['Example\\ComplexType1Type\\ConsultAType']);
    }

    public function testComplexTypeWithExtension_1()
    {
        $content = '
            <xs:schema targetNamespace="http://www.example.com" xmlns:xs="http://www.w3.org/2001/XMLSchema"  xmlns:ex="http://www.example.com">
                <xs:complexType name="personinfo">
                    <xs:sequence>
                        <xs:element name="firstname" type="xs:string"/>
                        <xs:element name="lastname" type="xs:string"/>
                    </xs:sequence>
                </xs:complexType>
                <xs:complexType name="fullpersoninfo">
                    <xs:complexContent>
                        <xs:extension base="personinfo">
                            <xs:attribute name="lang" type="xs:string" use="required" /> 
                            <xs:sequence>
                                <xs:element name="address" type="xs:string"/>
                                <xs:element name="city" type="xs:string"/>
                                <xs:element name="country" type="xs:string"/>
                            </xs:sequence>
                        </xs:extension>
                    </xs:complexContent>
                </xs:complexType>
            </xs:schema>
            ';

        $classes = $this->getClasses($content);

        $this->assertCount(2, $classes);

        $this->assertEquals(
            [
                'Example\\FullpersoninfoType' => [
                    'properties' => [
                        'lang' => [
                            [
                                'NotNull' => [
                                    'groups' => ['xsd_rules'],
                                ],
                            ],
                        ],
                        'address' => [
                            [
                                'NotNull' => [
                                    'groups' => ['xsd_rules'],
                                ],
                            ],
                        ],
                        'city' => [
                            [
                                'NotNull' => [
                                    'groups' => ['xsd_rules'],
                                ],
                            ],
                        ],
                        'country' => [
                            [
                                'NotNull' => [
                                    'groups' => ['xsd_rules'],
                                ],
                            ],
                        ],
                    ],
                ],
            ], $classes['Example\\FullpersoninfoType']);

        $this->assertEquals(
            [
                'Example\\PersoninfoType' => [
                    'properties' => [
                        'firstname' => [
                            [
                                'NotNull' => [
                                    'groups' => ['xsd_rules'],
                                ],
                            ],
                        ],
                        'lastname' => [
                            [
                                'NotNull' => [
                                    'groups' => ['xsd_rules'],
                                ],
                            ],
                        ],
                    ],
                ],
            ], $classes['Example\\PersoninfoType']);
    }
}
