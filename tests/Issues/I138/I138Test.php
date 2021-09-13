<?php
namespace GoetasWebservices\Xsd\XsdToPhp\Tests\Issues\I63;

use GoetasWebservices\Xsd\XsdToPhp\Naming\ShortNamingStrategy;
use GoetasWebservices\Xsd\XsdToPhp\Php\PhpConverter;
use GoetasWebservices\XML\XSDReader\SchemaReader;

class I138Test extends \PHPUnit_Framework_TestCase
{

    public function testChioce()
    {
        $reader = new SchemaReader();
        $schema = $reader->readFile(__DIR__ . '/data.xsd');

        $phpConv = new PhpConverter(new ShortNamingStrategy());
        $phpConv->addNamespace('http://www.example.com/', 'Epa');

        $items = $phpConv->convert([$schema]);

        $this->assertTrue($items['Epa\ComplexType']->getProperty('option1')->getNullable());
        $this->assertTrue($items['Epa\ComplexType']->getProperty('option2')->getNullable());
    }
}