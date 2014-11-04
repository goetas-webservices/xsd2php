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

class OTASerializationTest extends \PHPUnit_Framework_TestCase
{

    protected $serializer;

    protected $namespace = 'OTA';

    protected $phpDir = '/tmp';

    protected $jmsDir = '/tmp';

    protected $regen = false;

    private $reader;

    protected function getReader()
    {
        if (! $this->reader) {
            $this->reader = new SchemaReader();
        }
        return $this->reader;
    }

    private static function delTree($dir) {
        $files = array_diff(scandir($dir), array('.','..'));
        foreach ($files as $file) {
            (is_dir("$dir/$file")) ? self::delTree("$dir/$file") : unlink("$dir/$file");
        }
        return rmdir($dir);
    }

    protected function generatePHPFiles($xsd)
    {
        $phpcreator = new PhpConverter();
        $phpcreator->addNamespace('http://www.opentravel.org/OTA/2003/05', $this->namespace);

        $phpcreator->addAliasMapType('http://www.opentravel.org/OTA/2003/05', 'DateOrTimeOrDateTimeType', 'Goetas\Xsd\XsdToPhp\Tests\JmsSerializer\OTA\OTADateTime');
        $phpcreator->addAliasMapType('http://www.opentravel.org/OTA/2003/05', 'DateOrDateTimeType', 'Goetas\Xsd\XsdToPhp\Tests\JmsSerializer\OTA\OTADateTime');
        $phpcreator->addAliasMapType('http://www.opentravel.org/OTA/2003/05', 'TimeOrDateTimeType', 'Goetas\Xsd\XsdToPhp\Tests\JmsSerializer\OTA\OTADateTime');

        $schema = $this->getReader()->readFile($xsd);
        $items = $phpcreator->convert(array(
            $schema
        ));

        $generator = new ClassGenerator();
        $pathGenerator = new Psr4PathGenerator(array(
            $this->namespace . "\\" => $this->phpDir
        ));
        foreach ($items as $item) {
            $path = $pathGenerator->getPath($item);

            if (is_file($path)) {
                continue;
            }

            $fileGen = new FileGenerator();
            $fileGen->setFilename($path);
            $classGen = new \Zend\Code\Generator\ClassGenerator();

            if ($generator->generate($classGen, $item)) {

                $fileGen->setClass($classGen);

                $fileGen->write();
            }
        }
    }

    protected function generateJMSFiles($xsd)
    {
        $yamlcreator = new YamlConverter();
        $yamlcreator->addNamespace('http://www.opentravel.org/OTA/2003/05', $this->namespace);

        $yamlcreator->addAliasMapType('http://www.opentravel.org/OTA/2003/05', 'DateOrTimeOrDateTimeType', 'Goetas\Xsd\XsdToPhp\Tests\JmsSerializer\OTA\OTADateTime');
        $yamlcreator->addAliasMapType('http://www.opentravel.org/OTA/2003/05', 'DateOrDateTimeType', 'Goetas\Xsd\XsdToPhp\Tests\JmsSerializer\OTA\OTADateTime');
        $yamlcreator->addAliasMapType('http://www.opentravel.org/OTA/2003/05', 'TimeOrDateTimeType', 'Goetas\Xsd\XsdToPhp\Tests\JmsSerializer\OTA\OTADateTime');
        $schema = $this->getReader()->readFile($xsd);
        $items = $yamlcreator->convert(array(
            $schema
        ));

        $dumper = new Dumper();

        $pathGenerator = new JmsPsr4PathGenerator(array(
            $this->namespace . "\\" => $this->jmsDir
        ));

        foreach ($items as $item) {

            $path = $pathGenerator->getPath($item);

            if (is_file($path)) {
                continue;
            }
            file_put_contents($path, $dumper->dump($item, 10000));
        }
    }

    protected $loader;

    public function tearDown()
    {
        if ($this->loader) {
            $this->loader->unregister();
        }
    }

    protected $differ;

    public function setUp()
    {
        if (! $this->differ) {
            $this->differ = new \XMLDiff\Memory();
        }

        $tmp = sys_get_temp_dir();

        if (is_writable("/dev/shm")) {
            $tmp = "/dev/shm";
        }

        $this->phpDir = "$tmp/OTASerializationTestPHP";
        $this->jmsDir = "$tmp/OTASerializationTestJMS";

        $this->loader = new ClassLoader();
        $this->loader->addPsr4($this->namespace . "\\", $this->phpDir);
        $this->loader->register();

        if (is_dir($this->phpDir)) {
            //self::delTree($this->phpDir);
        }
        if (is_dir($this->jmsDir)) {
            //self::delTree($this->jmsDir);
        }

        if (! is_dir($this->phpDir)) {
            mkdir($this->phpDir);
        }
        if (! is_dir($this->jmsDir)) {
            mkdir($this->jmsDir);
        }

        $serializerBuiler = \JMS\Serializer\SerializerBuilder::create();
        $serializerBuiler->configureHandlers(function (HandlerRegistryInterface $h) use($serializerBuiler)
        {
            $serializerBuiler->addDefaultHandlers();
            $h->registerSubscribingHandler(new BaseTypesHandler());

            $h->registerSubscribingHandler(new XmlSchemaDateHandler());
            $h->registerSubscribingHandler(new OTASchemaDateHandler());
        });

        $serializerBuiler->addMetadataDir($this->jmsDir, $this->namespace);

        $this->serializer = $serializerBuiler->build();
    }

