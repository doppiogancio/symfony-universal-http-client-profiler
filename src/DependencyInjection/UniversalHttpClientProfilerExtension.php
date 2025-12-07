<?php

namespace Universal\HttpClientProfiler\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;

/**
 * Loads and manages bundle service configuration.
 */
class UniversalHttpClientProfilerExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $container->setParameter('universal_http_client_profiler.enabled', $config['enabled']);
        $container->setParameter('universal_http_client_profiler.max_body_length', $config['max_body_length']);
        $container->setParameter('universal_http_client_profiler.mask_sensitive_data', $config['mask_sensitive_data']);
        $container->setParameter('universal_http_client_profiler.collect_stack_trace', $config['collect_stack_trace']);
        $container->setParameter('universal_http_client_profiler.persist_cli_sessions', $config['persist_cli_sessions']);

        $loader = new PhpFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.php');
    }
}
