<?php
namespace GoetasWebservices\Xsd\XsdToPhp\Tests\JmsSerializer\OTA;

use Doctrine\Common\Inflector\Inflector;
use GoetasWebservices\XML\XSDReader\SchemaReader;
use GoetasWebservices\Xsd\XsdToPhp\Jms\Handler\OTA\SchemaDateHandler;
use GoetasWebservices\Xsd\XsdToPhp\Tests\Generator;
use GoetasWebservices\Xsd\XsdToPhpRuntime\Jms\Handler\BaseTypesHandler;
use GoetasWebservices\Xsd\XsdToPhpRuntime\Jms\Handler\XmlSchemaDateHandler;
use JMS\Serializer\Handler\HandlerRegistryInterface;

class OTASerializationTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Generator
     */
    protected static $generator;

    private static $namespace = 'OTA';
    private static $files = [];

    public static function setUpBeforeClass()
    {
        if (!self::$files){
            self::$files = self::getXmlFiles();
        }

        self::$generator = new Generator([
            'http://www.opentravel.org/OTA/2003/05' => self::$namespace
        ], [
            ['http://www.opentravel.org/OTA/2003/05', 'DateOrTimeOrDateTimeType', 'GoetasWebservices\Xsd\XsdToPhp\Tests\JmsSerializer\OTA\OTADateTime'],
            ['http://www.opentravel.org/OTA/2003/05', 'DateOrDateTimeType', 'GoetasWebservices\Xsd\XsdToPhp\Tests\JmsSerializer\OTA\OTADateTime'],
            ['http://www.opentravel.org/OTA/2003/05', 'TimeOrDateTimeType', 'GoetasWebservices\Xsd\XsdToPhp\Tests\JmsSerializer\OTA\OTADateTime']
        ]);

        $reader = new SchemaReader();
        $schemas = array();
        foreach (self::$files as $d) {
            if (!isset($schemas[$d[1]])) {
                $schemas[$d[1]] = $reader->readFile($d[1]);
            }
        }
        self::$generator->generate($schemas);
        self::$generator->registerAutoloader();
    }

    public static function tearDownAfterClass()
    {
        self::$generator->unRegisterAutoloader();
        self::$generator->cleanDirectories();
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
        if (!$dom->loadXML($xml)) {
            file_put_contents("d.xml", $xml);
        }

        $fix = function ($str) {
            $str = trim($str);
            // period
            if (preg_match("/^P([0-9]+[A-Z])+/", $str) || preg_match("/^PT([0-9]+[A-Z])+$/", $str)) {
                $str = str_replace("N", "D", $str);
                while (preg_match("/P0+[A-Z]/", $str)) {
                    $str = preg_replace("/P0+[A-Z]/", "P", $str); //P0D => P
                }
                while (preg_match("/T0+[A-Z]/", $str)) {
                    $str = preg_replace("/T0+[A-Z]/", "T", $str); //T0H => T
                }
                if ($str[strlen($str) - 1] == "T") {
                    $str = substr($str, 0, -1);
                }
            }

            // boolean
            $str = str_replace(['true', 'false'], ['1', '0'], $str); // 'true' => '1'

            // datetime
            $str = str_replace(array(
                '+00:00',
                '-05:00'
            ), '', $str);

            $str = preg_replace('/Z$/', '', $str);

            // number
            if (is_numeric($str) && strpos($str, '.') !== false) {
                $str = floatval($str);
            } elseif (is_numeric($str)) {
                $str = intval($str);
            } else {
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
     * @group slow
     * @dataProvider getTestFiles
     */
    public function testConversion($xml, $xsd, $class)
    {

        $serializer = self::$generator->buildSerializer(function (HandlerRegistryInterface $h) {
            $h->registerSubscribingHandler(new XmlSchemaDateHandler());
            $h->registerSubscribingHandler(new OTASchemaDateHandler());
            $h->registerSubscribingHandler(new BaseTypesHandler());
        });

        $original = $this->clearXML(file_get_contents($xml));
        $object = $serializer->deserialize($original, $class, 'xml');

        $new = $serializer->serialize($object, 'xml');

        $new = $this->clearXML($new);
        $differ = new \XMLDiff\Memory();
        $diff = $differ->diff($original, $new);

        $notEqual = strpos($diff, '<dm:copy count="1"/>') === false || strlen($diff) > 110;

        if (0 && $notEqual) {
            file_put_contents("a.xml", $original);
            file_put_contents("b.xml", $new);
            file_put_contents("c.xml", $diff);
            exit;
        }

        $this->assertFalse($notEqual);
    }

    private static function getXmlFiles()
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
                $tests[$n][2] = self::$namespace . "\\" . Inflector::classify(str_replace(".xsd", "", $name));
            }
        }
        return $tests;
    }

    public function getTestFiles()
    {
        if (!self::$files){
            self::$files = self::getXmlFiles();
        }
        return self::$files;
    }
}