<?php
namespace Goetas\Xsd\XsdToPhp\Command;

use Exception;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console;
use Goetas\XML\XSDReader\SchemaReader;
use Goetas\Xsd\XsdToPhp\AbstractConverter;
use Symfony\Component\Console\Output\OutputInterface;
use Goetas\Xsd\XsdToPhp\Naming\ShortNamingStrategy;
use Goetas\Xsd\XsdToPhp\Naming\LongNamingStrategy;
use Goetas\Xsd\XsdToPhp\Naming\NamingStrategy;

abstract class AbstractConvert extends Console\Command\Command
{

    /**
     *
     * @see Console\Command\Command
     */
    protected function configure()
    {
        $this->setDefinition(array(
            new InputArgument('src', InputArgument::REQUIRED | InputArgument::IS_ARRAY, 'Where is located your XSD definitions'),
            new InputOption('ns-map', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'How to map XML namespaces to PHP namespaces? Syntax: <info>XML-namespace;PHP-namespace</info>'),
            new InputOption('ns-dest', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Where place the generated files? Syntax: <info>PHP-namespace;destination-directory</info>'),
            new InputOption('alias-map', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'How to map XML namespaces into existing PHP classes? Syntax: <info>XML-namespace;XML-type;PHP-type</info>. '),
            new InputOption('naming-strategy', null, InputOption::VALUE_REQUIRED, 'The naming strategy for classes. short|long', 'short')
        ));
    }

    /**
     */
    protected abstract function getConverterter(NamingStrategy $naming);

    /**
     *
     * @see Console\Command\Command
     */
    protected function execute(Console\Input\InputInterface $input, Console\Output\OutputInterface $output)
    {
        $src = $input->getArgument('src');

        $nsMap = $input->getOption('ns-map');
        if (! $nsMap) {
            throw new \RuntimeException(__CLASS__ . " requires at least one ns-map.");
        }

        $nsTarget = $input->getOption('ns-dest');
        if (! $nsTarget) {
            throw new \RuntimeException(__CLASS__ . " requires at least one ns-target.");
        }

        if($input->getOption('naming-strategy')=='short'){
            $naming = new ShortNamingStrategy();
        }elseif($input->getOption('naming-strategy')=='long'){
            $naming = new LongNamingStrategy();
        }else{
            throw new \InvalidArgumentException("Unsupported naming strategy");
        }

        $converter = $this->getConverterter($naming);

        $nsMapKeyed = array();
        $output->writeln("Namespaces:");
        foreach ($nsMap as $val) {
            if (substr_count($val, ';') !== 1) {
                throw new Exception("Invalid syntax for --ns-map");
            }
            list ($xmlNs, $phpNs) = explode(";", $val, 2);
            $nsMapKeyed[$xmlNs] = $phpNs;
            $converter->addNamespace($xmlNs, trim(strtr($phpNs, "./", "\\\\"), "\\"));
            $output->writeln("\tXML namepsace: <comment>$xmlNs</comment> => PHP namepsace: <info>$phpNs</info>");
        }
        $targets = array();
        $output->writeln("Target directories:");
        foreach ($nsTarget as $val) {
            if (substr_count($val, ';') !== 1) {
                throw new Exception("Invalid syntax for --ns-dest");
            }
            list ($phpNs, $dir) = explode(";", $val, 2);
            $phpNs = strtr($phpNs, "./", "\\\\");

            $targets[$phpNs] = $dir;
            $output->writeln("\tPHP namepsace: <comment>" . strtr($phpNs, "\\", "/") . "</comment> => Destination directory: <info>$dir</info>");
        }
        $arrayMap = $input->getOption('alias-map');
        if ($arrayMap) {
            $output->writeln("Aliases:");
            foreach ($arrayMap as $val) {
                if (substr_count($val, ';') !== 2) {
                    throw new Exception("Invalid syntax for --alias-map");
                }
                list ($xmlNs, $name, $type) = explode(";", $val, 3);
                $converter->addAliasMapType($xmlNs, $name, $type);
                $output->writeln("\tXML Type: <comment>$xmlNs</comment>#<comment>$name</comment>  => PHP Class: <info>$type</info> ");
            }
        }
        $reader = new SchemaReader();
        $schemas = array();
        foreach ($src as $file) {
            $output->writeln("Reading <comment>$file</comment>");

            $xml = new \DOMDocument('1.0', 'UTF-8');
            if (! $xml->load($file)) {
                throw new \Exception("Can't load the schema '{$file}'");
            }

            if (! isset($nsMapKeyed[$xml->documentElement->getAttribute("targetNamespace")])) {
                $output->writeln("\tSkipping <comment>" . $xml->documentElement->getAttribute("targetNamespace") . "</comment>, can't find a PHP-equivalent namespace. Use --ns-map option?");
                continue;
            }

            $schema = $reader->readFile($file);

            $schemas[spl_object_hash($schema)] = $schema;
        }

        $this->convert($converter, $schemas, $targets, $output);

        return 1;
    }

    protected abstract function convert(AbstractConverter $converter, array $schemas, array $targets, OutputInterface $output);
}
