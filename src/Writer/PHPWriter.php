<?php
namespace GoetasWebservices\Xsd\XsdToPhp\Writer;

use GoetasWebservices\Xsd\XsdToPhp\Php\ClassGenerator;
use GoetasWebservices\Xsd\XsdToPhp\Php\Structure\PHPClass;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class PHPWriter extends Writer implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    protected $classWriter;
    private $generator;

    public function __construct(PHPClassWriter $classWriter, ClassGenerator $generator, LoggerInterface $logger = null)
    {
        $this->generator = $generator;
        $this->classWriter = $classWriter;
        $this->logger = $logger ?: new NullLogger();
    }

    public function write(array $items)
    {
        return $this->classWriter->write(array_filter(array_map(function (PHPClass $item) {
            return $this->generator->generate($item);
        }, $items)));
    }
}
