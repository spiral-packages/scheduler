<?php

declare(strict_types=1);

namespace Spiral\Scheduler\Commands;

use Spiral\Console\Command;
use Spiral\Scheduler\PeriodicCommandRunnerInterface;

final class ScheduleWorkCommand extends Command
{
    protected const NAME = 'schedule:work';
    protected const DESCRIPTION = 'Start the schedule worker';

    public function perform(
        PeriodicCommandRunnerInterface $runner
    ): int {
        $this->writeln('Schedule worker started successfully.');

        $runner->run(
            'schedule:run',
            function (string $message) {
                $this->writeln(\sprintf('<fg=green>%s</>', $message));
            },
            function (string $message) {
                $this->writeln(\sprintf('<fg=red>%s</>', $message));
            }
        );

        return self::SUCCESS;
    }
}
