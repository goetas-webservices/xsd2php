<?php
namespace GoetasWebservices\Xsd\XsdToPhp\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('xsd2php');

        $rootNode
            ->children()
                ->scalarNode('naming_strategy')
                    ->defaultValue('short')
                    ->cannotBeEmpty()
                ->end()
                ->scalarNode('cdata')
                    ->defaultValue('true')
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
                ->arrayNode('aliases')->fixXmlConfig('alias')
                    ->prototype('array')
                        ->prototype('scalar')
                        ->end()
                    ->end()
                ->end()
            ->end();
        return $treeBuilder;
    }
}
