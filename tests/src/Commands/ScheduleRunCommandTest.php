<?php

declare(strict_types=1);

namespace Spiral\Scheduler\Tests\Commands;

use Mockery as m;
use Spiral\Scheduler\Job\Job;
use Spiral\Scheduler\JobRegistryInterface;
use Spiral\Scheduler\Tests\TestCase;

final class ScheduleRunCommandTest extends TestCase
{
    public function testNoScheduledJobs(): void
    {
        $registry = $this->mockContainer(JobRegistryInterface::class);
        $registry->shouldReceive('getDueJobs')->andReturn([]);

        $this->assertConsoleCommandOutputContainsStrings(
            'schedule:run', strings: ['No scheduled jobs are ready to run.']
        );
    }

    public function testDueJobsShouldBeRun(): void
    {
        $registry = $this->mockContainer(JobRegistryInterface::class);
        $registry->shouldReceive('getDueJobs')->andReturn([
            $job1 = m::mock(Job::class),
            $job2 = m::mock(Job::class),
        ]);

        $job1->shouldReceive('filtersPass')->once()->with($this->getContainer())->andReturnFalse();
        $job1->shouldReceive('getId')->andReturn('Job name');

        $job2->shouldReceive('filtersPass')->once()->with($this->getContainer())->andReturnTrue();
        $job2->shouldReceive('getDescription')->once()->andReturn('Job description');
        $job2->shouldReceive('getId')->andReturn('Job name');

        $handler = $this->fakeScheduleJobHandler();

        $this->assertConsoleCommandOutputContainsStrings(
            'schedule:run', strings: ['Running scheduled job: Job description']
        );

        $handler->assertHandledJob($job2);
        $handler->assertNotHandledJob($job1);
    }

    public function testHandleJobByExpression()
    {
        $scheduler = $this->runScheduler('@everyFifteenMinutes');
        $scheduler->assertHandled(function (Job $job) {
            return $job->getName() === 'Another simple job';
        });
        $scheduler->assertHandledTotalJobs(1);

        //

        $scheduler = $this->runScheduler('@everySixHours');
        $scheduler->assertHandled(function (Job $job) {
            return $job->getName() === 'Another simple job';
        });
        $scheduler->assertHandled(function (Job $job) {
            return $job->getName() === 'Simple job';
        });
        $scheduler->assertHandledTotalJobs(2);
    }
}
