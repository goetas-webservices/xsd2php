<?php
namespace Goetas\Xsd\XsdToPhp\Tests\Jms\PathGenerator;

use Goetas\Xsd\XsdToPhp\Jms\PathGenerator\Psr4PathGenerator;

class Psr4PathGeneratorTest extends \PHPUnit_Framework_TestCase
{

    protected $cacheDir = null;

    public function setUp()
    {
        $this->cacheDir = sys_get_temp_dir() . "/Psr4PathGeneratorTest";

        $this->tearDown();

        if (! is_dir($this->cacheDir)) {
            mkdir($this->cacheDir);
        }
    }

    private static function delTree($dir)
    {
        $files = array_diff(scandir($dir), array(
            '.',
            '..'
        ));
        foreach ($files as $file) {
            (is_dir("$dir/$file")) ? self::selTree("$dir/$file") : unlink("$dir/$file");
        }
        return rmdir($dir);
    }

    public function tearDown()
    {
        if ($this->cacheDir && is_dir($this->cacheDir)) {
            self::delTree($this->cacheDir);
        }
    }

    public function testWriter()
    {
        $writer = new Psr4PathGenerator(array(
            'myns\\' => $this->cacheDir
        ));
        ;

        $writer->write([
            'myns\\Bar' => '.'
        ], '.');

        $filename = $this->cacheDir . "/Bar.yml";

        $this->assertFileExists($filename);
        $this->assertEquals('.', file_get_contents($filename));
    }

    public function testWriterLong()
    {
        $writer = new Psr4PathGenerator(array(
            'myns\\' => $this->cacheDir
        ));
        ;

        $writer->write([
            'myns\foo\Bar' => '.'
        ], '.');

        $filename = $this->cacheDir . "/foo.Bar.yml";

        $this->assertFileExists($filename);
        $this->assertEquals('.', file_get_contents($filename));
    }

    public function testNonExistingDir()
    {
        $this->setExpectedException('Exception');
        new Psr4PathGenerator(array(
            'myns\\' => "aaaa"
        ));
    }

    public function testNoNs()
    {
        $this->setExpectedException('Exception');
        $writer = new Psr4PathGenerator(array(
            'myns\\' => $this->cacheDir
        ));
        $writer->write([
            'myns2\\Bar' => '.'
        ], '.');
    }

    public function testInvalidNs()
    {
        $this->setExpectedException('Exception');
        new Psr4PathGenerator(array(
            'myns' => "aaaa"
        ));
    }
}