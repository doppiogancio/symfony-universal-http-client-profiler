<?php

namespace Universal\HttpClientProfiler;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Universal\HttpClientProfiler\DependencyInjection\Compiler\GuzzleClientTracerPass;

/**
 * Entry point for the Universal HTTP Client Profiler bundle.
 */
class UniversalHttpClientProfilerBundle extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(new GuzzleClientTracerPass(), PassConfig::TYPE_BEFORE_OPTIMIZATION, 10);
    }
}
