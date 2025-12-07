<?php

namespace UniversalHttpClientProfilerBundle\Collector;

use Symfony\Component\HttpKernel\DataCollector\DataCollector;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use HttpProfiler\Storage\TraceStorage;

/**
 * Integrates collected HTTP traces into the Symfony profiler.
 */
class HttpUniversalCollector extends DataCollector
{
    public function __construct(private readonly TraceStorage $storage)
    {
    }

    public function collect(Request $request, Response $response, \Throwable $exception = null): void
    {
        $this->data = $this->storage->all();
    }

    public function getName(): string
    {
        return 'http_universal_profiler';
    }

    public function reset(): void
    {
        $this->storage->clear();
        $this->data = [];
    }
}
