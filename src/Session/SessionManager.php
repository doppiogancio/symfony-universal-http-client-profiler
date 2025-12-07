<?php

namespace HttpProfiler\Session;

use DateTimeImmutable;
use JsonException;
use HttpProfiler\Model\TraceEntry;
use RuntimeException;

/**
 * Manages profiling sessions across different execution contexts.
 */
class SessionManager
{
    private ?string $sessionId = null;

    private ?string $storageDirectory = null;

    private ?string $context = null;

    private ?string $command = null;

    private ?DateTimeImmutable $startedAt = null;

    /**
     * @var array<int, TraceEntry>
     */
    private array $entries = [];

    public function __construct(?string $storageDirectory = null)
    {
        $this->storageDirectory = $storageDirectory;
    }

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

        $directory = $this->resolveStorageDirectory();

        if (!is_dir($directory) && !mkdir($directory, 0777, true) && !is_dir($directory)) {
            throw new RuntimeException(sprintf('Unable to create session directory at "%s".', $directory));
        }

        $path = sprintf('%s/session-%s.json', $directory, $this->sessionId);

        try {
            $payload = json_encode(
                $sessionData,
                JSON_PRETTY_PRINT | JSON_INVALID_UTF8_SUBSTITUTE | JSON_THROW_ON_ERROR,
            );
        } catch (JsonException $exception) {
            $this->resetSession();

            throw new RuntimeException('Unable to encode profiling session as JSON.', 0, $exception);
        }

        if (file_put_contents($path, $payload) === false) {
            $this->resetSession();

            throw new RuntimeException(sprintf('Unable to write profiling session to "%s".', $path));
        }

        $this->resetSession();
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

    private function resolveStorageDirectory(): string
    {
        $baseDirectory = $this->storageDirectory;

        if ($baseDirectory === null) {
            $workingDirectory = getcwd();
            $baseDirectory = ($workingDirectory !== false ? $workingDirectory : dirname(__DIR__, 2)) . '/var/http-profiler';
        }

        return rtrim($baseDirectory, '/');
    }

    private function resetSession(): void
    {
        $this->sessionId = null;
        $this->context = null;
        $this->command = null;
        $this->startedAt = null;
        $this->entries = [];
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
