<?php
namespace Goetas\Xsd\XsdToPhp\Command;

use Goetas\Xsd\XsdToPhp\Php\PhpConverter;
use Goetas\Xsd\XsdToPhp\Php\ClassGenerator;
use Goetas\Xsd\XsdToPhp\Php\PathGenerator\Psr4PathGenerator;
use Goetas\XML\XSDReader\SchemaReader;
use Goetas\Xsd\XsdToPhp\AbstractConverter;
use Symfony\Component\Console\Output\OutputInterface;
use Zend\Code\Generator\FileGenerator;
use Goetas\Xsd\XsdToPhp\Naming\NamingStrategy;

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

    protected function getConverterter(NamingStrategy $naming)
    {
        return new PhpConverter($naming);
    }

    protected function convert(AbstractConverter $converter, array $schemas, array $targets, OutputInterface $output)
    {
        $generator = new ClassGenerator();
        $pathGenerator = new Psr4PathGenerator($targets);
        $progress = $this->getHelperSet()->get('progress');

        $items = $converter->convert($schemas);
        $progress->start($output, count($items));

        foreach ($items as $item) {
            $progress->advance(1, true);
            $output->write(" Creating <info>" . $output->getFormatter()->escape($item->getFullName()) . "</info>... ");
            $path = $pathGenerator->getPath($item);


            $fileGen = new FileGenerator();
            $fileGen->setFilename($path);
            $classGen = new \Zend\Code\Generator\ClassGenerator();

            if ($generator->generate($classGen, $item)) {

                $fileGen->setClass($classGen);

                $fileGen->write();
                $output->writeln("done.");
            }else{
                $output->write("skip.");

            }
        }
        $progress->finish();
    }
}
