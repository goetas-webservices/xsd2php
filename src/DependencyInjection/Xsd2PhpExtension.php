<?php
namespace GoetasWebservices\Xsd\XsdToPhp\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;

class Xsd2PhpExtension extends Extension
{

    public function load(array $configs, ContainerBuilder $container)
    {
        $xml = new XmlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $xml->load('services.xml');

        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);
        foreach ($configs as $subConfig) {
            $config = array_merge($config, $subConfig);
        }

        $definition = $container->getDefinition('goetas_webservices.xsd2php.naming_convention.' . $config['naming_strategy']);
        $container->setDefinition('goetas_webservices.xsd2php.naming_convention', $definition);


        $schemaReader = $container->getDefinition('goetas_webservices.xsd2php.schema_reader');
        foreach ($config['known_locations'] as $namespace => $location) {
            $schemaReader->addMethodCall('addKnownSchemaLocation', [$namespace, $location]);
        }

        foreach (['php', 'jms'] as $type) {
            $definition = $container->getDefinition('goetas_webservices.xsd2php.path_generator.' . $type . '.' . $config['path_generator']);
            $container->setDefinition('goetas_webservices.xsd2php.path_generator.' . $type, $definition);

            $pathGenerator = $container->getDefinition('goetas_webservices.xsd2php.path_generator.' . $type);
            $pathGenerator->addMethodCall('setTargets', [$config['destinations_' . $type]]);

            $converter = $container->getDefinition('goetas_webservices.xsd2php.converter.' . $type);
            foreach ($config['namespaces'] as $xml => $php) {
                $converter->addMethodCall('addNamespace', [$xml, self::sanitizePhp($php)]);
            }
            $converter->addMethodCall('setCdata', [$config['cdata'] == 'true']);
            foreach ($config['aliases'] as $xml => $data) {
                foreach ($data as $type => $php) {
                    $converter->addMethodCall('addAliasMapType', [$xml, $type, self::sanitizePhp($php)]);
                }
            }
        }

        $container->setParameter('goetas_webservices.xsd2php.config', $config);
    }

    protected static function sanitizePhp($ns)
    {
        return strtr($ns, '/', '\\');
    }

    public function getAlias()
    {
        return 'xsd2php';
    }
}
