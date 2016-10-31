<?php
namespace GoetasWebservices\Xsd\XsdToPhp\Command;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Loader\DelegatingLoader;
use Symfony\Component\Config\Loader\LoaderResolver;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

class Convert extends Command
{
    /**
     * @var ContainerInterface
     */
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
        $this->setDescription("Convert a XSD file into PHP classes and JMS serializer metadata files");
        $this->setDefinition(array(
            new InputArgument('config', InputArgument::REQUIRED, 'Where is located your XSD definitions'),
            new InputArgument('src', InputArgument::REQUIRED | InputArgument::IS_ARRAY, 'Where is located your XSD definitions'),
        ));
    }

    /**
     *
     * @see Console\Command\Command
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->loadConfigurations($input->getArgument('config'));
        $src = $input->getArgument('src');

        $schemas = [];
        $reader = $this->container->get('goetas_webservices.xsd2php.schema_reader');
        foreach ($src as $file) {
            $schemas[] = $reader->readFile($file);
        }
        $configs = $this->container->getParameter('goetas_webservices.xsd2php.config');

        foreach (['php', 'jms', 'validation'] as $type) {

            if ($type === 'validation' && empty($configs['destinations_' . $type])) {
                continue;
            }
            $converter = $this->container->get('goetas_webservices.xsd2php.converter.' . $type);
            $items = $converter->convert($schemas);

            $writer = $this->container->get('goetas_webservices.xsd2php.writer.' . $type);
            $writer->write($items);
        }
        return count($items) ? 0 : 255;
    }

    protected function loadConfigurations($configFile)
    {
        $locator = new FileLocator('.');
        $yaml = new YamlFileLoader($this->container, $locator);
        $xml = new XmlFileLoader($this->container, $locator);

        $delegatingLoader = new DelegatingLoader(new LoaderResolver(array($yaml, $xml)));
        $delegatingLoader->load($configFile);

        $this->container->compile();

    }
}
