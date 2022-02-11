<?php

declare(strict_types=1);

namespace Spiral\Scheduler\Commands;

use Psr\EventDispatcher\EventDispatcherInterface;
use Spiral\Console\Command;
use Spiral\Scheduler\Event\BackgroundJobFinished;
use Spiral\Scheduler\Schedule;
use Symfony\Component\Console\Input\InputArgument;

final class ScheduleFinishCommand extends Command
{
    protected const NAME = 'schedule:finish';
    protected const DESCRIPTION = 'Handle the completion of a scheduled in background jobs';

    public const ARGUMENTS = [
        ['id', InputArgument::REQUIRED, 'Job id'],
        ['code', InputArgument::OPTIONAL, 'Exit code', 0],
    ];

    public function perform(
        Schedule $schedule,
        EventDispatcherInterface $dispatcher = null
    ): int {
        $id = $this->argument('id');
        $exitCode = $this->argument('code') ?? 0;

        foreach ($schedule->getJobs() as $job) {
            if ($job->getId() !== $id) {
                continue;
            }

            $job->finish($this->container, (int)$exitCode);
            $dispatcher?->dispatch(new BackgroundJobFinished($job));
        }

        return self::SUCCESS;
    }
}
