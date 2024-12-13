<?php

declare(strict_types=1);

namespace Spiral\Scheduler\Tests\Commands;

use Mockery as m;
use Psr\EventDispatcher\EventDispatcherInterface;
use Spiral\Console\Event\CommandFinished;
use Spiral\Console\Event\CommandStarting;
use Spiral\Interceptors\Event\InterceptorCalling;
use Spiral\Scheduler\Commands\ScheduleFinishCommand;
use Spiral\Scheduler\Event\BackgroundJobFinished;
use Spiral\Scheduler\Job\Job;
use Spiral\Scheduler\JobRegistryInterface;
use Spiral\Scheduler\Tests\TestCase;

final class ScheduleFinishCommandTest extends TestCase
{
    private \Mockery\MockInterface $registry;

    protected function setUp(): void
    {
        parent::setUp();

        $this->registry = $this->mockContainer(JobRegistryInterface::class);
    }

    public function testFoundJobShouldBeFinished()
    {
        $events = $this->mockContainer(EventDispatcherInterface::class);

        $this->registry->shouldReceive('getJobs')->once()->andReturn([
            $job1 = m::mock(Job::class),
            $job2 = m::mock(Job::class),
        ]);

        $events->shouldReceive('dispatch')->withArgs(function (mixed $event) use ($job1) {
            return match (true) {
                $event instanceof CommandStarting => $event->command instanceof ScheduleFinishCommand,
                $event instanceof BackgroundJobFinished => $event->job === $job1,
                $event instanceof CommandFinished => $event->exitCode === 0
                    && $event->command instanceof ScheduleFinishCommand,
                $event instanceof InterceptorCalling,
                $event instanceof \Spiral\Core\Event\InterceptorCalling => true,
                default => false
            };
        });

        $job1->shouldReceive('getId')->once()->andReturn('foo-id');
        $job1->shouldReceive('finish')->with(m::any(), 200);

        $job2->shouldReceive('getId')->once()->andReturn('bar-id');

        $this->runCommand('schedule:finish', ['id' => 'foo-id', 'code' => 200]);
    }
}
