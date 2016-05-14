<?php
namespace Goetas\Xsd\XsdToPhp\Tests\Issues\I40;
use GoetasWebservices\XML\XSDReader\SchemaReader;
use Goetas\Xsd\XsdToPhp\Jms\YamlConverter;
use Goetas\Xsd\XsdToPhp\Php\PhpConverter;
use Goetas\Xsd\XsdToPhp\Php\Structure\PHPClass;
use Goetas\Xsd\XsdToPhp\Php\Structure\PHPProperty;
use Goetas\Xsd\XsdToPhp\Naming\ShortNamingStrategy;

class I40Test extends \PHPUnit_Framework_TestCase{

    public function testMissingClass()
    {

        $expectedItems = array (
            'Epa\\Schema\\AdditionalIdentifier',
            'Epa\\Schema\\AdditionalIdentifierType',
            'Epa\\Schema\\AdditionalIdentifierTypes',
            'Epa\\Schema\\AdditionalIdentifiers',
        );
        $expectedItems = array_combine($expectedItems, $expectedItems);

        $reader = new SchemaReader();
        $schema = $reader->readFile(__DIR__.'/data.xsd');

        $yamlConv = new YamlConverter(new ShortNamingStrategy());
        $yamlConv->addNamespace('', 'Epa\\Schema');

        $yamlItems = $yamlConv->convert([$schema]);
        $this->assertCount(count($expectedItems), $yamlItems);
        $this->assertEmpty(array_diff_key($expectedItems, $yamlItems));

        $phpConv = new PhpConverter(new ShortNamingStrategy());
        $phpConv->addNamespace('', 'Epa\\Schema');

        $phpClasses = $phpConv->convert([$schema]);

        $this->assertCount(count($expectedItems), $phpClasses);
        $this->assertEmpty(array_diff_key($expectedItems, $phpClasses));

        $yamlClass = $yamlItems['Epa\\Schema\\AdditionalIdentifier']['Epa\\Schema\\AdditionalIdentifier'];
        $yamlProperty =  $yamlClass['properties']['additionalIdentifierType'];

        /** @var PHPClass $phpClass */
        $phpClass = $phpClasses['Epa\\Schema\\AdditionalIdentifier'];

        /** @var PHPProperty $phpProperty */
        $phpProperty = $phpClass->getProperty('additionalIdentifierType');

        /** @var PHPClass $phpType */
        $phpType = $phpProperty->getType();

        $this->assertSame($yamlProperty['type'], $phpType->getFullName());
    }
}