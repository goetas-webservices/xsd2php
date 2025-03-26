<?php
/**
 * @package
 * @author     David Pommer (conlabz GmbH) <david.pommer@conlabz.de>
 */

namespace GoetasWebservices\Xsd\XsdToPhp\Tests\Issues\I182;

use GoetasWebservices\XML\XSDReader\SchemaReader;
use GoetasWebservices\Xsd\XsdToPhp\DependencyInjection\Xsd2PhpExtension;
use GoetasWebservices\Xsd\XsdToPhp\Jms\YamlConverter;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Loader\DelegatingLoader;
use Symfony\Component\Config\Loader\LoaderResolver;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

class I182Test extends TestCase
{
    protected ContainerInterface $container;
    protected SchemaReader $reader;

    protected YamlConverter $converter;
    
    public function setUp(): void
    {
        parent::setUp();

        $this->container = new ContainerBuilder();
        $this->container->registerExtension(
            new Xsd2PhpExtension()
        );
        $this->container->set('logger', new NullLogger());

        $this->reader = new SchemaReader();
    }

    public function testXmlRootPrefix()
    {
        $this->loadConfigurations(__DIR__ . '/config.yml');

        $schema = $this->reader->readFile(__DIR__ . '/data.xsd');

        $converter = $this->container->get('goetas_webservices.xsd2php.converter.jms');

        $actual = $converter->convert([$schema]);

        $expected = [
            'Example\\Root\\RootAType' => [
                'Example\\Root\\RootAType' => [
                    'properties' => [
                        'child' => [
                            'expose' => true,
                            'access_type' => 'public_method',
                            'serialized_name' => 'child',
                            'accessor' => [
                                'getter' => 'getChild',
                                'setter' => 'setChild',
                            ],
                            'xml_list' => [
                                'inline' => true,
                                'entry_name' => 'child',
                            ],
                            'type' => 'array<Example\\ChildType>',
                        ],
                        'childRoot' => [
                            'expose' => true,
                            'access_type' => 'public_method',
                            'serialized_name' => 'childRoot',
                            'xml_element' => [
                                'namespace' => 'http://www.example.com',
                            ],
                            'accessor' => [
                                'getter' => 'getChildRoot',
                                'setter' => 'setChildRoot',
                            ],
                            'type' => 'Example\\ChildType',
                        ],
                    ],
                ],
            ],
            'Example\\Root' => [
                'Example\\Root' => [
                    'xml_root_name' => 'ns-8ece61d2:root',
                    'xml_root_namespace' => 'http://www.example.com',
                    'xml_root_prefix' => 'prefix'
                ],
            ],
            'Example\\ChildType' => [
                'Example\\ChildType' => [
                    'properties' => [
                        'id' => [
                            'expose' => true,
                            'access_type' => 'public_method',
                            'serialized_name' => 'id',
                            'accessor' => [
                                'getter' => 'getId',
                                'setter' => 'setId',
                            ],
                            'type' => 'string',
                        ],
                    ],
                ],
            ],
        ];

        $this->assertEquals($expected, $actual);
    }

    private function loadConfigurations($configFile)
    {
        $locator = new FileLocator('.');
        $yaml = new YamlFileLoader($this->container, $locator);
        $xml = new XmlFileLoader($this->container, $locator);

        $delegatingLoader = new DelegatingLoader(new LoaderResolver([$yaml, $xml]));
        $delegatingLoader->load($configFile);

        $this->container->compile();
    }
}