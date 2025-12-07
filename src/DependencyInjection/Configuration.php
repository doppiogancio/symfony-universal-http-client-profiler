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
        // TODO: define bundle configuration options.
        return new TreeBuilder('universal_http_client_profiler');
    }
}
