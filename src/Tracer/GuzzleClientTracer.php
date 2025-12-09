<?php

namespace Universal\HttpClientProfiler\Tracer;

use DateTimeImmutable;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Promise\Create;
use GuzzleHttp\Promise\PromiseInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Universal\HttpClientProfiler\Model\TraceEntry;
use Universal\HttpClientProfiler\Storage\TraceStorage;
use Universal\HttpClientProfiler\Session\SessionManager;

/**
 * Decorates a Guzzle client to capture request/response details.
 */
class GuzzleClientTracer implements ClientInterface
{
    public function __construct(
        private readonly ClientInterface $inner,
        private readonly TraceStorage $storage,
        private readonly SessionManager $sessionManager,
        private readonly int $maxBodyLength
    ) {
    }

    public function send(RequestInterface $request, array $options = []): ResponseInterface
    {
        $timestamp = new DateTimeImmutable();
        $start = microtime(true);

        $requestHeaders = $this->maskSensitiveHeaders($request->getHeaders());
        $requestBody = $this->truncateBody($this->stringify((string) $request->getBody()));
        $stackTrace = $this->captureStackTrace();

        try {
            $response = $this->inner->send($request, $options);

            $this->storeTrace(
                timestamp: $timestamp,
                method: $request->getMethod(),
                url: (string) $request->getUri(),
                requestHeaders: $requestHeaders,
                requestBody: $requestBody,
                responseStatus: $response->getStatusCode(),
                responseHeaders: $this->maskSensitiveHeaders($response->getHeaders()),
                responseBody: $this->truncateBody((string) $response->getBody()),
                durationMs: (microtime(true) - $start) * 1000,
                error: null,
                stackTrace: $stackTrace,
            );

            return $response;
        } catch (\Throwable $exception) {
            $this->storeTrace(
                timestamp: $timestamp,
                method: $request->getMethod(),
                url: (string) $request->getUri(),
                requestHeaders: $requestHeaders,
                requestBody: $requestBody,
                responseStatus: null,
                responseHeaders: [],
                responseBody: null,
                durationMs: (microtime(true) - $start) * 1000,
                error: $exception->getMessage(),
                stackTrace: $this->captureExceptionTrace($exception),
            );

            throw $exception;
        }
    }

    public function sendAsync(RequestInterface $request, array $options = []): PromiseInterface
    {
        $timestamp = new DateTimeImmutable();
        $start = microtime(true);

        $requestHeaders = $this->maskSensitiveHeaders($request->getHeaders());
        $requestBody = $this->truncateBody($this->stringify((string) $request->getBody()));
        $stackTrace = $this->captureStackTrace();

        return $this->inner->sendAsync($request, $options)->then(
            function (ResponseInterface $response) use ($timestamp, $request, $requestHeaders, $requestBody, $start, $stackTrace) {
                $this->storeTrace(
                    timestamp: $timestamp,
                    method: $request->getMethod(),
                    url: (string) $request->getUri(),
                    requestHeaders: $requestHeaders,
                    requestBody: $requestBody,
                    responseStatus: $response->getStatusCode(),
                    responseHeaders: $this->maskSensitiveHeaders($response->getHeaders()),
                    responseBody: $this->truncateBody((string) $response->getBody()),
                    durationMs: (microtime(true) - $start) * 1000,
                    error: null,
                    stackTrace: $stackTrace,
                );

                return Create::promiseFor($response);
            },
            function ($reason) use ($timestamp, $request, $requestHeaders, $requestBody, $start, $stackTrace) {
                $error = $reason instanceof \Throwable ? $reason->getMessage() : (string) $reason;
                $stackTrace = $reason instanceof \Throwable ? $this->captureExceptionTrace($reason) : $stackTrace;

                $this->storeTrace(
                    timestamp: $timestamp,
                    method: $request->getMethod(),
                    url: (string) $request->getUri(),
                    requestHeaders: $requestHeaders,
                    requestBody: $requestBody,
                    responseStatus: null,
                    responseHeaders: [],
                    responseBody: null,
                    durationMs: (microtime(true) - $start) * 1000,
                    error: $error,
                    stackTrace: $stackTrace,
                );

                return Create::rejectionFor($reason);
            }
        );
    }

    public function request(string $method, $uri, array $options = []): ResponseInterface
    {
        return $this->send(new \GuzzleHttp\Psr7\Request($method, $uri, $options['headers'] ?? [], $options['body'] ?? null), $options);
    }

    public function requestAsync(string $method, $uri, array $options = []): PromiseInterface
    {
        return $this->sendAsync(new \GuzzleHttp\Psr7\Request($method, $uri, $options['headers'] ?? [], $options['body'] ?? null), $options);
    }

    public function getConfig(?string $option = null): mixed
    {
        return $this->inner->getConfig($option);
    }

    /**
     * @param array<string, array<int, string>> $headers
     * @return array<string, array<int, string>>
     */
    private function maskSensitiveHeaders(array $headers): array
    {
        $sensitiveHeaders = [
            'authorization',
            'cookie',
            'proxy-authorization',
            'proxy-authenticate',
            'set-cookie',
            'token',
            'www-authenticate',
        ];

        $masked = [];

        foreach ($headers as $name => $values) {
            $lowerName = strtolower($name);
            $masked[$name] = in_array($lowerName, $sensitiveHeaders, true)
                ? array_fill(0, count($values), '***')
                : $values;
        }

        return $masked;
    }

    private function truncateBody(?string $body): ?string
    {
        if ($body === null) {
            return null;
        }

        if ($this->maxBodyLength <= 0 || strlen($body) <= $this->maxBodyLength) {
            return $body;
        }

        return substr($body, 0, $this->maxBodyLength);
    }

    private function stringify(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if (is_string($value)) {
            return $value;
        }

        if (is_scalar($value)) {
            return (string) $value;
        }

        if (is_array($value)) {
            return json_encode($value);
        }

        if (is_object($value) && method_exists($value, '__toString')) {
            return (string) $value;
        }

        return null;
    }

    /**
     * @return array<int, string>
     */
    private function captureStackTrace(): array
    {
        return array_map(
            static fn(array $trace): string => sprintf(
                '%s:%s%s',
                $trace['file'] ?? '[internal]',
                $trace['line'] ?? '0',
                isset($trace['function']) ? sprintf(' %s', $trace['function']) : ''
            ),
            debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS)
        );
    }

    /**
     * @return array<int, string>
     */
    private function captureExceptionTrace(\Throwable $throwable): array
    {
        return explode("\n", $throwable->getTraceAsString());
    }

    /**
     * @param array<string, array<int, string>> $requestHeaders
     * @param array<string, array<int, string>> $responseHeaders
     * @param array<int, string> $stackTrace
     */
    private function storeTrace(
        DateTimeImmutable $timestamp,
        string $method,
        string $url,
        array $requestHeaders,
        ?string $requestBody,
        ?int $responseStatus,
        array $responseHeaders,
        ?string $responseBody,
        float $durationMs,
        ?string $error,
        array $stackTrace,
    ): void {
        $entry = new TraceEntry(
            $timestamp,
            $method,
            $url,
            $requestHeaders,
            $requestBody,
            $responseStatus,
            $responseHeaders,
            $responseBody,
            $durationMs,
            $error,
            $stackTrace
        );

        $this->storage->add($entry);
        $this->sessionManager->addTrace($entry);
    }
}
