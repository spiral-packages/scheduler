<?php

declare(strict_types=1);

namespace Spiral\Scheduler\Mutex;

use Psr\SimpleCache\CacheInterface;

final class CacheEventMutex implements EventMutexInterface
{
    public function __construct(private CacheInterface $cache)
    {
    }

    public function create(string $id, int $minutes): bool
    {
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
