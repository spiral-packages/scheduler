<?php

declare(strict_types=1);

namespace Spiral\Scheduler\Commands;

use Carbon\Carbon;
use Spiral\Console\Command;
use Spiral\Scheduler\Config\SchedulerConfig;
use Spiral\Scheduler\JobHandlerInterface;
use Spiral\Scheduler\JobRegistryInterface;

final class ScheduleRunCommand extends Command
{
    protected const NAME = 'schedule:run';
    protected const DESCRIPTION = 'Run the scheduled jobs';

    public function perform(
        JobRegistryInterface $registry,
        JobHandlerInterface $jobHandler,
        SchedulerConfig $config,
    ): int {
        $date = Carbon::now($config->getTimezone());

        $jobsRan = false;

        foreach ($registry->getDueJobs($date) as $job) {
            if (! $job->filtersPass($this->container)) {
                continue;
            }

            $this->info(
                \sprintf(
                    '[%s] Running scheduled job: %s',
                    \date('c'),
                    $job->getDescription() ?? $job->getSystemDescription()
                )
            );

            $jobHandler->handle($job);
            $jobsRan = true;
        }

        if (! $jobsRan) {
            $this->info('No scheduled jobs are ready to run.');
        }

        return self::SUCCESS;
    }
}
