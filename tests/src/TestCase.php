<?php

namespace Spiral\Scheduler\Tests;

use Spiral\Boot\Bootloader\ConfigurationBootloader;
use Spiral\Cache\Bootloader\CacheBootloader;
use Spiral\Scheduler\Bootloader\SchedulerBootloader;
use Spiral\Scheduler\Testing\InteractsWithSchedule;

abstract class TestCase extends \Spiral\Testing\TestCase
{
    use InteractsWithSchedule;

    public function rootDirectory(): string
    {
        return __DIR__.'/../';
    }

    public function defineBootloaders(): array
    {
        return [
            ConfigurationBootloader::class,
            CacheBootloader::class,
            SchedulerBootloader::class,
        ];
    }
}
