<?php

declare(strict_types=1);

namespace Spiral\Scheduler\Commands;

use Spiral\Console\Command;
use Spiral\Scheduler\Schedule;
use Symfony\Component\Console\Input\InputArgument;

final class ScheduleFinishCommand extends Command
{
    protected const NAME = 'schedule:finish';
    protected const DESCRIPTION = 'Handle the completion of a scheduled in background command';

    public const ARGUMENTS = [
        ['id', InputArgument::REQUIRED, 'Event id'],
        ['code', InputArgument::OPTIONAL, 'Exit code', 0],
    ];

    public function perform(Schedule $schedule): int
    {
        $id = $this->argument('id');
        $exitCode = $this->argument('code') ?? 0;

        foreach ($schedule->events() as $event) {
            if ($event->getId() !== $id) {
                continue;
            }

            $event->finish($this->container, (int)$exitCode);
        }

        return self::SUCCESS;
    }
}
