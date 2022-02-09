<?php

namespace GoetasWebservices\Xsd\XsdToPhp\Tests\Converter;

use GoetasWebservices\Xsd\XsdToPhp\AbstractConverter;
use GoetasWebservices\Xsd\XsdToPhp\Naming\ShortNamingStrategy;
use PHPUnit\Framework\TestCase;

class AbstractXsdConverterTest extends TestCase
{
    /**
     * @var AbstractConverter
     */
    protected $converter;

    public function setUp(): void
    {
        $this->converter = $this->getMockForAbstractClass(AbstractConverter::class, [new ShortNamingStrategy()]);
    }

    public function testAliases()
    {
        $f = function () {
        };
        $this->converter->addAliasMap('http://www.example.com', 'myType', $f);

        $handlers = $this->converter->getTypeAliases();

        $this->assertArrayHasKey('http://www.example.com', $handlers);
        $this->assertArrayHasKey('myType', $exmpleHandlers = $handlers['http://www.example.com']);
        $this->assertSame($f, $exmpleHandlers['myType']);
    }

    public function testDefaultAliases()
    {
        $handlers = $this->converter->getTypeAliases();

        $this->assertArrayHasKey('http://www.w3.org/2001/XMLSchema', $handlers);
        $defaultHandlers = $handlers['http://www.w3.org/2001/XMLSchema'];

        $this->assertArrayHasKey('int', $defaultHandlers);
        $this->assertArrayHasKey('integer', $defaultHandlers);
        $this->assertArrayHasKey('string', $defaultHandlers);
    }

    public function testNamespaces()
    {
        $this->converter->addNamespace('http://www.example.com', 'some\php\ns');

        $namespaces = $this->converter->getNameSpaces();

        $this->assertArrayHasKey('http://www.example.com', $namespaces);
        $this->assertEquals('some\php\ns', $namespaces['http://www.example.com']);
    }
}
