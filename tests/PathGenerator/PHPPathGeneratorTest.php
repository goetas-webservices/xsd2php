<?php
namespace Goetas\Xsd\XsdToPhp\Tests\Php\PathGenerator;

use Goetas\Xsd\XsdToPhp\Php\PathGenerator\Psr4PathGenerator;
use Goetas\Xsd\XsdToPhp\Php\Structure\PHPClass;

class PHPPathGeneratorTest extends \PHPUnit_Framework_TestCase
{
    protected $tmpdir;

    public function setUp()
    {
        $tmp = sys_get_temp_dir();

        if (is_writable("/dev/shm")) {
            $tmp = "/dev/shm";
        }

        $this->tmpdir = "$tmp/PathGeneratorTest";
        if (!is_dir($this->tmpdir)) {
            mkdir($this->tmpdir);
        }
    }

    public function testNoNs()
    {
        $this->setExpectedException('Goetas\Xsd\XsdToPhp\PathGenerator\PathGeneratorException');
        $generator = new Psr4PathGenerator(array(
            'myns\\' => $this->tmpdir
        ));
        $generator->getPath(new PHPClass('Bar', 'myns2'));
    }

    public function testWriterLong()
    {
        $generator = new Psr4PathGenerator(array(
            'myns\\' => $this->tmpdir
        ));

        $path = $generator->getPath(new PHPClass('Bar', 'myns\foo'));

        $this->assertEquals($path, $this->tmpdir . "/foo/Bar.php");
    }

    public function testWriter()
    {
        $generator = new Psr4PathGenerator(array(
            'myns\\' => $this->tmpdir
        ));

        $path = $generator->getPath(new PHPClass('Bar', 'myns'));

        $this->assertEquals($path, $this->tmpdir . "/Bar.php");
    }

    public function testNonExistingDir()
    {
        $this->setExpectedException('Goetas\Xsd\XsdToPhp\PathGenerator\PathGeneratorException');
        new Psr4PathGenerator(array(
            'myns\\' => "aaaa"
        ));
    }

    public function testInvalidNs()
    {
        $this->setExpectedException('Goetas\Xsd\XsdToPhp\PathGenerator\PathGeneratorException');
        new Psr4PathGenerator(array(
            'myns' => "aaaa"
        ));
    }
}