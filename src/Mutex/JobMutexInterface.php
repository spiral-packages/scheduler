<?php

declare(strict_types=1);

namespace Spiral\Scheduler\Mutex;

interface JobMutexInterface
{
    /**
     * Attempt to obtain a job mutex for the given job.
     */
    public function create(string $id, int $minutes): bool;

    /**
     * Determine if a job mutex exists for the given job.
     */
    public function exists(string $id): bool;

    /**
     * Clear the job mutex for the given job.
     */
    public function forget(string $id): void;
}
