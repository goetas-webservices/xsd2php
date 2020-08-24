<?php
namespace GoetasWebservices\Xsd\XsdToPhp\Tests\Issues\I57;

use GoetasWebservices\Xsd\XsdToPhp\Jms\YamlConverter;
use GoetasWebservices\Xsd\XsdToPhp\Naming\ShortNamingStrategy;
use GoetasWebservices\Xsd\XsdToPhp\Php\PhpConverter;
use GoetasWebservices\XML\XSDReader\SchemaReader;
use PHPUnit\Framework\TestCase;

class I57Test extends TestCase
{

    public function testMissingClass()
    {

        $expectedItems = array(
            'Epa\\Job',
            'Epa\\Item',
            'Epa\\Item\\PriceAType'
        );

        $reader = new SchemaReader();
        $schema = $reader->readFile(__DIR__ . '/data.xsd');

        $yamlConv = new YamlConverter(new ShortNamingStrategy());
        $yamlConv->addNamespace('http://www.trogon.si/Schemas/2010/JobXML/2.0', 'Epa');

        $yamlItems = $yamlConv->convert([$schema]);
        $yamlItems = array_keys($yamlItems);
        $this->assertEmpty(array_diff($expectedItems, ($yamlItems)));

        $phpConv = new PhpConverter(new ShortNamingStrategy());
        $phpConv->addNamespace('http://www.trogon.si/Schemas/2010/JobXML/2.0', 'Epa');

        $phpClasses = $phpConv->convert([$schema]);
        $phpClasses = array_keys($phpClasses);
        $this->assertEmpty(array_diff_key($expectedItems, $phpClasses));
    }
}