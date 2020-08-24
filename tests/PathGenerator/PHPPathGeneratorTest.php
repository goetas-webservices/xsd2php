<?php
namespace GoetasWebservices\Xsd\XsdToPhp\Tests\Php\PathGenerator;

use GoetasWebservices\Xsd\XsdToPhp\Php\PathGenerator\Psr4PathGenerator;
use GoetasWebservices\Xsd\XsdToPhp\Php\Structure\PHPClass;
use Laminas\Code\Generator\ClassGenerator;
use PHPUnit\Framework\TestCase;

class PHPPathGeneratorTest extends TestCase
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
            'myns\\' => $this->tmpdir
        ));
        $class = new ClassGenerator('Bar', 'myns2');
        $generator->getPath($class);
    }

    public function testWriterLong()
    {
        $generator = new Psr4PathGenerator(array(
            'myns\\' => $this->tmpdir
        ));

        $class = new ClassGenerator('Bar', 'myns\foo');
        $path = $generator->getPath($class);

        $this->assertEquals($path, $this->tmpdir . "/foo/Bar.php");
    }

    public function testWriter()
    {
        $generator = new Psr4PathGenerator(array(
            'myns\\' => $this->tmpdir
        ));
        $class = new ClassGenerator('Bar', 'myns');
        $path = $generator->getPath($class);

        $this->assertEquals($path, $this->tmpdir . "/Bar.php");
    }
}
