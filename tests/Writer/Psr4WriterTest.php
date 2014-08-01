<?php
namespace Goetas\Xsd\XsdToPhp\Tests\Writer;

use Goetas\Xsd\XsdToPhp\Writer\Psr4Writer;
use Goetas\Xsd\XsdToPhp\Structure\PHPClass;

class Psr4WriterTest extends \PHPUnit_Framework_TestCase
{

    protected $cacheDir = null;
    public function setUp()
    {
        $this->cacheDir = sys_get_temp_dir()."/Psr4WriterTest";

        $this->tearDown();

        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir);
        }
    }

    private static function delTree($dir) {
        $files = array_diff(scandir($dir), array('.','..'));
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
        $writer = new Psr4Writer(array(
            'myns\\'=> $this->cacheDir
        ));
        $writer->write(new PHPClass('Bar', 'myns2'), '.');
    }
    public function testWriterLong()
    {

        $writer = new Psr4Writer(array(
            'myns\\'=> $this->cacheDir
        ));

        $writer->write(new PHPClass('Bar', 'myns\foo'), '.');

        $filename = $this->cacheDir  . "/foo/Bar.php";

        $this->assertFileExists($filename);
        $this->assertEquals('.', file_get_contents($filename));
    }
    public function testWriter()
    {

        $writer = new Psr4Writer(array(
            'myns\\'=> $this->cacheDir
        ));

        $writer->write(new PHPClass('Bar', 'myns'), '.');

        $filename = $this->cacheDir  . "/Bar.php";

        $this->assertFileExists($filename);
        $this->assertEquals('.', file_get_contents($filename));
    }
    public function testNonExistingDir()
    {

        $this->setExpectedException('Exception');
        new Psr4Writer(array(
            'myns\\'=> "aaaa"
        ));

    }
    public function testInvalidNs()
    {

        $this->setExpectedException('Exception');
        new Psr4Writer(array(
            'myns'=> "aaaa"
        ));

    }
}