<?php
namespace Goetas\Xsd\XsdToPhp\Tests\Converter\JMS;

use Goetas\Xsd\XsdToPhp\Jms\YamlConverter;
use GoetasWebservices\XML\XSDReader\SchemaReader;
use Goetas\Xsd\XsdToPhp\Naming\ShortNamingStrategy;

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