<?php
namespace GoetasWebservices\Xsd\XsdToPhp\Command;

use Exception;
use GoetasWebservices\Xsd\XsdToPhp\Php\ClassWriter;
use GoetasWebservices\Xsd\XsdToPhp\Php\PathGenerator\Psr4PathGenerator;
use Symfony\Component\Console;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Yaml\Dumper;

class Convert extends Console\Command\Command
{
    protected $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        parent::__construct();
    }

    /**
     *
     * @see Console\Command\Command
     */
    protected function configure()
    {
        $this->setName('convert');

        $this->setDefinition(array(
            new InputArgument('format', InputArgument::REQUIRED, 'Format'),
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
    protected function execute(Console\Input\InputInterface $input, Console\Output\OutputInterface $output)
    {
        $this->container->set('logger', new \Symfony\Component\Console\Logger\ConsoleLogger($output));
        $naming = $this->container->get('goetas.xsd2php.naming_convention.' . $input->getOption('naming-strategy'));
        $this->container->set('goetas.xsd2php.naming_convention', $naming);
        $logger = $this->container->get('logger');
        $pathGenerator = $this->container->get('goetas.xsd2php.path_generator.jms.psr4');
        $this->container->set('goetas.xsd2php.path_generator.jms', $pathGenerator);

        $pathGenerator = $this->container->get('goetas.xsd2php.path_generator.php.psr4');
        $this->container->set('goetas.xsd2php.path_generator.php', $pathGenerator);

        $format = strtr($input->getArgument('format'), '-', '_');
        $converter = $this->container->get('goetas.xsd2php.converter.' . $format);

        $nsMap = $input->getOption('ns-map');
        if (!$nsMap) {
            throw new \RuntimeException(__CLASS__ . ' requires at least one ns-map.');
        }
        foreach ($nsMap as $val) {
            if (substr_count($val, ';') !== 1) {
                throw new Exception('Invalid syntax for --ns-map');
            }
            list ($xmlNs, $phpNs) = explode(';', $val, 2);
            $converter->addNamespace($xmlNs, trim(strtr($phpNs, './', '\\\\'), '\\'));
        }

        $nsTarget = $input->getOption('ns-dest');
        if (!$nsTarget) {
            throw new \RuntimeException(__CLASS__ . ' requires at least one ns-dest.');
        }
        $targets = array();
        foreach ($nsTarget as $val) {
            if (substr_count($val, ';') !== 1) {
                throw new Exception('Invalid syntax for --ns-dest');
            }
            list ($phpNs, $dir) = explode(';', $val, 2);
            $phpNs = strtr($phpNs, './', '\\\\');

            $targets[$phpNs] = $dir;
        }
        $arrayMap = $input->getOption('alias-map');
        foreach ($arrayMap as $val) {
            if (substr_count($val, ';') !== 2) {
                throw new Exception('Invalid syntax for --alias-map');
            }
            list ($xmlNs, $name, $type) = explode(';', $val, 3);
            $converter->addAliasMapType($xmlNs, $name, $type);
        }

        $src = $input->getArgument('src');

        $schemas = [];
        $reader = $this->container->get('goetas.xsd2php.schema_reader');
        foreach ($src as $file) {
            $schemas[] = $reader->readFile($file);
            $logger->info(sprintf('Reading %s', $file));
        }
        $items = $converter->convert($schemas);

        $pathGenerator = $this->container->get('goetas.xsd2php.path_generator.' . $format . '.psr4');
        $pathGenerator->setTargets($targets);

        $writer = $this->container->get('goetas.xsd2php.writer.' . $format);
        $writer->write($items);
        $logger->info(sprintf('Writing %s items', count($items)));

        return 0;
    }
}
