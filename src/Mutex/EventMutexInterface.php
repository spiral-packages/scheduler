<?php

declare(strict_types=1);

namespace Spiral\Scheduler\Mutex;

interface EventMutexInterface
{
    /**
     * Attempt to obtain an event mutex for the given event.
     */
    public function create(string $id, int $minutes): bool;

    /**
     * Determine if an event mutex exists for the given event.
     */
    public function exists(string $id): bool;

    /**
     * Clear the event mutex for the given event.
     */
    public function forget(string $id): void;
}
