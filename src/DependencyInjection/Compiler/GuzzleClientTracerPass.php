<?php

namespace Universal\HttpClientProfiler\DependencyInjection\Compiler;

use GuzzleHttp\ClientInterface;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Universal\HttpClientProfiler\Session\SessionManager;
use Universal\HttpClientProfiler\Storage\TraceStorage;
use Universal\HttpClientProfiler\Tracer\GuzzleClientTracer;

class GuzzleClientTracerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!interface_exists(ClientInterface::class)) {
            return;
        }

        foreach ($this->findGuzzleServiceIds($container) as $serviceId) {
            $decoratorId = sprintf('universal_http_client_profiler.guzzle_tracer.%s', strtr($serviceId, ['.' => '_', '\\' => '_']));

            if ($container->hasDefinition($decoratorId)) {
                continue;
            }

            $definition = new Definition(GuzzleClientTracer::class);
            $definition->setDecoratedService($serviceId);
            $definition->setAutowired(true);
            $definition->setAutoconfigured(true);
            $definition->setArguments([
                new Reference(sprintf('%s.inner', $decoratorId)),
                new Reference(TraceStorage::class),
                new Reference(SessionManager::class),
                '%universal_http_client_profiler.max_body_length%',
            ]);

            $container->setDefinition($decoratorId, $definition);
        }
    }

    /**
     * @return array<int, string>
     */
    private function findGuzzleServiceIds(ContainerBuilder $container): array
    {
        $serviceIds = [];

        foreach ($container->getDefinitions() as $id => $definition) {
            if ($definition->isAbstract()) {
                continue;
            }

            if ($definition->getClass() === GuzzleClientTracer::class) {
                continue;
            }

            $class = $definition->getClass() ?? $id;

            if (is_string($class) && is_a($class, ClientInterface::class, true)) {
                $serviceIds[] = $id;
            }
        }

        if ($container->hasAlias(ClientInterface::class)) {
            $serviceIds[] = ClientInterface::class;
        }

        return array_values(array_unique($serviceIds));
    }
}
