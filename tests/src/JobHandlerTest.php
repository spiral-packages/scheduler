<?php

declare(strict_types=1);

namespace Spiral\Scheduler\Tests;

use Psr\EventDispatcher\EventDispatcherInterface;
use Spiral\Scheduler\Event\JobFailed;
use Spiral\Scheduler\Event\JobFinished;
use Spiral\Scheduler\Event\JobStarting;
use Spiral\Scheduler\Job\Job;
use Spiral\Scheduler\JobHandler;
use Spiral\Snapshots\SnapshotterInterface;

final class JobHandlerTest extends TestCase
{
    public function testHandleJob(): void
    {
        $handler = new JobHandler(
            $this->getContainer(),
        );

        $job = \Mockery::mock(Job::class);
        $job->shouldReceive('run')->with($this->getContainer())->once();

        $handler->handle($job);
    }

    public function testHandleJobWithSnapshotterAndEventBus(): void
    {
        $handler = new JobHandler(
            $this->getContainer(),
            $this->mockContainer(SnapshotterInterface::class),
            $events = $this->mockContainer(EventDispatcherInterface::class),
        );

        $events->shouldReceive('dispatch')->once()->withArgs(static function ($job) {
            return $job instanceof JobStarting;
        });

        $events->shouldReceive('dispatch')->once()->withArgs(static function ($job) {
            return $job instanceof JobFinished;
        });

        $job = \Mockery::mock(Job::class);
        $job->shouldReceive('run')->with($this->getContainer())->once();

        $handler->handle($job);
    }

    public function testHandleFailedJobShouldThrowAnException(): void
    {
        $handler = new JobHandler(
            $this->getContainer(),
            $snapshotter = $this->mockContainer(SnapshotterInterface::class),
            $events = $this->mockContainer(EventDispatcherInterface::class),
        );

        $events->shouldReceive('dispatch')->once()->withArgs(static function ($job) {
            return $job instanceof JobStarting;
        });

        $events->shouldReceive('dispatch')->once()->withArgs(static function ($job) {
            return $job instanceof JobFailed;
        });

        $job = \Mockery::mock(Job::class);
        $job->shouldReceive('run')
            ->with($this->getContainer())
            ->once()
            ->andThrow($e = new \Exception('Something went wrong'));

        $snapshotter->shouldReceive('register')->once()->with($e);

        $handler->handle($job);
    }
}
