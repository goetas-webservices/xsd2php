<?php
namespace GoetasWebservices\Xsd\XsdToPhp\Writer;

use GoetasWebservices\Xsd\XsdToPhp\Jms\PathGenerator\PathGenerator;
use Symfony\Component\Yaml\Dumper;

class JMSWriter extends Writer
{
    /**
     * @var PathGenerator
     */
    private $pathGenerator;

    public function __construct(PathGenerator $pathGenerator)
    {
        $this->pathGenerator = $pathGenerator;
    }

    public function write(array $items)
    {
        $dumper = new Dumper();
        foreach ($items as $item) {
            $source = $dumper->dump($item, 10000);
            $path = $this->pathGenerator->getPath($item);
            file_put_contents($path, $source);
        }
    }
}