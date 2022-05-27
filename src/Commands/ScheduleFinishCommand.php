<?php

declare(strict_types=1);

namespace Spiral\Scheduler\Commands;

use Psr\EventDispatcher\EventDispatcherInterface;
use Spiral\Console\Command;
use Spiral\Scheduler\Event\BackgroundJobFinished;
use Spiral\Scheduler\JobRegistryInterface;
use Symfony\Component\Console\Input\InputArgument;

final class ScheduleFinishCommand extends Command
{
    protected const SIGNATURE = 'schedule:finish {id : Job id} {code=0 : Exit code}';
    protected const DESCRIPTION = 'Handle the completion of a scheduled in background jobs';

    public function perform(
        JobRegistryInterface $registry,
        EventDispatcherInterface $dispatcher = null
    ): int {
        $id = $this->argument('id');
        $exitCode = $this->argument('code') ?? 0;

        foreach ($registry->getJobs() as $job) {
            if ($job->getId() !== $id) {
                continue;
            }

            $job->finish($this->container, (int)$exitCode);
            $dispatcher?->dispatch(new BackgroundJobFinished($job));
        }

        return self::SUCCESS;
    }
}
