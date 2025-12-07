<?php

namespace HttpProfiler\Session;

use JsonException;
use RuntimeException;

class SessionReader
{
    private ?string $storageDirectory;

    public function __construct(?string $storageDirectory = null)
    {
        $this->storageDirectory = $storageDirectory;
    }

    public function listSessions(): array
    {
        $directory = $this->resolveStorageDirectory();

        if (!is_dir($directory)) {
            return [];
        }

        $files = glob($directory . '/session-*.json');

        if ($files === false) {
            return [];
        }

        $sessions = [];

        foreach ($files as $file) {
            $sessionId = $this->extractSessionId($file);

            if ($sessionId === null) {
                continue;
            }

            try {
                $sessions[] = $this->loadSession($sessionId);
            } catch (RuntimeException) {
                continue;
            }
        }

        usort(
            $sessions,
            static function (array $first, array $second): int {
                return strcmp($second['started_at'] ?? '', $first['started_at'] ?? '');
            },
        );

        return $sessions;
    }

    public function loadSession(string $sessionId): array
    {
        $directory = $this->resolveStorageDirectory();
        $path = sprintf('%s/session-%s.json', $directory, $sessionId);

        if (!is_file($path)) {
            throw new RuntimeException(sprintf('Session file "%s" does not exist.', $path));
        }

        $payload = file_get_contents($path);

        if ($payload === false) {
            throw new RuntimeException(sprintf('Unable to read session file "%s".', $path));
        }

        try {
            $data = json_decode($payload, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new RuntimeException(sprintf('Invalid JSON in session file "%s".', $path), 0, $exception);
        }

        if (!is_array($data)) {
            throw new RuntimeException(sprintf('Session file "%s" did not contain an array.', $path));
        }

        return $data;
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

    private function extractSessionId(string $path): ?string
    {
        $fileName = basename($path);

        if (preg_match('/^session-(.+)\.json$/', $fileName, $matches) !== 1) {
            return null;
        }

        return $matches[1];
    }
}
