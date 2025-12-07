<?php

namespace UniversalHttpClientProfilerBundle\Collector;

use Symfony\Component\HttpKernel\DataCollector\DataCollector;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Integrates collected HTTP traces into the Symfony profiler.
 */
class HttpUniversalCollector extends DataCollector
{
    public function collect(Request $request, Response $response, \Throwable $exception = null): void
    {
        // TODO: gather data from the tracer and store it for the profiler.
    }

    public function getName(): string
    {
        // TODO: return a unique data collector name.
        return 'universal_http_client_profiler';
    }
}
