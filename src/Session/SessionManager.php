<?php

namespace HttpProfiler\Session;

use DateTimeImmutable;
use HttpProfiler\Model\TraceEntry;
use RuntimeException;

/**
 * Manages profiling sessions across different execution contexts.
 */
class SessionManager
{
    private ?string $sessionId = null;

    private ?string $context = null;

    private ?string $command = null;

    private ?DateTimeImmutable $startedAt = null;

    /**
     * @var array<int, TraceEntry>
     */
    private array $entries = [];

    public function startSession(string $context, ?string $commandName = null): void
    {
        if ($this->isActive()) {
            throw new RuntimeException('A profiling session is already active.');
        }

        $this->sessionId = bin2hex(random_bytes(16));
        $this->context = $context;
        $this->command = $commandName;
        $this->startedAt = new DateTimeImmutable();
        $this->entries = [];
    }

    public function endSession(): void
    {
        if (!$this->isActive() || $this->startedAt === null) {
            return;
        }

        $sessionData = [
            'session_id' => $this->sessionId,
            'context' => $this->context,
            'command' => $this->command,
            'started_at' => $this->startedAt->format(DateTimeImmutable::ATOM),
            'entries' => array_map($this->serializeEntry(...), $this->entries),
        ];

        $directory = dirname(__DIR__, 1) . '/../var/http-profiler';
        if (!is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        $path = sprintf('%s/session-%s.json', $directory, $this->sessionId);

        file_put_contents($path, json_encode($sessionData, JSON_PRETTY_PRINT));

        $this->sessionId = null;
        $this->context = null;
        $this->command = null;
        $this->startedAt = null;
        $this->entries = [];
    }

    public function isActive(): bool
    {
        return $this->sessionId !== null;
    }

    public function getSessionId(): string
    {
        if ($this->sessionId === null) {
            throw new RuntimeException('No active profiling session.');
        }

        return $this->sessionId;
    }

    public function addTrace(TraceEntry $entry): void
    {
        if (!$this->isActive()) {
            return;
        }

        $this->entries[] = $entry;
    }

    private function serializeEntry(TraceEntry $entry): array
    {
        return [
            'timestamp' => $entry->getTimestamp()->format(DateTimeImmutable::ATOM),
            'method' => $entry->getMethod(),
            'url' => $entry->getUrl(),
            'request_headers' => $entry->getRequestHeaders(),
            'request_body' => $entry->getRequestBody(),
            'response_status' => $entry->getResponseStatus(),
            'response_headers' => $entry->getResponseHeaders(),
            'response_body' => $entry->getResponseBody(),
            'duration_ms' => $entry->getDurationMs(),
            'error' => $entry->getError(),
            'stack_trace' => $entry->getStackTrace(),
        ];
    }
}
