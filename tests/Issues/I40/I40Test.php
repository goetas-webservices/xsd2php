<?php

namespace GoetasWebservices\Xsd\XsdToPhp\Tests\Issues\I40;

use DMS\PHPUnitExtensions\ArraySubset\Assert;
use GoetasWebservices\XML\XSDReader\SchemaReader;
use GoetasWebservices\Xsd\XsdToPhp\Jms\YamlConverter;
use GoetasWebservices\Xsd\XsdToPhp\Naming\ShortNamingStrategy;
use GoetasWebservices\Xsd\XsdToPhp\Php\PhpConverter;
use PHPUnit\Framework\TestCase;

class I40Test extends TestCase
{
    public function testMissingClass()
    {
        $reader = new SchemaReader();
        $schema = $reader->readFile(__DIR__ . '/data.xsd');

        $yamlConv = new YamlConverter(new ShortNamingStrategy());
        $yamlConv->addNamespace('', 'Epa\\Schema');

        $yamlItems = $yamlConv->convert([$schema]);
        $this->assertEquals([
            'Epa\\Schema\\AdditionalIdentifiers' => [
                'Epa\\Schema\\AdditionalIdentifiers' => [
                    'xml_root_name' => 'additionalIdentifiers',
                ],
            ],
            'Epa\\Schema\\AdditionalIdentifier' => [
                'Epa\\Schema\\AdditionalIdentifier' => [
                    'xml_root_name' => 'additionalIdentifier',
                ],
            ],
            'Epa\\Schema\\AdditionalIdentifierType' => [
                'Epa\\Schema\\AdditionalIdentifierType' => [
                    'xml_root_name' => 'additionalIdentifierType',
                ],
            ],
            'Epa\\Schema\\AdditionalIdentifierType\\AdditionalIdentifierTypeAType' => [
                'Epa\\Schema\\AdditionalIdentifierType\\AdditionalIdentifierTypeAType' => [
                    'properties' => [
                        'id' => [
                            'expose' => true,
                            'access_type' => 'public_method',
                            'serialized_name' => 'id',
                            'accessor' => [
                                'getter' => 'getId',
                                'setter' => 'setId',
                            ],
                            'xml_attribute' => true,
                            'type' => 'int',
                        ],
                    ],
                ],
            ],
            'Epa\\Schema\\AdditionalIdentifier\\AdditionalIdentifierAType' => [
                'Epa\\Schema\\AdditionalIdentifier\\AdditionalIdentifierAType' => [
                    'properties' => [
                        'additionalIdentifierType' => [
                            'expose' => true,
                            'access_type' => 'public_method',
                            'serialized_name' => 'additionalIdentifierType',
                            'accessor' => [
                                'getter' => 'getAdditionalIdentifierType',
                                'setter' => 'setAdditionalIdentifierType',
                            ],
                            'type' => 'Epa\\Schema\\AdditionalIdentifierType',
                        ],
                    ],
                ],
            ],
            'Epa\\Schema\\AdditionalIdentifiers\\AdditionalIdentifiersAType' => [
                'Epa\\Schema\\AdditionalIdentifiers\\AdditionalIdentifiersAType' => [
                    'properties' => [
                        'additionalIdentifier' => [
                            'expose' => true,
                            'access_type' => 'public_method',
                            'serialized_name' => 'additionalIdentifier',
                            'accessor' => [
                                'getter' => 'getAdditionalIdentifier',
                                'setter' => 'setAdditionalIdentifier',
                            ],
                            'xml_list' => [
                                'inline' => true,
                                'entry_name' => 'additionalIdentifier',
                            ],
                            'type' => 'array<Epa\\Schema\\AdditionalIdentifier>',
                        ],
                    ],
                ],
            ],
            'Epa\\Schema\\AdditionalIdentifierTypes' => [
                'Epa\\Schema\\AdditionalIdentifierTypes' => [
                    'xml_root_name' => 'additionalIdentifierTypes',
                ],
            ],
            'Epa\\Schema\\AdditionalIdentifierTypes\\AdditionalIdentifierTypesAType' => [
                'Epa\\Schema\\AdditionalIdentifierTypes\\AdditionalIdentifierTypesAType' => [
                    'properties' => [
                        'additionalIdentifierType' => [
                            'expose' => true,
                            'access_type' => 'public_method',
                            'serialized_name' => 'additionalIdentifierType',
                            'accessor' => [
                                'getter' => 'getAdditionalIdentifierType',
                                'setter' => 'setAdditionalIdentifierType',
                            ],
                            'xml_list' => [
                                'inline' => true,
                                'entry_name' => 'additionalIdentifierType',
                            ],
                            'type' => 'array<Epa\\Schema\\AdditionalIdentifierType>',
                        ],
                    ],
                ],
            ],
        ], $yamlItems);

        $phpConv = new PhpConverter(new ShortNamingStrategy());
        $phpConv->addNamespace('', 'Epa\\Schema');

        $phpClasses = $phpConv->convert([$schema]);

        $this->assertCount(count($yamlItems), $phpClasses);
        ksort($yamlItems);
        ksort($phpClasses);
        Assert::assertArraySubset(array_keys($yamlItems), array_keys($phpClasses));
    }


