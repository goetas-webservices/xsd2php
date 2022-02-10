<?php

namespace GoetasWebservices\Xsd\XsdToPhp\Tests\Issues\I63;

use GoetasWebservices\XML\XSDReader\SchemaReader;
use GoetasWebservices\Xsd\XsdToPhp\Naming\ShortNamingStrategy;
use GoetasWebservices\Xsd\XsdToPhp\Php\PhpConverter;
use PHPUnit\Framework\TestCase;

class I63Test extends TestCase
{
    public function testNaming()
    {
        $reader = new SchemaReader();
        $schema = $reader->readFile(__DIR__ . '/data.xsd');

        $phpConv = new PhpConverter(new ShortNamingStrategy());
        $phpConv->addNamespace('http://www.example.com/', 'Epa');

        $phpClasses = $phpConv->convert([$schema]);
        $this->assertEquals('convertToReseller', $phpClasses['Epa\Two\TwoAType']->getProperty('convertToReseller')->getType()->getArg()->getName());
    }
}
