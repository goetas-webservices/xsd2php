<?php
namespace GoetasWebservices\Xsd\XsdToPhp\Tests\Php\PathGenerator;

use GoetasWebservices\Xsd\XsdToPhp\Jms\PathGenerator\Psr4PathGenerator;
use PHPUnit\Framework\TestCase;

class JMSPathGeneratorTest extends TestCase
{
    protected $tmpdir;

    public function setUp(): void
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
        $this->expectException('GoetasWebservices\Xsd\XsdToPhp\PathGenerator\PathGeneratorException');
        $generator = new Psr4PathGenerator(array(
            'myns2\\' => $this->tmpdir
        ));
        $generator->getPath(array('myns\Bar' => true));
    }

    public function testWriterLong()
    {
        $generator = new Psr4PathGenerator(array(
            'myns\\' => $this->tmpdir
        ));

        $path = $generator->getPath(array('myns\foo\Bar' => true));
        $this->assertEquals($path, $this->tmpdir . "/foo.Bar.yml");
    }

    public function testWriter()
    {
        $generator = new Psr4PathGenerator(array(
            'myns\\' => $this->tmpdir
        ));

        $path = $generator->getPath(array('myns\Bar' => true));

        $this->assertEquals($path, $this->tmpdir . "/Bar.yml");
    }
}
