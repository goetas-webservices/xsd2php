<?php
namespace Goetas\Xsd\XsdToPhp\Tests\JmsSerializer\OTA;

use Goetas\Xsd\XsdToPhp\Php\PhpConverter;
use Goetas\XML\XSDReader\SchemaReader;
use Goetas\Xsd\XsdToPhp\Php\ClassGenerator;
use Goetas\Xsd\XsdToPhp\Jms\YamlConverter;

class AnyTypePHPConversionTest extends \PHPUnit_Framework_TestCase
{

    /**
     *
     * @param mixed $xml
     * @return array[]
     */
    protected function getYamlFiles($xml, array $types = array())
    {
        $creator = new YamlConverter();
        $creator->addNamespace('', 'Example');

        foreach ($types as $typeData) {
            list($ns, $name, $type) = $typeData;
            $creator->addAliasMapType($ns, $name, $type);
        }

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
        $items = $creator->convert($schemas);

        return $items;
    }
    /**
     *
     * @param mixed $xml
     * @return \Zend\Code\Generator\ClassGenerator[]
     */
    protected function getPhpClasses($xml, array $types = array())
    {
        $creator = new PhpConverter();
        $creator->addNamespace('', 'Example');

        foreach ($types as $typeData) {
            list($ns, $name, $type) = $typeData;
            $creator->addAliasMapType($ns, $name, $type);
        }

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
        $items = $creator->convert($schemas);

        $classes = array();
        foreach ($items as $k => $item) {
            $codegen = new \Zend\Code\Generator\ClassGenerator();
            if ($generator->generate($codegen, $item)) {
                $classes[$k] = $codegen;
            }
        }
        return $classes;
    }

    public function testSimpleAnyTypePHP()
    {
        $xml = '
            <xs:schema xmlns:xs="http://www.w3.org/2001/XMLSchema">
                <xs:complexType name="single">
                    <xs:all>
                        <xs:element name="id" type="xs:anyType"/>
                    </xs:all>
                </xs:complexType>
            </xs:schema>';

        $items = $this->getPhpClasses($xml, array(
            ['http://www.w3.org/2001/XMLSchema', 'anyType', 'mixed']
        ));


        $this->assertCount(1, $items);

        $single = $items['Example\SingleType'];

        $this->assertTrue($single->hasMethod('getId'));
        $this->assertTrue($single->hasMethod('setId'));

        $returnTags = $single->getMethod('getId')->getDocBlock()->getTags();

        $this->assertCount(1, $returnTags);
        $this->assertEquals(['mixed'], $returnTags[0]->getTypes());
    }

    public function testSimpleAnyTypeYaml()
    {
        $xml = '
            <xs:schema xmlns:xs="http://www.w3.org/2001/XMLSchema">
                <xs:complexType name="single">
                    <xs:all>
                        <xs:element name="id" type="xs:anyType"/>
                    </xs:all>
                </xs:complexType>
            </xs:schema>';

        $items = $this->getYamlFiles($xml, array(
            ['http://www.w3.org/2001/XMLSchema', 'anyType', 'My\Custom\MixedTypeHandler']
        ));


        $this->assertCount(1, $items);

        $single = $items['Example\SingleType'];

        $this->assertEquals(array(
            'Example\\SingleType' => array(
                'properties' => array(
                    'id' => array(
                        'expose' => true,
                        'access_type' => 'public_method',
                        'serialized_name' => 'id',
                        'accessor' => array(
                            'getter' => 'getId',
                            'setter' => 'setId'
                        ),
                        'type' => 'My\\Custom\\MixedTypeHandler'
                    )
                )
            )
        ), $single);
    }
}