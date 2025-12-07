<?php

namespace HttpProfiler\Tracer;

use DateTimeImmutable;
use HttpProfiler\Model\TraceEntry;
use HttpProfiler\Storage\TraceStorage;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * Decorates the Symfony HTTP client to capture detailed trace information.
 */
class HttpClientTracer implements HttpClientInterface
{
    public function __construct(
        private readonly HttpClientInterface $inner,
        private readonly TraceStorage $storage,
        private readonly int $maxBodyLength
    ) {
    }

    public function request(string $method, string $url, array $options = []): ResponseInterface
    {
        $timestamp = new DateTimeImmutable();
        $start = microtime(true);

        $requestHeaders = $this->maskSensitiveHeaders($this->normalizeHeaders($options['headers'] ?? []));
        $requestBody = $this->truncateBody($this->normalizeBody($options));
        $responseStatus = null;
        $responseHeaders = [];
        $responseBody = null;
        $error = null;
        $stackTrace = $this->captureStackTrace();

        try {
            $response = $this->inner->request($method, $url, $options);

            $responseStatus = $response->getStatusCode();
            $responseHeaders = $this->maskSensitiveHeaders($response->getHeaders(false));
            $responseBody = $this->truncateBody($response->getContent(false));
        } catch (\Throwable $exception) {
            $error = $exception->getMessage();
            $stackTrace = $this->captureExceptionTrace($exception);
            throw $exception;
        } finally {
            $durationMs = (microtime(true) - $start) * 1000;

            $this->storage->add(new TraceEntry(
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
            ));
        }

        return $response;
    }

    public function stream(ResponseInterface|iterable $responses, float $timeout = null): iterable
    {
        return $this->inner->stream($responses, $timeout);
    }

    /**
     * @param array<string, mixed>|iterable<string> $headers
     * @return array<string, array<int, string>>
     */
    private function normalizeHeaders(array|iterable $headers): array
    {
        $normalized = [];

        foreach ($headers as $name => $value) {
            $normalized[(string) $name] = is_array($value) ? array_map('strval', $value) : [(string) $value];
        }

        return $normalized;
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

    private function normalizeBody(array $options): ?string
    {
        if (array_key_exists('body', $options)) {
            return $this->stringify($options['body']);
        }

        if (array_key_exists('json', $options)) {
            return json_encode($options['json']);
        }

        if (array_key_exists('query', $options)) {
            return http_build_query($options['query']);
        }

        return null;
    }

    private function stringify(mixed $value): ?string
    {
        if (is_string($value)) {
            return $value;
        }

        if (is_scalar($value)) {
            return (string) $value;
        }

        if (is_array($value)) {
            return json_encode($value);
        }

        return null;
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

    /**
     * @return array<int, string>
     */
    private function captureStackTrace(): array
    {
        return array_map(
            static fn (array $trace): string => sprintf(
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
}
