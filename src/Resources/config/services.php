<?php

use Symfony\Component\Console\ConsoleEvents;
use Universal\HttpClientProfiler\Session\ContextDetector;
use Universal\HttpClientProfiler\Session\ConsoleSubscriber;
use Universal\HttpClientProfiler\Session\SessionManager;
use Universal\HttpClientProfiler\Session\SessionReader;
use Universal\HttpClientProfiler\Storage\TraceStorage;
use Universal\HttpClientProfiler\Tracer\HttpClientTracer;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Universal\HttpClientProfiler\Collector\HttpUniversalCollector;

use function Symfony\Component\DependencyInjection\Loader\Configurator\param;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $container): void {
    $services = $container->services()
        ->defaults()
        ->autowire()
        ->autoconfigure();

    $services->set(TraceStorage::class)
        ->public()
        ->share(true);

    $services->set(HttpClientTracer::class)
        ->decorate('http_client')
        ->args([
            service('.inner'),
            service(TraceStorage::class),
            service(SessionManager::class),
            param('universal_http_client_profiler.max_body_length'),
        ]);

    $services->set(SessionManager::class);

    $services->set(SessionReader::class);

    if (class_exists(ConsoleEvents::class)) {
        $services->set(ConsoleSubscriber::class)
            ->tag('kernel.event_subscriber');
    }

    $services->set(ContextDetector::class);

    $services->set(HttpUniversalCollector::class)
        ->tag('data_collector', [
            'id' => 'http_universal_profiler',
            'template' => '@UniversalHttpClientProfiler/Collector/http_profiler.html.twig',
        ]);
};
