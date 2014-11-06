<?php
namespace Goetas\Xsd\XsdToPhp\Tests\JmsSerializer\OTA;

use Doctrine\Common\Inflector\Inflector;
use Goetas\Xsd\XsdToPhp\Php\PhpConverter;
use Goetas\XML\XSDReader\SchemaReader;
use Goetas\Xsd\XsdToPhp\Php\ClassGenerator;
use Zend\Code\Generator\FileGenerator;
use Goetas\Xsd\XsdToPhp\Php\PathGenerator\Psr4PathGenerator;
use \Goetas\Xsd\XsdToPhp\Jms\PathGenerator\Psr4PathGenerator as JmsPsr4PathGenerator;
use Goetas\Xsd\XsdToPhp\Jms\YamlConverter;
use Symfony\Component\Yaml\Dumper;
use JMS\Serializer\Handler\HandlerRegistryInterface;
use Goetas\Xsd\XsdToPhp\Jms\Handler\BaseTypesHandler;
use Goetas\Xsd\XsdToPhp\Jms\Handler\XmlSchemaDateHandler;
use Goetas\Xsd\XsdToPhp\Jms\Handler\OTA\SchemaDateHandler;
use Composer\Autoload\ClassLoader;

class PHPConversionTest extends \PHPUnit_Framework_TestCase
{

    /**
     *
     * @param mixed $xml
     * @return \Zend\Code\Generator\ClassGenerator[]
     */
    protected function getClasses($xml)
    {
        $phpcreator = new PhpConverter();
        $phpcreator->addNamespace('http://www.example.com', 'Example');

        $generator = new ClassGenerator();
        $reader = new SchemaReader();

        if (! is_array($xml)) {
            $xml = [
                'schema.xsd' => $xml
            ];
        }
        $schemas = [];
        foreach ($xml as $name => $str) {
            $schemas[] = $reader->readString($str, $name);
        }
        $items = $phpcreator->convert($schemas);

        $classes = array();
        foreach ($items as $k => $item) {
            $codegen = new \Zend\Code\Generator\ClassGenerator();
            if ($generator->generate($codegen, $item)) {
                $classes[$k] = $codegen;
            }
        }
        return $classes;
    }

    public function testMulteplicity()
    {
        $xml = '
            <xs:schema targetNamespace="http://www.example.com"
            xmlns:xs="http://www.w3.org/2001/XMLSchema">
                <xs:complexType name="single">
                    <xs:all>
                        <xs:element name="id" type="xs:long" minOccurs="0"/>
                    </xs:all>
                </xs:complexType>
            </xs:schema>';

        $items = $this->getClasses($xml);
        $this->assertCount(1, $items);

        $codegen = $items['Example\SingleType'];
        $this->assertFalse($codegen->hasMethod('issetId'));
        $this->assertFalse($codegen->hasMethod('unsetId'));
    }
}