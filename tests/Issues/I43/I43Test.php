<?php
namespace Goetas\Xsd\XsdToPhp\Tests\Issues\I40;
use Goetas\XML\XSDReader\SchemaReader;
use Goetas\Xsd\XsdToPhp\Jms\YamlConverter;
use Goetas\Xsd\XsdToPhp\Php\PhpConverter;
use Goetas\Xsd\XsdToPhp\Php\Structure\PHPClass;
use Goetas\Xsd\XsdToPhp\Php\Structure\PHPProperty;

class I43Test extends \PHPUnit_Framework_TestCase{

    public function testOpcGeneration()
    {

        $nss = array(
            "http://schemas.openxmlformats.org/package/2006/metadata/core-properties" => "Iag/ECMA376/Package/Model/CoreProperties/",
            "http://purl.org/dc/elements/1.1/" => "Iag/ECMA376/Package/Model/CoreProperties/DcElements/",
            "http://purl.org/dc/terms/" => "Iag/ECMA376/Package/Model/CoreProperties/DcTerms/",
            "http://purl.org/dc/dcmitype/" => "Iag/ECMA376/Package/Model/CoreProperties/DcMiType/",
        );

        $reader = new SchemaReader();
        $schema = $reader->readFile(__DIR__.'/opc/opc-coreProperties.xsd');

        $yamlConv = new YamlConverter();
        $phpConv = new PhpConverter();

        foreach ($nss as $ns => $php) {
            $yamlConv->addNamespace($ns, $php);
            $phpConv->addNamespace($ns, $php);
        }

        $yamlItems = $yamlConv->convert([$schema]);
        $phpClasses = $phpConv->convert([$schema]);

        $this->assertEquals(count($phpClasses), count($yamlItems));
    }
}