    public function getTestDetectSimpleParents()
    {
         return [
             [__DIR__ . '/data_nested.xsd', 'string', [], []],
             [__DIR__ . '/data_nested_array.xsd', 'array<string>', [
                 'xml_list' =>
                     array (
                         'inline' => true,
                         'entry_name' => 'AdditionalIdentifierRootEl',
                     ),

             ], []],
// this will be done in the future
//             [__DIR__ . '/data_nested_array_nested.xsd', 'array<string>', [
//                 'xml_list' =>
//                     array (
//                         'inline' => false,
//                         'entry_name' => 'AdditionalIdentifierRootEl',
//                         'skip_when_empty' => false
//                     ),
//
//             ],[]],
         ];
    }
    /**
     * @dataProvider getTestDetectSimpleParents
     */
    public function testDetectSimpleParents(string $f, string $t, array $extra, array $extraTypes)
    {
        $reader = new SchemaReader();
        $schema = $reader->readFile($f);

        $yamlConv = new YamlConverter(new ShortNamingStrategy());
        $yamlConv->addNamespace('', 'Epa\\Schema');

        $yamlItems = $yamlConv->convert([$schema]);

        $this->assertEquals($extraTypes + [
                'Epa\Schema\AdditionalIdentifierRootEl' => [
                    'Epa\Schema\AdditionalIdentifierRootEl' => [
                        'xml_root_name' => 'AdditionalIdentifierRootEl',
                        'properties' => [
                            '__value' => [
                                'expose' => true,
                                'xml_value' => true,
                                'access_type' => 'public_method',
                                'accessor' => [
                                    'getter' => 'value',
                                    'setter' => 'value',
                                ],
                                'type' => 'string',
                            ],
                        ],
                    ]
                ],
            'Epa\\Schema\\UserType' => [
                'Epa\\Schema\\UserType' => [
                    'properties' => [
                        'additionalIdentifierRootEl' => $extra + [
                            'expose' => true,
                            'access_type' => 'public_method',
                            'serialized_name' => 'AdditionalIdentifierRootEl',
                            'accessor' => [
                                'getter' => 'getAdditionalIdentifierRootEl',
                                'setter' => 'setAdditionalIdentifierRootEl',
                            ],
                            'type' => $t,
                        ],
                    ],
                ],
            ],
            'Epa\\Schema\\AdditionalIdentifierRootElType' => [
                'Epa\\Schema\\AdditionalIdentifierRootElType' => [
                    'properties' => [
                        '__value' => [
                            'expose' => true,
                            'xml_value' => true,
                            'access_type' => 'public_method',
                            'accessor' => [
                                'getter' => 'value',
                                'setter' => 'value',
                            ],
                            'type' => 'string',
                        ],
                    ],
                ],
            ],
            'Epa\\Schema\\AdditionalIdentifierRootElParentType' => [
                'Epa\\Schema\\AdditionalIdentifierRootElParentType' => [
                    'properties' => [
                        '__value' => [
                            'expose' => true,
                            'xml_value' => true,
                            'access_type' => 'public_method',
                            'accessor' => [
                                'getter' => 'value',
                                'setter' => 'value',
                            ],
                            'type' => 'string',
                        ],
                    ],
                ],
            ],
        ], $yamlItems);
    }

    public function getTestDetectSimpleParentsWithAttributes()
    {
        return [
            [__DIR__ . '/data_nested_with_attributes.xsd', 'Epa\\Schema\\AdditionalIdentifierRootEl', []],
        ];
    }

