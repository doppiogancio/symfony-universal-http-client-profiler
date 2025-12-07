<?php

namespace HttpProfiler\Model;

use DateTimeImmutable;

/**
 * Represents a single HTTP call captured by the tracer.
 */
class TraceEntry
{
    private DateTimeImmutable $timestamp;

    private string $method;

    private string $url;

    /**
     * @var array<string, array<int, string>>
     */
    private array $requestHeaders;

    private ?string $requestBody;

    private ?int $responseStatus;

    /**
     * @var array<string, array<int, string>>
     */
    private array $responseHeaders;

    private ?string $responseBody;

    private ?float $durationMs;

    private ?string $error;

    /**
     * @var array<int, string>
     */
    private array $stackTrace;

    /**
     * @param array<string, array<int, string>> $requestHeaders
     * @param array<string, array<int, string>> $responseHeaders
     * @param array<int, string> $stackTrace
     */
    public function __construct(
        DateTimeImmutable $timestamp,
        string $method,
        string $url,
        array $requestHeaders,
        ?string $requestBody,
        ?int $responseStatus,
        array $responseHeaders,
        ?string $responseBody,
        ?float $durationMs,
        ?string $error,
        array $stackTrace
    ) {
        $this->timestamp = $timestamp;
        $this->method = $method;
        $this->url = $url;
        $this->requestHeaders = $requestHeaders;
        $this->requestBody = $requestBody;
        $this->responseStatus = $responseStatus;
        $this->responseHeaders = $responseHeaders;
        $this->responseBody = $responseBody;
        $this->durationMs = $durationMs;
        $this->error = $error;
        $this->stackTrace = $stackTrace;
    }

    public function getTimestamp(): DateTimeImmutable
    {
        return $this->timestamp;
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function getRequestHeaders(): array
    {
        return $this->requestHeaders;
    }

    public function getRequestBody(): ?string
    {
        return $this->requestBody;
    }

    public function getResponseStatus(): ?int
    {
        return $this->responseStatus;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function getResponseHeaders(): array
    {
        return $this->responseHeaders;
    }

    public function getResponseBody(): ?string
    {
        return $this->responseBody;
    }

    public function getDurationMs(): ?float
    {
        return $this->durationMs;
    }

    public function getError(): ?string
    {
        return $this->error;
    }

    /**
     * @return array<int, string>
     */
    public function getStackTrace(): array
    {
        return $this->stackTrace;
    }
}
