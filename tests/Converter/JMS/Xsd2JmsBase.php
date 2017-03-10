<?php
namespace GoetasWebservices\Xsd\XsdToPhp\Tests\Converter\JMS;

use GoetasWebservices\Xsd\XsdToPhp\Jms\YamlConverter;
use GoetasWebservices\Xsd\XsdToPhp\Naming\ShortNamingStrategy;
use GoetasWebservices\XML\XSDReader\SchemaReader;

abstract class Xsd2JmsBase extends \PHPUnit_Framework_TestCase
{
    /**
     *
     * @var YamlConverter
     */
    protected $converter;

    /**
     *
     * @var SchemaReader
     */
    protected $reader;

    public function setUp()
    {
        $this->converter = new YamlConverter(new ShortNamingStrategy());
        $this->converter->addNamespace('http://www.example.com', "Example");

        $this->reader = new SchemaReader();
    }

    protected function getClasses($xml)
    {

        $schema = $this->reader->readString($xml);
        return $this->converter->convert(array($schema));

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
            ['xs:int', 'integer'],
            ['xs:integer', 'integer'],
        ];
    }
}