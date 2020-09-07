<?php

namespace GoetasWebservices\Xsd\XsdToPhp\Tests\Issues\I63;

use GoetasWebservices\XML\XSDReader\SchemaReader;
use GoetasWebservices\Xsd\XsdToPhp\Naming\ShortNamingStrategy;
use GoetasWebservices\Xsd\XsdToPhp\Php\PhpConverter;

class I84Test extends \PHPUnit_Framework_TestCase
{
    public function testNaming()
    {
        $reader = new SchemaReader();
        $schema = $reader->readFile(__DIR__ . '/data.xsd');

        $phpConv = new PhpConverter(new ShortNamingStrategy());
        $phpConv->addNamespace('http://www.example.com/', 'Epa');

        $phpClasses = $phpConv->convert([$schema]);
        $this->assertArrayHasKey('Epa\ABType', $phpClasses);
        $class = $phpClasses['Epa\ABType'];
        $this->assertArrayHasKey('cDe', $class->getProperties());
        $this->assertArrayHasKey('fGh', $class->getProperties());
    }
}
