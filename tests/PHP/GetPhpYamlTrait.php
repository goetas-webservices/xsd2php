<?php

namespace GoetasWebservices\Xsd\XsdToPhp\Tests\PHP;

use GoetasWebservices\XML\XSDReader\SchemaReader;
use GoetasWebservices\XML\XSDReader\Schema\Schema;
use GoetasWebservices\Xsd\XsdToPhp\Jms\YamlConverter;
use GoetasWebservices\Xsd\XsdToPhp\Naming\ShortNamingStrategy;
use GoetasWebservices\Xsd\XsdToPhp\Php\ClassGenerator;
use GoetasWebservices\Xsd\XsdToPhp\Php\PhpConverter;

trait GetPhpYamlTrait
{
    /**
     * @param array|string $xml
     * @param array $types
     *
     * @return Schema[]
     */
    protected function getYamlFiles($xml, array $types = []): array
    {
        $creator = new YamlConverter(new ShortNamingStrategy());
        $creator->addNamespace('', 'Example');

        foreach ($types as $typeData) {
            list($ns, $name, $type) = $typeData;
            $creator->addAliasMapType($ns, $name, $type);
        }

        $reader = new SchemaReader();

        if (!is_array($xml)) {
            $xml = [
                'schema.xsd' => $xml,
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
     * @param array|string $xml
     * @param array $types
     *
     * @return \Laminas\Code\Generator\ClassGenerator[]
     */
    protected function getPhpClasses($xml, array $types = [])
    {
        $creator = new PhpConverter(new ShortNamingStrategy());
        $creator->addNamespace('', 'Example');

        foreach ($types as $typeData) {
            list($ns, $name, $type) = $typeData;
            $creator->addAliasMapType($ns, $name, $type);
        }

        $generator = new ClassGenerator();
        $reader = new SchemaReader();

        if (!is_array($xml)) {
            $xml = [
                'schema.xsd' => $xml,
            ];
        }
        $schemas = [];
        foreach ($xml as $name => $str) {
            $schemas[] = $reader->readString($str, $name);
        }
        $items = $creator->convert($schemas);

        $classes = [];
        foreach ($items as $k => $item) {
            if ($codegen = $generator->generate($item)) {
                $classes[$k] = $codegen;
            }
        }

        return $classes;
    }
}
