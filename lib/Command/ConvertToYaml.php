<?php
namespace Goetas\Xsd\XsdToPhp\Command;

use Goetas\Xsd\XsdToPhp\AbstractConverter;
use Goetas\Xsd\XsdToPhp\Jms\PathGenerator\Psr4PathGenerator;
use Goetas\Xsd\XsdToPhp\Jms\YamlConverter;
use Goetas\Xsd\XsdToPhp\Naming\NamingStrategy;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Dumper;

class ConvertToYaml extends AbstractConvert
{

    /**
     *
     * @see Console\Command\Command
     */
    protected function configure()
    {
        parent::configure();
        $this->setName('convert:jms-yaml');
        $this->setDescription('Convert XSD definitions into YAML metadata for JMS Serializer');
    }

    protected function getConverterter(NamingStrategy $naming)
    {
        return new YamlConverter($naming);
    }

    protected function convert(AbstractConverter $converter, array $schemas, array $targets, OutputInterface $output)
    {
        $items = $converter->convert($schemas);

        $dumper = new Dumper();

        $pathGenerator = new Psr4PathGenerator($targets);

        $progress = new ProgressBar($output, count($items));
        $progress->start();

        foreach ($items as $item) {
            $progress->advance();
            $output->write(" Item <info>" . key($item) . "</info>... ");

            $source = $dumper->dump($item, 10000);
            $output->write("created source... ");

            $path = $pathGenerator->getPath($item);
            $bytes = file_put_contents($path, $source);
            $output->writeln("saved source <comment>$bytes bytes</comment>.");
        }
    }
}
