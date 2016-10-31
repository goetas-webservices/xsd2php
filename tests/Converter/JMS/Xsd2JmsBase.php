<?php
namespace GoetasWebservices\Xsd\XsdToPhp\Tests\Converter\JMS;

use GoetasWebservices\Xsd\XsdToPhp\Jms\YamlConverter;
use GoetasWebservices\Xsd\XsdToPhp\Naming\ShortNamingStrategy;
use GoetasWebservices\XML\XSDReader\SchemaReader;

abstract class Xsd2JmsBase extends \PHPUnit_Framework_TestCase
{
    /**
     *
     * @var YamlConverter
     */
    protected $converter;

    /**
     *
     * @var SchemaReader
     */
    protected $reader;

    public function setUp()
    {
        $this->converter = new YamlConverter(new ShortNamingStrategy());
        $this->converter->addNamespace('http://www.example.com', "Example");

        $this->reader = new SchemaReader();
    }

    protected function getClasses($xml)
    {

        $schema = $this->reader->readString($xml);
        return $this->converter->convert(array($schema));

    }

    public function getBaseTypeConversions()
    {
        return [
            ['xs:dateTime', 'GoetasWebservices\\Xsd\\XsdToPhp\\XMLSchema\\DateTime'],
        ];
    }


    public function getPrimitiveTypeConversions()
    {
        return [
            ['xs:string', 'string'],
            ['xs:decimal', 'float'],
            ['xs:int', 'integer'],
            ['xs:integer', 'integer'],
        ];
    }
    
    public function getRestrictionsValidations() 
    {
        return [
            /**
             * enumeration / Choice->choices
             */
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
            /**
             * fractionDigits / Regex
             *                / Range
             */
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
            /**
             * fractionDigits / Regex
             *                / Range
             */
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
            /**
             * length / Length(min/max)
             */
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
            /**
             * maxLength / Length(max)
             */
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
            /**
             * minLength / Length(min)
             */
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
            /**
             * pattern / Regex
             */
            [
                '<xs:pattern value="\\([0-9]{2}\\)\\s[0-9]{4}-[0-9]{4,5}"/>',
                [
                    [
                        'Regex' => '/^\\([0-9]{2}\\)\\s[0-9]{4}-[0-9]{4,5}$/'
                    ]
                ]
            ],
            /**
             * maxExclusive / LessThan
             */
            [
                '<xs:maxExclusive value="50"/>',
                [
                    [
                        'LessThan' => 50
                    ]
                ]
            ],
            /**
             * maxInclusive / LessThanOrEqual
             */
            [
                '<xs:maxInclusive value="60"/>',
                [
                    [
                        'LessThanOrEqual' => 60
                    ]
                ]
            ],
            /**
             * minExclusive / GreaterThan
             */
            [
                '<xs:minExclusive value="10"/>',
                [
                    [
                        'GreaterThan' => 10
                    ]
                ]
            ],
            /**
             * minInclusive / GreaterThanOrEqual
             */
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
}