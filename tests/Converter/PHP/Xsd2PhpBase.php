<?php

namespace GoetasWebservices\Xsd\XsdToPhp\Tests\Converter\PHP;

use GoetasWebservices\XML\XSDReader\SchemaReader;
use GoetasWebservices\Xsd\XsdToPhp\Naming\ShortNamingStrategy;
use GoetasWebservices\Xsd\XsdToPhp\Php\PhpConverter;

abstract class Xsd2PhpBase extends \PHPUnit_Framework_TestCase
{
    /**
     * @var PhpConverter
     */
    protected $converter;

    /**
     * @var SchemaReader
     */
    protected $reader;

    public function setUp()
    {
        $this->converter = new PhpConverter(new ShortNamingStrategy());
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
            ['xs:dateTime', 'DateTime'],
        ];
    }

    public function getPrimitiveTypeConversions()
    {
        return [
            ['xs:string', 'string'],
            ['xs:decimal', 'float'],
            ['xs:int', 'integer'],
            ['xs:integer', 'integer'],
        ];
    }
}
