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

    public function testMulteplicity()
    {
        $phpcreator = new PhpConverter();
        $phpcreator->addNamespace('http://www.example.com', 'Example');

        $reader = new SchemaReader();
        $schemas = array(
            $reader->readString('
                <xs:schema targetNamespace="http://www.example.com"
                xmlns:xs="http://www.w3.org/2001/XMLSchema">
                    <xs:complexType name="single">
                        <xs:all>
                            <xs:element name="id" type="xs:long" minOccurs="0"/>
                        </xs:all>
                    </xs:complexType>
                </xs:schema>
                '));

        $items = $phpcreator->convert($schemas);

        $this->assertCount(1, $items);

        $generator = new ClassGenerator();
        $classGen = new \Zend\Code\Generator\ClassGenerator();
        foreach ($items as $item) {
            if ($generator->generate($classGen, $item)) {
                $this->assertFalse($classGen->hasMethod('issetId'));
                $this->assertFalse($classGen->hasMethod('unsetId'));
            }
        }
    }
}