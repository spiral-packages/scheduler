<?php

declare(strict_types=1);

namespace Spiral\Scheduler\Tests\Commands;

use Carbon\Carbon;
use Mockery as m;
use Spiral\Scheduler\Commands\ScheduleRunCommand;
use Spiral\Scheduler\Config\SchedulerConfig;
use Spiral\Scheduler\Job\Job;
use Spiral\Scheduler\JobHandlerInterface;
use Spiral\Scheduler\JobRegistryInterface;
use Spiral\Scheduler\Tests\TestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

final class ScheduleRunCommandTest extends TestCase
{
    public function testNoScheduledJobs(): void
    {
        $registry = $this->mockContainer(JobRegistryInterface::class);
        $registry->shouldReceive('getDueJobs')->andReturn([]);

        $this->assertConsoleCommandOutputContainsStrings(
            'schedule:run',
            strings: ['No scheduled jobs are ready to run.'],
        );
    }

    public function testDueJobsShouldBeRun(): void
    {
        $registry = $this->mockContainer(JobRegistryInterface::class);
        $registry->shouldReceive('getDueJobs')->andReturn([
            $job1 = m::mock(Job::class),
            $job2 = m::mock(Job::class),
        ]);

        $job1->shouldReceive('filtersPass')->once()->andReturnFalse();
        $job1->shouldReceive('getId')->andReturn('Job name');

        $job2->shouldReceive('filtersPass')->once()->andReturnTrue();
        $job2->shouldReceive('getDescription')->once()->andReturn('Job description');
        $job2->shouldReceive('getId')->andReturn('Job name');

        $handler = $this->fakeScheduleJobHandler();

        $this->assertConsoleCommandOutputContainsStrings(
            'schedule:run',
            strings: ['Running scheduled: `Job description`'],
        );

        $handler->assertHandledJob($job2);
        $handler->assertNotHandledJob($job1);
    }

    public function testHandleJobByExpression(): void
    {
        $scheduler = $this->runScheduler('@everyFifteenMinutes');
        $scheduler->assertHandled(static function (Job $job) {
            return $job->getName() === 'Another simple job';
        });
        $scheduler->assertHandledTotalJobs(1);

        //

        $scheduler = $this->runScheduler('@everySixHours');
        $scheduler->assertHandled(static function (Job $job) {
            return $job->getName() === 'Another simple job';
        });
        $scheduler->assertHandled(static function (Job $job) {
            return $job->getName() === 'Simple job';
        });
        $scheduler->assertHandledTotalJobs(2);
    }

    public function testUsesCarbonWithConfiguredTimezone(): void
    {
        $registry = $this->mockContainer(JobRegistryInterface::class);
        $registry
            ->shouldReceive('getDueJobs')
            ->once()
            ->withArgs(static function (Carbon $date): bool {
                self::assertSame('America/Toronto', $date->getTimezone()->getName());
                self::assertSame('2026-01-01 07:00:00', $date->format('Y-m-d H:i:s'));

                return true;
            })
            ->andReturn([]);

        Carbon::setTestNow(Carbon::parse('2026-01-01 12:00:00', 'UTC'));

        try {
            $this->mockContainer(JobHandlerInterface::class);
            $this->getContainer()->bindSingleton(
                SchedulerConfig::class,
                new SchedulerConfig(['timezone' => 'America/Toronto']),
            );

            $command = new ScheduleRunCommand();
            $command->setContainer($this->getContainer());

            $result = $command->run(new ArrayInput([]), new BufferedOutput());
        } finally {
            Carbon::setTestNow();
        }

        $this->assertSame(0, $result);
    }
}
