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
        $locator = new FileLocator('.');
        $yaml = new YamlFileLoader($this->container, $locator);
        $xml = new XmlFileLoader($this->container, $locator);

        $delegatingLoader = new DelegatingLoader(new LoaderResolver(array($yaml, $xml)));
        $delegatingLoader->load($input->getArgument('config'));

        $this->container->compile();

        $src = $input->getArgument('src');

        $schemas = [];
        $reader = $this->container->get('goetas.xsd2php.schema_reader');
        foreach ($src as $file) {
            $schemas[] = $reader->readFile($file);
        }

        foreach (['php', 'jms'] as $type) {
            $converter = $this->container->get('goetas.xsd2php.converter.'.$type);
            $items = $converter->convert($schemas);

            $writer = $this->container->get('goetas.xsd2php.writer.'.$type);
            $writer->write($items);
        }
        return count($items) ? 0 : 255;
    }
}
