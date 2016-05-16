<?php
namespace Goetas\Xsd\XsdToPhp\Php;

use Goetas\Xsd\XsdToPhp\Php\PathGenerator\PathGenerator;
use Zend\Code\Generator\FileGenerator;

class ClassWriter
{
    private $pathGenerator;

    public function __construct(PathGenerator $pathGenerator)
    {
        $this->pathGenerator = $pathGenerator;
    }

    public function write(array $items)
    {
        $generator = new ClassGenerator();

        foreach ($items as $item) {

            $path = $this->pathGenerator->getPath($item);

            $fileGen = new FileGenerator();
            $fileGen->setFilename($path);
            $classGen = new \Zend\Code\Generator\ClassGenerator();
            if ($generator->generate($classGen, $item)) {
                $fileGen->setClass($classGen);
                $fileGen->write();
            }
        }
    }
}