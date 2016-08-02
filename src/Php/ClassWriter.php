<?php
namespace GoetasWebservices\Xsd\XsdToPhp\Php;

use GoetasWebservices\Xsd\XsdToPhp\Php\PathGenerator\PathGenerator;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Zend\Code\Generator\FileGenerator;

class ClassWriter implements LoggerAwareInterface
{
    use LoggerAwareTrait;
    private $pathGenerator;

    public function __construct(PathGenerator $pathGenerator, LoggerInterface $logger = null)
    {
        $this->pathGenerator = $pathGenerator;
        $this->logger = $logger ?: new NullLogger();
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
                $this->logger->debug(sprintf("Written PHP class file %s", $path));
            }
        }
    }
}