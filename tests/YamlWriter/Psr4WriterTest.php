<?php
namespace Goetas\Xsd\XsdToPhp\Tests\YamlWriter;

use Goetas\Xsd\XsdToPhp\YamlWriter\Psr4Writer;
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

        $writer = new Psr4Writer(array(
            'myns\\'=> $this->cacheDir
        ));
        ;

        $writer->write([
            'myns\\Bar' => '.'
        ], '.');

        $filename = $this->cacheDir  . "/Bar.yml";

        $this->assertFileExists($filename);
        $this->assertEquals('.', file_get_contents($filename));
    }
    public function testWriterLong()
    {

        $writer = new Psr4Writer(array(
            'myns\\'=> $this->cacheDir
        ));
        ;

        $writer->write([
            'myns\foo\Bar' => '.'
            ], '.');

        $filename = $this->cacheDir  . "/foo.Bar.yml";

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
    public function testNoNs()
    {
        $this->setExpectedException('Exception');
        $writer = new Psr4Writer(array(
            'myns\\'=> $this->cacheDir
        ));
        $writer->write([
            'myns2\\Bar' => '.'
        ], '.');
    }
    public function testInvalidNs()
    {

        $this->setExpectedException('Exception');
        new Psr4Writer(array(
            'myns'=> "aaaa"
        ));

    }
}