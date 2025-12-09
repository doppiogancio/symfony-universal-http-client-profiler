<?php

namespace Universal\HttpClientProfiler\Tests\Tracer;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Universal\HttpClientProfiler\Session\SessionManager;
use Universal\HttpClientProfiler\Storage\TraceStorage;
use Universal\HttpClientProfiler\Tracer\GuzzleClientTracer;

class GuzzleClientTracerTest extends TestCase
{
    private ClientInterface&MockObject $innerClient;
    private TraceStorage&MockObject $storage;
    private SessionManager&MockObject $sessionManager;
    private GuzzleClientTracer $tracer;

    protected function setUp(): void
    {
        $this->innerClient = $this->createMock(ClientInterface::class);
        $this->storage = $this->createMock(TraceStorage::class);
        $this->sessionManager = $this->createMock(SessionManager::class);

        $this->tracer = new GuzzleClientTracer(
            $this->innerClient,
            $this->storage,
            $this->sessionManager,
            1024
        );
    }

    public function testSendRecordsTrace(): void
    {
        $request = new Request('GET', 'https://example.com');
        $response = new Response(200, ['content-type' => ['application/json']], '{"ok":true}');

        $this->innerClient->expects($this->once())
            ->method('send')
            ->with($request, [])
            ->willReturn($response);

        $this->storage->expects($this->once())
            ->method('add');
        $this->sessionManager->expects($this->once())
            ->method('addTrace');

        $result = $this->tracer->send($request);

        $this->assertSame($response, $result);
    }

    public function testSendAsyncRecordsTrace(): void
    {
        $request = new Request('POST', 'https://example.com', ['Content-Type' => 'application/json'], '{"ok":true}');
        $response = new Response(201, ['content-type' => ['application/json']], '{"created":true}');

        $promise = $this->createMock(PromiseInterface::class);
        $promise->method('then')->willReturnCallback(function (callable $onFulfilled, callable $onRejected) use ($response) {
            return $onFulfilled($response);
        });

        $this->innerClient->expects($this->once())
            ->method('sendAsync')
            ->with($request, [])
            ->willReturn($promise);

        $this->storage->expects($this->once())
            ->method('add');
        $this->sessionManager->expects($this->once())
            ->method('addTrace');

        $result = $this->tracer->sendAsync($request);

        $this->assertInstanceOf(PromiseInterface::class, $result);
    }
}
