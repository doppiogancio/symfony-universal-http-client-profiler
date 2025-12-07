<?php

namespace UniversalHttpClientProfilerBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * Defines the configuration structure for the bundle.
 */
class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('universal_http_client_profiler');
        $rootNode = $treeBuilder->getRootNode();

        $rootNode
            ->children()
                ->booleanNode('enabled')
                    ->defaultTrue()
                ->end()
                ->integerNode('max_body_length')
                    ->defaultValue(10240)
                    ->min(0)
                ->end()
                ->booleanNode('mask_sensitive_data')
                    ->defaultTrue()
                ->end()
                ->booleanNode('collect_stack_trace')
                    ->defaultFalse()
                ->end()
                ->booleanNode('persist_cli_sessions')
                    ->defaultFalse()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
