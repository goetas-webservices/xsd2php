<?php

namespace GoetasWebservices\Xsd\XsdToPhp\Tests\Issues\I22;

use GoetasWebservices\XML\XSDReader\SchemaReader;
use GoetasWebservices\Xsd\XsdToPhp\Jms\YamlConverter;
use GoetasWebservices\Xsd\XsdToPhp\Naming\ShortNamingStrategy;
use GoetasWebservices\Xsd\XsdToPhp\Php\PhpConverter;

class I24Test extends \PHPUnit_Framework_TestCase
{
    public function testDefaultYamlListSimplification()
    {
        $reader = new SchemaReader();
        $schema = $reader->readFile(__DIR__.'/data.xsd');

        $yamlConverter = new YamlConverter(new ShortNamingStrategy());
        $yamlConverter->addNamespace('', 'NestedArrayTest');
        $phpClasses = $yamlConverter->convert([$schema]);

        $type = $phpClasses['NestedArrayTest\MainElementType']['NestedArrayTest\MainElementType']['properties']['elementList']['type'];
        self::assertEquals('array<integer>', $type);
    }

    public function testDisabledYamlListSimplification()
    {
        $reader = new SchemaReader();
        $schema = $reader->readFile(__DIR__.'/data.xsd');

        $yamlConverter = new YamlConverter(new ShortNamingStrategy());
        $yamlConverter->addNamespace('', 'NestedArrayTest');
        $yamlConverter->setSimplifyNestedArrays(false);
        $phpClasses = $yamlConverter->convert([$schema]);

        $type = $phpClasses['NestedArrayTest\MainElementType']['NestedArrayTest\MainElementType']['properties']['elementList']['type'];
        self::assertEquals('NestedArrayTest\MainElementType\ElementListAType', $type);
    }

    public function testDefaultPhpListSimplification()
    {
        $reader = new SchemaReader();
        $schema = $reader->readFile(__DIR__.'/data.xsd');

        $phpConverter = new PhpConverter(new ShortNamingStrategy());
        $phpConverter->addNamespace('', 'NestedArrayTest');
        $phpClasses = $phpConverter->convert([$schema]);

        $type = $phpClasses['NestedArrayTest\MainElementType']->getProperties()['elementList']->getType()->getName();
        self::assertEquals('array', $type);
    }

    public function testDisabledPhpListSimplification()
    {
        $reader = new SchemaReader();
        $schema = $reader->readFile(__DIR__.'/data.xsd');

        $phpConverter = new PhpConverter(new ShortNamingStrategy());
        $phpConverter->addNamespace('', 'NestedArrayTest');
        $phpConverter->setSimplifyNestedArrays(false);
        $phpClasses = $phpConverter->convert([$schema]);

        $type = $phpClasses['NestedArrayTest\MainElementType']->getProperties()['elementList']->getType()->getName();
        self::assertEquals('ElementListAType', $type);
    }
}
