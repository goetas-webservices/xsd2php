<?php
namespace GoetasWebservices\Xsd\XsdToPhp\Tests\Converter\Validator;

use GoetasWebservices\Xsd\XsdToPhp\Jms\YamlValidatorConverter;
use GoetasWebservices\Xsd\XsdToPhp\Naming\ShortNamingStrategy;
use GoetasWebservices\XML\XSDReader\SchemaReader;

class Xsd2ValidatorTest extends \PHPUnit_Framework_TestCase
{
    /**
     *
     * @var YamlValidatorConverter
     */
    protected $converter;

    /**
     *
     * @var SchemaReader
     */
    protected $reader;

    public function setUp()
    {
        $this->converter = new YamlValidatorConverter(new ShortNamingStrategy());
        $this->converter->addNamespace('http://www.example.com', "Example");

        $this->reader = new SchemaReader();
    }

    protected function getClasses($xml)
    {

        $schema = $this->reader->readString($xml);
        return $this->converter->convert(array($schema));

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
                                '225105'
                            ]                           
                        ]
                    ]
                ]
            ],
            // fractionDigits / Regex
            //                / Range
            [
                '<xs:fractionDigits value="2"/>',
                [
                    [
                        'Regex' => '/^(\\d+\.\\d{1,2})|\\d*$/',
                    ], [
                        'Range' => [
                            'min' => 0
                        ]
                    ]
                ]
            ],
            // fractionDigits / Regex
            //                / Range
            [
                '<xs:totalDigits value="4"/>',
                [
                    [
                        'Regex' => '/^[\\d]{0,4}$/',
                    ], [
                        'Range' => [
                            'min' => 0
                        ]
                    ]
                ]
            ],
            // length / Length(min/max)
            [
                '<xs:length value="12"/>',
                [
                    [
                        'Length' => [
                            'min' => 12,
                            'max' => 12
                        ]
                    ]
                ]
            ],
            // maxLength / Length(max)
            [
                '<xs:maxLength value="100"/>',
                [
                    [
                        'Length' => [
                            'max' => 100
                        ]
                    ]
                ]
            ],
            // minLength / Length(min)
            [
                '<xs:minLength value="3"/>',
                [
                    [
                        'Length' => [
                            'min' => 3
                        ]
                    ]
                ]
            ],
            // pattern / Regex
            [
                '<xs:pattern value="\\([0-9]{2}\\)\\s[0-9]{4}-[0-9]{4,5}"/>',
                [
                    [
                        'Regex' => '/^\\([0-9]{2}\\)\\s[0-9]{4}-[0-9]{4,5}$/'
                    ]
                ]
            ],
            // maxExclusive / LessThan
            [
                '<xs:maxExclusive value="50"/>',
                [
                    [
                        'LessThan' => 50
                    ]
                ]
            ],
            // maxInclusive / LessThanOrEqual
            [
                '<xs:maxInclusive value="60"/>',
                [
                    [
                        'LessThanOrEqual' => 60
                    ]
                ]
            ],
            // minExclusive / GreaterThan
            [
                '<xs:minExclusive value="10"/>',
                [
                    [
                        'GreaterThan' => 10
                    ]
                ]
            ],
            // minInclusive / GreaterThanOrEqual
            [
                '<xs:minInclusive value="10"/>',
                [
                    [
                        'GreaterThanOrEqual' => 10
                    ]
                ]
            ]
        ];
                
    }
    
    /**
     * @dataProvider getRestrictionsValidations
     */
    public function testSimpleTypeWithValidations($xsRestrictions, $ymlValidations)
    {
        $xml = "
             <xs:schema targetNamespace=\"http://www.example.com\" xmlns:xs=\"http://www.w3.org/2001/XMLSchema\">
                <xs:element name=\"element-one\">
                    <xs:simpleType>
                         <xs:restriction base=\"xs:string\">
                            {$xsRestrictions}
                         </xs:restriction>
                    </xs:simpleType>
                </xs:element>
               </xs:schema>
            ";

        $classes = $this->getClasses($xml);
        
        $this->assertCount(1, $classes);

        $this->assertEquals(
            [
                'Example\\ElementOne' => [
                    'properties' => [
                        '__value' => $ymlValidations
                    ]
                ]
            ], $classes['Example\ElementOne']);
    }
    
    /**
     * @dataProvider getRestrictionsValidations
     */
    public function testComplexTypeWithValidations($xsRestrictions, $ymlValidations)
    {
        $content = '
             <xs:schema targetNamespace="http://www.example.com" xmlns:xs="http://www.w3.org/2001/XMLSchema"  xmlns:ex="http://www.example.com">
                <xs:complexType name="complexType-1">
                    <xs:sequence>
                        <xs:element name="column1">
                            <xs:simpleType>
                                <xs:restriction base="xs:string">
                                    '.$xsRestrictions.'
                                </xs:restriction>
                            </xs:simpleType>
                        </xs:element>
                        <xs:element name="column2" type="xs:string"></xs:element>
                        <xs:element name="column3" type="xs:string" minOccurs="0"></xs:element>
                        <xs:element name="column4" type="xs:string" maxOccurs="unbounded"></xs:element>
                        <xs:element name="column5" type="xs:string" minOccurs="1" maxOccurs="100"></xs:element>
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
                                    'NotNull' => null
                                ]
                            ]
                        ),                        
                        'column2' => [
                            [
                                'NotNull' => null
                            ]
                        ],
                        'column4' => [
                            [
                                'Count' => [
                                    'min' => 1
                                ]
                            ],
                            [
                                'Count' => [
                                    'min' => 1
                                ]
                            ],
                            [
                                'NotNull' => null
                            ]
                        ],
                        'column5' => [
                            [
                                'Count' => [
                                    'min' => 1,
                                    'max' => 100
                                ]
                            ],
                            [
                                'Count' => [
                                    'min' => 1
                                ]
                            ],
                            [
                                'NotNull' => null
                            ]
                        ],                      
                    ]
                ]
            ], $classes['Example\\ComplexType1Type']);

    }
    
}