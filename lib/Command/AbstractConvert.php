<?php

namespace Goetas\Xsd\XsdToPhp\Command;

use Goetas\Xsd\XsdToPhp\Xsd2PhpConverter;

use Exception;


use Symfony\Component\Console\Input\InputArgument,
    Symfony\Component\Console\Input\InputOption,
    Symfony\Component\Console,
    Doctrine\ORM\Tools\Console\MetadataFilter,
    Doctrine\ORM\Tools\EntityRepositoryGenerator;
use Goetas\Xsd\XsdToPhp\Generator\ClassGenerator;
use Goetas\Xsd\XsdToPhp\Writer\Psr0Writer;
use Goetas\Xsd\XsdToPhp\YamlWriter\Psr4Writer;
use Goetas\XML\XSDReader\SchemaReader;
use Goetas\Xsd\XsdToPhp\Xsd2JmsSerializerYamlConverter;
use Symfony\Component\Yaml\Dumper;
use Goetas\Xsd\XsdToPhp\AbstractXsd2Converter;
use Symfony\Component\Console\Output\OutputInterface;

abstract class AbstractConvert extends Console\Command\Command
{
    /**
     * @see Console\Command\Command
     */
    protected function configure()
    {
        $this
        ->setDefinition(array(
            new InputArgument(
                'src', InputArgument::REQUIRED|InputArgument::IS_ARRAY, 'Where is located your XSD definitions'
            ),
            new InputOption(
                'ns-map', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                'How to map XML namespaces to PHP namespaces? Syntax: <info>XML-namespace;PHP-namespace</info>'
            ),
            new InputOption(
                'ns-dest', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                'Where place the generated files? Syntax: <info>PHP-namespace;destination-directory</info>'
            ),
            new InputOption(
                'alias-map', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                'How to map XML namespaces into existing PHP classes? Syntax: <info>XML-namespace#XML-type;PHP-type</info>. '
            )
        ))
        ;
    }
    /**
     *
     */
    protected abstract  function getConverterter();

    /**
     * @see Console\Command\Command
     */
    protected function execute(Console\Input\InputInterface $input, Console\Output\OutputInterface $output)
    {

        $src = $input->getArgument('src');


        $nsMap = $input->getOption('ns-map');
        if (!$nsMap) {
            throw new \RuntimeException(__CLASS__." requires at least one ns-map (for {$destinationNs} namespace).");
        }

        $nsTarget = $input->getOption('ns-dest');
        if (!$nsTarget) {
            throw new \RuntimeException(__CLASS__." requires at least one ns-target.");
        }


        $converter = $this->getConverterter();


        $nsMapKeyed = array();
        $output->writeln("Namespaces:");
        foreach ($nsMap as $val) {
            if (strpos($val, ';')===false){
                throw new Exception("Invalid syntax for --ns-map option");
            }
            list($xmlNs, $phpNs) = explode(";", $val, 2);
            $nsMapKeyed[$xmlNs]=$phpNs;
            $converter->addNamespace($xmlNs, trim(strtr($phpNs, "./", "\\\\"), "\\"));
            $output->writeln("\tXML namepsace: <comment>$xmlNs </comment> => PHP namepsace: <comment>$phpNs </comment>");
        }
        $targets = array();
        $output->writeln("Targets:");
        foreach ($nsTarget as $val) {
            if (strpos($val, ';')===false){
                throw new Exception("Invalid syntax for --ns-dest option");
            }
            list($phpNs, $dir) = explode(";", $val, 2);
            $phpNs = strtr($phpNs, "./", "\\\\");

            $targets[$phpNs]=$dir;
            $output->writeln("\tPHP namepsace: <comment>".strtr($phpNs, "\\", "/")."</comment> => Folder: <comment>$dir </comment>");
        }
        $arrayMap = $input->getOption('alias-map');
        if ($arrayMap) {
            foreach ($arrayMap as $val) {
                if (strpos($val, ';')===false){
                    throw new Exception("Invalid syntax for --array-map option");
                }
                list($xml, $type) = explode(";", $val, 2);
                list($xmlNs, $name) = explode("#", $xml, 2);
                $converter->addAliasMap($xmlNs, $name, $type);
                $output->writeln("Alias <comment>$xmlNs</comment>#<info>$name $type</info> ");
            }
        }

        $reader = new SchemaReader();
        $schemas = array();
        foreach($src as $file){
            $output->writeln("Reading <comment>$file</comment>");

            $xml = new \DOMDocument('1.0', 'UTF-8');
            if (! $xml->load($file)) {
                throw new \Exception("Can't load the schema '{$file}'");
            }


            if (!$xml->documentElement->hasAttribute("targetNamespace") || !isset($nsMapKeyed[$xml->documentElement->getAttribute("targetNamespace")])) {
                $output->writeln("\tSkipping <comment>".$xml->documentElement->getAttribute("targetNamespace")."</comment>, can't find a PHP-equivalent namespace. Use --ns-map option?");
                continue;
            }

            $schema = $reader->readFile($file);

            $schemas[spl_object_hash($schema)]=$schema;
        }
        $this->convert($converter, $schemas, $targets, $output);

        return 1;
    }
    protected abstract function convert(AbstractXsd2Converter $converter, array $schemas, array $targets, OutputInterface $output);
}
