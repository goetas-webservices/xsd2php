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
            new InputOption('soap-messages', null, InputOption::VALUE_NONE),
        ));
    }

    /**
     *
     * @see Console\Command\Command
     */
    protected function execute(Console\Input\InputInterface $input, Console\Output\OutputInterface $output)
    {
        $naming = $this->container->get('goetas.xsd2php.naming_convention.' . $input->getOption('naming-strategy'));
        $this->container->set('goetas.xsd2php.naming_convention', $naming);

        $nsMap = $input->getOption('ns-map');
        if (!$nsMap) {
            throw new \RuntimeException(__CLASS__ . ' requires at least one ns-map.');
        }

        $nsTarget = $input->getOption('ns-dest');
        if (!$nsTarget) {
            throw new \RuntimeException(__CLASS__ . ' requires at least one ns-target.');
        }

        $format = strtr($input->getOption('format'), '-', '_');
        $converter = $this->container->get('goetas.xsd2php.converter.' . $format);

        foreach ($nsMap as $val) {
            if (substr_count($val, ';') !== 1) {
                throw new Exception('Invalid syntax for --ns-map');
            }
            list ($xmlNs, $phpNs) = explode(';', $val, 2);
            $converter->addNamespace($xmlNs, trim(strtr($phpNs, './', '\\\\'), '\\'));
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
        if ($arrayMap) {
            foreach ($arrayMap as $val) {
                if (substr_count($val, ';') !== 2) {
                    throw new Exception('Invalid syntax for --alias-map');
                }
                list ($xmlNs, $name, $type) = explode(';', $val, 3);
                $converter->addAliasMapType($xmlNs, $name, $type);
            }
        }
        $src = $input->getArgument('src');
        $items = $converter->run($src);

        if ($input->getOption('soap-messages')) {
            $wsdlConverter = $this->container->get('goetas.xsd2php.converter.extend.' . $format . '.soap');
            $items = array_merge($items, $wsdlConverter->run($src));
        }
        if ($format == 'php') {
            $path = new Psr4PathGenerator($targets);
            $writer = new ClassWriter($path);
            $writer->write($items);
        } else {
            $dumper = new Dumper();

            $pathGenerator = new \GoetasWebservices\Xsd\XsdToPhp\Jms\PathGenerator\Psr4PathGenerator($targets);
            foreach ($items as $item) {
                $source = $dumper->dump($item, 10000);

                $path = $pathGenerator->getPath($item);
                $bytes = file_put_contents($path, $source);
            }
        }
        return 0;
    }
}
