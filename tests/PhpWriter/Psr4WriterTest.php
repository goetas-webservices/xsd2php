<?php
namespace Goetas\Xsd\XsdToPhp\Tests\Php\PathGenerator;

use Goetas\Xsd\XsdToPhp\Php\PathGenerator\Psr4PathGenerator;
use Goetas\Xsd\XsdToPhp\Php\Structure\PHPClass;

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
            (is_dir("$dir/$file")) ? self::delTree("$dir/$file") : unlink("$dir/$file");
        }
        return rmdir($dir);
    }

    public function tearDown()
    {
        if ($this->cacheDir && is_dir($this->cacheDir)) {
            self::delTree($this->cacheDir);
        }
    }

    public function testNoNs()
    {
        $this->setExpectedException('Exception');
        $writer = new Psr4PathGenerator(array(
            'myns\\' => $this->cacheDir
        ));
        $writer->write(new PHPClass('Bar', 'myns2'), '.');
    }

    public function testWriterLong()
    {
        $writer = new Psr4PathGenerator(array(
            'myns\\' => $this->cacheDir
        ));

        $writer->write(new PHPClass('Bar', 'myns\foo'), '.');

        $filename = $this->cacheDir . "/foo/Bar.php";

        $this->assertFileExists($filename);
        $this->assertEquals('.', file_get_contents($filename));
    }

    public function testWriter()
    {
        $writer = new Psr4PathGenerator(array(
            'myns\\' => $this->cacheDir
        ));

        $writer->write(new PHPClass('Bar', 'myns'), '.');

        $filename = $this->cacheDir . "/Bar.php";

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

    public function testInvalidNs()
    {
        $this->setExpectedException('Exception');
        new Psr4PathGenerator(array(
            'myns' => "aaaa"
        ));
    }
}