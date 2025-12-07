<?php

namespace UniversalHttpClientProfilerBundle\DependencyInjection;

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
        // TODO: process configuration and register services.
        $loader = new PhpFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.php');
    }
}
