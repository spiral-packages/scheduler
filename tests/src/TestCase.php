<?php

namespace Spiral\Scheduler\Tests;

use Spiral\Boot\Bootloader\ConfigurationBootloader;
use Spiral\Cache\Bootloader\CacheBootloader;
use Spiral\Scheduler\Bootloader\SchedulerBootloader;

abstract class TestCase extends \Spiral\Testing\TestCase
{
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
