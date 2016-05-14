<?php
namespace Goetas\Xsd\XsdToPhp\Tests\Issues\I63;
use GoetasWebservices\XML\XSDReader\SchemaReader;
use Goetas\Xsd\XsdToPhp\Jms\YamlConverter;
use Goetas\Xsd\XsdToPhp\Php\PhpConverter;
use Goetas\Xsd\XsdToPhp\Naming\ShortNamingStrategy;

class I63Test extends \PHPUnit_Framework_TestCase{

    public function testNaming()
    {
        $reader = new SchemaReader();
        $schema = $reader->readFile(__DIR__.'/data.xsd');

        $phpConv = new PhpConverter(new ShortNamingStrategy());
        $phpConv->addNamespace('http://www.example.com/', 'Epa');

        $phpClasses = $phpConv->convert([$schema]);
        $this->assertEquals('convertToReseller', $phpClasses['Epa\Two']->getProperty('convertToReseller')->getType()->getArg()->getName());
    }
}