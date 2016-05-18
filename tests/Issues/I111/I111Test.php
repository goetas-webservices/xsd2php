<?php
namespace GoetasWebservices\Xsd\XsdToPhp\Tests\Issues\I11;

use GoetasWebservices\XML\XSDReader\SchemaReader;
use GoetasWebservices\Xsd\XsdToPhp\Jms\YamlConverter;
use GoetasWebservices\Xsd\XsdToPhp\Naming\ShortNamingStrategy;

class I111Test extends \PHPUnit_Framework_TestCase{

    public function testNamespace()
    {
        $reader = new SchemaReader();
        $schema = $reader->readFile(__DIR__.'/data.xsd');

        $jmsConv = new YamlConverter(new ShortNamingStrategy());
        $jmsConv->addNamespace('http://www.example.com', 'Tst');
        $jmsConv->addNamespace('http://www.example2.com', 'Tst');

        $phpClasses = $jmsConv->convert([$schema]);
        $type1 = $phpClasses['Tst\ComplexType1Type']['Tst\ComplexType1Type'];

        $propertyElement2 = $type1['properties']['element2'];
        self::assertEquals('http://www.example2.com', $propertyElement2['xml_element']['namespace']);

        $propertyElement1 = $type1['properties']['element1'];
        self::assertEquals('http://www.example.com', $propertyElement1['xml_element']['namespace']);
    }
}
