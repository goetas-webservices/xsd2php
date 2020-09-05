<?php
namespace GoetasWebservices\Xsd\XsdToPhp\Tests\Issues\I40;

use GoetasWebservices\Xsd\XsdToPhp\Jms\YamlConverter;
use GoetasWebservices\Xsd\XsdToPhp\Naming\ShortNamingStrategy;
use GoetasWebservices\Xsd\XsdToPhp\Php\ClassGenerator;
use GoetasWebservices\Xsd\XsdToPhp\Php\PathGenerator\Psr4PathGenerator as PhpPsr4PathGenerator;
use GoetasWebservices\Xsd\XsdToPhp\Php\PhpConverter;
use GoetasWebservices\Xsd\XsdToPhp\Php\Structure\PHPClass;
use GoetasWebservices\Xsd\XsdToPhp\Php\Structure\PHPProperty;
use GoetasWebservices\XML\XSDReader\SchemaReader;
use GoetasWebservices\Xsd\XsdToPhp\Writer\PHPClassWriter;
use GoetasWebservices\Xsd\XsdToPhp\Writer\PHPWriter;

class I40Test extends \PHPUnit_Framework_TestCase
{

    public function testMissingClass()
    {
        $expectedItems = array(
            'Epa\\Schema\\AdditionalIdentifier',
            'Epa\\Schema\\AdditionalIdentifierType',
            'Epa\\Schema\\AdditionalIdentifierTypes',
            'Epa\\Schema\\AdditionalIdentifiers',
        );

        /*
         * FArray
(


    [2] => Epa\Schema\AdditionalIdentifier\AdditionalIdentifierTypeAType
)

         */
        $expectedItems = array_combine($expectedItems, $expectedItems);

        $reader = new SchemaReader();
        $schema = $reader->readFile(__DIR__ . '/data.xsd');

        $yamlConv = new YamlConverter(new ShortNamingStrategy());
        $yamlConv->addNamespace('', 'Epa\\Schema');

        $yamlItems = $yamlConv->convert([$schema]);
        print_r(array_keys($yamlItems));
//        print_r($yamlItems);
//        $this->assertCount(count($expectedItems), $yamlItems);
        $this->assertEmpty(array_diff_key($expectedItems, $yamlItems));

        $phpConv = new PhpConverter(new ShortNamingStrategy());
        $phpConv->addNamespace('', 'Epa\\Schema');

        $phpClasses = $phpConv->convert([$schema]);

        $this->assertCount(count($expectedItems), $phpClasses);
        $this->assertEmpty(array_diff_key($expectedItems, $phpClasses));

        $yamlClass = $yamlItems['Epa\\Schema\\AdditionalIdentifier']['Epa\\Schema\\AdditionalIdentifier'];
        $yamlProperty = $yamlClass['properties']['additionalIdentifierType'];

        /** @var PHPClass $phpClass */
        $phpClass = $phpClasses['Epa\\Schema\\AdditionalIdentifier'];

        /** @var PHPProperty $phpProperty */
        $phpProperty = $phpClass->getProperty('additionalIdentifierType');

        /** @var PHPClass $phpType */
        $phpType = $phpProperty->getType();

        $this->assertSame($yamlProperty['type'], $phpType->getFullName());
    }

    public function testDetectSimpleParents()
    {
        $expectedItems = array(
            'Epa\\Schema\\AdditionalIdentifier',
            'Epa\\Schema\\AdditionalIdentifierType',
            'Epa\\Schema\\AdditionalIdentifierTypes',
            'Epa\\Schema\\AdditionalIdentifiers',
        );

        $expectedItems = array_combine($expectedItems, $expectedItems);

        $reader = new SchemaReader();
        $schema = $reader->readFile(__DIR__ . '/data_nested.xsd');

        $yamlConv = new YamlConverter(new ShortNamingStrategy());
        $yamlConv->addNamespace('', 'Epa\\Schema');

        $yamlItems = $yamlConv->convert([$schema]);
        print_r($yamlItems);

        $phpConv = new PhpConverter(new ShortNamingStrategy());
        $phpConv->addNamespace('', 'Epa\\Schema');

        $phpClasses = $phpConv->convert([$schema]);

        $pathGenerator = new PhpPsr4PathGenerator(['Epa\\Schema' => __DIR__ . '/'. __FUNCTION__]);

        $classWriter = new PHPClassWriter($pathGenerator);
        $writer = new PHPWriter($classWriter, new ClassGenerator());
        $writer->write($phpClasses);

    }

    public function testDetectSimpleParentsWithAttributes()
    {
        $expectedItems = array(
            'Epa\\Schema\\AdditionalIdentifier',
            'Epa\\Schema\\AdditionalIdentifierType',
            'Epa\\Schema\\AdditionalIdentifierTypes',
            'Epa\\Schema\\AdditionalIdentifiers',
        );

        $expectedItems = array_combine($expectedItems, $expectedItems);

        $reader = new SchemaReader();
        $schema = $reader->readFile(__DIR__ . '/data_nested_with_attributes.xsd');

        $yamlConv = new YamlConverter(new ShortNamingStrategy());
        $yamlConv->addNamespace('', 'Epa\\Schema');

        $yamlItems = $yamlConv->convert([$schema]);

        print_r($yamlItems);
        $phpConv = new PhpConverter(new ShortNamingStrategy());
        $phpConv->addNamespace('', 'Epa\\Schema');

        $phpClasses = $phpConv->convert([$schema]);

        $pathGenerator = new PhpPsr4PathGenerator(['Epa\\Schema' => __DIR__ . '/'. __FUNCTION__]);

        $classWriter = new PHPClassWriter($pathGenerator);
        $writer = new PHPWriter($classWriter, new ClassGenerator());
        $writer->write($phpClasses);

    }

    public function testDetectSimpleParentsWithAttributesInTheMiddle()
    {
        $expectedItems = array(
            'Epa\\Schema\\AdditionalIdentifier',
            'Epa\\Schema\\AdditionalIdentifierType',
            'Epa\\Schema\\AdditionalIdentifierTypes',
            'Epa\\Schema\\AdditionalIdentifiers',
        );

        $expectedItems = array_combine($expectedItems, $expectedItems);

        $reader = new SchemaReader();
        $schema = $reader->readFile(__DIR__ . '/data_nested_with_attributes_in_the_middle.xsd');

        $yamlConv = new YamlConverter(new ShortNamingStrategy());
        $yamlConv->addNamespace('', 'Epa\\Schema');

        $yamlItems = $yamlConv->convert([$schema]);

        print_r($yamlItems);

        $phpConv = new PhpConverter(new ShortNamingStrategy());
        $phpConv->addNamespace('', 'Epa\\Schema');

        $phpClasses = $phpConv->convert([$schema]);

        $pathGenerator = new PhpPsr4PathGenerator(['Epa\\Schema' => __DIR__ . '/'. __FUNCTION__]);

        $classWriter = new PHPClassWriter($pathGenerator);
        $writer = new PHPWriter($classWriter, new ClassGenerator());
        $writer->write($phpClasses);

    }
}
