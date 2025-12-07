<?php

namespace HttpProfiler\Session;

/**
 * Detects the execution context to adjust how profiling sessions are handled.
 */
class ContextDetector
{
    public function detect(): string
    {
        if (getenv('MESSENGER_CONSUMER') !== false) {
            return 'worker';
        }

        if (PHP_SAPI === 'cli') {
            return 'cli';
        }

        return 'web';
    }
}
