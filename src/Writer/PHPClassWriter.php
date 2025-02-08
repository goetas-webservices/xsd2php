<?php

namespace GoetasWebservices\Xsd\XsdToPhp\Writer;

use GoetasWebservices\Xsd\XsdToPhp\Php\ClassGenerator;
use GoetasWebservices\Xsd\XsdToPhp\Php\PathGenerator\PathGenerator;
use Laminas\Code\Generator\FileGenerator;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class PHPClassWriter implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    protected $pathGenerator;

    public function __construct(PathGenerator $pathGenerator, ?LoggerInterface $logger = null)
    {
        $this->pathGenerator = $pathGenerator;
        $this->logger = $logger ?: new NullLogger();
    }

    /**
     * @param ClassGenerator[] $items
     */
    public function write(array $items)
    {
        foreach ($items as $item) {
            $path = $this->pathGenerator->getPath($item);

            $fileGen = new FileGenerator();
            $fileGen->setFilename($path);
            $fileGen->setClass($item);
            $fileGen->write();
            $this->logger->debug(sprintf('Written PHP class file %s', $path));
        }
        $this->logger->info(sprintf('Written %s STUB classes', count($items)));
    }
}
