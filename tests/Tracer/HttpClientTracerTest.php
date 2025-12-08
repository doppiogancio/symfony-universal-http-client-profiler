<?php

namespace Universal\HttpClientProfiler\Tests\Tracer;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Universal\HttpClientProfiler\Session\SessionManager;
use Universal\HttpClientProfiler\Storage\TraceStorage;
use Universal\HttpClientProfiler\Tracer\HttpClientTracer;

class HttpClientTracerTest extends TestCase
{
    private HttpClientInterface&MockObject $innerClient;
    private TraceStorage&MockObject $storage;
    private SessionManager&MockObject $sessionManager;
    private HttpClientTracer $tracer;

    protected function setUp(): void
    {
        $this->innerClient = $this->createMock(HttpClientInterface::class);
        $this->storage = $this->createMock(TraceStorage::class);
        $this->sessionManager = $this->createMock(SessionManager::class);
        $this->tracer = new HttpClientTracer($this->innerClient, $this->storage, $this->sessionManager, 1024);
    }

    public function testRequestRecordsTrace(): void
    {
        $method = 'GET';
        $url = 'https://example.com';
        $options = [];

        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn(200);
        $response->method('getHeaders')->willReturn(['content-type' => ['application/json']]);
        $response->method('getContent')->willReturn('{"status":"ok"}');

        $this->innerClient->expects($this->once())
            ->method('request')
            ->with($method, $url, $options)
            ->willReturn($response);

        $this->storage->expects($this->once())
            ->method('add');

        $this->sessionManager->expects($this->once())
            ->method('addTrace');

        $result = $this->tracer->request($method, $url, $options);

        $this->assertSame($response, $result);
    }
}
