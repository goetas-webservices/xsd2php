<?php

namespace GoetasWebservices\Xsd\XsdToPhp\Tests\Issues\I40;

use GoetasWebservices\XML\XSDReader\SchemaReader;
use GoetasWebservices\Xsd\XsdToPhp\Tests\Generator;
use PHPUnit\Framework\TestCase;

class I43Test extends TestCase
{
    /**
     * @group long
     */
    public function testOpcGeneration()
    {
        $nss = [
            'http://schemas.openxmlformats.org/package/2006/metadata/core-properties' => 'Iag/ECMA376/Package/Model/CoreProperties',
            'http://purl.org/dc/elements/1.1/' => 'Iag/ECMA376/Package/Model/CoreProperties/DcElements',
            'http://purl.org/dc/terms/' => 'Iag/ECMA376/Package/Model/CoreProperties/DcTerms',
            'http://purl.org/dc/dcmitype/' => 'Iag/ECMA376/Package/Model/CoreProperties/DcMiType',
        ];
        $nss = array_map(function ($a) {
            return strtr($a, '/', '\\');
        }, $nss);

        $reader = new SchemaReader();
        $reader->addKnownSchemaLocation('http://dublincore.org/schemas/xmls/qdc/2003/04/02/dc.xsd', __DIR__ . '/opc/dc.xsd');
        $reader->addKnownSchemaLocation('http://dublincore.org/schemas/xmls/qdc/2003/04/02/dcterms.xsd', __DIR__ . '/opc/dcterms.xsd');
        $reader->addKnownSchemaLocation('http://dublincore.org/schemas/xmls/qdc/2003/04/02/dcterms.xsd', __DIR__ . '/opc/dcterms.xsd');
        $reader->addKnownSchemaLocation('http://dublincore.org/schemas/xmls/qdc/2003/04/02/dcmitype.xsd', __DIR__ . '/opc/dcmitype.xsd');

        $schema = $reader->readFile(__DIR__ . '/opc/opc-coreProperties.xsd');

        $generator = new Generator($nss, [], __DIR__ . '/tmp');

        list($phpClasses, $yamlItems, $validationItems) = $generator->getData([$schema]);
        $generator->generate([$schema]);

        $this->assertGreaterThanOrEqual(24, count($yamlItems));
        $this->assertGreaterThanOrEqual(24, count($phpClasses));
        $this->assertGreaterThanOrEqual(3, count($validationItems));
    }
}
