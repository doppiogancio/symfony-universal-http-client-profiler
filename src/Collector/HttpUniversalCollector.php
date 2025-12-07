<?php

namespace UniversalHttpClientProfilerBundle\Collector;

use HttpProfiler\Session\SessionReader;
use HttpProfiler\Storage\TraceStorage;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\DataCollector\DataCollector;

/**
 * Integrates collected HTTP traces into the Symfony profiler.
 */
class HttpUniversalCollector extends DataCollector
{
    public function __construct(
        private readonly TraceStorage $storage,
        private readonly SessionReader $sessionReader,
    )
    {
    }

    public function collect(Request $request, Response $response, \Throwable $exception = null): void
    {
        $this->data = [
            'requests' => $this->storage->all(),
            'cliSessions' => $this->sessionReader->listSessions(),
        ];
    }

    public function getName(): string
    {
        return 'http_universal_profiler';
    }

    public function reset(): void
    {
        $this->storage->clear();
        $this->data = [
            'requests' => [],
            'cliSessions' => [],
        ];
    }

    /**
     * @return array<int, \HttpProfiler\Model\TraceEntry>
     */
    public function getRequests(): array
    {
        return $this->data['requests'] ?? [];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getCliSessions(): array
    {
        return $this->data['cliSessions'] ?? [];
    }
}
