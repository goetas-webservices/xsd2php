<?php
namespace GoetasWebservices\Xsd\XsdToPhp\Writer;

use GoetasWebservices\Xsd\XsdToPhp\Php\ClassWriter;
use GoetasWebservices\Xsd\XsdToPhp\Php\PathGenerator\PathGenerator;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class PHPWriter extends Writer implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    protected $pathGenerator;
    public function __construct(PathGenerator $pathGenerator, LoggerInterface $logger = null)
    {
        $this->pathGenerator = $pathGenerator;
        $this->logger = $logger?: new NullLogger();
    }
    public function write(array $items)
    {
        $writer = new ClassWriter($this->pathGenerator, $this->logger);
        $writer->write($items);
    }
}