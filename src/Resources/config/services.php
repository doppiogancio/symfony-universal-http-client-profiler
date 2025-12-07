<?php

use HttpProfiler\Session\ContextDetector;
use HttpProfiler\Session\ConsoleSubscriber;
use HttpProfiler\Session\SessionManager;
use HttpProfiler\Storage\TraceStorage;
use HttpProfiler\Tracer\HttpClientTracer;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use UniversalHttpClientProfilerBundle\Collector\HttpUniversalCollector;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $container): void {
    $services = $container->services()
        ->defaults()
        ->autowire()
        ->autoconfigure();

    $services->set(TraceStorage::class)
        ->share(true);

    $services->set(HttpClientTracer::class)
        ->decorate('http_client')
        ->args([
            service('.inner'),
            service(TraceStorage::class),
        ]);

    $services->set(SessionManager::class);

    $services->set(ConsoleSubscriber::class)
        ->tag('kernel.event_subscriber');

    $services->set(ContextDetector::class);

    $services->set(HttpUniversalCollector::class)
        ->tag('data_collector', [
            'id' => 'http_universal_profiler',
            'template' => '@UniversalHttpClientProfiler/collector.html.twig',
        ]);
};
