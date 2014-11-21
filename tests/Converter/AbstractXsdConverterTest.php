<?php
namespace Goetas\Xsd\XsdToPhp\Tests\Converter;

use Goetas\Xsd\XsdToPhp\Naming\ShortNamingStrategy;
class AbstractXsdConverterTest extends \PHPUnit_Framework_TestCase
{

    /**
     *
     * @var AbstractConverter
     */
    protected $converter;

    public function setUp()
    {
        $this->converter = $this->getMockForAbstractClass('Goetas\Xsd\XsdToPhp\AbstractConverter', [new ShortNamingStrategy()]);
    }

    public function testAliases()
    {
        $f = function ()
        {
        };
        $this->converter->addAliasMap('http://www.example.com', "myType", $f);

        $handlers = \PHPUnit_Framework_Assert::readAttribute($this->converter, 'typeAliases');

        $this->assertArrayHasKey('http://www.example.com', $handlers);
        $this->assertArrayHasKey('myType', $exmpleHandlers = $handlers['http://www.example.com']);
        $this->assertSame($f, $exmpleHandlers['myType']);
    }

    public function testDefaultAliases()
    {
        $handlers = \PHPUnit_Framework_Assert::readAttribute($this->converter, 'typeAliases');

        $this->assertArrayHasKey('http://www.w3.org/2001/XMLSchema', $handlers);
        $defaultHandlers = $handlers['http://www.w3.org/2001/XMLSchema'];

        $this->assertArrayHasKey('int', $defaultHandlers);
        $this->assertArrayHasKey('integer', $defaultHandlers);
        $this->assertArrayHasKey('string', $defaultHandlers);
    }

    public function testNamespaces()
    {
        $this->converter->addNamespace('http://www.example.com', 'some\php\ns');

        $namespaces = \PHPUnit_Framework_Assert::readAttribute($this->converter, 'namespaces');

        $this->assertArrayHasKey('http://www.example.com', $namespaces);
        $this->assertEquals('some\php\ns', $namespaces['http://www.example.com']);
    }
}