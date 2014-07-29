<?php
namespace Goetas\Xsd\XsdToPhp\Command;

use Goetas\Xsd\XsdToPhp\Xsd2PhpConverter;
use Goetas\Xsd\XsdToPhp\Generator\ClassGenerator;
use Goetas\Xsd\XsdToPhp\Writer\Psr0Writer;
use Goetas\Xsd\XsdToPhp\Writer\Psr4Writer;
use Goetas\XML\XSDReader\SchemaReader;
use Goetas\Xsd\XsdToPhp\AbstractXsd2Converter;
use Symfony\Component\Console\Output\OutputInterface;

class ConvertToPHP extends AbstractConvert
{

    /**
     *
     * @see Console\Command\Command
     */
    protected function configure()
    {
        parent::configure();
        $this->setName('convert:php');
        $this->setDescription('Convert XSD definitions into PHP classes');
    }

    protected function getConverterter()
    {
        return new Xsd2PhpConverter();
    }

    protected function convert(AbstractXsd2Converter $converter, array $schemas, array $targets, OutputInterface $output)
    {
        $generator = new ClassGenerator();

        $writer = new Psr4Writer($targets);
        $progress = $this->getHelperSet()->get('progress');

        $items = $converter->convert($schemas);
        $progress->start($output, count($items));

        foreach ($items as $item) {
            $progress->advance(1, true);

            $output->write(" Item <info>" . $item->getFullName() . "</info>... ");

            $source = $generator->generate($item);
            $output->write("created source... ");

            $bytes = $writer->write($item, $source);
            $output->writeln("saved source <comment>$bytes bytes</comment>.");
        }
        $progress->finish();
    }
}