    /**
     * @dataProvider getTestDetectSimpleParentsWithAttributes
     */
    public function testDetectSimpleParentsWithAttributesZ(string $f, string $t, array $extra)
    {
        $reader = new SchemaReader();
        $schema = $reader->readFile($f);

        $yamlConv = new YamlConverter(new ShortNamingStrategy());
        $yamlConv->addNamespace('', 'Epa\\Schema');

        $yamlItems = $yamlConv->convert([$schema]);

        $this->assertEquals([
            'Epa\\Schema\\AdditionalIdentifierRootEl' => [
                'Epa\\Schema\\AdditionalIdentifierRootEl' => [
                    'xml_root_name' => 'AdditionalIdentifierRootEl',
                ],
            ],
            'Epa\\Schema\\UserType' => [
                'Epa\\Schema\\UserType' => [
                    'properties' => [
                        'additionalIdentifierRootEl' => $extra + [
                            'expose' => true,
                            'access_type' => 'public_method',
                            'serialized_name' => 'AdditionalIdentifierRootEl',
                            'accessor' => [
                                'getter' => 'getAdditionalIdentifierRootEl',
                                'setter' => 'setAdditionalIdentifierRootEl',
                            ],
                            'type' => $t,
                        ],
                    ],
                ],
            ],
            'Epa\\Schema\\AdditionalIdentifierRootElType' => [
                'Epa\\Schema\\AdditionalIdentifierRootElType' => [
                    'properties' => [
                    ],
                ],
            ],
            'Epa\\Schema\\AdditionalIdentifierRootElParentType' => [
                'Epa\\Schema\\AdditionalIdentifierRootElParentType' => [
                    'properties' => [
                        '__value' => [
                            'expose' => true,
                            'xml_value' => true,
                            'access_type' => 'public_method',
                            'accessor' => [
                                'getter' => 'value',
                                'setter' => 'value',
                            ],
                            'type' => 'string',
                        ],
                        'idType' => [
                            'expose' => true,
                            'access_type' => 'public_method',
                            'serialized_name' => 'idType',
                            'accessor' => [
                                'getter' => 'getIdType',
                                'setter' => 'setIdType',
                            ],
                            'xml_attribute' => true,
                            'type' => 'string',
                        ],
                    ],
                ],
            ],
        ], $yamlItems);

        $phpConv = new PhpConverter(new ShortNamingStrategy());
        $phpConv->addNamespace('', 'Epa\\Schema');

        $phpClasses = $phpConv->convert([$schema]);

        $this->assertCount(count($yamlItems), $phpClasses);
        ksort($yamlItems);
        ksort($phpClasses);
        Assert::assertArraySubset(array_keys($yamlItems), array_keys($phpClasses));
    }

    public function testDetectSimpleParentsWithAttributesInTheMiddle()
    {
        $reader = new SchemaReader();
        $schema = $reader->readFile(__DIR__ . '/data_nested_with_attributes_in_the_middle.xsd');

        $yamlConv = new YamlConverter(new ShortNamingStrategy());
        $yamlConv->addNamespace('', 'Epa\\Schema');

        $yamlItems = $yamlConv->convert([$schema]);

        $this->assertEquals([
            'Epa\\Schema\\AdditionalIdentifierRootEl' => [
                'Epa\\Schema\\AdditionalIdentifierRootEl' => [
                    'xml_root_name' => 'AdditionalIdentifierRootEl',
                ],
            ],
            'Epa\\Schema\\UserType' => [
                'Epa\\Schema\\UserType' => [
                    'properties' => [
                        'additionalIdentifierRootEl' => [
                            'expose' => true,
                            'access_type' => 'public_method',
                            'serialized_name' => 'AdditionalIdentifierRootEl',
                            'accessor' => [
                                'getter' => 'getAdditionalIdentifierRootEl',
                                'setter' => 'setAdditionalIdentifierRootEl',
                            ],
                            'type' => 'Epa\\Schema\\AdditionalIdentifierRootEl',
                        ],
                    ],
                ],
            ],
            'Epa\\Schema\\AdditionalIdentifierRootElType' => [
                'Epa\\Schema\\AdditionalIdentifierRootElType' => [
                    'properties' => [
                        '__value' => [
                            'expose' => true,
                            'xml_value' => true,
                            'access_type' => 'public_method',
                            'accessor' => [
                                'getter' => 'value',
                                'setter' => 'value',
                            ],
                            'type' => 'string',
                        ],
                        'idType' => [
                            'expose' => true,
                            'access_type' => 'public_method',
                            'serialized_name' => 'idType',
                            'accessor' => [
                                'getter' => 'getIdType',
                                'setter' => 'setIdType',
                            ],
                            'xml_attribute' => true,
                            'type' => 'string',
                        ],
                    ],
                ],
            ],
            'Epa\\Schema\\AdditionalIdentifierRootElParentType' => [
                'Epa\\Schema\\AdditionalIdentifierRootElParentType' => [
                    'properties' => [
                        '__value' => [
                            'expose' => true,
                            'xml_value' => true,
                            'access_type' => 'public_method',
                            'accessor' => [
                                'getter' => 'value',
                                'setter' => 'value',
                            ],
                            'type' => 'string',
                        ],
                    ],
                ],
            ],
        ], $yamlItems);

        $phpConv = new PhpConverter(new ShortNamingStrategy());
        $phpConv->addNamespace('', 'Epa\\Schema');

        $phpClasses = $phpConv->convert([$schema]);

        ksort($yamlItems);
        ksort($phpClasses);

        $this->assertCount(count($yamlItems), $phpClasses);
        Assert::assertArraySubset(array_keys($yamlItems), array_keys($phpClasses));
    }
}
