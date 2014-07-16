<?php
namespace Goetas\Xsd\XsdToPhp\Command;

use Goetas\Xsd\XsdToPhp\Xsd2PhpConverter;
use Exception;
use Goetas\Xsd\XsdToPhp\Generator\ClassGenerator;
use Goetas\Xsd\XsdToPhp\YamlWriter\Psr4Writer;
use Goetas\XML\XSDReader\SchemaReader;
use Goetas\Xsd\XsdToPhp\Xsd2JmsSerializerYamlConverter;
use Symfony\Component\Yaml\Dumper;
use Goetas\Xsd\XsdToPhp\AbstractXsd2Converter;
use Symfony\Component\Console\Output\OutputInterface;

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

    protected function getConverterter()
    {
        return new Xsd2JmsSerializerYamlConverter();
    }

    protected function convert(AbstractXsd2Converter $converter, array $schemas, array $targets, OutputInterface $output)
    {
        $items = $converter->convert($schemas);

        $dumper = new Dumper();

        $writer = new Psr4Writer($targets);
        $progress = $this->getHelperSet()->get('progress');

        $progress->start($output, count($items));

        foreach ($items as $item) {
            $progress->advance(1, true);
            $output->write(" Item <info>" . key($item) . "</info>... ");

            $source = $dumper->dump($item, 10000);
            $output->write("created source... ");

            $bytes = $writer->write($item, $source);
            $output->writeln("saved source <comment>$bytes bytes</comment>.");
        }
        $progress->finish();
    }
}
