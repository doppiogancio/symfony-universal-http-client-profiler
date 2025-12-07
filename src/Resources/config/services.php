<?php

use HttpProfiler\Session\ContextDetector;
use HttpProfiler\Session\ConsoleSubscriber;
use HttpProfiler\Session\SessionManager;
use HttpProfiler\Session\SessionReader;
use HttpProfiler\Storage\TraceStorage;
use HttpProfiler\Tracer\HttpClientTracer;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use UniversalHttpClientProfilerBundle\Collector\HttpUniversalCollector;

use function Symfony\Component\DependencyInjection\Loader\Configurator\param;
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
            param('universal_http_client_profiler.max_body_length'),
        ]);

    $services->set(SessionManager::class);

    $services->set(SessionReader::class);

    $services->set(ConsoleSubscriber::class)
        ->tag('kernel.event_subscriber');

    $services->set(ContextDetector::class);

    $services->set(HttpUniversalCollector::class)
        ->tag('data_collector', [
            'id' => 'http_universal_profiler',
            'template' => '@UniversalHttpClientProfiler/Collector/http_profiler.html.twig',
        ]);
};
