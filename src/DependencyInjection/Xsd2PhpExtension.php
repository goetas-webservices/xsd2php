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
        foreach ($config['known_locations'] as $remote => $local) {
            $schemaReader->addMethodCall('addKnownSchemaLocation', [$remote, $local]);
        }
        foreach ($config['known_namespace_locations'] as $namespace => $location) {
            $schemaReader->addMethodCall('addKnownNamespaceSchemaLocation', [$namespace, $location]);
        }

        foreach (['php', 'jms', 'validation'] as $type) {
            $definition = $container->getDefinition('goetas_webservices.xsd2php.path_generator.' . $type . '.' . $config['path_generator']);
            $container->setDefinition('goetas_webservices.xsd2php.path_generator.' . $type, $definition);

            $pathGenerator = $container->getDefinition('goetas_webservices.xsd2php.path_generator.' . $type);
            if (!empty($config['destinations_' . $type])) {
                $pathGenerator->addMethodCall('setTargets', [$config['destinations_' . $type]]);
            }

            $converter = $container->getDefinition('goetas_webservices.xsd2php.converter.' . $type);
            foreach ($config['namespaces'] as $xml => $php) {
                $converter->addMethodCall('addNamespace', [$xml, self::sanitizePhp($php)]);
            }
            foreach ($config['aliases'] as $xml => $data) {
                foreach ($data as $type => $php) {
                    $converter->addMethodCall('addAliasMapType', [$xml, $type, self::sanitizePhp($php)]);
                }
            }
            foreach ($config['prefixes'] as $target => $data) {
                foreach ($data as $element => $prefix) {
                    $converter->addMethodCall('addRootPrefix', [$target, $element, $prefix]);
                }
            }
        }

        if ($config['configs_jms']) {
            $converter = $container->getDefinition('goetas_webservices.xsd2php.converter.jms');
            $converter->addMethodCall('setUseCdata', [$config['configs_jms']['xml_cdata']]);
        }

        $container->setParameter('goetas_webservices.xsd2php.config', $config);
    }

    protected static function sanitizePhp($ns)
    {
        return strtr($ns, '/', '\\');
    }

    public function getAlias(): string
    {
        return 'xsd2php';
    }
}
