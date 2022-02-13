<?php

declare(strict_types=1);

namespace Spiral\Scheduler\Tests\Mutex;

use Psr\SimpleCache\CacheInterface;
use Spiral\Scheduler\Mutex\CacheJobMutex;
use Spiral\Scheduler\Tests\TestCase;

final class CacheJobMutexTest extends TestCase
{
    private \Mockery\MockInterface $cache;
    private CacheJobMutex $mutex;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cache = $this->mockContainer(CacheInterface::class);
        $this->mutex = new CacheJobMutex($this->cache);
    }

    public function testCreates()
    {
        $this->cache->shouldReceive('set')->with('job-id', true, 100 * 60)->andReturnTrue();
        $this->assertTrue($this->mutex->create('job-id', 100));
    }

    public function testExists()
    {
        $this->cache->shouldReceive('has')->with('job-id')->andReturnTrue();
        $this->assertTrue($this->mutex->exists('job-id'));
    }

    public function testForget()
    {
        $this->cache->shouldReceive('delete')->with('job-id');
        $this->mutex->forget('job-id');
    }
}
