<?php
namespace GoetasWebservices\Xsd\XsdToPhp\Command;

use Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class Convert extends Command
{
    protected $container;
    protected $what;

    public function __construct(ContainerInterface $container, $what)
    {
        $this->container = $container;
        $this->what = $what;
        parent::__construct();
    }

    /**
     *
     * @see Console\Command\Command
     */
    protected function configure()
    {
        $this->setName('xsd2php:convert:' . $this->what);

        $this->setDefinition(array(
            new InputArgument('src', InputArgument::REQUIRED | InputArgument::IS_ARRAY, 'Where is located your XSD definitions'),
            new InputOption('ns-map', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'How to map XML namespaces to PHP namespaces? Syntax: <info>XML-namespace;PHP-namespace</info>'),
            new InputOption('ns-dest', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Where place the generated files? Syntax: <info>PHP-namespace;destination-directory</info>'),
            new InputOption('alias-map', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'How to map XML namespaces into existing PHP classes? Syntax: <info>XML-namespace;XML-type;PHP-type</info>. '),
            new InputOption('naming-strategy', null, InputOption::VALUE_REQUIRED, 'The naming strategy for classes. short|long', 'short'),
        ));
    }

    /**
     *
     * @see Console\Command\Command
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->container->set('logger', new \Symfony\Component\Console\Logger\ConsoleLogger($output));
        $naming = $this->container->get('goetas.xsd2php.naming_convention.' . $input->getOption('naming-strategy'));
        $this->container->set('goetas.xsd2php.naming_convention', $naming);
        $logger = $this->container->get('logger');
        $pathGenerator = $this->container->get('goetas.xsd2php.path_generator.' . $this->what . '.psr4');
        $this->container->set('goetas.xsd2php.path_generator.' . $this->what, $pathGenerator);

        $converter = $this->container->get('goetas.xsd2php.converter.' . $this->what);

        foreach ($this->getMapOption($input, 'ns-map', 2, 1) as list($xmlNs, $phpNs)) {
            $converter->addNamespace($xmlNs, $this->sanitizePhp($phpNs));
        }
        foreach ($this->getMapOption($input, 'alias-map', 3, 0) as list($xmlNs, $name, $phpNs)) {
            $converter->addAliasMapType($xmlNs, $name, $this->sanitizePhp($phpNs));
        }

        $src = $input->getArgument('src');

        $schemas = [];
        $reader = $this->container->get('goetas.xsd2php.schema_reader');
        foreach ($src as $file) {
            $schemas[] = $reader->readFile($file);
            $logger->info(sprintf('Reading %s', $file));
        }
        $items = $converter->convert($schemas);

        $pathGenerator = $this->container->get('goetas.xsd2php.path_generator.' . $this->what . '.psr4');

        $targets = [];
        foreach ($this->getMapOption($input, 'ns-dest', 2, 1) as list($phpNs, $path)) {
            $targets[$this->sanitizePhp($phpNs)] = $path;
        }
        $pathGenerator->setTargets($targets);

        $writer = $this->container->get('goetas.xsd2php.writer.' . $this->what);
        $writer->write($items);
        $logger->info(sprintf('Writing %s items', count($items)));

        return count($items) ? 0 : 255;
    }

    protected function getMapOption(InputInterface $input, $nsMapName, $miSplit = 2, $minRep = 0)
    {
        $nsMap = $input->getOption($nsMapName);
        if (count($nsMap) < $minRep) {
            throw new \RuntimeException(__CLASS__ . ' requires at least one ' . $nsMapName . '.');
        }
        return array_map(function ($val) use ($miSplit, $nsMapName) {
            if (substr_count($val, ';') !== ($miSplit - 1)) {
                throw new Exception('Invalid syntax for --' . $nsMapName);
            }
            return explode(';', $val, $miSplit);

        }, $nsMap);
    }

    protected function sanitizePhp($ns)
    {
        return strtr($ns, '/', '\\');
    }
}
