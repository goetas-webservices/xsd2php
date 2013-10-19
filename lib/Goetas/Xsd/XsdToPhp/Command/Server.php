<?php

namespace Goetas\Xsd\XsdToPhp\Command;

use Goetas\Xsd\XsdToPhp\Xsd2PhpServer;

use Symfony\Component\Console\Input\InputArgument,
    Symfony\Component\Console\Input\InputOption,
    Symfony\Component\Console,
    Doctrine\ORM\Tools\Console\MetadataFilter,
    Doctrine\ORM\Tools\EntityRepositoryGenerator;

class Server extends Console\Command\Command
{
    /**
     * @see Console\Command\Command
     */
    protected function configure()
    {
        $this
        ->setName('convert')
        ->setDescription('Convert XSD into PHP.')
        ->setDefinition(array(
            new InputArgument(
                'source', InputArgument::REQUIRED, 'The path where save your XSD.'
            ),
            new InputArgument(
                'destination', InputArgument::REQUIRED, 'The path where save your XSD.'
            ),
            new InputArgument(
                'target-ns', InputArgument::REQUIRED, 'The target namespace for your XSD'
            ),
            new InputArgument(
                'target-ns-php-namespace', InputArgument::REQUIRED, 'The PHP namespace for your target namespace'
            ),
            new InputOption(
                'is-client', null, null,
                'PHP namespaces - XML namepsaces map Syntax = PHPns:XMLns'
            ),
            new InputOption(
                'extends', null, null,
                'PHP class that generated class has to extend'
            ),
            new InputOption(
                'ns-map', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                'PHP namespaces - XML namepsaces map Syntax = PHPns:XMLns'
            ),
            new InputOption(
                'alias-map', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                'alias map type:XMLns:typeXMLns:tyep:XMLns'
            ),
            new InputOption(
                'array-map', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                'Array map Syntax = *:XMLns'
            )
        ))
        ->setHelp("Generate repository classes from your mapping information.");
    }

    /**
     * @see Console\Command\Command
     */
    protected function execute(Console\Input\InputInterface $input, Console\Output\OutputInterface $output)
    {

        $src = $input->getArgument('source');
        $destination = $input->getArgument('destination');
        $destinationNs = $input->getArgument('target-ns');
        $destinationPHP = $input->getArgument('target-ns-php-namespace');

        $allowMap = $input->getOption('ns-map');

        if (!is_dir($destination)) {
            throw new \RuntimeException("Destination must be a directory.");
        }

        $nsMap = $input->getOption('ns-map');
        if (!$nsMap) {
            throw new \RuntimeException(__CLASS__." requires at least one ns-map (for {$destinationNs} namespace).");
        }

        $output->writeln("Target namespace: <info>$destinationNs</info>");

        $converter = new Xsd2PhpServer();

        foreach ($nsMap as $val) {
            list($phpNs,$xmlNs) = explode(":", $val, 2);
            $converter->addNamespace($xmlNs, $phpNs);
            $output->writeln("PHP: <comment>$phpNs</comment> to <comment>$xmlNs</comment>");
        }

        $arrayMap = $input->getOption('array-map');
        if ($arrayMap) {
            foreach ($arrayMap as $val) {
                list($type, $xmlNs) = explode(":", $val, 2);
                $converter->addArrayType($xmlNs, $type);
                $output->writeln("Array <comment>$xmlNs</comment>#<info>$type</info> ");
            }
        }

        $result = $converter->convert($src, $destinationNs, $destination, $destinationPHP, $input->getOption('extends'),  $input->getOption('is-client'));
        if ($result) {
            foreach ($result as $class => $path) {
                $output->writeln("Saved <info>$class</info> to <comment>$path</comment>");
            }
            return 0;
        }

        return 1;
    }
}
