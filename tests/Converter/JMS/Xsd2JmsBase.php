<?php

namespace GoetasWebservices\Xsd\XsdToPhp\Tests\Converter\JMS;

use GoetasWebservices\XML\XSDReader\SchemaReader;
use GoetasWebservices\Xsd\XsdToPhp\Jms\YamlConverter;
use GoetasWebservices\Xsd\XsdToPhp\Naming\ShortNamingStrategy;
use PHPUnit\Framework\TestCase;

abstract class Xsd2JmsBase extends TestCase
{
    /**
     * @var YamlConverter
     */
    protected $converter;

    /**
     * @var SchemaReader
     */
    protected $reader;

    public function setUp(): void
    {
        $this->converter = new YamlConverter(new ShortNamingStrategy());
        $this->converter->addNamespace('http://www.example.com', 'Example');

        $this->reader = new SchemaReader();
    }

    protected function getClasses($xml)
    {
        $schema = $this->reader->readString($xml);

        return $this->converter->convert([$schema]);
    }

    public function getBaseTypeConversions()
    {
        return [
            ['xs:dateTime', 'DateTime', 'GoetasWebservices\\Xsd\\XsdToPhp\\XMLSchema\\DateTime'],
            ['xs:date', 'DateTime', 'GoetasWebservices\\Xsd\\XsdToPhp\\XMLSchema\\Date'],
        ];
    }

    public function getPrimitiveTypeConversions()
    {
        return [
            ['xs:string', 'string'],
            ['xs:decimal', 'float'],
            ['xs:int', 'int'],
            ['xs:integer', 'int'],
        ];
    }
}