    protected function clearXML($xml)
    {
        $xml = str_replace("\r", "\n", $xml);
        $xml = str_replace(array(
            'xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"'
        ), '', $xml);
        $xml = preg_replace('~xsi:[a-z]+="[^"]+"~msi', '', $xml);

        $xml = preg_replace("/" . preg_quote('<![CDATA[', '/') . "(.*?)" . preg_quote(']]>', '/') . "/mis", "\\1", $xml);

        $xml = str_replace('&', '', $xml);

        $dom = new \DOMDocument();
        if(!$dom->loadXML($xml)){
            file_put_contents("d.xml", $xml);
        }

        $fix = function($str){
            $str = trim($str);
            // period
            if (preg_match("/^P([0-9]+[A-Z])+/", $str) || preg_match("/^PT([0-9]+[A-Z])+$/", $str)) {
                $str = str_replace("N", "D", $str);
                while (preg_match("/P0+[A-Z]/", $str)){
                    $str = preg_replace("/P0+[A-Z]/", "P", $str); //P0D => P
                }
                while (preg_match("/T0+[A-Z]/", $str)){
                    $str = preg_replace("/T0+[A-Z]/", "T", $str); //T0H => T
                }
                if ($str[strlen($str)-1]=="T"){
                    $str = substr($str, 0, -1);
                }
            }

            // boolean
            $str = str_replace(['true','false'], ['1','0'], $str); // 'true' => '1'

            // datetime
            $str = str_replace(array(
                '+00:00',
                '-05:00'
            ), '', $str);

            $str = preg_replace('/Z$/', '', $str);

            // number
            if (is_numeric($str) && strpos($str, '.')!==false){
                $str = floatval($str);
            }elseif (is_numeric($str)){
                $str = intval($str);
            }else{
                $str = preg_replace('/\.0+$/', '', $str); // 1.0000 => 1, .0 => ''
            }
            return $str;
        };

        $xp = new \DOMXPath($dom);
        do {
            $comments = $xp->query("//comment()|//text()[normalize-space()='']");
            $l = $comments->length;
            foreach ($comments as $comment) {
                $comment->parentNode->removeChild($comment);
            }
        } while ($l);

        foreach ($xp->query("//@*") as $attr) {
            $attr->value = $fix($attr->value);
        }
        foreach ($xp->query("//text()") as $text) {
            $text->data = $fix($text->data);
        }

        $dom->formatOutput = true;
        $dom->preserveWhitespace = true;

        return $dom->saveXML();
    }

    /**
     * @dataProvider getTestFiles
     */
    public function testConversion($xml, $xsd, $class)
    {
        if (strpos($xml, 'OTA_VehLocDetailRS')===false){
           // return;
        }

        $this->generatePHPFiles($xsd);
        $this->generateJMSFiles($xsd);

        $original = $this->clearXML(file_get_contents($xml));
        $object = $this->serializer->deserialize($original, $class, 'xml');

        $new = $this->serializer->serialize($object, 'xml');

        $new = $this->clearXML($new);

        $diff = $this->differ->diff($original, $new);

        if (strpos($diff, '<dm:copy count="1"/>') === false || strlen($diff) > 110) {
            file_put_contents("a.xml", $original);
            file_put_contents("b.xml", $new);
            file_put_contents("c.xml", $diff);
            print_r($object);

            $this->assertFalse(true);
        }
    }

    public function getTestFiles()
    {
        $files = glob(__DIR__ . "/otaxml/*.xml");

        $tests = array();
        foreach ($files as $n => $file) {
            $name = basename($file);
            $dir = dirname($file);

            $name = str_replace(".xml", ".xsd", $name);
            $name = preg_replace("/[0-9]+/", "", $name);
            if (is_file($dir . "/" . $name)) {
                $tests[$n][0] = $file;
                $tests[$n][1] = $dir . "/" . $name;
                $tests[$n][2] = $this->namespace . "\\" . Inflector::classify(str_replace(".xsd", "", $name));
            }
        }
        return $tests;
    }
}