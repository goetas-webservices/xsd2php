<?php

namespace GoetasWebservices\Xsd\XsdToPhp\Writer;

use GoetasWebservices\Xsd\XsdToPhp\Jms\PathGenerator\PathGenerator;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\Yaml\Dumper;

class JMSWriter extends Writer implements LoggerAwareInterface
{
    use LoggerAwareTrait;
    /**
     * @var PathGenerator
     */
    private $pathGenerator;

    public function __construct(PathGenerator $pathGenerator, ?LoggerInterface $logger = null)
    {
        $this->pathGenerator = $pathGenerator;
        $this->logger = $logger ?: new NullLogger();
    }

    public function write(array $items)
    {
        $dumper = new Dumper();
        foreach ($items as $item) {
            $source = $dumper->dump($item, 10000);
            $path = $this->pathGenerator->getPath($item);
            file_put_contents($path, $source);
            $this->logger->debug(sprintf('Written JMS metadata file %s', $path));
        }
        $this->logger->info(sprintf('Written %s JMS metadata files ', count($items)));
    }
}
