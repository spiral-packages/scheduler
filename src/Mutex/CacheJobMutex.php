<?php

declare(strict_types=1);

namespace Spiral\Scheduler\Mutex;

use Psr\SimpleCache\CacheInterface;

final class CacheJobMutex implements JobMutexInterface
{
    public function __construct(
        private readonly CacheInterface $cache,
    ) {}

    /**
     * TODO: use real mutexes
     */
    public function create(string $id, int $minutes): bool
    {
        if ($this->cache->has($id)) {
            return false;
        }

        return $this->cache->set($id, true, $minutes * 60);
    }

    public function exists(string $id): bool
    {
        return $this->cache->has($id);
    }

    public function forget(string $id): void
    {
        $this->cache->delete($id);
    }
}
