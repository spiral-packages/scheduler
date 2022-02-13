<?php

declare(strict_types=1);

namespace Spiral\Scheduler\Tests\Bootloader;

use Spiral\Scheduler\EveryMinuteCommandRunner;
use Spiral\Scheduler\JobHandler;
use Spiral\Scheduler\JobHandlerInterface;
use Spiral\Scheduler\JobRegistry;
use Spiral\Scheduler\JobRegistryInterface;
use Spiral\Scheduler\JobsLocator;
use Spiral\Scheduler\JobsLocatorInterface;
use Spiral\Scheduler\Mutex\CacheJobMutex;
use Spiral\Scheduler\Mutex\JobMutexInterface;
use Spiral\Scheduler\PeriodicCommandRunnerInterface;
use Spiral\Scheduler\Schedule;
use Spiral\Scheduler\Tests\TestCase;

final class SchedulerBootloaderTest extends TestCase
{
    public function testPeriodicCommandRunner()
    {
        $this->assertContainerBoundAsSingleton(
            PeriodicCommandRunnerInterface::class,
            EveryMinuteCommandRunner::class
        );
    }

    public function testSchedule()
    {
        $this->assertContainerBoundAsSingleton(
            Schedule::class,
            Schedule::class
        );
    }

    public function testJobMutex()
    {
        $this->assertContainerBoundAsSingleton(
            JobMutexInterface::class,
            CacheJobMutex::class
        );
    }

    public function testJobsLocator()
    {
        $this->assertContainerBoundAsSingleton(
            JobsLocatorInterface::class,
            JobsLocator::class
        );

        $this->assertContainerBoundAsSingleton(
            JobsLocator::class,
            JobsLocator::class
        );
    }

    public function testJobRegistry()
    {
        $this->assertContainerBoundAsSingleton(
            JobRegistryInterface::class,
            JobRegistry::class
        );

        $this->assertContainerBoundAsSingleton(
            JobRegistry::class,
            JobRegistry::class
        );
    }

    public function testJobHandler()
    {
        $this->assertContainerBoundAsSingleton(
            JobHandlerInterface::class,
            JobHandler::class
        );
    }
}
