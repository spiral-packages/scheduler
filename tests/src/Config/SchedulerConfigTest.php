<?php

declare(strict_types=1);

namespace Spiral\Scheduler\Tests\Config;

use Spiral\Scheduler\Config\SchedulerConfig;
use Spiral\Scheduler\Tests\TestCase;

final class SchedulerConfigTest extends TestCase
{
    public function testGetsTimezone(): void
    {
        $config = new SchedulerConfig([
            'timezone' => 'UTC'
        ]);


        $this->assertSame('UTC', $config->getTimezone()->getName());
    }

    public function testGetsTimezoneCanReturnNull(): void
    {
        $config = new SchedulerConfig();
        $this->assertNull($config->getTimezone());
    }

    public function testGetsCacheStorage(): void
    {
        $config = new SchedulerConfig([
            'cacheStorage' => 'foo'
        ]);
        $this->assertSame('foo', $config->getCacheStorage());
    }

    public function testGetsCacheStorageCanReturnNull(): void
    {
        $config = new SchedulerConfig();
        $this->assertNull($config->getCacheStorage());
    }

    public function testGetsQueueConnection(): void
    {
        $config = new SchedulerConfig([
            'queueConnection' => 'bar'
        ]);
        $this->assertSame('bar', $config->getQueueConnection());
    }

    public function testGetsQueueConnectionCanReturnNull(): void
    {
        $config = new SchedulerConfig();
        $this->assertNull($config->getQueueConnection());
    }
}
