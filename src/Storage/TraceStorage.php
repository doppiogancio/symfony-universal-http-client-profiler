<?php

namespace Universal\HttpClientProfiler\Storage;

use Universal\HttpClientProfiler\Model\TraceEntry;

/**
 * Handles in-memory storage of HTTP trace entries.
 */
class TraceStorage
{
    /**
     * @var array<int, TraceEntry>
     */
    private array $entries = [];

    public function add(TraceEntry $entry): void
    {
        $this->entries[] = $entry;
    }

    /**
     * @return array<int, TraceEntry>
     */
    public function all(): array
    {
        return $this->entries;
    }

    public function clear(): void
    {
        $this->entries = [];
    }
}
