<?php

namespace GoetasWebservices\Xsd\XsdToPhp\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('xsd2php');

        if (method_exists($treeBuilder, 'getRootNode')) {
            $rootNode = $treeBuilder->getRootNode();
        } else {
            $rootNode = $treeBuilder->root('xsd2php');
        }

        $rootNode
            ->children()
                ->scalarNode('naming_strategy')
                    ->defaultValue('short')
                    ->cannotBeEmpty()
                ->end()
                ->scalarNode('path_generator')
                    ->defaultValue('psr4')
                    ->cannotBeEmpty()
                ->end()
                ->arrayNode('namespaces')->fixXmlConfig('namespace')
                    ->cannotBeEmpty()->isRequired()
                    ->requiresAtLeastOneElement()
                    ->prototype('scalar')
                    ->end()
                ->end()
                ->arrayNode('known_locations')->fixXmlConfig('known_location')
                    ->prototype('scalar')
                    ->end()
                ->end()
                ->arrayNode('known_namespace_locations')->fixXmlConfig('known_namespace_location')
                    ->prototype('scalar')
                    ->end()
                ->end()
                ->arrayNode('configs_jms')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->booleanNode('xml_cdata')
                            ->defaultTrue()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('destinations_php')->fixXmlConfig('destination')
                    ->cannotBeEmpty()->isRequired()
                    ->requiresAtLeastOneElement()
                    ->prototype('scalar')
                    ->end()
                ->end()
                ->arrayNode('destinations_jms')->fixXmlConfig('destination')
                    ->cannotBeEmpty()->isRequired()
                    ->requiresAtLeastOneElement()
                    ->prototype('scalar')
                    ->end()
                ->end()
                ->arrayNode('destinations_validation')->fixXmlConfig('destination')
                    ->prototype('scalar')
                    ->end()
                ->end()
                ->arrayNode('aliases')->fixXmlConfig('alias')
                    ->prototype('scalar')
                    ->end()
                ->end()
                ->booleanNode('strict_types')
                ->end()
            ->end();

        return $treeBuilder;
    }
}
