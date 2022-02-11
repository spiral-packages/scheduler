<?php

declare(strict_types=1);

namespace Spiral\Scheduler\Commands;

use Psr\EventDispatcher\EventDispatcherInterface;
use Spiral\Console\Command;
use Spiral\Scheduler\Event\JobFailed;
use Spiral\Scheduler\Event\JobFinished;
use Spiral\Scheduler\Event\JobStarting;
use Spiral\Scheduler\Job\Job;
use Spiral\Scheduler\Schedule;
use Spiral\Snapshots\SnapshotterInterface;

final class ScheduleRunCommand extends Command
{
    protected const NAME = 'schedule:run';
    protected const DESCRIPTION = 'Run the scheduled jobs';

    private ?SnapshotterInterface $snapshotter;
    private ?EventDispatcherInterface $dispatcher;

    public function perform(
        Schedule $schedule,
        SnapshotterInterface $snapshotter = null,
        EventDispatcherInterface $dispatcher = null
    ): int {
        $this->dispatcher = $dispatcher;
        $this->snapshotter = $snapshotter;

        $jobsRan = false;

        foreach ($schedule->dueJobs() as $job) {
            if (! $job->filtersPass($this->container)) {
                continue;
            }

            $this->runJob($job);
            $jobsRan = true;
        }

        if (! $jobsRan) {
            $this->writeln('No scheduled jobs are ready to run.');
        }

        return self::SUCCESS;
    }

    private function runJob(Job $job): void
    {
        $this->writeln(
            sprintf(
                '<info>[%s] Running scheduled job:</info> %s',
                date('c'),
                $job->getDescription() ?? $job->getSystemDescription()
            )
        );

        $this->dispatcher?->dispatch(new JobStarting($job));

        $start = microtime(true);

        try {
            $job->run($this->container);

            $this->dispatcher?->dispatch(new JobFinished($job, round(microtime(true) - $start, 2)));
        } catch (\Throwable $e) {
            $this->dispatcher?->dispatch(new JobFailed($job, $e));
            $this->snapshotter?->register($e);
        }
    }
}
