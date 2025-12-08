<?php

namespace Universal\HttpClientProfiler\Tests\Kernel;

use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Universal\HttpClientProfiler\Storage\TraceStorage;
use Universal\HttpClientProfiler\Tracer\HttpClientTracer;

class TestKernelBootTest extends TestCase
{
    public function testKernelBootsAndRegistersServices(): void
    {
        $kernel = new TestKernel('test', true);
        $kernel->boot();

        $container = $kernel->getContainer();

        $this->assertInstanceOf(ContainerInterface::class, $container);
        $this->assertTrue($container->has(TraceStorage::class));
        $this->assertInstanceOf(TraceStorage::class, $container->get(TraceStorage::class));

        $bundles = $kernel->getBundles();
        $this->assertArrayHasKey('UniversalHttpClientProfilerBundle', $bundles);

        $httpClient = $container->get('public_http_client');
        $this->assertInstanceOf(HttpClientTracer::class, $httpClient);

        $kernel->shutdown();
    }
}
