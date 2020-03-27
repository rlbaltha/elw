<?php

namespace Elw\LTIBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * Class Configuration
 * @package Elw\LTIBundle\DependencyInjection
 */
class Configuration implements ConfigurationInterface
{
    /**
     * @return TreeBuilder
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder('symfony_lti');

        $treeBuilder->getRootNode()
            ->children()
            ->scalarNode('connect_class')->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
