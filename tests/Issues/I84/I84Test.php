<?php
namespace GoetasWebservices\Xsd\XsdToPhp\Tests\Issues\I63;

use GoetasWebservices\Xsd\XsdToPhp\Naming\ShortNamingStrategy;
use GoetasWebservices\Xsd\XsdToPhp\Php\PhpConverter;
use GoetasWebservices\XML\XSDReader\SchemaReader;
use PHPUnit\Framework\TestCase;

class I84Test extends TestCase
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